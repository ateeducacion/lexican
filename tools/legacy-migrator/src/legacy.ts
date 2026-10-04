import type { Connection, RowDataPacket } from 'mysql2/promise';

/**
 * Read side of the legacy MariaDB/MySQL database (Laravel 8 schema, docs/LEGACY-DATA-MAPPING.md).
 * Columns are listed explicitly: national ids (personas.NIF_NIE, cial, pasaporte), passwords and
 * tokens are never selected, so they cannot leak into the target or the report (§21).
 * ponytail: loads every table into memory; fine for a few hundred thousand rows, stream per table if it grows.
 */

type Flag = string | null; // char(1) estado/flags
type When = Date | null;

export interface Role {
  id: number;
  name: string;
}
export interface LegacyUser {
  id: number;
  name: string;
  email: string | null;
  role_id: number | null;
  created_at: When;
  updated_at: When;
}
export interface Persona {
  id: number;
  nombre: string;
  apellidos: string;
  estado: Flag;
  created_at: When;
  updated_at: When;
  deleted_at: When;
}
export interface UserPersona {
  user_id: number;
  persona_id: number;
  created_at: When;
  deleted_at: When;
}
export interface Centro {
  id: number;
  cod_centro: string;
  denominacion: string;
  deleted_at: When;
}
export interface UserCentro {
  user_id: number;
  centro_id: number;
  deleted_at: When;
}
export interface Master {
  id: number;
  descripcion: string;
  estado: Flag;
  deleted_at: When;
}
export interface CampoEntrada {
  id: number;
  nombre_campo: string;
}
export interface CampoValor extends Master {
  mst_campo_entrada_id: number;
}
export interface MstPauta {
  id: number;
  texto: string | null;
  estado: number | null;
  deleted_at: When;
}
export interface DicPersonal {
  id: number;
  persona_id: number;
  titulo: string;
  created_at: When;
  updated_at: When;
  deleted_at: When;
}
export interface DpEntrada {
  id: number;
  dic_personal_id: number;
  entrada: string;
  estado: Flag;
  created_at: When;
  updated_at: When;
  deleted_at: When;
}
export interface Acepcion {
  id: number;
  entrada_id: number;
  orden: number;
  cat_gramatical_id: number | null;
  genero_id: number | null;
  numero_id: number | null;
  idioma_id: number | null;
  idioma_palabra: string | null;
  definicion: string;
  frase_ejemplo: string | null;
  ejemplo2: string | null;
  estado: Flag;
  created_at: When;
  updated_at: When;
  deleted_at: When;
}
export interface Tematica {
  acepcion_id: number;
  tematica_id: number;
  deleted_at: When;
}
export interface Medio {
  id: number;
  acepcion_id: number;
  tipo_medio: Flag;
  nombre: string;
  url_externa: string | null;
  url_interna: string | null;
  estado: Flag;
  created_at: When;
  deleted_at: When;
}
export interface DicAula {
  id: number;
  persona_id: number;
  mst_tipo_dic_id: number | null;
  titulo: string;
  descripcion: string | null;
  letra_grupo: string | null;
  mst_nivel_estudios_id: number | null;
  mst_area_materia_id: number | null;
  mst_ensenanza_id: number | null;
  ano_ini_curso_escolar: number;
  max_acepciones_entrada: number | null;
  codigo: string | null;
  visible_estudiante: Flag;
  envios_habilitados: Flag;
  envios_fecha_ini: When;
  envios_fecha_fin: When;
  estado: Flag;
  comentarios_visibles: Flag;
  comentarios_visibles_anteriores_a: When;
  vigencia: number | null;
  created_at: When;
  updated_at: When;
  deleted_at: When;
}
export interface Atemporal {
  id: number;
  dic_aula_id: number;
  estado: Flag;
  hasta: When;
  deleted_at: When;
}
export interface DicAulaPauta {
  id: number;
  dic_aula_id: number;
  texto: string | null;
  url_fichero: string | null;
  estado: Flag;
  deleted_at: When;
}
export interface Participante {
  id: number;
  dic_aula_id: number;
  persona_id: number;
  rol_diccionario_id: number;
  estado: Flag;
  deleted_at: When;
}
export interface DicAulaCampo {
  id: number;
  dic_aula_id: number;
  mst_campo_entrada_id: number;
  visible: Flag;
  obligatorio: Flag;
  estado: Flag;
  deleted_at: When;
}
export interface DpEnvio {
  id: number;
  dic_personal_id: number;
  dic_aula_id: number;
  estado: Flag;
  created_at: When;
  deleted_at: When;
}
export interface EnvioEntrada {
  id: number;
  dp_envio_id: number;
  dp_entrada_id: number;
  entrada: string;
  estado: Flag;
  updated_by_persona_id: number | null;
  created_at: When;
  updated_at: When;
  deleted_at: When;
}
export interface DicAulaEntrada {
  id: number;
  dic_aula_id: number;
  envio_entrada_id: number;
  origen: Flag;
  estado: Flag;
  created_at: When;
  updated_at: When;
  deleted_at: When;
}
export interface Comentario {
  id: number;
  dic_aula_id: number;
  persona_id: number;
  /** comentarios_generales only. */
  dic_personal_id: number | null;
  /** comentarios_entradas only. */
  envio_entrada_id: number | null;
  comentario: string;
  fecha_envio: When;
  estado: Flag;
  created_at: When;
  updated_at: When;
  deleted_at: When;
}

