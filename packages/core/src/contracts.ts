import { z } from 'zod';
import { MEDIA_KINDS, SENSE_FIELD_CODES, VOCABULARIES } from './fields.ts';
import { MAX_VALIDITY_YEARS } from './school-year.ts';

/** Shared validation (API requests, forms, seeds). Authorization never lives here (§42). */

const id = z.uuid({ error: 'Identificador no válido' });
const text = (max: number, label: string) =>
  z
    .string()
    .trim()
    .max(max, { error: `${label}: máximo ${max} caracteres` });
const optionalText = (max: number, label: string) => text(max, label).default('');
const optionalId = id.nullable().default(null);

export const GLOBAL_ROLES = ['student', 'teacher', 'admin', 'support'] as const;
export type GlobalRole = (typeof GLOBAL_ROLES)[number];
export const MEMBER_ROLES = ['teacher', 'student'] as const;
export type MemberRole = (typeof MEMBER_ROLES)[number];
export const SUBMISSION_STATUSES = ['pending', 'published', 'rejected', 'withdrawn'] as const;
export type SubmissionStatus = (typeof SUBMISSION_STATUSES)[number];
export const COMMENT_VISIBILITY = ['hidden', 'visible', 'before_date'] as const;
export type CommentVisibility = (typeof COMMENT_VISIBILITY)[number];

export const LoginInput = z.object({
  email: z.email({ error: 'Escribe un correo electrónico válido' }).trim().toLowerCase(),
  password: z.string().min(1, { error: 'Escribe la contraseña' }),
});

export const SenseInput = z.object({
  id: id.optional(),
  definition: z
    .string()
    .trim()
    .min(1, { error: 'La definición es obligatoria' })
    .max(1000, { error: 'Definición: máximo 1000 caracteres' }),
  extraInfo: optionalText(255, 'Más datos'),
  example: optionalText(255, 'Ejemplo de uso'),
  partOfSpeechId: optionalId,
  genderId: optionalId,
  numberId: optionalId,
  languageId: optionalId,
  foreignForm: optionalText(150, 'Palabra en otra lengua'),
  hidden: z.boolean().default(false),
  topicIds: z.array(id).max(20).default([]),
  mediaIds: z.array(id).max(3).default([]),
});
export type SenseInput = z.infer<typeof SenseInput>;

export const EntryInput = z.object({
  headword: z
    .string()
    .trim()
    .min(1, { error: 'Escribe la palabra' })
    .max(150, { error: 'Entrada: máximo 150 caracteres' }),
  hidden: z.boolean().default(false),
  senses: z.array(SenseInput).min(1, { error: 'Añade al menos una acepción' }).max(50),
});
export type EntryInput = z.infer<typeof EntryInput>;

export const ListEntriesQuery = z.object({
  dictionaryId: id,
  q: z.string().trim().max(150).optional(),
  initial: z.string().length(1).optional(),
  topicId: id.optional(),
  includeHidden: z.coerce.boolean().optional(),
  offset: z.coerce.number().int().min(0).default(0),
  limit: z.coerce.number().int().min(1).max(200).default(50),
});

const classroomFields = {
  title: z
    .string()
    .trim()
    .min(1, { error: 'Escribe el nombre del diccionario' })
    .max(150, { error: 'Nombre: máximo 150 caracteres' }),
  description: optionalText(255, 'Descripción'),
  studyLevelId: optionalId,
  subjectId: optionalId,
  groupLabel: optionalText(20, 'Grupo'),
  validityYears: z.coerce.number().int().min(1).max(MAX_VALIDITY_YEARS).default(1),
  maxSenses: z.coerce.number().int().min(1).max(50).nullable().default(null),
  visibleFields: z.array(z.enum(SENSE_FIELD_CODES)).default([...SENSE_FIELD_CODES]),
  requiredFields: z.array(z.enum(SENSE_FIELD_CODES)).default([]),
  guidelines: optionalText(5000, 'Pautas'),
  visibleToStudents: z.boolean().default(true),
  submissionsEnabled: z.boolean().default(true),
  submissionsStartAt: z.coerce.date().nullable().default(null),
  submissionsEndAt: z.coerce.date().nullable().default(null),
  commentsVisibility: z.enum(COMMENT_VISIBILITY).default('visible'),
  commentsVisibleBefore: z.coerce.date().nullable().default(null),
};

export const ClassroomInput = z
  .object(classroomFields)
  .refine(
    (c) =>
      !c.submissionsStartAt || !c.submissionsEndAt || c.submissionsStartAt <= c.submissionsEndAt,
    {
      error: 'La fecha de fin debe ser posterior a la de inicio',
      path: ['submissionsEndAt'],
    },
  )
  .refine((c) => c.commentsVisibility !== 'before_date' || c.commentsVisibleBefore !== null, {
    error: 'Indica la fecha límite de los comentarios visibles',
    path: ['commentsVisibleBefore'],
  });
export type ClassroomInput = z.infer<typeof ClassroomInput>;

export const JOIN_CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
export const JoinInput = z.object({
  code: z
    .string()
    .trim()
    .toUpperCase()
    .regex(/^[A-Z0-9]{4,6}$/, { error: 'El código tiene entre 4 y 6 letras o números' }),
});

export const CommentInput = z.object({
  classroomId: id,
  studentId: id,
  submissionId: id.nullable().default(null),
  body: z
    .string()
    .trim()
    .min(1, { error: 'Escribe el comentario' })
    .max(2000, { error: 'Comentario: máximo 2000 caracteres' }),
});

export const VocabularyValueInput = z.object({
  vocabulary: z.enum(VOCABULARIES),
  code: z
    .string()
    .trim()
    .regex(/^[a-z0-9_]{1,60}$/, { error: 'Código: minúsculas, números y guion bajo' }),
  label: z.string().trim().min(1).max(150),
  abbreviation: z.string().trim().max(20).nullable().default(null),
  position: z.coerce.number().int().min(0).default(0),
  featured: z.boolean().default(false),
  active: z.boolean().default(true),
});

export const MediaKindSchema = z.enum(MEDIA_KINDS);
export const VocabularySchema = z.enum(VOCABULARIES);
