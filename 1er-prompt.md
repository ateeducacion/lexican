# Modernización integral de Lexicán

Quiero que modernices exhaustivamente el repositorio **`ateeducacion/lexican`**, tomando como referencia metodológica el trabajo realizado en **`ateeducacion/aritmates`** y **`ateeducacion/tonga`**, pero sin copiar mecánicamente ninguna de sus arquitecturas.

Lexicán es un producto distinto: no es una aplicación puramente estática. Tiene autenticación, usuarios, diccionarios personales y de aula, entradas, acepciones, medios, envío/publicación de contenidos, comentarios, permisos y persistencia. La modernización debe simplificar radicalmente la base tecnológica y el modelo, conservar el comportamiento útil y producir una aplicación que podamos mantener durante años.

El resultado debe tener **dos modos de ejecución que compartan el máximo código posible**:

1. **Producción**, con API y **PostgreSQL**.
2. **Demo completamente funcional en GitHub Pages**, sin backend, usando **PGlite en el navegador** con persistencia en IndexedDB y datos ficticios precargados.

La demo de GitHub Pages no es una maqueta. Debe permitir iniciar sesión con distintos roles y probar los flujos esenciales de alumnado y profesorado sobre una base de datos local del navegador.

No quiero mantener MySQL/MariaDB y PostgreSQL en paralelo. La base de datos de la nueva versión será PostgreSQL. Debes diseñar y ejecutar una migración verificable desde el modelo legacy actual.

---

# 1. Principios generales

Prioriza, en este orden:

1. preservación de la funcionalidad útil;
2. corrección del modelo de datos;
3. simplicidad operativa;
4. mantenibilidad;
5. seguridad y privacidad;
6. accesibilidad;
7. experiencia de usuario;
8. capacidad de prueba;
9. rendimiento;
10. facilidad de despliegue.

No conserves una tecnología solo porque ya exista.

No modernices por apariencia.

No introduzcas microservicios, Kubernetes, colas, Redis, GraphQL, event sourcing, CQRS ni otra infraestructura adicional salvo que exista una necesidad demostrable que no pueda resolverse de forma más sencilla.

Una dependencia nueva debe eliminar más complejidad de la que introduce.

La aplicación debe poder desplegarse en producción conceptualmente con algo parecido a:

```text
web/API Node.js + PostgreSQL + almacenamiento persistente de medios
```

No añadas servicios obligatorios que no sean necesarios.

---

# 2. Investigación obligatoria antes de modificar

Antes de hacer cambios significativos estudia completamente:

- rama `main`;
- rama `upstream`;
- historial Git relevante;
- `readme.md`;
- `developers.md`;
- `version.md`;
- `composer.json`;
- `package.json`;
- `webpack.mix.js`;
- `.env.example`;
- `routes/`;
- `app/Models/`;
- `app/Http/Controllers/`;
- `app/Cas/`;
- `database/migrations/`;
- `database/seeders/`;
- `resources/views/`;
- `public/`;
- `storage/`;
- `archivos/`;
- `config/`;
- tests existentes;
- scripts de despliegue;
- Dockerfiles;
- integración CAS;
- integración CAUCE;
- correo;
- exportación PDF/CSV;
- sistema de auditoría;
- Voyager;
- subida y almacenamiento de ficheros;
- cualquier código o recurso de terceros.

Estudia en paralelo **Aritmates** y **Tonga** como referencia de mantenimiento, no como plantilla de arquitectura.

De Aritmates revisa especialmente:

- `AGENTS.md`;
- `package.json`;
- `.github/workflows/`;
- `docs/ARCHITECTURE.md`;
- `docs/SIMPLIFICACION.md`;
- `docs/INFORME-MODERNIZACION.md`;
- `docs/TESTING.md`;
- tests de caracterización;
- E2E;
- tratamiento de `upstream`;
- métricas reproducibles;
- hardening de GitHub Actions;
- política de dependencias y licencias.

De Tonga revisa especialmente:

- `1er-prompt.md`;
- `AGENTS.md`;
- arquitectura actual;
- tests;
- CI;
- GitHub Pages;
- documentación de modernización;
- decisiones sobre licencias y código heredado.

No presupongas que Lexicán debe convertirse en Aritmates o Tonga.

---

# 3. Internet y Context7 son obligatorios

No tomes decisiones relevantes sobre librerías, frameworks, APIs, seguridad o formatos basándote únicamente en conocimiento interno o memoria.

Cuando evalúes una tecnología:

1. consulta documentación oficial actual;
2. consulta **Context7** si está disponible;
3. busca en Internet:
   - última versión estable;
   - mantenimiento reciente;
   - licencia;
   - vulnerabilidades/advisories;
   - compatibilidad;
   - issues relevantes;
   - política de soporte;
   - alternativas;
4. consulta el repositorio oficial cuando sea software libre;
5. registra decisiones importantes mediante ADR.

Utiliza Context7 especialmente para:

- Vite;
- React;
- Fastify;
- Drizzle ORM;
- PGlite;
- PostgreSQL;
- Vitest;
- Playwright;
- librería de validación elegida;
- autenticación/sesiones;
- subida de archivos;
- cualquier integración CAS seleccionada;
- cualquier librería de exportación PDF si se introduce.

Si Context7 y la documentación oficial discrepan, prioriza la documentación oficial más reciente y documenta la discrepancia.

No copies código de blogs, Stack Overflow, Gists o repositorios sin comprobar expresamente su licencia.

---

# 4. Estado observado del repositorio: verificar, no asumir

A fecha de elaboración de este prompt se observa aproximadamente:

```text
PHP 8.0/8.1
Laravel 8.83
Voyager 1.5
phpCAS 1.5
laravel-auditing
DOMPDF
Laravel Mix 6
Bootstrap 4.6
jQuery
TinyMCE 5
MariaDB/MySQL en los despliegues documentados
CAS + servicio CAUCE para acceso institucional
almacenamiento persistente de medios en filesystem/NFS
```

También se observa una rama `upstream` y una `main` prácticamente idénticas: `main` estaba solo un commit por delante de `upstream` en el momento de redactar este documento.

No uses estos datos como verdad eterna.

Antes de implementar:

- vuelve a comprobar versiones;
- comprueba SHAs actuales;
- comprueba diferencias `upstream..main`;
- comprueba si hay cambios posteriores;
- genera un inventario real y reproducible.

---

# 5. Rama `upstream`

Lexicán ya posee una rama `upstream`.

No la recrees.

No la modernices.

No hagas commits en ella.

No hagas `force push`.

Trátala como fotografía histórica del código original entregado/mantenido anteriormente.

Al comienzo:

- registra SHA de `upstream`;
- registra SHA de `main`;
- comprueba el merge-base;
- documenta qué diferencias existen;
- documenta qué versión de Lexicán representa;
- crea, si aporta valor y es correcto, un tag legible sobre el punto histórico, sin reescribir la historia.

Todo el código nuevo se desarrolla desde `main` mediante ramas y pull requests.

---

# 6. Auditoría funcional antes de reescribir

No reescribas basándote solo en nombres de controladores o tablas.

Construye una matriz de funcionalidades reales y prueba los flujos legacy.

Como mínimo investiga:

## Acceso y usuarios

- login CAS;
- callback CAS;
- validación CAUCE;
- creación/actualización del usuario local;
- relación `users` / `personas`;
- roles globales;
- perfiles;
- centros asociados;
- logout;
- administración.

## Diccionario personal

- creación;
- edición de nombre/avatar si existe;
- listado;
- entradas;
- acepciones;
- categoría gramatical;
- género;
- número;
- idioma;
- palabra en otro idioma;
- definición;
- frase de ejemplo;
- temáticas;
- medios;
- orden de acepciones;
- borrado/soft-delete;
- búsqueda;
- exportación;
- envío de una entrada;
- envío del diccionario completo.

## Diccionario de aula

- creación;
- edición;
- borrado;
- código de unión;
- tipo de diccionario;
- enseñanza;
- nivel;
- área/materia;
- grupo;
- curso escolar;
- vigencia;
- diccionario atemporal;
- máximo de acepciones;
- campos visibles;
- campos obligatorios;
- pautas;
- participantes;
- roles de participantes;
- altas/bajas de participantes;
- otros docentes/administradores;
- invitación;
- ventanas de envío;
- visibilidad para alumnado;
- comentarios;
- entradas pendientes;
- publicación;
- rechazo/eliminación;
- edición por docente;
- ocultación de entrada;
- ocultación de acepción;
- búsqueda por texto;
- búsqueda por inicial;
- filtro por temática;
- exportación.

## Flujo de envío/publicación

- relación entre entrada personal y envío;
- copia/snapshot de la entrada;
- copia/snapshot de acepciones;
- copia de medios;
- estado del envío;
- publicación en diccionario de aula;
- edición posterior por docente;
- trazabilidad con el original;
- reenvíos;
- comentarios sobre entrada;
- comentarios generales.

## Administración

