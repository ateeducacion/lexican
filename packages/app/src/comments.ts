import { DomainError, type CommentView, type ParsedInput } from '@lexican/core';
import {
  classroomSettings,
  comments,
  dictionaries,
  dictionaryMemberships,
  entryRevisions,
  submissions,
  users,
} from '@lexican/db';
import { and, desc, eq, inArray, isNull, lte, or, sql } from 'drizzle-orm';
import { alias } from 'drizzle-orm/pg-core';
import { assertEditable, classroomAccess } from './access.ts';
import { audit, notFound, requireUser, type Actor, type Deps } from './context.ts';

/** Teacher feedback: plain text only (no HTML, RULE-046/186 XSS removed), one student per comment. */
export function commentServices(deps: Deps) {
  const { db } = deps;
  const student = alias(users, 'student');

  async function views(where: ReturnType<typeof and>): Promise<CommentView[]> {
    const rows = await db
      .select({
        c: comments,
        classroomTitle: dictionaries.title,
        author: users.displayName,
        student: student.displayName,
        headword: sql<string | null>`${entryRevisions.snapshot}->>'headword'`,
      })
      .from(comments)
      .innerJoin(dictionaries, eq(dictionaries.id, comments.classroomId))
      .innerJoin(classroomSettings, eq(classroomSettings.dictionaryId, comments.classroomId))
      .innerJoin(users, eq(users.id, comments.authorId))
      .innerJoin(student, eq(student.id, comments.studentId))
      .leftJoin(submissions, eq(submissions.id, comments.submissionId))
      .leftJoin(entryRevisions, eq(entryRevisions.id, submissions.revisionId))
      .where(and(isNull(comments.deletedAt), isNull(dictionaries.deletedAt), where))
      .orderBy(desc(comments.createdAt));
    return rows.map((r) => ({
      id: r.c.id,
      classroomId: r.c.classroomId,
      classroomTitle: r.classroomTitle,
      author: { id: r.c.authorId, displayName: r.author },
      student: { id: r.c.studentId, displayName: r.student },
      submissionId: r.c.submissionId,
      headword: r.headword,
      body: r.c.body,
      createdAt: r.c.createdAt.toISOString(),
    }));
  }

  return {
    async listComments(
      actor: Actor | null,
      q: ParsedInput<'listComments'>,
    ): Promise<CommentView[]> {
      const a = requireUser(actor);
      if (q.classroomId) {
        const acc = await classroomAccess(deps, a, q.classroomId);
        if (acc.canEdit)
          return views(
            and(
              eq(comments.classroomId, q.classroomId),
              q.studentId ? eq(comments.studentId, q.studentId) : undefined,
            ),
          );
        if (!acc.role) throw notFound('El diccionario');
      }
      // Students only see comments addressed to them, filtered by each classroom's visibility (RULE-185).
      const mine = await db
        .select({ id: dictionaryMemberships.dictionaryId })
        .from(dictionaryMemberships)
        .where(
          and(eq(dictionaryMemberships.userId, a.userId), eq(dictionaryMemberships.active, true)),
        );
      if (mine.length === 0) return [];
      return views(
        and(
          eq(comments.studentId, a.userId),
          inArray(
            comments.classroomId,
            mine.map((m) => m.id),
          ),
          q.classroomId ? eq(comments.classroomId, q.classroomId) : undefined,
          or(
            eq(classroomSettings.commentsVisibility, 'visible'),
            and(
              eq(classroomSettings.commentsVisibility, 'before_date'),
              lte(comments.createdAt, classroomSettings.commentsVisibleBefore),
            ),
          ),
        ),
      );
    },

    async createComment(
      actor: Actor | null,
      input: ParsedInput<'createComment'>,
    ): Promise<CommentView> {
      const a = requireUser(actor);
      const acc = await classroomAccess(deps, a, input.classroomId);
      assertEditable(acc, { requireCurrent: false });
      const [m] = await db
        .select()
        .from(dictionaryMemberships)
        .where(
          and(
            eq(dictionaryMemberships.dictionaryId, input.classroomId),
            eq(dictionaryMemberships.userId, input.studentId),
          ),
        );
      if (!m) throw new DomainError('validation', 'Esa persona no participa en el diccionario.');
      if (input.submissionId) {
        const [s] = await db
          .select()
          .from(submissions)
          .where(eq(submissions.id, input.submissionId));
        if (!s || s.classroomId !== input.classroomId || s.submittedBy !== input.studentId)
          throw new DomainError(
            'validation',
            'El envío no corresponde a ese diccionario o alumno.',
          );
      }
      const [row] = await db
        .insert(comments)
        .values({
          classroomId: input.classroomId,
          authorId: a.userId,
          studentId: input.studentId,
          submissionId: input.submissionId,
          body: input.body,
          createdAt: deps.clock(),
        })
        .returning({ id: comments.id });
      await audit(db, a, 'comment.create', 'comment', row!.id);
      return (await views(eq(comments.id, row!.id)))[0]!;
    },

    async deleteComment(actor: Actor | null, { commentId }: ParsedInput<'deleteComment'>) {
      const a = requireUser(actor);
      const [c] = await db
        .select()
        .from(comments)
        .where(and(eq(comments.id, commentId), isNull(comments.deletedAt)));
      if (!c) throw notFound('El comentario');
      const acc = await classroomAccess(deps, a, c.classroomId);
      if (c.authorId !== a.userId && !acc.canEdit)
        throw new DomainError('forbidden', 'No puedes borrar este comentario.');
      await db.update(comments).set({ deletedAt: new Date() }).where(eq(comments.id, commentId));
      return { ok: true as const };
    },
  };
}
