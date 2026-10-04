# ADR 0002 — Stack: TypeScript, React + Vite, Fastify, PostgreSQL, npm workspaces

- Estado: aceptada (revalida §105 de `1er-prompt.md`)
- Fecha: 2026-10-04

## Decisión

| Pieza | Versión verificada (npm, 2026-10-04) | Motivo |
|---|---|---|
| Node.js | 24 LTS (motor `>=24`; CI en 24) | LTS vigente; Node 26 pasa a LTS el 2026-10-28 y se adoptará tras verificarlo |
| TypeScript | `~6.0.3` | `typescript-eslint` 8.71 exige `<6.1`; TypeScript 7.0 (nativo en Go) aún no expone API de compilador |
| React | 19.3 | — |
| Vite | 8.3 (Rolldown) | build estático con `base` configurable |
| react-router | 8.4, modo datos | `createHashRouter` en la demo y `createBrowserRouter` en producción; loaders en lugar de TanStack Query |
| Fastify | 5.12.5 (mínimo por avisos del 2026-09-30) | API REST pequeña |
| Zod | 4.6 | un único esquema para formularios, API, seeds e importación |
| Vitest | 5.0 | — |
| Playwright | 1.63 + `@axe-core/playwright` 4.13 | E2E en Chromium, Firefox y WebKit |

Monorepo con npm workspaces, sin herramientas de monorepo adicionales:

```text
packages/core   dominio puro + contratos Zod + tabla de operaciones (sin dependencias salvo zod)
packages/db     esquema Drizzle, migraciones SQL, semillas de vocabularios
packages/app    servicios de aplicación con autorización (Drizzle; corre en Node y en el navegador)
apps/web        React (HttpClient en producción, cliente PGlite en la demo)
apps/api        Fastify (sesiones, CAS, subida de medios)
tools/legacy-migrator  MariaDB → PostgreSQL, solo para la migración
```

## Simplificación respecto a la propuesta inicial

La propuesta de §9 tenía `domain`, `application`, `contracts`, `db`, `auth` y `testing`. Se agrupan en tres paquetes
con fronteras reales: `core` (lo que comparten navegador y servidor sin base de datos), `db` (esquema) y `app` (casos de
uso). `auth` es una función de `app` y de `apps/api`; `testing` son dos funciones en `packages/db/src/testing.ts`.

## Sin TanStack Query, Redux ni frameworks visuales

Los loaders y la revalidación del router cubren lectura, mutación y refresco. CSS propio con *custom properties* y
CSS Modules. TanStack también sufrió un incidente de malware en mayo de 2026 (GHSA-g7cv-rxg3-hmpx).
