# Guía para agentes

Reglas para cualquier agente (o persona) que trabaje en este repositorio. Si un skill o una herramienta dice otra
cosa, manda este archivo.

## Qué es

LexiCán: diccionarios personales y de aula para aprender léxico en centros educativos de Canarias. El alumnado crea
entradas con acepciones y medios, las envía a un diccionario de aula y el profesorado las revisa, comenta y publica.
Se escribe **LexiCán** en la interfaz y la documentación; los identificadores son `lexican`.

Esta versión (2.x) es una reconstrucción completa del Laravel 8 + Voyager original
([ADR 0001](docs/adr/0001-reconstruccion.md), [MODERNIZATION.md](docs/MODERNIZATION.md)).

## Ramas

- `upstream`: copia histórica del código del proveedor (`c80ff65`). **Intocable**: ni commits, ni *rebase*, ni
  *force-push*, ni borrado. Contiene credenciales filtradas que no se reescriben ([SECURITY.md](docs/SECURITY.md)).
- `main`: rama mantenida. Solo se cambia por PR, con CI en verde.
- Ramas de trabajo: `feat/…`, `fix/…`, `docs/…`, `chore/…`. PRs pequeñas, un cambio lógico.
- El árbol legacy (`app/`, `resources/`, `routes/`, `public/`, `database/`, `config/`…) está congelado: no se edita y se
  elimina entero en la fase de limpieza. La referencia del legacy es siempre `upstream`.

## Tecnologías

| Pieza | Versión | Nota |
|---|---|---|
| Node.js | `>=24` (CI en 24) | |
| TypeScript | `~6.0` | `typescript-eslint` 8.x exige `<6.1`; TypeScript 7 no se adopta hasta que lo soporte. Dependabot lo ignora |
| React / Vite / react-router | 19.3 / 8.3 / 8.4 | router en modo datos; sin TanStack Query ni Redux |
| Fastify | `^5.12.5` | mínimo por avisos de seguridad |
| Drizzle ORM / drizzle-kit | `^0.45.3` / `^0.31.11` | v1 cambia las migraciones: *majors* a mano |
| PostgreSQL / PGlite | 18 / 0.5.8 | |
| Zod | 4 | un esquema para formularios, API, seeds e importación |
| Vitest / Playwright | 5 / 1.63 + axe | |

Versiones verificadas y motivos: [ADR 0002](docs/adr/0002-stack.md). Una dependencia nueva tiene que quitar más
complejidad de la que añade; antes de añadirla se consulta su documentación oficial (y Context7), su licencia, su
mantenimiento y sus avisos de seguridad. No se introduce una pieza de infraestructura (Redis, colas, otro servicio)
sin un ADR que demuestre que simplifica el producto.

## Arquitectura

```text
packages/core   dominio puro: tipos, Zod, reglas (curso, vigencia, plazos), exportación, tabla de operaciones
packages/db     esquema Drizzle, migraciones SQL, semillas de vocabularios, helpers de test
packages/app    servicios de aplicación + autorización (Node y navegador)
apps/web        React + Vite (HttpClient en producción, cliente PGlite en la demo)
apps/api        Fastify: sesiones, CAS/CAUCE, subidas, rutas generadas desde la tabla de operaciones
tools/legacy-migrator  MariaDB → PostgreSQL (solo para migrar)
```

- `packages/core/src/operations.ts` declara cada operación una vez (verbo, ruta, entrada Zod). De ahí salen las rutas
  Fastify, el cliente HTTP y el cliente de la demo. Una funcionalidad = entrada en la tabla + servicio en
  `packages/app` + prueba de contrato + UI.
- Las rutas no deciden permisos: solo *sesión → validación → servicio → respuesta*.
- `packages/core` no importa React, Fastify ni Drizzle.
- Sin interfaces de repositorio: los servicios usan Drizzle directamente ([ADR 0003](docs/adr/0003-drizzle-pglite.md)).

