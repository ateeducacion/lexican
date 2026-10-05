import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { type Db, jsonbTextCodec, migrateBundled } from '@lexican/db';
import { loadMigrations } from '@lexican/db/testing';
import { SQL } from 'bun';
import { bunSqlPgCodecs, drizzle } from 'drizzle-orm/bun-sql/postgres';
import mysql from 'mysql2/promise';
import { afterAll, afterEach, beforeAll, describe, expect, it, vi } from 'vitest';

/** `bun run migrate:legacy` runs cli.ts as a script: each import parses argv, runs, and calls process.exit. */
async function cli(args: string[]): Promise<{ code: number; stderr: string }> {
  const lines: string[] = [];
  vi.spyOn(console, 'error').mockImplementation((...a: unknown[]) => void lines.push(a.join(' ')));
  const exit = vi
    .spyOn(process, 'exit')
    .mockImplementation((() => undefined) as typeof process.exit);
  const argv = process.argv;
  process.argv = ['node', 'cli.ts', ...args];
  try {
    vi.resetModules();
    await import('./cli.ts');
    await vi.waitFor(() => expect(exit).toHaveBeenCalled(), { timeout: 60_000, interval: 20 });
  } finally {
    process.argv = argv;
  }
  return { code: exit.mock.calls[0]![0] as number, stderr: lines.join('\n') };
}

afterEach(() => {
  vi.restoreAllMocks();
  vi.unstubAllEnvs();
});

describe('legacy migrator CLI arguments', () => {
  it('--help prints the usage and exits 0', async () => {
    const r = await cli(['--help']);
    expect(r).toMatchObject({
      code: 0,
      stderr: expect.stringMatching(/^Uso: bun run migrate:legacy/),
    });
  });

  it('missing required options print the usage and exit 2', async () => {
    const r = await cli(['--source', 'mysql://x', '--target', 'postgres://y']);
    expect(r.code).toBe(2);
    expect(r.stderr).toContain('--media-source');
  });

  it('unknown options exit 1 with the parser message', async () => {
    const r = await cli(['--sourse', 'x']);
    expect(r.code).toBe(1);
    expect(r.stderr).toMatch(/Unknown option '--sourse'/);
  });

  it('an unreachable source exits 1 without printing its password', async () => {
    const r = await cli([
      '--source',
      'mysql://lector:no-es-secreto@127.0.0.1:1/legacy',
      '--target',
      'postgres://x:y@127.0.0.1:1/z',
      '--media-source',
      '.',
      '--media-target',
      '.',
    ]);
    expect(r.code).toBe(1);
    expect(r.stderr).not.toContain('no-es-secreto');
  });
});

const MARIADB = process.env.TEST_MARIADB_URL;
const PG = process.env.TEST_DATABASE_URL;
const fixtures = fileURLToPath(new URL('../../../fixtures/legacy/', import.meta.url));

describe.skipIf(!MARIADB || !PG)('legacy migrator CLI against the fixture', () => {
  const legacyDb = `legacy_c_${crypto.randomUUID().replaceAll('-', '').slice(0, 10)}`;
  const targets: string[] = [];
  let admin: mysql.Connection;
  let pgAdmin: SQL;
  let work: string;
  let source: string;

  /** Fresh target database, optionally with the LexiCán schema applied. */
  async function target(migrated: boolean): Promise<string> {
    const name = `lexican_c_${crypto.randomUUID().replaceAll('-', '').slice(0, 12)}`;
    await pgAdmin.unsafe(`create database ${name}`);
    targets.push(name);
    const url = new URL(PG!);
    url.pathname = `/${name}`;
    if (migrated) {
      const client = new SQL({ url: url.toString(), max: 1 });
      const db: Db = drizzle({ client, codecs: { ...bunSqlPgCodecs, jsonb: jsonbTextCodec } });
      await migrateBundled(db, loadMigrations());
      await client.close();
    }
    return url.toString();
  }

  beforeAll(async () => {
    admin = await mysql.createConnection({ uri: MARIADB!, multipleStatements: true });
    await admin.query(
      `create database ${legacyDb} character set utf8mb4 collate utf8mb4_unicode_ci`,
    );
    await admin.query(`use ${legacyDb}`);
    await admin.query(await readFile(join(fixtures, 'schema.sql'), 'utf8'));
    await admin.query(await readFile(join(fixtures, 'data.sql'), 'utf8'));
    const u = new URL(MARIADB!);
    u.pathname = `/${legacyDb}`;
    source = u.toString();
    pgAdmin = new SQL({ url: PG!, max: 1 });
    work = await mkdtemp(join(tmpdir(), 'lexican-cli-'));
  }, 60_000);

  afterAll(async () => {
    for (const t of targets) await pgAdmin?.unsafe(`drop database if exists ${t} with (force)`);
    await pgAdmin?.close();
    await admin?.query(`drop database if exists ${legacyDb}`);
    await admin?.end();
    if (work) await rm(work, { recursive: true, force: true });
  });

  const args = (t: string, extra: string[] = []) => [
    '--source',
    source,
    '--target',
    t,
    '--media-source',
    join(fixtures, 'media'),
    '--media-target',
    join(work, 'media'),
    ...extra,
  ];

  it('refuses a target without the schema (exit 2)', async () => {
    const r = await cli(args(await target(false)));
    expect(r.code).toBe(2);
    expect(r.stderr).toContain('bun run db:migrate');
  });

  it('dry-run writes the JSON and CSV report relative to the caller, redacts passwords and exits 0', async () => {
    vi.stubEnv('INIT_CWD', work);
    const r = await cli(args(await target(true), ['--dry-run', '--report', 'informe.json']));
    expect(r.code).toBe(0);
    expect(r.stderr).toContain(
      `Informe: ${join(work, 'informe.json')} (+ ${join(work, 'informe.csv')})`,
    );
    expect(r.stderr).toMatch(/revertido · huérfanos: \d+/);
    expect(r.stderr).toContain('(dry-run: se revierte al final)');
    const password = new URL(MARIADB!).password;
    if (password) expect(r.stderr).not.toContain(`:${password}@`);
    expect(r.stderr).toMatch(/Leyendo mysql:\/\/[^:]+:\*\*\*@/);
    const report = JSON.parse(await readFile(join(work, 'informe.json'), 'utf8'));
    expect(report).toMatchObject({
      tool: 'lexican-legacy-migrator',
      dryRun: true,
      committed: false,
      failure: null,
    });
    const csv = (await readFile(join(work, 'informe.csv'), 'utf8')).split('\n');
    expect(csv[0]).toBe('table,legacy_id,class,message');
    expect(csv.length - 2).toBe(report.anomalies.length);
  });

  it('--fail-on-orphans exits 1 and prints the error', async () => {
    vi.stubEnv('INIT_CWD', work);
    const r = await cli(
      args(await target(true), ['--fail-on-orphans', '--report', 'huerfanos.json']),
    );
    expect(r.code).toBe(1);
    expect(r.stderr).toMatch(/ERROR: \d+ registros huérfanos con --fail-on-orphans/);
  });
});
