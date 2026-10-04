import { sql } from 'drizzle-orm';
import {
  boolean,
  customType,
  index,
  integer,
  jsonb,
  pgEnum,
  pgTable,
  primaryKey,
  smallint,
  text,
  timestamp,
  uniqueIndex,
  uuid,
  type AnyPgColumn,
} from 'drizzle-orm/pg-core';
import type { EntrySnapshot } from '@lexican/core';

/**
 * LexiCán data model (docs/DATA-MODEL.md). One schema for PostgreSQL and PGlite.
 * Legacy traceability: `legacy_source` + `legacy_id` per migrated row (unique together), never a primary key.
 */

const bytea = customType<{ data: Uint8Array; driverData: Uint8Array }>({ dataType: () => 'bytea' });
const ts = (name: string) => timestamp(name, { withTimezone: true });
const created = () => ts('created_at').notNull().defaultNow();
const updated = () => ts('updated_at').notNull().defaultNow();
const legacy = () => ({ legacySource: text('legacy_source'), legacyId: integer('legacy_id') });
const legacyUnique = (name: string, t: { legacySource: AnyPgColumn; legacyId: AnyPgColumn }) =>
  uniqueIndex(name).on(t.legacySource, t.legacyId);

export const globalRole = pgEnum('global_role', ['student', 'teacher', 'admin', 'support']);
export const userStatus = pgEnum('user_status', ['active', 'disabled']);
export const authProvider = pgEnum('auth_provider', ['cas', 'password']);
export const dictionaryKind = pgEnum('dictionary_kind', ['personal', 'classroom']);
export const memberRole = pgEnum('member_role', ['teacher', 'student']);
export const vocabulary = pgEnum('vocabulary', [
  'part_of_speech',
  'gender',
  'number',
  'language',
  'topic',
  'study_level',
  'subject',
  'education_stage',
  'dictionary_type',
]);
export const commentVisibility = pgEnum('comment_visibility', ['hidden', 'visible', 'before_date']);
export const mediaKind = pgEnum('media_kind', ['image', 'audio', 'video']);
export const submissionStatus = pgEnum('submission_status', [
  'pending',
  'published',
  'rejected',
  'withdrawn',
]);
export const revisionReason = pgEnum('revision_reason', ['submit', 'publish', 'teacher_edit']);

export const users = pgTable(
  'users',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    email: text('email'),
    displayName: text('display_name').notNull(),
    firstName: text('first_name').notNull().default(''),
    lastName: text('last_name').notNull().default(''),
    avatar: text('avatar').notNull().default('default'),
    globalRole: globalRole('global_role').notNull().default('student'),
    status: userStatus('status').notNull().default('active'),
    createdAt: created(),
    updatedAt: updated(),
    ...legacy(),
  },
  (t) => [
    uniqueIndex('users_email_key').on(sql`lower(${t.email})`),
    legacyUnique('users_legacy_key', t),
  ],
);

export const authIdentities = pgTable(
  'auth_identities',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    provider: authProvider('provider').notNull(),
    subject: text('subject').notNull(),
    /** PBKDF2 hash for the password provider (demo/dev only); null for CAS. */
    secretHash: text('secret_hash'),
    lastLoginAt: ts('last_login_at'),
    createdAt: created(),
  },
  (t) => [uniqueIndex('auth_identities_provider_subject_key').on(t.provider, t.subject)],
);

export const sessions = pgTable(
  'sessions',
  {
    idHash: text('id_hash').primaryKey(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    casTicket: text('cas_ticket'),
    createdAt: created(),
    lastSeenAt: ts('last_seen_at').notNull().defaultNow(),
    expiresAt: ts('expires_at').notNull(),
  },
  (t) => [
    index('sessions_user_idx').on(t.userId),
    index('sessions_cas_ticket_idx').on(t.casTicket),
  ],
);

export const schools = pgTable(
  'schools',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    code: text('code').notNull().unique(),
    name: text('name').notNull(),
    ...legacy(),
  },
  (t) => [legacyUnique('schools_legacy_key', t)],
);

export const userSchools = pgTable(
  'user_schools',
  {
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    schoolId: uuid('school_id')
      .notNull()
      .references(() => schools.id, { onDelete: 'cascade' }),
    roleCode: text('role_code'),
  },
  (t) => [primaryKey({ columns: [t.userId, t.schoolId] })],
);

export const vocabularyValues = pgTable(
  'vocabulary_values',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    vocabulary: vocabulary('vocabulary').notNull(),
    code: text('code').notNull(),
    label: text('label').notNull(),
    abbreviation: text('abbreviation'),
    position: integer('position').notNull().default(0),
    featured: boolean('featured').notNull().default(false),
    active: boolean('active').notNull().default(true),
    ...legacy(),
  },
  (t) => [
    uniqueIndex('vocabulary_values_code_key').on(t.vocabulary, t.code),
    legacyUnique('vocabulary_values_legacy_key', t),
  ],
);

