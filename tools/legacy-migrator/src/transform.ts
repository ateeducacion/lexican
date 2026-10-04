import {
  JOIN_CODE_ALPHABET,
  mediaKindOf,
  type CommentVisibility,
  type EntrySnapshot,
  type GlobalRole,
  type MediaKind,
  type MediaView,
  type MemberRole,
  type SenseField,
  type SubmissionStatus,
  type Vocabulary,
} from '@lexican/core';

/** Pure legacy → new transformations. Every rule here is documented in docs/LEGACY-DATA-MAPPING.md and unit-tested. */

/** Label comparison key: trimmed, single-spaced, case- and accent-insensitive (MySQL `utf8mb4_unicode_ci`-like). */
export const normalizeLabel = (s: string): string =>
  s.normalize('NFD').replace(/\p{M}/gu, '').trim().replace(/\s+/g, ' ').toLowerCase();

/** Stable snake_case code for a value that has no seeded equivalent. */
export const codeFromLabel = (s: string): string =>
  normalizeLabel(s)
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '') || 'valor';

/** Production order of `mst_campos_entrada` differs from a fresh seed: always match by label, never by id. */
const FIELD_LABELS: Record<string, SenseField> = {
  'categoria gramatical': 'part_of_speech',
  genero: 'gender',
  numero: 'number',
  'tematicas generales': 'topics',
  tematicas: 'topics',
  'mas datos': 'extra_info',
  'frase ejemplo': 'extra_info',
  video: 'video',
  audio: 'audio',
  imagen: 'image',
  lengua: 'language',
  'lengua-idioma': 'language',
  'otros lenguajes': 'language',
  'otras lenguas': 'language',
  'ejemplo de uso': 'example',
};
export const fieldCodeOf = (nombreCampo: string): SenseField | null =>
  FIELD_LABELS[normalizeLabel(nombreCampo)] ?? null;

/** Vocabulary of the values hanging from a `mst_campos_entrada` row (null for text/media fields). */
export function vocabularyOfField(nombreCampo: string): Vocabulary | null {
  switch (fieldCodeOf(nombreCampo)) {
    case 'part_of_speech':
      return 'part_of_speech';
    case 'gender':
      return 'gender';
    case 'number':
      return 'number';
    case 'topics':
      return 'topic';
    case 'language':
      return 'language';
    default:
      return null;
  }
}

/** Global role by legacy `roles.name` (ids differ per environment, RULE-203/206). */
export function globalRoleOf(roleName: string | undefined): GlobalRole | null {
  switch (roleName) {
    case 'admin':
      return 'admin';
    case 'user': // shown as «Oficina técnica»
      return 'support';
    case 'docente':
      return 'teacher';
    case 'alumno':
      return 'student';
    default:
      return null;
  }
}

/** Classroom role by legacy `roles.name` of `dic_aula_participantes.rol_diccionario_id`. */
export const memberRoleOf = (roleName: string | undefined): MemberRole | null =>
  roleName === 'docente' ? 'teacher' : roleName === 'alumno' ? 'student' : null;

const valid = (d: Date | null | undefined): Date | null =>
  d instanceof Date && !Number.isNaN(d.getTime()) ? d : null;
/** mysql2 returns `Invalid Date` for MySQL zero dates. */
export const dateOrNull = valid;

/** `dp_entradas` / `dp_acepciones` / `envios_acepciones` estado: 0 deleted, 1 visible, 2 hidden (CT:267-271). */
export function entryStateOf(
  estado: string | null,
  deletedAt: Date | null,
  updatedAt: Date | null,
): { hidden: boolean; deletedAt: Date | null } {
  const deleted = valid(deletedAt) ?? (estado === '0' ? (valid(updatedAt) ?? new Date(0)) : null);
  return { hidden: estado === '2', deletedAt: deleted };
}

/**
 * Submission status of an `envios_entradas` row. A `dic_aula_entradas` row in the submission's own classroom
 * is the source of truth for «published» (legacy also writes 2 «oculta» on published rows, an enum collision).
 */
export function submissionStatusOf(
  estado: string | null,
  deletedAt: Date | null,
  publishedHere: boolean,
): SubmissionStatus {
  if (publishedHere) return 'published';
  if (valid(deletedAt)) return 'withdrawn';
  return estado === '1' ? 'pending' : 'withdrawn';
}

