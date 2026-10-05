# Pruebas

## Niveles

| Nivel | Herramienta | Dónde | Comando |
|---|---|---|---|
| Unitarias de dominio | Vitest | `packages/core/src/*.test.ts` (curso escolar y vigencia, texto, contratos Zod, exportación DMLex validada con Ajv) | `bun run test` |
| Contrato de aplicación | Vitest + PGlite + PostgreSQL (Bun.SQL) | `packages/app/src/services.contract.test.ts`, `demo-seed.test.ts` | `bun run test:contracts` |
| Contrato de drivers y migraciones | Vitest + PGlite + PostgreSQL (Bun.SQL) | `packages/db/src/drivers.contract.test.ts`, `migrate.test.ts` | `bun run test` |
| API compartida | Vitest | `packages/http/src/cas.test.ts` (URLs, XML, DTD, éxito inequívoco, redirecciones, *timeout*, tamaño), `range.test.ts` (200/206/416) | `bun run test` |
| Integración del servidor (Hono + PostgreSQL) | Vitest + `app.fetch` (helper `inject`) | `apps/api/src/app.test.ts` (sesiones, `Origin`, 401/403/404/409, validación, subidas en *streaming* sin `Content-Length`, rangos y `HEAD`, CAS/SLO, tickets repetidos, SPA), `app.variants.test.ts` (HTTPS, perfiles CAS, emisores), `adapters.test.ts` (CAUCE, volumen de medios, `APP_ENV`/`CAS_*`), `server.test.ts` (arranca **Bun** de verdad) | `bun run test:integration` |
| Transporte del Worker (Hono + PGlite) | Vitest | `apps/web/src/demo/transport.test.ts`: el bucle real del Worker y la API Hono sobre PGlite con un `Worker` simulado; JSON, errores, sesión, multipart y binarios, concurrencia, *timeout* sin reintento, cancelación, caída, errores de arranque; y que React no llama a los servicios | `bun run test` |
| Medios | Vitest | `packages/app/src/media.test.ts`: tipos por contenido con audios sintéticos (`fixtures/media/`) | `bun run test` |
| Migración legacy | Vitest + MariaDB + PostgreSQL | `tools/legacy-migrator/src/transform.test.ts` (transformaciones puras), `migrate.test.ts` (fixture MariaDB → migrador → PostgreSQL) | `bun run test:migration` |
| E2E demo | Playwright | `e2e/*.spec.ts` sobre el build de Pages en `/lexican/`, servido por un servidor estático estricto; `media.spec.ts` (audio: subir, reproducir, pausar, desplazarse, recargar, volver a entrar), `student.spec.ts` (acceso sin CAS), `demo-engine.spec.ts` (segunda pestaña) | `bun run e2e:demo` |
| E2E producción | Playwright | `e2e/smoke-prod.spec.ts` (y los demás) contra web + API + PostgreSQL | `E2E_DATABASE_URL=… bun run e2e` |

`bun run test` ejecuta **todas** las pruebas Vitest (`{apps,packages,tools}/*/src/**/*.test.ts`); los otros comandos son
subconjuntos por carpeta.

### Contrato de persistencia

La misma batería recorre la aplicación completa (crear entradas, enviar, publicar, rechazar, comentar, permisos,
conflictos) contra:

- **PGlite** siempre;
- **PostgreSQL real** si existe `TEST_DATABASE_URL` (cada ejecución crea y borra su propia base dentro de ese
  servidor, `packages/db/src/testing.ts`).

Los contratos de drivers también comprueban JSONB, arrays, UUID, enum, fechas, índices parciales, rollback y 100
transacciones concurrentes en PostgreSQL. Así se comprueba que el único esquema y las mismas migraciones se comportan igual en la demo y en producción
([ADR 0003](adr/0003-drizzle-pglite.md)). Los tests de la API también usan PostgreSQL si `TEST_DATABASE_URL` está
definido y PGlite si no.

### Migración

`migrate.test.ts` solo se ejecuta con `TEST_MARIADB_URL` **y** `TEST_DATABASE_URL`; carga *fixtures* ficticios de
`fixtures/legacy/` en MariaDB, migra, comprueba recuentos, relaciones e idempotencia, y que no llega ningún
identificador nacional. Sin esas variables se omite. Ver [MIGRATION.md](MIGRATION.md).

### E2E

- Proyectos: `demo-chromium`, `demo-firefox`, `demo-webkit`, `demo-mobile` (Pixel 7, solo pruebas marcadas
  `@mobile`) y, si existe `E2E_DATABASE_URL`, `prod-chromium`.
- Demo: Playwright compila la demo y la sirve con
  `scripts/static-pages-server.mjs` (como GitHub Pages: sin *fallback* ni reescrituras) en
  `http://localhost:4317/lexican/`. Con `E2E_DEMO_PREBUILT=1` prueba el artefacto ya compilado tal cual (Pages).
- `demo-mobile` emula un Pixel 7: **no** demuestra el funcionamiento en un Android real (recorrido manual en
  [DEMO.md](DEMO.md)).
- Producción: `scripts/e2e-prod-server.mjs` crea una base temporal en `E2E_DATABASE_URL`, compila web y API si faltan,
  migra, siembra los datos demo con `AUTH_DEV_LOGIN` y sirve todo **con Bun** en `http://localhost:3100/`. Al terminar borra la base
  y el directorio de medios.
