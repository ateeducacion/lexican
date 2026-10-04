# Assessment — lexican

- Date: 2026-10-04 · `legacy/lexican` → `/Users/ernesto/Dropbox/Trabajo/ate/lexican` (in place; the repo itself)
- Tools: scc 4.1.0 (inventory/complexity), Composer 2.10.3 + `npm audit` on scratch-resolved locks, OSV API.
  Three subagents: structural map, technical debt, security audit. Their findings were spot-checked against the source
  (SQLi, upload, log viewer, CAS validation, Voyager/dompdf config, `.env.example`, seeders).
- Measured numbers and the commands behind them are in [BASELINE.md](BASELINE.md).

## Executive Summary

LexiCán is a Laravel 8 + TCG Voyager web app where students and teachers of Canarian schools build personal and
classroom dictionaries (entries, senses, media, comments, PDF export). It has about 68k code lines, of which roughly
36.5k are hand-written PHP/Blade/JS, in 742 tracked files. Three-quarters of the 32 MiB of tracked bytes are media,
documents and vendored libraries. Risk is **high**. The framework has been out of support since 2023 and Voyager is
abandoned. The dependencies can no longer be installed as committed. The public repo exposes the `APP_KEY`, two CAUCE
bearer tokens and seeded admin passwords. Two unauthenticated or low-privilege paths lead to code execution (CSRF-exempt
`/upload` with the client-supplied file name; Voyager media/log viewer). The custom admin views that the controllers
render are missing from the source. Recommendation: **Rebuild** with `/code-modernization:modernize-reimagine` on the
fixed TS/React/Fastify/PostgreSQL target. Rotate the exposed credentials now, independently of the rewrite.

## System Inventory

| Item | Value |
|---|---|
| Code lines (scc, all) | 68,371 in 626 files. PHP 20,292 · SVG 12,295 · CSS 10,821 · JS 9,500 · Blade 9,401 · Sass 2,409 · SQL 530 · Shell 334 · Vue 177 |
| Hand-written code | 36,525 lines in 399 files (excludes `public/`, SVG, CSS, docs) |
| Most complex files (scc complexity) | `public/js/acepciones.js` 2,395 (a **stale committed webpack-4 bundle**) · `app/Helpers/dicAula_helper.php` 176 · `app/Helpers/global_helper.php` 117 · `app/Http/Controllers/DicAulaController.php` 95 · `DiccionarioPersonalController.php` 89 · `dpEntradas_helper.php` 48 · `dpEnvios_helper.php` 48 · `ComentariosHelper.php` 43 · `app/Cas/CasManager.php` 40 |
| Runtime / framework | PHP `^8.0\|^8.1` (`composer.json:11`), `laravel/framework ^8.83`, `tcg/voyager ^1.5`, `apereo/phpcas 1.5.0` (exact), `barryvdh/laravel-dompdf 2.0.0` (exact), `owen-it/laravel-auditing ^12`, `lakshmaji/thumbnail` (ffmpeg) |
| Front end | Blade (137 views) + jQuery + Bootstrap 4 + bootstrap-table + TinyMCE 5 + Dropify + Swiper, built by Laravel Mix 6 (`webpack.mix.js`) into a committed `public/js/app.js` (1.7 MB) |
| Data store | MariaDB/MySQL (`config/database.php:18`). 15 migrations create 45 tables; Voyager core tables come from the package. 13 SQL reporting views live outside migrations (`documentos/vistas*.sql`). Every model is audited (`audits` table) |
| Integrations | CAS SSO (`app/Cas/*`, a hand-copied package), CAUCE REST WS over Guzzle (`global_helper.php:713-920`), SMTP (`mail_helper.php:40`), NFS volumes for `storage/app/public` (deploy only), dompdf |
| Build / deploy | No lock files and no CI. `deploy.sh` / `Dockerfile.apache81` / `provision.sh` build an OpenShift image. `composer.phar` is committed |
| Tests | 3 PHPUnit files, 8 methods. Only `tests/Unit/goblalHelpeVigenciasTest.php` (6) tests real logic (school-year validity). Not runnable today (no installable `vendor/`) |
| Reproducibility | `composer update` fails: the `larapack.io` repo returns 404. With current Composer, the pinned `phpcas 1.5.0` and `dompdf ^2` are blocked by advisories |

## Architecture at a Glance

Diagram: [ARCHITECTURE.mmd](ARCHITECTURE.mmd) (10 domains, external systems clustered).