- Voyager;
- vigencia de diccionarios;
- diccionarios atemporales;
- usuarios;
- roles;
- tablas maestras;
- avisos;
- mantenimiento;
- logs;
- estadísticas si existen;
- grabación u otras funciones específicas.

## Integraciones y salida

- PDF;
- CSV;
- correo;
- subida de imágenes/medios;
- TinyMCE si realmente se utiliza;
- enlaces externos;
- recursos almacenados en NFS;
- límites de subida.

Marca cada funcionalidad como:

```text
obligatoria
útil
prescindible
legacy sin uso
administrativa
integración externa
```

No perpetúes código muerto por precaución.

Cuando exista duda, crea primero una prueba de caracterización.

---

# 7. Baseline reproducible

Antes de sustituir la aplicación legacy crea un baseline medible.

Registra como mínimo:

- número de rutas;
- número de controladores;
- número de modelos;
- número de tablas/migraciones;
- dependencias PHP;
- dependencias npm;
- tamaño del repositorio de trabajo;
- tamaño de assets y medios versionados;
- tests existentes;
- tiempo de instalación;
- tiempo de build;
- vulnerabilidades detectadas por herramientas actuales;
- librerías sin mantenimiento;
- código vendorizado;
- ficheros generados o binarios versionados innecesariamente;
- `.DS_Store` u otros artefactos accidentales;
- errores de consola en los flujos principales;
- capturas de las pantallas relevantes.

Crea E2E de caracterización sobre el legacy para los flujos que se puedan automatizar sin depender de sistemas internos imposibles de reproducir.

No conectes tests automatizados a servicios reales de producción.

---

# 8. Decisión de arquitectura objetivo

La arquitectura inicial queda fijada así, salvo que durante la auditoría aparezca un impedimento técnico serio y documentado mediante ADR:

```text
TypeScript estricto
Node.js 24+ o LTS actual verificada
npm workspaces
React
Vite
Fastify
PostgreSQL
Drizzle ORM
PGlite para demo en navegador
Vitest
Playwright
GitHub Actions
GitHub Pages para demo
```

No uses Next.js, Nuxt, Laravel, Symfony, Django o un metaframework full-stack únicamente por comodidad si obliga a duplicar la estrategia de despliegue estático de la demo.

La separación frontend/API es intencional.

## Producción

```text
Browser
  ↓
React/Vite
  ↓ HTTPS /api
Fastify
  ↓
Domain/Application services
  ↓
Repositories
  ↓
Drizzle
  ↓
PostgreSQL
```

## Demo GitHub Pages

```text
Browser
  ↓
React/Vite
  ↓
Domain/Application services
  ↓
Repositories
  ↓
Drizzle PGlite
  ↓
PostgreSQL WASM persistido en IndexedDB
```

El objetivo es compartir:

- tipos;
- entidades;
- validación;
- reglas de negocio;
- casos de uso;
- schema PostgreSQL cuando sea razonable;
- migraciones compatibles con PGlite;
- tests de contrato de repositorios.

No intentes ejecutar Fastify dentro de GitHub Pages.

---

# 9. Estructura de repositorio propuesta

Usa un monorepo pequeño con npm workspaces, no una plataforma de monorepo compleja.

Estructura inicial recomendada:

```text
apps/
  web/
    src/
    public/
  api/
    src/

packages/
  domain/
  application/
  contracts/
  db/
  auth/
  testing/

migrations/
  legacy/
  postgres/

scripts/
  migration/
  metrics/

fixtures/
  legacy/
  demo/

integration/
e2e/
docs/
  adr/
```

No crees paquetes que contengan una sola función sin una frontera conceptual real.

Si durante la implementación una estructura más simple funciona mejor, simplifícala y registra el motivo.

---

# 10. TypeScript

Todo el código nuevo de aplicación debe ser TypeScript salvo archivos de configuración donde JavaScript sea más claro.

Activa modo estricto.

Evita `any`.

No silencies errores con casts masivos.

Las entidades del dominio no deben depender de React, Fastify o Drizzle.

Código, variables, nombres de clases, funciones, comentarios y commits: **inglés**.

Interfaz y documentación humana: **español** salvo que exista una razón para otro idioma.

---

# 11. Frontend: React + Vite

Usa React y Vite como hipótesis principal ya decidida.

No añadas Redux.

No añadas un framework visual pesado.

Utiliza:

- HTML semántico;
- CSS moderno;
- CSS Modules o una solución equivalente ligera;
- custom properties;
- controles nativos cuando sean suficientes;
- componentes pequeños;
- estado local primero.

Una librería de servidor-state como TanStack Query puede evaluarse si realmente simplifica sincronización, caché e invalidación. Si se añade, justifícala con ADR.

No añadas Tailwind, Material UI, Bootstrap, Ant Design u otro sistema completo por inercia. Si se elige uno, debe existir una justificación clara sobre accesibilidad, mantenimiento y coste de bundle.

---

# 12. Backend: Fastify

Producción usará una API Fastify sobre Node.js.

Objetivos:

- REST JSON sencillo;
- validación de entrada y salida;
- cookies de sesión seguras;
- límites de tamaño;
- multipart para medios;
- logging estructurado;
- OpenAPI generado si aporta utilidad real;
- errores consistentes;
- pruebas de integración.

No mezcles reglas de negocio directamente en handlers.

Los handlers deben hacer aproximadamente:

```text
auth → validate → application service → repository → response
```

No conviertas Fastify en un framework interno propio.

---

# 13. PostgreSQL es la única base de producción

No mantengas compatibilidad permanente con MySQL/MariaDB.

La aplicación nueva de producción soportará PostgreSQL.

Razones:

- simplifica el soporte;
- permite compartir un modelo PostgreSQL con PGlite;
- evita probar dos dialectos durante toda la vida del producto;
- permite utilizar tipos y restricciones modernas de PostgreSQL cuando aporten valor.

No uses extensiones PostgreSQL obligatorias si PGlite no puede reproducir el comportamiento esencial de la demo, salvo que exista una abstracción clara y tests.

No uses JSONB como sustituto indiscriminado de un modelo relacional.

---

# 14. Drizzle ORM

Usa Drizzle como hipótesis principal para:

- esquema TypeScript;
- queries tipadas;
- migraciones SQL revisables;
- PostgreSQL real;
- PGlite.

No uses `drizzle-kit push` como estrategia normal de producción.

Producción debe usar migraciones versionadas y revisables.

La demo debe poder aplicar las mismas migraciones o un subconjunto generado de la misma fuente de verdad.

Añade tests que demuestren que el schema relevante funciona tanto en PostgreSQL como en PGlite.

---

# 15. Demo obligatoria con PGlite en GitHub Pages

La demo debe publicarse en:

```text
https://ateeducacion.github.io/lexican/
```

si la configuración de la organización permite GitHub Pages para este repositorio.

El build de demo debe ser completamente estático.

No puede depender de:

- API propia;
- PostgreSQL remoto;
- CAS;
- CAUCE;
- SMTP;
- NFS;
- secretos;
- endpoints internos;
- servicios del Gobierno de Canarias.

La demo utilizará PGlite en el navegador con persistencia:

```text
IndexedDB
```

Usa un nombre de base versionado, por ejemplo conceptualmente:

```text
idb://lexican-demo-v1
```

La primera ejecución debe:

1. crear la base;
2. aplicar migraciones;
3. cargar datos demo;
4. marcar la versión de seed.

Recargas posteriores deben conservar datos.

Incluye una opción visible:

```text
Restablecer datos de demostración
```

que borre la base local y vuelva a sembrarla.

PGlite debe cargarse de forma diferida para no penalizar innecesariamente el primer render del login.

El bundle de producción no debe incluir PGlite si puede evitarse mediante separación de entrypoints/build modes y tree-shaking.

---

# 16. Cuentas demo debajo del login

En GitHub Pages, debajo del formulario de acceso, muestra claramente un bloque:

```text
Cuentas de demostración
```

Como mínimo:

```text
Profesor
profesor@ejemplo.com
contraseña: profesor

Alumno 1
alumno1@ejemplo.com
contraseña: alumno1

Alumno 2
alumno2@ejemplo.com
contraseña: alumno2
```

Puedes añadir:

```text
Administrador
admin@ejemplo.com
contraseña: admin
```

solo si existe funcionalidad administrativa que sea útil demostrar.

Añade botones opcionales:

```text
Entrar como profesor
Entrar como alumno 1
Entrar como alumno 2
```

que rellenen o ejecuten el acceso de forma accesible.

La interfaz debe indicar sin ambigüedad:

> Este es un entorno de demostración. Los usuarios, contraseñas y datos son ficticios y se guardan únicamente en este navegador.

No reutilices ninguna cuenta, correo, nombre, centro, identificador o dato real.

La autenticación demo no es una frontera de seguridad. No pretendas que lo sea.

---

# 17. Datos demo útiles

No siembres una base vacía.

La demo debe mostrar el producto.

Crea datos ficticios coherentes:

- un profesor;
- dos alumnos;
- un centro ficticio;
- al menos un diccionario de aula activo;
- un diccionario personal por alumno;
- varias entradas;
- varias acepciones;
- ejemplos;
- categorías gramaticales;
- temáticas;
- un par de medios pequeños con licencia clara;
- una entrada pendiente de revisión;
- una entrada publicada;
- un comentario del profesor;
- una ventana de envíos activa;
- una entrada oculta o deshabilitada si ese flujo se conserva.

El profesor debe poder revisar algo que previamente exista en el seed y también algo que cree un alumno durante la misma sesión del navegador.

Como todos los usuarios demo comparten la misma PGlite del perfil del navegador, el flujo debe permitir:

```text
login alumno → crear/enviar → logout → login profesor → revisar/publicar
```

sin reiniciar la base.

---

# 18. Repositories y adapters

No bifurques la lógica de negocio entre demo y producción.

Crea interfaces de repositorio estrechas y explícitas.

Ejemplo conceptual:

```text
DictionaryRepository
EntryRepository
SubmissionRepository
CommentRepository
UserRepository
MediaRepository
```

Producción implementará esas interfaces sobre PostgreSQL/API según la capa.

La demo las implementará directamente sobre PGlite.

La capa de aplicación debe ignorar si los datos vienen de:

```text
HTTP + PostgreSQL
```

o de:

```text
PGlite local
```

Añade **contract tests** que ejecuten el mismo conjunto de expectativas contra ambos adapters de persistencia.

---

# 19. Rediseño del modelo de datos

No reproduzcas literalmente el modelo actual de:

```text
dic_personal
dp_entradas
dp_acepciones
dp_envios
envios_entradas
envios_acepciones
dic_aula
dic_aula_entradas
```

Ese modelo refleja etapas del workflow mediante tablas duplicadas.

Diseña un modelo conceptual más pequeño y explícito.

Hipótesis inicial recomendada:

```text
users
auth_identities
schools
user_schools

dictionaries
dictionary_memberships
classroom_settings

entries
entry_senses
sense_topics

media_assets
sense_media

entry_revisions
submissions
comments

controlled_vocabularies
controlled_values

audit_events
```

El esquema exacto debe validarse contra todos los flujos legacy.

---

# 20. IDs y trazabilidad legacy

Usa UUID como identificador nuevo salvo que un ADR demuestre que otro esquema es mejor.

Durante la migración conserva trazabilidad sin convertir el ID legacy en la clave primaria nueva.

Opciones válidas:

```text
legacy_id
legacy_source
```

por tabla, o una tabla central de mapeo si resulta más limpia.

Debe ser posible responder:

```text
¿qué registro nuevo corresponde al registro legacy X?
```

sin depender de inferencias.

No expongas `legacy_id` como API pública si no hace falta.

---

# 21. Usuarios: simplifica `users` + `personas`

Investiga qué datos de `personas` son realmente necesarios.

La nueva aplicación no debe duplicar una identidad entre `users` y `personas` salvo que exista una razón funcional real.

Modelo inicial recomendado:

```text
users
  id
  email
  display_name
  first_name
  last_name
  avatar
  status
  created_at
  updated_at
  legacy_id
```

La identidad institucional debe vivir separada:

```text
auth_identities
  user_id
  provider
  subject
  metadata mínima necesaria
```

No migres automáticamente NIF/NIE, pasaporte, CIAL u otros identificadores personales sensibles solo porque existen en la base antigua.

Para cada dato personal legacy pregunta:

- ¿se utiliza funcionalmente?
- ¿es necesario legalmente?
- ¿es necesario para CAS/CAUCE?
- ¿podemos sustituirlo por un identificador técnico opaco?
- ¿cuánto tiempo debe conservarse?

Aplica minimización de datos.

---

# 22. Privacidad por diseño

Lexicán se usa en contexto educativo.

Trata privacidad como requisito de arquitectura.

Como mínimo:

- nunca publiques datos reales en fixtures;
- nunca publiques dumps reales;
- nunca metas identificadores reales en screenshots;
- elimina secretos y hosts internos de documentación pública cuando no deban estar ahí;
- no añadas analytics de terceros por defecto;
- no cargues fuentes desde CDNs externos;
- no cargues imágenes de servicios aleatorios;
- minimiza PII;
- documenta retención;
- documenta borrado/anonimización;
- protege logs frente a datos sensibles;
- no escribas tokens CAS o CAUCE en logs.

Añade `docs/PRIVACY.md`.

---

# 23. Diccionarios

Usa una entidad común `dictionary` o equivalente.

Debe diferenciar al menos:

```text
personal
classroom
```

No fuerces a que ambos tipos tengan exactamente la misma configuración.

Campos comunes posibles:

```text
id
kind
title
description
owner_id
status
created_at
updated_at
deleted_at
legacy_id
```

Los datos propios del aula deben ir en `classroom_settings` o equivalente.

No metas 25 columnas nullable de aula dentro de todos los diccionarios si puede evitarse.

---

# 24. Diccionarios personales

Preserva el concepto de espacio personal del alumno.

Debe permitir:

- crear/editar entradas;
- varias acepciones;
- reordenar acepciones;
- campos lingüísticos;
- ejemplos;
- temáticas;
- medios;
- búsqueda;
- borrado recuperable cuando corresponda;
- envío a uno o varios diccionarios de aula según reglas;
- visualización del estado de los envíos.

No mezcles en la misma pantalla edición personal y revisión docente.

---

# 25. Diccionarios de aula

Un diccionario de aula debe tener configuración explícita.

Modelo aproximado:

```text
classroom_settings
  dictionary_id
  join_code
  school_year
  dictionary_type
  teaching_level
  subject
  group_label
  max_senses_per_entry
  submissions_enabled
  submissions_start_at
  submissions_end_at
  visible_to_students
  comments_visible
  comments_visible_before
  active
  timeless
```

Normaliza nombres y tipos.

Sustituye flags `char(1)` por boolean/enum cuando proceda.

No copies datos educativos maestros como strings arbitrarios si deben seguir siendo vocabularios controlados.

---

# 26. Miembros y roles

No uses un `role_id` genérico para expresar simultáneamente el rol global y el rol dentro de un diccionario de aula.

Separa:

```text
global role
```

de:

```text
dictionary membership role
```

Roles de aula iniciales:

```text
teacher
student
```

Si el legacy necesita `co-teacher`, `reviewer` u otro rol, añádelo explícitamente.

Permisos deben comprobarse en backend, no solo ocultando botones.

La demo debe utilizar las mismas reglas de autorización en la capa de aplicación.

---

# 27. Envíos como workflow, no como duplicación accidental

Modela el envío del alumno como una entidad explícita:

```text
submission
```

Debe contener como mínimo:

```text
id
classroom_dictionary_id
source_entry_id
submitted_by
submitted_revision_id
status
submitted_at
reviewed_at
reviewed_by
review_note
legacy_id
```

Estados iniciales posibles:

```text
pending
published
rejected
withdrawn
```

Ajusta según comportamiento real.

No permitas que editar la entrada personal después del envío cambie silenciosamente lo que está revisando el profesor.

El envío debe apuntar a una **revisión/snapshot inmutable** de la entrada enviada.

---

# 28. Publicación

Al aprobar una submission, el diccionario de aula debe adquirir una entrada publicable sin perder procedencia.

Una opción recomendada:

```text
entrada personal
  ↓ snapshot
submission
  ↓ approve
nueva entrada en diccionario de aula
  ↘ source_submission_id
```

Esto permite que:

- el alumno siga editando su entrada personal;
- el profesor edite la copia publicada;
- quede trazabilidad;
- no se rompa la historia.

No reutilices el mismo registro mutable para representar simultáneamente original, envío y publicación.

---

# 29. Revisiones e historial

Añade un mecanismo sencillo de revisión de entradas.

No hace falta event sourcing.

Una tabla `entry_revisions` con snapshot estructurado y metadatos puede ser suficiente.

Úsala para:

- envíos inmutables;
- historial de cambios relevante;
- recuperación razonable;
- auditoría de publicación.

No guardes un snapshot por cada pulsación de tecla.

Define cuándo se crea una revisión:

- guardado significativo;
- envío;
- publicación;
- edición docente relevante.

---

# 30. Acepciones

Modela una acepción como entidad propia, no como JSON opaco, porque es un concepto central y consultable.

Debe soportar los campos que realmente existan tras auditoría, por ejemplo:

```text
position
part_of_speech
gender
number
language
foreign_form
definition
example
```

No hardcodees IDs numéricos históricos de tablas maestras.

Usa claves estables o relaciones tipadas.

---

# 31. Vocabularios controlados

El legacy contiene tablas maestras y `mst_campos_valores` para varios conceptos.

Rediseña esto para que sea comprensible.

Evalúa un modelo genérico pequeño:

```text
controlled_vocabularies
controlled_values
```

con `code`, `label`, orden y estado.

