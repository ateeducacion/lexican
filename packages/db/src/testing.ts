import { readdirSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { PGlite } from '@electric-sql/pglite';
import { drizzle as drizzlePg } from 'drizzle-orm/node-postgres';
import { drizzle as drizzlePglite } from 'drizzle-orm/pglite';
import pg from 'pg';
import type { Db } from './db.ts';
import { type Journal, migrateBundled } from './migrate.ts';
import * as schema from './schema.ts';

/** Node-side helpers to load migrations and open both drivers (tests, CLI). */

const migrationsDir = fileURLToPath(new URL('../migrations/', import.meta.url));

export function loadMigrations(): { journal: Journal; sqlByTag: Record<string, string> } {
  const journal = JSON.parse(readFileSync(`${migrationsDir}meta/_journal.json`, 'utf8')) as Journal;
  const sqlByTag = Object.fromEntries(
    readdirSync(migrationsDir)
      .filter((f) => f.endsWith('.sql'))
      .map((f) => [f.slice(0, -4), readFileSync(migrationsDir + f, 'utf8')]),
  );
  return { journal, sqlByTag };
}

export interface TestDb {
  name: 'pglite' | 'postgres';
  db: Db;
  close(): Promise<void>;
}

export async function openPglite(): Promise<TestDb> {
  const client = new PGlite();
  const db = drizzlePglite({ client, schema }) as unknown as Db;
  const { journal, sqlByTag } = loadMigrations();
  await migrateBundled(db, journal, sqlByTag);
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
  const db = drizzlePg({ client: pool, schema }) as unknown as Db;
  const { journal, sqlByTag } = loadMigrations();
  await migrateBundled(db, journal, sqlByTag);
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
