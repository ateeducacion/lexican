# Privacidad

LexiCán trata datos de alumnado menor de edad. La privacidad es un requisito de diseño: se guarda lo mínimo, no hay
terceros y la demo pública no contiene datos reales. No es asesoramiento jurídico.

## Inventario de datos (producción)

Esquema: `packages/db/src/schema.ts` ([DATA-MODEL.md](DATA-MODEL.md)).

| Tabla | Datos personales | Para qué | Conservación | Borrado / anonimización |
|---|---|---|---|---|
| `users` | nombre, apellidos, nombre visible, correo (opcional), avatar (de una lista cerrada), rol global, estado | identificar a la persona en sus diccionarios, aulas y comentarios | mientras la cuenta exista | `status = 'disabled'` corta el acceso. El borrado elimina en cascada identidades, sesiones y centros; **no existe todavía** una operación de borrado o anonimización en la interfaz (pendiente) |
| `auth_identities` | emisor (`cas`, `cas_test`, `password`) y sujeto CAS (identificador de usuario); hash PBKDF2 solo en demo/desarrollo; último acceso. `cas_test` solo contiene las cuentas ficticias del CAS de pruebas y nunca existe en producción | vincular el acceso institucional con la cuenta | como `users` | cascada con `users` |
| `sessions` | hash del identificador de sesión, ticket CAS, fechas | mantener la sesión y el *single logout* | hasta `expires_at` (`SESSION_TTL_HOURS`) | se borran al salir, por SLO, y las caducadas al iniciar sesión |
| `schools`, `user_schools` | centro y código de rol CAUCE | estadísticas por centro y cálculo del rol | se reescriben en cada acceso | cascada con `users` |
| `dictionaries`, `classroom_settings`, `dictionary_memberships` | propietario, participación en aulas | funcionamiento de diccionarios y aulas | mientras existan | `deleted_at` (borrado lógico); la participación se desactiva con `active = false` |
| `entries`, `entry_senses`, `sense_topics`, `entry_revisions`, `submissions` | contenido creado por el alumnado (palabras, definiciones, ejemplos), autoría y fechas | el producto | mientras exista el diccionario | borrado lógico de entradas; las revisiones son instantáneas inmutables de lo enviado |
| `media_assets`, `sense_media` + volumen de medios | imágenes, audios y vídeos subidos (pueden contener voz o imagen de menores), nombre original del fichero, autor | ilustrar acepciones | mientras se usen | **pendiente** de definir la purga de ficheros sin uso |
| `comments` | texto del profesorado dirigido a un alumno | retroalimentación docente | mientras exista el aula | `deleted_at` |
| `audit_events` | quién, qué acción, sobre qué entidad, cuándo; metadatos sin contenido | trazabilidad mínima | **pendiente** de fijar plazo | `actor_id` pasa a `null` si se borra el usuario |

Los plazos concretos de conservación (fin de curso, fin de vigencia del aula, años tras la última actividad) son una
decisión del responsable del tratamiento: **pendiente**.

## Minimización

- No se guardan NIF/NIE, CIAL ni pasaporte. El legacy los guardaba en `personas`; el adaptador CAUCE los ignora
  (`apps/api/src/cauce.ts`) y el migrador no los copia.
- Identificador de acceso: el sujeto CAS.
- Los centros solo sirven para estadísticas y para calcular el rol.
- Comentarios y contenidos en texto plano.

## Registros (logs)

- No se registran cookies, cabeceras `Authorization`, `Set-Cookie`, tickets CAS, `state` ni `logoutRequest` (el
  registro de peticiones solo recibe método, ruta tachada, estado, duración e IP: `apps/api/src/app.ts`).
- Las respuestas de CAUCE nunca se registran (el legacy registraba NIF, CIAL y centros); solo un código de error.
- La configuración inválida se informa por nombre de variable, nunca por valor.
- La auditoría (`audit_events`) guarda acciones, no contenido.

## Sin terceros

- Sin analítica, *tracking* ni *pixels*.
- Sin CDNs, fuentes ni imágenes externas: todo se sirve desde el mismo origen. La CSP (`default-src 'self'`) lo impide
  y los E2E fallan ante cualquier petición fuera del origen (`e2e/fixtures.ts`).
- Solo cookie técnica de sesión; no hace falta banner de consentimiento de cookies (el legacy tenía uno; confirmar con
  el DPD).

## Demo pública

- Usuarios, centro y contenidos ficticios (`packages/core/src/demo.ts`, `packages/app/src/demo-seed.ts`).
- Todo lo que se escribe en la demo se queda en IndexedDB (`/pglite/lexican-demo-v2`, `lexican-demo-media`),
  `localStorage` (identificador de sesión) y `sessionStorage` (acceso CAS pendiente) **del navegador** de quien la usa;
  no se envía a ningún servidor ([DEMO.md](DEMO.md)).
- Excepción deliberada: «Entrar con CAS de pruebas» lleva al CAS público de pruebas (casserverpac4j.dev, ajeno a la
  Consejería) y le pide validar el ticket. Solo se usan sus cuentas ficticias; la pantalla advierte de no escribir
  credenciales institucionales.
- Las ilustraciones de la demo son propias (CC0).

## Repositorio y pruebas

- Ni datos reales, ni volcados, ni capturas con datos reales en el repositorio, *fixtures* o informes.
- Los *fixtures* de CAS, CAUCE y del migrador son ficticios. El test del migrador comprueba que ningún identificador
  nacional ficticio de las fuentes llega a PostgreSQL (`tools/legacy-migrator/src/migrate.test.ts`).
- Los ensayos de migración con datos reales se hacen fuera del repositorio sobre una copia autorizada.

## Abierto (DPD)

- **Base jurídica** del tratamiento (previsiblemente la función educativa de la Consejería): pendiente de confirmar
  por la persona Delegada de Protección de Datos.
- Plazos de conservación por tabla y del volumen de medios.
- Procedimiento de borrado/anonimización a petición y al final de la vigencia de las aulas.
- Información a familias y alumnado sobre la subida de voz e imagen.
- Registro de actividades de tratamiento y, si procede, evaluación de impacto.