No lo conviertas en un EAV universal.

Los campos estructurales importantes deben seguir teniendo columnas/relaciones explícitas.

---

# 32. Ideas de Lexonomy: estudiar, no clonar

Investiga **Lexonomy** y su guía actual.

Lexicán no debe convertirse en un clon de Lexonomy, pero hay ideas útiles que debes evaluar:

- estructura explícita de una entrada;
- campos obligatorios/opcionales;
- cardinalidades;
- vocabularios controlados;
- separación entre editor y visor/publicación;
- permisos por diccionario;
- historial de edición;
- importación/exportación estructurada;
- búsqueda configurable;
- plantillas de nueva entrada;
- validación antes de publicar.

Conserva el enfoque educativo de Lexicán.

No implementes un motor de schemas arbitrarios si las necesidades actuales se resuelven con configuración tipada.

---

# 33. DMLex 1.0

Investiga el estándar **OASIS DMLex 1.0** actual.

No rediseñes toda la aplicación para cumplir DMLex a cualquier coste.

Sí debes evaluar y documentar:

- correspondencia entre `entry`, `sense`, `definition`, `example` y los conceptos de Lexicán;
- vocabularios controlados;
- multimedia;
- exportación JSON;
- futura interoperabilidad.

Crea:

```text
docs/DMLEX-MAPPING.md
```

con una tabla:

| Lexicán | DMLex | Correspondencia | Pérdidas/Extensiones |
|---|---|---|---|

Si implementar exportación DMLex JSON es razonablemente sencillo tras el nuevo modelo, hazlo.

Si no lo es, documenta el mapping y déjalo como evolución posterior.

---

# 34. Editor de entradas

La pantalla central del producto debe simplificarse.

Diseño recomendado:

## Cabecera

- palabra/entrada;
- estado;
- guardar;
- enviar al aula cuando corresponda;
- menú secundario.

## Acepciones

Cada acepción debe ser una tarjeta/sección reordenable con:

- definición;
- categoría gramatical;
- género;
- número;
- idioma;
- forma en otro idioma;
- ejemplo;
- temáticas;
- medios.

Debe existir:

- añadir acepción;
- duplicar si aporta valor;
- reordenar;
- borrar;
- validación inline;
- errores accesibles;
- contador/límite si el aula lo impone.

No uses un editor WYSIWYG pesado para campos que son texto estructurado.

Solo conserva HTML/rich text si la auditoría demuestra que existe contenido real que lo necesita.

---

# 35. UI de alumnado

Una persona alumna debería entender sin manual:

1. entrar;
2. ver su diccionario;
3. crear una palabra;
4. añadir una definición;
5. añadir ejemplo/temática/medio;
6. guardar;
7. enviarla al aula;
8. ver si está pendiente/publicada/rechazada;
9. leer comentarios del profesor cuando tenga permiso.

No escondas el flujo principal en menús secundarios.

---

# 36. UI de profesorado

Una persona docente debería poder:

1. entrar;
2. ver sus diccionarios de aula;
3. crear/configurar un aula;
4. compartir código de unión;
5. ver participantes;
6. ver entradas pendientes;
7. revisar una entrada;
8. comentar;
9. publicar o rechazar;
10. editar una entrada publicada si tiene permiso;
11. buscar el diccionario;
12. exportarlo.

Crea un dashboard pequeño basado en tareas pendientes, no un panel genérico tipo CMS.

---

# 37. Sustituir Voyager

No migres Voyager.

No introduzcas otro CMS genérico para reemplazarlo.

Identifica qué funciones administrativas de Voyager se usan realmente.

Reimplementa únicamente las necesarias como pantallas propias de administración:

- usuarios/roles si procede;
- vocabularios controlados;
- vigencia;
- diccionarios;
- avisos;
- mantenimiento mínimo;
- auditoría/diagnóstico.

Una función administrativa que nadie usa no debe sobrevivir por inercia.

---

# 38. Autenticación: demo y producción son distintas

Define una interfaz de autenticación.

## Demo

Autenticación local simulada con cuentas sembradas.

No necesita seguridad real.

## Producción

Debe preservar la capacidad de autenticación institucional.

No asumas que el paquete CAS actual debe conservarse.

Investiga:

- protocolo CAS usado realmente;
- CAS 3.0;
- logout;
- atributos retornados;
- integración CAUCE;
- mantenimiento y seguridad de clientes CAS modernos para Node;
- posibilidad de validar CAS directamente o mediante proxy/autenticador externo.

Elige solución mediante ADR.

No implementes una librería CAS abandonada solo para imitar PHP.

---

# 39. Integración CAUCE

El legacy usa un servicio CAUCE para validar/obtener información del usuario.

Audita exactamente:

- qué datos consume;
- cuándo se llama;
- qué decisiones de autorización dependen de él;
- cómo maneja errores;
- qué TTL puede tener la información;
- qué datos se almacenan localmente.

En producción crea un adapter:

```text
InstitutionalDirectory
```

o equivalente.

No mezcles llamadas HTTP CAUCE dentro de controladores de diccionarios.

La demo debe sustituir este adapter por datos ficticios locales.

Nunca publiques tokens CAUCE en GitHub Pages, tests o repositorio.

---

# 40. Sesiones de producción

Usa cookies:

- `HttpOnly`;
- `Secure` en HTTPS;
- `SameSite` adecuado;
- expiración explícita.

Evita guardar tokens de sesión en `localStorage`.

No introduzcas Redis solo para sesiones.

Preferencia inicial:

```text
sesiones persistidas en PostgreSQL
```

o una solución firmada equivalente si queda bien justificada.

Protege operaciones mutables frente a CSRF según el diseño final.

---

# 41. API y contratos

Define endpoints coherentes bajo `/api`.

No copies las URLs legacy como contrato nuevo.

Ejemplo conceptual:

```text
/api/me
/api/dictionaries
/api/dictionaries/:id
/api/dictionaries/:id/entries
/api/entries/:id
/api/entries/:id/revisions
/api/entries/:id/submissions
/api/classrooms/:id/submissions
/api/submissions/:id/publish
/api/submissions/:id/reject
/api/comments
/api/media
/api/vocabularies
```

Usa verbos HTTP correctamente.

Evita acciones destructivas mediante GET.

El legacy contiene operaciones de borrar/ocultar mediante GET; no reproduzcas ese patrón.

---

# 42. Validación compartida

Evalúa Zod, TypeBox u otra opción actual y mantenida.

Debe existir una fuente clara de validación para:

- requests API;
- formularios;
- seeds;
- imports;
- configuración;
- fixtures.

No dupliques reglas manualmente en frontend y backend si puede evitarse.

Las reglas de autorización no pertenecen al schema de validación.

---

# 43. Comentarios y feedback docente

Conserva comentarios si forman parte del uso real.

Unifica comentarios generales y comentarios de entrada si un modelo polimórfico sencillo lo resuelve sin perder claridad.

No uses polimorfismo oscuro si complica integridad referencial.

Debe quedar claro:

- autor;
- destinatario/contexto;
- aula;
- entrada/submission si aplica;
- fecha;
- visibilidad;
- estado.

No permitas HTML arbitrario en comentarios.

---

# 44. Medios

Audita los tipos reales de medios:

- imagen;
- audio;
- vídeo;
- URL externa;
- otros.

No guardes grandes binarios dentro de PostgreSQL en producción por defecto.

Crea un `MediaStorage` adapter.

Implementación inicial de producción recomendada:

```text
filesystem persistente
```

con estructura controlada y nombres no confiados al cliente.

Permite diseñar un adapter S3-compatible futuro sin convertirlo en requisito actual.

La demo puede usar:

- assets estáticos sembrados;
- almacenamiento local del navegador para uploads pequeños si se implementan.

No necesita imitar NFS.

---

# 45. Seguridad de uploads

Para cualquier upload:

- límite de tamaño;
- allowlist de MIME/extensiones;
- detección basada en contenido cuando sea viable;
- nombres aleatorios/controlados;
- no confiar en nombre original;
- impedir path traversal;
- servir con headers correctos;
- no ejecutar contenido subido;
- sanitizar SVG o directamente no aceptarlo si no es necesario;
- comprobar imágenes corruptas;
- proteger contra bombas de descompresión cuando corresponda.

No uses una carpeta pública escribible sin controles.

---

# 46. Migración de medios legacy

La migración debe incluir los ficheros, no solo filas de base de datos.

Crea un proceso que:

1. lea referencias legacy;
2. localice el fichero en el almacenamiento antiguo;
3. calcule checksum;
4. copie al nuevo layout;
5. cree `media_assets`;
6. relacione con acepciones/entries;
7. registre ausentes;
8. registre duplicados;
9. no modifique originales.

Genera informe:

```text
migrados
faltantes
corruptos
duplicados
sin referencia
referencias rotas
```

No falles silenciosamente.

---

# 47. Exportación

Audita qué exportaciones se usan realmente.

Como mínimo evalúa conservar:

- PDF de diccionario personal;
- PDF de diccionario de aula;
- CSV;
- JSON estructurado.

Preferencia:

- CSV/JSON generados desde datos estructurados;
- PDF mediante impresión CSS/browser o una librería cliente si produce resultado fiable;
- no mantener un servicio PDF de servidor si no hace falta.

Si la salida PDF necesita composición compleja, investiga alternativas actuales y documenta la elección.

No cargues una librería PDF pesada en el bundle inicial si solo se usa al exportar.

---

# 48. Importación

Añade importación solo si existe un caso real o si facilita la migración/interop.

Si se implementa:

- validación previa;
- preview;
- errores por fila/entrada;
- transacción;
- no crear datos parcialmente corruptos.

No confundas el importador legacy de migración con una función de usuario final.

---

# 49. Búsqueda

Conserva como mínimo:

- búsqueda por palabra;
- inicial;
- temática;
- contenido relevante si actualmente aporta valor.

La demo y producción deben devolver resultados semánticamente equivalentes.

No hagas depender el flujo básico de una extensión PostgreSQL que PGlite no pueda ejecutar.

Optimiza producción después de medir.

Puede existir una implementación SQL sencilla compartida y optimizaciones de PostgreSQL detrás del repositorio si los contract tests demuestran equivalencia.

---

# 50. Lexonomy: visor separado del editor

Adopta la idea de separar conceptualmente:

```text
editar
```

de:

```text
consultar/publicar
```

Una entrada publicada del diccionario de aula debe tener una vista limpia y legible.

La vista de consulta no debe mostrar controles de edición a alumnado sin permisos.

Permite URL directa a una entrada si encaja con la navegación.

---

# 51. Plantillas y configuración de entrada

El legacy ya tiene conceptos de campos visibles/obligatorios y máximo de acepciones.

No los pierdas.

Modernízalos como una configuración tipada del aula.

Evalúa una pequeña `entry_policy`, por ejemplo:

```text
requiredFields
visibleFields
maxSenses
allowedMediaTypes
```

No construyas un lenguaje de schema completo salvo que los requisitos actuales lo necesiten.

---

# 52. Estados y borrado

Sustituye flags opacos `estado = 0/1` por tipos explícitos.

Usa:

- boolean cuando sea boolean;
- enum cuando existan varios estados reales;
- `deleted_at` cuando necesites soft delete.

No mezcles `estado`, `deleted_at` y ausencia física sin reglas claras.

Documenta la semántica.

---

# 53. Auditoría

El legacy usa `owen-it/laravel-auditing`.

No copies toda esa implementación.

Determina qué auditoría se necesita realmente.

Una tabla `audit_events` sencilla puede registrar acciones sensibles:

```text
user
action
entity_type
entity_id
metadata mínima
timestamp
```

No guardes contraseñas, tokens, contenido sensible innecesario ni snapshots completos indiscriminadamente.

El historial funcional de entradas debe resolverse con `entry_revisions`, no mezclarse con el log técnico.

---

# 54. Correo

Audita si las invitaciones o avisos por email siguen siendo necesarios.

Producción debe usar un `MailAdapter` si se conserva.

Demo:

- nunca envía correo;
- puede mostrar el mensaje que se habría enviado;
- o simular la invitación mediante UI local.

No hagas que una caída SMTP rompa operaciones que no dependen estrictamente del correo.

---

# 55. Vigencia y curso escolar

La lógica de `INICIO_CURSO`, `VIGENCIA_MAX`, diccionarios atemporales y activación debe convertirse en reglas de dominio explícitas y testeadas.

No la entierres en controladores o consultas.

Crea funciones/casos de uso deterministas para:

- calcular curso vigente;
- decidir vigencia;
- activar/desactivar;
- tratar atemporales.

Prueba fechas límite.

No dependas de la fecha del sistema en tests: inyecta reloj.

---

# 56. Datos educativos maestros

Audita:

- enseñanzas;
- niveles;
- áreas/materias;
- tipos de diccionario;
- campos;
- valores;
- pautas.

Decide cuáles son:

```text
configuración del producto
```

y cuáles son:

```text
datos administrables
```

No metas todo en seeds inmutables si cambia cada curso.

No conviertas todo en tablas editables si son constantes técnicas.

Documenta la decisión.

---

# 57. Pautas

Investiga el uso real de `mst_pautas` / `dic_aula_pautas`.

Si se usa para instrucciones docentes:

- conviértelo en una funcionalidad clara del aula;
- soporte texto y fichero solo si ambos se usan;
- evita campos o pantallas legacy sin uso.

---

# 58. Diseño visual

No intentes conservar visualmente Bootstrap 4/Voyager.

Crea una UI actual, sobria y educativa.

Requisitos:

- responsive;
- mobile first razonable;
- buena jerarquía;
- ancho de lectura controlado;
- navegación consistente;
- estados vacíos útiles;
- skeletons solo donde aporten valor;
- mensajes claros;
- no abusar de modales;
- no esconder acciones primarias en kebab menus.

No conviertas una herramienta escolar en un dashboard corporativo genérico.

---

# 59. Login

La pantalla de login de demo debe ser suficiente para entender el producto sin documentación.

Incluye:

- nombre Lexicán;
- breve descripción;
- formulario;
- cuentas demo debajo;
- aviso de almacenamiento local;
- enlace a código fuente/documentación si corresponde;
- versión/build.

En producción, no muestres contraseñas demo.

El build debe distinguir correctamente:

```text
demo
production
```

sin un `if (hostname.includes('github.io'))` frágil.

---

# 60. Routing y GitHub Pages

Configura Vite correctamente para el subdirectorio `/lexican/`.

La demo debe soportar refresco/navegación sin 404.

Puedes usar:

- hash routing en demo;
- browser history en producción;

si eso reduce complejidad.

No obligues a producción a usar `#` si no hace falta.

Añade tests E2E sobre la URL con base path de Pages.

---

# 61. Accesibilidad

Objetivo: WCAG 2.2 AA en los flujos principales.

Como mínimo:

- navegación completa por teclado;
- foco visible;
- labels reales;
- errores asociados al campo;
- headings lógicos;
- landmarks;
- tablas accesibles;
- dialogs correctos;
- contraste;
- no depender solo de color;
- estados `aria-*` únicamente cuando HTML nativo no basta;
- drag/reorder con alternativa de teclado;
- avisos con región live cuando proceda.

Añade axe con Playwright o alternativa actual verificada.

Cero errores automáticos no equivale a cumplimiento total.

Documenta comprobaciones manuales.

---

# 62. Responsive

Prueba al menos:

```text
360x800
768x1024
1366x768
1920x1080
```

Las tablas administrativas deben degradar a cards/scroll controlado cuando corresponda.

El editor de acepciones debe ser utilizable en móvil.

No dupliques DOM para desktop y mobile.

---

# 63. Seguridad web

Como mínimo:

- CSP razonable;
- `X-Content-Type-Options`;
- `Referrer-Policy`;
- protección clickjacking según despliegue;
- cookies seguras;
- CSRF;
- CORS cerrado por defecto;
- rate limits en login/callbacks/endpoints sensibles cuando proceda;
- validación server-side;
- autorización server-side;
- consultas parametrizadas;
- sanitización si existe HTML;
- no `eval`;
- no secretos en frontend;
- no secretos en repo;
- logs sin tokens;
- dependencias auditadas.

No uses `dangerouslySetInnerHTML` con contenido de usuario salvo sanitización explícita y tests.

---

# 64. Errores

Implementa errores de dominio y errores HTTP coherentes.

La UI debe distinguir:

- validación;
- sin permisos;
- no encontrado;
- conflicto;
- sesión expirada;
- fallo de red;
- fallo interno.

No muestres stack traces a usuarios.

No uses `alert()` para el flujo normal.

Los E2E deben fallar ante errores inesperados de consola.

---

# 65. Concurrencia

Producción tendrá varios usuarios reales.

Evita el patrón “último guardado gana” sin detectar conflictos en entradas revisadas.

Evalúa optimistic locking mediante:

```text
version
updated_at
```

Para operaciones críticas de edición/publicación, devuelve `409 Conflict` cuando el registro cambió desde que el usuario lo cargó.

No construyas colaboración en tiempo real si no es necesaria.

---

# 66. Modelo legacy: documentar mapping

Crea:

```text
docs/LEGACY-DATA-MAPPING.md
```

Debe mapear como mínimo:

```text
users
personas
users_personas
centros
users_centros

mst_ensenanzas
mst_areas_materias
mst_nivel_estudios
mst_tipos_diccionario_aula
mst_campos_entrada
mst_campos_valores

 dic_personal
 dp_entradas
 dp_acepciones
 dp_acepciones_tematicas
 dp_acepciones_medios

 dic_aula
 dic_aula_participantes
 dic_aula_campos
 dic_aula_pautas
 dic_aula_destinatario_avisos

 dp_envios
 envios_entradas
 envios_acepciones
 envios_acepciones_tematicas
 envios_acepciones_medios
 dic_aula_entradas

 comentarios_generales
 comentarios_entradas
```

