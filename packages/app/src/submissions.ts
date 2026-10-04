import {
  DomainError,
  type EntrySnapshot,
  type EntryView,
  headwordKey,
  type ParsedInput,
  type PublishResult,
  SENSE_FIELDS,
  type SenseField,
  type SubmissionView,
  type SubmitResult,
  submissionWindowState,
  type WindowState,
} from '@lexican/core';
import { dictionaries, entries, entryRevisions, submissions, users } from '@lexican/db';
import { and, desc, eq, inArray, isNull, ne } from 'drizzle-orm';
import { alias } from 'drizzle-orm/pg-core';
import { assertEditable, classroomAccess, type SettingsRow } from './access.ts';
import { type Actor, audit, type Deps, iso, notFound, requireUser } from './context.ts';
import { addRevision, inputFromSnapshot, insertEntry, replaceEntry } from './entries.ts';
import { entryViews } from './views.ts';

const WINDOW_MESSAGES: Record<Exclude<WindowState, 'open'>, string> = {
  expired: 'El diccionario de aula ya no está vigente.',
  disabled: 'El profesorado no admite envíos en este momento.',
  not_started: 'El plazo de envíos aún no ha empezado.',
  ended: 'El plazo de envíos ha terminado.',
};

const fieldFilled = (s: EntryView['senses'][number], f: SenseField): boolean => {
  switch (f) {
    case 'part_of_speech':
      return !!s.partOfSpeechId;
    case 'gender':
      return !!s.genderId;
    case 'number':
      return !!s.numberId;
    case 'topics':
      return s.topicIds.length > 0;
    case 'extra_info':
      return s.extraInfo !== '';
    case 'example':
      return s.example !== '';
    case 'language':
      return !!s.languageId && s.foreignForm !== '';
    case 'image':
    case 'audio':
    case 'video':
      return s.media.some((m) => m.kind === f);
  }
};

/** Why an entry cannot be sent to a classroom, or null (RULE-076, -042, -195 now enforced server-side). */
export function submissionProblem(entry: EntryView, s: SettingsRow): string | null {
  if (entry.hidden) return 'La entrada está oculta.';
  const visible = entry.senses.filter((x) => !x.hidden);
  if (visible.length === 0) return 'La entrada no tiene ninguna acepción visible.';
  if (s.maxSenses && visible.length > s.maxSenses)
    return `El diccionario admite como máximo ${s.maxSenses} acepciones por entrada.`;
  const missing = (s.requiredFields as SenseField[]).filter((f) =>
    visible.some((x) => !fieldFilled(x, f)),
  );
  if (missing.length)
    return `Faltan campos obligatorios: ${missing.map((f) => SENSE_FIELDS.find((x) => x.code === f)!.label.toLowerCase()).join(', ')}.`;
  return null;
}

