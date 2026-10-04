import {
  type DictionaryView,
  DomainError,
  type OperationName,
  type OperationOutputs,
  operations,
  type ParsedInput,
  type VocabularyValue,
} from '@lexican/core';
import { classroomSettings, dictionaries, vocabularyValues } from '@lexican/db';
import { and, asc, eq, isNull } from 'drizzle-orm';
import { adminServices } from './admin.ts';
import { authServices } from './auth.ts';
import { classroomServices } from './classrooms.ts';
import { commentServices } from './comments.ts';
import { type Actor, type Deps, requireUser } from './context.ts';
import { entryServices } from './entries.ts';
import { mediaServices } from './media.ts';
import { submissionServices } from './submissions.ts';
import { dictionaryViews, toVocabularyValue } from './views.ts';

export {
  type CasProvider,
  createPasswordUser,
  hashPassword,
  type InstitutionalProfile,
  roleFromDirectory,
} from './auth.ts';
export { generateJoinCode } from './classrooms.ts';
export type { Actor, Deps, MediaStorage } from './context.ts';
export { memoryMediaStorage } from './context.ts';
export { DEMO_SEED_VERSION, demoSeedVersion, seedDemo } from './demo-seed.ts';
export { type InstitutionalDirectory, NOT_AUTHORIZED, testDirectory } from './directory.ts';
export { submissionProblem } from './submissions.ts';

export type OperationHandlers = {
  [K in OperationName]: (
    actor: Actor | null,
    input: ParsedInput<K>,
  ) => Promise<OperationOutputs[K]>;
};

/**
 * Application services: authorization + use cases over Drizzle. The same object runs inside the Fastify API
 * (node-postgres) and inside the browser demo (PGlite).
 */
export function createServices(deps: Deps) {
  const { db } = deps;
  const auth = authServices(deps);
  const classrooms = classroomServices(deps);

  const personal = {
    async myDictionary(actor: Actor | null): Promise<DictionaryView> {
      const a = requireUser(actor);
      const find = () =>
        db
          .select({ dict: dictionaries, settings: classroomSettings })
          .from(dictionaries)
          .leftJoin(classroomSettings, eq(classroomSettings.dictionaryId, dictionaries.id))
          .where(
            and(
              eq(dictionaries.ownerId, a.userId),
              eq(dictionaries.kind, 'personal'),
              isNull(dictionaries.deletedAt),
            ),
          );
      let rows = await find();
      if (rows.length === 0) {
        await db
          .insert(dictionaries)
          .values({
            kind: 'personal',
            title: 'Mi diccionario personal',
            ownerId: a.userId,
            avatar: 'default',
          })
          .onConflictDoNothing();
        rows = await find();
      }
      return (await dictionaryViews(deps, a, rows))[0]!;
    },

    async updateMyDictionary(
      actor: Actor | null,
      input: ParsedInput<'updateMyDictionary'>,
    ): Promise<DictionaryView> {
      const a = requireUser(actor);
      const mine = await personal.myDictionary(a);
      await db
        .update(dictionaries)
        .set({ title: input.title, avatar: input.avatar, updatedAt: new Date() })
        .where(eq(dictionaries.id, mine.id));
      return personal.myDictionary(a);
    },

    async vocabularies(): Promise<VocabularyValue[]> {
      const rows = await db
        .select()
        .from(vocabularyValues)
        .orderBy(
          asc(vocabularyValues.vocabulary),
          asc(vocabularyValues.position),
          asc(vocabularyValues.label),
        );
      return rows.map(toVocabularyValue);
    },
  };

  const handlers: OperationHandlers = {
    me: auth.me,
    login: auth.login,
    logout: auth.logout,
    ...personal,
    ...classrooms,
    ...entryServices(deps),
    exportDictionary: async (actor, input) => {
      const dictionary = await classrooms.getDictionary(actor, {
        dictionaryId: input.dictionaryId,
      });
      return { dictionary, entries: await entryServices(deps).exportDictionary(actor, input) };
    },
    ...submissionServices(deps),
    ...commentServices(deps),
    ...adminServices(deps),
  } as OperationHandlers;

  for (const name of Object.keys(operations) as OperationName[]) {
    if (typeof handlers[name] !== 'function')
      throw new Error(`Missing handler for operation ${name}`);
  }

  return {
    handlers,
    /** Validate raw input with the shared schema, then run the use case. */
    async call<K extends OperationName>(
      name: K,
      actor: Actor | null,
      raw: unknown,
    ): Promise<OperationOutputs[K]> {
      const parsed = operations[name].input.safeParse(raw ?? {});
      if (!parsed.success) {
        const details: Record<string, string[]> = {};
        for (const issue of parsed.error.issues) {
          const key = issue.path.join('.') || '_';
          (details[key] ??= []).push(issue.message);
        }
        throw new DomainError(
          'validation',
          parsed.error.issues[0]?.message ?? 'Datos no válidos.',
          details,
        );
      }
      return handlers[name](actor, parsed.data as ParsedInput<K>);
    },
    media: mediaServices(deps),
    auth,
  };
}

export type Services = ReturnType<typeof createServices>;