export const dictionaries = pgTable(
  'dictionaries',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    kind: dictionaryKind('kind').notNull(),
    title: text('title').notNull(),
    description: text('description').notNull().default(''),
    avatar: text('avatar'),
    ownerId: uuid('owner_id')
      .notNull()
      .references(() => users.id),
    createdAt: created(),
    updatedAt: updated(),
    deletedAt: ts('deleted_at'),
    ...legacy(),
  },
  (t) => [
    uniqueIndex('dictionaries_one_personal_key')
      .on(t.ownerId)
      .where(sql`${t.kind} = 'personal' and ${t.deletedAt} is null`),
    legacyUnique('dictionaries_legacy_key', t),
  ],
);

export const classroomSettings = pgTable('classroom_settings', {
  dictionaryId: uuid('dictionary_id')
    .primaryKey()
    .references(() => dictionaries.id, { onDelete: 'cascade' }),
  joinCode: text('join_code').notNull().unique(),
  schoolYear: smallint('school_year').notNull(),
  validityYears: smallint('validity_years').notNull().default(1),
  timeless: boolean('timeless').notNull().default(false),
  dictionaryTypeId: uuid('dictionary_type_id').references(() => vocabularyValues.id),
  educationStageId: uuid('education_stage_id').references(() => vocabularyValues.id),
  studyLevelId: uuid('study_level_id').references(() => vocabularyValues.id),
  subjectId: uuid('subject_id').references(() => vocabularyValues.id),
  groupLabel: text('group_label').notNull().default(''),
  maxSenses: smallint('max_senses'),
  visibleFields: text('visible_fields').array().notNull(),
  requiredFields: text('required_fields')
    .array()
    .notNull()
    .default(sql`'{}'::text[]`),
  guidelines: text('guidelines').notNull().default(''),
  visibleToStudents: boolean('visible_to_students').notNull().default(true),
  submissionsEnabled: boolean('submissions_enabled').notNull().default(true),
  submissionsStartAt: ts('submissions_start_at'),
  submissionsEndAt: ts('submissions_end_at'),
  commentsVisibility: commentVisibility('comments_visibility').notNull().default('visible'),
  commentsVisibleBefore: ts('comments_visible_before'),
});

export const dictionaryMemberships = pgTable(
  'dictionary_memberships',
  {
    dictionaryId: uuid('dictionary_id')
      .notNull()
      .references(() => dictionaries.id, { onDelete: 'cascade' }),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    role: memberRole('role').notNull(),
    active: boolean('active').notNull().default(true),
    createdAt: created(),
    updatedAt: updated(),
    ...legacy(),
  },
  (t) => [
    primaryKey({ columns: [t.dictionaryId, t.userId] }),
    index('memberships_user_idx').on(t.userId),
  ],
);

export const entries = pgTable(
  'entries',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    dictionaryId: uuid('dictionary_id')
      .notNull()
      .references(() => dictionaries.id, { onDelete: 'cascade' }),
    headword: text('headword').notNull(),
    headwordKey: text('headword_key').notNull(),
    initial: text('initial').notNull(),
    /** Spanish alphabetical order independent of the database collation (PGlite and Postgres agree). */
    sortKey: text('sort_key').notNull(),
    hidden: boolean('hidden').notNull().default(false),
    sourceSubmissionId: uuid('source_submission_id').references((): AnyPgColumn => submissions.id),
    version: integer('version').notNull().default(1),
    createdBy: uuid('created_by').references(() => users.id),
    updatedBy: uuid('updated_by').references(() => users.id),
    createdAt: created(),
    updatedAt: updated(),
    deletedAt: ts('deleted_at'),
    ...legacy(),
  },
  (t) => [
    uniqueIndex('entries_headword_key')
      .on(t.dictionaryId, t.headwordKey)
      .where(sql`${t.deletedAt} is null`),
    index('entries_initial_idx').on(t.dictionaryId, t.initial),
    index('entries_sort_idx').on(t.dictionaryId, t.sortKey),
    legacyUnique('entries_legacy_key', t),
  ],
);

export const entrySenses = pgTable(
  'entry_senses',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    entryId: uuid('entry_id')
      .notNull()
      .references(() => entries.id, { onDelete: 'cascade' }),
    position: smallint('position').notNull(),
    definition: text('definition').notNull(),
    extraInfo: text('extra_info').notNull().default(''),
    example: text('example').notNull().default(''),
    partOfSpeechId: uuid('part_of_speech_id').references(() => vocabularyValues.id),
    genderId: uuid('gender_id').references(() => vocabularyValues.id),
    numberId: uuid('number_id').references(() => vocabularyValues.id),
    languageId: uuid('language_id').references(() => vocabularyValues.id),
    foreignForm: text('foreign_form').notNull().default(''),
    hidden: boolean('hidden').notNull().default(false),
    createdAt: created(),
    updatedAt: updated(),
    ...legacy(),
  },
  (t) => [
    index('entry_senses_entry_idx').on(t.entryId, t.position),
    legacyUnique('entry_senses_legacy_key', t),
  ],
);

