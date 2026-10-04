# Despliegue en producción

## Topología

```text
Navegador ──HTTPS──► proxy inverso (TLS) ──HTTP──► lexican (un proceso Node.js)
                                                     ├─ API Fastify  /api/*, /media/*
                                                     ├─ SPA compilada (WEB_DIST)
                                                     ├─► PostgreSQL 18
                                                     └─► volumen de medios (MEDIA_DIR)
                                                     ─► CAS 3.0 y CAUCE (salientes)
```

Un proceso Node, una base PostgreSQL y un volumen para medios. Sin Redis, colas ni servicios adicionales
([ARCHITECTURE.md](ARCHITECTURE.md)). Las sesiones viven en PostgreSQL, así que se pueden ejecutar varias réplicas
compartiendo base y volumen (el límite de peticiones es por réplica).

## Imagen Docker

`Dockerfile` multi-stage (`node:24-bookworm-slim`):

1. `build`: `npm ci` y build de web (Vite) y API (esbuild, `apps/api/build.mjs`; las migraciones SQL se copian a
   `apps/api/dist/migrations`).
2. `deps`: `npm ci --omit=dev` solo para `@lexican/api`.
3. `runtime`: `node_modules` de producción + `apps/api/dist` + `apps/web/dist`; usuario `node` (no root);
   `VOLUME /data/media`; `EXPOSE 3000`; `HEALTHCHECK` contra `/api/health` (comprueba la base de datos); proceso
   `node apps/api/dist/server.js` con cierre ordenado en `SIGTERM`/`SIGINT`.

El contexto de build es una lista blanca (`.dockerignore`): no entra el árbol legacy, ni `.env`, ni `node_modules`.

```bash
docker build -t lexican .
docker run -d --name lexican \
  --env-file /ruta/segura/lexican.env \
  -v lexican-media:/data/media \
  --read-only --tmpfs /tmp \
  -p 3000:3000 lexican
```

La aplicación solo escribe en `/data/media`, así que puede ejecutarse con el sistema de ficheros de solo lectura
(`--read-only`). Prueba completa en local: `docker compose --profile app up --build` (PostgreSQL + imagen, con
`read_only: true` y migraciones al arrancar).

## Variables de entorno

Validadas al arrancar por `apps/api/src/config.ts` (falla con el nombre de la variable, nunca su valor). Ejemplo con
valores ficticios: `apps/api/.env.example`.

| Variable | Por defecto | Uso |
|---|---|---|
| `NODE_ENV` | `development` (`production` en la imagen) | `production` prohíbe `AUTH_DEV_LOGIN` y `db:seed --demo` |
| `DATABASE_URL` | — (obligatoria) | conexión PostgreSQL |
| `PUBLIC_URL` | — (obligatoria) | origen público. Define la URL de servicio CAS, la comprobación `Origin` y si la cookie es `__Host-sid` + `Secure` (`https`) |
| `PORT` / `HOST` | `3000` / `0.0.0.0` | escucha |
| `SESSION_TTL_HOURS` | `8` (máx. 720) | caducidad de sesión |
| `MEDIA_DIR` | — (obligatoria; `/data/media` en la imagen) | raíz de medios |
| `MAX_UPLOAD_MB` | `10` (máx. 100) | tamaño máximo por archivo |
| `MEDIA_QUOTA_MB_PER_DAY` | `100` | cuota de subida por usuario en 24 h (ventana móvil) |
| `CAS_BASE_URL` | vacío (CAS desactivado) | base del servidor CAS 3.0 |
| `CAUCE_URL`, `CAUCE_TOKEN` | vacío | directorio institucional; obligatorios si hay CAS. **Secreto**: el token |
| `CAS_ALLOWED_SLO_HOSTS` | vacío (cualquiera; **obligatoria en producción con CAS**) | IP permitidas para el SLO, separadas por comas |
| `AUTH_DEV_LOGIN` | `false` | acceso con contraseña; solo desarrollo/test |
| `SCHOOL_YEAR_START` | `08-30` | inicio del curso escolar (`MM-DD`) |
| `WEB_DIST` | vacío (`/app/apps/web/dist` en la imagen) | carpeta de la SPA que sirve la API |
| `LOG_LEVEL` | `info` | `fatal`…`trace`, `silent` |
| `TRUST_PROXY` | vacío | número de saltos de proxy de confianza (`1`) o lista de IP/CIDR del proxy. `true` se rechaza |
| `MIGRATE_ON_START` | `false` | aplica migraciones al arrancar |
| `MIGRATIONS_DIR` | autodetectado | carpeta de migraciones si no está junto al bundle (`apps/api/src/db.ts`) |

Los secretos (`DATABASE_URL`, `CAUCE_TOKEN`) se inyectan desde el gestor de secretos del entorno; nunca en la imagen
ni en el repositorio.

## Migraciones

Ficheros SQL revisados en `packages/db/migrations/` (Drizzle). Dos formas:

```bash
# como paso previo al despliegue (recomendado)
docker run --rm --env-file lexican.env lexican node apps/api/dist/cli/migrate.js
# o al arrancar
MIGRATE_ON_START=true
```

Desde el repositorio: `npm run db:migrate` (lee `DATABASE_URL`). Vocabularios: `node apps/api/dist/cli/seed.js` en la
imagen o `npm run db:seed` desde el repositorio (idempotente). Con varias réplicas, migrar como paso previo y no con
`MIGRATE_ON_START`.

## Proxy inverso y TLS

- TLS termina en el proxy; `PUBLIC_URL` debe ser la URL `https://` que ve el navegador.
- `TRUST_PROXY` con la IP (o el número de saltos) del proxy para que `req.ip` (límite de peticiones, `CAS_ALLOWED_SLO_HOSTS`) sea la del
  cliente.
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
4. Migrar con la nueva imagen (`node apps/api/dist/cli/migrate.js`).
5. Sustituir el contenedor. Comprobar `GET /api/health` y un acceso real.

## Artefactos de *release*

`.github/workflows/release.yml`, con una etiqueta `vX.Y.Z` que coincida con `package.json`: repite el gate de CI
(audit, licencias, lint, typecheck, tests con PostgreSQL, builds, `check:dist`), genera el SBOM y publica una
*release* de GitHub con:

| Fichero | Contenido |
|---|---|
| `lexican-X.Y.Z.zip` | `web/` (SPA compilada) y `api/` (bundle con migraciones); sin `node_modules` |
| `lexican-X.Y.Z-demo.zip` | build estático de la demo |
| `sbom.spdx.json` | SBOM SPDX de dependencias de producción |

No se publica todavía una imagen OCI en un registro: se construye desde el `Dockerfile` de la etiqueta.

## Vuelta atrás

- **Código**: volver a desplegar la imagen anterior.
- **Esquema**: las migraciones no tienen *down*. Si una versión cambia el esquema, la vuelta atrás es restaurar la copia
  previa (base + medios) y desplegar la imagen anterior. Por eso se hace la copia en el paso 2.
- **Corte desde el legacy**: durante la migración, la vuelta atrás es apuntar de nuevo al legacy (en solo lectura
  durante el corte). Procedimiento en [MIGRATION.md](MIGRATION.md).
