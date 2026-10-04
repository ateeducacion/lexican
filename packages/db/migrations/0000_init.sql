CREATE TYPE "public"."auth_provider" AS ENUM('cas', 'password');--> statement-breakpoint
CREATE TYPE "public"."comment_visibility" AS ENUM('hidden', 'visible', 'before_date');--> statement-breakpoint
CREATE TYPE "public"."dictionary_kind" AS ENUM('personal', 'classroom');--> statement-breakpoint
CREATE TYPE "public"."global_role" AS ENUM('student', 'teacher', 'admin', 'support');--> statement-breakpoint
CREATE TYPE "public"."media_kind" AS ENUM('image', 'audio', 'video');--> statement-breakpoint
CREATE TYPE "public"."member_role" AS ENUM('teacher', 'student');--> statement-breakpoint
CREATE TYPE "public"."revision_reason" AS ENUM('submit', 'publish', 'teacher_edit');--> statement-breakpoint
CREATE TYPE "public"."submission_status" AS ENUM('pending', 'published', 'rejected', 'withdrawn');--> statement-breakpoint
CREATE TYPE "public"."user_status" AS ENUM('active', 'disabled');--> statement-breakpoint
CREATE TYPE "public"."vocabulary" AS ENUM('part_of_speech', 'gender', 'number', 'language', 'topic', 'study_level', 'subject', 'education_stage', 'dictionary_type');--> statement-breakpoint
CREATE TABLE "app_settings" (
	"key" text PRIMARY KEY NOT NULL,
	"value" jsonb NOT NULL
);
--> statement-breakpoint
CREATE TABLE "audit_events" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"actor_id" uuid,
	"action" text NOT NULL,
	"entity_type" text NOT NULL,
	"entity_id" uuid,
	"metadata" jsonb DEFAULT '{}'::jsonb NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "auth_identities" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"user_id" uuid NOT NULL,
	"provider" "auth_provider" NOT NULL,
	"subject" text NOT NULL,
	"secret_hash" text,
	"last_login_at" timestamp with time zone,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "classroom_settings" (
	"dictionary_id" uuid PRIMARY KEY NOT NULL,
	"join_code" text NOT NULL,
	"school_year" smallint NOT NULL,
	"validity_years" smallint DEFAULT 1 NOT NULL,
	"timeless" boolean DEFAULT false NOT NULL,
	"dictionary_type_id" uuid,
	"education_stage_id" uuid,
	"study_level_id" uuid,
	"subject_id" uuid,
	"group_label" text DEFAULT '' NOT NULL,
	"max_senses" smallint,
	"visible_fields" text[] NOT NULL,
	"required_fields" text[] DEFAULT '{}'::text[] NOT NULL,
	"guidelines" text DEFAULT '' NOT NULL,
	"visible_to_students" boolean DEFAULT true NOT NULL,
	"submissions_enabled" boolean DEFAULT true NOT NULL,
	"submissions_start_at" timestamp with time zone,
	"submissions_end_at" timestamp with time zone,
	"comments_visibility" "comment_visibility" DEFAULT 'visible' NOT NULL,
	"comments_visible_before" timestamp with time zone,
	CONSTRAINT "classroom_settings_join_code_unique" UNIQUE("join_code")
);
--> statement-breakpoint
CREATE TABLE "comments" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"classroom_id" uuid NOT NULL,
	"author_id" uuid NOT NULL,
	"student_id" uuid NOT NULL,
	"submission_id" uuid,
	"body" text NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"deleted_at" timestamp with time zone,
	"legacy_source" text,
	"legacy_id" integer
);
--> statement-breakpoint
CREATE TABLE "dictionaries" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"kind" "dictionary_kind" NOT NULL,
	"title" text NOT NULL,
	"description" text DEFAULT '' NOT NULL,
	"avatar" text,
	"owner_id" uuid NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	"deleted_at" timestamp with time zone,
	"legacy_source" text,
	"legacy_id" integer
);
--> statement-breakpoint
CREATE TABLE "dictionary_memberships" (
	"dictionary_id" uuid NOT NULL,
	"user_id" uuid NOT NULL,
	"role" "member_role" NOT NULL,
	"active" boolean DEFAULT true NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	"legacy_source" text,
	"legacy_id" integer,
	CONSTRAINT "dictionary_memberships_dictionary_id_user_id_pk" PRIMARY KEY("dictionary_id","user_id")
);
--> statement-breakpoint
CREATE TABLE "entries" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"dictionary_id" uuid NOT NULL,
	"headword" text NOT NULL,
	"headword_key" text NOT NULL,
	"initial" text NOT NULL,
	"sort_key" text NOT NULL,
	"hidden" boolean DEFAULT false NOT NULL,
	"source_submission_id" uuid,
	"version" integer DEFAULT 1 NOT NULL,
	"created_by" uuid,
	"updated_by" uuid,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	"deleted_at" timestamp with time zone,
	"legacy_source" text,
	"legacy_id" integer
);
--> statement-breakpoint
CREATE TABLE "entry_revisions" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"entry_id" uuid NOT NULL,
	"number" integer NOT NULL,
	"reason" "revision_reason" NOT NULL,
	"snapshot" jsonb NOT NULL,
	"created_by" uuid,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "entry_senses" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"entry_id" uuid NOT NULL,
	"position" smallint NOT NULL,
	"definition" text NOT NULL,
	"extra_info" text DEFAULT '' NOT NULL,
	"example" text DEFAULT '' NOT NULL,
	"part_of_speech_id" uuid,
	"gender_id" uuid,
	"number_id" uuid,
	"language_id" uuid,
	"foreign_form" text DEFAULT '' NOT NULL,
	"hidden" boolean DEFAULT false NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	"legacy_source" text,
	"legacy_id" integer
);
--> statement-breakpoint
CREATE TABLE "media_assets" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"kind" "media_kind" NOT NULL,
	"mime" text NOT NULL,
	"byte_size" integer NOT NULL,
	"sha256" text NOT NULL,
	"storage_key" text NOT NULL,
	"original_name" text DEFAULT '' NOT NULL,
	"created_by" uuid,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"legacy_source" text,
	"legacy_id" integer,
	CONSTRAINT "media_assets_storage_key_unique" UNIQUE("storage_key")
);
--> statement-breakpoint
CREATE TABLE "media_blobs" (
	"storage_key" text PRIMARY KEY NOT NULL,
	"data" "bytea" NOT NULL
);
--> statement-breakpoint
CREATE TABLE "schools" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"code" text NOT NULL,
	"name" text NOT NULL,
	"legacy_source" text,
	"legacy_id" integer,
	CONSTRAINT "schools_code_unique" UNIQUE("code")
);
--> statement-breakpoint
CREATE TABLE "sense_media" (
	"sense_id" uuid NOT NULL,
	"media_id" uuid NOT NULL,
	"kind" "media_kind" NOT NULL,
	CONSTRAINT "sense_media_sense_id_media_id_pk" PRIMARY KEY("sense_id","media_id")
);
--> statement-breakpoint
CREATE TABLE "sense_topics" (
	"sense_id" uuid NOT NULL,
	"topic_id" uuid NOT NULL,
	CONSTRAINT "sense_topics_sense_id_topic_id_pk" PRIMARY KEY("sense_id","topic_id")
);
--> statement-breakpoint
CREATE TABLE "sessions" (
	"id_hash" text PRIMARY KEY NOT NULL,
	"user_id" uuid NOT NULL,
	"cas_ticket" text,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"last_seen_at" timestamp with time zone DEFAULT now() NOT NULL,
	"expires_at" timestamp with time zone NOT NULL
);
--> statement-breakpoint
CREATE TABLE "submissions" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"classroom_id" uuid NOT NULL,
	"source_entry_id" uuid NOT NULL,
	"submitted_by" uuid NOT NULL,
	"revision_id" uuid NOT NULL,
	"status" "submission_status" DEFAULT 'pending' NOT NULL,
	"submitted_at" timestamp with time zone DEFAULT now() NOT NULL,
	"reviewed_at" timestamp with time zone,
	"reviewed_by" uuid,
	"review_note" text DEFAULT '' NOT NULL,
	"published_entry_id" uuid,
	"legacy_source" text,
	"legacy_id" integer
);
--> statement-breakpoint
CREATE TABLE "user_schools" (
	"user_id" uuid NOT NULL,
	"school_id" uuid NOT NULL,
	"role_code" text,
	CONSTRAINT "user_schools_user_id_school_id_pk" PRIMARY KEY("user_id","school_id")
);
--> statement-breakpoint
CREATE TABLE "users" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"email" text,
	"display_name" text NOT NULL,
	"first_name" text DEFAULT '' NOT NULL,
	"last_name" text DEFAULT '' NOT NULL,
	"avatar" text DEFAULT 'default' NOT NULL,
	"global_role" "global_role" DEFAULT 'student' NOT NULL,
	"status" "user_status" DEFAULT 'active' NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	"legacy_source" text,
	"legacy_id" integer
);
--> statement-breakpoint
CREATE TABLE "vocabulary_values" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"vocabulary" "vocabulary" NOT NULL,
	"code" text NOT NULL,
	"label" text NOT NULL,
	"abbreviation" text,
	"position" integer DEFAULT 0 NOT NULL,
	"featured" boolean DEFAULT false NOT NULL,
	"active" boolean DEFAULT true NOT NULL,
	"legacy_source" text,
	"legacy_id" integer
);
--> statement-breakpoint
ALTER TABLE "audit_events" ADD CONSTRAINT "audit_events_actor_id_users_id_fk" FOREIGN KEY ("actor_id") REFERENCES "public"."users"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "auth_identities" ADD CONSTRAINT "auth_identities_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "classroom_settings" ADD CONSTRAINT "classroom_settings_dictionary_id_dictionaries_id_fk" FOREIGN KEY ("dictionary_id") REFERENCES "public"."dictionaries"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "classroom_settings" ADD CONSTRAINT "classroom_settings_dictionary_type_id_vocabulary_values_id_fk" FOREIGN KEY ("dictionary_type_id") REFERENCES "public"."vocabulary_values"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "classroom_settings" ADD CONSTRAINT "classroom_settings_education_stage_id_vocabulary_values_id_fk" FOREIGN KEY ("education_stage_id") REFERENCES "public"."vocabulary_values"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "classroom_settings" ADD CONSTRAINT "classroom_settings_study_level_id_vocabulary_values_id_fk" FOREIGN KEY ("study_level_id") REFERENCES "public"."vocabulary_values"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "classroom_settings" ADD CONSTRAINT "classroom_settings_subject_id_vocabulary_values_id_fk" FOREIGN KEY ("subject_id") REFERENCES "public"."vocabulary_values"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "comments" ADD CONSTRAINT "comments_classroom_id_dictionaries_id_fk" FOREIGN KEY ("classroom_id") REFERENCES "public"."dictionaries"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "comments" ADD CONSTRAINT "comments_author_id_users_id_fk" FOREIGN KEY ("author_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "comments" ADD CONSTRAINT "comments_student_id_users_id_fk" FOREIGN KEY ("student_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "comments" ADD CONSTRAINT "comments_submission_id_submissions_id_fk" FOREIGN KEY ("submission_id") REFERENCES "public"."submissions"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "dictionaries" ADD CONSTRAINT "dictionaries_owner_id_users_id_fk" FOREIGN KEY ("owner_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "dictionary_memberships" ADD CONSTRAINT "dictionary_memberships_dictionary_id_dictionaries_id_fk" FOREIGN KEY ("dictionary_id") REFERENCES "public"."dictionaries"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "dictionary_memberships" ADD CONSTRAINT "dictionary_memberships_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "entries" ADD CONSTRAINT "entries_dictionary_id_dictionaries_id_fk" FOREIGN KEY ("dictionary_id") REFERENCES "public"."dictionaries"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "entries" ADD CONSTRAINT "entries_source_submission_id_submissions_id_fk" FOREIGN KEY ("source_submission_id") REFERENCES "public"."submissions"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "entries" ADD CONSTRAINT "entries_created_by_users_id_fk" FOREIGN KEY ("created_by") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "entries" ADD CONSTRAINT "entries_updated_by_users_id_fk" FOREIGN KEY ("updated_by") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "entry_revisions" ADD CONSTRAINT "entry_revisions_entry_id_entries_id_fk" FOREIGN KEY ("entry_id") REFERENCES "public"."entries"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "entry_revisions" ADD CONSTRAINT "entry_revisions_created_by_users_id_fk" FOREIGN KEY ("created_by") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "entry_senses" ADD CONSTRAINT "entry_senses_entry_id_entries_id_fk" FOREIGN KEY ("entry_id") REFERENCES "public"."entries"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "entry_senses" ADD CONSTRAINT "entry_senses_part_of_speech_id_vocabulary_values_id_fk" FOREIGN KEY ("part_of_speech_id") REFERENCES "public"."vocabulary_values"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "entry_senses" ADD CONSTRAINT "entry_senses_gender_id_vocabulary_values_id_fk" FOREIGN KEY ("gender_id") REFERENCES "public"."vocabulary_values"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "entry_senses" ADD CONSTRAINT "entry_senses_number_id_vocabulary_values_id_fk" FOREIGN KEY ("number_id") REFERENCES "public"."vocabulary_values"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "entry_senses" ADD CONSTRAINT "entry_senses_language_id_vocabulary_values_id_fk" FOREIGN KEY ("language_id") REFERENCES "public"."vocabulary_values"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "media_assets" ADD CONSTRAINT "media_assets_created_by_users_id_fk" FOREIGN KEY ("created_by") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "sense_media" ADD CONSTRAINT "sense_media_sense_id_entry_senses_id_fk" FOREIGN KEY ("sense_id") REFERENCES "public"."entry_senses"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "sense_media" ADD CONSTRAINT "sense_media_media_id_media_assets_id_fk" FOREIGN KEY ("media_id") REFERENCES "public"."media_assets"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "sense_topics" ADD CONSTRAINT "sense_topics_sense_id_entry_senses_id_fk" FOREIGN KEY ("sense_id") REFERENCES "public"."entry_senses"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "sense_topics" ADD CONSTRAINT "sense_topics_topic_id_vocabulary_values_id_fk" FOREIGN KEY ("topic_id") REFERENCES "public"."vocabulary_values"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "sessions" ADD CONSTRAINT "sessions_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "submissions" ADD CONSTRAINT "submissions_classroom_id_dictionaries_id_fk" FOREIGN KEY ("classroom_id") REFERENCES "public"."dictionaries"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "submissions" ADD CONSTRAINT "submissions_source_entry_id_entries_id_fk" FOREIGN KEY ("source_entry_id") REFERENCES "public"."entries"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "submissions" ADD CONSTRAINT "submissions_submitted_by_users_id_fk" FOREIGN KEY ("submitted_by") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "submissions" ADD CONSTRAINT "submissions_revision_id_entry_revisions_id_fk" FOREIGN KEY ("revision_id") REFERENCES "public"."entry_revisions"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "submissions" ADD CONSTRAINT "submissions_reviewed_by_users_id_fk" FOREIGN KEY ("reviewed_by") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "submissions" ADD CONSTRAINT "submissions_published_entry_id_entries_id_fk" FOREIGN KEY ("published_entry_id") REFERENCES "public"."entries"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "user_schools" ADD CONSTRAINT "user_schools_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "user_schools" ADD CONSTRAINT "user_schools_school_id_schools_id_fk" FOREIGN KEY ("school_id") REFERENCES "public"."schools"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
CREATE INDEX "audit_events_created_idx" ON "audit_events" USING btree ("created_at");--> statement-breakpoint
CREATE UNIQUE INDEX "auth_identities_provider_subject_key" ON "auth_identities" USING btree ("provider","subject");--> statement-breakpoint
CREATE INDEX "comments_classroom_student_idx" ON "comments" USING btree ("classroom_id","student_id");--> statement-breakpoint
CREATE UNIQUE INDEX "comments_legacy_key" ON "comments" USING btree ("legacy_source","legacy_id");--> statement-breakpoint
CREATE UNIQUE INDEX "dictionaries_one_personal_key" ON "dictionaries" USING btree ("owner_id") WHERE "dictionaries"."kind" = 'personal' and "dictionaries"."deleted_at" is null;--> statement-breakpoint
CREATE UNIQUE INDEX "dictionaries_legacy_key" ON "dictionaries" USING btree ("legacy_source","legacy_id");--> statement-breakpoint
CREATE INDEX "memberships_user_idx" ON "dictionary_memberships" USING btree ("user_id");--> statement-breakpoint
CREATE UNIQUE INDEX "entries_headword_key" ON "entries" USING btree ("dictionary_id","headword_key") WHERE "entries"."deleted_at" is null;--> statement-breakpoint
CREATE INDEX "entries_initial_idx" ON "entries" USING btree ("dictionary_id","initial");--> statement-breakpoint
CREATE INDEX "entries_sort_idx" ON "entries" USING btree ("dictionary_id","sort_key");--> statement-breakpoint
CREATE UNIQUE INDEX "entries_legacy_key" ON "entries" USING btree ("legacy_source","legacy_id");--> statement-breakpoint
CREATE UNIQUE INDEX "entry_revisions_number_key" ON "entry_revisions" USING btree ("entry_id","number");--> statement-breakpoint
CREATE INDEX "entry_senses_entry_idx" ON "entry_senses" USING btree ("entry_id","position");--> statement-breakpoint
CREATE UNIQUE INDEX "entry_senses_legacy_key" ON "entry_senses" USING btree ("legacy_source","legacy_id");--> statement-breakpoint
CREATE INDEX "media_assets_sha_idx" ON "media_assets" USING btree ("sha256");--> statement-breakpoint
CREATE UNIQUE INDEX "media_assets_legacy_key" ON "media_assets" USING btree ("legacy_source","legacy_id");--> statement-breakpoint
CREATE UNIQUE INDEX "schools_legacy_key" ON "schools" USING btree ("legacy_source","legacy_id");--> statement-breakpoint
CREATE UNIQUE INDEX "sense_media_kind_key" ON "sense_media" USING btree ("sense_id","kind");--> statement-breakpoint
CREATE INDEX "sense_topics_topic_idx" ON "sense_topics" USING btree ("topic_id");--> statement-breakpoint
CREATE INDEX "sessions_user_idx" ON "sessions" USING btree ("user_id");--> statement-breakpoint
CREATE INDEX "sessions_cas_ticket_idx" ON "sessions" USING btree ("cas_ticket");--> statement-breakpoint
CREATE UNIQUE INDEX "submissions_one_pending_key" ON "submissions" USING btree ("source_entry_id","classroom_id") WHERE "submissions"."status" = 'pending';--> statement-breakpoint
CREATE INDEX "submissions_classroom_idx" ON "submissions" USING btree ("classroom_id","status");--> statement-breakpoint
CREATE UNIQUE INDEX "submissions_legacy_key" ON "submissions" USING btree ("legacy_source","legacy_id");--> statement-breakpoint
CREATE UNIQUE INDEX "users_email_key" ON "users" USING btree (lower("email"));--> statement-breakpoint
CREATE UNIQUE INDEX "users_legacy_key" ON "users" USING btree ("legacy_source","legacy_id");--> statement-breakpoint
CREATE UNIQUE INDEX "vocabulary_values_code_key" ON "vocabulary_values" USING btree ("vocabulary","code");--> statement-breakpoint
CREATE UNIQUE INDEX "vocabulary_values_legacy_key" ON "vocabulary_values" USING btree ("legacy_source","legacy_id");