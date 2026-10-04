import {
  DomainError,
  JOIN_CODE_ALPHABET,
  schoolYearOf,
  type ClassroomInput,
  type DictionaryView,
  type MemberView,
  type ParsedInput,
} from '@lexican/core';
import {
  classroomSettings,
  dictionaries,
  dictionaryMemberships,
  submissions,
  users,
  type Db,
} from '@lexican/db';
import { and, asc, eq, isNull, sql } from 'drizzle-orm';
import {
  assertEditable,
  classroomAccess,
  dictionaryAccess,
  type DictionaryAccess,
} from './access.ts';
import {
  audit,
  isUniqueViolation,
  notFound,
  requireRole,
  requireUser,
  type Actor,
  type Deps,
} from './context.ts';
import { dictionaryViews } from './views.ts';

/** Six characters without look-alikes (0/O, 1/I), generated on the server (RULE-002/048 fixed). */
export function generateJoinCode(
  random: (n: number) => Uint8Array = (n) => crypto.getRandomValues(new Uint8Array(n)),
): string {
  return [...random(6)].map((b) => JOIN_CODE_ALPHABET[b % JOIN_CODE_ALPHABET.length]).join('');
}

/** Required fields are always visible. */
const settingsValues = (c: ClassroomInput) => ({
  studyLevelId: c.studyLevelId,
  subjectId: c.subjectId,
  groupLabel: c.groupLabel,
  validityYears: c.validityYears,
  maxSenses: c.maxSenses,
  visibleFields: [...new Set([...c.visibleFields, ...c.requiredFields])],
  requiredFields: [...new Set(c.requiredFields)],
  guidelines: c.guidelines,
  visibleToStudents: c.visibleToStudents,
  submissionsEnabled: c.submissionsEnabled,
  submissionsStartAt: c.submissionsStartAt,
  submissionsEndAt: c.submissionsEndAt,
  commentsVisibility: c.commentsVisibility,
  commentsVisibleBefore: c.commentsVisibleBefore,
});

async function withFreshCode<T>(fn: (code: string) => Promise<T>): Promise<T> {
  for (let attempt = 0; ; attempt++) {
    try {
      return await fn(generateJoinCode());
    } catch (e) {
      if (!isUniqueViolation(e) || attempt >= 5) throw e;
    }
  }
}

async function teacherCount(db: Db, classroomId: string): Promise<number> {
  const [r] = await db
    .select({ n: sql<number>`count(*)::int` })
    .from(dictionaryMemberships)
    .where(
      and(
        eq(dictionaryMemberships.dictionaryId, classroomId),
        eq(dictionaryMemberships.role, 'teacher'),
        eq(dictionaryMemberships.active, true),
      ),
    );
  return r?.n ?? 0;
}