Incluye tablas añadidas por migraciones posteriores.

Para cada tabla/campo indica:

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|

No migres una columna sin saber qué significa.

---

# 67. Migración MariaDB/MySQL → PostgreSQL

Crea una herramienta de migración independiente de la aplicación web.

Comando conceptual:

```bash
npm run migrate:legacy -- \
  --source "$LEGACY_DATABASE_URL" \
  --target "$DATABASE_URL" \
  --media-source /path/to/legacy/storage \
  --media-target /path/to/new/storage \
  --dry-run
```

Debe soportar:

```text
--dry-run
--resume o checkpoint si hace falta
--report <file>
--fail-on-orphans
```

El importador puede usar `mysql2` u otro cliente mantenido **solo como dependencia de migración**, no como dependencia runtime de producción.

---

# 68. Orden de migración

El orden conceptual recomendado es:

1. vocabularios maestros;
2. usuarios/personas;
3. centros y relaciones;
4. diccionarios personales;
5. entradas personales;
6. acepciones;
7. temáticas;
8. medios personales;
9. diccionarios de aula;
10. miembros;
11. configuración/pautas;
12. envíos;
13. snapshots de envíos;
14. entradas publicadas de aula;
15. comentarios;
16. auditoría relevante;
17. medios relacionados;
18. tablas auxiliares restantes.

No des por correcto este orden hasta analizar foreign keys y semántica real.

---

# 69. No dual-write

No implementes dual-write MySQL/PostgreSQL salvo que el plan real de despliegue lo exija de forma inevitable.

Preferencia:

```text
ensayos de migración
→ ventana de solo lectura
→ migración final
→ validación
→ cambio de servicio
```

Es una aplicación suficientemente acotada para evitar una arquitectura permanente de sincronización entre bases.

---

# 70. Migración idempotente y verificable

Una ejecución de prueba debe poder repetirse sin producir duplicados inesperados.

Puedes:

- vaciar un schema destino de ensayo;
- usar tablas de staging;
- usar claves de `legacy_id` únicas.

Al terminar genera métricas por tabla:

```text
legacy count
new count
mapped
skipped
invalid
orphaned
```

Comprueba:

- FK;
- unicidad;
- cardinalidades;
- estados;
- orden de acepciones;
- relaciones temática;
- relaciones medios;
- pertenencia a aula;
- autoría;
- trazabilidad de envíos/publicaciones.

---

# 71. Datos inconsistentes legacy

No “arregles” silenciosamente datos dudosos durante la migración.

Clasifica:

```text
repairable automatically
needs rule
needs human review
cannot migrate
```

Genera CSV/JSON con los registros problemáticos.

Toda corrección automática debe estar documentada y testeada.

---

# 72. Ensayo de migración

Antes del corte final:

1. restaura una copia anonimizada o de entorno autorizado;
2. ejecuta importación completa;
3. verifica counts;
4. verifica checksums de medios;
5. ejecuta tests de integridad;
6. ejecuta E2E sobre datos migrados;
7. compara entradas seleccionadas manualmente;
8. mide duración;
9. documenta pasos exactos;
10. documenta rollback.

No pruebes el migrador por primera vez contra producción.

---

# 73. Rollback

La migración debe tener un plan de rollback operativo.

Durante el primer corte:

- conserva base legacy intacta/read-only;
- conserva medios legacy;
- no borres nada;
- no reescribas ficheros originales;
- registra timestamp del corte;
- registra versión del código;
- registra versión de migraciones.

Rollback inicial debe consistir en volver a apuntar al sistema legacy, no en intentar “desmigrar” PostgreSQL hacia MySQL.

---

# 74. Tests unitarios

Usa Vitest salvo que la investigación demuestre una alternativa claramente mejor.

Prueba intensamente:

- reglas de curso/vigencia;
- autorización;
- validación de entrada;
- máximo de acepciones;
- políticas del aula;
- submissions;
- publicación;
- revisiones;
- mapping DMLex si existe;
- búsqueda pura/helper;
- serialización;
- exports;
- transforms de migración.

No pruebes getters triviales solo para inflar cobertura.

---

# 75. Contract tests de persistencia

Este punto es obligatorio.

Crea una suite reutilizable que verifique el comportamiento del repositorio sobre:

```text
PostgreSQL real
PGlite
```

Casos:

- crear diccionario;
- crear entrada;
- añadir varias acepciones;
- editar;
- borrar/soft-delete;
- buscar;
- submission;
- revisar;
- publicar;
- comments;
- memberships;
- restricciones y errores de integridad.

La demo no debe usar una base “parecida”. Debe usar el mismo modelo conceptual.

---

# 76. Tests de API

Levanta PostgreSQL de test aislado.

Prueba:

- auth middleware;
- permisos;
- validación;
- status codes;
- conflictos;
- transacciones;
- uploads;
- downloads;
- errores;
- sesión.

No dependas de CAS/CAUCE real: usa adapters fake.

---

# 77. Tests E2E de demo

GitHub Pages debe estar protegido por E2E.

Como mínimo:

## Alumno

1. abre login;
2. ve cuentas demo;
3. entra como `alumno1@ejemplo.com`;
4. abre diccionario personal;
5. crea entrada;
6. añade dos acepciones;
7. guarda;
8. busca;
9. envía al aula;
10. cierra sesión.

## Profesor

1. entra como `profesor@ejemplo.com`;
2. ve submission pendiente;
3. abre;
4. comenta;
5. publica;
6. busca la entrada publicada;
7. cierra sesión.

## Alumno de nuevo

1. entra como alumno;
2. ve estado publicado/comentario según permisos.

Ejecuta al menos Chromium.

Siempre que sea razonable añade Firefox y WebKit.

---

# 78. Tests E2E de producción

Crea un entorno E2E con:

```text
web + api + PostgreSQL
```

sin CAS real.

Usa un proveedor de auth fake/dev que solo esté habilitado en test/desarrollo explícito.

Ejecuta los mismos journeys principales que la demo para detectar divergencias.

---

# 79. Tests de migración

Crea fixtures legacy pequeños pero representativos en MySQL/MariaDB.

Incluye:

- usuario/profesor/alumno;
- centro;
- diccionario personal;
- varias acepciones;
- temática;
- medio;
- aula;
- participantes;
- submission;
- publicación;
- comentario;
- soft-delete;
- dato huérfano fixture separado.

CI debe demostrar:

```text
legacy fixture → migración → PostgreSQL → assertions
```

No bases el test en un dump real con datos personales.

---

# 80. Visual tests

Añade screenshots de referencia para:

- login demo;
- alumno home;
- editor;
- profesor dashboard;
- revisión;
- visor de diccionario;
- móvil.

No hagas bloqueantes todos los píxeles desde el primer día.

Úsalos inicialmente para revisión humana y estabiliza después los componentes deterministas.

---

# 81. Cobertura

No persigas 100 %.

Después de separar dominio y aplicación, establece gates razonables.

Objetivo inicial:

- dominio/application: ~90 % líneas/branches cuando sea realista;
- migración: alta cobertura de transforms;
- UI: cubierta principalmente por unit/component + E2E.

No excluyas código difícil simplemente para mejorar la cifra.

Documenta qué no cubre la métrica.

---

# 82. Build

Objetivos:

```bash
npm ci
npm run dev
npm run build
npm run build:demo
```

`build:demo` debe generar una carpeta completamente estática.

`build` de producción debe generar:

- frontend estático;
- API ejecutable.

No versiones `dist/` en `main` salvo razón operativa fuerte.

---

# 83. Scripts npm

Objetivo aproximado:

```json
{
  "scripts": {
    "dev": "...",
    "dev:web": "...",
    "dev:api": "...",
    "build": "...",
    "build:demo": "...",
    "lint": "...",
    "format": "...",
    "typecheck": "...",
    "test": "...",
    "test:integration": "...",
    "test:contracts": "...",
    "e2e": "...",
    "e2e:demo": "...",
    "db:generate": "...",
    "db:migrate": "...",
    "db:seed": "...",
    "demo:reset": "...",
    "migrate:legacy": "...",
    "check": "...",
    "audit": "...",
    "licenses": "..."
  }
}
```

Ajusta nombres al resultado real.

No crees tres scripts diferentes que ejecuten la misma suite accidentalmente.

---

# 84. Makefile

Mantén un Makefile pequeño que delegue en npm.

Objetivo:

```text
up
dev
build
build-demo
test
integration
e2e
lint
fix
check
migrate
clean
help
```

No dupliques lógica compleja del `package.json`.

---

# 85. Desarrollo local

Debe existir un flujo sencillo.

Preferencia:

```bash
cp .env.example .env
make up
npm ci
npm run db:migrate
npm run db:seed
npm run dev
```

Puedes usar Docker Compose únicamente para dependencias de infraestructura como PostgreSQL.

No obligues a desarrollar dentro del contenedor si no aporta valor.

