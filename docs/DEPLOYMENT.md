# Despliegue en producción

## Topología

```text
Navegador ──HTTPS──► proxy inverso (TLS) ──HTTP──► lexican (un proceso Bun 1.4.2)
                                                     ├─ API Hono  /api/*, /media/*
                                                     ├─ SPA compilada (WEB_DIST)
                                                     ├─► PostgreSQL 18
                                                     └─► volumen de medios (MEDIA_DIR)
                                                     ─► CAS 3.0 y CAUCE (salientes)
```

Un proceso Bun, una base PostgreSQL y un volumen para medios. Sin Redis, colas ni servicios adicionales
([ARCHITECTURE.md](ARCHITECTURE.md)). Las sesiones viven en PostgreSQL. Se recomienda **una réplica**: el límite de
peticiones es por proceso y la limpieza de medios huérfanos al arrancar supone que no hay subidas en curso en otra.

## Perfiles (`APP_ENV`)

| Perfil | Dónde | Qué permite |
|---|---|---|
| `production` (por defecto) | Docker institucional | CAS institucional + CAUCE. Rechaza CAS de pruebas, `CAS_PROFILES=test`, `AUTH_DEV_LOGIN`, `db:seed --demo`, `http` en `PUBLIC_URL`/`CAS_URL` y la falta de `CAUCE_*` o `CAS_ALLOWED_SLO_HOSTS` |
| `local` | `docker-compose.yml`, desarrollo | CAS público de pruebas (`https://www.casserverpac4j.dev`) con fichas ficticias, contraseñas y datos demo |
| `test` | pruebas automatizadas | nada activado salvo lo que se configure |

La imagen sin configuración no arranca (`APP_ENV=production requires CAS_URL`): nunca activa por accidente el CAS
público ni cuentas ficticias. El restablecimiento de la demo de Pages solo borra bases de IndexedDB del navegador; no
puede tocar los datos de un Docker.

## Imagen Docker

`Dockerfile` multi-stage, imágenes fijadas por *digest*:

1. `build` (`node:24-alpine` + binario de Bun 1.4.2): `npm ci`, build de la web (Vite) y de la API (`bun build`: un
   bundle autosuficiente con sus dependencias; las migraciones SQL se copian a `apps/api/dist/migrations`).
2. `runtime` (`oven/bun:1.4.2-alpine`, amd64 y arm64): solo `api/` (bundle) y `web/` (SPA); **sin `node_modules`**;
   usuario `bun` (no root); `VOLUME /data/media`; `EXPOSE 3000`; `HEALTHCHECK` contra `/api/health` (comprueba la
   base); proceso `bun --no-env-file api/server.js` con cierre ordenado en `SIGTERM`/`SIGINT` (deja de aceptar, termina
   las peticiones en curso y cierra el *pool*).

El contexto de build es una lista blanca (`.dockerignore`): no entra el árbol legacy, ni `.env`, ni `node_modules`.
Bun no lee ficheros `.env` implícitamente (`bunfig.toml`, `--no-env-file`): la configuración llega solo por entorno.

### Docker local (pruebas)

```bash
docker compose --profile app up --build     # migraciones + vocabularios + datos ficticios, app en http://localhost:3000
docker compose --profile app down            # parar (añadir -v para borrar base y medios)
```

`docker-compose.yml` fija `APP_ENV=local`, el CAS público de pruebas (`alice`/`pwd` profesora, `bob`/`pwd` alumno) y
`AUTH_DEV_LOGIN=true`. El servicio `setup` migra y siembra (idempotente) antes de arrancar `app`. Ambos con
`read_only: true`.

### Producción institucional

```bash
docker build -t lexican:X.Y.Z .
LEXICAN_IMAGE=lexican:X.Y.Z docker compose -f docker-compose.prod.yml --env-file /ruta/segura/production.env up -d
```