export const senseTopics = pgTable(
  'sense_topics',
  {
    senseId: uuid('sense_id')
      .notNull()
      .references(() => entrySenses.id, { onDelete: 'cascade' }),
    topicId: uuid('topic_id')
      .notNull()
      .references(() => vocabularyValues.id),
  },
  (t) => [
    primaryKey({ columns: [t.senseId, t.topicId] }),
    index('sense_topics_topic_idx').on(t.topicId),
  ],
);

export const mediaAssets = pgTable(
  'media_assets',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    kind: mediaKind('kind').notNull(),
    mime: text('mime').notNull(),
    byteSize: integer('byte_size').notNull(),
    sha256: text('sha256').notNull(),
    /** Opaque key inside the MediaStorage adapter (never a client-supplied name). */
    storageKey: text('storage_key').notNull().unique(),
    originalName: text('original_name').notNull().default(''),
    createdBy: uuid('created_by').references(() => users.id),
    createdAt: created(),
    ...legacy(),
  },
  (t) => [index('media_assets_sha_idx').on(t.sha256), legacyUnique('media_assets_legacy_key', t)],
);

/** Bytes for the in-browser demo storage adapter only; production stores files on disk. */
export const mediaBlobs = pgTable('media_blobs', {
  storageKey: text('storage_key').primaryKey(),
  data: bytea('data').notNull(),
});

export const senseMedia = pgTable(
  'sense_media',
  {
    senseId: uuid('sense_id')
      .notNull()
      .references(() => entrySenses.id, { onDelete: 'cascade' }),
    mediaId: uuid('media_id')
      .notNull()
      .references(() => mediaAssets.id),
    kind: mediaKind('kind').notNull(),
  },
  (t) => [
    primaryKey({ columns: [t.senseId, t.mediaId] }),
    uniqueIndex('sense_media_kind_key').on(t.senseId, t.kind),
  ],
);

export const entryRevisions = pgTable(
  'entry_revisions',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    entryId: uuid('entry_id')
      .notNull()
      .references(() => entries.id, { onDelete: 'cascade' }),
    number: integer('number').notNull(),
    reason: revisionReason('reason').notNull(),
    snapshot: jsonb('snapshot').$type<EntrySnapshot>().notNull(),
    createdBy: uuid('created_by').references(() => users.id),
    createdAt: created(),
  },
  (t) => [uniqueIndex('entry_revisions_number_key').on(t.entryId, t.number)],
);

export const submissions = pgTable(
  'submissions',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    classroomId: uuid('classroom_id')
      .notNull()
      .references(() => dictionaries.id, { onDelete: 'cascade' }),
    sourceEntryId: uuid('source_entry_id')
      .notNull()
      .references(() => entries.id, { onDelete: 'cascade' }),
    submittedBy: uuid('submitted_by')
      .notNull()
      .references(() => users.id),
    revisionId: uuid('revision_id')
      .notNull()
      .references(() => entryRevisions.id),
    status: submissionStatus('status').notNull().default('pending'),
    submittedAt: ts('submitted_at').notNull().defaultNow(),
    reviewedAt: ts('reviewed_at'),
    reviewedBy: uuid('reviewed_by').references(() => users.id),
    reviewNote: text('review_note').notNull().default(''),
    publishedEntryId: uuid('published_entry_id').references((): AnyPgColumn => entries.id),
    ...legacy(),
  },
  (t) => [
    uniqueIndex('submissions_one_pending_key')
      .on(t.sourceEntryId, t.classroomId)
      .where(sql`${t.status} = 'pending'`),
    index('submissions_classroom_idx').on(t.classroomId, t.status),
    legacyUnique('submissions_legacy_key', t),
  ],
);

export const comments = pgTable(
  'comments',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    classroomId: uuid('classroom_id')
      .notNull()
      .references(() => dictionaries.id, { onDelete: 'cascade' }),
    authorId: uuid('author_id')
      .notNull()
      .references(() => users.id),
    studentId: uuid('student_id')
      .notNull()
      .references(() => users.id),
    submissionId: uuid('submission_id').references(() => submissions.id, { onDelete: 'set null' }),
    body: text('body').notNull(),
    createdAt: created(),
    deletedAt: ts('deleted_at'),
    ...legacy(),
  },
  (t) => [
    index('comments_classroom_student_idx').on(t.classroomId, t.studentId),
    legacyUnique('comments_legacy_key', t),
  ],
);

export const auditEvents = pgTable(
  'audit_events',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    actorId: uuid('actor_id').references(() => users.id, { onDelete: 'set null' }),
    action: text('action').notNull(),
    entityType: text('entity_type').notNull(),
    entityId: uuid('entity_id'),
    metadata: jsonb('metadata')
      .$type<Record<string, string | number | boolean | null>>()
      .notNull()
      .default({}),
    createdAt: created(),
  },
  (t) => [index('audit_events_created_idx').on(t.createdAt)],
);

/** Small key/value settings (master guidelines, demo seed version). */
export const appSettings = pgTable('app_settings', {
  key: text('key').primaryKey(),
  value: jsonb('value').$type<unknown>().notNull(),
});