export interface LegacyData {
  roles: Role[];
  users: LegacyUser[];
  personas: Persona[];
  usersPersonas: UserPersona[];
  centros: Centro[];
  usersCentros: UserCentro[];
  ensenanzas: Master[];
  nivelEstudios: Master[];
  areasMaterias: Master[];
  tiposDiccionario: Master[];
  camposEntrada: CampoEntrada[];
  camposValores: CampoValor[];
  mstPautas: MstPauta[];
  dicPersonal: DicPersonal[];
  dpEntradas: DpEntrada[];
  dpAcepciones: Acepcion[];
  dpTematicas: Tematica[];
  dpMedios: Medio[];
  dicAula: DicAula[];
  atemporales: Atemporal[];
  dicAulaPautas: DicAulaPauta[];
  participantes: Participante[];
  dicAulaCampos: DicAulaCampo[];
  dpEnvios: DpEnvio[];
  enviosEntradas: EnvioEntrada[];
  enviosAcepciones: Acepcion[];
  enviosTematicas: Tematica[];
  enviosMedios: Medio[];
  dicAulaEntradas: DicAulaEntrada[];
  comentariosGenerales: Comentario[];
  comentariosEntradas: Comentario[];
  /** Tables that are only counted (not migrated); null when the table does not exist. */
  counts: Record<string, number | null>;
}

/** Legacy tables that are intentionally not migrated (reasons in docs/LEGACY-DATA-MAPPING.md). */
export const COUNTED_ONLY = [
  'audits',
  'password_resets',
  'avisos',
  'avisos_dp_envios',
  'avisos_coment_generales',
  'avisos_coment_entradas',
  'avisos_entradas_compartidas',
  'entradas_compartidas',
  'dic_aula_destinatario_avisos',
  'mst_ensenanzas_estudios',
  'mst_estudios_areas_materias',
  'centros_dic_aula',
  'user_roles',
  'permissions',
  'permission_role',
  'menus',
  'menu_items',
  'data_types',
  'data_rows',
  'settings',
  'translations',
  'pages',
  'posts',
  'categories',
  'migrations',
] as const;

const ACEPCION = `id, orden, cat_gramatical_id, genero_id, numero_id, idioma_id, idioma_palabra, definicion,
  frase_ejemplo, ejemplo2, estado, created_at, updated_at, deleted_at`;
const MEDIO = 'id, tipo_medio, nombre, url_externa, url_interna, estado, created_at, deleted_at';
const MASTER = 'id, descripcion, estado, deleted_at';
const COMENTARIO =
  'id, dic_aula_id, persona_id, comentario, fecha_envio, estado, created_at, updated_at, deleted_at';

