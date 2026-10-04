import {
  DomainError,
  type EntryInput,
  type EntrySnapshot,
  type EntrySummary,
  type EntryView,
  headwordKey,
  initialOf,
  normalizeText,
  type Page,
  type ParsedInput,
  sortKeyOf,
  type Vocabulary,
} from '@lexican/core';
import {
  type Db,
  entries,
  entryRevisions,
  entrySenses,
  mediaAssets,
  senseMedia,
  senseTopics,
  submissions,
  vocabularyValues,
} from '@lexican/db';
import { and, asc, eq, exists, inArray, isNull, like, notInArray, sql } from 'drizzle-orm';
import {
  assertEditable,
  assertReadable,
  type DictionaryAccess,
  dictionaryAccess,
} from './access.ts';
import {
  type Actor,
  audit,
  type Deps,
  isUniqueViolation,
  notFound,
  requireUser,
} from './context.ts';
import { entryViews, snapshotOf } from './views.ts';

const duplicate = (headword: string) =>
  new DomainError(
    'conflict',
    `Ya existe la entrada «${normalizeText(headword)}» en este diccionario.`,
    {
      headword: ['Ya existe una entrada con esta palabra'],
    },
  );

/** Senses may only reference values of the right vocabulary (no cross-vocabulary ids). */
async function assertVocabulary(db: Db, input: EntryInput): Promise<void> {
  const wanted: [string, Vocabulary][] = input.senses.flatMap((s) => [
    ...(s.partOfSpeechId ? [[s.partOfSpeechId, 'part_of_speech'] as [string, Vocabulary]] : []),
    ...(s.genderId ? [[s.genderId, 'gender'] as [string, Vocabulary]] : []),
    ...(s.numberId ? [[s.numberId, 'number'] as [string, Vocabulary]] : []),
    ...(s.languageId ? [[s.languageId, 'language'] as [string, Vocabulary]] : []),
    ...s.topicIds.map((t) => [t, 'topic'] as [string, Vocabulary]),
  ]);
  if (wanted.length === 0) return;
  const rows = await db
    .select({ id: vocabularyValues.id, vocabulary: vocabularyValues.vocabulary })
    .from(vocabularyValues)
    .where(inArray(vocabularyValues.id, [...new Set(wanted.map((w) => w[0]))]));
  const ok = new Map(rows.map((r) => [r.id, r.vocabulary]));
  if (wanted.some(([id, voc]) => ok.get(id) !== voc))
    throw new DomainError('validation', 'Hay un valor de lista no válido en una acepción.');
}

/** Media must be the actor's own uploads or already attached to this entry; at most one per kind per sense. */
async function assertMedia(
  db: Db,
  actor: Actor,
  input: EntryInput,
  entryId: string | null,
): Promise<Map<string, string>> {
  const ids = [...new Set(input.senses.flatMap((s) => s.mediaIds))];
  if (ids.length === 0) return new Map();
  const assets = await db.select().from(mediaAssets).where(inArray(mediaAssets.id, ids));
  const attached = entryId
    ? await db
        .select({ id: senseMedia.mediaId })
        .from(senseMedia)
        .innerJoin(entrySenses, eq(entrySenses.id, senseMedia.senseId))
        .where(eq(entrySenses.entryId, entryId))
    : [];
  const allowed = new Set(attached.map((a) => a.id));
  const kinds = new Map<string, string>();
  for (const a of assets) {
    if (a.createdBy !== actor.userId && !allowed.has(a.id))
      throw new DomainError('forbidden', 'No puedes usar ese archivo.');
    kinds.set(a.id, a.kind);
  }
  if (kinds.size !== ids.length) throw new DomainError('validation', 'Falta un archivo adjunto.');
  for (const s of input.senses) {
    const k = s.mediaIds.map((m) => kinds.get(m));
    if (new Set(k).size !== k.length)
      throw new DomainError(
        'validation',
        'Cada acepción admite como máximo una imagen, un audio y un vídeo.',
      );
  }
  return kinds;
}