/** `comentarios_visibles` 0/1/2 (CT:365-369). Value 2 without a date cannot be honoured: hidden (reported). */
export function commentsVisibilityOf(
  value: string | null,
  before: Date | null,
): { visibility: CommentVisibility; before: Date | null; problem: string | null } {
  if (value === '1') return { visibility: 'visible', before: null, problem: null };
  if (value === '2') {
    const d = valid(before);
    return d
      ? { visibility: 'before_date', before: d, problem: null }
      : {
          visibility: 'hidden',
          before: null,
          problem: 'comentarios_visibles=2 sin fecha: se migra como «hidden»',
        };
  }
  return {
    visibility: 'hidden',
    before: null,
    problem:
      value === '0' || value === null
        ? null
        : `comentarios_visibles desconocido «${value}»: «hidden»`,
  };
}

/** Legacy stored a boolean in `max_acepciones_entrada` (RULE-I34): only values > 1 are a real limit. */
export const maxSensesOf = (n: number | null): number | null =>
  n !== null && n > 1 ? Math.min(n, 50) : null;

/** Legacy join code kept only when it is a valid new code (4-6 uppercase letters/digits). */
/** A legacy code is kept only if it is a valid current code: 6 chars of the unambiguous alphabet (SEC-002). */
export const isJoinCode = (c: string): boolean =>
  c.length === 6 && [...c].every((ch) => JOIN_CODE_ALPHABET.includes(ch));

export function legacyJoinCode(codigo: string | null): string | null {
  const c = (codigo ?? '').trim().toUpperCase();
  return isJoinCode(c) ? c : null;
}

/** Same generator as the app (6 chars, unambiguous alphabet). */
export const generateJoinCode = (
  random: (n: number) => Uint8Array = (n) => crypto.getRandomValues(new Uint8Array(n)),
): string => [...random(6)].map((b) => JOIN_CODE_ALPHABET[b % JOIN_CODE_ALPHABET.length]).join('');

/** `letra_grupo` '0' means «no group / several groups». */
export const groupLabelOf = (letra: string | null): string => {
  const l = (letra ?? '').trim();
  return l === '0' ? '' : l;
};

/** Sort by (orden, id) and report whether `orden` was not already 1..n. */
export function orderSenses<T extends { orden: number; id: number }>(
  rows: T[],
): { rows: T[]; renumbered: boolean } {
  const sorted = [...rows].sort((a, b) => a.orden - b.orden || a.id - b.id);
  return { rows: sorted, renumbered: sorted.some((r, i) => r.orden !== i + 1) };
}

const ENTITIES: Record<string, string> = {
  nbsp: ' ',
  amp: '&',
  lt: '<',
  gt: '>',
  quot: '"',
  apos: "'",
  iexcl: '¡',
  iquest: '¿',
  laquo: '«',
  raquo: '»',
  aacute: 'á',
  eacute: 'é',
  iacute: 'í',
  oacute: 'ó',
  uacute: 'ú',
  Aacute: 'Á',
  Eacute: 'É',
  Iacute: 'Í',
  Oacute: 'Ó',
  Uacute: 'Ú',
  ntilde: 'ñ',
  Ntilde: 'Ñ',
  uuml: 'ü',
  Uuml: 'Ü',
  ccedil: 'ç',
  Ccedil: 'Ç',
  ordf: 'ª',
  ordm: 'º',
  deg: '°',
  euro: '€',
  hellip: '…',
  mdash: '—',
  ndash: '–',
  lsquo: '‘',
  rsquo: '’',
  ldquo: '“',
  rdquo: '”',
  middot: '·',
  bull: '•',
};

/**
 * TinyMCE HTML → plain text (guidelines and comments, §43/§57): block ends and <br> become newlines,
 * list items become «- » lines, other tags are dropped and entities decoded.
 */
export function htmlToText(html: string): string {
  return html
    .replace(/<(script|style)\b[\s\S]*?<\/\1>/gi, '')
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<li\b[^>]*>/gi, '\n- ')
    .replace(/<\/li>/gi, '')
    .replace(/<\/(p|div|ul|ol|h[1-6]|tr|table|blockquote)>/gi, '\n\n')
    .replace(/<[^>]*>/g, '')
    .replace(/&(#x[0-9a-f]+|#\d+|[a-z]+);/gi, (m, e: string) => {
      if (e[0] === '#') {
        const cp =
          e[1] === 'x' || e[1] === 'X' ? parseInt(e.slice(2), 16) : parseInt(e.slice(1), 10);
        return cp > 0 && cp <= 0x10ffff ? String.fromCodePoint(cp) : m;
      }
      return ENTITIES[e] ?? m;
    })
    .replace(/[ \t\u00a0]+/g, ' ')
    .split('\n')
    .map((l) => l.trim())
    .join('\n')
    .replace(/\n{3,}/g, '\n\n')
    .trim();
}

