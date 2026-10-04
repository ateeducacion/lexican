import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import type { Actor } from './index.ts';
import { type AppFixture, appFixture } from './testing/fixture.ts';

/** Admin screens replacing Voyager: role rules, validity filters, reactivation cap, stats and vocabularies. */
describe('admin services', () => {
  let f: AppFixture;
  let admin: Actor, support: Actor, teacher: Actor, student: Actor;
  let old: string, current: string;
  const missing = '00000000-0000-4000-8000-000000000000';

  beforeAll(async () => {
    f = await appFixture('2023-10-15T10:00:00Z');
    admin = await f.user('admin', 'admin');
    support = await f.user('support', 'soporte');
    teacher = await f.user('teacher', 'profe');
    student = await f.user('student', 'alumna');
    old = (await f.classroom(teacher, { title: 'Aula 2023' })).id; // school year 2023, validity 1
    f.setNow('2025-10-15T10:00:00Z');
    const c = await f.classroom(teacher, { title: 'Aula 2025', validityYears: 2 });
    current = c.id;
    // One published entry in 2025 for the stats.
    await f.call('joinClassroom', student, { code: c.code });
    const e = await f.entry(student, 'gofio');
    await f.call('publishSubmissions', teacher, {
      submissionIds: [await f.submit(student, e.id, current)],
    });
  }, 60_000);
  afterAll(() => f.close());

  it('lists users only for admins and searches name or email case-insensitively', async () => {
    await expect(f.call('adminListUsers', null, {})).rejects.toMatchObject({
      code: 'unauthenticated',
    });
    await expect(f.call('adminListUsers', support, {})).rejects.toMatchObject({
      code: 'forbidden',
    });
    await expect(f.call('adminListUsers', teacher, {})).rejects.toMatchObject({
      code: 'forbidden',
    });
    expect(await f.call('adminListUsers', admin, {})).toHaveLength(4);
    expect((await f.call('adminListUsers', admin, { q: 'SOPORTE' })).map((u) => u.email)).toEqual([
      'soporte@ejemplo.com',
    ]);
    expect(
      (await f.call('adminListUsers', admin, { q: 'alumna@ejemplo' })).map((u) => u.displayName),
    ).toEqual(['alumna Prueba']);
  });

  it('changes the role of others, never its own, and audits the change', async () => {
    await expect(
      f.call('adminSetUserRole', admin, { userId: admin.userId, globalRole: 'student' }),
    ).rejects.toMatchObject({
      code: 'validation',
    });
    await expect(
      f.call('adminSetUserRole', admin, { userId: missing, globalRole: 'teacher' }),
    ).rejects.toMatchObject({
      code: 'not_found',
    });
    await expect(
      f.call('adminSetUserRole', support, { userId: student.userId, globalRole: 'teacher' }),
    ).rejects.toMatchObject({
      code: 'forbidden',
    });
    const u = await f.call('adminSetUserRole', admin, {
      userId: student.userId,
      globalRole: 'teacher',
    });
    expect(u.globalRole).toBe('teacher');
    const [last] = await f.call('adminAudit', admin, { limit: 1 });
    expect(last).toMatchObject({
      action: 'user.role',
      entityType: 'user',
      entityId: student.userId,
      actor: 'admin Prueba',
    });
    await f.call('adminSetUserRole', admin, { userId: student.userId, globalRole: 'student' });
  });

  it('filters classrooms by validity for admin and support, newest school year first', async () => {
    await expect(f.call('adminListClassrooms', teacher, {})).rejects.toMatchObject({
      code: 'forbidden',
    });
    const all = await f.call('adminListClassrooms', support, {});
    expect(all.map((c) => [c.title, c.schoolYear, c.current, c.memberCount, c.entryCount])).toEqual(
      [
        ['Aula 2025', 2025, true, 2, 1],
        ['Aula 2023', 2023, false, 1, 0],
      ],
    );
    expect(all[0]!.owner).toBe('profe Prueba');
    expect(
      (await f.call('adminListClassrooms', admin, { filter: 'current' })).map((c) => c.id),
    ).toEqual([current]);
    expect(
      (await f.call('adminListClassrooms', admin, { filter: 'expired' })).map((c) => c.id),
    ).toEqual([old]);
    expect(await f.call('adminListClassrooms', admin, { filter: 'timeless' })).toEqual([]);
  });

  it('reactivates an expired classroom without ever shortening its validity (RULE-007)', async () => {
    await expect(
      f.call('adminUpdateValidity', teacher, { classroomId: old, reactivate: true }),
    ).rejects.toMatchObject({
      code: 'forbidden',
    });
    await expect(
      f.call('adminUpdateValidity', support, { classroomId: missing, reactivate: true }),
    ).rejects.toMatchObject({
      code: 'not_found',
    });
    const r = await f.call('adminUpdateValidity', support, { classroomId: old, reactivate: true });
    expect(r).toMatchObject({ validityYears: 3, current: true, timeless: false }); // 2023 → 2025 needs 3 years
    // Already current with validity 2: reactivation needs only 1, the larger value is kept.
    expect(
      (await f.call('adminUpdateValidity', support, { classroomId: current, reactivate: true }))
        .validityYears,
    ).toBe(2);
    const audit = await f.call('adminAudit', admin, { limit: 2 });
    expect(audit.map((e) => [e.action, e.entityId])).toEqual([
      ['classroom.validity', current],
      ['classroom.validity', old],
    ]);
  });

  it('refuses reactivation beyond ten school years but allows a timeless classroom', async () => {
    f.setNow('2034-10-15T10:00:00Z'); // 2023 → 2034 would need 12 years
    try {
      await expect(
        f.call('adminUpdateValidity', admin, { classroomId: old, reactivate: true }),
      ).rejects.toMatchObject({
        code: 'validation',
        message: expect.stringMatching(/10 cursos/),
      });
      const r = await f.call('adminUpdateValidity', admin, { classroomId: old, timeless: true });
      expect(r).toMatchObject({ timeless: true, current: true });
      expect(
        (await f.call('adminListClassrooms', admin, { filter: 'timeless' })).map((c) => c.id),
      ).toEqual([old]);
      expect(
        (await f.call('adminListClassrooms', admin, { filter: 'expired' })).map((c) => c.id),
      ).toEqual([current]);
      await f.call('adminUpdateValidity', admin, { classroomId: old, timeless: false });
    } finally {
      f.setNow('2025-10-15T10:00:00Z');
    }
  });

  it('reports statistics per school year and global totals', async () => {
    await expect(f.call('adminStats', student, {})).rejects.toMatchObject({ code: 'forbidden' });
    const s = await f.call('adminStats', support, {});
    expect(s.bySchoolYear).toEqual([
      { schoolYear: 2025, classrooms: 1, current: 1, members: 2, published: 1 },
      { schoolYear: 2023, classrooms: 1, current: 1, members: 1, published: 0 },
    ]);
    expect(s.totals).toEqual({
      users: 4,
      personalDictionaries: 1,
      personalEntries: 1,
      classrooms: 2,
      submissions: 1,
    });
  });

  it('shows the audit trail only to admins, newest first, honouring the limit', async () => {
    await expect(f.call('adminAudit', support, {})).rejects.toMatchObject({ code: 'forbidden' });
    const all = await f.call('adminAudit', admin, {});
    expect(all.length).toBeGreaterThan(3);
    expect(all.map((e) => e.action)).toContain('submission.publish');
    expect(all.map((e) => e.action)).toContain('classroom.create');
    const times = all.map((e) => e.createdAt);
    expect([...times].sort().reverse()).toEqual(times);
    expect(await f.call('adminAudit', admin, { limit: 1 })).toHaveLength(1);
  });

  it('creates and edits vocabulary values; duplicate codes are conflicts', async () => {
    const input = { vocabulary: 'topic', code: 'volcanes', label: 'Volcanes' };
    await expect(f.call('adminSaveVocabularyValue', support, input)).rejects.toMatchObject({
      code: 'forbidden',
    });
    const v = await f.call('adminSaveVocabularyValue', admin, input);
    expect(v).toMatchObject({
      vocabulary: 'topic',
      code: 'volcanes',
      label: 'Volcanes',
      abbreviation: null,
      active: true,
    });
    const edited = await f.call('adminSaveVocabularyValue', admin, {
      ...input,
      id: v.id,
      label: 'Volcanes y lava',
      featured: true,
    });
    expect(edited).toMatchObject({ id: v.id, label: 'Volcanes y lava', featured: true });
    expect((await f.call('vocabularies', student, {})).find((x) => x.id === v.id)?.label).toBe(
      'Volcanes y lava',
    );
    await expect(f.call('adminSaveVocabularyValue', admin, input)).rejects.toMatchObject({
      code: 'conflict',
    });
    await expect(
      f.call('adminSaveVocabularyValue', admin, { ...input, code: 'otro', id: missing }),
    ).rejects.toMatchObject({
      code: 'not_found',
    });
    await expect(
      f.call('adminSaveVocabularyValue', admin, { ...input, code: 'Con Mayúsculas' }),
    ).rejects.toMatchObject({
      code: 'validation',
    });
  });
});
