import { readdirSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { PGlite } from '@electric-sql/pglite';
import { SQL } from 'bun';
import { bunSqlPgCodecs, drizzle as drizzlePg } from 'drizzle-orm/bun-sql/postgres';
import { drizzle as drizzlePglite } from 'drizzle-orm/pglite';
import { type Db, jsonbTextCodec } from './db.ts';
import { migrateBundled } from './migrate.ts';

/** Bun-only helpers (never imported by the browser) to load migrations and open both drivers (tests, CLI). */

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
  const admin = new SQL({ url, max: 1 });
  await admin.unsafe(`create database ${name}`);
  const target = new URL(url);
  target.pathname = `/${name}`;
  const client = new SQL({ url: target.toString(), max: 4 });
  const db: Db = drizzlePg({ client, codecs: { ...bunSqlPgCodecs, jsonb: jsonbTextCodec } });
  await migrateBundled(db, loadMigrations());
  return {
    name: 'postgres',
    db,
    async close() {
      await client.close();
      await admin.unsafe(`drop database ${name} with (force)`);
      await admin.close();
    },
  };
}
