import type { MigrationMeta } from 'drizzle-orm/migrator';
import { PgDialect, type PgSession } from 'drizzle-orm/pg-core';
import type { Db } from './db.ts';

export interface Journal {
  entries: { idx: number; when: number; tag: string; breakpoints: boolean }[];
}

async function sha256(text: string): Promise<string> {
  const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
  return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

/**
 * Apply drizzle-kit migrations from already-loaded SQL (browser bundle or tests). Writes the same
 * `drizzle.__drizzle_migrations` rows and hashes as `drizzle-orm/node-postgres/migrator`, so a database
 * migrated here and one migrated by the production CLI are interchangeable.
 * ponytail: relies on drizzle 0.45 journal layout; rewrite when moving to drizzle v1 (docs/adr/0003).
 */
export async function migrateBundled(
  db: Db,
  journal: Journal,
  sqlByTag: Record<string, string>,
): Promise<void> {
  const migrations: MigrationMeta[] = [];
  for (const e of journal.entries) {
    const query = sqlByTag[e.tag];
    if (query === undefined) throw new Error(`Missing migration SQL for ${e.tag}`);
    migrations.push({
      sql: query.split('--> statement-breakpoint'),
      bps: e.breakpoints,
      folderMillis: e.when,
      hash: await sha256(query),
    });
  }
  await new PgDialect().migrate(migrations, db._.session as unknown as PgSession, {
    migrationsFolder: '',
  });
}
