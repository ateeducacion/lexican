# ADR 0010 — `bun test` sustituye a Vitest; las pruebas usan Bun canary

- Estado: aceptada
- Fecha: 2026-11-22
- Modifica [ADR 0002](0002-stack.md) (stack de pruebas).

## Contexto

Vitest 5.1.1 rompió su pool `forks` bajo Bun (`child.channel?.unref is not a function` al arrancar un worker,
`setMaxListeners` con `-1` al cerrarlo): Dependabot no pudo actualizarlo (#67). Vitest es una capa más sobre Bun, que
ya ejecuta el servidor, las herramientas y los scripts, y su ejecutor de pruebas implementa la API `vi` que usan
estas pruebas.

## Decisión

1. Las pruebas unitarias, de contrato, de integración y del migrador se ejecutan con **`bun test`**. Importan de
   `bun:test`; la configuración (exclusión de `e2e/`, _timeout_, cobertura y umbrales) está en `bunfig.toml`. Se
   eliminan `vitest`, `@vitest/coverage-v8` y `vitest.config.ts`. Playwright sigue con los E2E.
2. La API `vi` que usan las pruebas (`stubGlobal`, `stubEnv`, `resetModules`, `waitFor`) solo está en **Bun canary**;
   la estable 1.4.5 no la trae. Los jobs que ejecutan `bun test` (`quality` y `migration` en CI, y el de
   `release.yml`, que también empaqueta el ZIP de la release) instalan `canary`. Los tipos se fijan a la versión exacta
   `bun-types@1.4.5-canary.…`. La imagen Docker y Pages siguen en la versión estable fijada.
3. Bun aplica el umbral de cobertura **a cada fichero**, no al total, y no mide ramas. El umbral bloqueante pasa a ser
   del 90 % de líneas y el 80 % de funciones por fichero (los módulos pequeños tienen pocas funciones). Codecov
   mantiene su objetivo del 90 % sobre el total.

## Consecuencias

- Toda la suite pasa sin cambiar ningún test: 300 pruebas en unos 13 s con PostgreSQL y MariaDB. Solo cambian las
  importaciones y cuatro anotaciones de tipos.
- Una dependencia menos, que además fallaba, y una sola herramienta para ejecutar y probar.
- CI depende de una versión que cambia a diario: una regresión de canary puede romper la CI sin que cambie el
  repositorio. Es aceptable en un repositorio experimental; cuando una versión estable traiga esa API, CI vuelve a
  fijarla.
- Codecov deja de mostrar ramas y líneas cubiertas a medias.
