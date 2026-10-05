# Migración del LexiCán legacy (MariaDB/MySQL) a PostgreSQL

Herramienta: `tools/legacy-migrator` (TypeScript, `mysql2` solo como dependencia de migración). Qué se migra y
cómo: [LEGACY-DATA-MAPPING.md](LEGACY-DATA-MAPPING.md). Estrategia: ensayos → ventana de solo lectura →
migración final → validación → cambio de servicio. **No hay doble escritura** (§69).

## Qué hace

1. Lee todas las tablas legacy necesarias (nunca NIF/NIE, CIAL, pasaporte ni contraseñas).
2. En **una única transacción** PostgreSQL: siembra los vocabularios, carga usuarios, centros, diccionarios,
   entradas, acepciones, medios, aulas, miembros, envíos, publicaciones y comentarios, y ejecuta las verificaciones.
3. Copia los medios (`<media-source>/dp/medios/{imagenes,audios,videos}/<url_interna>`) al almacenamiento nuevo
   `<media-target>/<2 primeros caracteres de la clave>/<uuid>.<ext>` (mismo formato que `apps/api/src/media-storage.ts`).
   Los originales nunca se modifican ni se mueven.
4. Escribe el informe JSON y, al lado, un CSV con todas las anomalías.

Si algo falla, la transacción se revierte entera: o se migra todo o nada. Los ficheros ya copiados se quedan en
el destino (sin filas que los referencien) y se reutilizan en la siguiente ejecución.

## Requisitos

- Bun 1.4.2 y `bun ci` en la raíz del repositorio; el destino PostgreSQL usa `Bun.SQL`.
- Acceso de **solo lectura** a la base legacy (`SELECT` sobre el esquema y `information_schema`).
- PostgreSQL 18 (versión probada) con el esquema de LexiCán aplicado (`bun run db:migrate`). El migrador no crea el esquema.
- Lectura del directorio `storage/app/public` legacy (volumen NFS) y escritura en el directorio de medios nuevo
  (el `MEDIA_DIR` de la API).
- Las fechas legacy están en UTC (`config/app.php`): no hace falta fijar zona horaria.

## Comando

```bash
bun run migrate:legacy \
  --source "mysql://lector:CLAVE@legacy-db:3306/lexican" \
  --target "$DATABASE_URL" \
  --media-source /mnt/legacy/storage/app/public \
  --media-target /var/lib/lexican/media \
  --report informes/migracion-$(date +%Y%m%d-%H%M).json \
  [--dry-run] [--fail-on-orphans]
```

| Opción | Efecto |
|---|---|
| `--source` | URL MySQL/MariaDB legacy (usuario de solo lectura) |
| `--target` | URL PostgreSQL destino |
| `--media-source` | Directorio `storage/app/public` legacy (contiene `dp/medios`) |
| `--media-target` | Raíz del almacenamiento de medios nuevo |
| `--dry-run` | Hace todo dentro de la transacción y la revierte; calcula checksums pero **no copia** ficheros. El informe es completo |
| `--report` | Ruta del informe JSON (por defecto `legacy-migration-report.json`); el CSV se escribe junto a él |
| `--fail-on-orphans` | Si hay filas huérfanas, revierte y termina con código 1 |

Las rutas relativas se resuelven desde el directorio donde se lanza el comando (`bun run migrate:legacy` desde la raíz). Códigos de salida: `0` correcto
(también en dry-run), `1` fallo o huérfanos con `--fail-on-orphans`, `2` argumentos o esquema destino ausente.
Las contraseñas de las URL se ocultan en la salida.

## El informe

```jsonc
{
  "dryRun": false, "committed": true, "failure": null, "durationMs": 188, "orphans": 1,
  "tables": { "dp_entradas": { "legacy": 7, "new": 7, "mapped": 7, "skipped": 0, "invalid": 0, "orphaned": 0 }, … },
  "notMigrated": { "audits": { "count": 2, "reason": "Log técnico de owen-it; …" }, … },
  "media": { "migrated": 4, "sameContent": 2, "missing": 1, "corrupt": 1, "duplicates": 1,
             "unreferenced": 1, "brokenRefs": 1, "thumbnailsIgnored": 1, "bytesCopied": 253 },
  "verification": [ { "check": "orden de acepciones contiguo (1..n)", "ok": true, "detail": "…" }, … ],
  "anomalySummary": { "repairable automatically": 9, "needs rule": 0, "needs human review": 8, "cannot migrate": 3 },
  "anomalies": [ { "table": "envios_entradas", "legacyId": 803, "class": "repairable automatically",
                   "message": "dp_entrada 9999 inexistente: se crea una entrada personal borrada como origen del envío" }, … ]
}
```

