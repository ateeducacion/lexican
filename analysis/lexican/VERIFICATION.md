# Verification — lexican

Date: 2026-10-04. Dual execution against the legacy is impossible (the legacy no longer installs, PREFLIGHT 3b), so
equivalence is proven against the **Behavior Contract** in `MODERNIZATION_BRIEF.md` §5: each rule is pinned by an
executable test that runs on PGlite **and** PostgreSQL 18 (`services.contract.test.ts`), through the API
(`apps/api/src/app.test.ts`) or in the browser (Playwright `e2e/`). Legacy defects flagged in `BUSINESS_RULES.md` are
fixed, not reproduced (INTENT: fix known bugs as we go).

| # | Contract rule (brief §5) | Legacy rule(s) | Proven by | Verdict |
|---|---|---|---|---|
| 1 | School year starts on configurable day/month | RULE-005 | `core/school-year.test.ts` "RULE-005 …" | ✅ equivalent |
| 2 | Classroom current while elapsed < validity, or timeless | RULE-006, RULE-065 (default) | `school-year.test.ts` "RULE-006 …" | ✅ equivalent (`>` semantics chosen) |
| 3 | Non-current classrooms read-only; reactivation capped at 10 | RULE-007 (cap added) | `school-year.test.ts` "RULE-007 …"; contract "closes submissions … expires" | ✅ fixed |
| 4 | Submission window applies to single and bulk send | RULE-009, -074 (fixed), -230 | `school-year.test.ts` "RULE-009 …"; contract "closes submissions outside the window…" | ✅ fixed |
| 5 | Only active members submit; only classroom teachers review | RULE-072 (fixed), -062 (default) | contract "validates submissions…" (outsider forbidden), "only classroom teachers review…"; API "a student cannot publish (403/404)" | ✅ fixed |
| 6 | Submission is an immutable revision | §27 | contract "validates submissions … immutable revision" (edit after send does not change snapshot) | ✅ new behavior |
| 7 | ≥1 visible sense, required fields, max senses | RULE-076, -042/043 (now enforced), -195 | contract dry-run problem "categoría gramatical"; E2E dry-run problem | ✅ fixed |
| 8 | One open submission per entry/classroom; resubmission refreshes | RULE-229 (default) | contract "resubmitting a published entry updates the published copy" | ✅ default applied |
| 9 | Publication with provenance; same-headword other source → conflict | RULE-057, §28 | contract "only classroom teachers review; publication keeps provenance", "blocks a same-headword publication (RULE-057)" | ✅ equivalent + provenance |
| 10 | Rejection with note (replaces hard delete) | RULE-148 | contract "…allows rejection with a note" | ✅ redesigned |
| 11 | Headword unique, case-insensitive, accent-sensitive; accent-insensitive search | RULE-094 (default), RULE-051 (SQL injection fixed) | `text.test.ts` "RULE-094 …"; contract "creates entries … rejects duplicates", "lists in Spanish order and searches…" | ✅ fixed |
| 12 | Personal delete = soft delete; pending → withdrawn; publications kept | RULE-127, -147 | contract "deleting a personal entry withdraws pending submissions but keeps publications" | ✅ equivalent |
| 13 | Hidden entries/senses invisible to students | RULE-144 (default), -223 | contract "teachers hide senses and entries; students do not see them" | ✅ default applied |
| 14 | Plain-text comments, visibility never/always/before date | RULE-185 (default), RULE-046/186 (XSS removed) | contract "comment visibility follows the classroom setting"; E2E journey (student sees the comment) | ✅ fixed |
| 15 | Server-generated unique join codes; membership rules | RULE-002/048/049, -058 | contract "teachers create classrooms; students join by code…", "enforces membership rules (last teacher, no self-change)" | ✅ fixed |
| 16 | Global role recomputed from CAUCE every login; no national ids | RULE-063, -173 (shared password removed) | `apps/api/src/adapters.test.ts` "maps a teacher and drops national ids"; app.test CAS callback | ✅ fixed |
| 17 | Uploads by content sniffing, server-side limits | RULE-079/080/081, -236 | contract "accepts uploads by content and restricts who can read them"; API upload tests (PNG accepted, script 400, 413) | ✅ fixed |
| 18 | Optimistic locking on edits (409) | §65 | contract "detects concurrent edits with optimistic locking (409)"; API "stale versions are 409" | ✅ new behavior |

End-to-end (§77) across roles: `e2e/journey.spec.ts` (student sends → teacher comments and publishes → student sees
"Publicada" and the comment) passes on Chromium, Firefox and WebKit in the demo build and on Chromium against
web + API + PostgreSQL.

Data equivalence (legacy rows → new model): `tools/legacy-migrator/src/migrate.test.ts` on the MariaDB fixture
(counts, PII excluded, sense order, provenance, idempotent re-run) — see `docs/MIGRATION.md`.

**Verdict: all 18 contract rules have an executing test; no rule is left pending.** Open items that need people, not
code: real CAS/CAUCE in pre-production, migration rehearsal on authorized data, rotation of exposed legacy
credentials (brief §7).