Documenta también ejecución completamente containerizada si es útil para CI/servidores.

---

# 86. Docker de producción

Crea imagen multi-stage pequeña.

Preferencia:

- Node LTS/current verificado;
- usuario no root;
- filesystem de aplicación read-only cuando sea posible;
- volumen separado solo para medios si se usa filesystem;
- healthcheck;
- señalización correcta;
- no compilar en runtime;
- no incluir devDependencies finales;
- no copiar `.env` dentro de la imagen.

Alpine puede usarse si las dependencias elegidas funcionan correctamente en musl. No fuerces Alpine si introduce incompatibilidades innecesarias.

---

# 87. GitHub Pages

Crea workflow específico de Pages.

Debe:

1. ejecutarse solo tras CI correcto en `main`;
2. `npm ci`;
3. `npm run build:demo`;
4. subir artifact estático;
5. desplegarlo mediante actions oficiales actuales;
6. tener permisos mínimos.

No mantengas manualmente una rama `gh-pages` si el despliegue por artifact funciona.

La demo no debe contener secretos.

Añade una comprobación automatizada del bundle para evitar accidentalmente cadenas como tokens o URLs internas sensibles cuando sea razonable.

---

# 88. CI

CI debe ejecutar conceptualmente:

```bash
npm ci
npm run lint
npm run typecheck
npm test
npm run test:contracts
npm run test:integration
npm run build
npm run build:demo
npm run e2e:demo
npm run audit
npm run licenses
```

Los tests de migración pueden ejecutarse en job separado con MariaDB/MySQL + PostgreSQL.

No uses `continue-on-error` en checks críticos.

Pages y releases dependen de CI correcto.

---

# 89. GitHub Actions hardening

Sigue el patrón de mínimo privilegio usado en Aritmates/Tonga.

Por defecto:

```yaml
permissions:
  contents: read
```

Añade permisos extra solo al job que realmente los necesite.

Usa actions oficiales/mantenidas y versiones actuales verificadas.

Evalúa pinning por SHA para actions sensibles si el modelo de Aritmates/Tonga ya lo utiliza y el mantenimiento sigue siendo razonable.

No uses `write-all`.

---

# 90. Dependabot y supply chain

Configura Dependabot para:

- npm;
- GitHub Actions.

Añade:

- lockfile;
- instalación reproducible;
- `npm audit` o alternativa actual;
- inventario de licencias;
- SBOM si aporta valor operativo.

No hagas auto-merge ciego de majors.

No introduzcas una plataforma de supply chain mayor que la aplicación.

---

# 91. Releases

Workflow para tags `v*`.

Debe:

1. ejecutar quality gate;
2. construir frontend/API;
3. construir demo si procede;
4. construir imagen OCI si se decide publicarla;
5. generar artifact de despliegue;
6. publicar release notes/changelog;
7. adjuntar SBOM/licencias si ya forman parte del proceso.

No incluyas `node_modules` en ZIPs de frontend.

---

# 92. Licencia del proyecto

Actualmente no debes asumir una licencia solo porque Aritmates/Tonga tengan una.

Audita:

- derechos del código original;
- cabeceras;
- documentación contractual disponible en repo;
- assets;
- código de terceros;
- Voyager y vendor heredado;
- iconos/fuentes/medios.

No añadas AGPL/MIT/otra licencia al código original sin determinar que el titular puede hacerlo.

Crea:

```text
docs/LICENSING.md
THIRD_PARTY_NOTICES.md
```

Añade REUSE/SPDX solo cuando los titulares y licencias estén correctamente identificados.

---

# 93. No arrastres vendor legacy

No migres:

- Composer;
- Laravel;
- Voyager;
- Laravel Mix;
- Bootstrap 4;
- jQuery;
- TinyMCE;
- PHPDoc/phpdox;
- scripts OpenShift antiguos;

simplemente para conservar compatibilidad.

Extrae requisitos y datos, no infraestructura obsoleta.

Cuando la nueva versión cubra una funcionalidad y sus tests, elimina la implementación legacy de `main` en una fase explícita.

`upstream` conserva la referencia histórica.

---

# 94. Código de terceros

Cada fragmento reutilizado debe registrar:

- proyecto;
- URL;
- versión/commit;
- autor/titular;
- licencia;
- modificaciones.

Repositorio público no significa reutilización autorizada.

Si no hay licencia clara, no copies el código.

Reimplementa la idea.

---

# 95. Documentación

Crea como mínimo:

```text
README.md
AGENTS.md
developers.md
CHANGELOG.md

docs/
  ARCHITECTURE.md
  DATA-MODEL.md
  LEGACY-DATA-MAPPING.md
  MIGRATION.md
  DEMO.md
  AUTHENTICATION.md
  SECURITY.md
  PRIVACY.md
  ACCESSIBILITY.md
  TESTING.md
  DEPLOYMENT.md
  LICENSING.md
  DMLEX-MAPPING.md
  MODERNIZATION.md
  MODERNIZATION-REPORT.md
  adr/
```

Documentación humana en español.

Código y comentarios en inglés.

No dejes `developers.md` como una colección de instrucciones históricas contradictorias.

---

# 96. AGENTS.md

Escribe un `AGENTS.md` específico para Lexicán.

Debe fijar:

- `upstream` es histórica;
- `main` es mantenida;
- stack objetivo;
- arquitectura;
- separación demo/producción;
- PostgreSQL único en producción;
- PGlite solo demo/local cuando corresponda;
- reglas de schema/migrations;
- reglas de auth;
- no poner secretos;
- tests obligatorios;
- migración legacy;
- privacidad;
- accesibilidad;
- licencia;
- comandos;
- política de commits/PRs.

---

# 97. Skills para agentes

Estudia `.agents/skills` / `.claude/skills` de Aritmates/Tonga si existen.

Reutiliza únicamente skills que tengan sentido y licencia clara.

Candidatos:

- GitHub Actions hardening;
- Playwright;
- Playwright trace;
- security audit;
- test-gap audit;
- accessibility;
- migration review.

No llenes el repo de instrucciones duplicadas.

---

# 98. Informe de modernización

Crea un informe reproducible comparable al de Aritmates.

Compara:

```text
upstream
vs
main modernizada
```

Mide al menos:

- dependencias;
- tecnologías;
- tamaño del frontend;
- JS/CSS inicial;
- requests iniciales;
- tiempo de build;
- número de rutas API;
- número de tablas;
- tests;
- cobertura;
- E2E;
- vulnerabilidades;
- licencias identificadas;
- código legacy eliminado;
- complejidad de despliegue;
- servicios obligatorios;
- tiempos del migrador;
- número de registros migrados en fixture/ensayo;
- accesibilidad automática;
- errores de consola.

No inventes cifras.

Genera métricas mediante scripts.

El informe debe incluir también qué empeoró, qué no se migró y qué deuda queda.

---

# 99. Modernización por fases

No hagas un commit gigantesco.

## Fase 0 — auditoría y caracterización

- SHAs;
- upstream/main;
- inventario funcional;
- inventario técnico;
- licencias;
- datos;
- baseline;
- E2E legacy donde sea posible;
- mapping inicial.

## Fase 1 — esqueleto moderno

- workspaces;
- TypeScript;
- React/Vite;
- Fastify;
- lint;
- format;
- Vitest;
- Playwright;
- CI;
- estructura docs.

Sin borrar legacy todavía.

## Fase 2 — nuevo modelo PostgreSQL

- Drizzle schema;
- migrations;
- vocabularios;
- usuarios;
- diccionarios;
- entries/senses;
- submissions;
- revisions;
- comments;
- media metadata.

## Fase 3 — demo PGlite

- migraciones en navegador;
- seed;
- cuentas demo;
- login local;
- alumno;
- profesor;
- reset;
- GitHub Pages.

GitHub Pages debe ser funcional pronto, no solo al final.

## Fase 4 — API producción

- auth abstraction;
- repositories;
- Fastify;
- sessions;
- CRUD;
- permissions;
- uploads;
- exports.

## Fase 5 — UI funcional completa

- diccionario personal;
- editor;
- aula;
- participantes;
- submissions;
- review;
- publish;
- comments;
- search;
- exports.

## Fase 6 — autenticación institucional

- CAS;
- CAUCE;
- session security;
- logout;
- mapping de usuarios.

## Fase 7 — migrador legacy

- MySQL reader;
- transforms;
- mapping;
- medios;
- reports;
- fixtures;
- ensayo.

## Fase 8 — administración

- vocabularios;
- vigencia;
- avisos;
- funciones realmente usadas de Voyager.

## Fase 9 — hardening

- security;
- privacy;
- accessibility;
- performance;
- licenses;
- cross-browser;
- migration rehearsal.

## Fase 10 — cutover

- backup;
- read-only legacy;
- migración;
- validación;
- smoke/E2E;
- cambio de servicio;
- rollback documentado.

## Fase 11 — limpieza

Solo después de cobertura funcional suficiente elimina de `main`:

