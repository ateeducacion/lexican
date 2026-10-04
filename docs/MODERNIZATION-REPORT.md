# Informe de modernización (métricas)

Comparación entre `upstream` (`c80ff652`, código legacy entregado) y la rama modernizada. Las cifras del sistema nuevo
salen de `npm run metrics` (`scripts/metrics.mjs`), que lee git, el sistema de ficheros y los builds. Las del legacy salen
del mismo script y de `analysis/lexican/BASELINE.md`, donde cada número lleva su comando. No hay cifras escritas a mano.

Reproducir:

```bash
git fetch origin upstream
npm ci && npm run build && npm run build:demo
npm run metrics > metrics.json
TEST_DATABASE_URL=postgres://… npm run test:coverage
```

Medido el 2026-10-04, commit `a9352f6`.

## Comparación

| Métrica | Legacy (`upstream`) | Modernizado |
|---|---:|---:|
| Tecnologías principales | PHP 8 / Laravel 8.83 / Voyager 1.5 / phpCAS 1.5 / Blade / jQuery 2.1.3 / Bootstrap 4 / TinyMCE 5 / dompdf / MariaDB | TypeScript 6.0 / React 19 / Vite 8 / Fastify 5 / Drizzle 0.45 / PostgreSQL 18 / PGlite 0.5 |
| ¿Se instala con las herramientas actuales? | No (repositorio `larapack.io` 404; Composer bloquea phpCAS y dompdf por avisos) | Sí (`npm ci`) |
| Ficheros de código escritos a mano | 389 (PHP, Blade, JS, Vue, Sass) | 96 (TS/TSX/CSS) + 11 de tests unitarios/integración + 3 especificaciones E2E |
| Líneas de código | 36 525 (scc, BASELINE) | 14 389 de código fuente (sin tests) |
| Dependencias directas | 18 PHP + 21 npm | 38 npm (todos los workspaces, incluido tooling) |
| Paquetes de producción instalados | 92 PHP + 884 entradas npm en el lock | 132 npm |
| Vulnerabilidades en dependencias de producción | 16 Composer (1 crítica) + 25 npm (1 crítica) + jQuery 2.1.3 servido (4 CVE) | 0 (`npm audit --omit=dev`) |
| Rutas | 48 declaraciones (+ `Auth::routes()` y `Voyager::routes()` ×2); 21 GET que modifican datos (`FUNCTIONAL_INVENTORY.md` §7) | 39 operaciones REST en una tabla única; ningún GET modifica datos |
| Tablas | 45 (`Schema::create`) | 20 (`pgTable`) + 10 enums |
| Controladores / modelos | 33 / 36 | sin controladores: rutas generadas desde `operations.ts`; servicios en `packages/app` |
| Tests | 8 métodos PHPUnit que no pueden ejecutarse | 114 tests Vitest (unitarios, contrato PGlite + PostgreSQL, API, migración) + 9 escenarios E2E (demo en 3 navegadores + móvil; producción web+API+PostgreSQL) |
| Cobertura (líneas, `packages/*`, `apps/api`, `tools/*`) | — | 53 % (sin el test MariaDB del migrador, que requiere `TEST_MARIADB_URL`) |
| CI | No | 3 workflows (CI con PostgreSQL y MariaDB, Pages, release), acciones fijadas por SHA, permisos mínimos |
| Bundle web de producción | Laravel Mix + TinyMCE + jQuery + vendor en `public/` | 557 KB en total; carga inicial 5 peticiones, 429 KB JS+CSS (133 KB gzip); sin WASM |
| Bundle demo (GitHub Pages) | No existe | 22,7 MB en total, de los que 16,8 MB son WASM/datos de PGlite descargados tras pintar el acceso; carga inicial 134 KB gzip |
| Tiempo de build | No medible (no instala) | `npm run build` 0,5 s; `npm run build:demo` 0,7 s (máquina de desarrollo) |
| Servicios obligatorios en producción | Apache + PHP-FPM, MariaDB, NFS, SMTP, CAS, CAUCE | Node.js + PostgreSQL + volumen de medios (+ CAS y CAUCE externos; SMTP no usado) |
| Ficheros binarios/medios versionados | 12,5 MB; `composer.phar`; 8 `.DS_Store` | 2 ilustraciones CC0 propias (11 KB) + fixtures de migración |
| Migrador legacy | — | MariaDB → PostgreSQL, idempotente, con *dry-run*, informe y medios; fixture: 188 ms |
| Accesibilidad automática | — | axe (WCAG 2.0/2.1/2.2 A+AA) sin violaciones en acceso, diccionario, ficha y editor |
| Errores de consola en flujos principales | No medido (el legacy no se ejecuta) | 0: todo E2E falla ante un error de consola o una petición externa |

