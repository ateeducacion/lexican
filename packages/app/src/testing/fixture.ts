import type {
  ClassroomInput,
  EntryInput,
  GlobalRole,
  OperationName,
  OperationOutputs,
} from '@lexican/core';
import { seedVocabulary, vocabularyValues } from '@lexican/db';
import { openPglite } from '@lexican/db/testing';
import {
  createPasswordUser,
  createServices,
  type Actor,
  type Deps,
  memoryMediaStorage,
} from '../index.ts';

/** 1×1 PNG, the smallest upload the media service accepts. */
export const PNG = Uint8Array.from(
  atob(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
  ),
  (c) => c.charCodeAt(0),
);

/**
 * Isolated PGlite database with vocabularies, application services and a movable clock, for service rule tests.
 * Every account is fictitious (`@ejemplo.com`).
 */
export async function appFixture(start: string, extra: Partial<Deps> = {}) {
  const t = await openPglite();
  await seedVocabulary(t.db);
  const vocab: Record<string, string> = {};
  for (const v of await t.db.select().from(vocabularyValues))
    vocab[`${v.vocabulary}:${v.code}`] = v.id;
  const clock = { now: new Date(start) };
  const media = memoryMediaStorage();
  const blobs = media.blobs;
  const deps: Deps = {
    db: t.db,
    clock: () => clock.now,
    media,
    mediaUrl: (id) => `/media/${id}`,
    ...extra,
  };
  const svc = createServices(deps);

  const user = async (globalRole: GlobalRole, name: string): Promise<Actor> => ({
    userId: await createPasswordUser(t.db, {
      email: `${name}@ejemplo.com`,
      password: 'x',
      firstName: name,
      lastName: 'Prueba',
      globalRole,
    }),
    globalRole,
  });
  const call = <K extends OperationName>(
    name: K,
    actor: Actor | null,
    input: unknown,
  ): Promise<OperationOutputs[K]> => svc.call(name, actor, input);
  const setNow = (iso: string) => {
    clock.now = new Date(iso);
  };
  const classroom = async (teacher: Actor, input: Partial<ClassroomInput> & { title: string }) => {
    const c = await call('createClassroom', teacher, input);
    return { id: c.id, code: c.classroom!.joinCode };
  };
  /** Personal entry of `actor` (dictionary created on first use). */
  const entry = async (
    actor: Actor,
    headword: string,
    sense: Partial<EntryInput['senses'][number]> = {},
  ) => {
    const dict = await call('myDictionary', actor, {});
    return call('createEntry', actor, {
      dictionaryId: dict.id,
      entry: { headword, senses: [{ definition: `Definición de ${headword}`, ...sense }] },
    });
  };
  /** Submit a personal entry and return the submission id. */
  const submit = async (actor: Actor, entryId: string, classroomId: string) =>
    (await call('submitEntries', actor, { entryIds: [entryId], classroomIds: [classroomId] }))
      .created[0]!.id;

  return {
    t,
    db: t.db,
    deps,
    svc,
    vocab,
    blobs,
    user,
    call,
    setNow,
    classroom,
    entry,
    submit,
    close: () => t.close(),
  };
}

export type AppFixture = Awaited<ReturnType<typeof appFixture>>;
