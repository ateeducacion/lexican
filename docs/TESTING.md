# Pruebas

## Niveles

| Nivel | Herramienta | Dónde | Comando |
|---|---|---|---|
| Unitarias de dominio | Vitest | `packages/core/src/*.test.ts` (curso escolar y vigencia, texto, contratos Zod, exportación DMLex validada con Ajv) | `npm test` |
| Contrato de persistencia y aplicación | Vitest + PGlite + PostgreSQL | `packages/app/src/services.contract.test.ts`, `demo-seed.test.ts`, `packages/db/src/migrate.test.ts` | `npm run test:contracts` |
| Integración de la API | Vitest + `app.inject` de Fastify | `apps/api/src/app.test.ts` (sesiones, `Origin`, 401/403/404/409, validación, subidas, CAS/SLO, SPA), `adapters.test.ts` (XML CAS/CAUCE, almacenamiento de medios, configuración) | `npm run test:integration` |
| Migración legacy | Vitest + MariaDB + PostgreSQL | `tools/legacy-migrator/src/transform.test.ts` (transformaciones puras), `migrate.test.ts` (fixture MariaDB → migrador → PostgreSQL) | `npm run test:migration` |
| E2E demo | Playwright | `e2e/student.spec.ts`, `e2e/journey.spec.ts` sobre el build de Pages en `/lexican/` | `npm run e2e:demo` |
| E2E producción | Playwright | `e2e/smoke-prod.spec.ts` (y los demás) contra web + API + PostgreSQL | `E2E_DATABASE_URL=… npm run e2e` |

`npm test` ejecuta **todas** las pruebas Vitest (`{apps,packages,tools}/*/src/**/*.test.ts`); los otros comandos son
subconjuntos por carpeta.

### Contrato de persistencia

La misma batería recorre la aplicación completa (crear entradas, enviar, publicar, rechazar, comentar, permisos,
conflictos) contra:

- **PGlite** siempre;
- **PostgreSQL real** si existe `TEST_DATABASE_URL` (cada ejecución crea y borra su propia base dentro de ese
  servidor, `packages/db/src/testing.ts`).

Así se comprueba que el único esquema y las mismas migraciones se comportan igual en la demo y en producción
([ADR 0003](adr/0003-drizzle-pglite.md)). Los tests de la API también usan PostgreSQL si `TEST_DATABASE_URL` está
definido y PGlite si no.

### Migración

`migrate.test.ts` solo se ejecuta con `TEST_MARIADB_URL` **y** `TEST_DATABASE_URL`; carga *fixtures* ficticios de
`fixtures/legacy/` en MariaDB, migra, comprueba recuentos, relaciones e idempotencia, y que no llega ningún
identificador nacional. Sin esas variables se omite. Ver [MIGRATION.md](MIGRATION.md).

### E2E

- Proyectos: `demo-chromium`, `demo-firefox`, `demo-webkit`, `demo-mobile` (Pixel 7, solo pruebas marcadas
  `@mobile`) y, si existe `E2E_DATABASE_URL`, `prod-chromium`.
- Demo: Playwright lanza `npm run build:demo && npm run preview:demo -- --port 4173` y abre
  `http://localhost:4173/lexican/`.
- Producción: `scripts/e2e-prod-server.mjs` crea una base temporal en `E2E_DATABASE_URL`, compila web y API si faltan,
  migra, siembra los datos demo con `AUTH_DEV_LOGIN` y sirve todo en `http://localhost:3100/`. Al terminar borra la base
  y el directorio de medios.
- Cada prueba falla ante un error de página, un `console.error` o una petición fuera del origen
  (`e2e/fixtures.ts`). axe se ejecuta en las pantallas indicadas en [ACCESSIBILITY.md](ACCESSIBILITY.md).
- Recorrido §77 (`journey.spec.ts`): alumno crea y envía → profesora comenta y publica → alumno ve el resultado.
- Fuera de CI se reutiliza un servidor ya arrancado en el mismo puerto.

## Ejecutar en local

```bash
npm ci
npx playwright install chromium firefox webkit   # una vez

npm test                     # todo Vitest (PGlite)
npm run e2e:demo             # E2E de la demo en los tres navegadores + móvil
```

Con PostgreSQL (el de `docker-compose.yml`):

```bash
docker compose up -d db
TEST_DATABASE_URL=postgres://lexican:lexican@localhost:5432/postgres npm test
E2E_DATABASE_URL=postgres://lexican:lexican@localhost:5432/postgres npm run e2e
```

Migración (MariaDB temporal):

```bash
docker run -d --name lexican-mariadb -e MARIADB_ROOT_PASSWORD=root -e MARIADB_DATABASE=legacy -p 53306:3306 mariadb:11
TEST_MARIADB_URL=mysql://root:root@127.0.0.1:53306/legacy \
TEST_DATABASE_URL=postgres://lexican:lexican@localhost:5432/postgres npm run test:migration
```

Depurar un E2E: `npx playwright test e2e/student.spec.ts --project=demo-chromium --headed`; las trazas de fallos
quedan en `test-results/` (`trace: 'retain-on-failure'`) y se abren con `npx playwright show-trace`.

## Cobertura

`npm run test:coverage` (Vitest v8) mide `packages/*/src`, `apps/api/src` y `tools/*/src`, excluidos los tests.
Informe `text-summary` en consola y `lcov` en `coverage/`. Todavía **no hay umbrales** que bloqueen CI; el objetivo
(§81) es ~90 % en dominio y aplicación.

La cifra **no mide**:

- `apps/web` (React): lo cubren los E2E, que no cuentan en la cobertura;
- el comportamiento contra el CAS y CAUCE reales (solo XML grabado ficticio);
- la migración si no se ejecuta con MariaDB (`migrate.test.ts` se omite);
- la concurrencia real (PGlite tiene una sola conexión);
- que algo sea accesible o se vea bien: eso es axe más revisión manual.

## En CI

`.github/workflows/ci.yml`, en cada PR y en cada *push* a `main`:

| Job | Servicios | Pasos |
|---|---|---|
| `quality` | PostgreSQL 18 | `npm ci` → `audit` → `licenses` → `lint` → `format:check` → `typecheck` → `test:coverage` (con `TEST_DATABASE_URL`: contrato en PGlite y PostgreSQL + integración de la API) → `build` → `build:demo` → `check:dist` → instalación de navegadores → `npm run e2e` (con `E2E_DATABASE_URL`: demo en 3 navegadores + móvil y producción) → informe de Playwright como artefacto si falla |
| `migration` | PostgreSQL 18 + MariaDB 11 | `npm ci` → `npm run test:migration` |

Pages ([DEMO.md](DEMO.md)) y las *releases* ([DEPLOYMENT.md](DEPLOYMENT.md)) dependen de este gate.

## Qué prueba exige cada cambio

| Cambio | Prueba mínima |
|---|---|
| Regla de dominio (`packages/core`) | unitaria con reloj inyectado |
| Operación o servicio nuevo (`packages/app`) | caso en `services.contract.test.ts` (permisos incluidos) |
| Ruta HTTP, sesión, cabeceras, subidas | `apps/api/src/app.test.ts` |
| Esquema / migración | contrato en PGlite y PostgreSQL; `migrate.test.ts` del paquete `db` |
| Transformación del migrador | `transform.test.ts` y, si cambia el recorrido, `migrate.test.ts` |
| Pantalla o flujo | E2E con `expectAccessible` cuando sea una pantalla nueva |

No se añaden tests vacíos ni `skip` para ideas futuras.