- Laravel;
- PHP;
- Composer;
- Voyager;
- Mix;
- Blade;
- Bootstrap legacy;
- jQuery;
- scripts de despliegue obsoletos;
- vendor/binarios innecesarios;
- código muerto.

---

# 100. No big bang ciego

Aunque el destino sea una reescritura completa de tecnología, no borres el legacy antes de entenderlo.

Patrón preferido:

```text
caracterizar
→ modelar
→ implementar nuevo flujo
→ comparar
→ migrar datos
→ probar
→ retirar legacy
```

No:

```text
rm -rf app resources database
→ empezar de memoria
```

---

# 101. Pull requests

PRs pequeños y revisables.

Commits:

- inglés;
- un cambio lógico;
- sin atribuir autoría a agentes de IA.

PRs:

- título en inglés;
- descripción Markdown en inglés;
- resumen;
- tests;
- screenshots si UI;
- migraciones de schema destacadas;
- implicaciones de datos;
- implicaciones de seguridad/licencia;
- rollback cuando aplique.

No trabajes directamente sobre `main`.

No toques `upstream`.

---

# 102. Definition of Done

No consideres finalizada la modernización hasta que:

- `upstream` siga intacta;
- funcionalidades útiles estén inventariadas;
- modelo legacy esté documentado;
- modelo nuevo esté documentado;
- PostgreSQL sea la única base de producción;
- exista migrador MySQL/MariaDB → PostgreSQL;
- migrador tenga dry-run e informe;
- medios estén contemplados;
- exista trazabilidad legacy;
- PGlite funcione en navegador;
- demo persista en IndexedDB;
- demo tenga reset;
- GitHub Pages funcione;
- login muestre cuentas demo;
- `profesor@ejemplo.com` funcione;
- `alumno1@ejemplo.com` funcione;
- exista al menos un segundo alumno demo;
- flujo alumno → envío → profesor → publicación funcione enteramente en Pages;
- producción use API real;
- auth institucional esté preservada o reemplazada por solución equivalente aprobada;
- CAS no dependa de una biblioteca abandonada sin justificar;
- CAUCE esté encapsulado;
- roles estén separados correctamente;
- Voyager no sea dependencia de producción;
- Laravel no sea dependencia de producción;
- jQuery no sea dependencia de producción;
- Bootstrap legacy no sea dependencia de producción;
- TinyMCE no sea dependencia salvo requisito demostrado;
- entradas/acepciones tengan modelo limpio;
- submissions sean snapshots/revisions coherentes;
- publicación conserve procedencia;
- comments funcionen si son requisito;
- búsqueda funcione;
- exportaciones acordadas funcionen;
- uploads sean seguros;
- tests unitarios pasen;
- contract tests PostgreSQL/PGlite pasen;
- API integration tests pasen;
- E2E demo pase;
- E2E producción pase;
- migration tests pasen;
- lint pase;
- typecheck pase;
- build producción pase;
- build demo pase;
- no haya errores inesperados de consola;
- auditoría de dependencias pase;
- licencias estén inventariadas;
- privacidad esté documentada;
- accesibilidad esté documentada;
- despliegue esté documentado;
- informe de modernización esté generado;
- deuda técnica restante esté explícita.

---

# 103. Entregables

Al finalizar quiero:

1. aplicación modernizada;
2. demo GitHub Pages completamente funcional;
3. backend Fastify;
4. PostgreSQL schema/migrations;
5. PGlite demo;
6. seeds demo;
7. cuentas demo visibles bajo login;
8. migrador legacy;
9. migración de medios;
10. informes de migración;
11. tests unitarios;
12. contract tests PostgreSQL/PGlite;
13. tests API;
14. E2E demo;
15. E2E producción;
16. tests de migración;
17. CI;
18. Pages workflow;
19. release workflow;
20. Dockerfile producción;
21. documentación;
22. AGENTS.md;
23. ADRs;
24. mapping legacy/nuevo;
25. mapping DMLex;
26. inventario de dependencias;
27. inventario de licencias;
28. THIRD_PARTY_NOTICES;
29. métricas reproducibles;
30. informe de modernización;
31. screenshots;
32. changelog;
33. plan de rollback/cutover;
34. lista explícita de deuda técnica.

---

# 104. Primer resultado obligatorio antes de implementar la reescritura

Antes de realizar la sustitución principal del código, entrega un informe en Markdown con estas secciones.

## Estado actual

Explica qué hace Lexicán y cómo está construido.

## `upstream` vs `main`

Incluye SHAs, merge-base y diferencias.

## Inventario funcional

Tabla de funcionalidades con estado:

```text
conservar
rediseñar
eliminar
dudosa
```

## Inventario tecnológico

Incluye versiones verificadas y estado de mantenimiento.

## Modelo legacy

Diagrama ER simplificado.

Explica especialmente:

```text
dic_personal
→ dp_entradas
→ dp_acepciones

personal
→ dp_envios
→ envios_entradas
→ envios_acepciones
→ dic_aula_entradas
→ aula
```

## Riesgos

Como mínimo:

- framework/backend legacy;
- autenticación CAS;
- dependencia CAUCE;
- Voyager;
- modelo duplicado de envíos/publicación;
- PII;
- NFS/medios;
- migración MariaDB→PostgreSQL;
- ausencia/estado de CI;
- tests;
- licencias;
- código de terceros;
- exportaciones;
- contenido HTML legacy si existe.

## Arquitectura propuesta

Incluye diagrama producción y demo.

## ADR tecnológico

Justifica o revalida:

- React/Vite;
- Fastify;
- PostgreSQL;
- Drizzle;
- PGlite;
- Vitest;
- Playwright;
- validación;
- estrategia CAS;
- almacenamiento de medios.

## Nuevo modelo de datos

Diagrama y tabla de entidades.

## Estrategia PGlite/GitHub Pages

Demuestra cómo funciona sin backend y cómo se resetean seeds.

## Plan de migración

Incluye:

- mapping;
- orden;
- medios;
- verificación;
- dry-run;
- ensayo;
- cutover;
- rollback.

## Plan PR por PR

Propón una secuencia pequeña y revisable.

Solo después comienza la implementación principal.

---

# 105. Decisiones que no debes volver a abrir sin motivo fuerte

Estas decisiones ya están tomadas para evitar análisis infinito:

```text
Producción: PostgreSQL solamente.
Demo Pages: PGlite + IndexedDB.
Frontend: TypeScript + React + Vite.
Backend: TypeScript + Fastify sobre Node.js.
ORM/schema: Drizzle como hipótesis principal.
Unit tests: Vitest como hipótesis principal.
E2E: Playwright.
No conservar Laravel/Voyager como arquitectura destino.
No soportar MySQL como motor nuevo de producción.
No necesitar backend en GitHub Pages.
No usar datos reales en demo.
```

Puedes cambiar una de ellas únicamente si encuentras un bloqueo real durante la investigación y lo documentas mediante ADR con evidencia actual de Context7/documentación oficial/Internet.

No cambies una decisión simplemente por preferencia personal.

---

# 106. Decisiones que debes tomar tú tras investigar

Debes investigar y decidir con ADR:

- cliente/integración CAS concreta;
- si CAS se integra en Node o mediante proxy externo;
- estrategia exacta de sesión;
- librería de schemas/validación;
- librería de generación PDF, si se necesita;
- estrategia exacta de almacenamiento local de uploads en demo;
- estrategia de almacenamiento de medios en producción más allá del filesystem inicial;
- si merece la pena implementar export DMLex ya;
- qué funciones de administración legacy se conservan;
- qué campos personales legacy son realmente necesarios;
- qué contenido rich-text legacy debe sanearse/conservarse;
- si se necesita una librería de server-state en React.

No preguntes por cada detalle menor. Investiga, toma una decisión razonable y documenta.

---

# 107. Regla final

No modernices Lexicán para que “parezca una aplicación de 2026”.

Modernízalo para que:

- el alumnado entienda qué tiene que hacer;
- el profesorado revise y publique sin fricción;
- los datos tengan un modelo coherente;
- una persona nueva pueda desarrollar localmente sin arqueología;
- la demo pueda probarse desde GitHub Pages sin infraestructura;
- producción sea sencilla;
- la migración sea verificable;
- la privacidad sea defendible;
- los tests protejan el comportamiento;
- podamos sustituir el sistema legacy con una ruta de rollback clara.

Cada cambio debe responder al menos a una de estas preguntas:

- ¿reduce complejidad?
- ¿reduce riesgo?
- ¿mejora el modelo de datos?
- ¿mejora mantenibilidad?
- ¿mejora seguridad?
- ¿mejora privacidad?
- ¿mejora accesibilidad?
- ¿mejora UX?
- ¿mejora testabilidad?
- ¿mejora interoperabilidad?
- ¿hace la migración más segura?
- ¿elimina una dependencia o comportamiento obsoleto?

Si no responde a ninguna, probablemente no necesitamos ese cambio.