export async function loadLegacy(conn: Connection): Promise<LegacyData> {
  const [tableRows] = await conn.query<RowDataPacket[]>(
    'select table_name as t from information_schema.tables where table_schema = database()',
  );
  const existing = new Set(tableRows.map((r) => String(r.t)));
  const q = async <T>(table: string, sql: string): Promise<T[]> => {
    if (!existing.has(table)) return [];
    const [rows] = await conn.query<RowDataPacket[]>(sql);
    return rows as unknown as T[];
  };
  const counts: Record<string, number | null> = {};
  for (const t of COUNTED_ONLY) {
    if (!existing.has(t)) counts[t] = null;
    else
      counts[t] = Number(
        (
          (await conn.query<RowDataPacket[]>(`select count(*) as n from \`${t}\``))[0][0] as {
            n: number;
          }
        ).n,
      );
  }
  return {
    roles: await q('roles', 'select id, name from roles'),
    users: await q(
      'users',
      'select id, name, email, role_id, created_at, updated_at from users order by id',
    ),
    personas: await q(
      'personas',
      'select id, nombre, apellidos, estado, created_at, updated_at, deleted_at from personas order by id',
    ),
    usersPersonas: await q(
      'users_personas',
      'select user_id, persona_id, created_at, deleted_at from users_personas',
    ),
    centros: await q(
      'centros',
      'select id, cod_centro, denominacion, deleted_at from centros order by id',
    ),
    usersCentros: await q(
      'users_centros',
      'select user_id, centro_id, deleted_at from users_centros',
    ),
    ensenanzas: await q('mst_ensenanzas', `select ${MASTER} from mst_ensenanzas order by id`),
    nivelEstudios: await q(
      'mst_nivel_estudios',
      `select ${MASTER} from mst_nivel_estudios order by id`,
    ),
    areasMaterias: await q(
      'mst_areas_materias',
      `select ${MASTER} from mst_areas_materias order by id`,
    ),
    tiposDiccionario: await q(
      'mst_tipos_diccionario_aula',
      'select id, tipo_diccionario as descripcion, estado, deleted_at from mst_tipos_diccionario_aula order by id',
    ),
    camposEntrada: await q(
      'mst_campos_entrada',
      'select id, nombre_campo from mst_campos_entrada order by id',
    ),
    camposValores: await q(
      'mst_campos_valores',
      `select ${MASTER}, mst_campo_entrada_id from mst_campos_valores order by id`,
    ),
    mstPautas: await q(
      'mst_pautas',
      'select id, texto, estado, deleted_at from mst_pautas order by id',
    ),
    dicPersonal: await q(
      'dic_personal',
      'select id, persona_id, titulo, created_at, updated_at, deleted_at from dic_personal order by id',
    ),
    dpEntradas: await q(
      'dp_entradas',
      'select id, dic_personal_id, entrada, estado, created_at, updated_at, deleted_at from dp_entradas order by id',
    ),
    dpAcepciones: await q(
      'dp_acepciones',
      `select dic_entrada_id as entrada_id, ${ACEPCION} from dp_acepciones order by id`,
    ),
    dpTematicas: await q(
      'dp_acepciones_tematicas',
      'select dp_acepcion_id as acepcion_id, tematica_id, deleted_at from dp_acepciones_tematicas',
    ),
    dpMedios: await q(
      'dp_acepciones_medios',
      `select dic_acepcion_id as acepcion_id, ${MEDIO} from dp_acepciones_medios order by id`,
    ),
    dicAula: await q('dic_aula', 'select * from dic_aula order by id'),
    atemporales: await q(
      'dic_aula_atemporales',
      'select id, dic_aula_id, estado, hasta, deleted_at from dic_aula_atemporales order by id',
    ),
    dicAulaPautas: await q(
      'dic_aula_pautas',
      'select id, dic_aula_id, texto, url_fichero, estado, deleted_at from dic_aula_pautas order by id',
    ),
    participantes: await q(
      'dic_aula_participantes',
      'select id, dic_aula_id, persona_id, rol_diccionario_id, estado, deleted_at from dic_aula_participantes order by id',
    ),
    dicAulaCampos: await q(
      'dic_aula_campos',
      'select id, dic_aula_id, mst_campo_entrada_id, visible, obligatorio, estado, deleted_at from dic_aula_campos order by id',
    ),
    dpEnvios: await q(
      'dp_envios',
      'select id, dic_personal_id, dic_aula_id, estado, created_at, deleted_at from dp_envios order by id',
    ),
    enviosEntradas: await q(
      'envios_entradas',
      `select id, dp_envio_id, dp_entrada_id, entrada, estado, updated_by_persona_id, created_at, updated_at, deleted_at
       from envios_entradas order by id`,
    ),
    enviosAcepciones: await q(
      'envios_acepciones',
      `select envio_entrada_id as entrada_id, ${ACEPCION} from envios_acepciones order by id`,
    ),
    enviosTematicas: await q(
      'envios_acepciones_tematicas',
      'select envio_acepcion_id as acepcion_id, tematica_id, deleted_at from envios_acepciones_tematicas',
    ),
    enviosMedios: await q(
      'envios_acepciones_medios',
      `select envio_acepcion_id as acepcion_id, ${MEDIO} from envios_acepciones_medios order by id`,
    ),
    dicAulaEntradas: await q(
      'dic_aula_entradas',
      'select id, dic_aula_id, envio_entrada_id, origen, estado, created_at, updated_at, deleted_at from dic_aula_entradas order by id',
    ),
    comentariosGenerales: await q(
      'comentarios_generales',
      `select ${COMENTARIO}, dic_personal_id, null as envio_entrada_id from comentarios_generales order by id`,
    ),
    comentariosEntradas: await q(
      'comentarios_entradas',
      `select ${COMENTARIO}, null as dic_personal_id, envio_entrada_id from comentarios_entradas order by id`,
    ),
    counts,
  };
}
