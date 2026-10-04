# Seguridad

## Punto de partida

La auditoría del legacy (Laravel 8 + Voyager) está en
[`analysis/lexican/ASSESSMENT.md`](../analysis/lexican/ASSESSMENT.md#security-findings) (28 hallazgos, SEC-001 a
SEC-028) y las cifras de dependencias vulnerables en [`analysis/lexican/BASELINE.md`](../analysis/lexican/BASELINE.md).
Lo más grave: subida de ficheros con nombre del cliente y sin CSRF (RCE), visor de logs que descarga o borra cualquier
fichero, credenciales en el repositorio público, validación TLS desactivada en CAS y CAUCE, SQL concatenado, IDOR,
XSS almacenado por HTML de TinyMCE, rutas `GET` que modifican datos y un stack sin soporte.

La reconstrucción no hereda código ni dependencias del legacy. Esta tabla dice cómo se cubre cada clase.

| Clase (legacy) | Ahora | Dónde |
|---|---|---|
| IDOR / autorización ausente (SEC-010, -013, -014) | Autorización central en los servicios de aplicación: cada operación pasa por `requireUser`/`requireRole` y, para diccionarios, por `dictionaryAccess` (propietario, rol en el aula, vigencia). Las rutas HTTP no deciden permisos. La demo ejecuta el mismo código | `packages/app/src/access.ts`, `context.ts` |
| `GET` que modifican (SEC-015) | La tabla de operaciones asigna `POST`/`PUT`/`PATCH`/`DELETE` a toda mutación. Única excepción: `GET /api/auth/cas/logout`, que cierra la propia sesión para redirigir al logout de CAS | `packages/core/src/operations.ts` |
| CSRF (SEC-001, -015) | `Origin` obligatorio e igual a `PUBLIC_URL` en métodos no seguros, cookie `SameSite=Lax`, solo JSON y *multipart* | `apps/api/src/app.ts` |
| Subida de ficheros (SEC-001, -020) | Tipo por **contenido** (`file-type`), lista cerrada (PNG, JPEG, WebP, GIF; MP3, OGG, WAV, WebM; MP4, WebM), sin SVG; clave de almacenamiento opaca (`uuid.ext`), nunca el nombre del cliente; tamaño máximo `MAX_UPLOAD_MB` (y 2 MB en la demo); un fichero por petición; cuota por usuario `MEDIA_QUOTA_MB_PER_DAY` en 24 h; servido por `/media/:id` solo a quien lo subió, a quien puede leer una entrada **visible** que lo usa (entradas o acepciones ocultas solo para quien puede editar) o al profesorado que revisa un envío que lo referencia en su lista de medios (contención `jsonb`, nunca búsqueda de texto), con `nosniff`, `Content-Disposition` saneado y `CSP: default-src 'none'; sandbox` | `packages/app/src/media.ts`, `packages/core/src/fields.ts`, `apps/api/src/media-storage.ts` |
| Inyección SQL (SEC-005) | Consultas con Drizzle (parametrizadas). Drizzle `≥0.45.2` por GHSA-gpj5-g38j-94v9 | `packages/app/src/*.ts` |
| XSS (SEC-011, -012) | Todo el contenido de usuario es **texto plano**; no hay editor HTML ni `dangerouslySetInnerHTML`. React escapa la salida | `apps/web/src` |
| Cabeceras (SEC-018) | `@fastify/helmet`: CSP `default-src 'self'` sin `unsafe-inline` ni `eval`, `frame-ancestors 'none'`, `object-src 'none'`, `form-action` limitado al origen y al CAS; `Referrer-Policy: strict-origin-when-cross-origin`; `X-Content-Type-Options: nosniff`; HSTS con HTTPS | `apps/api/src/app.ts` |
| TLS desactivado (SEC-006, -007) | `fetch` con verificación TLS por defecto contra CAS y CAUCE; XML sin DTD | `apps/api/src/cas.ts`, `cauce.ts` |
| Fuerza bruta | `@fastify/rate-limit`: login 10/min, callback CAS 20/min (por IP); subidas 30/min y unirse a un aula 10/min (por usuario). Los códigos de aula son exactamente 6 caracteres del alfabeto sin ambigüedades (32⁶ ≈ 10⁹); el migrador regenera los códigos legacy que no cumplen | `apps/api/src/app.ts`, `packages/core/src/contracts.ts` |
| *Login CSRF* en CAS | `state` aleatorio en cookie `cas_state` (5 min) y en la URL de servicio; el callback exige que coincidan ([AUTHENTICATION.md](AUTHENTICATION.md)). SLO solo con `SessionIndex` `ST-…` y, en producción, solo desde `CAS_ALLOWED_SLO_HOSTS` | `apps/api/src/app.ts`, `cas.ts`, `config.ts` |
| IP del cliente | `TRUST_PROXY` solo acepta número de saltos o IP/CIDR; `true` se rechaza porque permitiría falsear `X-Forwarded-For` (límites, SLO) | `apps/api/src/config.ts` |
| Inyección de fórmulas en CSV | Celdas que empiezan por `=`, `+`, `-`, `@`, tabulador o retorno de carro llevan un apóstrofo delante | `packages/core/src/export.ts` |
| Sesiones | Identificador aleatorio de 256 bits, solo el hash en base de datos, cookie `__Host-` `HttpOnly`/`Secure`; ver [AUTHENTICATION.md](AUTHENTICATION.md) | `apps/api/src/app.ts` |
| Errores con detalle (SEC-017) | Manejador central: errores de dominio con código y mensaje; 500 genérico «Error interno», sin trazas | `apps/api/src/app.ts` |
| Configuración y secretos (SEC-016, -017) | Variables de entorno validadas con Zod; los mensajes de error nombran el campo, nunca el valor; `.env` fuera de la imagen (`.dockerignore`) y de git | `apps/api/src/config.ts` |
| Logs con datos personales | `pino` con `redact` de `cookie`, `authorization` y `set-cookie`; `ticket` y `logoutRequest` tachados en la URL; nunca se registra la respuesta de CAUCE; los errores de base de datos se registran solo con clase y SQLSTATE (sin SQL, parámetros ni valores) | `apps/api/src/app.ts` |
| Dependencias vulnerables (SEC-008, -009, -021, -022, -023) | Lockfile, `npm ci`, `npm run audit` (`--omit=dev --audit-level=high`) en CI y release, Dependabot semanal (npm y Actions), mínimos fijados por avisos (Fastify `≥5.12.5`) | `package.json`, `.github/` |
| Licencias de terceros | `npm run licenses` falla si una dependencia de producción tiene una licencia fuera de la lista permitida | `scripts/licenses.mjs` |
| Secretos en artefactos públicos | `npm run check:dist` busca hosts institucionales, claves privadas, asignaciones de secretos, `APP_KEY` de Laravel y los tokens filtrados del legacy en los bundles; el de producción no puede contener PGlite, WASM ni contraseñas demo | `scripts/check-dist.mjs` |
| Imagen de producción (SEC-017, -024) | Multi-stage, sin `devDependencies`, usuario `node`, sistema de ficheros de solo lectura posible (solo `/data/media` escribible), sin `.env` | `Dockerfile` |
| Admin con credenciales fijas (SEC-002) | No hay contraseñas en producción: solo CAS. Administración se concede desde `/admin/usuarios`. Las cuentas con contraseña solo existen en demo y desarrollo | `packages/app/src/admin.ts` |
| CI | Permisos `contents: read` por defecto, escritura solo en el job que la necesita, actions fijadas por SHA, `persist-credentials: false` | `.github/workflows/` |

