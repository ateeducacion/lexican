/**
 * Sense fields a classroom can show or require (legacy `mst_campos_entrada`, production order).
 * Definition is always visible and required, so it is not configurable.
 */
export const SENSE_FIELDS = [
  { code: 'part_of_speech', label: 'Categoría gramatical' },
  { code: 'gender', label: 'Género' },
  { code: 'number', label: 'Número' },
  { code: 'topics', label: 'Temáticas' },
  { code: 'extra_info', label: 'Más datos' },
  { code: 'video', label: 'Vídeo' },
  { code: 'audio', label: 'Audio' },
  { code: 'image', label: 'Imagen' },
  { code: 'language', label: 'Otras lenguas' },
  { code: 'example', label: 'Ejemplo de uso' },
] as const;

export type SenseField = (typeof SENSE_FIELDS)[number]['code'];
export const SENSE_FIELD_CODES = SENSE_FIELDS.map((f) => f.code) as [SenseField, ...SenseField[]];

export const VOCABULARIES = [
  'part_of_speech',
  'gender',
  'number',
  'language',
  'topic',
  'study_level',
  'subject',
  'education_stage',
  'dictionary_type',
] as const;
export type Vocabulary = (typeof VOCABULARIES)[number];

export const VOCABULARY_LABELS: Record<Vocabulary, string> = {
  part_of_speech: 'Categoría gramatical',
  gender: 'Género',
  number: 'Número',
  language: 'Lenguas',
  topic: 'Temáticas',
  study_level: 'Niveles de estudio',
  subject: 'Áreas y materias',
  education_stage: 'Enseñanzas',
  dictionary_type: 'Tipos de diccionario',
};

export const MEDIA_KINDS = ['image', 'audio', 'video'] as const;
export type MediaKind = (typeof MEDIA_KINDS)[number];

/**
 * Allowed upload types by content sniffing (brief §5.17), as stored (canonical, without parameters). Audio covers what
 * phones record and browsers play: MP3, M4A/AAC (Android and iOS recorders), OGG Vorbis/Opus (voice notes), WAV and
 * WebM/Opus (MediaRecorder in Chrome).
 */
export const MEDIA_MIME: Record<MediaKind, readonly string[]> = {
  image: ['image/png', 'image/jpeg', 'image/webp', 'image/gif'],
  audio: ['audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/webm'],
  video: ['video/mp4', 'video/webm'],
};

/** File picker filter: the MIME types plus extensions, since phones report some audio types inconsistently. */
export const MEDIA_ACCEPT: Record<MediaKind, string> = {
  image: [...MEDIA_MIME.image, '.png', '.jpg', '.jpeg', '.webp', '.gif'].join(','),
  audio: [
    ...MEDIA_MIME.audio,
    'audio/x-m4a',
    'audio/aac',
    '.mp3',
    '.m4a',
    '.ogg',
    '.oga',
    '.opus',
    '.wav',
    '.webm',
  ].join(','),
  video: [...MEDIA_MIME.video, '.mp4', '.webm'].join(','),
};

export const mediaKindOf = (mime: string): MediaKind | null =>
  (Object.keys(MEDIA_MIME) as MediaKind[]).find((k) => MEDIA_MIME[k].includes(mime)) ?? null;
