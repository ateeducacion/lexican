# ADR 0012 — `bun check` sustituye a `tsc`

- Estado: aceptada
- Fecha: 2026-11-22
- Modifica [ADR 0002](0002-stack.md) (TypeScript). Sigue a [ADR 0010](0010-bun-test.md) y
  [ADR 0011](0011-bun-lint-fmt.md).

## Contexto

`bun run typecheck` ejecutaba `tsc --noEmit` con TypeScript 6. El proyecto no emite JavaScript ni declaraciones: Vite
y `bun build` empaquetan, así que `tsc` solo comprobaba tipos. Bun trae ahora `bun check`, que usa el mismo
`tsconfig.json`, informa de los mismos errores que `tsc` y sigue las reglas de TypeScript 7.

## Decisión

1. `bun run typecheck` ejecuta `bun check --no-script`. Hace falta `--no-script` porque `package.json` ya tiene un
   script `check` (lint, tipos y pruebas), y `bun check` lo ejecutaría en lugar del comprobador.
2. Se elimina la dependencia `typescript`: ningún paquete la necesita.
3. `bun check` no existe en Bun 1.4.2, así que `pages.yml`, que comprueba tipos antes del despliegue, pasa a canary
   como `ci.yml` y `release.yml`.

## Consecuencias

- La comprobación de los 141 ficheros baja de unos 4 s a unos 0,2 s. La base actual no da errores con las reglas de
  TypeScript 7, así que esta PR adopta TypeScript 7 de hecho.
- Comprobado con errores introducidos a propósito en `packages/core`, `apps/web` y `e2e`: `bun check` informa del
  mismo error que `tsc` y termina con código 1.
- Los editores usan su propio TypeScript, no el del repositorio. Es aceptable: CI es la referencia.
