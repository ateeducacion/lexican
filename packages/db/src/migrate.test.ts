import { fileURLToPath } from 'node:url';
import { sql } from 'drizzle-orm';
import { readMigrationFiles } from 'drizzle-orm/migrator';
import { describe, expect, it } from 'vitest';
import { migrateBundled } from './migrate.ts';
import { appSettings, vocabularyValues } from './schema.ts';
import { seedVocabulary } from './seed/vocabulary.ts';
import { loadMigrations, openPglite, openPostgres } from './testing.ts';

const server = process.env.TEST_DATABASE_URL;
const migrations = readMigrationFiles({
  migrationsFolder: fileURLToPath(new URL('../migrations/', import.meta.url)),
});

for (const driver of ['pglite', 'postgres'] as const) {
  describe.skipIf(driver === 'postgres' && !server)(`migrations on ${driver}`, () => {
    const open = () => (driver === 'pglite' ? openPglite() : openPostgres(server!));

    it('matches the native migrator, applies each folder once and seeds idempotently', async () => {
      const t = await open();
      try {
        await migrateBundled(t.db, loadMigrations());
        await seedVocabulary(t.db);
        await seedVocabulary(t.db);
        const [row] = await t.db.select({ n: sql<number>`count(*)::int` }).from(vocabularyValues);
        expect(row?.n).toBe(360);
        const { rows: history } = (await t.db.execute(
          sql`select name, hash, created_at::text from drizzle.__drizzle_migrations order by name`,
        )) as unknown as { rows: { name: string; hash: string; created_at: string }[] };
        expect(history).toEqual(
          migrations.map((m) => ({
            name: m.name,
            hash: m.hash,
            created_at: String(m.folderMillis),
          })),
        );
      } finally {
        await t.close();
      }
    });

    it('upgrades v0 migration history without replaying SQL or losing existing data', async () => {
      const t = await open();
      try {
        await t.db.insert(appSettings).values({ key: 'migration-test', value: { retained: true } });
        // Only the first two migrations existed before the v1 upgrade.
        await t.db.execute(
          sql`delete from drizzle.__drizzle_migrations where created_at >= ${migrations[2]!.folderMillis}`,
        );
        await t.db.execute(
          sql`alter table drizzle.__drizzle_migrations drop column name, drop column applied_at`,
        );
        // v0 journal timestamps included milliseconds; v1 folder names retain whole seconds.
        await t.db.execute(
          sql`update drizzle.__drizzle_migrations set created_at = created_at + 137`,
        );
        await migrateBundled(t.db, loadMigrations());
        await migrateBundled(t.db, loadMigrations());
        expect(await t.db.select().from(appSettings)).toContainEqual({
          key: 'migration-test',
          value: { retained: true },
        });
        const { rows: history } = (await t.db.execute(
          sql`select name, applied_at is null as legacy from drizzle.__drizzle_migrations order by name`,
        )) as unknown as { rows: { name: string; legacy: boolean }[] };
        expect(history).toEqual(migrations.map((m, i) => ({ name: m.name, legacy: i < 2 })));
      } finally {
        await t.close();
      }
    });

    it('rejects an invalid migration folder name before applying SQL', async () => {
      const t = await open();
      try {
        await expect(migrateBundled(t.db, { invalid: 'select 1' })).rejects.toThrow(
          'Invalid migration folder name: invalid',
        );
      } finally {
        await t.close();
      }
    });
  });
}
