# Baseline — lexican (legacy, `1er-prompt.md` §7)

- Date: 2026-10-04 · Measured: legacy (`upstream`, vendor import `c80ff65`). Scans exclude the non-legacy files listed below.
- Run from the repo root. Every scan excludes `analysis/`, `legacy/` (symlink to the repo itself), `.git/` and `1er-prompt.md`.
- The legacy app was **not** run (user instruction; Laravel 8 does not run on the local PHP 8.5). Rows that need a running
  legacy are marked **not measured**, with what it would take to measure them.
- Lock-dependent rows: the repo has **no `composer.lock` and no `package-lock.json`/`yarn.lock`**
  (`ls composer.lock package-lock.json yarn.lock` → all "No such file"). To audit, locks were resolved **in the
  scratchpad only** from copies of `composer.json`/`package.json`. Each resolves to the newest version allowed by the
  constraints, so the counts are a **floor**: the production install is probably older and has more advisories.

## Size and shape

| Metric | Value | Command |
|---|---|---|
| Tracked files | 742 | `git ls-files \| wc -l` |
| Tracked bytes (working copy of tracked files) | 33,527,943 B (~32 MiB) | `git ls-files -z \| xargs -0 stat -f %z \| awk '{s+=$1}END{print s}'` |
| Git object store | 24.50 MiB (832 loose objects, 0 packs) | `git count-objects -vH` |
| Working dir on disk, incl. `.git`, untracked `.env`, `analysis/` | 59,976 KB (`.git` = 25,412 KB) | `du -sk .` · `du -sk .git` |
| Code lines, all languages (scc) | 68,371 code / 91,168 total in 626 files | `scc --exclude-dir analysis,legacy,.git --not-match 1er-prompt.md .` |
| Hand-written code lines (PHP, Blade, JS, Sass, Vue, SQL, Shell; no `public/`, SVG, CSS, docs) | 36,525 code in 399 files | `scc --exclude-dir analysis,legacy,.git,public --exclude-ext svg,css,json,md,txt,csv,xml,yml,yaml --not-match 1er-prompt.md .` |
| PHP | 210 files, 20,292 code lines | scc (above) |
| Blade templates | 137 files, 9,401 code lines | scc (above); `git ls-files 'resources/views/**/*.blade.php' \| wc -l` = 137 |
| JavaScript | 28 files, 9,500 code lines (6,987 of them in the stale bundle `public/js/acepciones.js`) | `scc --by-file -s complexity …` |

## Application surface

| Metric | Value | Command |
|---|---|---|
| Explicit route declarations, active | 40 (32 GET, 9 POST, 2 PUT, 1 DELETE across all lines, incl. commented) | `grep -vE '^\s*(//\|\*\|#)' routes/web.php \| grep -oE 'Route::(get\|post\|put\|patch\|delete\|any\|match\|resource\|view\|redirect)\(' \| wc -l` · `grep -oE 'Route::(get\|post\|put\|patch\|delete\|any\|match\|resource)' routes/web.php \| sort \| uniq -c` |
| Commented-out route declarations | 8 | `grep -E '^\s*//.*Route::(…)\(' routes/web.php \| wc -l` |
| Route macros (expand to many routes) | `Auth::routes()` ×1 (`routes/web.php:280`), `Voyager::routes()` ×2 active (`:351`, `:355`), +1 commented (`:384`) | `grep -n 'Voyager::routes\|Auth::routes' routes/web.php` |
| API routes | 0 | `grep -cE 'Route::(get\|post\|…)' routes/api.php` |
| Controllers | 34 files (15 domain/app, 6 `Auth/*`, 13 `Voyager/*`; includes base `Controller.php`) | `git ls-files 'app/Http/Controllers/*.php' \| wc -l` |
| Models | 35 files under `app/Models` + `app/User.php`; 32 extend an Eloquent/Voyager model class | `git ls-files app/Models \| wc -l` · `… \| xargs grep -lE 'extends (Model\|Authenticatable\|…)'` |
| Migrations | 15 | `git ls-files 'database/migrations/*.php' \| wc -l` |
| Tables created by migrations | 45 distinct `Schema::create` names (no duplicates) | `git ls-files 'database/migrations/*.php' \| xargs grep -ohE "Schema::create\('[a-z_0-9]+'" \| sort -u \| wc -l` |
| Seeders | 26 | `git ls-files 'database/seeders/*.php' \| wc -l` |
| SQL views defined outside migrations | 13 `CREATE … VIEW` in `documentos/vistas*.sql` | `git ls-files 'documentos/*.sql' \| xargs grep -ciE 'create … view'` |