- Cada prueba falla ante un error de página, un `console.error` o una petición fuera del origen
  (`e2e/fixtures.ts`). axe se ejecuta en las pantallas indicadas en [ACCESSIBILITY.md](ACCESSIBILITY.md).
- Recorrido §77 (`journey.spec.ts`): alumno crea y envía → profesora comenta y publica → alumno ve el resultado.
- Fuera de CI se reutiliza un servidor ya arrancado en el mismo puerto.

## Ejecutar en local

```bash
bun ci
bunx --bun playwright install chromium firefox webkit   # una vez

bun run test                     # todo Vitest (PGlite)
bun run e2e:demo             # E2E de la demo en los tres navegadores + móvil
```

Con PostgreSQL (el de `docker-compose.yml`):

```bash
docker compose up -d db
TEST_DATABASE_URL=postgres://lexican:lexican@localhost:5432/postgres bun run test
E2E_DATABASE_URL=postgres://lexican:lexican@localhost:5432/postgres bun run e2e
```

Migración (MariaDB temporal):

```bash
docker run -d --name lexican-mariadb -e MARIADB_ROOT_PASSWORD=root -e MARIADB_DATABASE=legacy -p 53306:3306 mariadb:11
TEST_MARIADB_URL=mysql://root:root@127.0.0.1:53306/legacy \
TEST_DATABASE_URL=postgres://lexican:lexican@localhost:5432/postgres bun run test:migration
```

Depurar un E2E: `bunx --bun playwright test e2e/student.spec.ts --project=demo-chromium --headed`; las trazas de fallos
quedan en `test-results/` (`trace: 'retain-on-failure'`) y se abren con `bunx --bun playwright show-trace`.

## Cobertura

`bun run test:coverage` (Vitest v8) mide `packages/*/src`, `apps/api/src` y `tools/*/src`, excluidos los tests.
Informe `text-summary` en consola y `lcov` en `coverage/`. **Umbral bloqueante: 90 %** en líneas, sentencias,
funciones y ramas (`vitest.config.ts`); en CI se ejecuta con PostgreSQL y MariaDB para incluir el contrato y el migrador.
Codecov (`codecov.yml`) publica en cada PR la cobertura del proyecto y del parche, ambas con objetivo del 90 %, y el
badge del README.

La cifra **no mide**:

- `apps/web` (React): lo cubren los E2E, que no cuentan en la cobertura;
- el comportamiento contra el CAS y CAUCE reales (solo XML grabado ficticio);
- la migración si no se ejecuta con MariaDB (`migrate.test.ts` se omite);
- la concurrencia en producción más allá del contrato de 100 transacciones sobre PostgreSQL (PGlite tiene una sola conexión);
- un Android real (solo emulación de Pixel en Playwright);
- que algo sea accesible o se vea bien: eso es axe más revisión manual.

## En CI

`.github/workflows/ci.yml`, en cada PR y en cada *push* a `main`:

| Job | Servicios | Pasos |
|---|---|---|
| `quality` | PostgreSQL 18 + MariaDB 11 | `bun ci` → `check:lockfile` → `audit` → `licenses` → `lint` (Biome) → `typecheck` → `test:coverage` (con `TEST_DATABASE_URL` y `TEST_MARIADB_URL`; umbral 90 %) → subida a Codecov → `build` → `build:demo` → `check:dist` → instalación de navegadores → `bun run e2e` (demo Chromium, Firefox y móvil; producción Chromium) → informe de Playwright como artefacto si falla |
| `webkit` | — | E2E de la demo en WebKit (un *worker*). **Desactivado temporalmente** (`if: false`): tarda más de 17 min y se va a revisar en una PR propia; en local, `bun run e2e:webkit` |
| `docker` | Compose: PostgreSQL 18 | workflow compartido `docker.yml`: construye una imagen, comprueba que sin configuración no arranca, levanta el perfil local con `--no-build` (salud, proveedores, SPA e ID de imagen) y guarda la imagen probada como artefacto de un día |
| `migration` | PostgreSQL 18 + MariaDB 11 | `bun ci` → `bun run test:migration` |
| `publish-image` | — | solo en `main`, tras `quality`, `docker` y `migration`: carga el artefacto probado, verifica su ID y publica `ghcr.io/ateeducacion/lexican:main` sin reconstruir |

Las *releases* ([DEPLOYMENT.md](DEPLOYMENT.md)) ejecutan su gate de audit, licencias, lint, tipos, tests con PostgreSQL,
builds y `check:dist`, además de la prueba Docker compartida. No repiten los E2E ni el contrato MariaDB del CI.
Pages tiene su vía rápida (`pages.yml`: tipos,
build, `check:dist` y E2E esenciales del mismo artefacto que publica; [DEMO.md](DEMO.md)).

## Qué prueba exige cada cambio

| Cambio | Prueba mínima |
|---|---|
| Regla de dominio (`packages/core`) | unitaria con reloj inyectado |
| Operación o servicio nuevo (`packages/app`) | caso en `services.contract.test.ts` (permisos incluidos) |
| Ruta HTTP, sesión, cabeceras, subidas | `apps/api/src/app.test.ts` (y `transport.test.ts` si afecta al transporte de la demo) |
| Esquema / migración | contrato en PGlite y PostgreSQL; `migrate.test.ts` del paquete `db` |
| Transformación del migrador | `transform.test.ts` y, si cambia el recorrido, `migrate.test.ts` |
| Pantalla o flujo | E2E con `expectAccessible` cuando sea una pantalla nueva |

No se añaden tests vacíos ni `skip` para ideas futuras.
