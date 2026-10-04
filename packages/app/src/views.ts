import {
  isClassroomCurrent,
  submissionWindowState,
  type ClassroomSettingsView,
  type DictionaryView,
  type EntrySnapshot,
  type EntryView,
  type MediaKind,
  type MemberRole,
  type SenseField,
  type SenseView,
  type SubmissionBrief,
  type UserView,
} from '@lexican/core';
import {
  dictionaries,
  dictionaryMemberships,
  entries,
  entrySenses,
  mediaAssets,
  senseMedia,
  senseTopics,
  submissions,
  users,
} from '@lexican/db';
import { and, asc, eq, inArray, isNull, sql } from 'drizzle-orm';
import type { DictionaryRow, SettingsRow } from './access.ts';
import { iso, type Actor, type Deps } from './context.ts';

export type UserRow = typeof users.$inferSelect;

export const toUserView = (u: UserRow): UserView => ({
  id: u.id,
  displayName: u.displayName,
  firstName: u.firstName,
  lastName: u.lastName,
  email: u.email,
  globalRole: u.globalRole,
  avatar: u.avatar,
});

export function settingsView(s: SettingsRow, deps: Deps): ClassroomSettingsView {
  const now = deps.clock();
  return {
    joinCode: s.joinCode,
    schoolYear: s.schoolYear,
    validityYears: s.validityYears,
    timeless: s.timeless,
    current: isClassroomCurrent(s, now, deps.schoolYear),
    windowState: submissionWindowState(s, now, deps.schoolYear),
    studyLevelId: s.studyLevelId,
    subjectId: s.subjectId,
    groupLabel: s.groupLabel,
    maxSenses: s.maxSenses,
    visibleFields: s.visibleFields as SenseField[],
    requiredFields: s.requiredFields as SenseField[],
    guidelines: s.guidelines,
    visibleToStudents: s.visibleToStudents,
    submissionsEnabled: s.submissionsEnabled,
    submissionsStartAt: iso(s.submissionsStartAt),
    submissionsEndAt: iso(s.submissionsEndAt),
    commentsVisibility: s.commentsVisibility,
    commentsVisibleBefore: iso(s.commentsVisibleBefore),
  };
}

/** Build dictionary cards in a fixed number of queries, whatever the list size. */
export async function dictionaryViews(
  deps: Deps,
  actor: Actor,
  rows: { dict: DictionaryRow; settings: SettingsRow | null }[],
): Promise<DictionaryView[]> {
  if (rows.length === 0) return [];
  const { db } = deps;
  const ids = rows.map((r) => r.dict.id);
  const owners = await db
    .select({ id: users.id, displayName: users.displayName })
    .from(users)
    .where(inArray(users.id, [...new Set(rows.map((r) => r.dict.ownerId))]));
  const roles = await db
    .select({ id: dictionaryMemberships.dictionaryId, role: dictionaryMemberships.role, active: dictionaryMemberships.active })
    .from(dictionaryMemberships)
    .where(and(inArray(dictionaryMemberships.dictionaryId, ids), eq(dictionaryMemberships.userId, actor.userId)));
  const roleOf = new Map(roles.filter((r) => r.active).map((r) => [r.id, r.role as MemberRole]));

  const counts = await db
    .select({
      id: entries.dictionaryId,
      all: sql<number>`count(*)::int`,
      visible: sql<number>`count(*) filter (where not ${entries.hidden})::int`,
    })
    .from(entries)
    .where(and(inArray(entries.dictionaryId, ids), isNull(entries.deletedAt)))
    .groupBy(entries.dictionaryId);
  const countOf = new Map(counts.map((c) => [c.id, c]));
  const pending = await db
    .select({ id: submissions.classroomId, n: sql<number>`count(*)::int` })
    .from(submissions)
    .where(and(inArray(submissions.classroomId, ids), eq(submissions.status, 'pending')))
    .groupBy(submissions.classroomId);
  const pendingOf = new Map(pending.map((p) => [p.id, p.n]));

  return rows.map(({ dict, settings }) => {
    const myRole = roleOf.get(dict.id) ?? null;
    const editor = dict.kind === 'personal' ? dict.ownerId === actor.userId : myRole === 'teacher';
    const c = countOf.get(dict.id);
    return {
      id: dict.id,
      kind: dict.kind,
      title: dict.title,
      description: dict.description,
      avatar: dict.avatar,
      owner: { id: dict.ownerId, displayName: owners.find((o) => o.id === dict.ownerId)?.displayName ?? '' },
      myRole,
      entryCount: (editor ? c?.all : c?.visible) ?? 0,
      pendingCount: myRole === 'teacher' ? (pendingOf.get(dict.id) ?? 0) : 0,
      classroom: settings ? settingsView(settings, deps) : null,
    };
  });
}

