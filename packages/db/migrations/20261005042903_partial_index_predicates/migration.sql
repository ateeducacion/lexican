DROP INDEX "dictionaries_one_personal_key";--> statement-breakpoint
CREATE UNIQUE INDEX "dictionaries_one_personal_key" ON "dictionaries" ("owner_id") WHERE "kind" = 'personal' and "deleted_at" is null;--> statement-breakpoint
DROP INDEX "entries_headword_key";--> statement-breakpoint
CREATE UNIQUE INDEX "entries_headword_key" ON "entries" ("dictionary_id","headword_key") WHERE "deleted_at" is null;--> statement-breakpoint
DROP INDEX "submissions_one_pending_key";--> statement-breakpoint
CREATE UNIQUE INDEX "submissions_one_pending_key" ON "submissions" ("source_entry_id","classroom_id") WHERE "status" = 'pending';