| # | Domain | Key files | Tables |
|---|---|---|---|
| D1 | Identity & Access (CAS + CAUCE provisioning, policies) | `routes/web.php:396-411`, `app/Cas/*`, `Auth/CasController.php`, `global_helper.php:466,713,930,1116`, `app/Policies/*` | users, roles, personas, users_personas, users_centros, centros |
| D2 | Reference / master data | `app/Models/{CampoEntrada,CampoValor,Ensenanza,…,Pautas}.php`, `mstEntradaValor_helper.php`, `MasterTablesDataSeeder*` | mst_* (9) |
| D3 | Personal Dictionary | `routes/web.php:41-133`, `DiccionarioPersonalController.php`, `dp{Diccionarios,Entradas,Acepciones}_helper.php` | dic_personal, dp_entradas, dp_acepciones, dp_acepciones_tematicas |
| D4 | Media (image/audio/video, thumbnails, TinyMCE uploads) | `dpMedios_helper.php`, `daMedios_helper.php`, `create-thumbnail.php`, `video_thumbnail.php`, `HomeController.php:55-59` | dp_acepciones_medios, envios_acepciones_medios + files |
| D5 | Submissions (envíos: snapshot copy personal → classroom) | `EnviosController.php`, `EnvioAcepcionController.php`, `dpEnvios_helper.php` | dp_envios, envios_* (4) |
| D6 | Classroom Dictionary (create, participants, review/publish, validity) | `routes/web.php:138-240`, `DicAulaController.php`, `dicAula_helper.php` (2,089 lines) | dic_aula_* (7) |
| D7 | Comments / feedback | `ComentariosController.php`, `ComentariosHelper.php`, `resources/views/comentarios/*` | comentarios_generales, comentarios_entradas |
| D8 | Exports & notifications (PDF, CSV, mail) | `PDFController.php`, `CSVController.php`, `MailController.php`, `pdf_helper.php`, `mail_helper.php` | reads D3/D5/D6 |
| D9 | Voyager admin (BREAD, school-year validity, stats, log viewer) | `routes/web.php:282-367`, `app/Http/Controllers/Voyager/*` (`VoyagerCompassController.php`) | Voyager core, audits, dic_aula_atemporales |
| D10 | Platform kernel (global helpers, constants, audit, layouts) | `global_helper.php`, `HelperServiceProvider.php:27` (glob-loads 16 helper files), `config/ctes.php`, `resources/views/layouts/**` | audits |

Most business logic sits in about 7.2k lines of **global procedural helpers** (`app/Helpers/*.php`), not in controllers
or models. Domain coupling is therefore implicit.

**Dangling references** (they change what the rewrite should preserve):
- **The custom admin UI is missing from the source.** There is no `resources/views/vendor/voyager/`, yet the code
  renders `voyager::compass.{logs,dicsCursoEsc,dicsPersonales,centrosAñoEscolar,vigencia,record}`
  (`VoyagerCompassController.php:68,155,205,248,272,520`; `vRecordingController.php:23`).
- Routes to methods that do not exist: `web.php:92` (`comentariosEntradaGET`), `:356,358,359`
  (`maintenance`, `configuracion_*`), `:360-366` (the warnings CRUD). `CSVController.php:30,47` calls a
  `queryNumDicsCursoEscolar` that does not exist.
- Model with a dropped table: `CentroDicAula.php:24` → `centros_dic_aula`, dropped by migration `2021_02_11_…`.
  Six tables are never used: `entradas_compartidas` and `avisos*` (`create_initial_structure.php:468-529`).
- Duplicate route names (`aula.consulta.ajax*`, `pdfdp.download`, `pdfda.download`, `diccionarioaula.entradas`).
  `Voyager::routes()` is registered twice (`web.php:351,355`).

## Production Runtime Profile

No telemetry is available (no APM, no logs supplied, legacy not runnable locally). Runtime overlay skipped.

## Technical Debt (top 10 by remediation value)

Credential values masked; inventory in `SECRETS.local.md`.

