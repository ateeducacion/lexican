import { mkdir, mkdtemp, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { type Db } from '@lexican/db';
import { openPglite, type TestDb } from '@lexican/db/testing';
import { sql } from 'drizzle-orm';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import type {
  Acepcion,
  Comentario,
  DicAula,
  LegacyData,
  LegacyUser,
  Medio,
  Persona,
} from './legacy.ts';
import { migrateLegacy, type MigrateOptions } from './migrate.ts';
import type { Report } from './report.ts';

/**
 * Rare legacy anomalies, built in memory (no MariaDB needed) and migrated into PGlite. Every row below exists to
 * trigger one documented rule of docs/LEGACY-DATA-MAPPING.md; all data is fictitious.
 */
const D = new Date('2024-10-01T10:00:00Z');
const PNG = Uint8Array.from(
  atob(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
  ),
  (c) => c.charCodeAt(0),
);

const empty = (): LegacyData => ({
  roles: [],
  users: [],
  personas: [],
  usersPersonas: [],
  centros: [],
  usersCentros: [],
  ensenanzas: [],
  nivelEstudios: [],
  areasMaterias: [],
  tiposDiccionario: [],
  camposEntrada: [],
  camposValores: [],
  mstPautas: [],
  dicPersonal: [],
  dpEntradas: [],
  dpAcepciones: [],
  dpTematicas: [],
  dpMedios: [],
  dicAula: [],
  atemporales: [],
  dicAulaPautas: [],
  participantes: [],
  dicAulaCampos: [],
  dpEnvios: [],
  enviosEntradas: [],
  enviosAcepciones: [],
  enviosTematicas: [],
  enviosMedios: [],
  dicAulaEntradas: [],
  comentariosGenerales: [],
  comentariosEntradas: [],
  counts: {},
});
const persona = (
  id: number,
  nombre: string,
  apellidos: string,
  over: Partial<Persona> = {},
): Persona => ({
  id,
  nombre,
  apellidos,
  estado: '1',
  created_at: D,
  updated_at: D,
  deleted_at: null,
  ...over,
});
const user = (
  id: number,
  role_id: number | null,
  name: string,
  email: string | null = null,
  over: Partial<LegacyUser> = {},
): LegacyUser => ({
  id,
  name,
  email,
  role_id,
  created_at: D,
  updated_at: D,
  ...over,
});
const sense = (
  id: number,
  entrada_id: number,
  orden: number,
  over: Partial<Acepcion> = {},
): Acepcion => ({
  id,
  entrada_id,
  orden,
  cat_gramatical_id: null,
  genero_id: null,
  numero_id: null,
  idioma_id: null,
  idioma_palabra: null,
  definicion: `Definición ${id}`,
  frase_ejemplo: null,
  ejemplo2: null,
  estado: '1',
  created_at: D,
  updated_at: D,
  deleted_at: null,
  ...over,
});
const medio = (
  id: number,
  acepcion_id: number,
  tipo: string | null,
  url: string | null,
  over: Partial<Medio> = {},
): Medio => ({
  id,
  acepcion_id,
  tipo_medio: tipo,
  nombre: `medio ${id}`,
  url_externa: null,
  url_interna: url,
  estado: '1',
  created_at: D,
  deleted_at: null,
  ...over,
});
const aula = (id: number, persona_id: number, over: Partial<DicAula> = {}): DicAula => ({
  id,
  persona_id,
  mst_tipo_dic_id: null,
  titulo: `Aula ${id}`,
  descripcion: null,
  letra_grupo: null,
  mst_nivel_estudios_id: null,
  mst_area_materia_id: null,
  mst_ensenanza_id: null,
  ano_ini_curso_escolar: 2024,
  max_acepciones_entrada: null,
  codigo: null,
  visible_estudiante: '1',
  envios_habilitados: '1',
  envios_fecha_ini: null,
  envios_fecha_fin: null,
  estado: '1',
  comentarios_visibles: '1',
  comentarios_visibles_anteriores_a: null,
  vigencia: 1,
  created_at: D,
  updated_at: D,
  deleted_at: null,
  ...over,
});
const comment = (id: number, over: Partial<Comentario>): Comentario => ({
  id,
  dic_aula_id: 500,
  persona_id: 1,
  dic_personal_id: null,
  envio_entrada_id: null,
  comentario: '<p>Bien</p>',
  fecha_envio: D,
  estado: '1',
  created_at: D,
  updated_at: D,
  deleted_at: null,
  ...over,
});

/** Persona 1 teaches classroom 500; persona 11 (user 4) is the student; the rest are anomalies. */
function legacy(): LegacyData {
  const L = empty();
  L.roles = [
    { id: 1, name: 'docente' },
    { id: 2, name: 'alumno' },
    { id: 3, name: 'raro' },
  ];
  L.personas = [
    persona(1, 'Marta', 'Docente'),
    persona(3, 'Tercera', 'Docente'), // no account; teacher of 500 as a persona
    persona(11, 'Pablo', 'Alumno'),
    persona(12, 'Lucía', 'Baja', { deleted_at: D }),
    persona(13, '', '', { created_at: null, updated_at: null }),
    persona(14, 'Otra', 'Persona'),
  ];
  L.users = [
    user(1, 1, 'prof.uno', 'Prof@Ejemplo.com'),
    user(2, 3, 'alu.raro', null, { created_at: null, updated_at: null }),
    user(3, 2, 'alu.baja', 'prof@ejemplo.com'),
    user(4, null, '  '),
    user(5, 2, 'PROF.UNO'),
  ];
  L.usersPersonas = [
    { user_id: 1, persona_id: 14, created_at: D, deleted_at: null },
    { user_id: 1, persona_id: 1, created_at: D, deleted_at: null },
    { user_id: 1, persona_id: 11, created_at: D, deleted_at: D },
    { user_id: 2, persona_id: 999, created_at: D, deleted_at: null },
    { user_id: 3, persona_id: 12, created_at: D, deleted_at: null },
    { user_id: 4, persona_id: 11, created_at: D, deleted_at: null },
    { user_id: 5, persona_id: 14, created_at: D, deleted_at: null },
  ];
  L.centros = [
    { id: 1, cod_centro: '35000001', denominacion: 'IES Ficticio', deleted_at: null },
    { id: 2, cod_centro: ' ', denominacion: 'Sin código', deleted_at: null },
    { id: 3, cod_centro: '35000003', denominacion: 'Cerrado', deleted_at: D },
    { id: 4, cod_centro: '35000002', denominacion: '  ', deleted_at: null },
  ];
  L.usersCentros = [
    { user_id: 1, centro_id: 1, deleted_at: null },
    { user_id: 1, centro_id: 3, deleted_at: null },
    { user_id: 1, centro_id: 4, deleted_at: D },
    { user_id: 99, centro_id: 1, deleted_at: null },
  ];
  L.ensenanzas = [{ id: 5, descripcion: 'Enseñanza Inventada', estado: '0', deleted_at: null }];
  L.camposEntrada = [
    { id: 1, nombre_campo: 'Categoría gramatical' },
    { id: 4, nombre_campo: 'Temáticas' },
    { id: 50, nombre_campo: 'Campo raro' },
  ];
  L.camposValores = [
    { id: 1, mst_campo_entrada_id: 1, descripcion: 'Sustantivo', estado: '1', deleted_at: null },
    {
      id: 20,
      mst_campo_entrada_id: 4,
      descripcion: 'Fauna canaria',
      estado: '1',
      deleted_at: null,
    },
    {
      id: 21,
      mst_campo_entrada_id: 4,
      descripcion: 'Flora canaria',
      estado: '1',
      deleted_at: null,
    },
    {
      id: 30,
      mst_campo_entrada_id: 50,
      descripcion: 'Valor colgante',
      estado: '1',
      deleted_at: null,
    },
  ];
  L.mstPautas = [{ id: 1, texto: null, estado: 1, deleted_at: null }];

  // Pablo (persona 11) has an older dictionary 100 and the active one 101; 102 belongs to nobody.
  L.dicPersonal = [
    {
      id: 100,
      persona_id: 11,
      titulo: 'Viejo',
      created_at: null,
      updated_at: null,
      deleted_at: null,
    },
    { id: 101, persona_id: 11, titulo: '  ', created_at: null, updated_at: null, deleted_at: null },
    {
      id: 102,
      persona_id: 999,
      titulo: 'Huérfano',
      created_at: D,
      updated_at: D,
      deleted_at: null,
    },
  ];
  const entrada = (
    id: number,
    dic: number,
    text: string,
    over: Partial<LegacyData['dpEntradas'][number]> = {},
  ) => ({
    id,
    dic_personal_id: dic,
    entrada: text,
    estado: '1',
    created_at: D,
    updated_at: D,
    deleted_at: null,
    ...over,
  });
  L.dpEntradas = [
    entrada(1000, 101, 'Drago'),
    entrada(1001, 101, '   '),
    entrada(1002, 102, 'Huérfana'),
    entrada(1003, 101, 'drago ', { updated_at: null }),
    entrada(1004, 101, 'Gofio'),
  ];
  L.dpAcepciones = [
    sense(2000, 1000, 2, {
      definicion: null as unknown as string,
      created_at: null,
      updated_at: null,
      cat_gramatical_id: 20, // a topic used as part of speech
      genero_id: 30, // a value under a field without vocabulary
      numero_id: 888, // broken reference
    }),
    sense(2010, 1002, 1),
    sense(2020, 1004, 1),
    sense(2021, 1004, 2),
  ];
  L.dpTematicas = [
    { acepcion_id: 2000, tematica_id: 20, deleted_at: null },
    { acepcion_id: 2000, tematica_id: 20, deleted_at: null },
    { acepcion_id: 2000, tematica_id: 21, deleted_at: D },
    { acepcion_id: 2000, tematica_id: 777, deleted_at: null },
  ];
  L.dpMedios = [
    medio(3000, 2000, '1', 'a.png'),
    medio(3001, 2000, '1', 'a.png', { created_at: null }), // older duplicate image
    medio(3002, 2000, '2', 'png-como-audio.mp3'), // declared audio, PNG bytes
    medio(3003, 2000, '9', 'x.bin'),
    medio(3004, 2000, null, 'y.bin'),
    medio(3005, 2000, '1', 'b.png', { estado: '0' }),
    medio(3006, 2000, '3', null),
    medio(3007, 2000, '3', null, { url_externa: 'https://video.example.test/v' }),
    medio(3020, 2020, '1', 'perdida.png'),
    medio(3021, 2021, '1', 'perdida.png'),
  ];

  L.dicAula = [
    aula(500, 1, {
      titulo: ' ',
      codigo: 'ABCDEF',
      vigencia: null,
      created_at: null,
      updated_at: null,
      mst_area_materia_id: 999,
      mst_ensenanza_id: 5,
      max_acepciones_entrada: 0,
      comentarios_visibles: '2',
    }),
    aula(501, 1, { codigo: 'abcdef', vigencia: 11, deleted_at: D }),
    aula(502, 999),
    aula(503, 1, { vigencia: 0 }),
    aula(504, 1, { vigencia: 2.5, codigo: 'KLMNPQ' }),
    aula(505, 1, { vigencia: 10, codigo: 'RSTUVW' }),
  ];
  L.atemporales = [{ id: 1, dic_aula_id: 500, estado: '1', hasta: D, deleted_at: null }];
  L.dicAulaPautas = [
    {
      id: 1,
      dic_aula_id: 500,
      texto: null,
      url_fichero: 'normas.pdf',
      estado: '1',
      deleted_at: null,
    },
  ];
  L.dicAulaCampos = [
    {
      id: 1,
      dic_aula_id: 500,
      mst_campo_entrada_id: 50,
      visible: '1',
      obligatorio: '0',
      estado: '1',
      deleted_at: null,
    },
    {
      id: 2,
      dic_aula_id: 500,
      mst_campo_entrada_id: 1,
      visible: '1',
      obligatorio: '1',
      estado: '0',
      deleted_at: null,
    },
    {
      id: 3,
      dic_aula_id: 500,
      mst_campo_entrada_id: 4,
      visible: '1',
      obligatorio: '0',
      estado: '1',
      deleted_at: null,
    },
  ];
  const member = (
    id: number,
    dic: number,
    p: number,
    rol: number,
    over: Partial<LegacyData['participantes'][number]> = {},
  ) => ({
    id,
    dic_aula_id: dic,
    persona_id: p,
    rol_diccionario_id: rol,
    estado: '1',
    deleted_at: null,
    ...over,
  });
  L.participantes = [
    member(600, 500, 11, 2),
    member(601, 500, 11, 1), // duplicated as teacher: merged
    member(602, 500, 1, 2, { estado: '0' }), // the owner as a disabled student
    member(603, 500, 13, 3), // unknown classroom role
    member(604, 500, 14, 2, { deleted_at: D }),
    member(605, 999, 11, 2),
    member(606, 500, 3, 1),
  ];

  L.dpEnvios = [
    {
      id: 700,
      dic_personal_id: 101,
      dic_aula_id: 500,
      estado: '1',
      created_at: D,
      deleted_at: null,
    },
  ];
  const envio = (
    id: number,
    dp: number,
    text: string,
    estado: string,
    over: Partial<LegacyData['enviosEntradas'][number]> = {},
  ) => ({
    id,
    dp_envio_id: 700,
    dp_entrada_id: dp,
    entrada: text,
    estado,
    updated_by_persona_id: null,
    created_at: D,
    updated_at: D,
    deleted_at: null,
    ...over,
  });
  L.enviosEntradas = [
    envio(800, 1000, 'Drago', '1', { created_at: null }), // superseded by 801
    envio(801, 1000, 'Drago', '1', { created_at: new Date('2024-10-02T10:00:00Z') }),
    envio(802, 1004, 'Gofio', '3', { updated_by_persona_id: 555 }), // «published» without dic_aula_entradas
    envio(803, 1000, 'Drago', '1', { dp_envio_id: 9999 }),
    envio(804, 1002, 'Huérfana', '1'),
    envio(805, 5555, 'Desaparecida', '0', { created_at: null, updated_at: null }),
    envio(806, 1004, 'Gofio', '1'),
    envio(807, 1003, 'GOFIO', '1'),
  ];
  L.enviosAcepciones = [sense(810, 801, 3), sense(811, 806, 1), sense(812, 807, 1)];
  L.dicAulaEntradas = [
    {
      id: 900,
      dic_aula_id: 500,
      envio_entrada_id: 806,
      origen: '1',
      estado: '1',
      created_at: null,
      updated_at: null,
      deleted_at: null,
    },
    {
      id: 901,
      dic_aula_id: 999,
      envio_entrada_id: 806,
      origen: '1',
      estado: '1',
      created_at: D,
      updated_at: D,
      deleted_at: null,
    },
    {
      id: 902,
      dic_aula_id: 500,
      envio_entrada_id: 807,
      origen: '1',
      estado: '1',
      created_at: D,
      updated_at: D,
      deleted_at: null,
    },
  ];
  L.comentariosGenerales = [
    comment(950, { dic_personal_id: null }),
    comment(951, {
      dic_personal_id: 101,
      comentario: `<p>${'Muy bien. '.repeat(210)}</p>`,
      estado: '0',
      updated_at: null,
      fecha_envio: null,
      created_at: null,
    }),
    comment(952, { dic_personal_id: 101, comentario: '<p> </p>' }),
  ];
  L.comentariosEntradas = [
    comment(960, { envio_entrada_id: null }),
    comment(961, { envio_entrada_id: 806, dic_aula_id: 999, persona_id: 11 }), // a persona id, not a users.id
    comment(962, { envio_entrada_id: 806, dic_aula_id: 503, persona_id: 1 }),
    // users.id 3 is a (disabled) student, persona 3 a teacher of 500.
    comment(963, { envio_entrada_id: 806, persona_id: 999 }),
    comment(964, { envio_entrada_id: 806, persona_id: 3 }),
  ];
  return L;
}

async function rows<T>(db: Db, q: ReturnType<typeof sql>): Promise<T[]> {
  return ((await db.execute(q)) as unknown as { rows: T[] }).rows;
}

describe('legacy migration of rare anomalies (in memory → PGlite)', () => {
  let t: TestDb;
  let source: string, target: string;
  let opts: MigrateOptions;
  let r: Report;
  const of = (table: string, id: number | null) =>
    r.anomalies
      .filter((a) => a.table === table && a.legacyId === id)
      .map((a) => `${a.class}: ${a.message}`);

  beforeAll(async () => {
    t = await openPglite();
    source = await mkdtemp(join(tmpdir(), 'lexican-legacy-src-'));
    target = await mkdtemp(join(tmpdir(), 'lexican-legacy-dst-'));
    for (const d of ['imagenes', 'audios', 'videos'])
      await mkdir(join(source, 'dp', 'medios', d), { recursive: true });
    await writeFile(join(source, 'dp', 'medios', 'imagenes', 'a.png'), PNG);
    await writeFile(
      join(source, 'dp', 'medios', 'audios', 'png-como-audio.mp3'),
      Uint8Array.from([...PNG, 1]),
    );
    opts = { mediaSource: source, mediaTarget: target, dryRun: false, failOnOrphans: false };
  }, 60_000);
  afterAll(async () => {
    await t.close();
    await rm(source, { recursive: true, force: true });
    await rm(target, { recursive: true, force: true });
  });

  it('--fail-on-orphans rolls everything back and reports why', async () => {
    const fail = await migrateLegacy(t.db, legacy(), { ...opts, failOnOrphans: true });
    expect(fail.committed).toBe(false);
    expect(fail.failure).toMatch(
      /registros huérfanos con --fail-on-orphans: transacción revertida/,
    );
    expect(fail.orphans).toBeGreaterThan(5);
    expect(await rows(t.db, sql`select 1 from users`)).toEqual([]);
  });

  it('migrates despite the anomalies and passes every verification', async () => {
    r = await migrateLegacy(t.db, legacy(), opts);
    expect(r.failure).toBeNull();
    expect(r.committed).toBe(true);
    expect(r.verification.filter((c) => !c.ok)).toEqual([]);
    expect(Object.values(r.notMigrated).every((n) => n.count === null)).toBe(true);
  });

  it('users: several personas, none, unknown roles, repeated emails and CAS ids, empty names', async () => {
    expect(of('users', 1)).toEqual([
      'needs human review: usuario con 2 personas; se usa la persona 1',
    ]);
    expect(of('users', 2)).toEqual([
      'needs human review: usuario sin persona: nombre provisional «Usuario legacy N»',
      'needs rule: rol legacy desconocido (role_id=3): se asigna «student»',
    ]);
    expect(of('users_personas', 2)).toEqual([
      'cannot migrate: users_personas apunta a persona 999 inexistente',
    ]);
    expect(of('users', 3)).toEqual([
      'needs human review: email repetido (sin distinguir mayúsculas): no se migra',
    ]);
    expect(of('users', 4)).toEqual([
      'needs rule: rol legacy desconocido (role_id=null): se asigna «student»',
      'cannot migrate: users.name vacío: sin identidad CAS (no podrá iniciar sesión)',
    ]);
    expect(of('users', 5)).toEqual([
      'needs human review: identificador CAS repetido: la identidad queda en el primer usuario',
    ]);
    expect(of('users_personas', 14)).toEqual([
      'needs human review: persona enlazada a varios usuarios; se usa el primero (usuario 5 ignorado)',
    ]);
    const u = await rows<{
      legacy_source: string;
      legacy_id: number;
      display_name: string;
      email: string | null;
      status: string;
      global_role: string;
    }>(
      t.db,
      sql`select legacy_source, legacy_id, display_name, email, status, global_role from users order by legacy_source, legacy_id`,
    );
    expect(
      u.map((x) => [
        x.legacy_source,
        x.legacy_id,
        x.display_name,
        x.email,
        x.status,
        x.global_role,
      ]),
    ).toEqual([
      ['personas', 3, 'Tercera Docente', null, 'disabled', 'student'],
      ['personas', 13, 'Persona legacy 13', null, 'disabled', 'student'],
      ['users', 1, 'Marta Docente', 'Prof@Ejemplo.com', 'active', 'teacher'],
      ['users', 2, 'Usuario legacy 2', null, 'active', 'student'],
      ['users', 3, 'Lucía Baja', null, 'disabled', 'student'],
      ['users', 4, 'Pablo Alumno', null, 'active', 'student'],
      ['users', 5, 'Otra Persona', null, 'active', 'student'],
    ]);
    expect(await rows(t.db, sql`select subject from auth_identities order by subject`)).toEqual([
      { subject: 'alu.baja' },
      { subject: 'alu.raro' },
      { subject: 'prof.uno' },
    ]);
  });

  it('schools: empty and deleted codes are skipped, a blank name falls back to the code', async () => {
    expect(r.tables.centros).toMatchObject({ mapped: 2, skipped: 2 });
    expect(await rows(t.db, sql`select code, name from schools order by code`)).toEqual([
      { code: '35000001', name: 'IES Ficticio' },
      { code: '35000002', name: '35000002' },
    ]);
    expect(r.tables.users_centros).toMatchObject({ mapped: 1, skipped: 2, orphaned: 1 });
  });

  it('vocabularies: inactive unknown values, cross-field use, broken references and unknown fields', () => {
    expect(of('mst_ensenanzas', 5)).toEqual([
      'needs human review: «Enseñanza Inventada» sin equivalente en el seed (education_stage): creado con código «ensenanza_inventada»',
    ]);
    expect(of('mst_campos_entrada', 50)).toEqual([
      'needs human review: campo «Campo raro» sin equivalente: su configuración por aula no se migra',
    ]);
    expect(of('mst_campos_valores', 20)).toContainEqual(
      'repairable automatically: «Fauna canaria» cuelga del campo 4 pero se usa como part_of_speech: se asigna por uso',
    );
    expect(of('mst_campos_valores', 30)[0]).toMatch(
      /«Valor colgante» cuelga del campo 50 pero se usa como gender/,
    );
    expect(of('dp_acepciones', 2000)).toContain(
      'needs human review: referencia rota a mst_campos_valores.id=888 (number): queda vacío',
    );
    expect(of('dp_acepciones_tematicas', 2000)).toContain(
      'needs human review: referencia rota a mst_campos_valores.id=777 (topic): queda vacío',
    );
    expect(of('dic_aula', 500)).toContain(
      'needs human review: referencia rota a mst_areas_materias.id=999: queda vacío',
    );
  });

  it('personal dictionaries and entries: duplicates, orphans, empty headwords', async () => {
    expect(of('dic_personal', 100)).toEqual([
      'needs human review: la persona tiene varios diccionarios personales; activo el 101, este queda borrado',
    ]);
    expect(of('dic_personal', 102)).toEqual(['cannot migrate: persona 999 inexistente']);
    expect(of('dp_entradas', 1001)).toEqual(['cannot migrate: entrada vacía']);
    expect(of('dp_entradas', 1002)).toEqual([
      'cannot migrate: diccionario personal 102 no migrado',
    ]);
    expect(of('dp_acepciones', 2010)).toEqual(['cannot migrate: entrada 1002 no migrada']);
    expect(of('dp_entradas', 1003)[0]).toMatch(
      /«drago» repetida en el mismo diccionario \(ya migrada desde 1000\)/,
    );
    expect(of('dp_entradas', 1000)).toEqual([
      'repairable automatically: orden de acepciones (2) renumerado 1..1',
    ]);
    const [d] = await rows<{ title: string }>(
      t.db,
      sql`select title from dictionaries where legacy_source = 'dic_personal' and legacy_id = 101`,
    );
    expect(d!.title).toBe('Mi diccionario personal');
    const [s] = await rows<{ definition: string; topics: number }>(
      t.db,
      sql`select s.definition, (select count(*)::int from sense_topics t where t.sense_id = s.id) as topics
          from entry_senses s where s.legacy_source = 'dp_acepciones' and s.legacy_id = 2000`,
    );
    expect(s).toEqual({ definition: '', topics: 1 }); // the repeated topic is kept once
  });

  it('media: duplicates, unknown kinds, external URLs, missing files and content that contradicts the kind', async () => {
    const m = (id: number) => of('dp_acepciones_medios', id);
    expect(m(3001)).toEqual([
      'repairable automatically: medio duplicado del mismo tipo en la acepción 2000: se conserva el más reciente (3000)',
    ]);
    expect(m(3002)).toEqual([
      'needs human review: tipo_medio audio pero el contenido es image/png: se clasifica por contenido',
      'needs human review: la acepción 2000 ya tiene un image: no se adjunta',
    ]);
    expect(m(3003)).toEqual(['cannot migrate: tipo_medio desconocido «9»']);
    expect(m(3004)).toEqual(['cannot migrate: tipo_medio desconocido «null»']);
    expect(m(3006)).toEqual([
      'repairable automatically: medio duplicado del mismo tipo en la acepción 2000: se conserva el más reciente (3007)',
    ]);
    expect(m(3007)).toEqual(['cannot migrate: medio por URL externa: no soportado']);
    expect(m(3020)).toEqual([
      'cannot migrate: fichero no encontrado: dp/medios/imagenes/perdida.png',
    ]);
    expect(m(3021)).toEqual([]); // same missing path: counted once, not reported twice
    expect(r.media).toMatchObject({ migrated: 2, missing: 1, duplicates: 2, brokenRefs: 1 });
    expect(r.tables.dp_acepciones_medios).toMatchObject({ skipped: 3 });
    expect(
      r.verification.find((c) => c.check === 'medios copiados con checksum correcto'),
    ).toMatchObject({ ok: true, detail: '2/2 ficheros verificados' });
  });

  it('classrooms: invalid validity, shared codes, deleted and orphan classrooms, guidelines and field config', async () => {
    expect(of('dic_aula', 500)).toEqual(
      expect.arrayContaining([
        expect.stringMatching(
          /^repairable automatically: código «ABCDEF» no válido o repetido: se genera [A-Z2-9]{6}$/,
        ),
        'needs rule: comentarios_visibles=2 sin fecha: se migra como «hidden»',
      ]),
    );
    expect(of('dic_aula', 501)).toEqual([
      expect.stringMatching(/código «abcdef» no válido o repetido/),
      'repairable automatically: vigencia 11 fuera de 1..10: 10',
      'repairable automatically: sin dic_aula_campos: todos los campos visibles, ninguno obligatorio',
    ]);
    expect(of('dic_aula', 502)).toEqual(['cannot migrate: profesor (persona 999) inexistente']);
    // Boundaries: 2.5 is truncated, 10 is the maximum and kept silently.
    expect(of('dic_aula', 504)).toEqual([
      'repairable automatically: vigencia 2.5 fuera de 1..10: 2',
      'repairable automatically: sin dic_aula_campos: todos los campos visibles, ninguno obligatorio',
    ]);
    expect(of('dic_aula', 505)).toEqual([
      'repairable automatically: sin dic_aula_campos: todos los campos visibles, ninguno obligatorio',
    ]);
    expect(of('dic_aula', 503)).toEqual([
      expect.stringMatching(/código «» no válido o repetido/),
      'repairable automatically: vigencia 0 fuera de 1..10: 1',
      'repairable automatically: sin dic_aula_campos: todos los campos visibles, ninguno obligatorio',
    ]);
    expect(of('dic_aula_atemporales', 1)).toEqual([
      'needs human review: atemporal con fecha «hasta»: se migra como permanente sin fecha',
    ]);
    expect(of('dic_aula_pautas', 1)).toEqual([
      'needs human review: fichero adjunto a las pautas: no se migra',
    ]);
    expect(r.tables.dic_aula_campos).toMatchObject({ mapped: 1, skipped: 1, invalid: 1 });
    const cs = await rows<{
      id: number;
      title: string;
      timeless: boolean;
      validity_years: number;
      visible_fields: string[];
      deleted: boolean;
      comments_visibility: string;
    }>(
      t.db,
      sql`select d.legacy_id as id, d.title, s.timeless, s.validity_years, s.visible_fields, d.deleted_at is not null as deleted, s.comments_visibility
          from dictionaries d join classroom_settings s on s.dictionary_id = d.id order by d.legacy_id`,
    );
    expect(
      cs.map((c) => [
        c.id,
        c.title,
        c.timeless,
        c.validity_years,
        c.deleted,
        c.comments_visibility,
      ]),
    ).toEqual([
      [500, 'Diccionario de aula 500', true, 1, false, 'hidden'],
      [501, 'Aula 501', false, 10, true, 'visible'],
      [503, 'Aula 503', false, 1, false, 'visible'],
      [504, 'Aula 504', false, 2, false, 'visible'],
      [505, 'Aula 505', false, 10, false, 'visible'],
    ]);
    expect(cs[0]!.visible_fields).toEqual(['topics']);
  });

  it('members: merged duplicates, unknown roles, orphans and an owner restored as active teacher', async () => {
    expect(of('dic_aula_participantes', 601)).toEqual([
      'repairable automatically: participante repetido (también 600): se fusiona',
    ]);
    expect(of('dic_aula_participantes', 602)).toEqual([
      'repairable automatically: el propietario del aula figuraba como alumno o deshabilitado: pasa a profesor activo',
    ]);
    expect(of('dic_aula_participantes', 603)).toEqual([
      'needs human review: rol de aula «raro» desconocido: no se migra',
    ]);
    expect(of('dic_aula_participantes', 605)).toEqual([
      'cannot migrate: aula 999 o persona 11 no migradas',
    ]);
    expect(r.tables.dic_aula_participantes).toMatchObject({ skipped: 1, invalid: 1, orphaned: 1 });
    const m = await rows<{ id: number; role: string; active: boolean }>(
      t.db,
      sql`select legacy_id as id, role, active from dictionary_memberships where legacy_source = 'dic_aula_participantes' order by legacy_id`,
    );
    expect(m).toEqual([
      { id: 600, role: 'teacher', active: true },
      { id: 602, role: 'teacher', active: true },
      { id: 606, role: 'teacher', active: true },
    ]);
  });

  it('submissions: superseded pendings, orphans, placeholders and «published» without a classroom entry', async () => {
    expect(of('envios_entradas', 800)).toEqual([
      'repairable automatically: otro envío pendiente más reciente de la misma entrada al mismo aula: este pasa a «withdrawn»',
    ]);
    expect(of('envios_entradas', 801)).toEqual([
      'repairable automatically: orden de acepciones (3) renumerado 1..1',
    ]);
    expect(of('envios_entradas', 802)).toEqual([
      'needs rule: estado publicado sin dic_aula_entradas en su aula: se migra como «withdrawn»',
    ]);
    expect(of('envios_entradas', 803)).toEqual([
      'cannot migrate: sin envío (9999), aula o alumno migrados',
    ]);
    expect(of('envios_entradas', 804)).toEqual([
      'cannot migrate: entrada personal 1002 no migrada',
    ]);
    expect(of('envios_entradas', 805)).toEqual([
      'repairable automatically: dp_entrada 5555 inexistente: se crea una entrada personal borrada como origen del envío',
    ]);
    expect(of('dic_aula_entradas', 901)).toEqual([
      'cannot migrate: envío 806 o aula 999 no migrados',
    ]);
    expect(of('dic_aula_entradas', 902)).toEqual([
      'needs human review: «GOFIO» repetida en el mismo diccionario (ya migrada desde 900): se migra como borrada',
    ]);
    const s = await rows<{ id: number; status: string; published: boolean; reviewed: boolean }>(
      t.db,
      sql`select legacy_id as id, status, published_entry_id is not null as published, reviewed_by is not null as reviewed
          from submissions order by legacy_id`,
    );
    expect(s.map((x) => [x.id, x.status, x.published, x.reviewed])).toEqual([
      [800, 'withdrawn', false, false],
      [801, 'pending', false, false],
      [802, 'withdrawn', false, false],
      [805, 'withdrawn', false, false],
      [806, 'published', true, true],
      [807, 'published', true, true],
    ]);
  });

  it('comments: orphans, persona ids, ambiguous authors, empty, long and hidden comments', async () => {
    expect(of('comentarios_generales', 950)).toEqual([
      'cannot migrate: aula, alumno o autor no migrados',
    ]);
    expect(of('comentarios_generales', 951)).toEqual([
      expect.stringMatching(
        /^needs human review: comentario de 2\d{3} caracteres \(la app admite 2000\): se conserva completo$/,
      ),
      'needs rule: comentario «no visible» (estado 0): se migra como borrado',
    ]);
    expect(of('comentarios_generales', 952)).toEqual([
      'cannot migrate: comentario vacío tras quitar el HTML',
    ]);
    expect(of('comentarios_entradas', 960)).toEqual(['cannot migrate: envío null no migrado']);
    expect(of('comentarios_entradas', 961)).toEqual([
      'needs human review: persona_id no es un users.id: se interpreta como persona',
    ]);
    expect(of('comentarios_entradas', 962)).toEqual([
      'needs human review: dic_aula_id distinto del aula del envío: se usa el del comentario',
    ]);
    expect(of('comentarios_entradas', 964)).toEqual([
      'needs human review: autor ambiguo: como users.id no es profesor del aula, como persona sí',
    ]);
    expect(of('comentarios_entradas', 963)).toEqual([
      'cannot migrate: autor 999 no encontrado ni como usuario ni como persona',
    ]);
    const [long] = await rows<{ len: number; deleted: boolean }>(
      t.db,
      sql`select length(body)::int as len, deleted_at is not null as deleted from comments where legacy_id = 951`,
    );
    expect(long!.len).toBeGreaterThan(2000);
    expect(long!.deleted).toBe(true);
  });

  it('master guidelines with empty text are stored as an empty setting', async () => {
    expect(
      await rows(t.db, sql`select value from app_settings where key = 'master_guidelines'`),
    ).toEqual([{ value: '' }]);
    expect(r.tables.mst_pautas).toMatchObject({ mapped: 1, skipped: 0 });
  });

  it('re-running is idempotent: same rows, kept join codes, media reused by content', async () => {
    const count = async () =>
      (
        await rows<{ n: number }>(
          t.db,
          sql`select (select count(*) from users) + (select count(*) from entries) + (select count(*) from submissions) + (select count(*) from media_assets) as n`,
        )
      )[0]!.n;
    const codes = async () =>
      rows(t.db, sql`select join_code from classroom_settings order by dictionary_id`);
    const before = [await count(), await codes()];
    const again = await migrateLegacy(t.db, legacy(), opts);
    expect(again.failure).toBeNull();
    expect([await count(), await codes()]).toEqual(before);
    // Assets already in the target are found by checksum: nothing new is created or copied.
    expect(again.media).toMatchObject({ migrated: 0, sameContent: 0, bytesCopied: 0 });
  });

  it('an empty legacy database migrates cleanly; inactive master guidelines are skipped', async () => {
    const L = empty();
    L.mstPautas = [{ id: 1, texto: '<p>x</p>', estado: 0, deleted_at: null }];
    const fresh = await openPglite();
    try {
      const e = await migrateLegacy(fresh.db, L, { ...opts, dryRun: true });
      expect(e).toMatchObject({ failure: null, committed: false, orphans: 0 });
      expect(e.tables.mst_pautas).toMatchObject({ mapped: 0, skipped: 1 });
      expect(e.verification.every((c) => c.ok)).toBe(true);
    } finally {
      await fresh.close();
    }
  });
});
