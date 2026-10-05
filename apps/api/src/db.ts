import { existsSync, readdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { type Db, jsonbTextCodec } from '@lexican/db';
import { SQL } from 'bun';
import { migrate } from 'drizzle-orm/bun-sql/migrator';
import { bunSqlPgCodecs, drizzle } from 'drizzle-orm/bun-sql/postgres';

export function openDb(databaseUrl: string): { db: Db; client: SQL } {
  const client = new SQL({ url: databaseUrl, max: 10 });
  return { db: drizzle({ client, codecs: { ...bunSqlPgCodecs, jsonb: jsonbTextCodec } }), client };
}

/** drizzle-kit migrations: copied next to the bundle by the `build` script, or read from the workspace in dev. */
function migrationsFolder(): string {
  const here = dirname(fileURLToPath(import.meta.url));
  const candidates = [
    process.env.MIGRATIONS_DIR,
    join(here, 'migrations'), // dist/server.js
    join(here, '../migrations'), // dist/cli/*.js
    join(here, '../../../packages/db/migrations'), // apps/api/src (bun, tests)
  ];
  const dir = candidates.find(
    (d): d is string =>
      !!d &&
      existsSync(d) &&
      readdirSync(d).some((name) => existsSync(join(d, name, 'migration.sql'))),
  );
  if (!dir) throw new Error('Migrations folder not found (set MIGRATIONS_DIR).');
  return dir;
}

export async function runMigrations(client: SQL): Promise<void> {
  await migrate(drizzle({ client, codecs: { ...bunSqlPgCodecs, jsonb: jsonbTextCodec } }), {
    migrationsFolder: migrationsFolder(),
  });
}