| # | Item | Evidence | Rewrite action |
|---|---|---|---|
| 1 | Credentials committed to a public repo | `.env.example:3` (APP_KEY, masked), `:63`/`:65` (CAUCE bearer tokens, masked), seeders and `HomeController.php:73-76` (admin password, masked) | Rotate now. In the rewrite, secrets come only from an env schema; admins are seeded by a CLI, never by a controller |
| 2 | Authentication trust chain disabled | `global_helper.php:748` `verify => false`; `CasManager.php:183-189` falls back to `setNoCasServerValidation()`; `phpcas 1.5.0` pinned (CVE-2022-39369); `app/Cas/*` hand-copied package | Maintained CAS client, strict ticket validation, fixed service URL, TLS verification on |
| 3 | EOL / abandoned stack | Laravel 8 (security EOL Jan 2023), Voyager abandoned, swiftmailer abandoned, Laravel Mix | Freeze and rewrite (no in-place upgrade) |
| 4 | God controllers + global-function helpers | `DicAulaController.php` 1,660 lines / 39 public methods; `DiccionarioPersonalController.php` 1,548 / 26; 16 helper files (7.2k lines) glob-loaded | Domain modules (entrada, acepción, envío, dicAula, comentario), typed services |
| 5 | Personal vs classroom duplication | `routes/web.php:47-65` vs `:192-213`; `dpMedios_helper.php` vs `daMedios_helper.php`; the same jQuery topic filter is pasted 4× in Blade | One dictionary model with a `kind` field, one search endpoint, one React component |
| 6 | Broken routes, dead code, dev/test endpoints live | See "Dangling references"; `/test` (`web.php:266`, `dd()` on CAUCE fixtures that writes users); `DEV_unirseGET` (`:169`); test mail GET (`:262`); `modify_config_files` (`global_helper.php:280`) | Do not port; build the route list from verified behaviour |
| 7 | Error handling missing or leaking | `dd()` in `catch` blocks (`EnvioAcepcionController.php:179`, `EnviosController.php:280`, `VoyagerCompassController.php:341,360`); 77 `::find(` calls without null handling; unguarded XML parse `global_helper.php:759`; hand-coded cascade as 10 SQL strings `DicAula.php:375-415` | Central error handler, schema validation, FK cascades in PostgreSQL |
| 8 | State-changing GET routes; CSRF-exempt upload | `web.php:86,89,103,106-108,112,148,181,217,218,231,…`; `VerifyCsrfToken.php:31` | POST/PATCH/DELETE + CSRF/SameSite on all mutations |
| 9 | Hardcoded hosts, paths and emails | `/var/www/html/medusa/apps/lexican/...` (`global_helper.php:1272,1287`, `HomeController.php:90`); emails in `MailController.php`; `env()` outside config (`global_helper.php:741,743`, `CSVController.php:23,103`); 115 tracked lines name internal hosts or domains | One typed config module; public repo must carry no internal hosts (§22, §87) |
| 10 | Binaries, vendored libraries, build output committed | `composer.phar`; jQuery **2.1.3** (`public/js/jquery.min.js`, unreferenced but served); TinyMCE skins; `public/js/app.js`; stale `public/js/acepciones.js` still loaded by `consulta/acepciones.blade.php:177`; 7 `.xcf`, 7 `.DS_Store`, duplicate Carlito fonts | npm-managed dependencies, Vite build, nothing built in git |

## Security Findings

Credential inventory in `SECRETS.local.md` (gitignored; not for sharing). Rows marked ✔ were re-read in the source
after the audit.

