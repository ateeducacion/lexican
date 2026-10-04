import { createHash } from 'node:crypto';
import { mkdtemp, readdir, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { type Db, seedVocabulary } from '@lexican/db';
import { openPostgres, type TestDb } from '@lexican/db/testing';
import { sql } from 'drizzle-orm';
import mysql from 'mysql2/promise';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { type LegacyData, loadLegacy } from './legacy.ts';
import { type MigrateOptions, migrateLegacy } from './migrate.ts';

/**
 * legacy fixture (MariaDB) → migrator → PostgreSQL → assertions (§79). Runs only with both servers:
 *   docker run -d --name lexican-mariadb -e MARIADB_ROOT_PASSWORD=root -e MARIADB_DATABASE=legacy -p 53306:3306 mariadb:11
 *   TEST_MARIADB_URL=mysql://root:root@127.0.0.1:53306/legacy TEST_DATABASE_URL=postgres://… npx vitest run tools/legacy-migrator
 */
const MARIADB = process.env.TEST_MARIADB_URL;
const PG = process.env.TEST_DATABASE_URL;
const fixtures = fileURLToPath(new URL('../../../fixtures/legacy/', import.meta.url));
const FAKE_PII = /00000000T|11111111H|CIAL000|XX0000001/;

async function rows<T>(db: Db, q: ReturnType<typeof sql>): Promise<T[]> {
  return ((await db.execute(q)) as unknown as { rows: T[] }).rows;
}
const COUNTED = [
  'users',
  'auth_identities',
  'schools',
  'user_schools',
  'vocabulary_values',
  'dictionaries',
  'classroom_settings',
  'dictionary_memberships',
  'entries',
  'entry_senses',
  'sense_topics',
  'media_assets',
  'sense_media',
  'entry_revisions',
  'submissions',
  'comments',
] as const;
async function counts(db: Db): Promise<Record<string, number>> {
  const out: Record<string, number> = {};
  for (const t of COUNTED)
    out[t] = Number(
      (await rows<{ n: number }>(db, sql`select count(*)::int as n from ${sql.identifier(t)}`))[0]!
        .n,
    );
  return out;
}

describe.skipIf(!MARIADB || !PG)('legacy migration (MariaDB fixture → PostgreSQL)', () => {
  let target: TestDb;
  let db: Db;
  let legacy: LegacyData;
  let media: string;
  let opts: MigrateOptions;
  const legacyDb = `legacy_t_${crypto.randomUUID().replaceAll('-', '').slice(0, 10)}`;
  let admin: mysql.Connection;

  beforeAll(async () => {
    admin = await mysql.createConnection({
      uri: MARIADB!,
      multipleStatements: true,
      timezone: 'Z',
    });
    await admin.query(
      `create database ${legacyDb} character set utf8mb4 collate utf8mb4_unicode_ci`,
    );
    await admin.query(`use ${legacyDb}`);
    await admin.query(await readFile(join(fixtures, 'schema.sql'), 'utf8'));
    await admin.query(await readFile(join(fixtures, 'data.sql'), 'utf8'));
    legacy = await loadLegacy(admin);
    target = await openPostgres(PG!);
    db = target.db;
    await seedVocabulary(db);
    media = await mkdtemp(join(tmpdir(), 'lexican-media-'));
    opts = {
      mediaSource: join(fixtures, 'media'),
      mediaTarget: media,
      dryRun: false,
      failOnOrphans: false,
    };
  }, 60_000);

  afterAll(async () => {
    await target?.close();
    await admin?.query(`drop database if exists ${legacyDb}`);
    await admin?.end();
    if (media) await rm(media, { recursive: true, force: true });
  });

  it('dry-run reports everything and leaves the target and media untouched', async () => {
    const before = await counts(db);
    const r = await migrateLegacy(db, legacy, { ...opts, dryRun: true });
    expect(r.failure).toBeNull();
    expect(r.committed).toBe(false);
    expect(r.tables.dp_entradas!.mapped).toBe(7);
    expect(r.media.migrated).toBe(4);
    expect(await counts(db)).toEqual(before);
    expect(await readdir(media)).toEqual([]);
  });

  it('migrates the fixture and passes every verification', async () => {
    const r = await migrateLegacy(db, legacy, opts);
    expect(r.failure).toBeNull();
    expect(r.committed).toBe(true);
    expect(r.verification.filter((c) => !c.ok)).toEqual([]);
    expect(r.verification.map((c) => c.check)).toContain('medios copiados con checksum correcto');
    expect(await counts(db)).toMatchObject({
      users: 5, // 4 accounts + 1 persona without account (disabled)
      auth_identities: 4,
      schools: 1,
      dictionaries: 5, // 3 personal + 2 classroom
      entries: 11, // 7 personal + 1 placeholder + 3 classroom
      submissions: 5,
      comments: 2,
      media_assets: 4,
      dictionary_memberships: 5,
    });

    // Report: classes, orphans and per-table metrics.
    const msg = (cls: string) =>
      r.anomalies.filter((a) => a.class === cls).map((a) => `${a.table}:${a.legacyId}`);
    expect(msg('cannot migrate')).toEqual(
      expect.arrayContaining([
        'dp_acepciones_medios:3006',
        'dp_acepciones_medios:3004',
        'envios_acepciones_medios:822',
      ]),
    );
    expect(msg('repairable automatically')).toEqual(
      expect.arrayContaining([
        'dp_entradas:1000',
        'dp_acepciones_medios:3001',
        'envios_entradas:803',
        'centros:3',
        'dic_aula:500', // legacy code «abc123» uses 1: regenerated (SEC-002)
        'dic_aula:501',
      ]),
    );
    expect(msg('needs human review')).toEqual(
      expect.arrayContaining([
        'dp_entradas:1005',
        'dic_aula_pautas:1',
        'mst_campos_valores:31',
        'personas:13',
        'media:null',
      ]),
    );
    expect(r.orphans).toBe(1);
    expect(r.tables.envios_entradas).toMatchObject({ legacy: 5, mapped: 5, orphaned: 1, new: 5 });
    expect(r.tables.dp_acepciones).toMatchObject({ legacy: 8, mapped: 7, skipped: 1 });
    expect(r.notMigrated.audits).toMatchObject({ count: 2 });
    expect(r.notMigrated.dic_aula_destinatario_avisos).toMatchObject({ count: 1 });
    expect(r.media).toMatchObject({
      migrated: 4,
      sameContent: 2,
      missing: 1,
      corrupt: 1,
      duplicates: 1,
      unreferenced: 1,
      brokenRefs: 1,
      thumbnailsIgnored: 1,
    });
  });

  it('never migrates national ids', async () => {
    const leaked = await rows<{ t: string }>(
      db,
      sql`select 'users' t from users u where row_to_json(u)::text ~ ${FAKE_PII.source}
          union all select 'auth' from auth_identities a where row_to_json(a)::text ~ ${FAKE_PII.source}`,
    );
    expect(leaked).toEqual([]);
    const ids = await rows<{ subject: string }>(
      db,
      sql`select subject from auth_identities order by subject`,
    );
    expect(ids.map((i) => i.subject)).toEqual([
      'admin.ficticio',
      'alu.lopez',
      'alu.perez',
      'prof.garcia',
    ]);
    const people = await rows<{ display_name: string; global_role: string; email: string | null }>(
      db,
      sql`select display_name, global_role, email from users where legacy_source = 'users' order by legacy_id`,
    );
    expect(people).toEqual([
      { display_name: 'Usuario legacy 1', global_role: 'admin', email: 'admin@example.org' },
      { display_name: 'Marta García Ficticia', global_role: 'teacher', email: null }, // CAS id, not an address
      {
        display_name: 'Pablo Pérez Inventado',
        global_role: 'student',
        email: 'alu.perez@example.org',
      },
      { display_name: 'Lucía López Ejemplo', global_role: 'student', email: null },
    ]);
  });

  it('keeps sense order, states, vocabularies, topics and the newest media of each kind', async () => {
    const senses = await rows<{
      position: number;
      definition: string;
      hidden: boolean;
      lang: string | null;
      foreign_form: string;
      extra_info: string;
      example: string;
    }>(
      db,
      sql`select s.position, s.definition, s.hidden, v.code as lang, s.foreign_form, s.extra_info, s.example
          from entry_senses s join entries e on e.id = s.entry_id left join vocabulary_values v on v.id = s.language_id
          where e.legacy_source = 'dp_entradas' and e.legacy_id = 1000 order by s.position`,
    );
    expect(senses.map((s) => [s.position, s.definition, s.hidden])).toEqual([
      [1, 'Planta de tronco leñoso.', false],
      [2, 'Segunda acepción.', true],
      [3, 'Tercera acepción.', false],
    ]);
    expect(senses[0]).toMatchObject({
      lang: 'ingles',
      foreign_form: 'tree',
      extra_info: 'Más datos de prueba',
      example: 'El árbol da sombra.',
    });
    expect(senses[1]!.lang).toBe('elfico_ficticio');

    const states = await rows<{ legacy_id: number; hidden: boolean; deleted: boolean }>(
      db,
      sql`select legacy_id, hidden, deleted_at is not null as deleted from entries where legacy_source = 'dp_entradas' order by legacy_id`,
    );
    expect(states.filter((s) => s.hidden).map((s) => s.legacy_id)).toEqual([1001]);
    expect(states.filter((s) => s.deleted).map((s) => s.legacy_id)).toEqual([1002, 1005]);

    const topics = await rows<{ code: string }>(
      db,
      sql`select v.code from sense_topics t join vocabulary_values v on v.id = t.topic_id
          join entry_senses s on s.id = t.sense_id where s.legacy_source = 'dp_acepciones' and s.legacy_id = 2002 order by v.code`,
    );
    expect(topics.map((t) => t.code)).toEqual(['fauna_canaria', 'gastronomia']);

    const attached = await rows<{ kind: string; sha256: string; storage_key: string }>(
      db,
      sql`select m.kind, m.sha256, m.storage_key from sense_media sm join media_assets m on m.id = sm.media_id
          join entry_senses s on s.id = sm.sense_id where s.legacy_source = 'dp_acepciones' and s.legacy_id = 2002 order by m.kind`,
    );
    expect(attached.map((a) => a.kind)).toEqual(['image', 'audio']);
    const newest = await readFile(join(fixtures, 'media/dp/medios/imagenes/11_1000_2002_bbbb.png'));
    expect(attached[0]!.sha256).toBe(createHash('sha256').update(newest).digest('hex'));
    for (const a of attached) {
      expect(a.storage_key).toMatch(/^[0-9a-f-]{36}\.(png|mp3)$/);
      const copied = await readFile(join(media, a.storage_key.slice(0, 2), a.storage_key));
      expect(createHash('sha256').update(copied).digest('hex')).toBe(a.sha256);
    }
    // Same bytes under another name → one shared asset.
    const shared = await rows<{ n: number }>(
      db,
      sql`select count(distinct sm.media_id)::int n from sense_media sm join entry_senses s on s.id = sm.sense_id
          where s.legacy_source = 'dp_acepciones' and s.legacy_id in (2002, 2005) and sm.kind = 'image'`,
    );
    expect(shared[0]!.n).toBe(1);
  });

  it('migrates classrooms, settings and members', async () => {
    const cs = await rows<Record<string, unknown>>(
      db,
      sql`select d.legacy_id, c.join_code, c.timeless, c.validity_years, c.max_senses, c.visible_fields, c.required_fields,
          c.comments_visibility, c.group_label, c.guidelines, c.submissions_enabled, c.submissions_end_at,
          (select code from vocabulary_values where id = c.subject_id) subject,
          (select code from vocabulary_values where id = c.study_level_id) level,
          (select code from vocabulary_values where id = c.education_stage_id) stage
          from classroom_settings c join dictionaries d on d.id = c.dictionary_id order by d.legacy_id`,
    );
    expect(cs[0]).toMatchObject({
      timeless: false,
      validity_years: 2,
      max_senses: null,
      visible_fields: [
        'part_of_speech',
        'topics',
        'extra_info',
        'audio',
        'image',
        'language',
        'example',
      ],
      required_fields: ['part_of_speech', 'example'],
      comments_visibility: 'before_date',
      group_label: 'A',
      guidelines:
        'Normas del aula:\n\n- Una entrada por palabra\n- Revisa la ortografía & los acentos',
      submissions_enabled: true,
      submissions_end_at: null,
      subject: 'interdisciplinar',
      level: '1_eso',
      stage: 'eso',
    });
    expect(cs[1]).toMatchObject({
      timeless: true,
      max_senses: 5,
      group_label: '',
      comments_visibility: 'hidden',
      subject: 'robotica_ficticia',
    });
    expect(String(cs[0]!.join_code)).toMatch(/^[A-HJ-NP-Z2-9]{6}$/);
    expect(String(cs[1]!.join_code)).toMatch(/^[A-HJ-NP-Z2-9]{6}$/);
    expect((cs[1]!.visible_fields as string[]).length).toBe(10);

    const members = await rows<{ aula: number; name: string; role: string; active: boolean }>(
      db,
      sql`select d.legacy_id aula, u.display_name name, m.role, m.active from dictionary_memberships m
          join dictionaries d on d.id = m.dictionary_id join users u on u.id = m.user_id order by d.legacy_id, u.display_name`,
    );
    expect(members).toEqual([
      { aula: 500, name: 'Lucía López Ejemplo', role: 'student', active: false },
      { aula: 500, name: 'Marta García Ficticia', role: 'teacher', active: true },
      { aula: 500, name: 'Pablo Pérez Inventado', role: 'student', active: true },
      { aula: 501, name: 'Marta García Ficticia', role: 'teacher', active: true },
      { aula: 501, name: 'Pablo Pérez Inventado', role: 'student', active: true },
    ]);
  });

  it('turns envíos into submissions with immutable snapshots and published entries with provenance', async () => {
    const subs = await rows<{
      legacy_id: number;
      status: string;
      headword: string;
      source: string;
      snap: { headword: string; senses: { definition: string; position: number }[] };
      published: number | null;
      reason: string;
    }>(
      db,
      sql`select s.legacy_id, s.status, e.headword, e.legacy_source source, r.snapshot snap, r.reason,
          (select legacy_id from entries where id = s.published_entry_id) published
          from submissions s join entries e on e.id = s.source_entry_id join entry_revisions r on r.id = s.revision_id order by s.legacy_id`,
    );
    expect(subs.map((s) => [s.legacy_id, s.status, s.published])).toEqual([
      [800, 'published', 900],
      [801, 'pending', null],
      [802, 'withdrawn', null],
      [803, 'published', 901],
      [804, 'pending', null],
    ]);
    expect(subs.every((s) => s.reason === 'submit')).toBe(true);
    // The snapshot is the (teacher-edited) envíos copy without the hidden sense.
    expect(subs[0]!.snap.senses).toEqual([
      expect.objectContaining({
        position: 1,
        definition: 'Edificio para vivir (revisado por la profesora).',
        example: 'Mi casa es azul.',
      }),
    ]);
    // Orphan envío: kept on a deleted placeholder personal entry.
    expect(subs[3]).toMatchObject({ headword: 'Luna', source: 'envios_entradas' });

    const published = await rows<{
      legacy_id: number;
      aula: number;
      sub: number;
      senses: number;
      hidden_senses: number;
      author: string;
    }>(
      db,
      sql`select e.legacy_id, d.legacy_id aula, s.legacy_id sub, u.display_name author,
          (select count(*)::int from entry_senses where entry_id = e.id) senses,
          (select count(*)::int from entry_senses where entry_id = e.id and hidden) hidden_senses
          from entries e join dictionaries d on d.id = e.dictionary_id join submissions s on s.id = e.source_submission_id
          join users u on u.id = e.created_by where e.legacy_source = 'dic_aula_entradas' order by e.legacy_id`,
    );
    expect(published).toEqual([
      {
        legacy_id: 900,
        aula: 500,
        sub: 800,
        senses: 2,
        hidden_senses: 1,
        author: 'Pablo Pérez Inventado',
      },
      {
        legacy_id: 901,
        aula: 500,
        sub: 803,
        senses: 1,
        hidden_senses: 0,
        author: 'Lucía López Ejemplo',
      },
      // Imported into a second classroom: same source submission.
      {
        legacy_id: 902,
        aula: 501,
        sub: 800,
        senses: 2,
        hidden_senses: 1,
        author: 'Pablo Pérez Inventado',
      },
    ]);
  });

  it('converts comments to plain text and fixes the persona_id/user id mix-up', async () => {
    const cs = await rows<{
      legacy_source: string;
      body: string;
      author: string;
      student: string;
      sub: number | null;
    }>(
      db,
      sql`select c.legacy_source, c.body, a.display_name author, st.display_name student,
          (select legacy_id from submissions where id = c.submission_id) sub
          from comments c join users a on a.id = c.author_id join users st on st.id = c.student_id order by c.legacy_source`,
    );
    expect(cs).toEqual([
      {
        legacy_source: 'comentarios_entradas',
        body: 'Revisa la acepción 2',
        author: 'Marta García Ficticia',
        student: 'Pablo Pérez Inventado',
        sub: 800,
      },
      {
        legacy_source: 'comentarios_generales',
        body: 'Buen trabajo, Pablo.\nSigue así.',
        author: 'Marta García Ficticia',
        student: 'Pablo Pérez Inventado',
        sub: null,
      },
    ]);
  });

  it('is idempotent: a second run creates nothing new', async () => {
    const before = await counts(db);
    const files = (await readdir(media, { recursive: true })).length;
    const r = await migrateLegacy(db, legacy, opts);
    expect(r.failure).toBeNull();
    expect(r.media.migrated).toBe(0);
    expect(r.media.bytesCopied).toBe(0);
    expect(await counts(db)).toEqual(before);
    expect((await readdir(media, { recursive: true })).length).toBe(files);
    expect(JSON.stringify(r)).not.toMatch(FAKE_PII);
  });

  it('--fail-on-orphans rolls back and reports the failure', async () => {
    const before = await counts(db);
    const r = await migrateLegacy(db, legacy, { ...opts, failOnOrphans: true });
    expect(r.committed).toBe(false);
    expect(r.failure).toMatch(/huérfanos/);
    expect(await counts(db)).toEqual(before);
  });
});
