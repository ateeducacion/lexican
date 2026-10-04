# Preflight — lexican

- Date: 2026-10-04
- `legacy/lexican` → `/Users/ernesto/Dropbox/Trabajo/ate/lexican` (in-place; the repo itself)
- Target stack: TypeScript + React/Vite + Fastify + PostgreSQL/PGlite (fixed by `1er-prompt.md` §8, §105)

## Answers

The user asked not to be asked ("si, no me preguntes, termina todo"). Answers below come from `1er-prompt.md`
where it is explicit; everything else is an **open item the human must fill in**.

1. **Scope** — Whole system? From `1er-prompt.md`: the repository is the complete LexiCán application. External
   systems are CAS and CAUCE (Gobierno de Canarias), consumed, not consumers. **Open item:** confirm no other
   system reads the LexiCán MariaDB directly (e.g. the SQL views in `documentos/vistas*.sql` suggest reporting
   consumers).
2. **Build & test locally** — **Open item.** No CI exists in the repo (`.github/` absent). PHP 8.5 and Composer are
   present here but the legacy pins PHP ^8.0|^8.1 and Laravel 8; no `vendor/` or `node_modules/` are checked in.
3. **Bespoke build infrastructure** — **Open item.** Found: `deploy.sh`, `copytonfs.sh`, `subirDev.sh`,
   `provision.sh`, `Dockerfile.apache81`, `hooks/` (OpenShift-era scripts, NFS copy). Documented in `developers.md`.
4. **Prior attempts** — **Open item.** None recorded in git (history is a single vendor import, `c80ff65`).
5. **Off limits** — From `1er-prompt.md` §5: branch `upstream` (`c80ff652b3be44fc5b881060af5abc8c5f8842e8`) is a
   frozen historical snapshot — no commits, no force-push, no rewrite. The legacy tree stays frozen in the working tree until Phase 11; `upstream` is the reference.

## Check 6 — Scope boundary

Standalone repository (`ateeducacion/lexican`, public, admin access). No parent repo/solution; no inbound code
consumers found. Outbound runtime dependencies: CAS server, CAUCE web service, SMTP, NFS media share.

## Checks

| # | Check | Status | Found | Fix |
|---|---|---|---|---|
| 1 | Stack | ✅ | PHP 8 / Laravel 8.83 / Voyager 1.5 / Blade (137) / jQuery+Bootstrap 4 / Laravel Mix 6 / MariaDB. 210 PHP, 28 JS, 3 Vue, 45 CSS, 21 Sass, 156 SVG; ~71k code lines (scc) | — |
| 2 | Analysis tools | ✅ | scc 4.1.0, python3 3.14.8, lizard 2.1.0 (cloc missing, not needed) | — |
| 3a | Build definition | ⚠️ | No CI. `package.json` (mix), `composer.json` (pinned `apereo/phpcas 1.5.0`, `barryvdh/laravel-dompdf 2.0.0`), `composer.phar` committed, `deploy.sh`/`Dockerfile.apache81` | Legacy build is not reproduced in CI; equivalence uses recorded traces + characterization, not dual run |
| 3b | Legacy smoke L1 | ✅ | `php -l` passes on controllers/routes | — |
| 3b | Legacy smoke L2 | ⚠️ | Not attempted: Laravel 8 does not support PHP 8.5; would need a PHP 8.1 container + MariaDB | Optional: `docker run php:8.1` if characterization E2E on the legacy is needed |
| 3c | Target stack | ✅ | Scratch project: TypeScript 6.0 + Vitest 5.0.3 + Drizzle 0.45.3 on PGlite 0.5.8 — 1 test passed. Docker daemon up (Postgres/MariaDB containers available). Node 26.10, npm 11.19 | Pin TypeScript `~6.0` (typescript-eslint 8.71 requires `<6.1`; TS 7.0 is out but unsupported by the linter) |
| 4 | Source completeness | ✅ | 15 migrations, 26 seeders, 34 models, 15 controllers, `routes/`; SQL views/data in `documentos/`. No missing includes (Composer autoload) | — |
| 4 | Binary artifacts | ⚠️ | `composer.phar` (2.2 MB) tracked; 7 `.DS_Store` files | Remove in cleanup phase |
| 5 | Telemetry | ⚠️ | None available | Runtime overlay skipped |
| 5 | Version control | ⚠️ | Git, but history is a single import commit — no change-frequency signal | — |
| 7 | Edit protection | ⚠️ | No `Edit` deny rule for `legacy/**`. Note: here `legacy/lexican` *is* the working repo, and the plan (§99 Phase 11) eventually deletes the legacy tree from the working tree, so a deny rule would block the planned cleanup. Convention used instead: no edits to legacy paths until Phase 11; `upstream` is the immutable copy | — |

A permission rule covers Claude's file tools and recognized shell writes, not arbitrary scripts; the hard guarantee
here is the frozen `upstream` branch on GitHub.

## Verdict per command

| Command | Verdict |
|---|---|
| assess, map, extract-rules | Ready |
| brief | Ready (after discovery artifacts) |
| reimagine | Ready-with-gaps — legacy cannot run locally without a PHP 8.1 container; equivalence via characterization specs and recorded behavior |
| harden | Ready (Composer/npm advisory DBs reachable; no PHP SAST installed) |
| uplift | n/a (reimagine) |

Next: `/code-modernization:modernize-assess lexican`
