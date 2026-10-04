import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import type { EntryView, SenseField, SenseView } from '@lexican/core';
import type { SettingsRow } from './access.ts';
import { submissionProblem, type Actor } from './index.ts';
import { appFixture, PNG, type AppFixture } from './testing/fixture.ts';

const sense = (over: Partial<SenseView> = {}): SenseView => ({
  id: crypto.randomUUID(),
  position: 1,
  definition: 'Definición',
  extraInfo: '',
  example: '',
  partOfSpeechId: null,
  genderId: null,
  numberId: null,
  languageId: null,
  foreignForm: '',
  hidden: false,
  topicIds: [],
  media: [],
  ...over,
});
const entry = (senses: SenseView[], over: Partial<EntryView> = {}): EntryView => ({
  id: crypto.randomUUID(),
  dictionaryId: crypto.randomUUID(),
  headword: 'guagua',
  initial: 'G',
  hidden: false,
  version: 1,
  createdAt: '2025-10-01T00:00:00.000Z',
  updatedAt: '2025-10-01T00:00:00.000Z',
  author: null,
  sourceSubmissionId: null,
  senses,
  submissions: [],
  ...over,
});
const policy = (requiredFields: SenseField[] = [], maxSenses: number | null = null) =>
  ({ requiredFields, maxSenses }) as SettingsRow;
const media = (kind: 'image' | 'audio' | 'video') => ({
  id: crypto.randomUUID(),
  kind,
  mime: 'x/y',
  originalName: 'f',
  url: '/m',
});
const id = crypto.randomUUID();

describe('submissionProblem (RULE-076, RULE-042, RULE-195)', () => {
  it('refuses hidden entries and entries without a visible sense', () => {
    expect(submissionProblem(entry([sense()], { hidden: true }), policy())).toBe(
      'La entrada está oculta.',
    );
    expect(submissionProblem(entry([sense({ hidden: true })]), policy())).toBe(
      'La entrada no tiene ninguna acepción visible.',
    );
  });

  it('counts only visible senses against the classroom maximum', () => {
    const two = [sense(), sense(), sense({ hidden: true })];
    expect(submissionProblem(entry(two), policy([], 2))).toBeNull();
    expect(submissionProblem(entry([...two, sense()]), policy([], 2))).toBe(
      'El diccionario admite como máximo 2 acepciones por entrada.',
    );
  });

  it.each<[SenseField, Partial<SenseView>, Partial<SenseView>, string]>([
    ['part_of_speech', { partOfSpeechId: id }, {}, 'categoría gramatical'],
    ['gender', { genderId: id }, {}, 'género'],
    ['number', { numberId: id }, {}, 'número'],
    ['topics', { topicIds: [id] }, {}, 'temáticas'],
    ['extra_info', { extraInfo: 'Canarias' }, {}, 'más datos'],
    ['example', { example: 'Cogí la guagua' }, {}, 'ejemplo de uso'],
    ['language', { languageId: id, foreignForm: 'bus' }, { languageId: id }, 'otras lenguas'],
    ['image', { media: [media('image')] }, { media: [media('audio')] }, 'imagen'],
    ['audio', { media: [media('audio')] }, { media: [media('video')] }, 'audio'],
    ['video', { media: [media('video')] }, { media: [media('image')] }, 'vídeo'],
  ])('required %s must be filled in every visible sense', (field, filled, empty, label) => {
    expect(
      submissionProblem(entry([sense(filled), sense({ ...empty, hidden: true })]), policy([field])),
    ).toBeNull();
    expect(submissionProblem(entry([sense(filled), sense(empty)]), policy([field]))).toBe(
      `Faltan campos obligatorios: ${label}.`,
    );
  });

  it('lists every missing field in the configured order', () => {
    expect(submissionProblem(entry([sense()]), policy(['gender', 'example']))).toBe(
      'Faltan campos obligatorios: género, ejemplo de uso.',
    );
  });
});

