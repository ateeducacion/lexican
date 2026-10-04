# Guía de desarrollo

Para la versión 2.x (TypeScript). Las instrucciones de la versión Laravel (OpenShift, Composer, NFS) se conservan en la
rama `upstream` y no aplican aquí. Normas del repositorio: [AGENTS.md](AGENTS.md).

## Requisitos

- Bun 1.4.2 (runtime de la API y de sus CLI: <https://bun.com/docs/installation>).
- Node.js 24 o superior (`engines` en `package.json`) y npm: instalación, Vite, Vitest y Playwright.
- Docker, solo para PostgreSQL local (y MariaDB si se prueba la migración).
- Navegadores de Playwright para los E2E: `npx playwright install chromium firefox webkit`.

## Puesta en marcha

```bash
npm ci
cp apps/api/.env.example apps/api/.env        # valores ficticios para desarrollo
make up                                       # = docker compose up -d db (PostgreSQL 18 en :5432)
npm run db:migrate
npm run db:seed -- --demo                     # vocabularios + cuentas y datos ficticios
npm run dev:api                               # API (Bun --watch) en :3000; db:* y dev:api leen apps/api/.env
npm run dev                                   # otra terminal: Vite en :5173, /api y /media van a :3000
```

Entra en <http://localhost:5173> con las cuentas de la demo (`AUTH_DEV_LOGIN=true`) o con «Entrar con CAS de pruebas»
(`alice`/`pwd`, `bob`/`pwd` en el CAS público; el `.env` de ejemplo usa `APP_ENV=local`).

Sin backend: `npm run dev:demo` (<http://localhost:5173/lexican/>): la API Hono y PGlite en un Web Worker.

Imagen completa (PostgreSQL + API Bun + SPA): `docker compose --profile app up --build` → <http://localhost:3000>, con
`APP_ENV=local`: migra, siembra los datos ficticios y activa el CAS de pruebas y el acceso con contraseña. Producción
usa `docker-compose.prod.yml` ([DEPLOYMENT.md](docs/DEPLOYMENT.md)).

## Estructura

| Workspace | Paquete | Contenido |
|---|---|---|
| `packages/core` | `@lexican/core` | tipos, esquemas Zod (`contracts.ts`), tabla de operaciones (`operations.ts`), reglas de curso y vigencia (`school-year.ts`), texto (`text.ts`), exportación (`export.ts`), errores de dominio, cuentas demo |
| `packages/db` | `@lexican/db` | esquema Drizzle (`schema.ts`), migraciones (`migrations/`), `migrateBundled`, semilla de vocabularios, helpers de test (`testing.ts`) |
| `packages/app` | `@lexican/app` | servicios de aplicación y autorización (`access.ts`), semilla demo |
| `packages/http` | `@lexican/http` | API Hono compartida (`createApi`), cliente CAS, rangos HTTP |
| `apps/api` | `@lexican/api` | servidor Bun (`server.ts`, `app.ts`): configuración, cookies, cabeceras, IP de confianza, CAUCE, volumen de medios, SPA, CLI de migración y semilla |
| `apps/web` | `@lexican/web` | React: rutas (`router.tsx`), páginas, componentes, cliente común (`api/client.ts`) y motor de la demo en un Web Worker (`demo/`) |
| `tools/legacy-migrator` | `@lexican/legacy-migrator` | migración MariaDB → PostgreSQL |
| `e2e/` | — | pruebas Playwright |
| `scripts/` | — | `check-dist.mjs`, `licenses.mjs`, `metrics.mjs`, `e2e-prod-server.mjs` |

Los paquetes se importan como TypeScript fuente (sin compilar entre ellos); la API se empaqueta con `bun build` en un bundle autosuficiente.

## Scripts

| Script | Qué hace |
|---|---|
| `npm run dev` / `dev:demo` / `dev:api` | Vite (producción), Vite (demo), API con recarga |
| `npm run build` | web (`apps/web/dist`) + API (`apps/api/dist`) |
| `npm run build:demo` / `preview:demo` | demo estática (`apps/web/dist-demo`) / servirla como Pages en http://localhost:4317/lexican/ (sin *fallback*) |
| `npm run check` | `lint` + `format:check` + `typecheck` + `test` |
| `npm run lint` / `format` / `format:check` / `typecheck` | ESLint, Prettier, `tsc` |
| `npm test` / `test:watch` / `test:coverage` | Vitest |
| `npm run test:contracts` / `test:integration` / `test:migration` | Vitest de `packages/app`, `apps/api`, `tools` |
| `npm run e2e` / `e2e:demo` | Playwright: todo / solo proyectos `demo-*` |
| `npm run db:generate` | genera una migración SQL a partir de `schema.ts` |
| `npm run db:migrate` / `db:seed` | aplica migraciones / siembra vocabularios (`-- --demo` añade datos ficticios) |
| `npm run migrate:legacy -- …` | migrador legacy ([docs/MIGRATION.md](docs/MIGRATION.md)) |
| `npm run check:dist` | revisa los bundles (secretos, hosts, base `/lexican/`, PGlite fuera de producción) |
| `npm run audit` / `licenses` / `sbom` | avisos de seguridad / licencias de producción / SBOM SPDX |
| `npm run metrics` | métricas `upstream` vs árbol actual para el informe de modernización |

`make help` muestra los atajos del `Makefile` (envoltorio fino sobre estos scripts).

## Añadir una operación de principio a fin

Ejemplo: «archivar un aula».

1. **Contrato** — `packages/core/src/operations.ts`: añadir `archiveClassroom: op('POST',
   '/api/classrooms/:classroomId/archive', z.object({ classroomId: id }))` y su tipo de salida en `OperationOutputs`.
   Si la entrada es compleja, el esquema va en `contracts.ts`.
2. **Servicio** — en el módulo de `packages/app/src/` que corresponda (`classrooms.ts`): función
   `archiveClassroom(actor, input)` que empiece por la autorización (`classroomAccess` + `assertEditable`), use Drizzle y
   registre `audit(...)` si cambia datos. Errores con `DomainError` (`validation`, `forbidden`, `not_found`,
   `conflict`…). `createServices` comprueba al arrancar que toda operación tiene manejador.
3. **Prueba de contrato** — `packages/app/src/services.contract.test.ts`: el caso feliz y los permisos (alumno → 403,
   ajeno → 404). Se ejecuta en PGlite y, con `TEST_DATABASE_URL`, en PostgreSQL.
4. **API** — nada que hacer: `apps/api/src/app.ts` registra una ruta por operación. Solo si la operación es especial
   (subida, cookies, redirecciones) se escribe a mano, con su prueba en `app.test.ts`.
5. **UI** — en la página, `(await getApi()).archiveClassroom({ classroomId })` (`apps/web/src/api/index.ts`); funciona
   igual con el cliente HTTP y con el de la demo. Mensajes en español; errores con el componente `ErrorMessage`.
6. **E2E** si es un flujo visible; `expectAccessible(page)` si es una pantalla nueva.

## Añadir o cambiar una tabla

1. Editar `packages/db/src/schema.ts`.
2. `npm run db:generate` → nuevo `packages/db/migrations/NNNN_*.sql` + `meta/`.
3. Leer el SQL generado (nombres, índices, `NOT NULL` con datos existentes, valores por defecto). Si hace falta
   migrar datos, añadirlo en el mismo SQL.
4. `npm test` (las migraciones se aplican en PGlite) y con `TEST_DATABASE_URL` (PostgreSQL).
5. Si la tabla contiene datos personales, actualizar [docs/PRIVACY.md](docs/PRIVACY.md); si recibe datos del legacy,
   añadir `legacy_source`/`legacy_id` y actualizar el migrador y [docs/LEGACY-DATA-MAPPING.md](docs/LEGACY-DATA-MAPPING.md).
6. Si el cambio invalida datos ya guardados en la demo, subir `DATA_DIR` en `apps/web/src/demo/db.ts`.

Nunca `drizzle-kit push` ni editar una migración que ya esté en `main`. Detalle en
[docs/DATA-MODEL.md](docs/DATA-MODEL.md).

## Pruebas por nivel

```bash
npm test                                   # todo Vitest en PGlite
npm run test:contracts                     # solo packages/app
npm run test:integration                   # solo la API
TEST_DATABASE_URL=postgres://lexican:lexican@localhost:5432/postgres npm test   # también PostgreSQL
npm run e2e:demo                           # demo, 3 navegadores + móvil
E2E_DATABASE_URL=postgres://lexican:lexican@localhost:5432/postgres npm run e2e  # + producción
npx vitest run packages/core/src/school-year.test.ts     # un fichero
npx playwright test e2e/journey.spec.ts --project=demo-chromium --headed
```

Migración con MariaDB y cobertura: [docs/TESTING.md](docs/TESTING.md).

## Depurar la base de la demo en el navegador

- La base está en IndexedDB: DevTools → *Application* → *IndexedDB* → `/pglite/lexican-demo-v2`. El usuario activo está
  en *Local storage* → `lexican-demo-session`.
- Empezar de cero: botón «Restablecer datos de demostración», o borrar esa base y recargar.
- Para consultar con SQL, reproducir el estado en Node: `openPglite()` (`packages/db/src/testing.ts`) + `seedDemo()`
  (`packages/app`) en un test temporal da la misma base que la demo recién sembrada.
- Los errores de los servicios llegan a la UI como `ApiError` con el mismo `code` que la API (`validation`,
  `forbidden`, `not_found`, `conflict`…).

## Convenciones

- TypeScript estricto (`noUncheckedIndexedAccess`), módulos ES, imports con extensión `.ts`.
- Validación siempre con los esquemas Zod de `packages/core`; nada de validar a mano en la UI o en la API.
- Fechas en UTC en la base; el curso escolar se calcula con `SCHOOL_YEAR_START` y un reloj inyectado.
- Interfaz en español; código, comentarios, commits y PR en inglés.
- Sin `console.log` (ESLint permite `warn` y `error`).
- Commits Conventional Commits en inglés, sin atribución a herramientas de IA.

## Publicar una versión

1. Mover las entradas de `[Sin publicar]` de `CHANGELOG.md` a la nueva versión con fecha.
2. `npm version X.Y.Z --no-git-tag-version` y PR `chore: release X.Y.Z`.
3. Tras fusionar: `git tag vX.Y.Z && git push origin vX.Y.Z`. `release.yml` comprueba que la etiqueta coincide con
   `package.json`, repite el gate y publica los artefactos ([docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)).
