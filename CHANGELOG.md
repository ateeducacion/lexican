# Registro de cambios

Formato basado en [Keep a Changelog 1.1.0](https://keepachangelog.com/es-ES/1.1.0/) y versionado
[SemVer](https://semver.org/lang/es/). Las versiones 1.x (Laravel) están en la rama `upstream`.

## [Sin publicar]

### 2.0.0 — reconstrucción completa

#### Añadido

- Demo pública en GitHub Pages que funciona entera en el navegador (PostgreSQL en WebAssembly con PGlite), con
  cuentas y datos ficticios, persistencia en el navegador y botón para restablecerla.
- Diccionario personal: entradas con varias acepciones, temáticas, imagen, audio y vídeo; búsqueda por texto, inicial y
  temática; entradas ocultas.
- Diccionarios de aula con código de unión, participantes con rol de profesorado o alumnado, vigencia por cursos o
  atemporal, plazo de envíos, campos obligatorios, máximo de acepciones y pautas.
- Envíos como revisiones inmutables: lo que revisa el profesorado no cambia aunque el alumno siga editando. Estados
  pendiente, publicado, rechazado (con nota) y retirado; publicación de varios envíos a la vez con aviso de conflictos.
- Comentarios del profesorado a cada alumno, con visibilidad configurable.
- Exportación a CSV, JSON y OASIS DMLex 1.0 JSON, y vista de impresión para guardar en PDF.
- Administración propia: usuarios y roles, vigencia de aulas, listas controladas, estadísticas y auditoría.
- Detección de ediciones concurrentes (aviso de conflicto en lugar de sobrescribir).
- Herramienta de migración de los datos y medios del sistema anterior a PostgreSQL, con simulación e informe.
- Imagen Docker de producción y `docker compose` para desarrollo.
- CI con pruebas unitarias, de contrato (PGlite y PostgreSQL), de API, de migración y E2E en Chromium, Firefox, WebKit
  y móvil con comprobaciones de accesibilidad.

#### Cambiado

- Stack: TypeScript, React + Vite, Fastify, PostgreSQL y Drizzle en lugar de PHP/Laravel 8, Voyager, Blade, jQuery,
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
