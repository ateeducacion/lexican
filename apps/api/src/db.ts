import { existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { schema, type Db } from '@lexican/db';
import { drizzle } from 'drizzle-orm/node-postgres';
import { migrate } from 'drizzle-orm/node-postgres/migrator';
import pg from 'pg';

export function openDb(databaseUrl: string): { db: Db; pool: pg.Pool } {
  const pool = new pg.Pool({ connectionString: databaseUrl, max: 10 });
  return { db: drizzle({ client: pool, schema }) as unknown as Db, pool };
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
  const dir = candidates.find((d): d is string => !!d && existsSync(join(d, 'meta/_journal.json')));
  if (!dir) throw new Error('Migrations folder not found (set MIGRATIONS_DIR).');
  return dir;
}

export async function runMigrations(pool: pg.Pool): Promise<void> {
  await migrate(drizzle({ client: pool }), { migrationsFolder: migrationsFolder() });
}
