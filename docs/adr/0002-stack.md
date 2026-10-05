# ADR 0002 — Stack: TypeScript, React + Vite, Hono, Bun y PostgreSQL

- Estado: aceptada (revalida §105 de `1er-prompt.md`)
- Fecha: 2026-10-05

## Decisión

Antes de la primera etiqueta de versión, esta decisión recoge el stack vigente; las decisiones especializadas
explican sus límites sin repetir versiones históricas.

| Pieza | Versión | Motivo |
|---|---|---|
| Bun | 1.4.2 | gestor de paquetes, runtime de la API y bundle autosuficiente del servidor |
| Node.js | >=24; CI en 24 | ejecuta Vite, Vitest y Playwright |
| TypeScript | ~6.0 | comprobación estática con `tsc --noEmit`; una actualización mayor requiere revisión propia |
| Biome | 2.5.15 | lint, formato y orden de importaciones |
| React / Vite | 19.3 / 8.3 | interfaz común a producción y demo; build estático con `base` configurable |
| react-router | 8.4, modo datos | loaders y revalidación; hash en la demo y rutas normales en producción |
| Hono | ^4.13.13 | la misma API en Bun y en el Worker; [ADR 0009](0009-hono-bun-worker.md) |
| Drizzle ORM / drizzle-kit | 1.0.0-rc.4 / 1.0.0-rc.4, exactas | esquema y migraciones compartidos; [ADR 0003](0003-drizzle-pglite.md) |
| PostgreSQL / PGlite | 18 / 0.5.8 | producción / demo y tests |
| Zod | 4.6 | contratos comunes a formularios, API, semillas e importación |
| Vitest / Playwright | 5.0 / 1.63 + axe | pruebas unitarias, contratos y E2E |

Workspaces de Bun, sin herramientas adicionales de monorepo. `bun.lock` permanece en `lockfileVersion` 1 para
Dependabot; Bun 1.4.2 conserva ese formato. `bun run check:lockfile` lo comprueba. `linker = "hoisted"` mantiene la
estructura esperada por las herramientas. Bun instala con `bun ci`, comprueba avisos con `bun audit` y enumera
licencias con `bun pm licenses`; `scripts/sbom.mjs` genera el SBOM SPDX.

```text
packages/core   dominio puro + Zod + tabla de operaciones
packages/db     esquema Drizzle, migraciones SQL, semillas de vocabularios
packages/app    servicios de aplicación y autorización sobre Drizzle
packages/http   API Hono, sesiones, CAS y medios; solo APIs web
apps/web        React, cliente HTTP o transporte al Worker de la demo
apps/api        servidor Bun, cookies, CAUCE, volumen de medios y SPA
tools/legacy-migrator  MariaDB → PostgreSQL, solo para migrar
```

## Fronteras y simplificación

Se agrupan dominio, contratos, autenticación y pruebas en paquetes con fronteras reales: `core` comparte reglas sin
base de datos; `db` declara el esquema; `app` aplica los casos de uso; `http` los expone. Los helpers de bases de
pruebas viven en `packages/db/src/testing.ts`. Ningún paquete compartido usa APIs de Bun ni configuración del servidor.

Los loaders y la revalidación del router cubren lectura, mutación y refresco. CSS propio con variables y CSS Modules.
Sin TanStack Query, Redux ni framework visual. Las dependencias actuales están en los `package.json` y `bun.lock`;
no se añade infraestructura sin una decisión que demuestre que simplifica el producto.
