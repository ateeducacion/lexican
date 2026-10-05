import { is, sql } from 'drizzle-orm';
import { getTableConfig, PgTable } from 'drizzle-orm/pg-core';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { queryRows } from './db.ts';
import * as schema from './schema.ts';
import { openPglite, type TestDb } from './testing.ts';

/** Every foreign key declared in schema.ts, as `table.column → table.column (on delete)`. */
const declared = Object.values(schema as Record<string, unknown>)
  .filter((v): v is PgTable => is(v, PgTable))
  .flatMap((table) => {
    const cfg = getTableConfig(table);
    return cfg.foreignKeys.map((fk) => {
      const { columns, foreignColumns, foreignTable } = fk.reference();
      expect(columns).toHaveLength(1);
      return `${cfg.name}.${columns[0]!.name} → ${getTableConfig(foreignTable).name}.${foreignColumns[0]!.name} (${fk.onDelete ?? 'no action'})`;
    });
  })
  .sort();

describe('schema integrity', () => {
  let t: TestDb;
  beforeAll(async () => {
    t = await openPglite();
  });
  afterAll(() => t.close());

  it('the migrations create exactly the foreign keys declared in schema.ts', async () => {
    const rows = await queryRows<{ t: string; c: string; ft: string; fc: string; del: string }>(
      t.db,
      sql`
      select c.conrelid::regclass::text as t, a.attname as c, c.confrelid::regclass::text as ft, af.attname as fc,
             case c.confdeltype when 'c' then 'cascade' when 'n' then 'set null' when 'r' then 'restrict' else 'no action' end as del
      from pg_constraint c
      join pg_attribute a on a.attrelid = c.conrelid and a.attnum = c.conkey[1]
      join pg_attribute af on af.attrelid = c.confrelid and af.attnum = c.confkey[1]
      where c.contype = 'f'
    `,
    );
    expect(rows.map((r) => `${r.t}.${r.c} → ${r.ft}.${r.fc} (${r.del})`).sort()).toEqual(declared);
    expect(declared).toHaveLength(39);
  });

  it('deleting a dictionary removes its content; history keeps people and reviews', () => {
    expect(declared).toEqual(
      expect.arrayContaining([
        // A classroom or personal dictionary owns everything inside it.
        'entries.dictionary_id → dictionaries.id (cascade)',
        'dictionary_memberships.dictionary_id → dictionaries.id (cascade)',
        'classroom_settings.dictionary_id → dictionaries.id (cascade)',
        'submissions.classroom_id → dictionaries.id (cascade)',
        'comments.classroom_id → dictionaries.id (cascade)',
        'entry_senses.entry_id → entries.id (cascade)',
        'sense_topics.sense_id → entry_senses.id (cascade)',
        'sense_media.sense_id → entry_senses.id (cascade)',
        // Removing a person never silently removes authored content or audit history.
        'dictionaries.owner_id → users.id (no action)',
        'comments.author_id → users.id (no action)',
        'audit_events.actor_id → users.id (set null)',
        'auth_identities.user_id → users.id (cascade)',
        'sessions.user_id → users.id (cascade)',
        // Provenance links between entries and submissions (both directions).
        'entries.source_submission_id → submissions.id (no action)',
        'submissions.published_entry_id → entries.id (no action)',
        'submissions.revision_id → entry_revisions.id (no action)',
        'comments.submission_id → submissions.id (set null)',
        // Media assets are shared by reference and never cascade away.
        'sense_media.media_id → media_assets.id (no action)',
      ]),
    );
  });

  it('vocabulary references always point at vocabulary_values', () => {
    const vocab = declared.filter((d) => d.includes('→ vocabulary_values.'));
    expect(vocab.map((d) => d.split(' → ')[0]).sort()).toEqual([
      'classroom_settings.dictionary_type_id',
      'classroom_settings.education_stage_id',
      'classroom_settings.study_level_id',
      'classroom_settings.subject_id',
      'entry_senses.gender_id',
      'entry_senses.language_id',
      'entry_senses.number_id',
      'entry_senses.part_of_speech_id',
      'sense_topics.topic_id',
    ]);
  });
});