/** Replace the senses of an entry, keeping ids (and hidden flags) of senses that survive. */
async function writeSenses(
  db: Db,
  entryId: string,
  input: EntryInput,
  kinds: Map<string, string>,
): Promise<void> {
  const keep = input.senses.flatMap((s) => (s.id ? [s.id] : []));
  await db
    .delete(entrySenses)
    .where(
      keep.length
        ? and(eq(entrySenses.entryId, entryId), notInArray(entrySenses.id, keep))
        : eq(entrySenses.entryId, entryId),
    );
  for (const [i, s] of input.senses.entries()) {
    const values = {
      position: i + 1,
      definition: s.definition,
      extraInfo: s.extraInfo,
      example: s.example,
      partOfSpeechId: s.partOfSpeechId,
      genderId: s.genderId,
      numberId: s.numberId,
      languageId: s.languageId,
      foreignForm: s.foreignForm,
      hidden: s.hidden,
      updatedAt: new Date(),
    };
    let senseId = s.id;
    if (senseId) {
      const updated = await db
        .update(entrySenses)
        .set(values)
        .where(and(eq(entrySenses.id, senseId), eq(entrySenses.entryId, entryId)))
        .returning({ id: entrySenses.id });
      if (updated.length === 0)
        throw new DomainError('validation', 'La acepción no pertenece a esta entrada.');
      await db.delete(senseTopics).where(eq(senseTopics.senseId, senseId));
      await db.delete(senseMedia).where(eq(senseMedia.senseId, senseId));
    } else {
      const [row] = await db
        .insert(entrySenses)
        .values({ entryId, ...values })
        .returning({ id: entrySenses.id });
      senseId = row!.id;
    }
    if (s.topicIds.length)
      await db
        .insert(senseTopics)
        .values([...new Set(s.topicIds)].map((topicId) => ({ senseId: senseId!, topicId })));
    if (s.mediaIds.length)
      await db.insert(senseMedia).values(
        s.mediaIds.map((mediaId) => ({
          senseId: senseId!,
          mediaId,
          kind: kinds.get(mediaId) as 'image' | 'audio' | 'video',
        })),
      );
  }
}

const headwordColumns = (headword: string) => ({
  headword: normalizeText(headword),
  headwordKey: headwordKey(headword),
  initial: initialOf(headword),
  sortKey: sortKeyOf(headword),
});

/** Insert a full entry (used by personal editing, publication and seeds). */
export async function insertEntry(
  db: Db,
  actor: Actor,
  dictionaryId: string,
  input: EntryInput,
  kinds: Map<string, string>,
  extra: { sourceSubmissionId?: string; createdBy?: string } = {},
): Promise<string> {
  try {
    const [row] = await db
      .insert(entries)
      .values({
        dictionaryId,
        ...headwordColumns(input.headword),
        hidden: input.hidden,
        createdBy: extra.createdBy ?? actor.userId,
        updatedBy: actor.userId,
        sourceSubmissionId: extra.sourceSubmissionId ?? null,
      })
      .returning({ id: entries.id });
    await writeSenses(db, row!.id, input, kinds);
    return row!.id;
  } catch (e) {
    if (isUniqueViolation(e)) throw duplicate(input.headword);
    throw e;
  }
}

/** Overwrite headword and senses of an existing entry (publication of a resubmitted entry). */
export async function replaceEntry(
  db: Db,
  actor: Actor,
  entryId: string,
  input: EntryInput,
  kinds: Map<string, string>,
  extra: { sourceSubmissionId: string },
): Promise<string> {
  try {
    await db
      .update(entries)
      .set({
        ...headwordColumns(input.headword),
        sourceSubmissionId: extra.sourceSubmissionId,
        version: sql`${entries.version} + 1`,
        updatedBy: actor.userId,
        updatedAt: new Date(),
      })
      .where(eq(entries.id, entryId));
  } catch (e) {
    if (isUniqueViolation(e)) throw duplicate(input.headword);
    throw e;
  }
  await writeSenses(db, entryId, input, kinds);
  return entryId;
}

/** Snapshot → entry input, for publication (media are shared by reference; assets are immutable). */
export const inputFromSnapshot = (s: EntrySnapshot): EntryInput => ({
  headword: s.headword,
  hidden: false,
  senses: s.senses.map((x) => ({
    definition: x.definition,
    extraInfo: x.extraInfo,
    example: x.example,
    partOfSpeechId: x.partOfSpeechId,
    genderId: x.genderId,
    numberId: x.numberId,
    languageId: x.languageId,
    foreignForm: x.foreignForm,
    hidden: false,
    topicIds: x.topicIds,
    mediaIds: x.media.map((m) => m.id),
  })),
});

export async function nextRevision(db: Db, entryId: string): Promise<number> {
  const [r] = await db
    .select({ n: sql<number>`coalesce(max(${entryRevisions.number}), 0)::int` })
    .from(entryRevisions)
    .where(eq(entryRevisions.entryId, entryId));
  return (r?.n ?? 0) + 1;
}

export async function addRevision(
  deps: Deps,
  actor: Actor,
  entry: EntryView,
  reason: 'submit' | 'publish' | 'teacher_edit',
): Promise<string> {
  const [row] = await deps.db
    .insert(entryRevisions)
    .values({
      entryId: entry.id,
      number: await nextRevision(deps.db, entry.id),
      reason,
      snapshot: snapshotOf(entry),
      createdBy: actor.userId,
    })
    .returning({ id: entryRevisions.id });
  return row!.id;
}

