# ADR 0009 — Una API Hono para Docker y para Pages; Bun en el servidor; demo en un Web Worker

- Estado: aceptada
- Fecha: 2026-10-04
- Sustituye en parte a: [ADR 0002](0002-stack.md) (Fastify, tsx/esbuild), [ADR 0004](0004-demo-pglite.md) (PGlite en
  el hilo principal, `media_blobs`), [ADR 0005](0005-autenticacion.md) (`CAS_BASE_URL`, `NODE_ENV`) y
  [ADR 0007](0007-medios-y-exportacion.md) (lectura completa de medios).

## Contexto

La demo de Pages llamaba a los servicios directamente desde React, sin pasar por la API: no comprobaba rutas,
serialización JSON, errores HTTP, límites ni sesiones, y PGlite bloqueaba el hilo de la interfaz. Los medios se
guardaban como `bytea` en PGlite. Queremos que Pages ejecute el mismo código de API que Docker y que responda bien en
Android, sin complicar la arquitectura.

## Decisión

1. **`packages/http`**: la API completa como una aplicación **Hono** creada con `createApi(deps)`, generada desde la
   tabla de operaciones. Solo usa APIs web (fetch, Request/Response, Blob, WebCrypto); no importa `node:*`, `pg`, Bun
   ni configuración institucional. Se inyecta lo que varía: base de datos, almacenamiento de medios, cómo viaja la
   sesión (`SessionCarrier`), directorio de perfiles, IP de cliente, configuración pública, reloj y registro.
2. **Servidor**: Bun 1.4.2 ejecuta `apps/api` (`Bun.serve` → `app.fetch`). Fuera del paquete compartido quedan
   cookies `HttpOnly`, cabeceras de seguridad, IP tras proxy de confianza (`proxy-addr`), SPA estática, CAUCE y el
   sistema de archivos. El backend se empaqueta con `bun build` en un bundle autosuficiente (sin `node_modules` en la
   imagen). `node-postgres` sigue detrás de Drizzle 0.45: no se adopta Bun.SQL (exigiría Drizzle 1.x preliminar).
3. **Pages**: un **Web Worker dedicado** ejecuta la misma app Hono sobre PGlite (`idb://lexican-demo-v2`). React usa
   el mismo cliente (`apps/web/src/api/client.ts`) con un transporte por mensajes genérico: método, URL, cabeceras y
   cuerpo `Blob` (nunca base64), con timeout, cancelación, detección de caída y sin reintentos automáticos.
4. **Sesión**: mismas filas `sessions` (hash SHA-256, caducidad deslizante, rotación). En Docker viaja en la cookie
   `__Host-sid`; en Pages, como identificador opaco en `localStorage`, enviado en el sobre del mensaje (un Worker no
   puede fijar cookies). La API del servidor nunca acepta ese canal.
5. **Varias pestañas**: el Worker toma el Web Lock `lexican-demo-database`; una segunda pestaña no abre la base y
   explica por qué. Sin Web Locks, la demo no arranca (comportamiento seguro).
6. **Medios fuera de SQL**: `MediaStorage` (`put`, `open` → `Blob` perezoso, `delete`, `keys`). Docker: volumen con
   `openAsBlob` y rangos HTTP (`206`/`416`, `HEAD`). Pages: Blobs en la base IndexedDB `lexican-demo-media` (con
   `ArrayBuffer` como alternativa donde WebKit no admite Blobs, p. ej. navegación privada). Primero se escriben los
   bytes y luego la fila; si la fila falla se borran; los huérfanos de una caída se eliminan al arrancar. La migración
   0001 elimina `media_blobs` y el Worker copia antes sus bytes al almacén de Blobs: los datos de la demo se conservan.
7. **Entornos**: `APP_ENV=production|local|test` (por defecto `production`). Producción exige CAS institucional +
   CAUCE y rechaza CAS de pruebas, perfiles ficticios, acceso por contraseña y semillas demo. `local` usa por defecto
   el CAS público de pruebas con perfiles ficticios. `NODE_ENV` ya no decide nada.
8. **CAS**: `CAS_URL`, `CAS_LOGIN_PATH`, `CAS_VALIDATE_PATH`, `CAS_LOGOUT_PATH` (se rechaza `CAS_BASE_URL`). Un único
   cliente CAS (`packages/http/src/cas.ts`) valida tickets en el servidor y en el Worker. Emisores separados en
   `auth_identities.provider` (`cas`, `cas_test`): el mismo sujeto nunca enlaza cuentas de emisores distintos.
   `alice`/`bob` del CAS público se vinculan de forma determinista a la profesora y al alumno 1 de la demo.

## Comprobaciones hechas (2026-10-04)

- `https://www.casserverpac4j.dev/p3/serviceValidate` (XML y `format=JSON`) **no** envía `Access-Control-Allow-Origin`.
  En Chrome, desde `https://ateeducacion.github.io`, `fetch` falla con «blocked by CORS policy». Por tanto Pages no
  puede validar tickets de ese servidor: el flujo está implementado y probado con un CAS falso que sí permite CORS,
  pero con el servidor público la demo muestra el aviso y no inicia sesión. Docker sí valida (desde el servidor).
- Las cuentas documentadas en la página de acceso del CAS público son `alice`/`pwd` y `bob`/`pwd`.
- Bun carga `.env` automáticamente; un `.env` heredado de Laravel cambiaba `APP_ENV` en silencio. Se desactiva con
  `bunfig.toml` (`env = false`) y `--no-env-file`.
- La intercepción `route.fulfill` de Playwright no aplica CORS a las peticiones del Worker; por eso las pruebas de CORS
  usan un servidor CAS falso en otro origen (`scripts/fake-cas-server.mjs`).

## Versiones y fuentes

| Pieza | Versión | Fuente consultada |
|---|---|---|
| Hono | 4.13.13 | Context7 `/websites/hono_dev`: `app.fetch`, `bodyLimit` (cuenta el flujo sin `Content-Length`), cookies `__Host-`, `getConnInfo` |
| Bun | 1.4.2 (estable, 2026-09-05) | Context7 `/oven-sh/bun/bun-v1.4.2`: `--no-env-file`, `bunfig.toml env = false`; GitHub releases |
| PGlite | 0.5.8 | Context7 `/electric-sql/pglite`: `PGliteWorker` (descartado), `relaxedDurability`, `idb://` |
| Drizzle ORM | 0.45.3 | Sin cambios; 1.x sigue en *beta/rc* en npm |
| Vite | 8.3.2 | `worker.format: 'es'`; WASM y `.data` de PGlite verificados en el build de producción |
| CAS | 3.0 | Especificación de Apereo; servidor público comprobado a mano |

## Alternativas descartadas

- *PGliteWorker* (líder entre pestañas): deja la API en el hilo principal de cada pestaña; un Worker propio con Web Lock
  es más simple y saca todo el trabajo del hilo de React.
- *Service Worker* que intercepte `fetch`: ciclo de vida y caché de otro producto; fuera de alcance.
- Gestor de paquetes Bun: npm sigue como herramienta de instalación (lockfile, auditoría, licencias y SBOM ya
  funcionan); Bun es el runtime del servidor.
- Proxy CORS para el CAS público: añadiría un servicio externo sin autorización.
