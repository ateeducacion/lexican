import {
  isClassroomCurrent,
  schoolYearOf,
  DomainError,
  validityToReactivate,
  type AdminClassroomView,
  type ParsedInput,
  type StatsView,
  type VocabularyValue,
} from '@lexican/core';
import {
  auditEvents,
  classroomSettings,
  dictionaries,
  dictionaryMemberships,
  entries,
  submissions,
  users,
  vocabularyValues,
} from '@lexican/db';
import { and, asc, desc, eq, ilike, isNull, or, sql } from 'drizzle-orm';
import {
  audit,
  isUniqueViolation,
  notFound,
  requireRole,
  type Actor,
  type Deps,
} from './context.ts';
import { toUserView, toVocabularyValue } from './views.ts';

/** Own admin screens replacing Voyager (§37): only what the inventory showed in use. */
export function adminServices(deps: Deps) {
  const { db } = deps;

  async function classrooms(where?: ReturnType<typeof and>): Promise<AdminClassroomView[]> {
    const rows = await db
      .select({
        d: dictionaries,
        s: classroomSettings,
        owner: users.displayName,
        members: sql<number>`(select count(*)::int from ${dictionaryMemberships} where ${dictionaryMemberships.dictionaryId} = ${dictionaries.id})`,
        entries: sql<number>`(select count(*)::int from ${entries} where ${entries.dictionaryId} = ${dictionaries.id} and ${entries.deletedAt} is null)`,
      })
      .from(dictionaries)
      .innerJoin(classroomSettings, eq(classroomSettings.dictionaryId, dictionaries.id))
      .innerJoin(users, eq(users.id, dictionaries.ownerId))
      .where(and(isNull(dictionaries.deletedAt), where))
      .orderBy(desc(classroomSettings.schoolYear), asc(dictionaries.title));
    return rows.map((r) => ({
      id: r.d.id,
      title: r.d.title,
      owner: r.owner,
      schoolYear: r.s.schoolYear,
      validityYears: r.s.validityYears,
      timeless: r.s.timeless,
      current: isClassroomCurrent(r.s, deps.clock(), deps.schoolYear),
      memberCount: r.members,
      entryCount: r.entries,
    }));
  }

  return {
    async adminListUsers(actor: Actor | null, { q }: ParsedInput<'adminListUsers'>) {
      requireRole(actor, 'admin');
      const rows = await db
        .select()
        .from(users)
        .where(q ? or(ilike(users.displayName, `%${q}%`), ilike(users.email, `%${q}%`)) : undefined)
        .orderBy(asc(users.lastName), asc(users.firstName))
        .limit(200);
      return rows.map(toUserView);
    },

    async adminSetUserRole(
      actor: Actor | null,
      { userId, globalRole }: ParsedInput<'adminSetUserRole'>,
    ) {
      const a = requireRole(actor, 'admin');
      if (userId === a.userId)
        throw new DomainError('validation', 'No puedes cambiar tu propio rol.');
      const [u] = await db
        .update(users)
        .set({ globalRole, updatedAt: new Date() })
        .where(eq(users.id, userId))
        .returning();
      if (!u) throw notFound('La persona usuaria');
      await audit(db, a, 'user.role', 'user', userId, { globalRole });
      return toUserView(u);
    },

    async adminListClassrooms(actor: Actor | null, { filter }: ParsedInput<'adminListClassrooms'>) {
      requireRole(actor, 'admin', 'support');
      const all = await classrooms(
        filter === 'timeless' ? and(eq(classroomSettings.timeless, true)) : undefined,
      );
      if (filter === 'current') return all.filter((c) => c.current);
      if (filter === 'expired') return all.filter((c) => !c.current);
      return all;
    },

    async adminUpdateValidity(actor: Actor | null, input: ParsedInput<'adminUpdateValidity'>) {
      const a = requireRole(actor, 'admin', 'support');
      const [s] = await db
        .select()
        .from(classroomSettings)
        .where(eq(classroomSettings.dictionaryId, input.classroomId));
      if (!s) throw notFound('El diccionario de aula');
      const patch: Partial<typeof classroomSettings.$inferInsert> = {};
      if (input.timeless !== undefined) patch.timeless = input.timeless;
      if (input.reactivate) {
        const v = validityToReactivate(s.schoolYear, deps.clock(), deps.schoolYear);
        if (v === null)
          throw new DomainError(
            'validation',
            'No se puede reactivar: superaría la vigencia máxima de 10 cursos.',
          );
        patch.validityYears = Math.max(v, s.validityYears);
      }
      await db
        .update(classroomSettings)
        .set(patch)
        .where(eq(classroomSettings.dictionaryId, input.classroomId));
      await audit(db, a, 'classroom.validity', 'dictionary', input.classroomId, {
        timeless: input.timeless ?? null,
        reactivate: input.reactivate ?? null,
      });
      return (await classrooms(and(eq(dictionaries.id, input.classroomId))))[0]!;
    },

    async adminStats(actor: Actor | null): Promise<StatsView> {
      requireRole(actor, 'admin', 'support');
      const all = await classrooms();
      const published = await db
        .select({ year: classroomSettings.schoolYear, n: sql<number>`count(*)::int` })
        .from(entries)
        .innerJoin(classroomSettings, eq(classroomSettings.dictionaryId, entries.dictionaryId))
        .where(isNull(entries.deletedAt))
        .groupBy(classroomSettings.schoolYear);
      const years = [...new Set(all.map((c) => c.schoolYear))].sort((x, y) => y - x);
      const count = async (q: Promise<{ n: number }[]>) => (await q)[0]?.n ?? 0;
      const n = sql<number>`count(*)::int`;
      return {
        bySchoolYear: years.map((y) => {
          const inYear = all.filter((c) => c.schoolYear === y);
          return {
            schoolYear: y,
            classrooms: inYear.length,
            current: inYear.filter((c) => c.current).length,
            members: inYear.reduce((t, c) => t + c.memberCount, 0),
            published: published.find((p) => p.year === y)?.n ?? 0,
          };
        }),
        totals: {
          users: await count(db.select({ n }).from(users)),
          personalDictionaries: await count(
            db
              .select({ n })
              .from(dictionaries)
              .where(and(eq(dictionaries.kind, 'personal'), isNull(dictionaries.deletedAt))),
          ),
          personalEntries: await count(
            db
              .select({ n })
              .from(entries)
              .innerJoin(dictionaries, eq(dictionaries.id, entries.dictionaryId))
              .where(and(eq(dictionaries.kind, 'personal'), isNull(entries.deletedAt))),
          ),
          classrooms: all.length,
          submissions: await count(db.select({ n }).from(submissions)),
        },
      };
    },

    async adminAudit(actor: Actor | null, { limit }: ParsedInput<'adminAudit'>) {
      requireRole(actor, 'admin');
      const rows = await db
        .select({ e: auditEvents, actor: users.displayName })
        .from(auditEvents)
        .leftJoin(users, eq(users.id, auditEvents.actorId))
        .orderBy(desc(auditEvents.createdAt))
        .limit(limit);
      return rows.map((r) => ({
        id: r.e.id,
        actor: r.actor,
        action: r.e.action,
        entityType: r.e.entityType,
        entityId: r.e.entityId,
        createdAt: r.e.createdAt.toISOString(),
      }));
    },

    async adminSaveVocabularyValue(
      actor: Actor | null,
      input: ParsedInput<'adminSaveVocabularyValue'>,
    ): Promise<VocabularyValue> {
      const a = requireRole(actor, 'admin');
      const { id, ...values } = input;
      try {
        const [row] = id
          ? await db
              .update(vocabularyValues)
              .set(values)
              .where(eq(vocabularyValues.id, id))
              .returning()
          : await db.insert(vocabularyValues).values(values).returning();
        if (!row) throw notFound('El valor');
        await audit(db, a, 'vocabulary.save', 'vocabulary_value', row.id, {
          vocabulary: row.vocabulary,
        });
        return toVocabularyValue(row);
      } catch (e) {
        if (isUniqueViolation(e))
          throw new DomainError('conflict', 'Ya existe un valor con ese código en la lista.');
        throw e;
      }
    },

    currentSchoolYear: () => schoolYearOf(deps.clock(), deps.schoolYear),
  };
}