`docker-compose.prod.yml` exige cada variable institucional (`${VAR:?}`) y no hereda nada del perfil local. Sin
Compose:

```bash
docker run -d --name lexican --env-file /ruta/segura/lexican.env \
  -v lexican-media:/data/media --read-only --tmpfs /tmp -p 3000:3000 lexican:X.Y.Z
```

## Variables de entorno

Validadas al arrancar por `apps/api/src/config.ts` (falla con el nombre de la variable, nunca su valor). Ejemplo con
valores ficticios: `apps/api/.env.example`.

| Variable | Por defecto | Uso |
|---|---|---|
| `APP_ENV` | `production` | perfil: `production`, `local` o `test` (ver arriba). `NODE_ENV` no influye |
| `DATABASE_URL` | — (obligatoria) | conexión PostgreSQL |
| `PUBLIC_URL` | — (obligatoria; `https` en producción) | origen público. Define la URL de servicio CAS, la comprobación `Origin` y si las cookies son `__Host-` + `Secure` |
| `PORT` / `HOST` | `3000` / `0.0.0.0` | escucha |
| `SESSION_TTL_HOURS` | `8` (máx. 720) | caducidad de sesión |
| `MEDIA_DIR` | — (obligatoria; `/data/media` en la imagen) | raíz de medios |
| `MAX_UPLOAD_MB` | `10` (máx. 100) | tamaño máximo por archivo (se corta mientras llega el cuerpo) |
| `MEDIA_QUOTA_MB_PER_DAY` | `100` | cuota de subida por usuario en 24 h (ventana móvil) |
| `LOGIN_RATE_LIMIT` | `10` | intentos de acceso con contraseña por minuto e IP (solo `local`/`test`) |
| `CAS_URL` | vacío; `https://www.casserverpac4j.dev` en `local` | servidor CAS 3.0, con su prefijo (`https://host/cas`); sin *query* ni fragmento |
| `CAS_LOGIN_PATH` | `/login` | ruta de acceso, relativa a `CAS_URL` |
| `CAS_VALIDATE_PATH` | `/p3/serviceValidate` | ruta de validación de tickets |
| `CAS_LOGOUT_PATH` | `/logout` | ruta de cierre de sesión SSO |
| `CAS_PROFILES` | `cauce` en producción, `test` en el resto | quién resuelve el sujeto CAS: CAUCE o las fichas ficticias del CAS de pruebas (emisor `cas_test`) |
| `CAUCE_URL`, `CAUCE_TOKEN` | vacío | directorio institucional; obligatorios con `CAS_PROFILES=cauce`. **Secreto**: el token |
| `CAS_ALLOWED_SLO_HOSTS` | vacío (cualquiera; **obligatoria en producción**) | IP permitidas para el SLO, separadas por comas |
| `AUTH_DEV_LOGIN` | `false` | acceso con contraseña; solo `local`/`test` |
| `SCHOOL_YEAR_START` | `08-30` | inicio del curso escolar (`MM-DD`) |
| `WEB_DIST` | vacío (`/app/web` en la imagen) | carpeta de la SPA que sirve la API |
| `LOG_LEVEL` | `info` | `fatal`…`trace`, `silent` |
| `TRUST_PROXY` | vacío | número de saltos de proxy de confianza (`1`) o lista de IP/CIDR del proxy. `true` se rechaza |
| `MIGRATE_ON_START` | `false` | aplica migraciones al arrancar |
| `MIGRATIONS_DIR` | autodetectado | carpeta de migraciones si no está junto al bundle (`apps/api/src/db.ts`) |
| `DEMO_ASSETS_DIR` | `/app/web/demo` en la imagen | imágenes de `db:seed --demo` |

`CAS_BASE_URL` (versiones anteriores) se rechaza con un mensaje que indica el cambio a `CAS_URL`.

Los secretos (`DATABASE_URL`, `CAUCE_TOKEN`) se inyectan desde el gestor de secretos del entorno; nunca en la imagen
ni en el repositorio.

