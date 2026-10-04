import { resolve } from 'node:path';
import { parseArgs } from 'node:util';
import { type Db, schema } from '@lexican/db';
import { drizzle } from 'drizzle-orm/node-postgres';
import mysql from 'mysql2/promise';
import pg from 'pg';
import { loadLegacy } from './legacy.ts';
import { migrateLegacy } from './migrate.ts';
import { writeReport } from './report.ts';

const USAGE = `Uso: bun run migrate:legacy --source mysql://… --target postgres://… \\
  --media-source <storage/app/public legacy> --media-target <raíz de medios nueva> \\
  [--dry-run] [--report informe.json] [--fail-on-orphans]

El destino debe tener el esquema aplicado (bun run db:migrate). Ver docs/MIGRATION.md.`;

/** Paths are relative to where the command was launched (INIT_CWD when a package manager sets it), not this workspace. */
const fromCaller = (p: string) => resolve(process.env.INIT_CWD ?? process.cwd(), p);
const redact = (url: string) => url.replace(/\/\/([^:/@]+):[^@]*@/, '//$1:***@');

async function main(): Promise<number> {
  const { values: a } = parseArgs({
    options: {
      source: { type: 'string' },
      target: { type: 'string' },
      'media-source': { type: 'string' },
      'media-target': { type: 'string' },
      'dry-run': { type: 'boolean', default: false },
      report: { type: 'string', default: 'legacy-migration-report.json' },
      'fail-on-orphans': { type: 'boolean', default: false },
      help: { type: 'boolean', default: false },
    },
  });
  if (a.help || !a.source || !a.target || !a['media-source'] || !a['media-target']) {
    console.error(USAGE);
    return a.help ? 0 : 2;
  }
  // Laravel stored dates in UTC (config/app.php), so read them as UTC.
  const legacyConn = await mysql.createConnection({
    uri: a.source,
    timezone: 'Z',
    charset: 'utf8mb4',
    dateStrings: false,
  });
  const pool = new pg.Pool({ connectionString: a.target, max: 2 });
  try {
    const db = drizzle({ client: pool, schema }) as unknown as Db;
    const { rows } = await pool.query<{ t: string | null }>(
      `select to_regclass('public.users')::text as t`,
    );
    if (!rows[0]?.t) {
      console.error(
        'El destino no tiene el esquema de LexiCán: ejecuta antes `bun run db:migrate`.',
      );
      return 2;
    }
    console.error(`Leyendo ${redact(a.source)}…`);
    const legacy = await loadLegacy(legacyConn);
    console.error(
      `Migrando a ${redact(a.target)}${a['dry-run'] ? ' (dry-run: se revierte al final)' : ''}…`,
    );
    const report = await migrateLegacy(db, legacy, {
      mediaSource: fromCaller(a['media-source']),
      mediaTarget: fromCaller(a['media-target']),
      dryRun: a['dry-run'],
      failOnOrphans: a['fail-on-orphans'],
    });
    const file = fromCaller(a.report);
    const csv = await writeReport(report, file);
    const failed = report.verification.filter((c) => !c.ok);
    console.error(
      [
        `Informe: ${file} (+ ${csv})`,
        `Duración: ${report.durationMs} ms · ${report.committed ? 'confirmado' : 'revertido'} · huérfanos: ${report.orphans}`,
        `Anomalías: ${Object.entries(report.anomalySummary)
          .map(([k, v]) => `${k}=${v}`)
          .join(', ')}`,
        `Medios: ${Object.entries(report.media)
          .map(([k, v]) => `${k}=${v}`)
          .join(', ')}`,
        ...failed.map((c) => `Verificación fallida: ${c.check}: ${c.detail}`),
        ...(report.failure ? [`ERROR: ${report.failure}`] : []),
      ].join('\n'),
    );
    return report.failure ? 1 : 0;
  } finally {
    await legacyConn.end();
    await pool.end();
  }
}

main().then(
  (code) => process.exit(code),
  (e: unknown) => {
    console.error(e instanceof Error ? e.message : e);
    process.exit(1);
  },
);