/** HTML constructs that are lost by htmlToText and need a person to check the result. */
export const htmlLosses = (html: string): string[] =>
  ['img', 'table', 'iframe', 'video', 'audio'].filter((t) =>
    new RegExp(`<${t}\\b`, 'i').test(html),
  );

/** Legacy `tipo_medio` and its folder under storage/app/public/dp/medios (CT:82-99). */
export const LEGACY_MEDIA: Record<string, { kind: MediaKind; dir: string }> = {
  '1': { kind: 'image', dir: 'imagenes' },
  '2': { kind: 'audio', dir: 'audios' },
  '3': { kind: 'video', dir: 'videos' },
};

/** A stored file name must be a plain name (no separators, no `..`) before it is joined to a path. */
export const safeFileName = (name: string | null): string | null => {
  const n = (name ?? '').trim();
  return n && !/[\\/]/.test(n) && n !== '.' && n !== '..' && !n.includes('\0') ? n : null;
};

/**
 * Content sniffing for the formats the new app accepts (core MEDIA_MIME). WebM is audio or video depending
 * on the legacy kind (the container is the same). Returns null for anything else (bmp, tiff, empty…).
 */
export function sniffMedia(
  b: Uint8Array,
  legacyKind: MediaKind,
): { mime: string; ext: string; kind: MediaKind } | null {
  const at = (i: number, ...bytes: number[]) => bytes.every((x, j) => b[i + j] === x);
  const ascii = (i: number, s: string) => at(i, ...[...s].map((c) => c.charCodeAt(0)));
  let type: { mime: string; ext: string } | null = null;
  if (at(0, 0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a))
    type = { mime: 'image/png', ext: 'png' };
  else if (at(0, 0xff, 0xd8, 0xff)) type = { mime: 'image/jpeg', ext: 'jpg' };
  else if (ascii(0, 'GIF87a') || ascii(0, 'GIF89a')) type = { mime: 'image/gif', ext: 'gif' };
  else if (ascii(0, 'RIFF') && ascii(8, 'WEBP')) type = { mime: 'image/webp', ext: 'webp' };
  else if (ascii(0, 'RIFF') && ascii(8, 'WAVE')) type = { mime: 'audio/wav', ext: 'wav' };
  else if (ascii(0, 'OggS')) type = { mime: 'audio/ogg', ext: 'ogg' };
  else if (ascii(0, 'ID3') || (b.length > 1 && b[0] === 0xff && (b[1]! & 0xe0) === 0xe0))
    type = { mime: 'audio/mpeg', ext: 'mp3' };
  else if (ascii(4, 'ftyp')) type = { mime: 'video/mp4', ext: 'mp4' };
  else if (at(0, 0x1a, 0x45, 0xdf, 0xa3))
    type =
      legacyKind === 'audio'
        ? { mime: 'audio/webm', ext: 'webm' }
        : { mime: 'video/webm', ext: 'webm' };
  const kind = type ? mediaKindOf(type.mime) : null;
  return type && kind ? { ...type, kind } : null;
}

/** Same sanitising as the app's upload (originalName is metadata only). */
export const originalNameOf = (nombre: string): string =>
  nombre.replace(/[^\p{L}\p{N} ._-]/gu, '_').slice(0, 120);

export interface SenseData {
  definition: string;
  extraInfo: string;
  example: string;
  partOfSpeechId: string | null;
  genderId: string | null;
  numberId: string | null;
  languageId: string | null;
  foreignForm: string;
  hidden: boolean;
  topicIds: string[];
  media: Omit<MediaView, 'url'>[];
}

/** Same shape as the app's `snapshotOf`: hidden senses are left out, positions renumbered, URLs recomputed on read. */
export const buildSnapshot = (headword: string, senses: SenseData[]): EntrySnapshot => ({
  headword,
  senses: senses
    .filter((s) => !s.hidden)
    .map(({ hidden: _hidden, media, ...rest }, i) => ({
      ...rest,
      position: i + 1,
      media: media.map((m) => ({ ...m, url: '' })),
    })),
});

/** RFC 4180 CSV line. */
export const csvLine = (cells: (string | number | null)[]): string =>
  cells
    .map((c) =>
      c === null
        ? ''
        : /[",\n\r]/.test(String(c))
          ? `"${String(c).replace(/"/g, '""')}"`
          : String(c),
    )
    .join(',');
