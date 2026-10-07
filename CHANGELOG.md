# Registro de cambios

Formato basado en [Keep a Changelog 1.1.0](https://keepachangelog.com/es-ES/1.1.0/) y versionado
[SemVer](https://semver.org/lang/es/). Las versiones 1.x (Laravel) están en la rama `upstream`.

## [Sin publicar]

### Cambiado — Biome

- Biome 2.5.15 sustituye a ESLint y Prettier (`bun run lint`/`fix`, `make lint`/`make fix`): mismas reglas efectivas,
  incluida la prohibición de APIs de servidor en el código compartido, más avisos de accesibilidad revisados uno a uno.

### Eliminado — árbol Laravel

- Código PHP/Laravel, sus migraciones, vistas, recursos, `public/`, scripts de despliegue, `Dockerfile.apache81`,
  `documentos/`, `archivos/` y la configuración heredada (`.htaccess`, `.styleci.yml`, `.yarnrc.yml`…). Siguen en la
  rama `upstream`. Se conservan `analysis/` (especificación de la reconstrucción) y el migrador de datos.

### Cambiado — Bun como gestor de paquetes, herramientas y servidor

- `bun.lock` sustituye a `package-lock.json`; `bun ci` en CI y Docker, `bun run` para los scripts, `bun audit`,
  licencias con `bun pm licenses` y SBOM SPDX con `scripts/sbom.mjs`. Dependabot usa el ecosistema `bun`.
- Vite, Vitest, Playwright y TypeScript se ejecutan con `bunx --bun`; se elimina Node de los requisitos, CI y Docker.
- Drizzle ORM y Kit se fijan juntos en `1.0.0-rc.5-5935859`, con migraciones v3 y SQL común a PostgreSQL y PGlite.
  `Bun.SQL` sustituye a `pg` y `@types/pg` en servidor, CLI, pruebas y migrador; contratos para JSONB y tipos,
  rollback y 100 transacciones concurrentes.
- Docker conserva la capa de instalación ante cambios de fuentes. CI y releases prueban una imagen y publican ese
  mismo artefacto en GHCR: `main` tras los checks, versión y `latest` tras una etiqueta `v*` válida.
- Dependabot agrupa Drizzle ORM y Kit; el tooling E2E valida los nombres de sus bases antes de eliminarlas.

### Cambiado — una API para Docker y Pages ([ADR 0009](docs/adr/0009-hono-bun-worker.md))

- La API pasa de Fastify a **Hono** (`packages/http`) y el servidor se ejecuta con **Bun 1.4.2**; imagen Alpine sin
  `node_modules`, y `docker-compose.prod.yml` aparte del Compose local.
- La demo de Pages ejecuta la misma API en un **Web Worker** (PGlite fuera del hilo de la interfaz) con el mismo
  cliente que producción; una sola pestaña a la vez; estado de arranque y errores visibles.
- Medios fuera de SQL: volumen en Docker (con rangos HTTP para desplazarse por audio y vídeo) y Blobs en IndexedDB en
  la demo. **Migración `media_out_of_sql`**: añade el emisor `cas_test` y elimina `media_blobs` (la demo copia antes sus bytes).
- Audio de móviles: se admiten M4A/AAC, OGG Opus y WebM solo audio (antes se rechazaban).
- Configuración: `APP_ENV` (`production` por defecto) y `CAS_URL` + `CAS_LOGIN_PATH`/`CAS_VALIDATE_PATH`/
  `CAS_LOGOUT_PATH` (se rechaza `CAS_BASE_URL`). CAS público de pruebas solo en Docker local; Pages usa cuentas ficticias.
- La pantalla de acceso muestra los errores de CAS; las cuentas institucionales deshabilitadas no pueden entrar.
- Menú de cuenta con desconexión al pulsar el nombre y ajustes del diccionario personal mediante un engranaje.

### 2.0.0 — reconstrucción completa

#### Añadido

- Licencia AGPL-3.0-or-later para todo el proyecto ([LICENSE](LICENSE), [docs/LICENSING.md](docs/LICENSING.md)).
- Interfaz nueva: espacio de trabajo en tres columnas para el diccionario personal (lista con buscador y estado de cada
  entrada, ficha de lectura y panel con aulas, comentarios y exportación), editor estructurado de entradas con vista
  previa y comprobación de las pautas del aula, y espacio de revisión para el profesorado (cola con publicación en
  bloque, instantánea fija, decisión y conversación). En móvil, navegación inferior y botón para añadir.
- Tipografías Lexend y Literata servidas desde la propia aplicación, sin CDNs.
- Demo pública en GitHub Pages que funciona entera en el navegador (PostgreSQL en WebAssembly con PGlite), con
  cuentas y datos ficticios, persistencia en el navegador y botón para restablecerla.
- Diccionario personal: entradas con varias acepciones, temáticas, imagen, audio y vídeo; búsqueda por texto, inicial y
  temática; entradas ocultas.
- Diccionarios de aula con código de unión, participantes con rol de profesorado o alumnado, vigencia por cursos o
  atemporal, plazo de envíos, campos obligatorios, máximo de acepciones y pautas.
- Envíos como revisiones inmutables: lo que revisa el profesorado no cambia aunque el alumno siga editando. Estados
  pendiente, publicado, rechazado (con nota) y retirado; publicación de varios envíos a la vez con aviso de conflictos.
- Comentarios del profesorado a cada alumno, con visibilidad configurable.
- Exportación a CSV, JSON y **OASIS DMLex 1.0 JSON**, nueva respecto al sistema 1.x y validada automáticamente
  contra el JSON Schema oficial para interoperar con otras herramientas lexicográficas; vista de impresión para guardar en PDF.
- Administración propia: usuarios y roles, vigencia de aulas, listas controladas, estadísticas y auditoría.
- Detección de ediciones concurrentes (aviso de conflicto en lugar de sobrescribir).
- Herramienta de migración de los datos y medios del sistema anterior a PostgreSQL, con simulación e informe.
- Imagen Docker de producción y `docker compose` para desarrollo.
- CI con pruebas unitarias, de contrato (PGlite y PostgreSQL), de API, de migración y E2E en Chromium, Firefox y móvil
  con comprobaciones de accesibilidad. WebKit queda disponible en local, desactivado temporalmente en CI.

#### Cambiado

- Stack: TypeScript, React + Vite, Hono, Bun, PostgreSQL y Drizzle en lugar de PHP/Laravel 8, Voyager, Blade, jQuery,
  Bootstrap 4 y MariaDB.
- Acceso institucional con un cliente CAS 3.0 propio con validación TLS; el rol de profesorado o alumnado se
  recalcula en cada acceso a partir de CAUCE.
- Sesiones guardadas en PostgreSQL con cookie `HttpOnly`.
- El profesorado de un aula se determina por su rol en esa aula, no por su rol global.
- Borrar una entrada personal es recuperable y retira sus envíos pendientes; lo ya publicado en el aula se mantiene.
- Comentarios y contenidos en texto plano.
- PDF generado por el navegador en lugar de en el servidor.

#### Eliminado

- Panel Voyager, registro público y recuperación de contraseña de Laravel, visor de logs y rutas de prueba.
- Editor HTML TinyMCE, miniaturas de vídeo con FFmpeg y generación de PDF con dompdf.
- Datos que no hacen falta: NIF/NIE, CIAL y pasaporte ya no se guardan.
- Scripts de despliegue de OpenShift y copia a NFS.

#### Seguridad

- Autorización centralizada en los servicios: cada operación comprueba quién la hace y sobre qué diccionario.
- Ninguna ruta `GET` modifica datos; protección CSRF por origen y cookies `SameSite`.
- Subidas validadas por contenido, con lista cerrada de tipos y tamaño máximo, servidas solo a quien puede verlas.
- Cabeceras de seguridad (CSP sin `unsafe-inline`, `nosniff`, `frame-ancestors 'none'`, HSTS) y límites de intentos
  en el acceso.
- Consultas parametrizadas, sin HTML de usuario y sin secretos en el repositorio ni en los bundles.
- Auditoría de dependencias y licencias en CI.
- Las credenciales del sistema anterior publicadas en el historial deben rotarse en el sistema en servicio
  ([docs/SECURITY.md](docs/SECURITY.md)).
