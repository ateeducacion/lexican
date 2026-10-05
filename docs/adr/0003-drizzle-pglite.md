# ADR 0003 — Un esquema Drizzle para PostgreSQL y PGlite

- Estado: aceptada
- Fecha: 2026-10-05

## Contexto

Producción, demo y pruebas deben compartir el mismo modelo SQL, los mismos servicios y las mismas migraciones.
Esta implementación aún no tiene su primera etiqueta de versión: se adopta Drizzle v1 antes de estabilizarla.

## Decisión

- Drizzle ORM y drizzle-kit `1.0.0-rc.5-5935859`, fijados exactamente y actualizados juntos. Un único
  `packages/db/src/schema.ts`; `drizzle-kit generate` genera SQL que se revisa (nunca `push`).
- El tipo `Db = PgAsyncDatabase<PgQueryResultHKT>` acepta Bun.SQL y PGlite sin conversiones de tipo.
  Los servicios consultan Drizzle directamente; no hay una interfaz de repositorio con una única implementación.
  Se usa el constructor `drizzle({ client, codecs })`: las consultas SQL tipadas toman las tablas
  del esquema y no necesitan declarar relaciones ni usar el constructor de consultas relacionales.
- PostgreSQL se conecta con el pool nativo `Bun.SQL` (`max: 10` en la API); se cierra con `client.close()`.
  Se eliminan `pg`, `@types/pg` y el adaptador node-postgres. Los helpers de pruebas y las CLI son entradas
  exclusivas de Bun; el esquema, los servicios y el Worker siguen usando solo APIs compartidas con el navegador.
- Bun.SQL en RC4 y la revisión RC5 fijada necesita un codec JSONB explícito: `JSON.stringify` y parámetros `::text::jsonb`. El codec predeterminado
  de Bun.SQL infiere tipos numéricos al insertar primitivos y serializa de nuevo las cadenas JSON; el fallo rompe
  la versión de la semilla demo y la búsqueda de medios en snapshots. La consulta de contención de medios también
  fija ese cast de texto. No se modifica el formato almacenado ni el esquema. [Codecs de Drizzle](https://orm.drizzle.team/docs/pg/codecs).
- `queryRows` devuelve un array ordinario desde SQL directo en ambos drivers: PGlite entrega `{ rows }` y
  Bun.SQL un array con metadatos adicionales. Los errores únicos y los logs leen SQLSTATE de `code` (PGlite)
  o `errno` (Bun.SQL), conservando las reglas de conflicto y la redacción de valores privados.
- Migraciones v3: carpetas `YYYYMMDDHHmmss_nombre` con `migration.sql` y `snapshot.json`, sin `_journal.json`.
  `drizzle-kit up` convierte los snapshots y mueve el SQL existente sin cambiar sus bytes ni sus hashes.
  La primera generación v1 recrea tres índices únicos parciales con los mismos filtros, cuya representación deja
  de incluir el nombre de la tabla. No cambia las columnas ni elimina datos.
- El servidor usa el migrador `drizzle-orm/bun-sql/migrator`. La demo carga el SQL con `import.meta.glob(?raw)`
  y `migrateBundled` prepara los metadatos con WebCrypto y la utilidad de fechas de Drizzle. Ambos llaman al mismo migrador nativo
  exportado desde `drizzle-orm/pg-core`; el navegador no importa `node:fs` ni accede a una sesión interna.
- Drizzle actualiza su tabla `drizzle.__drizzle_migrations` añadiendo `name` y `applied_at`, y enlaza las filas
  anteriores por fecha y hash. Se conserva la base de la demo: no se cambia `DATA_DIR` ni se vuelve a sembrar.
- Sin extensiones de PostgreSQL. La búsqueda sin tildes usa `sort_key` calculado en TypeScript y `COLLATE "C"`.

## Comprobación y consecuencias

Los contratos ejecutan los servicios sobre PGlite y PostgreSQL 18 con Bun.SQL. Incluyen JSONB anidado y
primitivos, arrays con caracteres especiales, UUID, enum, fechas con zona horaria y milisegundos, índices únicos
parciales, rollback por error y explícito, y 100 transacciones concurrentes. Las pruebas de migración contrastan
nombres, fechas y hashes con el lector nativo, comprueban idempotencia y convierten una tabla de seguimiento v0 con datos
existentes sin repetir el SQL. Las pruebas CLI cubren el migrador del servidor.

La versión sigue siendo una RC: cualquier actualización requiere revisar cambios y repetir esos contratos, los
builds de producción/demo y los E2E. Las pruebas de concurrencia real se ejecutan solo en PostgreSQL; PGlite tiene
una sola conexión.

Fuentes: [actualización a v1](https://orm.drizzle.team/docs/upgrade-v1),
[cambios de v0 a v1](https://orm.drizzle.team/docs/v0-v1-changes) y el código instalado de `1.0.0-rc.5-5935859`.
