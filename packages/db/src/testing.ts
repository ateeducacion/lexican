import { readdirSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { PGlite } from '@electric-sql/pglite';
import { drizzle as drizzlePg } from 'drizzle-orm/node-postgres';
import { drizzle as drizzlePglite } from 'drizzle-orm/pglite';
import pg from 'pg';
import type { Db } from './db.ts';
import { migrateBundled } from './migrate.ts';

/** Node-side helpers to load migrations and open both drivers (tests, CLI). */

const migrationsDir = fileURLToPath(new URL('../migrations/', import.meta.url));

export function loadMigrations(): Record<string, string> {
  return Object.fromEntries(
    readdirSync(migrationsDir)
      .sort()
      .map((name) => [name, readFileSync(`${migrationsDir}${name}/migration.sql`, 'utf8')]),
  );
}

export interface TestDb {
  name: 'pglite' | 'postgres';
  db: Db;
  close(): Promise<void>;
}

export async function openPglite(): Promise<TestDb> {
  const client = new PGlite();
  const db: Db = drizzlePglite({ client });
  await migrateBundled(db, loadMigrations());
  return { name: 'pglite', db, close: () => client.close() };
}

/** Fresh database inside an existing Postgres server (TEST_DATABASE_URL), dropped on close. */
export async function openPostgres(url: string): Promise<TestDb> {
  const name = `lexican_t_${crypto.randomUUID().replaceAll('-', '').slice(0, 12)}`;
  const admin = new pg.Client({ connectionString: url });
  await admin.connect();
  await admin.query(`create database ${name}`);
  const target = new URL(url);
  target.pathname = `/${name}`;
  const pool = new pg.Pool({ connectionString: target.toString(), max: 4 });
  // `drop database … with (force)` terminates sessions that are still closing; that is expected at teardown.
  pool.on('error', () => undefined);
  const db: Db = drizzlePg({ client: pool });
  await migrateBundled(db, loadMigrations());
  return {
    name: 'postgres',
    db,
    async close() {
      await pool.end();
      await admin.query(`drop database ${name} with (force)`);
      await admin.end();
    },
  };
}
