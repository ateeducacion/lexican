import {
  DEMO_ACCOUNTS,
  DEMO_SCHOOL,
  type EntryInput,
  type OperationName,
  type OperationOutputs,
} from '@lexican/core';
import {
  appSettings,
  authIdentities,
  type Db,
  schools,
  seedVocabulary,
  userSchools,
  vocabularyValues,
} from '@lexican/db';
import { and, eq } from 'drizzle-orm';
import { createPasswordUser } from './auth.ts';
import type { Actor, Deps } from './context.ts';
import { createServices } from './index.ts';

export const DEMO_SEED_VERSION = 1;

type Sense = Partial<EntryInput['senses'][number]> & {
  definition: string;
  pos?: string;
  gender?: string;
  number?: string;
  topics?: string[];
  lang?: string;
};

/**
 * Coherent fictitious dataset: one teacher, two students, a fictitious school, a current classroom with an open
 * submission window, personal dictionaries, published / pending / rejected submissions, a teacher comment, a hidden
 * entry and two small CC0 illustrations. Built through the application services so it obeys every rule.
 */
export async function seedDemo(
  deps: Deps,
  images: { name: string; bytes: Uint8Array }[] = [],
): Promise<void> {
  const { db } = deps;
  const [done] = await db
    .select()
    .from(appSettings)
    .where(eq(appSettings.key, 'demo_seed_version'));
  if (done) return linkTestCasIdentities(db);
  await seedVocabulary(db);
  const svc = createServices(deps);
  const v: Record<string, string> = {};
  for (const row of await db.select().from(vocabularyValues))
    v[`${row.vocabulary}:${row.code}`] = row.id;

  const actors: Record<string, Actor> = {};
  for (const a of DEMO_ACCOUNTS) {
    const userId = await createPasswordUser(db, { ...a, globalRole: a.role });
    actors[a.email] = { userId, globalRole: a.role };
  }
  const teacher = actors['profesor@ejemplo.com']!;
  const s1 = actors['alumno1@ejemplo.com']!;
  const s2 = actors['alumno2@ejemplo.com']!;

  const [school] = await db
    .insert(schools)
    .values({ ...DEMO_SCHOOL })
    .returning();
  await db
    .insert(userSchools)
    .values([teacher, s1, s2].map((a) => ({ userId: a.userId, schoolId: school!.id })));

  const call = <K extends OperationName>(
    name: K,
    actor: Actor,
    input: unknown,
  ): Promise<OperationOutputs[K]> => svc.call(name, actor, input);

  const media: Record<string, string> = {};
  for (const img of images) media[img.name] = (await svc.media.uploadMedia(s1, img)).id;

  const sense = (s: Sense): EntryInput['senses'][number] => ({
    definition: s.definition,
    extraInfo: s.extraInfo ?? '',
    example: s.example ?? '',
    partOfSpeechId: s.pos ? v[`part_of_speech:${s.pos}`]! : null,
    genderId: s.gender ? v[`gender:${s.gender}`]! : null,
    numberId: s.number ? v[`number:${s.number}`]! : null,
    languageId: s.lang ? v[`language:${s.lang}`]! : null,
    foreignForm: s.foreignForm ?? '',
    hidden: s.hidden ?? false,
    topicIds: (s.topics ?? []).map((t) => v[`topic:${t}`]!),
    mediaIds: s.mediaIds ?? [],
  });

  const add = async (actor: Actor, headword: string, senses: Sense[], hidden = false) => {
    const dict = await call('myDictionary', actor, {});
    return call('createEntry', actor, {
      dictionaryId: dict.id,
      entry: { headword, hidden, senses: senses.map(sense) },
    });
  };

  // Personal dictionary — Daniel (alumno 1)
  const guagua = await add(s1, 'guagua', [
    {
      definition: 'Autobús de transporte público.',
      pos: 'sustantivo',
      gender: 'femenino',
      number: 'singular',
      topics: ['dialecto_canario'],
      example: 'Cogí la guagua para ir al instituto.',
      lang: 'ingles',
      foreignForm: 'bus',
      mediaIds: media['guagua.png'] ? [media['guagua.png']] : [],
    },
    {
      definition: 'Bebé, niño o niña de pocos meses.',
      pos: 'sustantivo',
      gender: 'femenino',
      number: 'singular',
      topics: ['dialecto_canario'],
      example: 'La guagua de mi prima ya gatea.',
    },
  ]);
  const gofio = await add(s1, 'gofio', [
    {
      definition:
        'Harina de cereal tostado, típica de Canarias, que se toma con leche, caldo o amasada.',
      pos: 'sustantivo',
      gender: 'masculino',
      number: 'singular',
      topics: ['gastronomia', 'patrimonio_canario'],
      example: 'Desayuno gofio con leche.',
      mediaIds: media['gofio.png'] ? [media['gofio.png']] : [],
    },
  ]);
  const baifo = await add(s1, 'baifo', [
    {
      definition: 'Cría de la cabra.',
      pos: 'sustantivo',
      gender: 'masculino',
      number: 'singular',
      topics: ['fauna_canaria', 'dialecto_canario'],
      example: 'El baifo sigue a su madre por el risco.',
    },
  ]);
  await add(s1, 'millo', [
    {
      definition: 'Maíz.',
      pos: 'sustantivo',
      gender: 'masculino',
      number: 'singular',
      topics: ['gastronomia'],
      example: 'Compramos piñas de millo en el mercado.',
    },
  ]);
  await add(
    s1,
    'fisco',
    [
      {
        definition: 'Pizca, cantidad muy pequeña de algo.',
        pos: 'sustantivo',
        gender: 'masculino',
        topics: ['dialecto_canario'],
      },
    ],
    true,
  );

  // Personal dictionary — Aitana (alumna 2)
  const cholas = await add(s2, 'cholas', [
    {
      definition: 'Chanclas, sandalias abiertas de playa.',
      pos: 'sustantivo',
      gender: 'femenino',
      number: 'plural',
      topics: ['vestuario_y_complementos_canarios'],
      example: 'No vengas en cholas a clase de educación física.',
    },
  ]);
  const machango = await add(s2, 'machango', [
    {
      definition: 'Muñeco o figura hecha de trapo.',
      pos: 'sustantivo',
      gender: 'masculino',
      number: 'singular',
      topics: ['etnografia_y_artesania_canarias'],
    },
    {
      definition: 'Persona poco seria, que hace el payaso.',
      pos: 'sustantivo',
      gender: 'masculino',
      number: 'singular',
      example: 'Deja de hacer el machango.',
    },
  ]);
  const mojo = await add(s2, 'mojo', [
    { definition: 'Salsa.', pos: 'sustantivo', gender: 'masculino' },
  ]);
  await add(s2, 'tenique', [
    {
      definition: 'Piedra que se usa para formar el fogón de leña.',
      pos: 'sustantivo',
      gender: 'masculino',
      number: 'singular',
      topics: ['etnografia_y_artesania_canarias'],
    },
  ]);

  // Classroom dictionary with an open submission window
  const now = deps.clock();
  const classroom = await call('createClassroom', teacher, {
    title: 'Canarismos de 2º ESO B',
    description: 'Diccionario de aula con palabras propias del español de Canarias.',
    studyLevelId: v['study_level:2_eso'] ?? null,
    subjectId: v['subject:lengua_castellana_y_literatura'] ?? null,
    groupLabel: 'B',
    validityYears: 1,
    maxSenses: 3,
    requiredFields: ['part_of_speech'],
    guidelines:
      'Antes de enviar una palabra:\n• Escribe la definición con tus propias palabras.\n• Indica la categoría gramatical.\n• Añade un ejemplo de uso real.\n• Si puedes, acompáñala de una imagen hecha por ti.',
    submissionsEnabled: true,
    submissionsStartAt: null,
    submissionsEndAt: new Date(now.getTime() + 60 * 24 * 3600 * 1000),
    commentsVisibility: 'visible',
  });
  await call('createClassroom', teacher, {
    title: 'Vocabulario de Biología 1º ESO',
    description: 'Términos científicos trabajados en el aula.',
    studyLevelId: v['study_level:1_eso'] ?? null,
    subjectId: v['subject:biologia_y_geologia'] ?? null,
    groupLabel: 'A',
    submissionsEnabled: false,
  });
  const code = classroom.classroom!.joinCode;
  await call('joinClassroom', s1, { code });
  await call('joinClassroom', s2, { code });

  const submit = async (actor: Actor, entryId: string) =>
    (await call('submitEntries', actor, { entryIds: [entryId], classroomIds: [classroom.id] }))
      .created[0]!.id;

  const published = [
    await submit(s1, guagua.id),
    await submit(s1, gofio.id),
    await submit(s2, cholas.id),
  ];
  await call('publishSubmissions', teacher, { submissionIds: published });
  const pendingBaifo = await submit(s1, baifo.id);
  await submit(s2, machango.id);
  const rejected = await submit(s2, mojo.id);
  await call('rejectSubmission', teacher, {
    submissionId: rejected,
    note: 'La definición es demasiado general. Explica de qué tipo de salsa se trata y añade un ejemplo.',
  });

  await call('createComment', teacher, {
    classroomId: classroom.id,
    studentId: s1.userId,
    submissionId: pendingBaifo,
    body: '¡Buen trabajo! Antes de publicarla, añade el plural en un ejemplo de uso.',
  });
  await call('createComment', teacher, {
    classroomId: classroom.id,
    studentId: s2.userId,
    submissionId: null,
    body: 'Muy buena selección de palabras. Recuerda revisar las pautas del diccionario antes de enviar.',
  });

  // One sense hidden by the teacher in the published copy of "guagua".
  const pub = await call('listEntries', teacher, { dictionaryId: classroom.id, q: 'guagua' });
  const full = await call('getEntry', teacher, { entryId: pub.items[0]!.id });
  await call('setSenseHidden', teacher, { senseId: full.senses[1]!.id, hidden: true });

  await db.insert(appSettings).values({ key: 'demo_seed_version', value: DEMO_SEED_VERSION });
  await linkTestCasIdentities(db);
}

/**
 * Deterministic, idempotent link of the public test CAS accounts (alice, bob) to their demo personas, under the
 * separate `cas_test` issuer. Also runs on databases seeded before the link existed.
 */
async function linkTestCasIdentities(db: Db): Promise<void> {
  for (const a of DEMO_ACCOUNTS) {
    if (!('casSubject' in a)) continue;
    const [p] = await db
      .select({ userId: authIdentities.userId })
      .from(authIdentities)
      .where(and(eq(authIdentities.provider, 'password'), eq(authIdentities.subject, a.email)));
    if (p)
      await db
        .insert(authIdentities)
        .values({ userId: p.userId, provider: 'cas_test', subject: a.casSubject })
        .onConflictDoNothing();
  }
}

export async function demoSeedVersion(db: Db): Promise<number | null> {
  const [row] = await db.select().from(appSettings).where(eq(appSettings.key, 'demo_seed_version'));
  return row ? Number(row.value) : null;
}