- `media`: `migrated` (ficheros distintos copiados), `sameContent` (filas que comparten fichero o contenido),
  `missing` (faltantes), `corrupt` (vacíos o formato no admitido), `duplicates` (más de un medio del mismo tipo en
  una acepción), `unreferenced` (ficheros sin referencia), `brokenRefs` (referencias rotas: vacías, URL externa,
  ruta no válida).
- Clases de anomalía (§71): `repairable automatically` (corrección automática aplicada y documentada),
  `needs rule` (falta una decisión funcional; se aplicó la indicada), `needs human review` (revisar a mano),
  `cannot migrate` (la fila o el dato no se migra).
- En una re-ejecución el informe solo recoge lo que esa ejecución ha hecho: por ejemplo, un valor maestro creado
  la primera vez ya casa por clave legacy y no vuelve a aparecer.

El informe contiene titulares, nombres de fichero y mensajes, pero no nombres de personas ni identificadores
nacionales. Aun así es un documento interno: no lo publiques ni lo subas al repositorio.

## Ensayo (§72)

Nunca se prueba por primera vez contra producción.

1. **Copia autorizada.** Restaura un volcado de producción (o anonimizado) en una MariaDB de ensayo y monta una
   copia (o el volumen en solo lectura) de `storage/app/public`:
   ```bash
   docker run -d --name lexican-legacy -e MARIADB_ROOT_PASSWORD=… -p 53306:3306 mariadb:11
   docker exec -i lexican-legacy mariadb -uroot -p… -e 'create database lexican'
   docker exec -i lexican-legacy mariadb -uroot -p… lexican < volcado.sql
   ```
2. **Destino vacío.** Crea una base PostgreSQL nueva y aplica el esquema: `DATABASE_URL=… bun run db:migrate`.
3. **Dry-run** y revisión del CSV con el equipo funcional: `bun run migrate:legacy … --dry-run --report ensayo-dry.json`.
   Decide cada `needs rule` y `needs human review` (corregir en el legacy de ensayo, aceptar o cambiar la regla).
4. **Importación completa**: el mismo comando sin `--dry-run`.
5. **Recuentos**: en `tables`, `legacy = mapped + skipped + invalid + orphaned` en las tablas principales y `new`
   coherente con `mapped`. Contraste manual, por ejemplo:
   ```sql
   -- legacy
   select count(*) from dp_entradas where deleted_at is null and estado <> '0';
   -- nuevo
   select count(*) from entries where legacy_source = 'dp_entradas' and deleted_at is null;
   ```
6. **Checksums de medios**: la verificación «medios copiados con checksum correcto» debe ser `ok`. Además,
   `faltantes` y `corruptos` se revisan con el equipo (¿existen en otra copia del NFS?).
7. **Integridad**: todas las entradas de `verification` en `ok: true`. Una con `ok: false` no impide el commit
   (es un hallazgo de datos), pero hay que entenderla antes del corte.
8. **Idempotencia**: vuelve a ejecutar; los recuentos de `new` no cambian y `media.migrated` es 0.
9. **E2E sobre datos migrados**: arranca la API contra la base migrada (`MEDIA_DIR` = `--media-target`) y ejecuta
   `bun run e2e` (perfil producción) más un recorrido manual con un docente y un alumno reales del ensayo.
10. **Comparación manual**: elige 10 entradas personales, 5 envíos publicados y 3 aulas; compara pantalla legacy y
    nueva (acepciones y su orden, temáticas, medios, estado del envío, comentarios, configuración del aula).
11. **Mide la duración** (`durationMs` y tiempo total) para dimensionar la ventana de corte.
12. Documenta en el acta del ensayo: commit del código, versión de migraciones (`drizzle.__drizzle_migrations`),
    duración, informe y decisiones tomadas.

Para repetir un ensayo desde cero, borra y recrea la base destino y vacía el directorio de medios.

### Prueba automática

`tools/legacy-migrator/src/migrate.test.ts` reproduce el ciclo con los datos ficticios de `fixtures/legacy/`
(esquema derivado de las migraciones Laravel, datos y medios mínimos): dry-run, migración, aserciones,
re-ejecución idempotente y `--fail-on-orphans`. Se ejecuta solo si existen ambos servidores:

```bash
docker run -d --name lexican-mariadb -e MARIADB_ROOT_PASSWORD=root -e MARIADB_DATABASE=legacy -p 53306:3306 mariadb:11
TEST_MARIADB_URL=mysql://root:root@127.0.0.1:53306/legacy \
TEST_DATABASE_URL=postgres://lexican:lexican@localhost:55432/postgres \
  bunx --bun vitest run tools/legacy-migrator
```

El test crea una base MariaDB y otra PostgreSQL temporales y las borra al terminar. Las funciones puras
(HTML→texto, estados, etiquetas, sniffing, instantáneas) tienen tests unitarios sin bases de datos.

Para lanzar el CLI a mano contra el fixture:

```bash
docker exec -i lexican-mariadb mariadb -uroot -proot legacy < fixtures/legacy/schema.sql
docker exec -i lexican-mariadb mariadb -uroot -proot legacy < fixtures/legacy/data.sql
bun run migrate:legacy --source mysql://root:root@127.0.0.1:53306/legacy \
  --target postgres://lexican:lexican@localhost:55432/lexican_ensayo \
  --media-source fixtures/legacy/media --media-target /tmp/lexican-media --dry-run --report /tmp/informe.json
```

### Tiempos medidos sobre el fixture

Portátil de desarrollo (Apple Silicon), MariaDB 11 y PostgreSQL 18 en Docker local:

| Ejecución | `durationMs` (transacción) | Tiempo total del comando |
|---|---|---|
| `--dry-run` | 220 ms | 1,6 s |
| primera migración | 188 ms | 1,4 s |
| re-ejecución (sin cambios) | 163 ms | 1,4 s |

El fixture es diminuto (≈60 filas); el tiempo real crece de forma lineal con las filas (una sentencia por fila) y
con el volumen de medios (lectura + sha256 + copia). Mide en el ensayo con la copia real y reserva el doble para la
ventana de corte. El migrador carga cada tabla en memoria y escribe fila a fila; si el ensayo supera la
ventana, el siguiente paso es insertar por lotes.

## Corte (cutover)

1. Anuncia la ventana. Registra la hora de inicio.
2. Pon el legacy en **solo lectura**: modo mantenimiento de Laravel (`php artisan down`) y revoca `INSERT/UPDATE/DELETE`
   al usuario de la aplicación (o `SET GLOBAL read_only = ON` si la base es exclusiva).
3. Haz un volcado del legacy y una copia (snapshot) del volumen de medios. Son el punto de retorno.
4. Base destino vacía + `bun run db:migrate`.
5. `bun run migrate:legacy … --fail-on-orphans --report corte.json` (si el ensayo aceptó huérfanos conocidos,
   omite `--fail-on-orphans` y compara con el informe del ensayo).
6. Valida: `committed: true`, `failure: null`, verificaciones `ok`, recuentos en línea con el último ensayo,
   humo E2E y la comparación manual reducida (3 entradas, 1 aula).
7. Cambia el servicio: DNS o proxy apuntan a la API nueva. Registra hora del corte, commit desplegado y última
   migración aplicada.
8. Mantén el legacy en solo lectura y sus medios intactos al menos durante el periodo de vuelta atrás acordado.

## Vuelta atrás (rollback, §73)

La vuelta atrás consiste en **volver a apuntar al legacy**, nunca en «desmigrar» PostgreSQL hacia MySQL.

- **Antes del cambio de servicio** (paso 7): no hay nada que deshacer; el legacy sigue siendo el sistema. Si la
  migración falla, la transacción ya se revirtió; se corrige y se repite o se pospone el corte.
- **Después del cambio de servicio**: vuelve a apuntar DNS/proxy al legacy, quita el modo mantenimiento y devuelve
  los permisos de escritura. Los datos creados en la app nueva durante ese tiempo **no vuelven** al legacy: se
  exportan (DMLex/JSON) y se comunican a los docentes afectados. Por eso la decisión de vuelta atrás tiene que
  tomarse pronto (primeras horas).
- No se borra nada: ni la base legacy, ni el volcado, ni los medios originales. El volumen de medios nuevo es una
  copia y se puede descartar.
- La base PostgreSQL de un corte fallido se conserva (renombrada) para el análisis; el siguiente intento parte
  de una base nueva.