Detalle: [ARCHITECTURE.md](docs/ARCHITECTURE.md), [DATA-MODEL.md](docs/DATA-MODEL.md).

## Demo y producción

- **Producción**: un proceso Node (API + SPA) + PostgreSQL + volumen de medios. Login CAS + CAUCE.
- **Demo** (GitHub Pages, `/lexican/`): el mismo frontend con `vite build --mode demo`; ejecuta `packages/app` sobre
  PGlite en IndexedDB. Cuentas ficticias. Enrutado por *hash*.
- El modo se decide en el build (`import.meta.env.MODE`), **nunca** por el nombre del host.
- El build de producción no puede contener PGlite, WASM ni contraseñas demo (`npm run check:dist`).
- La demo no es una frontera de seguridad y no debe aparentarlo ([DEMO.md](docs/DEMO.md)).

## Base de datos y migraciones

- **PostgreSQL es la única base de producción.** PGlite solo en la demo y en los tests.
- Un único esquema: `packages/db/src/schema.ts`. Sin extensiones de PostgreSQL.
- Cambiar el esquema: editar `schema.ts` → `npm run db:generate` (drizzle-kit genera SQL en `packages/db/migrations/`)
  → **revisar el SQL** → commit del SQL y del *snapshot*. **Nunca `drizzle-kit push`.** Nunca editar una migración ya
  publicada en `main`.
- La demo aplica las mismas migraciones en el navegador (`migrateBundled`). Si un cambio rompe los datos guardados en
  la demo, subir `DATA_DIR` en `apps/web/src/demo/db.ts`.
- Tablas que reciben datos del legacy: columnas `legacy_source` + `legacy_id` con índice único; no se exponen en la
  API.
- Borrado lógico con `deleted_at`; estados como `enum`, no códigos mágicos.

## Autenticación y autorización

- Producción: CAS 3.0 propio + adaptador CAUCE (`InstitutionalDirectory`). TLS verificado, XML sin DTD, URL de
  servicio fija ([AUTHENTICATION.md](docs/AUTHENTICATION.md)).
- Sesiones en PostgreSQL (hash del identificador), cookie `__Host-sid` `HttpOnly; Secure; SameSite=Lax`. Nada en
  `localStorage` salvo el marcador de usuario de la demo.
- Mutaciones solo con `POST`/`PUT`/`PATCH`/`DELETE`; `Origin` comprobado.
- `AUTH_DEV_LOGIN` (contraseña) solo en desarrollo y tests; prohibido con `NODE_ENV=production`.
- Toda comprobación de permisos vive en `packages/app` (`access.ts`, `requireUser`, `requireRole`). Cada operación
  nueva tiene prueba de permisos.
- Contenido de usuario en texto plano; nada de `dangerouslySetInnerHTML`.

## Secretos y datos

- Ningún secreto, token, host interno, correo o nombre real en código, tests, *fixtures*, capturas, docs o commits.
- Configuración solo por variables de entorno validadas en `apps/api/src/config.ts`; ejemplo en
  `apps/api/.env.example` con valores ficticios.
- Nada del legacy (`.env.example`, *seeders*, `readme.md` histórico) se copia: contiene credenciales y hosts.
- Datos de demo y de tests: ficticios (`@ejemplo.com`, «IES Ficticio…»).
- Logs sin cookies, tickets, tokens ni respuestas de CAUCE.

## Tests obligatorios

| Cambio | Prueba |
|---|---|
| Regla de dominio | unitaria en `packages/core` con reloj inyectado |
| Operación / servicio | `packages/app/src/services.contract.test.ts` (PGlite y PostgreSQL), incluidos permisos |
| HTTP, sesión, subidas, cabeceras | `apps/api/src/app.test.ts` |
| Esquema | contrato en ambos drivers |
| Migrador | `tools/legacy-migrator/src/*.test.ts` |
| Pantalla o flujo | E2E Playwright (con `expectAccessible` si es pantalla nueva) |