| ID | CWE | Sev. | file:line | Finding |
|---|---|---|---|---|
| SEC-001 ✔ | CWE-434 | Critical | `HomeController.php:55-58`; `VerifyCsrfToken.php:31`; `web.php:277` | `/upload` stores the file under the client-supplied name on the public disk, with no type check, and is CSRF-exempt. Any logged-in user can upload `x.php` to `/storage/tinyUploads/` → RCE on the mod_php image |
| SEC-002 ✔ | CWE-798/521 | Critical | `UsersRolesTablesSeeder.php:72,82,92,103,255`; `VoyagerCustomization.php:1941`; `UsersTableSeeder.php:25`; `provision.sh:23,31,55` | Seeded admins with passwords in public source (one trivial), plus `voyager:install --with-dummy` |
| SEC-003 ✔ | CWE-22/73 | Critical | `VoyagerCompassController.php:39-52` | The log viewer takes any base64 path to download or delete a file (`.env`). `browse_compass` is granted to the `user` role (`VoyagerCustomization.php:1911`) |
| SEC-004 | CWE-269/284 | High | `VoyagerCustomization.php:1908-1918`; `web.php:280` | `user` role has `browse_admin`. `Auth::routes()` leaves register and password login on beside CAS (needs confirming against the installed Voyager) |
| SEC-005 ✔ | CWE-89 | High | `dpEntradas_helper.php:119`; `dicAula_helper.php:1644` | `whereRaw('… LIKE "'.$input.'"')` with user input, reachable via GET `/personal/entrada/insert` |
| SEC-006 ✔ | CWE-295 | High | `CasManager.php:182-190`; `config/cas.php:71` | CAS server certificate not validated unless `CAS_VALIDATION` is `ca`/`self` |
| SEC-007 ✔ | CWE-295/522 | High | `global_helper.php:748` | CAUCE call with `verify=false` sends the bearer token |
| SEC-008 | CWE-1104 | High | `composer.json:13` | `phpcas 1.5.0`: CVE-2022-39369 (confirmed by `composer audit`) |
| SEC-009 ✔ | CWE-1104 | Critical/High | `composer.json:19`; `config/voyager.php:210,214` | Voyager CVE-2025-32931 (critical), CVE-2024-55415/55417/55416. Made worse by `allowed_mimetypes => '*'` and `compass_in_production => true` |
| SEC-010 | CWE-639/862 | High | `DicAulaController.php:1598-1602` (GET delete), `:1173,1215,1425`; `EnvioAcepcionController.php:38,110,204`; `ComentariosController.php:318,329` | IDOR: records are loaded by request id with no ownership or role check |
| SEC-011 | CWE-79 | High | `comentarios/comentariosDiccionarioVer.blade.php:143,208` and others | Stored XSS: TinyMCE HTML saved unsanitized and printed with `{!! !!}` |
| SEC-012 | CWE-79 | Medium | `ModalAjaxController.php:38-51`; `components/modal-*.blade.php` | Request parameters reflected raw into modal HTML/JS |
| SEC-013 ✔ | CWE-749 | Medium | `CSVController.php:108`; `web.php:244` | `call_user_func([$adminController, $request->methodName])` for any logged-in user |
| SEC-014 | CWE-306 | Medium | `web.php:282-284,298-299,340-344` | Admin group middleware commented out. Vigencia check and recording endpoints open without auth |
| SEC-015 | CWE-352 | Medium | `web.php:84,86,89,…`; `config/session.php:197` | State-changing GETs with `same_site=null` |
| SEC-016 ✔ | CWE-321/798 | High | `.env.example:3,63,65` | APP_KEY and CAUCE tokens in the public repo |
| SEC-017 | CWE-489/215 | Medium | `.env.example:4`; `Dockerfile.apache81:58-61`; `provision.sh:12`; `web.php:266` | `APP_DEBUG=true`, dev dependencies and php.ini-development in the image; `/test` dumps data |
| SEC-018 | CWE-552 | High (verify) | `Dockerfile.apache81:3,17-21,106`; `.htaccess` | DocumentRoot is the app root, not `public/`. Only a rewrite rule protects `.env`, logs and `documentos/` |
| SEC-019 ✔ | CWE-94/918 | Medium | `config/dompdf.php:202,231` | dompdf `enable_php` and `enable_remote` are true. Any HTML injection into a PDF becomes RCE/SSRF |
| SEC-020 | CWE-434 | Medium | `dpMedios_helper.php:56,118-121`; `envioAcepcion_helper.php:79` | Media uploads have no mime/size validation and are stored under the public disk |
| SEC-021 ✔ | CWE-829 | Medium | `composer.json:83-88` | Composer repository `larapack.io` is dead (404); a domain takeover would control package resolution |
| SEC-022 | CWE-1104 | High | `package.json` | npm audit: 25 findings (1 critical swiper, 14 high incl. axios 0.21 and tinymce 5). Vendored jQuery 2.1.3 adds 4 CVEs |
| SEC-023 | CWE-1104 | Medium | `composer.json:17` | Laravel 8 EOL. Composer audit: 16 advisories (1 critical, 3 high, 7 medium, 5 low) even at the newest allowed versions |
| SEC-024 | CWE-732 | Low | `copytonfs.sh:16-17`; `Dockerfile.apache81:106` | 0777/0666 modes; code owned by www-data |
| SEC-025 | CWE-359 | Medium | `UsersRolesTablesSeeder.php:89-102` | Real-format national IDs and named staff emails in the public repo |
| SEC-026 | CWE-319 | Low | `.env.example:36,39` | SMTP on port 25 without TLS |
| SEC-027 | CWE-798 | Low | `StressTestUserDataSeeder.php:56` | 2,000 stress users share one static password |
| SEC-028 | CWE-200 | Low | `CSVController.php:23,81`; `public/exports/*.csv` | Exports written to the web root under predictable names |

