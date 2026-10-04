# Referencias de mantenimiento: Aritmates y Tonga

Notas de método (no de arquitectura) tomadas de `ateeducacion/aritmates` y `ateeducacion/tonga` (lectura 2026-10-04, solo lectura). **Tonga es la referencia más madura** (actions fijadas por SHA, TypeScript+Vite, REUSE/SPDX, licencias, SBOM, ADR, fixture E2E que falla ante errores de consola, rulesets). Aritmates aporta el golden master, el gate de cobertura en dos niveles, `codecov.yml`, el informe con gráficas y el workflow de actualización de skills. Los bloques verbatim son de **Tonga** salvo que se indique.

## 1. Resumen comparado

| Tema | Aritmates | Tonga | Para LexiCán |
|---|---|---|---|
| Stack | JS + jQuery/Bootstrap, esbuild, Sass, Mocha/c8 | TS 6, Vite 8, Vitest 5, Playwright 1.63, ESLint 10 flat + typescript-eslint | Tonga |
| Node | `engines.node >=24`, CI Node 24 | igual | igual |
| Deps | rangos `^`, `overrides`, `allowScripts` | **versiones exactas** en devDeps, `allowScripts` (`canvas: true`, `fsevents: false`), sin `overrides` | exactas + `allowScripts` |
| Actions | por tag (`@v7`) | **por SHA + comentario `# vX.Y.Z`** | SHA |
| Concurrencia CI | ninguna | `ci-${{ github.ref }}`, cancel-in-progress | sí |
| Pages | `workflow_run` tras CI; permisos `pages/id-token` a nivel workflow | `workflow_run` tras CI **solo si event==push**; manual solo desde `main`; `git merge-base --is-ancestor HEAD origin/main`; permisos de escritura solo en job `deploy` | Tonga |
| Release | `release: published`, `contents: write` a nivel job, sin pinning | `push: tags v*`, comprueba tag==package.json, repite gate completo, ZIP + SBOM SPDX, `generate_release_notes` | Tonga (adaptado) |
| Lighthouse | — | `lighthouse.yml` tras Pages, informativo (warning bajo baseline) | opcional |
| Skills | `.agents/skills` canónico + copia `.claude/skills`; workflow semanal `update-agent-skills.yml` | igual, sin workflow de actualización | copiar el modelo |
| Licencias | `LICENSE` AGPL-3.0 (id obsoleto) | `AGPL-3.0-or-later`, `LICENSES/`, `REUSE.toml`, `THIRD_PARTY_NOTICES.md`, `docs/LICENSING.md`, `npm run licenses` (allow-list runtime), `pipx run reuse==6.2.0 lint` en CI | Tonga |
| `upstream` | rama con el código original del proveedor (`a78fc91`), solo historia | igual (`f10785f`); **ruleset «upstream: read-only»** (update/deletion/non_fast_forward); ruleset «main: pull requests only» (1 aprobación, no force-push, no borrado; admins bypass solo vía PR) | igual |
| Cobertura | c8, gate en 2 niveles (global 90 %, módulos saneados 98/95/90); Codecov informativo vía `codecov.yml` | Vitest v8, `include` explícito de módulos de lógica, thresholds 98/97/97/90; DOM cubierto por E2E | Tonga + `codecov.yml` de Aritmates |
| E2E | Chromium, `pageerror` por spec, axe en `accessibility.spec.js` | Chromium/Firefox/WebKit, **fixture `auto` que falla con pageerror, console.error o petición externa**; axe WCAG 2.0/2.1/2.2 A+AA; `e2e/visual` no bloqueante | Tonga |
| Changelog | `changelog.md` libre por bloques «(post 1.3)» | `CHANGELOG.md` Keep a Changelog 1.1.0 en español (`Añadido/Cambiado/Corregido`), `[Unreleased]` | Tonga |
| Idioma | docs para personas en español; código, comentarios, commits y PR en inglés | igual | igual |

## 2. Workflows: hechos clave

- **Triggers**: CI `push: [main]` + `pull_request`. Pages `workflow_run: [CI] completed, branches [main]` + `workflow_dispatch`. Release `push: tags ['v*']` (Tonga; una etiqueta NO relanza CI ni Pages, ver CHANGELOG 2.3.0). Lighthouse `workflow_run: [Pages]`.
- **Permisos**: `permissions: contents: read` a nivel workflow siempre; escritura solo en el job que la necesita (`id-token: write` para Codecov OIDC en CI; `pages: write` + `id-token: write` solo en `deploy`; `contents: write` solo en release).
- **`persist-credentials: false`** en todos los checkouts de Tonga.
- **`timeout-minutes`** 30 (CI, release), 15 (Lighthouse).
- **Concurrencia**: CI `ci-${{ github.ref }}` cancel; Pages `pages` sin cancel (no se corta un despliegue; el siguiente sustituye a los pendientes); Lighthouse `lighthouse` cancel.
- **Pages depende de CI** por `workflow_run` + `if: conclusion == 'success' && event == 'push'`, checkout de `workflow_run.head_sha`, y verificación de ancestro de `origin/main`. Aritmates (AGENTS) añade: el check `CI` debe ser obligatorio en la protección de rama; Pages es una segunda barrera.
- **Artefacto de fallo**: `playwright-report/` con `actions/upload-artifact` solo `if: failure()`, 7 días.
- **REUSE** en CI con `pipx run reuse==6.2.0 lint` (sin dependencia npm).

