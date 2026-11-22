# ADR 0011 — `bun lint` y `bun fmt` sustituyen a Biome

- Estado: aceptada
- Fecha: 2026-11-22
- Modifica [ADR 0002](0002-stack.md) (herramientas). Sigue a [ADR 0010](0010-bun-test.md) (Bun canary en CI).

## Contexto

Biome se encargaba del lint, el formato y el orden de las importaciones. Bun trae ahora un linter (`bun lint`, reglas
de ESLint, typescript-eslint, React y jsx-a11y ejecutadas de forma nativa) y un formateador (`bun fmt`, la salida de
Prettier) que se configuran en `bunfig.toml`. Con [ADR 0010](0010-bun-test.md), Bun ya ejecuta el servidor, los
scripts y las pruebas.

## Decisión

1. `bun run lint` ejecuta `bun lint && bun fmt --check`; `bun run fix`, `bun lint --fix && bun fmt`. La configuración
   está en `[lint]` y `[fmt]` de `bunfig.toml`. Se eliminan `biome.json` y `@biomejs/biome`.
2. Se conservan las reglas que daban valor con Biome:
   - en el código compartido (`packages/core`, `packages/app`, `packages/http`, `apps/web/src`, sin tests),
     `no-restricted-imports` (`pg`, `proxy-addr`, `bun`, `node:*`) y `no-restricted-globals` (`Bun`), con los mismos
     mensajes;
   - `no-console`, que solo permite `warn` y `error`, salvo en `scripts/` y las CLI;
   - los _hooks_ de React y todas las reglas de jsx-a11y, salvo `prefer-tag-over-role`, que convertiría cada región
     `role="status"` en un `<output>`.
3. Formato: 100 columnas, comillas simples solo en JavaScript y TypeScript, comas finales e importaciones ordenadas
   por grupos (`sortImports`).
4. `bun lint` solo existe en Bun canary (la estable 1.4.5 trae `bun fmt`, pero no `bun lint`). Los jobs que ejecutan
   `bun run lint` ya usan canary.

## Consecuencias

- Una herramienta menos. El lint de los 152 ficheros tarda unos 30 ms.
- Se pierde el lint de CSS y JSON que hacía Biome. `bun fmt` sigue formateándolos.
- El reformateo inicial (orden de las importaciones con líneas en blanco entre grupos, cadenas de métodos largas)
  va en un commit aparte.
- Las supresiones usan la sintaxis de ESLint (`eslint-disable-next-line regla -- motivo`).
