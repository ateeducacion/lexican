import { afterAll, afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import { generateJoinCode, type Actor } from './index.ts';
import { appFixture, type AppFixture } from './testing/fixture.ts';

describe('generateJoinCode', () => {
  it('maps random bytes onto the unambiguous 32-character alphabet', () => {
    expect(generateJoinCode(() => Uint8Array.from([0, 1, 31, 32, 8, 255]))).toBe('AB9AJ9');
    expect(generateJoinCode()).toMatch(/^[A-HJ-NP-Z2-9]{6}$/);
  });
});

describe('classrooms', () => {
  let f: AppFixture;
  let owner: Actor, coTeacher: Actor, admin: Actor, ana: Actor, ben: Actor, outsider: Actor;
  let aula: { id: string; code: string };
  const missing = '00000000-0000-4000-8000-000000000000';

  beforeAll(async () => {
    f = await appFixture('2023-10-15T10:00:00Z');
    owner = await f.user('teacher', 'propietaria');
    coTeacher = await f.user('teacher', 'cotutor');
    admin = await f.user('admin', 'admin');
    ana = await f.user('student', 'ana');
    ben = await f.user('student', 'ben');
    outsider = await f.user('student', 'fuera');
    aula = await f.classroom(owner, { title: 'Aula 2023', validityYears: 3 });
    for (const s of [ana, ben, coTeacher]) await f.call('joinClassroom', s, { code: aula.code });
    await f.call('updateMember', owner, { classroomId: aula.id, userId: coTeacher.userId, role: 'teacher' });
    f.setNow('2025-10-15T10:00:00Z'); // third and last current school year
  }, 60_000);
  afterAll(() => f.close());
  afterEach(() => vi.restoreAllMocks());

  it('a student or support account cannot create classrooms; admins can', async () => {
    await expect(f.call('createClassroom', ana, { title: 'No' })).rejects.toMatchObject({ code: 'forbidden' });
    await expect(f.call('createClassroom', await f.user('support', 'soporte'), { title: 'No' })).rejects.toMatchObject({
      code: 'forbidden',
    });
    const c = await f.call('createClassroom', admin, { title: 'Aula admin', requiredFields: ['example'], visibleFields: [] });
    expect(c.classroom).toMatchObject({ schoolYear: 2025, visibleFields: ['example'], requiredFields: ['example'], current: true });
    expect(c.myRole).toBe('teacher');
  });

  it('retries the join code on a collision and gives up after six attempts', async () => {
    // Only the 6-byte join-code draws are forced; anything else (UUIDs, salts) stays random.
    const real = crypto.getRandomValues.bind(crypto);
    const spy = vi
      .spyOn(crypto, 'getRandomValues')
      .mockImplementation(((a: Uint8Array) => (a.length === 6 ? a.fill(0) : real(a as Uint8Array<ArrayBuffer>))) as typeof crypto.getRandomValues);
    const draws = () => spy.mock.calls.filter(([a]) => (a as Uint8Array).length === 6).length;
    const first = await f.call('createClassroom', owner, { title: 'Código A' });
    expect(first.classroom!.joinCode).toBe('AAAAAA');
    spy.mockClear();
    await expect(f.call('createClassroom', owner, { title: 'Código B' })).rejects.toSatisfy(
      (e: { code?: string; cause?: { code?: string } }) => e.code === '23505' || e.cause?.code === '23505',
    );
    expect(draws()).toBe(6);
    // One collision, then a fresh code.
    spy.mockClear();
    spy.mockImplementation(((a: Uint8Array) => (a.length === 6 ? a.fill(draws() > 1 ? 1 : 0) : real(a as Uint8Array<ArrayBuffer>))) as typeof crypto.getRandomValues);
    expect((await f.call('createClassroom', owner, { title: 'Código B' })).classroom!.joinCode).toBe('BBBBBB');
    expect(draws()).toBe(2);
    expect((await f.call('myClassrooms', owner, {})).filter((c) => c.title.startsWith('Código'))).toHaveLength(2);
  });

  it('regenerates the join code for teachers only; the old code stops working', async () => {
    await expect(f.call('regenerateJoinCode', ana, { classroomId: aula.id })).rejects.toMatchObject({ code: 'forbidden' });
    const r = await f.call('regenerateJoinCode', coTeacher, { classroomId: aula.id });
    expect(r.classroom!.joinCode).not.toBe(aula.code);
    await expect(f.call('joinClassroom', outsider, { code: aula.code })).rejects.toMatchObject({
      code: 'not_found',
      details: { code: ['Código no encontrado'] },
    });
    aula.code = r.classroom!.joinCode;
  });

  it('students of a hidden classroom see its card but not its entries; outsiders see nothing', async () => {
    await f.call('updateClassroom', owner, { classroomId: aula.id, classroom: { title: 'Aula 2023', validityYears: 3, visibleToStudents: false } });
    expect((await f.call('getDictionary', ana, { dictionaryId: aula.id })).classroom!.visibleToStudents).toBe(false);
    await expect(f.call('listEntries', ana, { dictionaryId: aula.id })).rejects.toMatchObject({
      code: 'forbidden',
      message: expect.stringMatching(/aún no ha hecho visible/),
    });
    await expect(f.call('getDictionary', outsider, { dictionaryId: aula.id })).rejects.toMatchObject({ code: 'not_found' });
    await expect(f.call('getDictionary', ana, { dictionaryId: missing })).rejects.toMatchObject({ code: 'not_found' });
    // Staff can always read.
    expect((await f.call('listEntries', admin, { dictionaryId: aula.id })).total).toBe(0);
    await f.call('updateClassroom', owner, { classroomId: aula.id, classroom: { title: 'Aula 2023', validityYears: 3 } });
  });

  it('validity cannot drop below the school years already elapsed', async () => {
    await expect(
      f.call('updateClassroom', owner, { classroomId: aula.id, classroom: { title: 'Aula 2023', validityYears: 2 } }),
    ).rejects.toMatchObject({ code: 'validation', details: { validityYears: ['Mínimo 3'] } });
    await expect(f.call('updateClassroom', ana, { classroomId: aula.id, classroom: { title: 'X', validityYears: 3 } })).rejects.toMatchObject({
      code: 'forbidden',
    });
    const r = await f.call('updateClassroom', owner, {
      classroomId: aula.id,
      classroom: { title: 'Aula renombrada', description: 'Canarismos', validityYears: 4, maxSenses: 3 },
    });
    expect(r).toMatchObject({ title: 'Aula renombrada', description: 'Canarismos', classroom: { validityYears: 4, maxSenses: 3 } });
  });

  it('expired classrooms are read-only and cannot be joined', async () => {
    f.setNow('2027-10-15T10:00:00Z'); // 2023 + 4 years of validity → expired
    try {
      await expect(f.call('joinClassroom', outsider, { code: aula.code })).rejects.toMatchObject({
        code: 'validation',
        message: expect.stringMatching(/ya no está vigente/),
      });
      await expect(f.call('updateClassroom', owner, { classroomId: aula.id, classroom: { title: 'Tarde', validityYears: 5 } })).rejects.toMatchObject({
        code: 'forbidden',
        message: expect.stringMatching(/solo se puede consultar/),
      });
      expect((await f.call('getDictionary', ana, { dictionaryId: aula.id })).classroom!.current).toBe(false);
      // Membership management still works on an expired classroom.
      expect(await f.call('listMembers', owner, { classroomId: aula.id })).toHaveLength(4);
    } finally {
      f.setNow('2025-10-15T10:00:00Z');
    }
  });

  it('membership: no self-change, the creator stays, unknown people are not found, disabled students cannot rejoin', async () => {
    await expect(f.call('updateMember', coTeacher, { classroomId: aula.id, userId: owner.userId, active: false })).rejects.toMatchObject({
      code: 'validation',
      message: expect.stringMatching(/quien creó/),
    });
    await expect(f.call('updateMember', coTeacher, { classroomId: aula.id, userId: owner.userId, role: 'student' })).rejects.toMatchObject({
      code: 'validation',
    });
    await expect(f.call('updateMember', coTeacher, { classroomId: aula.id, userId: coTeacher.userId, role: 'student' })).rejects.toMatchObject({
      code: 'validation',
      message: expect.stringMatching(/propia/),
    });
    await expect(f.call('updateMember', owner, { classroomId: aula.id, userId: outsider.userId, active: false })).rejects.toMatchObject({
      code: 'not_found',
    });
    await expect(f.call('updateMember', ana, { classroomId: aula.id, userId: ben.userId, active: false })).rejects.toMatchObject({
      code: 'forbidden',
    });
    const members = await f.call('updateMember', coTeacher, { classroomId: aula.id, userId: ben.userId, active: false });
    expect(members.find((m) => m.user.id === ben.userId)).toMatchObject({ active: false, role: 'student', isOwner: false });
    expect(members.find((m) => m.user.id === owner.userId)).toMatchObject({ isOwner: true, role: 'teacher' });
    await expect(f.call('joinClassroom', ben, { code: aula.code })).rejects.toMatchObject({
      code: 'forbidden',
      message: expect.stringMatching(/deshabilitado/),
    });
    expect((await f.call('myClassrooms', ben, {})).map((c) => c.id)).not.toContain(aula.id);
    await f.call('updateMember', owner, { classroomId: aula.id, userId: ben.userId, active: true });
    expect((await f.call('myClassrooms', ben, {})).map((c) => c.id)).toContain(aula.id);
  });

  it('only the creator or an admin deletes a classroom; pending submissions are withdrawn', async () => {
    const c = await f.classroom(owner, { title: 'Para borrar' });
    await f.call('joinClassroom', ana, { code: c.code });
    await f.call('joinClassroom', coTeacher, { code: c.code });
    await f.call('updateMember', owner, { classroomId: c.id, userId: coTeacher.userId, role: 'teacher' });
    const e = await f.entry(ana, 'perenquén');
    const sub = await f.submit(ana, e.id, c.id);
    await expect(f.call('deleteClassroom', coTeacher, { classroomId: c.id })).rejects.toMatchObject({ code: 'forbidden' });
    await expect(f.call('deleteClassroom', owner, { classroomId: (await f.call('myDictionary', owner, {})).id })).rejects.toMatchObject({
      code: 'not_found',
    });
    expect(await f.call('deleteClassroom', admin, { classroomId: c.id })).toEqual({ ok: true });
    expect((await f.call('myClassrooms', ana, {})).map((x) => x.id)).not.toContain(c.id);
    expect((await f.call('getSubmission', ana, { submissionId: sub })).status).toBe('withdrawn');
    await expect(f.call('joinClassroom', ben, { code: c.code })).rejects.toMatchObject({ code: 'not_found' });
    expect((await f.call('getEntry', ana, { entryId: e.id })).submissions).toEqual([]); // deleted classroom hidden
    const own = await f.classroom(owner, { title: 'Propia' });
    expect(await f.call('deleteClassroom', owner, { classroomId: own.id })).toEqual({ ok: true });
    expect(await f.call('myClassrooms', outsider, {})).toEqual([]);
  });
});