### SHAs fijados (Tonga)

| Action | SHA | Versión |
|---|---|---|
| `actions/checkout` | `3d3c42e5aac5ba805825da76410c181273ba90b1` | v7.0.1 |
| `actions/setup-node` | `820762786026740c76f36085b0efc47a31fe5020` | v7.0.0 |
| `codecov/codecov-action` | `303a32d7a59b442fa8d48b6a1cc6825c09c847a5` | v7.1.1 |
| `actions/upload-artifact` | `043fb46d1a93c77aae656e7c1c64a875d1fc6a0a` | v7.0.1 |
| `actions/configure-pages` | `45bfe0192ca1faeb007ade9deae92b16b8254a0d` | v6.0.0 |
| `actions/upload-pages-artifact` | `fc324d3547104276b827a68afc52ff2a11cc49c9` | v5.0.0 |
| `actions/deploy-pages` | `368f82528645a54fb793d4d04e342629a3f51346` | v5.0.1 |
| `softprops/action-gh-release` | `efb35369e0ad2afab669f228072c1b0d510eae64` | v3.0.3 |
| `lighthouse` (npx) | — | `lighthouse@13.5.0` |
| `reuse` (pipx) | — | `reuse==6.2.0` |

Aritmates usa tags sin SHA: `checkout@v7`, `setup-node@v7`, `codecov-action@v7`, `configure-pages@v6`, `upload-pages-artifact@v5`, `deploy-pages@v5`, `softprops/action-gh-release@v3`, `devantler-tech/actions/update-agent-skills@v13.10.3`, `peter-evans/create-pull-request@v8` (su AGENTS.md decide expresamente no pasarlos a SHA en el workflow de skills).

### CI — Tonga `.github/workflows/ci.yml` (verbatim)

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:

permissions:
  contents: read

# A new push to the same branch or pull request cancels the run in progress.
concurrency:
  group: ci-${{ github.ref }}
  cancel-in-progress: true

jobs:
  quality:
    runs-on: ubuntu-latest
    timeout-minutes: 30
    permissions:
      contents: read
      id-token: write # Codecov upload via OIDC, no secret token
    steps:
      - uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1
        with:
          persist-credentials: false

      - uses: actions/setup-node@820762786026740c76f36085b0efc47a31fe5020 # v7.0.0
        with:
          node-version: 24
          cache: npm

      - run: npm ci
      - run: npm run audit
      - run: npm run licenses
      - run: pipx run reuse==6.2.0 lint
      - run: npm run lint
      - run: npm run typecheck
      - run: npm run coverage

      - uses: codecov/codecov-action@303a32d7a59b442fa8d48b6a1cc6825c09c847a5 # v7.1.1
        with:
          files: coverage/lcov.info
          disable_search: true
          use_oidc: true
          fail_ci_if_error: false
      - run: npm run build
      - run: npm run check

      - run: npx playwright install --with-deps chromium firefox webkit
      - run: npm run e2e

      - if: failure()
        uses: actions/upload-artifact@043fb46d1a93c77aae656e7c1c64a875d1fc6a0a # v7.0.1
        with:
          name: playwright-report
          path: playwright-report/
          retention-days: 7
```

### Pages — Tonga `.github/workflows/pages.yml` (verbatim)

```yaml
# Publishes dist/ to GitHub Pages, only after CI succeeded on main.
name: Pages

on:
  workflow_run:
    workflows: [CI]
    types: [completed]
    branches: [main]
  workflow_dispatch:

permissions:
  contents: read

# A deployment in progress is never cut; a newer one waits and replaces any older pending run.
concurrency:
  group: pages
  cancel-in-progress: false

jobs:
  build:
    # Manual runs may only redeploy main; automatic runs only after a successful CI on a push to main.
    if: (github.event_name == 'workflow_dispatch' && github.ref == 'refs/heads/main') || (github.event.workflow_run.conclusion == 'success' && github.event.workflow_run.event == 'push')
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1
        with:
          ref: ${{ github.event.workflow_run.head_sha || github.sha }}
          fetch-depth: 0 # origin/main, for the check below
          persist-credentials: false

      - name: Deploy only commits that are on main
        run: git merge-base --is-ancestor HEAD origin/main

      - uses: actions/setup-node@820762786026740c76f36085b0efc47a31fe5020 # v7.0.0
        with:
          node-version: 24
          cache: npm

      - run: npm ci
      - run: npm run build
      - run: npm run check

      - uses: actions/configure-pages@45bfe0192ca1faeb007ade9deae92b16b8254a0d # v6.0.0

      - uses: actions/upload-pages-artifact@fc324d3547104276b827a68afc52ff2a11cc49c9 # v5.0.0
        with:
          path: dist

  deploy:
    needs: build
    runs-on: ubuntu-latest
    permissions:
      pages: write
      id-token: write
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:
      - id: deployment
        uses: actions/deploy-pages@368f82528645a54fb793d4d04e342629a3f51346 # v5.0.1
```

### Release — Tonga `.github/workflows/release.yml` (verbatim)

```yaml
# Builds, checks and publishes a release ZIP of dist/ for tags v*. The same gate as CI: Pages
# already deployed this commit from main, so the tag never redeploys it.
name: Release

on:
  push:
    tags: ['v*']

permissions:
  contents: read

