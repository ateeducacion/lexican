import { describe, expect, it } from 'vitest';
import { sql } from 'drizzle-orm';
import { seedVocabulary } from './seed/vocabulary.ts';
import { vocabularyValues } from './schema.ts';
import { loadMigrations, openPglite } from './testing.ts';
import { migrateBundled } from './migrate.ts';

describe('schema on PGlite', () => {
  it('migrates idempotently and seeds vocabularies', async () => {
    const t = await openPglite();
    const { journal, sqlByTag } = loadMigrations();
    await migrateBundled(t.db, journal, sqlByTag);
    await seedVocabulary(t.db);
    await seedVocabulary(t.db);
    const [row] = await t.db.select({ n: sql<number>`count(*)::int` }).from(vocabularyValues);
    expect(row?.n).toBe(360);
    await t.close();
  });
});