/** Load full entries (senses, topics, media, submissions) preserving the order of `ids`. */
export async function entryViews(
  deps: Deps,
  ids: string[],
  { hideHidden, withSubmissions }: { hideHidden: boolean; withSubmissions: boolean },
): Promise<EntryView[]> {
  if (ids.length === 0) return [];
  const { db } = deps;
  const rows = await db
    .select({ e: entries, author: users.displayName })
    .from(entries)
    .leftJoin(users, eq(users.id, entries.createdBy))
    .where(inArray(entries.id, ids));
  const senseRows = await db
    .select()
    .from(entrySenses)
    .where(inArray(entrySenses.entryId, ids))
    .orderBy(asc(entrySenses.position));
  const senseIds = senseRows.map((s) => s.id);
  const topicRows = senseIds.length
    ? await db.select().from(senseTopics).where(inArray(senseTopics.senseId, senseIds))
    : [];
  const mediaRows = senseIds.length
    ? await db
        .select({ senseId: senseMedia.senseId, m: mediaAssets })
        .from(senseMedia)
        .innerJoin(mediaAssets, eq(mediaAssets.id, senseMedia.mediaId))
        .where(inArray(senseMedia.senseId, senseIds))
    : [];
  const subRows = withSubmissions
    ? await db
        .select({ s: submissions, title: dictionaries.title })
        .from(submissions)
        .innerJoin(dictionaries, eq(dictionaries.id, submissions.classroomId))
        .where(and(inArray(submissions.sourceEntryId, ids), isNull(dictionaries.deletedAt)))
        .orderBy(asc(submissions.submittedAt))
    : [];

  const byId = new Map(rows.map((r) => [r.e.id, r]));
  return ids.flatMap((id) => {
    const r = byId.get(id);
    if (!r) return [];
    const senses: SenseView[] = senseRows
      .filter((s) => s.entryId === id && !(hideHidden && s.hidden))
      .map((s) => ({
        id: s.id,
        position: s.position,
        definition: s.definition,
        extraInfo: s.extraInfo,
        example: s.example,
        partOfSpeechId: s.partOfSpeechId,
        genderId: s.genderId,
        numberId: s.numberId,
        languageId: s.languageId,
        foreignForm: s.foreignForm,
        hidden: s.hidden,
        topicIds: topicRows.filter((t) => t.senseId === s.id).map((t) => t.topicId),
        media: mediaRows
          .filter((m) => m.senseId === s.id)
          .map(({ m }) => ({
            id: m.id,
            kind: m.kind as MediaKind,
            mime: m.mime,
            originalName: m.originalName,
            url: deps.mediaUrl(m.id),
          })),
      }));
    const subs: SubmissionBrief[] = subRows
      .filter((x) => x.s.sourceEntryId === id)
      .map((x) => ({
        id: x.s.id,
        classroomId: x.s.classroomId,
        classroomTitle: x.title,
        status: x.s.status,
        submittedAt: x.s.submittedAt.toISOString(),
        reviewedAt: iso(x.s.reviewedAt),
        reviewNote: x.s.reviewNote,
      }));
    return [
      {
        id: r.e.id,
        dictionaryId: r.e.dictionaryId,
        headword: r.e.headword,
        initial: r.e.initial,
        hidden: r.e.hidden,
        version: r.e.version,
        createdAt: r.e.createdAt.toISOString(),
        updatedAt: r.e.updatedAt.toISOString(),
        author: r.e.createdBy ? { id: r.e.createdBy, displayName: r.author ?? '' } : null,
        sourceSubmissionId: r.e.sourceSubmissionId,
        senses,
        submissions: subs,
      },
    ];
  });
}

/** Immutable copy of the visible senses: what a teacher reviews (§27). */
export const snapshotOf = (e: EntryView): EntrySnapshot => ({
  headword: e.headword,
  senses: e.senses
    .filter((s) => !s.hidden)
    .map(({ id: _id, hidden: _hidden, ...rest }, i) => ({ ...rest, position: i + 1 })),
});

export const toVocabularyValue = (v: typeof import('@lexican/db').vocabularyValues.$inferSelect): import('@lexican/core').VocabularyValue => ({
  id: v.id,
  vocabulary: v.vocabulary,
  code: v.code,
  label: v.label,
  abbreviation: v.abbreviation,
  position: v.position,
  featured: v.featured,
  active: v.active,
});