No se añaden tests vacíos ni `skip` para ideas futuras. Los E2E fallan ante errores de consola y peticiones externas;
no se silencian. Detalle: [TESTING.md](docs/TESTING.md).

## Migración legacy

- Herramienta aparte (`tools/legacy-migrator`, `npm run migrate:legacy`): idempotente, con `--dry-run` e informe.
- No hay escritura doble ni sincronización con el legacy.
- No migra NIF/NIE, CIAL ni pasaporte. Los ids legacy no se reutilizan como ids nuevos.
- Ensayos con datos reales solo fuera del repositorio sobre una copia autorizada.
- [MIGRATION.md](docs/MIGRATION.md), [LEGACY-DATA-MAPPING.md](docs/LEGACY-DATA-MAPPING.md).

## Privacidad

Datos de menores: mínimo necesario, sin analítica, sin CDNs ni fuentes externas, retención documentada
([PRIVACY.md](docs/PRIVACY.md)). Cualquier columna nueva con datos personales se añade al inventario de PRIVACY.md.

## Accesibilidad

WCAG 2.2 AA. HTML nativo antes que ARIA, etiquetas reales, foco visible, sin depender solo del color, alternativa de
teclado a cualquier arrastre. Cero errores de axe no basta: checklist manual en
[ACCESSIBILITY.md](docs/ACCESSIBILITY.md).

## Licencias

- LexiCán es **AGPL-3.0-or-later** ([LICENSE](LICENSE), [LICENSING.md](docs/LICENSING.md)). Todo fichero nuevo se
  publica bajo esa licencia; un `package.json` nuevo declara `"license": "AGPL-3.0-or-later"`.
- Código o contenidos de terceros solo con licencia compatible con AGPL-3.0-or-later y comprobada (un repositorio
  público sin licencia no autoriza a copiar). Registrar proyecto, URL, versión/commit, titular, licencia y cambios en
  `THIRD_PARTY_NOTICES.md`.
- Dependencias de producción: solo licencias de la lista de `scripts/licenses.mjs`; ampliarla exige justificar la
  compatibilidad en la PR.

## Comandos

```bash
npm ci
docker compose up -d db          # PostgreSQL local
cp apps/api/.env.example apps/api/.env
set -a; . apps/api/.env; set +a  # las CLI y la API leen el entorno, no el fichero
npm run db:migrate && npm run db:seed -- --demo
npm run dev:api                  # API en :3000
npm run dev                      # web en :5173 contra la API
npm run dev:demo                 # demo PGlite, sin backend
npm run check                    # lint + format:check + typecheck + test
npm run e2e:demo                 # E2E de la demo
npm run build && npm run build:demo && npm run check:dist
npm run db:generate              # nueva migración tras editar schema.ts
npm run audit && npm run licenses
```

`make help` lista los atajos. Más en [developers.md](developers.md).

## CI y despliegue

- `ci.yml` en cada PR y *push* a `main`; `pages.yml` despliega la demo solo tras CI correcto en `main`; `release.yml`
  con etiquetas `v*`.
- Actions fijadas por SHA con comentario de versión, `permissions: contents: read` por defecto, escritura solo en el
  job que la necesita, `persist-credentials: false`. Nada de `continue-on-error` en checks.
- Despliegue de producción: [DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Commits y pull requests

- En **inglés**, Conventional Commits (`feat:`, `fix:`, `docs:`, `test:`, `refactor:`, `build:`, `ci:`, `chore:`,
  `chore(deps):`), un cambio lógico por commit.
- PR con título y descripción en inglés: *Summary*, *Tests*, capturas si cambia la UI, migraciones de esquema
  destacadas, implicaciones de datos, seguridad y licencias, y vuelta atrás cuando aplique.
- **No se atribuyen commits ni PR a agentes de IA** (sin `Co-Authored-By` ni firmas de herramientas).
- No trabajar directamente en `main`; no tocar `upstream`.

## Idioma

Documentación para personas e interfaz en **español**. Código, identificadores, comentarios, commits y PR en
**inglés**.