Checked and not a finding: `DicAula::delete()` raw SQL uses only an integer id. `documentos/*.sql` hold only
statistics queries (no INSERTs or personal data). `TrustProxies` trusts no proxy.

**Implication for the rewrite.** The GitHub Pages demo and the new code must not reuse any of these values. Seed data
must be synthetic. No internal host may appear in the new tree. Rotating the APP_KEY, the CAUCE tokens and the seeded
accounts on the **running legacy** is an operations action that cannot wait for cutover.

## Documentation Gaps (top 5)

The existing docs (`readme.md`, `developers.md`, `version.md`) cover OpenShift deployment and version upgrades only.
They describe no behaviour.

1. **Classroom dictionary lifecycle and school-year validity** (vigencia, atemporal, activation, `ctes.inicio_curso`):
   `dicAula_helper.php:1975-2010`, `VoyagerCompassController.php:257-531`. This is the only logic with tests, and it has no prose spec.
2. **Envío (submission) semantics**: what is snapshot-copied, when the copy diverges from the personal original, and
   the requirement check `dpEnvio_cumple_requisitos_diccionario` (`dpEnvios_helper.php:535`).
3. **CAUCE provisioning and role mapping**: how a CAS id becomes a Persona/User with centre and role
   (`global_helper.php:713-1116`, `config/ctes.php:472`), including the shared password hash at `:873`.
4. **Admin back-office**: what the missing `voyager::compass.*` screens did (statistics, validity, recording, the
   "warnings" feature); none of it is in the repo.
5. **Data model**: 45 tables plus Voyager core plus 13 out-of-band SQL views; no ER diagram; six tables unused. It is
   not documented which `documentos/vistas*.sql` consumers still exist (open item from PREFLIGHT).

## Relative Scale

| Measure | Value |
|---|---|
| KSLOC (scc, all languages) | 68.4 |
| KSLOC (hand-written subset) | 36.5 |
| Scale index `2.94 × KSLOC^1.10` | 306.7 (all) · 153.9 (hand-written) |

The index ranks this system against others by relative size. **It is not a timeline or a cost.** It assumes
human-team productivity and does not apply to agentic modernization. About 40 % of the "all" figure is SVG, CSS and
vendored or generated JS, so the hand-written figure is the better comparison.

## Recommended Modernization Pattern

**Rebuild → `/code-modernization:modernize-reimagine lexican`** (confirms the target fixed in INTENT.md).

Evidence for this choice over the alternatives:

- **Uplift/Refactor (stay on Laravel) is not cheaper.** The parts that would need upgrading are the parts that are
  broken or gone. Voyager is abandoned and carries a critical CVE with no fix line. Its custom admin views are missing
  from the source, so there is nothing to upgrade. Laravel 8 → 11/12 is three majors. Composer cannot even resolve the
  committed manifest (dead repository, advisory-blocked pins). Most logic is in 7.2k lines of global helpers that an
  uplift would carry along unchanged.
- **Transform (module-by-module port) fits poorly.** The domains share tables and global helpers (the D3/D5/D6 snapshot
  copy chain), and the UI is server-rendered Blade with 4× pasted jQuery. There is no API seam to strangle along.
- **The domain is small and well bounded.** There are 10 domains, 45 tables and about 40 explicit routes, and the
  intent can be extracted into rule cards (`extract-rules`). The fixed target suits a dictionary app with a
  local-first demo: React/Vite + Fastify + PostgreSQL, with PGlite for GitHub Pages.
- **Rehost and Replace are ruled out.** The intent requires a public GitHub Pages demo and a new stack. No product
  covers personal-to-classroom dictionary workflows with CAS/CAUCE.

Caveats that `brief` must carry:
1. Credential rotation on the live legacy is independent of, and earlier than, any build phase.
2. Equivalence cannot use a dual run (the legacy does not install). Use characterization specs, plus a PHP 8.1 +
   MariaDB container only if needed, with a repaired `composer.json` in scratch space.
3. Admin features whose views are missing must be specified by an SME, not inferred.
4. Do not port the known bugs and dead features (missing methods, unused `avisos*` tables, `/test`, GET mutations).

Next: `/code-modernization:modernize-map lexican`.