jobs:
  release:
    runs-on: ubuntu-latest
    timeout-minutes: 30
    permissions:
      contents: write # create the GitHub release
    steps:
      - uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1
        with:
          persist-credentials: false

      - name: Tag matches package.json
        run: |
          version="v$(node -p "require('./package.json').version")"
          [ "$version" = "$GITHUB_REF_NAME" ] || { echo "::error::Tag $GITHUB_REF_NAME does not match package.json ($version)"; exit 1; }

      - uses: actions/setup-node@820762786026740c76f36085b0efc47a31fe5020 # v7.0.0
        with:
          node-version: 24
          cache: npm

      - run: npm ci
      - run: npm run audit
      - run: npm run licenses
      - run: pipx run reuse==6.2.0 lint
      - run: npm run lint
      - run: npm run typecheck
      - run: npm run coverage
      - run: npm run build
      - run: npm run check
      - run: npx playwright install --with-deps chromium firefox webkit
      - run: npm run e2e
      - run: npm run sbom

      - name: Package dist/
        run: |
          set -euo pipefail
          name="tonga-${GITHUB_REF_NAME#v}"
          (cd dist && zip -qr "../${name}.zip" .)
          echo "ZIP=${name}.zip" >> "$GITHUB_ENV"

      - uses: softprops/action-gh-release@efb35369e0ad2afab669f228072c1b0d510eae64 # v3.0.3
        with:
          files: |
            ${{ env.ZIP }}
            dist/sbom.spdx.json
          generate_release_notes: true
```

### Lighthouse — Tonga `.github/workflows/lighthouse.yml` (verbatim, opcional)

```yaml
# Informative Lighthouse run against the deployed site after each Pages deployment.
# It never blocks anything: results go to the run summary and a report artifact.
name: Lighthouse

on:
  workflow_run:
    workflows: [Pages]
    types: [completed]
    branches: [main]
  workflow_dispatch:

permissions:
  contents: read

# Only the latest deployment is worth measuring.
concurrency:
  group: lighthouse
  cancel-in-progress: true

jobs:
  audit:
    if: github.event_name == 'workflow_dispatch' || github.event.workflow_run.conclusion == 'success'
    runs-on: ubuntu-latest
    timeout-minutes: 15
    env:
      URL: https://ateeducacion.github.io/tonga/
    steps:
      - uses: actions/setup-node@820762786026740c76f36085b0efc47a31fe5020 # v7.0.0
        with:
          node-version: 24

      - name: Run Lighthouse (desktop and mobile)
        run: |
          set -euo pipefail
          for preset in desktop mobile; do
            flags=$([ "$preset" = desktop ] && echo "--preset=desktop" || true)
            npx --yes lighthouse@13.5.0 "$URL" $flags --quiet \
              --only-categories=performance,accessibility,best-practices \
              --chrome-flags="--headless=new --no-sandbox" \
              --output=json --output=html --output-path="./lighthouse-$preset"
          done

      - name: Summary (warn below the measured baseline)
        run: |
          node - <<'JS' >> "$GITHUB_STEP_SUMMARY"
          const fs = require('fs');
          // Baseline measured on 2026-10-02 (docs/MODERNIZATION.md).
          const min = { performance: 90, accessibility: 100, 'best-practices': 100 };
          console.log('| Perfil | Rendimiento | Accesibilidad | Buenas prácticas | LCP | CLS |\n|---|---:|---:|---:|---:|---:|');
          for (const preset of ['desktop', 'mobile']) {
            const r = JSON.parse(fs.readFileSync(`lighthouse-${preset}.report.json`, 'utf8'));
            const s = Object.fromEntries(Object.entries(r.categories).map(([k, v]) => [k, Math.round(v.score * 100)]));
            for (const [k, v] of Object.entries(min)) if (s[k] < v) process.stderr.write(`::warning::Lighthouse ${preset} ${k} ${s[k]} < ${v}\n`);
            console.log(`| ${preset} | ${s.performance} | ${s.accessibility} | ${s['best-practices']} | ${r.audits['largest-contentful-paint'].displayValue} | ${r.audits['cumulative-layout-shift'].displayValue} |`);
          }
          JS

      - uses: actions/upload-artifact@043fb46d1a93c77aae656e7c1c64a875d1fc6a0a # v7.0.1
        with:
          name: lighthouse
          path: lighthouse-*
          retention-days: 30
```

### Actualización de skills — Aritmates `.github/workflows/update-agent-skills.yml` (verbatim, opcional)

```yaml
name: Update Agent Skills

on:
  schedule:
    - cron: '17 6 * * 1'
  workflow_dispatch:

permissions: {}

concurrency:
  group: update-agent-skills
  cancel-in-progress: false