## Qué empeoró o queda peor medido

- **Primera visita a la demo**: descarga ≈5,5 MB comprimidos de PGlite (solo en la demo, después de pintar el acceso).
- **Carga inicial de producción**: 133 KB gzip, dominada por React, react-router y Zod. Aceptable, pero mejorable separando
  Zod del primer *chunk*.
- **Cobertura**: 53 % de líneas; los servicios de aplicación tienen una batería de contrato extensa, pero hay ramas de
  error de la API y del migrador sin cubrir. No hay umbral bloqueante todavía.
- **WebKit E2E** es lento al reabrir PGlite desde IndexedDB tras recargar (timeout de 120 s en ese proyecto).

## Qué no se migró (deliberadamente)

Voyager y su panel genérico, visor de logs, avisos/mantenimiento/configuración (rutas sin implementación), grabación de
prueba, registro y recuperación de contraseña de Laravel, `/test`, correo de prueba, componentes Vue sin uso, tablas
`avisos*` y `entradas_compartidas`, auditoría `owen-it` histórica (solo se cuenta), miniaturas de vídeo con FFmpeg,
TinyMCE (los textos pasan a texto plano). Detalle: `docs/LEGACY-DATA-MAPPING.md` y `analysis/lexican/FUNCTIONAL_INVENTORY.md`.

## Deuda técnica restante

| Deuda | Impacto | Siguiente paso |
|---|---|---|
| Credenciales del legacy expuestas en el histórico público | Alto (operación) | Rotar APP_KEY, tokens CAUCE y contraseñas sembradas (`docs/SECURITY.md`) |
| CAS y CAUCE reales sin probar | Alto en el corte | Prueba en preproducción con la lista de `docs/AUTHENTICATION.md` |
| Ensayo de migración con datos reales anonimizados | Alto en el corte | `docs/MIGRATION.md` |
| Licencia del proyecto sin decidir | Medio | Decisión del titular (`docs/LICENSING.md`) |
| Invitación de docentes por correo | Bajo | `MailAdapter` (hoy se comparte el código de unión) |
| Importar entradas de otro aula al crearla | Bajo | Caso de uso nuevo con procedencia |
| Pautas maestras (`app_settings.master_guidelines`) migradas pero no mostradas | Bajo | Mostrarlas como valor por defecto al crear un aula |
| Grabación de audio/vídeo desde el navegador | Bajo | Hoy se sube un archivo; evaluar `MediaRecorder` |
| Borrado/anonimización de usuarios y purga de medios huérfanos | Medio (privacidad) | Casos de uso de administración (`docs/PRIVACY.md`) |
| Comprobación de dimensiones de imagen (bombas de descompresión) | Bajo | `image-size` en `packages/app/src/media.ts` |
| Límites de peticiones en memoria (una instancia) | Bajo | Almacén compartido solo si se escala horizontalmente |
| Imagen Docker de 484 MB (medida antes de sacar PGlite de las dependencias del servidor) | Bajo | Copiar solo las dependencias externas del bundle |
| Eliminación del código Laravel de `main` (fase 11) | — | Tras el corte en producción |
