import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import type { Actor } from './index.ts';
import { appFixture, type AppFixture } from './testing/fixture.ts';

/** Teacher feedback: who may write, read and delete comments, and the per-classroom visibility (RULE-185). */
describe('comments', () => {
  let f: AppFixture;
  let teacher: Actor, coTeacher: Actor, ana: Actor, ben: Actor, loner: Actor;
  let aula: string, otra: string;
  let anaSubmission: string, benSubmission: string;
  const missing = '00000000-0000-4000-8000-000000000000';

  const settings = async (classroomId: string, patch: Record<string, unknown>) => {
    const d = await f.call('getDictionary', teacher, { dictionaryId: classroomId });
    await f.call('updateClassroom', teacher, { classroomId, classroom: { title: d.title, ...patch } });
  };

  beforeAll(async () => {
    f = await appFixture('2025-10-15T10:00:00Z');
    teacher = await f.user('teacher', 'profe');
    coTeacher = await f.user('teacher', 'cotutora');
    ana = await f.user('student', 'ana');
    ben = await f.user('student', 'ben');
    loner = await f.user('student', 'sola');
    const c = await f.classroom(teacher, { title: 'Aula A' });
    const o = await f.classroom(teacher, { title: 'Aula B' });
    aula = c.id;
    otra = o.id;
    for (const s of [ana, ben, coTeacher]) await f.call('joinClassroom', s, { code: c.code });
    await f.call('joinClassroom', ana, { code: o.code });
    await f.call('updateMember', teacher, { classroomId: aula, userId: coTeacher.userId, role: 'teacher' });
    anaSubmission = await f.submit(ana, (await f.entry(ana, 'baifo')).id, aula);
    benSubmission = await f.submit(ben, (await f.entry(ben, 'gofio')).id, aula);
  }, 60_000);
  afterAll(() => f.close());

  it('only classroom teachers comment, and only to participants about their own submissions', async () => {
    const body = { classroomId: aula, studentId: ana.userId, body: 'Revisa la definición' };
    await expect(f.call('createComment', ana, body)).rejects.toMatchObject({ code: 'forbidden' });
    await expect(f.call('createComment', teacher, { ...body, studentId: loner.userId })).rejects.toMatchObject({
      code: 'validation',
      message: expect.stringMatching(/no participa/),
    });
    await expect(f.call('createComment', teacher, { ...body, submissionId: benSubmission })).rejects.toMatchObject({
      code: 'validation',
      message: expect.stringMatching(/no corresponde/),
    });
    await expect(f.call('createComment', teacher, { ...body, submissionId: missing })).rejects.toMatchObject({ code: 'validation' });
    await expect(f.call('createComment', teacher, { ...body, classroomId: otra, submissionId: anaSubmission })).rejects.toMatchObject({
      code: 'validation',
    });
    await expect(f.call('createComment', teacher, { ...body, body: '   ' })).rejects.toMatchObject({ code: 'validation' });

    const c = await f.call('createComment', teacher, { ...body, submissionId: anaSubmission });
    expect(c).toMatchObject({
      classroomId: aula,
      classroomTitle: 'Aula A',
      author: { id: teacher.userId, displayName: 'profe Prueba' },
      student: { id: ana.userId, displayName: 'ana Prueba' },
      submissionId: anaSubmission,
      headword: 'baifo',
      body: 'Revisa la definición',
    });
    f.setNow('2025-10-20T10:00:00Z');
    const general = await f.call('createComment', coTeacher, { classroomId: aula, studentId: ben.userId, body: 'Buen trabajo' });
    expect(general).toMatchObject({ headword: null, submissionId: null });
    f.setNow('2025-10-21T10:00:00Z');
    await f.call('createComment', teacher, { classroomId: otra, studentId: ana.userId, body: 'Comentario del aula B' });
  });

  it('teachers list a classroom, optionally per student; outsiders get not found', async () => {
    expect((await f.call('listComments', teacher, { classroomId: aula })).map((c) => c.body)).toEqual([
      'Buen trabajo',
      'Revisa la definición',
    ]);
    expect((await f.call('listComments', coTeacher, { classroomId: aula, studentId: ana.userId })).map((c) => c.body)).toEqual([
      'Revisa la definición',
    ]);
    await expect(f.call('listComments', loner, { classroomId: aula })).rejects.toMatchObject({ code: 'not_found' });
    await expect(f.call('listComments', null, {})).rejects.toMatchObject({ code: 'unauthenticated' });
  });

  it('students only see comments addressed to them in classrooms they belong to', async () => {
    expect((await f.call('listComments', ana, {})).map((c) => c.body)).toEqual(['Comentario del aula B', 'Revisa la definición']);
    expect((await f.call('listComments', ana, { classroomId: aula })).map((c) => c.body)).toEqual(['Revisa la definición']);
    // A student asking for a classmate's comments still gets only their own.
    expect((await f.call('listComments', ana, { classroomId: aula, studentId: ben.userId })).map((c) => c.body)).toEqual([
      'Revisa la definición',
    ]);
    expect((await f.call('listComments', ben, {})).map((c) => c.body)).toEqual(['Buen trabajo']);
    expect(await f.call('listComments', loner, {})).toEqual([]);
  });

  it('hidden and before-date visibility filter what students read; deactivated members read nothing', async () => {
    await settings(aula, { commentsVisibility: 'hidden' });
    expect((await f.call('listComments', ana, {})).map((c) => c.body)).toEqual(['Comentario del aula B']);
    expect(await f.call('listComments', teacher, { classroomId: aula })).toHaveLength(2); // teachers always see them
    await settings(aula, { commentsVisibility: 'before_date', commentsVisibleBefore: '2025-10-18T00:00:00Z' });
    expect((await f.call('listComments', ana, { classroomId: aula })).map((c) => c.body)).toEqual(['Revisa la definición']);
    expect(await f.call('listComments', ben, {})).toEqual([]); // written on 20 October
    await settings(aula, { commentsVisibility: 'visible' });
    await f.call('updateMember', teacher, { classroomId: otra, userId: ana.userId, active: false });
    expect((await f.call('listComments', ana, {})).map((c) => c.body)).toEqual(['Revisa la definición']);
    await f.call('updateMember', teacher, { classroomId: otra, userId: ana.userId, active: true });
  });

  it('authors and classroom teachers delete comments; students cannot', async () => {
    const [general] = await f.call('listComments', ben, {});
    await expect(f.call('deleteComment', ben, { commentId: general!.id })).rejects.toMatchObject({ code: 'forbidden' });
    await expect(f.call('deleteComment', teacher, { commentId: missing })).rejects.toMatchObject({ code: 'not_found' });
    // Written by the co-teacher, removed by the owner teacher.
    expect(await f.call('deleteComment', teacher, { commentId: general!.id })).toEqual({ ok: true });
    expect(await f.call('listComments', ben, {})).toEqual([]);
    await expect(f.call('deleteComment', coTeacher, { commentId: general!.id })).rejects.toMatchObject({ code: 'not_found' });
    const [own] = await f.call('listComments', teacher, { classroomId: otra });
    expect(await f.call('deleteComment', teacher, { commentId: own!.id })).toEqual({ ok: true });
  });
});
