import type { MigrationMeta } from 'drizzle-orm/migrator';
import { formatToMillis } from 'drizzle-orm/migrator.utils';
import { migrate } from 'drizzle-orm/pg-core';
import type { Db } from './db.ts';

async function sha256(text: string): Promise<string> {
  const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
  return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

/**
 * Apply drizzle-kit migrations from already-loaded SQL (browser bundle or tests). Writes the same
 * `drizzle.__drizzle_migrations` rows and hashes as `drizzle-orm/bun-sql/migrator`, so a database
 * migrated here and one migrated by the production CLI are interchangeable.
 */
export async function migrateBundled(db: Db, sqlByName: Record<string, string>): Promise<void> {
  const migrations: MigrationMeta[] = [];
  for (const [name, query] of Object.entries(sqlByName).sort(([a], [b]) => a.localeCompare(b))) {
    const folderMillis = formatToMillis(name.slice(0, 14));
    if (!/^\d{14}_/.test(name) || !Number.isFinite(folderMillis))
      throw new Error(`Invalid migration folder name: ${name}`);
    migrations.push({
      name,
      sql: query.split('--> statement-breakpoint'),
      bps: true,
      folderMillis,
      hash: await sha256(query),
    });
  }
  await migrate(migrations, db, { migrationsFolder: '' });
}