jobs:
  update:
    runs-on: ubuntu-latest
    timeout-minutes: 10
    permissions:
      contents: write
      pull-requests: write
    steps:
      - uses: actions/checkout@v7
        with:
          ref: main
          persist-credentials: false

      - id: update
        uses: devantler-tech/actions/update-agent-skills@v13.10.3
        with:
          dir: .agents/skills

      - name: Copiar skills a .claude/skills
        id: mirror
        run: |
          set -euo pipefail
          mkdir -p .claude/skills
          rsync -a --delete --exclude .DS_Store --exclude .venv .agents/skills/ .claude/skills/
          if ! git diff --quiet -- .claude/skills || [ -n "$(git ls-files --others --exclude-standard -- .claude/skills)" ]; then
            echo "changed=true" >> "$GITHUB_OUTPUT"
          else
            echo "changed=false" >> "$GITHUB_OUTPUT"
          fi

      - name: Open pull request
        if: steps.update.outputs.changed == 'true' || steps.mirror.outputs.changed == 'true'
        uses: peter-evans/create-pull-request@v8
        with:
          base: main
          branch: feature/update-agent-skills
          delete-branch: true
          add-paths: |
            .agents/skills/**
            .claude/skills/**
          commit-message: 'chore(deps): update agent skills'
          title: 'chore(deps): update agent skills'
          body: |
            Updates installed skills with `gh skills update --all`.
            Review upstream instructions and provenance before merging.
            Local skills without GitHub provenance are skipped by the updater.
            `.claude/skills/` is a full copy of `.agents/skills/` (no symlinks).
            Default-token PRs do not trigger CI automatically.
```

### Dependabot — Tonga `.github/dependabot.yml` (verbatim)

Agrupa dev-tooling minor/patch y todas las actions en un PR; ignora TS ≥6.1 (límite de typescript-eslint, documentado en ADR) y majors de `@types/node`. Aritmates es la versión mínima (npm + actions semanal, sin grupos).

```yaml
version: 2
updates:
  - package-ecosystem: npm
    directory: /
    schedule:
      interval: weekly
    groups:
      dev-tooling:
        dependency-type: development
        update-types: [minor, patch]
    ignore:
      # typescript-eslint 8.x supports TypeScript < 6.1 (docs/adr/0003-vite-typescript.md).
      - dependency-name: typescript
        versions: ['>=6.1.0']
      # Types follow the Node LTS that Tonga targets (engines >=24, CI on Node 24).
      - dependency-name: '@types/node'
        update-types: [version-update:semver-major]

  - package-ecosystem: github-actions
    directory: /
    schedule:
      interval: weekly
    groups:
      actions:
        patterns: ['*']
```

### Codecov — Aritmates `codecov.yml` (verbatim)

```yaml
# The blocking gate is `npm run coverage:ci`; Codecov only reports.
coverage:
  status:
    project:
      default:
        informational: true
    patch:
      default:
        informational: true
```

## 3. Makefile — Tonga (verbatim)

Fino envoltorio de `npm run`; `help` se autogenera de los comentarios `## `. Aritmates es igual pero con `.RECIPEPREFIX` y sin `check`/`e2e`.

```make
.PHONY: up build test lint fix e2e check package clean help

## Install dependencies and start the dev server
up:
	npm ci
	npm run dev

## Build the static site into dist/
build:
	npm run build

## Unit tests
test:
	npm test

## Lint and typecheck
lint:
	npm run lint
	npm run typecheck

## Fix lint issues
fix:
	npm run lint -- --fix

## End-to-end tests (Chromium, Firefox, WebKit)
e2e:
	npm run e2e

## Full local quality gate
check: lint test build
	npm run check
	npm run e2e

## Zip dist/ as tonga-<version>.zip
package: ZIP = $(shell node -p "require('./package.json').name+'-'+require('./package.json').version").zip
package: build
	rm -f $(ZIP)
	cd dist && zip -qr ../$(ZIP) .

## Remove build output
clean:
	npm run clean

## Show this help
help:
	@awk '/^## /{d=substr($$0,4);next} /^[a-z0-9-]+:/{if(d)printf "\033[36m%-10s\033[0m %s\n",substr($$1,1,length($$1)-1),d;d=""}' Makefile
```

## 4. ESLint — Tonga `eslint.config.js` (verbatim)

Patrón reutilizable: `no-restricted-imports` para imponer fronteras de capas (solo ciertas carpetas importan `fabric`) y `no-restricted-syntax` contra APIs privadas. Para LexiCán: solo `packages/db` importa `drizzle-orm`/`pg`; `apps/web` no importa nada de `apps/api`.

```js
import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import globals from 'globals';

export default tseslint.config(
  { ignores: ['.agents/', '.claude/', 'dist/', 'coverage/', 'legacy/', 'analysis/', 'playwright-report/', 'test-results/', 'vendor/'] },
  js.configs.recommended,
  ...tseslint.configs.recommended,
  {
    languageOptions: { globals: { ...globals.browser } },
    rules: {
      'no-restricted-syntax': [
        'error',
        // Fabric is only reached through the public API: no underscore-prefixed members.
        { selector: 'MemberExpression[property.name=/^_/]', message: 'Private (_-prefixed) members are not allowed.' },
      ],
    },
  },
  {
    files: ['src/**/*.ts'],
    rules: { 'no-console': ['error', { allow: ['warn', 'error'] }] },
  },
  {
    files: ['src/**/*.ts'],
    ignores: ['src/canvas/**', 'src/export/**', 'src/import/**'],
    rules: {
      'no-restricted-imports': ['error', { paths: [{ name: 'fabric', message: 'Only src/canvas/, src/export/ and src/import/ import fabric.' }] }],
    },
  },
  {
    files: ['src/sw.js'],
    languageOptions: { globals: { ...globals.serviceworker, __PRECACHE__: 'readonly' } },
  },
  {
    files: ['scripts/**', '*.config.*'],
    languageOptions: { globals: { ...globals.node } },
  },
);
```

Aritmates (`eslint.config.js`) no usa `@eslint/js`: define globals a mano y solo reglas de corrección para no reescribir legacy; endurece por lista de ficheros saneados (`no-unused-vars` con `^_`, `no-console` salvo warn/error). Útil solo como idea de «endurecer por zonas».

## 5. tsconfig, Vite, Playwright (Tonga)

- `tsconfig.json`: `target ES2022`, `module ESNext`, `moduleResolution bundler`, `lib [ES2023, DOM, DOM.Iterable]`, `types [vite/client, node]`, `strict`, **`noUncheckedIndexedAccess`**, `noImplicitOverride`, `isolatedModules`, `skipLibCheck`, `noEmit`, `resolveJsonModule`; `include` cubre `src test e2e scripts/*.ts` y configs. Typecheck = `tsc --noEmit`.
- `vite.config.ts`: `base: './'` (subcarpeta de Pages sin reescribir), `define` `__APP_VERSION__` (package.json) y `__APP_BUILD__` (`version+shortsha`, solo tooltip); `VITE_SITE_URL` por defecto `https://ateeducacion.github.io/tonga/` para og:image; plugin propio que emite `sw.js` con precache hasheado; Vitest dentro del mismo config (`environment: jsdom`, `coverage.include` explícito de lógica, `thresholds {lines 98, statements 97, functions 97, branches 90}`, `reporter ['text-summary','lcov']`).
- `playwright.config.ts`: `fullyParallel`, `forbidOnly` en CI, `retries` 1 en CI, reporter `github`+`html` en CI, `trace: 'retain-on-failure'`, proyectos chromium/firefox/webkit sobre `e2e/app`, proyecto `visual` solo con `VISUAL=1`, `webServer` = `node scripts/serve.mjs 4173` (servidor estático propio sin dependencias, `scripts/serve.mjs`) sobre `dist/`.
- Aritmates: Chromium único, `fullyParallel: false`, timeouts largos, servidor propio en 9012.

## 6. E2E

- `e2e/app/fixtures.ts` (Tonga): `test.extend` con fixture `problems` `{ auto: true }` que recoge `pageerror`, `console` tipo error y **cualquier request que no sea `http://127.0.0.1|blob:|data:`** y hace `expect(problems).toEqual([])` al final. Todos los specs importan `test` de ahí. Para LexiCán: ampliar la allow-list con el origen de la API en modo producción y nada externo en la demo PGlite.
- `a11y.spec.ts`: `new AxeBuilder({ page }).withTags(['wcag2a','wcag2aa','wcag21a','wcag21aa','wcag22aa']).analyze()`, una auditoría por pantalla/diálogo/tema oscuro/móvil; comentario explícito: cero violaciones ≠ conformidad WCAG, checklist manual en `docs/ACCESSIBILITY.md`.
- Specs: `critical`, `security` (entradas hostiles fallan controladamente), `a11y`, `pwa`; `e2e/visual/gallery.spec.ts` genera `docs/screenshots/` sin bloquear.
- Descargas: `waitForEvent('download')` antes del clic; validar por estructura, nunca píxel a píxel.
- Aritmates: `critical.spec.js` (flujos críticos), `accessibility.spec.js`, `ayuda`, `dialogos`; golden master en unitarios (`test/golden.spec.js` contra `test/fixtures/golden.json`, regenerar y revisar diff en PR) + semilla obligatoria por spec.

## 7. Scripts de métricas

| Script | Repo | Mide | Cómo |
|---|---|---|---|
| `scripts/metrics.mjs [refA] [refB] [--json]` | Tonga | ficheros, bytes, ficheros JS/CSS, vendored, código propio, nº de tests, `console.log`, `eval/new Function`, handlers en línea, API privada | solo del árbol git (`git ls-tree -r -l`, `git grep -c`), así `node_modules`/`dist` no sesgan; por defecto `origin/upstream` vs `HEAD` |
| `scripts/measure-load.mjs URL` | Tonga | peticiones, bytes por tipo, hosts externos, errores de consola, tiempo a networkidle | Playwright Chromium, viewport 1366×768 |
| `scripts/report.mjs [upstreamRef] [vendorDistRef]` | Tonga | genera `docs/MODERNIZATION-REPORT.md` (repo, despliegue, carga, calidad) | combina metrics + tamaño de `dist/` + measure-load sirviendo ambas versiones; «no hay valores escritos a mano» |
| `scripts/check-dist.mjs` | Tonga | `dist/` sano: entradas existen, sin URLs root-absolutas (rompen subcarpeta), sin `<script>` inline (CSP estricta), og:image https absoluta, sin `.map`/`.ts` | `npm run check`, en CI y Pages |
| `scripts/licenses.mjs [--json]` | Tonga | inventario de licencias npm; **falla si una dep de runtime no está en la allow-list** (MIT, MIT-0, ISC, Apache-2.0, BSD-2/3, 0BSD, CC0-1.0, BlueOak-1.0.0, Unlicense); dev solo informa | `npm ls --all --json --long [--omit=dev]` |
| `npm run sbom` | Tonga | `npm sbom --sbom-format spdx --omit dev > dist/sbom.spdx.json` | adjunto a la release |
| `scripts/informe-modernizacion.mjs [old] [new]` | Aritmates | igual que report pero escribe `docs/informe/metricas.json` + SVG (código, dependencias, despliegue, carga, pruebas) | git + Playwright |

## 8. Licencias (Tonga)

- `LICENSE` = texto AGPL v3; `package.json` `"license": "AGPL-3.0-or-later"`; `LICENSES/` con un `.txt` por SPDX usado (AGPL-3.0-or-later, Apache-2.0, CC-BY-NC-SA-4.0, CC-BY-SA-4.0, ISC, MIT).
- `REUSE.toml` (version 1): anotación `path = "**"`, `precedence = "aggregate"`, copyright «2019-2026 Gobierno de Canarias», AGPL-3.0-or-later; overrides por ruta para contenidos, iconos, vendor, skills (`.agents/skills/<x>/**` y `.claude/skills/<x>/**` con su licencia), `.agents/licenses/**`, og-image y logo ATE. Cada override lleva un comentario con la evidencia.
- `THIRD_PARTY_NOTICES.md`: tabla «Componente | Versión | Uso | Licencia | Origen» de lo que **se distribuye en `dist/`**, después el texto completo de cada licencia; menciona lo retirado que ya no se distribuye.
- `docs/LICENSING.md`: tabla «Elemento | Licencia aplicada | Evidencia | Estado (Decidido/Duda)», decisión fechada del mantenedor, alcance (el código de `upstream` no tiene licencia y no se reutiliza), dudas abiertas, propuesta no aplicada. «No es asesoramiento jurídico».
- AGENTS.md: no copiar código sin licencia comprobada; registrar proyecto, autor, URL, fichero, versión/commit, licencia y cambios.

## 9. Skills de agentes

Ambos repos tienen los mismos 5 skills de terceros, instalados con `gh skill add OWNER/REPO PATH --dir .agents/skills` (metadatos `github-repo/path/ref/tree-sha` en cada `SKILL.md`, no se editan a mano), con `.claude/skills/` como **copia completa vía rsync** (no symlinks; intencional):

| Skill | Origen | Licencia | ¿Reutilizable en LexiCán? |
|---|---|---|---|
| `github-actions-hardening` | `github/awesome-copilot` `skills/github-actions-hardening` | MIT | Sí |
| `security-audit` | `cloudflare/security-audit-skill` `skills/security-audit` | MIT | Sí (más valor: hay auth y API) |
| `playwright-cli` | `microsoft/playwright-cli` `skills/playwright-cli` | Apache-2.0 | Sí |
| `playwright-trace` | `microsoft/playwright` `packages/playwright-core/src/tools/skills/playwright-trace` | Apache-2.0 (+ NOTICE) | Sí |
| `test-gap-audit` | `github/awesome-copilot` `skills/test-gap-audit` | MIT | Sí |

Ficheros de apoyo: `.agents/README.md` (catálogo), `.agents/upstream-skills.txt` (línea `repo path licencia aviso` para reinstalar), `.agents/licenses/*.txt` (MIT de GitHub y Cloudflare, Apache-2.0 de Playwright y playwright-cli, NOTICE de Playwright). Reinstalar desde origen, no copiar de los repos hermanos.

## 10. Rama `upstream` y reglas de repositorio

- `upstream` = un único commit «Initial commit: original vendor source» con el código del proveedor; `main` desciende de ella. No se modifica, rebasa ni publica. Los informes comparan `origin/upstream` vs `HEAD`. Si el original necesita artefactos no versionados para arrancar, se guardan en un commit aparte en `main` y se cita su SHA (Tonga `8f23fce`).
- Rulesets de Tonga (no branch protection clásica): **«main: pull requests only»** (deletion, non_fast_forward, pull_request con 1 aprobación, dismiss stale reviews; bypass de admin solo vía PR) y **«upstream: read-only»** (update, deletion, non_fast_forward). Falta en Tonga exigir el status check `CI`; Aritmates lo pide en AGENTS.md. Para LexiCán: añadir `required_status_checks` con `CI` al ruleset de main.
- LexiCán hoy: `origin/upstream` (`c80ff65`) es la referencia legacy congelada. Falta aplicar los dos rulesets.

## 11. Documentación: estructura y tono

Tono: español, frases cortas, declarativas, sin marketing; decisiones con fecha y evidencia; deuda explícita; cifras siempre de un comando reproducible. Tablas para inventarios. Enlaces relativos entre docs.

- `docs/ARCHITECTURE.md` (Tonga, ~60 líneas): Capas · Decisiones clave · Flujo de un cambio · Rutas y despliegue · Errores. (Aritmates: Entrada · UI · Aplicación · Motor · Configuración · Build · Tests · Seguridad.)
- `docs/TESTING.md`: tabla «Nivel | Herramienta | Dónde | Comando», qué cubre cada nivel en viñetas, fixtures, visual, «En CI» (orden exacto de pasos). Aritmates añade Golden master · Semilla · Reproducir un fallo · Calidad incremental.
- `docs/SIMPLIFICACION.md` (Aritmates): Objetivos · Arquitectura · Componentes · Utilidades propias · Fases realizadas · **Qué no se ha hecho (a propósito)** · Despliegue · Autoría.
- `docs/INFORME-MODERNIZACION.md` (Aritmates, 240 líneas): 1 Objeto · 2 Conclusión · 3 Resumen de la valoración · 4 Cifras · 5 Mejoras aplicadas (Despliegue y rendimiento, Seguridad, Calidad, Pruebas e IC, Errores corregidos) · 6/7 Valoración crítica original/actual · 8 DAFO · 9 Propuestas · Anexo A Metodología · Anexo B Métricas.
- Tonga separa `MODERNIZATION.md` (narrativa: Punto de partida · Qué se encontró · Qué se hizo, por fases [tabla Fase|PR|Contenido] · Desviaciones respecto al plan · Qué cambia para quien lo usa · Qué empeoró o queda pendiente (deuda, tachada al resolverse) · Mediciones) de `MODERNIZATION-REPORT.md` (generado por script).
- Otros de Tonga: `ACCESSIBILITY.md`, `SECURITY.md` (Privacidad · Entradas no confiables · Cabeceras recomendadas · Cadena de suministro · Informar de un problema · Revisión), `LICENSING.md`, `ASSETS.md`, `PROJECT-FORMAT.md`.
- **ADR** (`docs/adr/NNNN-slug.md`, índice en `docs/adr/README.md` con tabla ADR|Decisión): título `# 0001: <decisión>`, `Estado: aceptada (AAAA-MM-DD)`, secciones Contexto · Decisión · Alternativas · Consecuencias; ~20 líneas cada uno. El README del índice dice dónde se comprobaron versiones y licencias (npm, docs oficiales, Context7, GitHub Advisories).
- `README.md` (Tonga): Qué puedes hacer · Instalación y despliegue · Desarrollo · Documentación · Historia · Licencias. `developers.md`: Requisitos · Estructura · Ciclo de trabajo · Publicar una versión · Métricas.
- `CHANGELOG.md`: Keep a Changelog 1.1.0 (es-ES) + SemVer; `## [Unreleased]`; `## [x.y.z] - AAAA-MM-DD`; `### Añadido / Cambiado / Corregido`; entradas orientadas a la persona usuaria.
- Release (developers.md): mover `[Unreleased]` → versión, `npm version x.y.z --no-git-tag-version`, PR `chore: release x.y.z`, fusionar (Pages publica ya el número), `git tag vx.y.z && git push origin vx.y.z` → `release.yml`.

## 12. AGENTS.md: encabezados

**Tonga** (más completo; recomendado como plantilla):

```text
# Guía para agentes
## Qué es
## Ramas
## Tecnologías permitidas
## Arquitectura
## Comandos
## Tests
## Licencias
## Biblioteca de imágenes
## CI y despliegue
## Commits y pull requests
## Idioma
## Dónde investigar
## Skills
```

**Aritmates**:

```text
# Guía para agentes
## Qué es
## Comportamiento que se conserva
## Cómo está partido el código
## Comandos
## Documentación
## Skills
## Integración
```

Frases que conviene conservar casi literales: «Si un skill de terceros dice otra cosa, manda este archivo.» · «Una dependencia nueva tiene que quitar más complejidad de la que añade; antes de añadirla se consulta su documentación oficial (y Context7), su licencia, su mantenimiento y sus advisories.» · «No se añaden tests vacíos ni `skip` para ideas futuras.» · «No se atribuyen commits ni PR a agentes de IA.» · «No se introduce X sin un ADR en `docs/adr/` que demuestre que simplifica el producto.» · (Aritmates) sección «Comportamiento que se conserva» con las rarezas legacy fijadas por test.

## 13. Commits y PR

- Commits: Conventional Commits en inglés (`feat:`, `fix:`, `perf:`, `refactor:`, `test:`, `docs:`, `ci:`, `build:`, `chore:`, `chore(deps):`, `lint:`), un cambio lógico, squash-merge con `(#NN)`. Releases: `chore: release x.y.z`. Algunos títulos de docs en español en Aritmates; Tonga los pasa a inglés.
- Ramas: `feat/…`, `fix/…`, `chore/release-x.y.z`, fases `modernize/phase-N-<tema>`.
- Fases de modernización de Tonga como PR grandes: #9 baseline+infra, #10 núcleo, #13 UI, #14 cutover+hardening.
- Cuerpo de PR (Tonga #44, #14):

```markdown
## Summary
<problema en 1-2 frases; viñetas en negrita por cambio; tabla de capturas si cambia la UI>
Not included: <lo que se deja fuera y por qué>

## Tests
- New E2E/unit: <qué fijan>
- `npm run coverage` (N), `npm run e2e` (N), lint, typecheck, `reuse lint` pass.

## Metrics   (cuando procede; cifras de docs/…REPORT.md, generado)
## Licensing  (o "Licensing implications": nuevas licencias o "already covered")
```

## 14. Adopt / Adapt / Don't adopt para LexiCán

Contexto: monorepo npm workspaces (`apps/web` React+Vite, `apps/api` Fastify, `packages/db` Drizzle+PostgreSQL), demo PGlite en Pages bajo `/lexican/`.

### Adopt (tal cual)

- **Actions fijadas por SHA con `# vX.Y.Z`**, `permissions: contents: read` global, escritura por job, `persist-credentials: false`, `timeout-minutes`, concurrencia de CI con cancel. Dependabot las actualiza (grupo `actions`).
- **Pages tras CI vía `workflow_run`** con `event == 'push'`, manual solo desde `main`, `merge-base --is-ancestor`, concurrencia `pages` sin cancel, `pages/id-token` solo en `deploy`.
- **Release por tag `v*`** que comprueba tag == `package.json`, repite el gate y adjunta SBOM SPDX; release notes generadas. Proceso de `developers.md`.
- **Dependabot de Tonga** (grupos `dev-tooling` minor/patch y `actions`; `ignore` documentados con ADR).
- **Codecov por OIDC** (`id-token: write`, `use_oidc`, `fail_ci_if_error: false`) + `codecov.yml` informativo de Aritmates; el gate son los thresholds locales.
- **Fixture E2E `auto`** que falla ante `pageerror`, `console.error` y peticiones fuera de la app; **axe** con tags WCAG 2.2 AA por pantalla y por rol; galería visual no bloqueante.
- **Licencias**: `AGPL-3.0-or-later` (si así se decide para LexiCán, con decisión fechada en `docs/LICENSING.md`), `LICENSES/`, `REUSE.toml` + `pipx run reuse==6.2.0 lint` en CI, `THIRD_PARTY_NOTICES.md`, `scripts/licenses.mjs` con allow-list de runtime, `npm run sbom`.
- **`upstream` read-only** + ruleset `main` solo por PR (añadir check `CI` obligatorio).
- **Skills**: los 5 mismos, instalados con `gh skill add` desde su origen, `.agents/` canónico + copia rsync en `.claude/`, `upstream-skills.txt`, `licenses/`, overrides en `REUSE.toml`.
- **Docs**: ADR con Contexto/Decisión/Alternativas/Consecuencias; `TESTING.md` con tabla de niveles; `MODERNIZATION.md` (narrativa + deuda explícita) separado del informe generado; Keep a Changelog en español; idioma (docs ES, código/commits/PR EN); sin atribución de IA.
- **Makefile** como alias finos de `npm run` con `help` autogenerado.
- **Métricas desde git** (`git ls-tree`, `git grep -c`) `origin/upstream` vs `HEAD`, y un `report.mjs` que escribe el informe sin cifras a mano.

### Adapt

- **CI**: añadir `services: postgres` (imagen fijada por digest) para tests de integración de la API y migraciones Drizzle (`drizzle-kit migrate` + `check`), y ejecutar el mismo suite de repositorio contra PGlite. Pasos por workspace (`npm run -ws --if-present lint/typecheck/test`). E2E: dos proyectos/modos — demo PGlite sobre `dist` estático y producción web+api+Postgres. Considerar solo Chromium+Firefox+WebKit para la demo y Chromium para el modo API si el tiempo de CI pesa.
- **`check-dist.mjs`**: comprobar que `apps/web/dist` funciona bajo `/lexican/` (Vite `base: '/lexican/'` en demo o `'./'`; con React Router hace falta `basename` y un `404.html` de fallback SPA para Pages), que no hay secretos/URLs de API en el bundle demo, sin `.map`, sin scripts inline, y el tamaño del WASM de PGlite.
- **Pages**: el build de la demo es un modo (`VITE_MODE=demo` o `--mode demo`) del mismo `apps/web`; el paso `npm run build -w apps/web -- --mode demo`. No usar el truco de Aritmates de reescribir `config.json`.
- **Release**: además del ZIP de la demo, publicar la imagen/artefacto de la API si se decide (p. ej. `docker build` + GHCR con `packages: write` solo en ese job) o solo el tarball; SBOM con `--workspaces`.
- **Cobertura**: thresholds por workspace sobre la lógica de dominio y la capa de datos (`packages/db`, servicios de `apps/api`); la UI React la cubren E2E. Gate en dos niveles al estilo Aritmates mientras se sanea.
- **ESLint**: flat config de Tonga + `eslint-plugin-react-hooks`; usar `no-restricted-imports` para fronteras (web no importa `pg`/`fastify`; solo `packages/db` importa `drizzle-orm`). Sin reglas de estilo (no Prettier salvo decisión).
- **tsconfig**: base compartido (`tsconfig.base.json`) con las opciones de Tonga (`strict`, `noUncheckedIndexedAccess`…), y `tsc -b` por workspace (o `--noEmit` por paquete).
- **Lighthouse** tras Pages, informativo, con URL `https://ateeducacion.github.io/lexican/`.
- **Golden master/caracterización** (Aritmates): caracterizar el legacy (respuestas HTTP, consultas, datos migrados) antes de reescribir; regenerar fixtures solo con diff revisado en PR.
- **`allowScripts`**: mantenerlo; PGlite y esbuild pueden requerir permisos explícitos — revisar.
- **AGENTS.md**: estructura de Tonga, con secciones nuevas: «Modos de ejecución (producción / demo PGlite)», «Base de datos y migraciones», «Seguridad y privacidad (auth, permisos, datos del alumnado)», y «Comportamiento que se conserva» de Aritmates.

### Don't adopt

- **Actions por tag** (Aritmates) ni el `update-agent-skills.yml` con actions sin SHA tal cual: si se adopta, fijarlo por SHA. Es opcional; Dependabot no actualiza skills.
- **`release: published` con `contents: write` y sin repetir checks de tag** (Aritmates release) — usar el de Tonga.
- **Reescribir `config.json` en Pages** (Aritmates) — usar variables de build de Vite.
- **Service worker/PWA propio** de Tonga — no hasta que haya necesidad; PGlite + IndexedDB ya da persistencia, y un SW añade riesgos de caché con migraciones de esquema.
- **ESLint con globals a mano** y reglas «solo corrección» de Aritmates — es una concesión al legacy JS.
- **c8 + bundle de esbuild con `--exclude`** (Aritmates) — Vitest v8 con `include` lo hace sin trucos.
- **ADR 0001 «Sin framework SPA»** de Tonga — LexiCán sí tiene muchas vistas y estado de servidor; React está justificado, pero redactar su propio ADR.
- **Servidor estático propio `scripts/serve.mjs`** — con Vite, `vite preview --base /lexican/` basta para E2E de la demo (menos código). Mantenerlo solo si `vite preview` no reproduce Pages.
- **`.RECIPEPREFIX`** de Aritmates — innecesario.