## Dependencies

| Metric | Value | Command |
|---|---|---|
| PHP direct deps | 10 `require` (incl. `php`) + 8 `require-dev` | `jq '.require\|length, ."require-dev"\|length' composer.json` |
| PHP resolved packages (scratch lock) | 92 prod + 40 dev | `jq '.packages\|length, ."packages-dev"\|length' composer.lock` (scratchpad) |
| npm direct deps | 11 `dependencies` + 10 `devDependencies` | `jq '.dependencies\|length, .devDependencies\|length' package.json` |
| npm resolved packages (scratch lock) | 884 lock entries | `npm install --package-lock-only --ignore-scripts` then `jq '.packages\|length' package-lock.json` |
| **Composer install reproducible as committed?** | **No.** `composer.json` declares the repository `https://larapack.io`, which now returns 404, so `composer update` aborts | `composer update --no-install …` → "larapack.io/packages.json could not be downloaded (HTTP/1.1 404)" |
| **Composer install with current Composer (2.10.3)?** | **Blocked by advisories.** The exact pins `apereo/phpcas 1.5.0` and `barryvdh/laravel-dompdf 2.0.0` (→ `dompdf/dompdf ^2`) are refused; resolution only works with `policy.advisories.block=false` | same command, without the dead repo |

## Vulnerabilities (current tools)

| Source | Result | Command |
|---|---|---|
| Composer (scratch lock) | **16 advisories: 1 critical, 3 high, 7 medium, 5 low.** `tcg/voyager` v1.7: CVE-2025-32931 (critical, argument injection), CVE-2024-55415 (high, path traversal), CVE-2024-55417 (medium, arbitrary file write), CVE-2024-55416 (low, reflected XSS). `apereo/phpcas` 1.5.0: CVE-2022-39369 (high, service hostname discovery). `laravel/framework` v8.83.29: CRLF injection in the email rule (high), CVE-2025-27515 (medium), signed-URL path confusion (medium), CVE-2026-102279 (low). `dompdf/dompdf` v2.0.8: 6 (CVE-2026-55554/55555/56722/59941/59942/59943). `league/flysystem` 1.1.10: CVE-2026-102601 (low) | `composer audit --locked --format=json` → `jq` |
| Composer abandoned packages | 3: `tcg/voyager` (no replacement), `swiftmailer/swiftmailer` (→ symfony/mailer), `maximebf/debugbar` (dev) | same, `.abandoned` |
| npm (scratch lock) | **25: 1 critical, 14 high, 5 moderate, 5 low.** Direct: `swiper` (critical, prototype pollution), `axios ^0.21.4` (high, 20+ advisories incl. SSRF/CSRF), `tinymce ^5.10.5` (high, 7 XSS advisories), `laravel-mix` (high, transitive), `vue-template-compiler` (moderate, XSS) | `npm audit --json` → `.metadata.vulnerabilities` |
| Vendored, served JS (not covered by npm audit) | `public/js/jquery.min.js` = **jQuery 2.1.3**: CVE-2015-9251, CVE-2019-11358, CVE-2020-11022, CVE-2020-11023. `public/js/swiper-bundle.min.js` = Swiper 6.2.0: CVE-2021-23370. `dropify` 0.2.1, `bootstrap` 4.6.2: none in OSV | `head -c 200 <file>` for version; `curl -X POST https://api.osv.dev/v1/query -d '{"version":"…","package":{"name":"…","ecosystem":"npm"}}'` |

## Unmaintained libraries

| Library | Evidence | Command |
|---|---|---|
| Laravel 8 | Security fixes ended Jan 2023 (Laravel support policy); pinned `^8.83` | `composer.json:17` |
| TCG Voyager | Marked abandoned on Packagist | `composer audit` `.abandoned` |
| swiftmailer | Abandoned (→ symfony/mailer) | same |
| popper.js v1 | npm `deprecated`: "find the new Popper v2 at @popperjs/core" | `npm view popper.js deprecated` |
| dropify | Latest 0.2.2, last metadata change 2022-06-16; vendored copy is 0.2.1 from 2016 | `npm view dropify version time.modified` |
| jquery-typeahead | Latest 2.11.1, last change 2022-06-19 | `npm view jquery-typeahead version time.modified` |
| Bootstrap 4 / TinyMCE 5 | Major lines superseded (latest 5.3.8 / 8.9.2) | `npm view bootstrap version` · `npm view tinymce version` |
| laravel-mix | Latest 6.0.49, last change 2025-06-12; Laravel itself moved to Vite | `npm view laravel-mix version time.modified` |