export function classroomServices(deps: Deps) {
  const { db } = deps;

  const view = async (
    actor: Actor,
    acc: Pick<DictionaryAccess, 'dict' | 'settings'>,
  ): Promise<DictionaryView> => (await dictionaryViews(deps, actor, [acc]))[0]!;

  const reload = async (actor: Actor, id: string) =>
    view(actor, await dictionaryAccess(deps, actor, id));

  async function listMembers(
    actor: Actor | null,
    { classroomId }: ParsedInput<'listMembers'>,
  ): Promise<MemberView[]> {
    const acc = await classroomAccess(deps, actor, classroomId);
    assertEditable(acc, { requireCurrent: false });
    const rows = await db
      .select({
        m: dictionaryMemberships,
        name: users.displayName,
        n: sql<number>`(select count(*)::int from ${submissions} where ${submissions.classroomId} = ${classroomId} and ${submissions.submittedBy} = ${users.id})`,
      })
      .from(dictionaryMemberships)
      .innerJoin(users, eq(users.id, dictionaryMemberships.userId))
      .where(eq(dictionaryMemberships.dictionaryId, classroomId))
      .orderBy(asc(users.lastName), asc(users.firstName), asc(users.displayName));
    return rows.map((r) => ({
      user: { id: r.m.userId, displayName: r.name },
      role: r.m.role,
      active: r.m.active,
      isOwner: r.m.userId === acc.dict.ownerId,
      submissionCount: r.n,
    }));
  }

  return {
    listMembers,

    async myClassrooms(actor: Actor | null): Promise<DictionaryView[]> {
      const a = requireUser(actor);
      const rows = await db
        .select({ dict: dictionaries, settings: classroomSettings })
        .from(dictionaryMemberships)
        .innerJoin(dictionaries, eq(dictionaries.id, dictionaryMemberships.dictionaryId))
        .leftJoin(classroomSettings, eq(classroomSettings.dictionaryId, dictionaries.id))
        .where(
          and(
            eq(dictionaryMemberships.userId, a.userId),
            eq(dictionaryMemberships.active, true),
            eq(dictionaries.kind, 'classroom'),
            isNull(dictionaries.deletedAt),
          ),
        )
        .orderBy(sql`${classroomSettings.schoolYear} desc`, asc(dictionaries.title));
      return dictionaryViews(deps, a, rows);
    },

    async getDictionary(
      actor: Actor | null,
      { dictionaryId }: ParsedInput<'getDictionary'>,
    ): Promise<DictionaryView> {
      const a = requireUser(actor);
      const acc = await dictionaryAccess(deps, a, dictionaryId);
      // Students of a hidden classroom still see its card (guidelines, window) but not its entries.
      if (!acc.canRead && acc.role !== 'student') throw notFound('El diccionario');
      return view(a, acc);
    },

    async createClassroom(
      actor: Actor | null,
      input: ParsedInput<'createClassroom'>,
    ): Promise<DictionaryView> {
      const a = requireRole(actor, 'teacher', 'admin');
      const id = await withFreshCode((joinCode) =>
        db.transaction(async (tx) => {
          const [d] = await tx
            .insert(dictionaries)
            .values({
              kind: 'classroom',
              title: input.title,
              description: input.description,
              ownerId: a.userId,
            })
            .returning({ id: dictionaries.id });
          await tx.insert(classroomSettings).values({
            dictionaryId: d!.id,
            joinCode,
            schoolYear: schoolYearOf(deps.clock(), deps.schoolYear),
            ...settingsValues(input),
          });
          await tx
            .insert(dictionaryMemberships)
            .values({ dictionaryId: d!.id, userId: a.userId, role: 'teacher' });
          return d!.id;
        }),
      );
      await audit(db, a, 'classroom.create', 'dictionary', id);
      return reload(a, id);
    },

    async updateClassroom(
      actor: Actor | null,
      { classroomId, classroom }: ParsedInput<'updateClassroom'>,
    ): Promise<DictionaryView> {
      const a = requireUser(actor);
      const acc = await classroomAccess(deps, a, classroomId);
      assertEditable(acc);
      const elapsed = schoolYearOf(deps.clock(), deps.schoolYear) - acc.settings!.schoolYear + 1;
      if (!acc.settings!.timeless && classroom.validityYears < elapsed)
        throw new DomainError(
          'validation',
          `La vigencia no puede ser inferior a ${elapsed} cursos (ya transcurridos).`,
          {
            validityYears: [`Mínimo ${elapsed}`],
          },
        );
      await db.transaction(async (tx) => {
        await tx
          .update(dictionaries)
          .set({
            title: classroom.title,
            description: classroom.description,
            updatedAt: new Date(),
          })
          .where(eq(dictionaries.id, classroomId));
        await tx
          .update(classroomSettings)
          .set(settingsValues(classroom))
          .where(eq(classroomSettings.dictionaryId, classroomId));
      });
      return reload(a, classroomId);
    },

    async deleteClassroom(actor: Actor | null, { classroomId }: ParsedInput<'deleteClassroom'>) {
      const a = requireUser(actor);
      const acc = await classroomAccess(deps, a, classroomId);
      if (!acc.isOwner && a.globalRole !== 'admin')
        throw new DomainError(
          'forbidden',
          'Solo quien creó el diccionario de aula puede borrarlo.',
        );
      await db
        .update(dictionaries)
        .set({ deletedAt: new Date() })
        .where(eq(dictionaries.id, classroomId));
      await db
        .update(submissions)
        .set({ status: 'withdrawn' })
        .where(and(eq(submissions.classroomId, classroomId), eq(submissions.status, 'pending')));
      await audit(db, a, 'classroom.delete', 'dictionary', classroomId);
      return { ok: true as const };
    },

    async joinClassroom(
      actor: Actor | null,
      { code }: ParsedInput<'joinClassroom'>,
    ): Promise<DictionaryView> {
      const a = requireUser(actor);
      const [row] = await db
        .select({ dict: dictionaries, settings: classroomSettings })
        .from(classroomSettings)
        .innerJoin(dictionaries, eq(dictionaries.id, classroomSettings.dictionaryId))
        .where(and(eq(classroomSettings.joinCode, code), isNull(dictionaries.deletedAt)));
      if (!row)
        throw new DomainError('not_found', 'No hay ningún diccionario de aula con ese código.', {
          code: ['Código no encontrado'],
        });
      const acc = await dictionaryAccess(deps, a, row.dict.id);
      if (!acc.current)
        throw new DomainError('validation', 'Ese diccionario de aula ya no está vigente.');
      const [m] = await db
        .select()
        .from(dictionaryMemberships)
        .where(
          and(
            eq(dictionaryMemberships.dictionaryId, row.dict.id),
            eq(dictionaryMemberships.userId, a.userId),
          ),
        );
      if (m?.active)
        throw new DomainError('conflict', 'Ya participas en este diccionario de aula.');
      if (m)
        throw new DomainError(
          'forbidden',
          'El profesorado ha deshabilitado tu participación en este diccionario.',
        );
      await db
        .insert(dictionaryMemberships)
        .values({ dictionaryId: row.dict.id, userId: a.userId, role: 'student' });
      return reload(a, row.dict.id);
    },

    async regenerateJoinCode(
      actor: Actor | null,
      { classroomId }: ParsedInput<'regenerateJoinCode'>,
    ): Promise<DictionaryView> {
      const a = requireUser(actor);
      assertEditable(await classroomAccess(deps, a, classroomId));
      await withFreshCode((joinCode) =>
        db
          .update(classroomSettings)
          .set({ joinCode })
          .where(eq(classroomSettings.dictionaryId, classroomId)),
      );
      return reload(a, classroomId);
    },

    async updateMember(
      actor: Actor | null,
      input: ParsedInput<'updateMember'>,
    ): Promise<MemberView[]> {
      const a = requireUser(actor);
      const acc = await classroomAccess(deps, a, input.classroomId);
      assertEditable(acc, { requireCurrent: false });
      if (input.userId === a.userId)
        throw new DomainError('validation', 'No puedes cambiar tu propia participación.');
      if (input.userId === acc.dict.ownerId && (input.role === 'student' || input.active === false))
        throw new DomainError('validation', 'No se puede quitar a quien creó el diccionario.');
      const [m] = await db
        .select()
        .from(dictionaryMemberships)
        .where(
          and(
            eq(dictionaryMemberships.dictionaryId, input.classroomId),
            eq(dictionaryMemberships.userId, input.userId),
          ),
        );
      if (!m) throw notFound('La persona participante');
      const losesTeacher =
        m.role === 'teacher' && m.active && (input.role === 'student' || input.active === false);
      if (losesTeacher && (await teacherCount(db, input.classroomId)) <= 1)
        throw new DomainError('validation', 'El diccionario necesita al menos un docente.');
      await db
        .update(dictionaryMemberships)
        .set({
          ...(input.role ? { role: input.role } : {}),
          ...(input.active !== undefined ? { active: input.active } : {}),
          updatedAt: new Date(),
        })
        .where(
          and(
            eq(dictionaryMemberships.dictionaryId, input.classroomId),
            eq(dictionaryMemberships.userId, input.userId),
          ),
        );
      await audit(db, a, 'membership.update', 'dictionary', input.classroomId, {
        role: input.role ?? null,
        active: input.active ?? null,
      });
      return listMembers(a, { classroomId: input.classroomId });
    },
  };
}