export function submissionServices(deps: Deps) {
  const { db } = deps;
  const reviewer = alias(users, 'reviewer');

  /** Media URLs are adapter-specific, so they are recomputed when a stored snapshot is read. */
  const withUrls = (s: EntrySnapshot): EntrySnapshot => ({
    ...s,
    senses: s.senses.map((x) => ({
      ...x,
      media: x.media.map((m) => ({ ...m, url: deps.mediaUrl(m.id) })),
    })),
  });

  async function views(where: ReturnType<typeof and>): Promise<SubmissionView[]> {
    const rows = await db
      .select({
        s: submissions,
        r: entryRevisions,
        title: dictionaries.title,
        by: users.displayName,
        reviewer: reviewer.displayName,
      })
      .from(submissions)
      .innerJoin(entryRevisions, eq(entryRevisions.id, submissions.revisionId))
      .innerJoin(dictionaries, eq(dictionaries.id, submissions.classroomId))
      .innerJoin(users, eq(users.id, submissions.submittedBy))
      .leftJoin(reviewer, eq(reviewer.id, submissions.reviewedBy))
      .where(where)
      .orderBy(desc(submissions.submittedAt));
    const classroomIds = [...new Set(rows.map((r) => r.s.classroomId))];
    const published = classroomIds.length
      ? await db
          .select({
            id: entries.id,
            headword: entries.headword,
            key: entries.headwordKey,
            classroomId: entries.dictionaryId,
            source: submissions.sourceEntryId,
          })
          .from(entries)
          .leftJoin(submissions, eq(submissions.id, entries.sourceSubmissionId))
          .where(and(inArray(entries.dictionaryId, classroomIds), isNull(entries.deletedAt)))
      : [];
    return rows.map(({ s, r, title, by, reviewer: rv }) => {
      const clash = published.find(
        (p) =>
          p.classroomId === s.classroomId &&
          p.key === headwordKey(r.snapshot.headword) &&
          p.source !== s.sourceEntryId,
      );
      return {
        id: s.id,
        classroomId: s.classroomId,
        classroomTitle: title,
        status: s.status,
        submittedAt: s.submittedAt.toISOString(),
        reviewedAt: iso(s.reviewedAt),
        reviewNote: s.reviewNote,
        sourceEntryId: s.sourceEntryId,
        submittedBy: { id: s.submittedBy, displayName: by },
        reviewedBy: s.reviewedBy ? { id: s.reviewedBy, displayName: rv ?? '' } : null,
        snapshot: withUrls(r.snapshot),
        publishedEntryId: s.publishedEntryId,
        conflict:
          s.status === 'pending' && clash ? { entryId: clash.id, headword: clash.headword } : null,
      };
    });
  }

  async function loadSubmission(actor: Actor, submissionId: string) {
    const [s] = await db.select().from(submissions).where(eq(submissions.id, submissionId));
    if (!s) throw notFound('El envío');
    const acc = await classroomAccess(deps, actor, s.classroomId).catch(() => null);
    return { s, acc };
  }

  return {
    async submitEntries(
      actor: Actor | null,
      input: ParsedInput<'submitEntries'>,
    ): Promise<SubmitResult> {
      const a = requireUser(actor);
      const owned = await db
        .select({ id: entries.id })
        .from(entries)
        .innerJoin(dictionaries, eq(dictionaries.id, entries.dictionaryId))
        .where(
          and(
            inArray(entries.id, input.entryIds),
            isNull(entries.deletedAt),
            eq(dictionaries.kind, 'personal'),
            eq(dictionaries.ownerId, a.userId),
          ),
        );
      if (owned.length !== new Set(input.entryIds).size)
        throw new DomainError(
          'forbidden',
          'Solo puedes enviar entradas de tu diccionario personal.',
        );
      const list = await entryViews(deps, input.entryIds, {
        hideHidden: false,
        withSubmissions: false,
      });

      const result: SubmitResult = { created: [], problems: [] };
      const targets: { classroomId: string; title: string }[] = [];
      for (const classroomId of input.classroomIds) {
        const acc = await classroomAccess(deps, a, classroomId);
        const problem = !acc.role
          ? 'No participas en este diccionario de aula.' // RULE-072 fixed
          : ((s) => (s === 'open' ? null : WINDOW_MESSAGES[s]))(
              submissionWindowState(acc.settings!, deps.clock(), deps.schoolYear),
            );
        for (const e of list) {
          const p = problem ?? submissionProblem(e, acc.settings!);
          if (p)
            result.problems.push({
              entryId: e.id,
              headword: e.headword,
              classroomId,
              classroomTitle: acc.dict.title,
              message: p,
            });
        }
        if (!problem) targets.push({ classroomId, title: acc.dict.title });
      }
      if (input.dryRun) return result;

      for (const e of list) {
        const ok = targets.filter(
          (t) =>
            !result.problems.some((p) => p.entryId === e.id && p.classroomId === t.classroomId),
        );
        if (ok.length === 0) continue;
        await db.transaction(async (tx) => {
          const revisionId = await addRevision({ ...deps, db: tx }, a, e, 'submit');
          for (const t of ok) {
            const now = deps.clock();
            // RULE-229 default: a pending submission is refreshed instead of queueing a duplicate.
            const [pending] = await tx
              .update(submissions)
              .set({ revisionId, submittedAt: now })
              .where(
                and(
                  eq(submissions.sourceEntryId, e.id),
                  eq(submissions.classroomId, t.classroomId),
                  eq(submissions.status, 'pending'),
                ),
              )
              .returning();
            const row =
              pending ??
              (
                await tx
                  .insert(submissions)
                  .values({
                    classroomId: t.classroomId,
                    sourceEntryId: e.id,
                    submittedBy: a.userId,
                    revisionId,
                    submittedAt: now,
                  })
                  .returning()
              )[0]!;
            result.created.push({
              id: row.id,
              classroomId: t.classroomId,
              classroomTitle: t.title,
              status: row.status,
              submittedAt: row.submittedAt.toISOString(),
              reviewedAt: null,
              reviewNote: '',
            });
          }
        });
      }
      return result;
    },

    async withdrawSubmission(
      actor: Actor | null,
      { submissionId }: ParsedInput<'withdrawSubmission'>,
    ) {
      const a = requireUser(actor);
      const [s] = await db.select().from(submissions).where(eq(submissions.id, submissionId));
      if (!s || s.submittedBy !== a.userId) throw notFound('El envío');
      if (s.status !== 'pending')
        throw new DomainError('conflict', 'Solo se pueden retirar envíos pendientes.');
      await db
        .update(submissions)
        .set({ status: 'withdrawn' })
        .where(eq(submissions.id, submissionId));
      return { ok: true as const };
    },

    async listSubmissions(
      actor: Actor | null,
      q: ParsedInput<'listSubmissions'>,
    ): Promise<SubmissionView[]> {
      const a = requireUser(actor);
      assertEditable(await classroomAccess(deps, a, q.classroomId), { requireCurrent: false });
      const list = await views(
        and(
          eq(submissions.classroomId, q.classroomId),
          q.status ? eq(submissions.status, q.status) : ne(submissions.status, 'withdrawn'),
          q.studentId ? eq(submissions.submittedBy, q.studentId) : undefined,
        ),
      );
      const needle = q.q ? headwordKey(q.q) : null;
      return needle ? list.filter((s) => headwordKey(s.snapshot.headword).includes(needle)) : list;
    },

    async getSubmission(
      actor: Actor | null,
      { submissionId }: ParsedInput<'getSubmission'>,
    ): Promise<SubmissionView> {
      const a = requireUser(actor);
      const { s, acc } = await loadSubmission(a, submissionId);
      if (s.submittedBy !== a.userId && !acc?.canEdit) throw notFound('El envío');
      return (await views(and(eq(submissions.id, submissionId))))[0]!;
    },

    async publishSubmissions(
      actor: Actor | null,
      { submissionIds }: ParsedInput<'publishSubmissions'>,
    ): Promise<PublishResult> {
      const a = requireUser(actor);
      const result: PublishResult = { published: [], conflicts: [] };
      for (const submissionId of submissionIds) {
        const { s, acc } = await loadSubmission(a, submissionId);
        if (!acc) throw notFound('El envío');
        assertEditable(acc);
        const [rev] = await db
          .select()
          .from(entryRevisions)
          .where(eq(entryRevisions.id, s.revisionId));
        const snapshot = rev!.snapshot;
        if (s.status !== 'pending') {
          result.conflicts.push({
            submissionId,
            headword: snapshot.headword,
            message: 'El envío ya fue revisado.',
          });
          continue;
        }
        // An earlier publication of the same personal entry is updated in place (brief §5.9).
        const [previous] = await db
          .select({ id: entries.id })
          .from(entries)
          .innerJoin(submissions, eq(submissions.id, entries.sourceSubmissionId))
          .where(
            and(
              eq(entries.dictionaryId, s.classroomId),
              isNull(entries.deletedAt),
              eq(submissions.sourceEntryId, s.sourceEntryId),
            ),
          );
        const [clash] = await db
          .select({ id: entries.id })
          .from(entries)
          .where(
            and(
              eq(entries.dictionaryId, s.classroomId),
              isNull(entries.deletedAt),
              eq(entries.headwordKey, headwordKey(snapshot.headword)),
              previous ? ne(entries.id, previous.id) : undefined,
            ),
          );
        if (clash) {
          result.conflicts.push({
            submissionId,
            headword: snapshot.headword,
            message: `Ya hay una entrada publicada «${snapshot.headword}» en este diccionario (RULE-057).`,
          });
          continue;
        }
        const entryInput = inputFromSnapshot(snapshot);
        const kinds = new Map(
          snapshot.senses.flatMap((x) => x.media.map((m) => [m.id, m.kind] as [string, string])),
        );
        await db.transaction(async (tx) => {
          const entryId = previous
            ? await replaceEntry(tx, a, previous.id, entryInput, kinds, {
                sourceSubmissionId: s.id,
              })
            : await insertEntry(tx, a, s.classroomId, entryInput, kinds, {
                sourceSubmissionId: s.id,
                createdBy: s.submittedBy,
              });
          await tx
            .update(submissions)
            .set({
              status: 'published',
              reviewedAt: deps.clock(),
              reviewedBy: a.userId,
              publishedEntryId: entryId,
            })
            .where(eq(submissions.id, s.id));
          const [view] = await entryViews({ ...deps, db: tx }, [entryId], {
            hideHidden: false,
            withSubmissions: false,
          });
          await addRevision({ ...deps, db: tx }, a, view!, 'publish');
        });
        await audit(db, a, 'submission.publish', 'submission', s.id);
        result.published.push(submissionId);
      }
      return result;
    },

    async rejectSubmission(
      actor: Actor | null,
      { submissionId, note }: ParsedInput<'rejectSubmission'>,
    ) {
      const a = requireUser(actor);
      const { s, acc } = await loadSubmission(a, submissionId);
      if (!acc) throw notFound('El envío');
      assertEditable(acc, { requireCurrent: false });
      if (s.status !== 'pending') throw new DomainError('conflict', 'El envío ya fue revisado.');
      await db
        .update(submissions)
        .set({
          status: 'rejected',
          reviewNote: note,
          reviewedAt: deps.clock(),
          reviewedBy: a.userId,
        })
        .where(eq(submissions.id, submissionId));
      await audit(db, a, 'submission.reject', 'submission', submissionId);
      return { ok: true as const };
    },
  };
}