## Repository hygiene

| Metric | Value | Command |
|---|---|---|
| Media/docs/fonts tracked (png, jpg, svg, ico, woff/woff2/ttf/eot, pdf, odt, xlsx, xcf) | 242 files, 25,109,803 B (~75 % of tracked bytes) | `git ls-files -z \| xargs -0 stat -f '%z%t%N'` → `grep -iE '\.(png\|…\|xcf)$'` → sum |
| `public/` | 260 files, 16,130,061 B (175 under `public/imagenes`) | same TSV, prefix `public/` · `git ls-files public \| awk -F/ '{print $2}' \| sort \| uniq -c` |
| `documentos/` | 24 files, 12,738,226 B (3 `.odt` = 10.4 MB, PDFs, SQL, xlsx) | same TSV, prefix `documentos/` |
| Binary files tracked | 91 | `git diff --numstat 4b825dc6… HEAD \| awk '$1=="-"' \| wc -l` |
| Source art files (GIMP `.xcf`) | 7 files, 2,332,665 B | by-extension sum |
| Vendored third-party code | 50 files, 3,616,061 B: `composer.phar` (2.2 MB), `public/js/*.min.js` (jQuery 2.1.3, Bootstrap, bootstrap-table, Swiper, Dropify), `public/css/*.min.css`, `public/js/skins/**` (TinyMCE), `public/fonts/dropify.*`, `public/css/fontawesome.css` | `grep -E 'public/js/[^/]*\.min\.js$\|public/css/[^/]*\.min\.css$\|public/js/skins/\|public/fonts/dropify\|fontawesome\.css\|composer\.phar'` on the TSV |
| Build output committed | 8 files, ~2.25 MB: `public/js/app.js` (1.7 MB), `app.js.LICENSE.txt`, `public/css/app*.css` (3, two hash-named stale copies), `public/mix-manifest.json`, `public/js/langs/es.js` (copy of `resources/js/langs/es.js`), plus `public/js/acepciones.js` (11,015-line webpack-4 bundle no longer produced by `webpack.mix.js`) | same TSV; `head -c 600 public/js/acepciones.js` |
| Duplicate assets | Carlito webfonts in both `public/fonts/` and `public/fonts/carlito/` (identical; e.g. same md5 `edf6…e3e6`) | `md5 -q public/fonts/carlito-bold-webfont.woff public/fonts/carlito/carlito-bold-webfont.woff` |
| Vendored JS not referenced by any view/config | `jquery.min.js`, `swiper-bundle.min.js`, `dropify.min.js` (served publicly anyway) | `grep -rln <file> resources app config webpack.mix.js` → empty |
| `.DS_Store` | 7 tracked, 7 on disk | `git ls-files \| grep -c '\.DS_Store$'` · `find . -path ./legacy -prune -o -name .DS_Store -print \| wc -l` |
| Other accidental artifacts | `public/exports/data_export_2023-06-26.csv` (aggregate counts only); `storage/.DS_Store` | `git ls-files \| grep -iE '\.(sql\|csv\|…)$\|storage/'` |
| Git history | legacy (`upstream`) is a single vendor import commit, so no change-frequency signal | `git log --oneline origin/upstream \| wc -l` |
| CI | none (`.github/` absent) | `ls .github` |

## Tests

| Metric | Value | Command |
|---|---|---|
| Test files | 3 (`tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php`, `tests/Unit/goblalHelpeVigenciasTest.php`) | `git ls-files tests \| grep -c 'Test\.php$'` |
| Test methods | 8 (6 in the vigencias helper test, 1 + 1 framework examples) | `grep -c 'function test\|@test' tests/*/*.php` |
| Executed | **not run**: needs `vendor/` (Composer install currently blocked, see above) and PHP ≤ 8.1 | — |

## Not measured (need a running legacy)

| §7 item | Status | How to measure |
|---|---|---|
| Install time | **Not measured.** Only lock resolution was timed: Composer 15.4 s wall (after removing the dead repo and disabling advisory blocking), npm 31.4 s wall (`--package-lock-only`) | `time composer install` / `time npm ci` inside a `php:8.1` container with a repaired `composer.json` |
| Build time | **Not measured** | `time npm run production` in the same container |
| Console errors in main flows | **Not measured** | Playwright against a PHP 8.1 + MariaDB container with seeded data, CAS mocked |
| Screenshots of relevant screens | **Not captured** | same harness |
| Characterization E2E on legacy | **Not created** (§7 asks for them; scope of a later step) | same harness; must not touch real CAS/CAUCE |
