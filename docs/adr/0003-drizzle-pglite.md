# ADR 0003 — Un esquema Drizzle para PostgreSQL y PGlite; repositorios sin interfaces duplicadas

- Estado: aceptada
- Fecha: 2026-10-04

## Contexto

§14 y §18 piden un modelo PostgreSQL compartido con la demo PGlite, repositorios con interfaces estrechas y *contract
tests* contra ambos *adapters*.

## Decisión

- Drizzle ORM `~0.45.3` y drizzle-kit `~0.31.11`. Un único `packages/db/src/schema.ts`; migraciones generadas con
  `drizzle-kit generate` y revisadas (nunca `push`).
- El tipo `Db = PgDatabase<PgQueryResultHKT, typeof schema>` acepta tanto `drizzle-orm/node-postgres` como
  `drizzle-orm/pglite`. Por eso hay **un solo** *adapter* de persistencia: los servicios de `packages/app` consultan
  Drizzle directamente. Una interfaz `EntryRepository` con una única implementación sería código duplicado.
- El requisito de §75 se cumple con más fuerza: la batería `packages/app/src/services.contract.test.ts` ejecuta el
  recorrido completo de la aplicación (no solo los repositorios) contra PGlite y contra PostgreSQL 18 real
  (`TEST_DATABASE_URL`, servicio de CI).
- En el navegador, el migrador de Drizzle necesita `node:fs`; `migrateBundled()` (30 líneas) aplica los mismos ficheros
  SQL importados con `import.meta.glob(?raw)` y escribe las mismas filas `drizzle.__drizzle_migrations` con el mismo
  *hash*, de modo que ambas bases son intercambiables.
- Sin extensiones de PostgreSQL. La búsqueda insensible a tildes usa una columna `sort_key` calculada en TypeScript y
  `COLLATE "C"`, que produce el mismo orden en PGlite y en PostgreSQL.

## Consecuencias

- Drizzle v1 (en RC) cambia el formato de migraciones: `migrateBundled` deberá reescribirse; Dependabot ignora los
  *majors* de Drizzle.
- Las pruebas de concurrencia real (bloqueos) solo tienen sentido en PostgreSQL; PGlite es de una sola conexión.