async function loadEntryRow(db: Db, entryId: string) {
  const [e] = await db
    .select()
    .from(entries)
    .where(and(eq(entries.id, entryId), isNull(entries.deletedAt)));
  if (!e) throw notFound('La entrada');
  return e;
}

export async function entryAccess(deps: Deps, actor: Actor | null, entryId: string) {
  const e = await loadEntryRow(deps.db, entryId);
  const acc = await dictionaryAccess(deps, actor, e.dictionaryId);
  assertReadable(acc);
  if (e.hidden && !acc.canEdit) throw notFound('La entrada');
  return { e, acc };
}

export async function getEntryView(
  deps: Deps,
  acc: DictionaryAccess,
  entryId: string,
): Promise<EntryView> {
  const [view] = await entryViews(deps, [entryId], {
    hideHidden: !acc.canEdit,
    withSubmissions: acc.dict.kind === 'personal',
  });
  if (!view) throw notFound('La entrada');
  return view;
}

export function entryServices(deps: Deps) {
  const { db } = deps;

  return {
    async listEntries(
      actor: Actor | null,
      q: ParsedInput<'listEntries'>,
    ): Promise<Page<EntrySummary>> {
      const acc = await dictionaryAccess(deps, actor, q.dictionaryId);
      assertReadable(acc);
      const showHidden = acc.canEdit && q.includeHidden !== false;
      const where = and(
        eq(entries.dictionaryId, q.dictionaryId),
        isNull(entries.deletedAt),
        showHidden ? undefined : eq(entries.hidden, false),
        // Accent-insensitive like the legacy MySQL collation: "arbol" finds "árbol".
        q.q
          ? like(entries.sortKey, `%${sortKeyOf(q.q).replace(/[\\%_]/g, (c) => `\\${c}`)}%`)
          : undefined,
        q.initial ? eq(entries.initial, q.initial.toLocaleUpperCase('es')) : undefined,
        q.topicId
          ? exists(
              db
                .select({ one: sql`1` })
                .from(senseTopics)
                .innerJoin(entrySenses, eq(entrySenses.id, senseTopics.senseId))
                .where(
                  and(
                    eq(entrySenses.entryId, entries.id),
                    eq(senseTopics.topicId, q.topicId),
                    showHidden ? undefined : eq(entrySenses.hidden, false),
                  ),
                ),
            )
          : undefined,
      );
      const [total] = await db.select({ n: sql<number>`count(*)::int` }).from(entries).where(where);
      const rows = await db
        .select({ id: entries.id })
        .from(entries)
        .where(where)
        .orderBy(sql`${entries.sortKey} collate "C"`, asc(entries.headwordKey))
        .limit(q.limit)
        .offset(q.offset);
      const views = await entryViews(
        deps,
        rows.map((r) => r.id),
        {
          hideHidden: !showHidden,
          withSubmissions: acc.dict.kind === 'personal',
        },
      );
      return {
        total: total?.n ?? 0,
        items: views.map((v) => ({
          id: v.id,
          headword: v.headword,
          initial: v.initial,
          hidden: v.hidden,
          senseCount: v.senses.length,
          firstDefinition: v.senses[0]?.definition ?? '',
          updatedAt: v.updatedAt,
          submissions: latestPerClassroom(v).map((s) => ({
            classroomTitle: s.classroomTitle,
            status: s.status,
          })),
        })),
      };
    },

    async getEntry(actor: Actor | null, { entryId }: ParsedInput<'getEntry'>): Promise<EntryView> {
      const { acc } = await entryAccess(deps, actor, entryId);
      return getEntryView(deps, acc, entryId);
    },

    async createEntry(
      actor: Actor | null,
      { dictionaryId, entry }: ParsedInput<'createEntry'>,
    ): Promise<EntryView> {
      const a = requireUser(actor);
      const acc = await dictionaryAccess(deps, a, dictionaryId);
      if (acc.dict.kind !== 'personal' || !acc.canEdit)
        throw new DomainError(
          'forbidden',
          'Las entradas nuevas se crean en tu diccionario personal.',
        );
      await assertVocabulary(db, entry);
      const kinds = await assertMedia(db, a, entry, null);
      const id = await db.transaction((tx) => insertEntry(tx, a, dictionaryId, entry, kinds));
      return getEntryView(deps, acc, id);
    },

    async updateEntry(
      actor: Actor | null,
      { entryId, version, entry }: ParsedInput<'updateEntry'>,
    ): Promise<EntryView> {
      const a = requireUser(actor);
      const { e, acc } = await entryAccess(deps, a, entryId);
      assertEditable(acc);
      await assertVocabulary(db, entry);
      const kinds = await assertMedia(db, a, entry, entryId);
      await db.transaction(async (tx) => {
        try {
          const updated = await tx
            .update(entries)
            .set({
              ...headwordColumns(entry.headword),
              hidden: entry.hidden,
              version: sql`${entries.version} + 1`,
              updatedBy: a.userId,
              updatedAt: new Date(),
            })
            .where(and(eq(entries.id, entryId), eq(entries.version, version)))
            .returning({ id: entries.id });
          if (updated.length === 0)
            throw new DomainError(
              'conflict',
              'Otra persona ha modificado esta entrada. Recarga para ver los cambios.',
            );
        } catch (err) {
          if (isUniqueViolation(err)) throw duplicate(entry.headword);
          throw err;
        }
        await writeSenses(tx, entryId, entry, kinds);
      });
      const view = await getEntryView(deps, acc, entryId);
      if (acc.dict.kind === 'classroom') {
        await addRevision(deps, a, view, 'teacher_edit');
        await audit(db, a, 'entry.teacher_edit', 'entry', e.id);
      }
      return view;
    },

    async deleteEntry(actor: Actor | null, { entryId }: ParsedInput<'deleteEntry'>) {
      const a = requireUser(actor);
      const { acc } = await entryAccess(deps, a, entryId);
      assertEditable(acc);
      await db.transaction(async (tx) => {
        await tx
          .update(entries)
          .set({ deletedAt: new Date(), updatedBy: a.userId })
          .where(eq(entries.id, entryId));
        // RULE-127: pending submissions of a deleted personal entry are withdrawn; published copies stay.
        await tx
          .update(submissions)
          .set({ status: 'withdrawn' })
          .where(and(eq(submissions.sourceEntryId, entryId), eq(submissions.status, 'pending')));
      });
      if (acc.dict.kind === 'classroom') await audit(db, a, 'entry.unpublish', 'entry', entryId);
      return { ok: true as const };
    },

    async setEntryHidden(
      actor: Actor | null,
      { entryId, hidden }: ParsedInput<'setEntryHidden'>,
    ): Promise<EntryView> {
      const a = requireUser(actor);
      const { acc } = await entryAccess(deps, a, entryId);
      assertEditable(acc);
      await db
        .update(entries)
        .set({
          hidden,
          updatedBy: a.userId,
          updatedAt: new Date(),
          version: sql`${entries.version} + 1`,
        })
        .where(eq(entries.id, entryId));
      return getEntryView(deps, acc, entryId);
    },

    async setSenseHidden(
      actor: Actor | null,
      { senseId, hidden }: ParsedInput<'setSenseHidden'>,
    ): Promise<EntryView> {
      const a = requireUser(actor);
      const [s] = await db
        .select({ entryId: entrySenses.entryId })
        .from(entrySenses)
        .where(eq(entrySenses.id, senseId));
      if (!s) throw notFound('La acepción');
      const { acc } = await entryAccess(deps, a, s.entryId);
      assertEditable(acc);
      if (hidden) {
        const [visible] = await db
          .select({ n: sql<number>`count(*)::int` })
          .from(entrySenses)
          .where(and(eq(entrySenses.entryId, s.entryId), eq(entrySenses.hidden, false)));
        if ((visible?.n ?? 0) <= 1)
          throw new DomainError(
            'validation',
            'La entrada debe conservar al menos una acepción visible.',
          );
      }
      await db
        .update(entrySenses)
        .set({ hidden, updatedAt: new Date() })
        .where(eq(entrySenses.id, senseId));
      await db
        .update(entries)
        .set({ version: sql`${entries.version} + 1`, updatedAt: new Date() })
        .where(eq(entries.id, s.entryId));
      return getEntryView(deps, acc, s.entryId);
    },

    async exportDictionary(
      actor: Actor | null,
      { dictionaryId, includeHidden }: ParsedInput<'exportDictionary'>,
    ) {
      const acc = await dictionaryAccess(deps, actor, dictionaryId);
      assertReadable(acc);
      const showHidden = acc.canEdit && includeHidden;
      const rows = await db
        .select({ id: entries.id })
        .from(entries)
        .where(
          and(
            eq(entries.dictionaryId, dictionaryId),
            isNull(entries.deletedAt),
            showHidden ? undefined : eq(entries.hidden, false),
          ),
        )
        .orderBy(sql`${entries.sortKey} collate "C"`, asc(entries.headwordKey));
      return entryViews(
        deps,
        rows.map((r) => r.id),
        { hideHidden: !showHidden, withSubmissions: false },
      );
    },
  };
}

const latestPerClassroom = (v: EntryView) => {
  const last = new Map<string, EntryView['submissions'][number]>();
  for (const s of v.submissions) last.set(s.classroomId, s);
  return [...last.values()];
};