describe('submission workflow', () => {
  let f: AppFixture;
  let teacher: Actor, ana: Actor, ben: Actor, outsiderTeacher: Actor;
  let aula: string, closed: string, notMine: string;
  const missing = '00000000-0000-4000-8000-000000000000';

  beforeAll(async () => {
    f = await appFixture('2025-10-15T10:00:00Z');
    teacher = await f.user('teacher', 'profe');
    outsiderTeacher = await f.user('teacher', 'otroprofe');
    ana = await f.user('student', 'ana');
    ben = await f.user('student', 'ben');
    const a = await f.classroom(teacher, { title: 'Abierta', requiredFields: ['example'] });
    const c = await f.classroom(teacher, { title: 'Cerrada', submissionsEnabled: false });
    aula = a.id;
    closed = c.id;
    notMine = (await f.classroom(teacher, { title: 'Ajena' })).id;
    for (const s of [ana, ben]) {
      await f.call('joinClassroom', s, { code: a.code });
      await f.call('joinClassroom', s, { code: c.code });
    }
  }, 60_000);
  afterAll(() => f.close());

  it('only submits own, existing personal entries', async () => {
    const e = await f.entry(ana, 'baifo', { example: 'El baifo salta' });
    await expect(
      f.call('submitEntries', ben, { entryIds: [e.id], classroomIds: [aula] }),
    ).rejects.toMatchObject({ code: 'forbidden' });
    await expect(
      f.call('submitEntries', ana, { entryIds: [e.id, missing], classroomIds: [aula] }),
    ).rejects.toMatchObject({
      code: 'forbidden',
    });
    await expect(
      f.call('submitEntries', null, { entryIds: [e.id], classroomIds: [aula] }),
    ).rejects.toMatchObject({ code: 'unauthenticated' });
  });

  it('reports per-classroom problems and creates only the valid pairs', async () => {
    const good = await f.entry(ana, 'gofio', { example: 'Gofio con leche' });
    const bad = await f.entry(ana, 'mojo'); // no example, required in "Abierta"
    const r = await f.call('submitEntries', ana, {
      entryIds: [good.id, bad.id],
      classroomIds: [aula, closed, notMine],
    });
    expect(r.problems.map((p) => [p.headword, p.classroomTitle, p.message])).toEqual([
      ['mojo', 'Abierta', 'Faltan campos obligatorios: ejemplo de uso.'],
      ['gofio', 'Cerrada', 'El profesorado no admite envíos en este momento.'],
      ['mojo', 'Cerrada', 'El profesorado no admite envíos en este momento.'],
      ['gofio', 'Ajena', 'No participas en este diccionario de aula.'],
      ['mojo', 'Ajena', 'No participas en este diccionario de aula.'],
    ]);
    expect(r.created.map((c) => [c.classroomTitle, c.status])).toEqual([['Abierta', 'pending']]);
  });

  it('explains a window that has not started or has ended', async () => {
    const e = await f.entry(ben, 'tenique', { example: 'Un tenique' });
    const set = (patch: object) =>
      f.call('updateClassroom', teacher, {
        classroomId: aula,
        classroom: { title: 'Abierta', requiredFields: ['example'], ...patch },
      });
    await set({ submissionsStartAt: '2025-11-01T00:00:00Z' });
    expect(
      (await f.call('submitEntries', ben, { entryIds: [e.id], classroomIds: [aula] })).problems[0]
        ?.message,
    ).toBe('El plazo de envíos aún no ha empezado.');
    await set({ submissionsEndAt: '2025-10-01T00:00:00Z' });
    expect(
      (await f.call('submitEntries', ben, { entryIds: [e.id], classroomIds: [aula] })).problems[0]
        ?.message,
    ).toBe('El plazo de envíos ha terminado.');
    await set({});
  });

  it('resubmitting a pending entry refreshes the same submission instead of queueing a duplicate (RULE-229)', async () => {
    const e = await f.entry(ben, 'guanche', { example: 'Pueblo guanche' });
    const first = await f.submit(ben, e.id, aula);
    f.setNow('2025-10-16T10:00:00Z');
    const again = await f.call('submitEntries', ben, { entryIds: [e.id], classroomIds: [aula] });
    expect(again.created[0]).toMatchObject({ id: first, submittedAt: '2025-10-16T10:00:00.000Z' });
    expect(
      (await f.call('listSubmissions', teacher, { classroomId: aula, studentId: ben.userId })).map(
        (s) => s.id,
      ),
    ).toEqual([first]);
  });

  it('students withdraw only their own pending submissions', async () => {
    const e = await f.entry(ana, 'papa', { example: 'Papas arrugadas' });
    const s = await f.submit(ana, e.id, aula);
    await expect(f.call('withdrawSubmission', ben, { submissionId: s })).rejects.toMatchObject({
      code: 'not_found',
    });
    await expect(
      f.call('withdrawSubmission', ana, { submissionId: missing }),
    ).rejects.toMatchObject({ code: 'not_found' });
    expect(await f.call('withdrawSubmission', ana, { submissionId: s })).toEqual({ ok: true });
    await expect(f.call('withdrawSubmission', ana, { submissionId: s })).rejects.toMatchObject({
      code: 'conflict',
    });
    expect(
      (await f.call('listSubmissions', teacher, { classroomId: aula })).map((x) => x.id),
    ).not.toContain(s);
    expect(
      (await f.call('listSubmissions', teacher, { classroomId: aula, status: 'withdrawn' })).map(
        (x) => x.id,
      ),
    ).toEqual([s]);
  });

  it('teachers list and search submissions; students and other teachers cannot', async () => {
    await expect(f.call('listSubmissions', ana, { classroomId: aula })).rejects.toMatchObject({
      code: 'forbidden',
    });
    await expect(
      f.call('listSubmissions', outsiderTeacher, { classroomId: aula }),
    ).rejects.toMatchObject({ code: 'forbidden' });
    expect(await f.call('listSubmissions', teacher, { classroomId: closed })).toEqual([]);
    expect(
      (await f.call('listSubmissions', teacher, { classroomId: aula, q: 'GUAN' })).map(
        (s) => s.snapshot.headword,
      ),
    ).toEqual(['guanche']);
    expect(
      (await f.call('listSubmissions', teacher, { classroomId: aula, studentId: ana.userId })).map(
        (s) => s.snapshot.headword,
      ),
    ).toEqual(['gofio']);
  });

  it('a submission is visible to its author and the classroom teachers only', async () => {
    const [s] = await f.call('listSubmissions', teacher, {
      classroomId: aula,
      studentId: ana.userId,
    });
    await expect(f.call('getSubmission', teacher, { submissionId: missing })).rejects.toMatchObject(
      { code: 'not_found' },
    );
    await expect(f.call('getSubmission', ben, { submissionId: s!.id })).rejects.toMatchObject({
      code: 'not_found',
    });
    await expect(
      f.call('getSubmission', outsiderTeacher, { submissionId: s!.id }),
    ).rejects.toMatchObject({ code: 'not_found' });
    expect((await f.call('getSubmission', ana, { submissionId: s!.id })).submittedBy.id).toBe(
      ana.userId,
    );
  });

  it('publishing records the reviewer, serves snapshot media by URL and refuses a second review', async () => {
    const m = await f.svc.media.uploadMedia(ana, { bytes: PNG, name: 'drago.png' });
    const e = await f.entry(ana, 'drago', { example: 'El drago milenario', mediaIds: [m.id] });
    const s = await f.submit(ana, e.id, aula);
    const view = await f.call('getSubmission', teacher, { submissionId: s });
    expect(view.snapshot.senses[0]!.media[0]).toMatchObject({
      id: m.id,
      kind: 'image',
      url: `/media/${m.id}`,
    });
    expect(view.reviewedBy).toBeNull();
    await expect(
      f.call('publishSubmissions', outsiderTeacher, { submissionIds: [s] }),
    ).rejects.toMatchObject({ code: 'forbidden' });
    expect(await f.call('publishSubmissions', teacher, { submissionIds: [s] })).toEqual({
      published: [s],
      conflicts: [],
    });
    const after = await f.call('getSubmission', ana, { submissionId: s });
    expect(after).toMatchObject({
      status: 'published',
      reviewedBy: { id: teacher.userId, displayName: 'profe Prueba' },
      conflict: null,
    });
    expect(after.publishedEntryId).toBeTruthy();
    expect(await f.call('publishSubmissions', teacher, { submissionIds: [s] })).toEqual({
      published: [],
      conflicts: [{ submissionId: s, headword: 'drago', message: 'El envío ya fue revisado.' }],
    });
    await expect(
      f.call('rejectSubmission', teacher, { submissionId: s, note: 'tarde' }),
    ).rejects.toMatchObject({ code: 'conflict' });
    await expect(
      f.call('publishSubmissions', teacher, { submissionIds: [missing] }),
    ).rejects.toMatchObject({ code: 'not_found' });
  });

  it('expired classrooms can still reject but not publish; deleted ones neither', async () => {
    const e = await f.entry(ben, 'sancocho', { example: 'Sancocho canario' });
    const s1 = await f.submit(ben, e.id, aula);
    f.setNow('2026-10-15T10:00:00Z');
    try {
      await expect(
        f.call('publishSubmissions', teacher, { submissionIds: [s1] }),
      ).rejects.toMatchObject({ code: 'forbidden' });
      await expect(f.call('rejectSubmission', ben, { submissionId: s1 })).rejects.toMatchObject({
        code: 'forbidden',
      });
      expect(
        await f.call('rejectSubmission', teacher, { submissionId: s1, note: 'Fuera de plazo' }),
      ).toEqual({ ok: true });
    } finally {
      f.setNow('2025-10-16T10:00:00Z');
    }
    const e2 = await f.entry(ben, 'escaldón', { example: 'Escaldón de gofio' });
    const s2 = await f.submit(ben, e2.id, aula);
    await f.call('deleteClassroom', teacher, { classroomId: aula });
    await expect(
      f.call('publishSubmissions', teacher, { submissionIds: [s2] }),
    ).rejects.toMatchObject({ code: 'not_found' });
    await expect(f.call('rejectSubmission', teacher, { submissionId: s2 })).rejects.toMatchObject({
      code: 'not_found',
    });
    await expect(f.call('getSubmission', teacher, { submissionId: s2 })).rejects.toMatchObject({
      code: 'not_found',
    });
  });
});
