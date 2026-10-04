import { VocabularyValueInput } from '@lexican/core';
import type { Db } from '../db.ts';
import { vocabularyValues } from '../schema.ts';
import data from './vocabulary.json' with { type: 'json' };

/** Product vocabularies (legacy master tables, docs/DATA-MODEL.md). Idempotent: existing codes are kept. */
export async function seedVocabulary(db: Db): Promise<void> {
  const rows = data.map((v) => VocabularyValueInput.parse(v));
  for (let i = 0; i < rows.length; i += 200) {
    await db
      .insert(vocabularyValues)
      .values(rows.slice(i, i + 200))
      .onConflictDoNothing({ target: [vocabularyValues.vocabulary, vocabularyValues.code] });
  }
}
