# Intent — lexican

- Date: 2026-10-04
- Goal: **reimagine** (rebuild on a new architecture, legacy keeps running until cutover)
- Answer (word for word): "/goal have the modernized app in github pages the repo is public"
- From: PHP 8 / Laravel 8 / Voyager / Blade + jQuery + Bootstrap 4 / MariaDB, CAS + CAUCE auth
- To (fixed by `1er-prompt.md` §8 and §105 — `brief` must not override it):
  TypeScript strict, Node.js LTS, npm workspaces, React + Vite, Fastify, PostgreSQL (only prod DB),
  Drizzle ORM, PGlite + IndexedDB for the GitHub Pages demo, Vitest, Playwright, GitHub Actions.
- What must stay true (from `1er-prompt.md`, not a pop-up):
  - The old system keeps running during the move; rollback = point back to legacy (§73).
  - Preserve useful functionality, not dead code or quirks (§1, §6) — fix known bugs as we go.
  - `origin/upstream` is a historic snapshot: never commit, rebase or force-push it (§5).
  - Public repo: no real data, secrets or internal hosts anywhere (§22, §87).
- Source: `/Users/ernesto/Dropbox/Trabajo/ate/lexican` (in place, `legacy/lexican` symlink)
- References (method, not architecture): `/Users/ernesto/Dropbox/Trabajo/ate/aritmates`, `/Users/ernesto/Dropbox/Trabajo/ate/tonga`
- Full requirements: `1er-prompt.md` (§104 "primer resultado obligatorio" = output of assess/map/extract-rules/brief).
- End state: working demo at https://ateeducacion.github.io/lexican/ deployed from `main` via CI,
  plus Fastify + PostgreSQL production build.
