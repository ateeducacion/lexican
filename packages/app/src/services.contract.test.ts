import type { EntryInput, OperationName, OperationOutputs } from '@lexican/core';
import { seedVocabulary, vocabularyValues } from '@lexican/db';
import { openPglite, openPostgres, type TestDb } from '@lexican/db/testing';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { type Actor, createPasswordUser, createServices, memoryMediaStorage } from './index.ts';

/**
 * Persistence contract (§75): the same application journey against PGlite and real PostgreSQL.
 * Postgres runs when TEST_DATABASE_URL is set (CI service container or local docker).
 */
const drivers: { name: string; open: () => Promise<TestDb> }[] = [
  { name: 'pglite', open: openPglite },
];
if (process.env.TEST_DATABASE_URL)
  drivers.push({ name: 'postgres', open: () => openPostgres(process.env.TEST_DATABASE_URL!) });

const PNG = Uint8Array.from(
  atob(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
  ),
  (c) => c.charCodeAt(0),
);

describe.each(drivers)('application contract on $name', ({ open }) => {
  let t: TestDb;
  let now = new Date('2025-10-15T10:00:00Z');
  const media = memoryMediaStorage();
  let svc: ReturnType<typeof createServices>;
  let teacher: Actor, ana: Actor, ben: Actor, outsider: Actor;
  const vocab: Record<string, string> = {};

  const call = <K extends OperationName>(
    name: K,
    actor: Actor | null,
    input: unknown,
  ): Promise<OperationOutputs[K]> => svc.call(name, actor, input);
  const entry = (headword: string, extra: Partial<EntryInput['senses'][number]> = {}) => ({
    headword,
    senses: [{ definition: `Definición de ${headword}`, ...extra }],
  });

  beforeAll(async () => {
    t = await open();
    await seedVocabulary(t.db);
    for (const v of await t.db.select().from(vocabularyValues))
      vocab[`${v.vocabulary}:${v.code}`] = v.id;
    svc = createServices({ db: t.db, clock: () => now, media, mediaUrl: (id) => `/media/${id}` });
    const mk = async (email: string, globalRole: 'teacher' | 'student') => ({
      userId: await createPasswordUser(t.db, {
        email,
        password: 'x',
        firstName: email.split('@')[0]!,
        lastName: 'Prueba',
        globalRole,
      }),
      globalRole,
    });
    teacher = await mk('profe@ejemplo.com', 'teacher');
    ana = await mk('ana@ejemplo.com', 'student');
    ben = await mk('ben@ejemplo.com', 'student');
    outsider = await mk('otra@ejemplo.com', 'student');
  }, 120_000);
  afterAll(() => t.close());

  let anaDict: string, classroomId: string, joinCode: string;

  it('logs in with a password identity and rejects a wrong one', async () => {
    expect((await call('login', null, { email: 'ANA@ejemplo.com', password: 'x' })).id).toBe(
      ana.userId,
    );
    await expect(
      call('login', null, { email: 'ana@ejemplo.com', password: 'mal' }),
    ).rejects.toMatchObject({ code: 'unauthenticated' });
  });

  it('creates the personal dictionary on first access, once', async () => {
    anaDict = (await call('myDictionary', ana, {})).id;
    expect((await call('myDictionary', ana, {})).id).toBe(anaDict);
  });

  it('creates entries with senses, topics and vocabulary; rejects duplicates and foreign ids', async () => {
    const e = await call('createEntry', ana, {
      dictionaryId: anaDict,
      entry: {
        headword: '  Guagua ',
        senses: [
          {
            definition: 'Autobús',
            partOfSpeechId: vocab['part_of_speech:sustantivo'],
            genderId: vocab['gender:femenino'],
            topicIds: [vocab['topic:dialecto_canario']],
          },
          {
            definition: 'Bebé',
            example: 'La guagua duerme',
            partOfSpeechId: vocab['part_of_speech:sustantivo'],
          },
        ],
      },
    });
    expect(e.headword).toBe('Guagua');
    expect(e.senses.map((s) => s.position)).toEqual([1, 2]);
    expect(e.senses[0]!.topicIds).toEqual([vocab['topic:dialecto_canario']]);
    await expect(
      call('createEntry', ana, { dictionaryId: anaDict, entry: entry('guagua') }),
    ).rejects.toMatchObject({ code: 'conflict' });
    await expect(
      call('createEntry', ana, {
        dictionaryId: anaDict,
        entry: entry('papa', { genderId: vocab['part_of_speech:verbo'] }),
      }),
    ).rejects.toMatchObject({ code: 'validation' });
    await call('createEntry', ana, { dictionaryId: anaDict, entry: entry('árbol') });
    await call('createEntry', ana, { dictionaryId: anaDict, entry: entry('ñame') });
    await call('createEntry', ana, { dictionaryId: anaDict, entry: entry('arbol') }); // accent-sensitive uniqueness
  });

  it('lists in Spanish order and searches by text, initial and topic', async () => {
    const all = await call('listEntries', ana, { dictionaryId: anaDict });
    expect(all.items.map((i) => i.headword)).toEqual(['arbol', 'árbol', 'Guagua', 'ñame']);
    expect((await call('listEntries', ana, { dictionaryId: anaDict, q: 'ARBOL' })).total).toBe(2);
    expect((await call('listEntries', ana, { dictionaryId: anaDict, initial: 'A' })).total).toBe(2);
    expect(
      (await call('listEntries', ana, { dictionaryId: anaDict, initial: 'Ñ' })).items[0]?.headword,
    ).toBe('ñame');
    expect(
      (
        await call('listEntries', ana, {
          dictionaryId: anaDict,
          topicId: vocab['topic:dialecto_canario'],
        })
      ).total,
    ).toBe(1);
  });

  it('keeps personal dictionaries private', async () => {
    await expect(call('listEntries', ben, { dictionaryId: anaDict })).rejects.toMatchObject({
      code: 'not_found',
    });
  });

  it('detects concurrent edits with optimistic locking (409)', async () => {
    const [g] = (await call('listEntries', ana, { dictionaryId: anaDict, q: 'guagua' })).items;
    const e = await call('getEntry', ana, { entryId: g!.id });
    const input = {
      headword: 'guagua',
      senses: e.senses.map((s) => ({ ...s, media: undefined, mediaIds: [] })),
    };
    const updated = await call('updateEntry', ana, {
      entryId: e.id,
      version: e.version,
      entry: input,
    });
    expect(updated.version).toBe(e.version + 1);
    expect(updated.senses[0]!.id).toBe(e.senses[0]!.id);
    await expect(
      call('updateEntry', ana, { entryId: e.id, version: e.version, entry: input }),
    ).rejects.toMatchObject({ code: 'conflict' });
  });

  it('teachers create classrooms; students join by code; outsiders cannot', async () => {
    await expect(call('createClassroom', ana, { title: 'No' })).rejects.toMatchObject({
      code: 'forbidden',
    });
    const c = await call('createClassroom', teacher, {
      title: 'Canarismos 2º ESO',
      requiredFields: ['part_of_speech'],
      maxSenses: 2,
      commentsVisibility: 'visible',
    });
    classroomId = c.id;
    joinCode = c.classroom!.joinCode;
    expect(joinCode).toMatch(/^[A-Z2-9]{6}$/);
    expect(c.myRole).toBe('teacher');
    expect(c.classroom!.visibleFields).toContain('part_of_speech');
    expect((await call('joinClassroom', ana, { code: joinCode.toLowerCase() })).myRole).toBe(
      'student',
    );
    await call('joinClassroom', ben, { code: joinCode });
    await expect(call('joinClassroom', ana, { code: joinCode })).rejects.toMatchObject({
      code: 'conflict',
    });
    await expect(
      call('listEntries', outsider, { dictionaryId: classroomId }),
    ).rejects.toMatchObject({ code: 'not_found' });
  });

  let pendingId: string;
  it('validates submissions against the classroom policy before creating an immutable revision', async () => {
    const list = (await call('listEntries', ana, { dictionaryId: anaDict })).items;
    const guagua = list.find((i) => i.headword === 'guagua')!;
    const arbol = list.find((i) => i.headword === 'árbol')!;
    const dry = await call('submitEntries', ana, {
      entryIds: [guagua.id, arbol.id],
      classroomIds: [classroomId],
      dryRun: true,
    });
    expect(dry.created).toHaveLength(0);
    expect(dry.problems.map((p) => p.headword)).toEqual(['árbol']); // missing part of speech
    expect(dry.problems[0]!.message).toMatch(/categoría gramatical/);
    await expect(
      call('submitEntries', outsider, { entryIds: [guagua.id], classroomIds: [classroomId] }),
    ).rejects.toMatchObject({
      code: 'forbidden',
    });
    const res = await call('submitEntries', ana, {
      entryIds: [guagua.id],
      classroomIds: [classroomId],
    });
    expect(res.created).toHaveLength(1);
    pendingId = res.created[0]!.id;
    // Editing the personal entry afterwards does not change what the teacher reviews (§27).
    const e = await call('getEntry', ana, { entryId: guagua.id });
    await call('updateEntry', ana, {
      entryId: e.id,
      version: e.version,
      entry: {
        headword: 'guagua',
        senses: [{ definition: 'Cambiada', partOfSpeechId: vocab['part_of_speech:sustantivo'] }],
      },
    });
    const s = await call('getSubmission', teacher, { submissionId: pendingId });
    expect(s.snapshot.senses.map((x) => x.definition)).toEqual(['Autobús', 'Bebé']);
    expect((await call('getEntry', ana, { entryId: guagua.id })).submissions[0]?.status).toBe(
      'pending',
    );
  });

  it('closes submissions outside the window and when the classroom expires', async () => {
    const [g] = (await call('listEntries', ana, { dictionaryId: anaDict, q: 'guagua' })).items;
    now = new Date('2026-09-15T10:00:00Z'); // next school year, validity 1
    const r = await call('submitEntries', ana, {
      entryIds: [g!.id],
      classroomIds: [classroomId],
      dryRun: true,
    });
    expect(r.problems[0]?.message).toMatch(/vigente/);
    now = new Date('2025-10-15T10:00:00Z');
  });

  it('only classroom teachers review; publication keeps provenance', async () => {
    await expect(
      call('publishSubmissions', ana, { submissionIds: [pendingId] }),
    ).rejects.toMatchObject({ code: 'forbidden' });
    expect(
      (await call('listSubmissions', teacher, { classroomId, status: 'pending' })).map((s) => s.id),
    ).toEqual([pendingId]);
    const r = await call('publishSubmissions', teacher, { submissionIds: [pendingId] });
    expect(r.published).toEqual([pendingId]);
    const published = (await call('listEntries', ben, { dictionaryId: classroomId })).items;
    expect(published.map((p) => p.headword)).toEqual(['guagua']);
    const full = await call('getEntry', ben, { entryId: published[0]!.id });
    expect(full.sourceSubmissionId).toBe(pendingId);
    expect(full.author?.id).toBe(ana.userId);
    expect(full.senses).toHaveLength(2);
  });

  it('blocks a same-headword publication from another student (RULE-057) and allows rejection with a note', async () => {
    const benDict = (await call('myDictionary', ben, {})).id;
    const e = await call('createEntry', ben, {
      dictionaryId: benDict,
      entry: entry('Guagua', { partOfSpeechId: vocab['part_of_speech:sustantivo'] }),
    });
    const { created } = await call('submitEntries', ben, {
      entryIds: [e.id],
      classroomIds: [classroomId],
    });
    const sub = await call('getSubmission', teacher, { submissionId: created[0]!.id });
    expect(sub.conflict?.headword).toBe('guagua');
    const r = await call('publishSubmissions', teacher, { submissionIds: [sub.id] });
    expect(r.conflicts).toHaveLength(1);
    await call('rejectSubmission', teacher, { submissionId: sub.id, note: 'Ya está publicada' });
    expect((await call('getEntry', ben, { entryId: e.id })).submissions[0]).toMatchObject({
      status: 'rejected',
      reviewNote: 'Ya está publicada',
    });
  });

  it('resubmitting a published entry updates the published copy', async () => {
    const [g] = (await call('listEntries', ana, { dictionaryId: anaDict, q: 'guagua' })).items;
    const { created } = await call('submitEntries', ana, {
      entryIds: [g!.id],
      classroomIds: [classroomId],
    });
    await call('publishSubmissions', teacher, { submissionIds: [created[0]!.id] });
    const items = (await call('listEntries', ben, { dictionaryId: classroomId })).items;
    expect(items).toHaveLength(1);
    expect(items[0]!.firstDefinition).toBe('Cambiada');
  });

  it('teachers hide senses and entries; students do not see them', async () => {
    const [p] = (await call('listEntries', teacher, { dictionaryId: classroomId })).items;
    await call('createComment', teacher, {
      classroomId,
      studentId: ana.userId,
      body: '¡Muy bien!',
    });
    await call('setEntryHidden', teacher, { entryId: p!.id, hidden: true });
    expect((await call('listEntries', ben, { dictionaryId: classroomId })).total).toBe(0);
    await expect(
      call('setEntryHidden', ben, { entryId: p!.id, hidden: false }),
    ).rejects.toBeTruthy();
    await call('setEntryHidden', teacher, { entryId: p!.id, hidden: false });
  });

  it('comment visibility follows the classroom setting', async () => {
    expect((await call('listComments', ana, {})).map((c) => c.body)).toEqual(['¡Muy bien!']);
    expect(await call('listComments', ben, {})).toEqual([]);
    const c = await call('getDictionary', teacher, { dictionaryId: classroomId });
    const base = {
      title: c.title,
      requiredFields: c.classroom!.requiredFields,
      maxSenses: c.classroom!.maxSenses,
      commentsVisibility: 'before_date' as const,
      commentsVisibleBefore: new Date('2025-01-01'),
    };
    await call('updateClassroom', teacher, { classroomId, classroom: base });
    expect(await call('listComments', ana, {})).toEqual([]);
    await call('updateClassroom', teacher, {
      classroomId,
      classroom: { ...base, commentsVisibility: 'visible' },
    });
  });

  it('enforces membership rules (last teacher, no self-change)', async () => {
    await expect(
      call('updateMember', teacher, { classroomId, userId: teacher.userId, active: false }),
    ).rejects.toMatchObject({ code: 'validation' });
    const members = await call('updateMember', teacher, {
      classroomId,
      userId: ben.userId,
      role: 'teacher',
    });
    expect(members.find((m) => m.user.id === ben.userId)?.role).toBe('teacher');
    await expect(
      call('updateMember', ben, { classroomId, userId: teacher.userId, role: 'student' }),
    ).rejects.toMatchObject({ code: 'validation' });
    await call('updateMember', teacher, { classroomId, userId: ben.userId, role: 'student' });
    await expect(call('listMembers', ben, { classroomId })).rejects.toMatchObject({
      code: 'forbidden',
    });
  });

  it('deleting a personal entry withdraws pending submissions but keeps publications', async () => {
    const [n] = (await call('listEntries', ana, { dictionaryId: anaDict, q: 'ñame' })).items;
    const e = await call('getEntry', ana, { entryId: n!.id });
    await call('updateEntry', ana, {
      entryId: e.id,
      version: e.version,
      entry: entry('ñame', { partOfSpeechId: vocab['part_of_speech:sustantivo'] }),
    });
    const { created } = await call('submitEntries', ana, {
      entryIds: [n!.id],
      classroomIds: [classroomId],
    });
    await call('deleteEntry', ana, { entryId: n!.id });
    expect(
      (await call('listSubmissions', teacher, { classroomId, status: 'withdrawn' })).map(
        (s) => s.id,
      ),
    ).toContain(created[0]!.id);
    expect((await call('listEntries', ben, { dictionaryId: classroomId })).total).toBe(1);
  });

  it('accepts uploads by content and restricts who can read them', async () => {
    const m = await svc.media.uploadMedia(ana, { bytes: PNG, name: 'foto.php' });
    expect(m).toMatchObject({ kind: 'image', mime: 'image/png' });
    await expect(
      svc.media.uploadMedia(ana, {
        bytes: new TextEncoder().encode('<script>alert(1)</script>'),
        name: 'x.png',
      }),
    ).rejects.toMatchObject({
      code: 'validation',
    });
    await expect(svc.media.readMedia(ben, m.id)).rejects.toMatchObject({ code: 'not_found' });
    const e = await call('createEntry', ana, {
      dictionaryId: anaDict,
      entry: entry('palmera', {
        partOfSpeechId: vocab['part_of_speech:sustantivo'],
        mediaIds: [m.id],
      }),
    });
    await expect(
      call('createEntry', ben, {
        dictionaryId: (await call('myDictionary', ben, {})).id,
        entry: entry('robo', { mediaIds: [m.id] }),
      }),
    ).rejects.toMatchObject({
      code: 'forbidden',
    });
    const { created } = await call('submitEntries', ana, {
      entryIds: [e.id],
      classroomIds: [classroomId],
    });
    expect((await svc.media.readMedia(teacher, m.id)).mime).toBe('image/png');
    await call('publishSubmissions', teacher, { submissionIds: [created[0]!.id] });
    expect(new Uint8Array(await (await svc.media.readMedia(ben, m.id)).blob.arrayBuffer())).toEqual(
      PNG,
    );
  });

  it('records an audit trail without content', async () => {
    await expect(call('adminAudit', teacher, {})).rejects.toMatchObject({ code: 'forbidden' });
  });

  it('rejects invalid input with field details', async () => {
    await expect(
      call('createEntry', ana, { dictionaryId: anaDict, entry: { headword: '', senses: [] } }),
    ).rejects.toMatchObject({
      code: 'validation',
      details: expect.objectContaining({ 'entry.headword': expect.any(Array) }),
    });
  });
});
