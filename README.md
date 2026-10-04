# LexiCán

[![CI](https://github.com/ateeducacion/lexican/actions/workflows/ci.yml/badge.svg)](https://github.com/ateeducacion/lexican/actions/workflows/ci.yml) [![codecov](https://codecov.io/gh/ateeducacion/lexican/graph/badge.svg)](https://codecov.io/gh/ateeducacion/lexican) [![Licencia: AGPL-3.0-or-later](https://img.shields.io/badge/licencia-AGPL--3.0--or--later-0f4c81)](LICENSE)

Diccionarios personales y de aula para aprender léxico. Cada alumno o alumna construye su propio diccionario con
palabras, acepciones, ejemplos, imágenes, audio y vídeo; el profesorado reúne lo mejor en un diccionario de aula que
revisa, comenta y publica.

**Demo:** <https://ateeducacion.github.io/lexican/>

La demo funciona entera en tu navegador (PostgreSQL en WebAssembly) y guarda los datos solo ahí. Usuarios, contraseñas
y datos son ficticios:

| Cuenta | Correo | Contraseña |
|---|---|---|
| Profesora (Yaiza Tutoriales) | `profesor@ejemplo.com` | `profesor` |
| Alumno 1 (Alumno Padrón Armas) | `alumno1@ejemplo.com` | `alumno1` |
| Alumna 2 (Alumna Armas Padrón) | `alumno2@ejemplo.com` | `alumno2` |
| Administración | `admin@ejemplo.com` | `admin` |

Más detalles en [docs/DEMO.md](docs/DEMO.md).

## Qué puedes hacer

**Alumnado**

- Crear entradas con varias acepciones: definición, más datos, ejemplo de uso, categoría gramatical, género, número,
  forma en otra lengua, temáticas e imagen/audio/vídeo.
- Buscar por texto, por inicial y por temática; ocultar entradas.
- Unirse a un aula con un código y enviar una o varias entradas.
- Ver el estado de cada envío (pendiente, publicado, rechazado con nota) y los comentarios del profesorado.
- Imprimir o exportar el diccionario (PDF desde el navegador, CSV, JSON y OASIS DMLex JSON).

**Profesorado**

- Crear aulas con curso, nivel, materia, vigencia, plazo de envíos, campos obligatorios, máximo de acepciones y pautas.
- Gestionar participantes y roles, y regenerar el código de unión.
- Revisar envíos, publicarlos (uno o varios) o rechazarlos con nota; editar y ocultar lo publicado.
- Comentar el trabajo de cada alumno.

**Administración**

- Usuarios y roles, vigencia de las aulas, listas controladas (categorías, temáticas, niveles…), estadísticas y
  registro de auditoría.

## Desarrollo rápido

Requisitos: Node.js 24 o superior, Bun 1.4.2 (API) y Docker.

```bash
bun ci
bun run dev:demo                         # demo sin backend: http://localhost:5173/lexican/
```

Con API y PostgreSQL:

```bash
docker compose up -d db
cp apps/api/.env.example apps/api/.env      # APP_ENV=local: CAS de pruebas + datos ficticios
bun run db:migrate
bun run db:seed --demo                # vocabularios + cuentas y datos ficticios
bun run dev:api                          # API en http://localhost:3000
bun run dev                              # en otra terminal: web en http://localhost:5173
```

O todo en Docker, como en producción pero con el perfil local: `docker compose --profile app up --build`
(<http://localhost:3000>).

Guía completa: [developers.md](developers.md). Normas para agentes y contribuciones: [AGENTS.md](AGENTS.md).

## Documentación

| Documento | Contenido |
|---|---|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | arquitectura de producción y demo |
| [docs/DATA-MODEL.md](docs/DATA-MODEL.md) | modelo de datos |
| [docs/DEMO.md](docs/DEMO.md) | demo en GitHub Pages |
| [docs/AUTHENTICATION.md](docs/AUTHENTICATION.md) | CAS, CAUCE y sesiones |
| [docs/SECURITY.md](docs/SECURITY.md) | seguridad y cómo informar de vulnerabilidades |
| [docs/PRIVACY.md](docs/PRIVACY.md) | datos personales |
| [docs/ACCESSIBILITY.md](docs/ACCESSIBILITY.md) | accesibilidad |
| [docs/TESTING.md](docs/TESTING.md) | pruebas |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | despliegue en producción |
| [docs/MIGRATION.md](docs/MIGRATION.md) | migración desde el sistema anterior |
| [docs/DMLEX-MAPPING.md](docs/DMLEX-MAPPING.md) | exportación DMLex |
| [docs/MODERNIZATION.md](docs/MODERNIZATION.md) | por qué y cómo se reconstruyó |
| [docs/adr/](docs/adr/) | decisiones de arquitectura |
| [CHANGELOG.md](CHANGELOG.md) | cambios por versión |

## Historia

La versión 1.x (Laravel 8 + Voyager) se conserva sin cambios en la rama `upstream`. La 2.0 es una reconstrucción
completa en TypeScript ([docs/MODERNIZATION.md](docs/MODERNIZATION.md)).

## Licencia

LexiCán es software libre bajo la [GNU Affero General Public License v3.0 o posterior](LICENSE) (AGPL-3.0-or-later).
Detalle y componentes de terceros: [docs/LICENSING.md](docs/LICENSING.md), [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