### Qué no cubre

- La demo no es una frontera de seguridad: todo ocurre en el navegador de quien la usa.
- No hay antivirus de ficheros subidos; el tipo se verifica, el contenido multimedia no se recodifica.
- El límite de peticiones es por proceso (memoria). Con varias réplicas, cada una cuenta por separado.
- La cuota de subida se comprueba antes de insertar: subidas simultáneas pueden superarla en un fichero.
- Los medios subidos que nunca se usan en una entrada no se purgan todavía (deuda: tarea periódica que borre
  `media_assets` sin referencias de más de N días y sus ficheros).
- El comportamiento real del CAS y de CAUCE se verifica en preproducción ([AUTHENTICATION.md](AUTHENTICATION.md)).

## Credenciales expuestas en el histórico

La rama `upstream` (copia histórica pública del código legacy, intocable por §5 de `1er-prompt.md`) contiene:

- la `APP_KEY` de Laravel;
- dos tokens *bearer* del servicio CAUCE;
- contraseñas de cuentas de administración y de usuarios sembrados;
- identificadores con formato de documento nacional y correos de personal en los *seeders*.

**Acción de operaciones, independiente de esta reconstrucción y urgente:** rotar la `APP_KEY` y los dos tokens CAUCE,
y cambiar o deshabilitar las cuentas sembradas en el legacy que sigue en servicio. Reescribir el historial de git está
fuera de alcance (§5): el histórico seguirá siendo público, así que lo único eficaz es la rotación.

Este repositorio no reproduce ninguno de esos valores. El inventario con ubicaciones solo existe en la copia local de
quien hizo la auditoría (`analysis/lexican/SECRETS.local.md`, ignorado por git); se entrega a operaciones por un canal
privado.

## Informar de una vulnerabilidad

No abras una *issue* pública. Usa **Report a vulnerability** (avisos privados de seguridad) en la pestaña *Security* de
<https://github.com/ateeducacion/lexican>, con:

- versión o commit afectado;
- pasos para reproducirlo;
- impacto estimado.

No incluyas datos personales reales. Si afecta al legacy en producción, indícalo: la corrección allí corresponde a
operaciones.

> **Pendiente:** confirmar que los avisos privados de seguridad están activados en el repositorio y quién los atiende.