## Migraciones

Ficheros SQL revisados en `packages/db/migrations/` (Drizzle). Dos formas:

```bash
# como paso previo al despliegue (recomendado)
docker run --rm --env-file lexican.env lexican bun --no-env-file api/cli/migrate.js
# o al arrancar
MIGRATE_ON_START=true
```

Desde el repositorio: `npm run db:migrate` (lee `DATABASE_URL`). Vocabularios: `bun --no-env-file api/cli/seed.js` en la
imagen o `npm run db:seed` desde el repositorio (idempotente). Con varias réplicas, migrar como paso previo y no con
`MIGRATE_ON_START`.

## Proxy inverso y TLS

- TLS termina en el proxy; `PUBLIC_URL` debe ser la URL `https://` que ve el navegador.
- `TRUST_PROXY` con la IP (o el número de saltos) del proxy para que la IP de cliente (límite de peticiones,
  `CAS_ALLOWED_SLO_HOSTS`) sea la real. Sin él, `X-Forwarded-For` se ignora.
- El proxy debe conservar la cabecera `Origin` y el `Host`, y permitir cuerpos de al menos `MAX_UPLOAD_MB`.
- No añadir CORS ni reescribir la CSP: la API ya envía las cabeceras de seguridad ([SECURITY.md](SECURITY.md)).
- La aplicación espera estar en la raíz del dominio (`/`): la cookie `__Host-` exige `Path=/`.

## Copias de seguridad

Hay que copiar **las dos cosas** y en el mismo momento aproximado:

```bash
pg_dump --format=custom --file=lexican-$(date +%F).dump "$DATABASE_URL"
tar -C /ruta/del/volumen -czf lexican-media-$(date +%F).tar.gz .
```

Restaurar: `pg_restore --clean --if-exists -d "$DATABASE_URL" lexican-AAAA-MM-DD.dump` y descomprimir el volumen. Los
medios se nombran por UUID y la base guarda su SHA-256 (`media_assets.sha256`), así que se puede comprobar la
coherencia. Las copias contienen datos personales de menores: cifradas y con acceso restringido
([PRIVACY.md](PRIVACY.md)).

## Actualizar

1. Leer el `CHANGELOG.md` y las notas de la *release* (migraciones destacadas).
2. Copia de seguridad (base + medios).
3. `docker pull`/`docker build` de la nueva versión.
4. Migrar con la nueva imagen (`bun --no-env-file api/cli/migrate.js`).
5. Sustituir el contenedor. Comprobar `GET /api/health` y un acceso real.

## Artefactos de *release*

`.github/workflows/release.yml`, con una etiqueta `vX.Y.Z` que coincida con `package.json`: repite el gate de CI
(audit, licencias, lint, typecheck, tests con PostgreSQL, builds, `check:dist`), genera el SBOM y publica una
*release* de GitHub con:

| Fichero | Contenido |
|---|---|
| `lexican-X.Y.Z.zip` | `web/` (SPA compilada) y `api/` (bundle Bun autosuficiente con migraciones); sin `node_modules` |
| `lexican-X.Y.Z-demo.zip` | build estático de la demo |
| `sbom.spdx.json` | SBOM SPDX de dependencias de producción |

No se publica todavía una imagen OCI en un registro: se construye desde el `Dockerfile` de la etiqueta.

## Vuelta atrás

- **Código**: volver a desplegar la imagen anterior.
- **Esquema**: las migraciones no tienen *down*. Si una versión cambia el esquema, la vuelta atrás es restaurar la copia
  previa (base + medios) y desplegar la imagen anterior. Por eso se hace la copia en el paso 2.
- **Corte desde el legacy**: durante la migración, la vuelta atrás es apuntar de nuevo al legacy (en solo lectura
  durante el corte). Procedimiento en [MIGRATION.md](MIGRATION.md).
