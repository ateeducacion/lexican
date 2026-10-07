# Informe de modernización (métricas)

Comparación entre `upstream` (`c80ff652`, código legacy entregado) y el sistema nuevo. Las cifras del sistema nuevo
salen de `bun run metrics` (`scripts/metrics.mjs`), que lee git, el sistema de ficheros y los builds; omite enlaces
simbólicos. Los recuentos se conservan en [metrics/2026-10-05.json](metrics/2026-10-05.json). Las cifras del legacy
salen del mismo script y de `analysis/lexican/BASELINE.md`; pruebas y cobertura, del
[CI de main](https://github.com/ateeducacion/lexican/actions/runs/37268093858).

Reproducir:

```bash
git fetch origin upstream
bun ci && bun run build && bun run build:demo
bun run metrics > metrics.json
TEST_DATABASE_URL=postgres://… TEST_MARIADB_URL=mysql://… bun run test:coverage
```

Medido el 2026-10-05 sobre el código de `6343fee`; builds locales en Apple Silicon con Bun 1.4.2. Los tiempos
son una ejecución local con las dependencias instaladas, no un benchmark de CI. Los tamaños totales incluyen
sourcemaps; la carga inicial cuenta el HTML y los JS/CSS declarados en él, no todas las peticiones del navegador.

## Comparación

| Métrica | Legacy (`upstream`) | Modernizado |
|---|---:|---:|
| Tecnologías principales | PHP 8 / Laravel 8.83 / Voyager 1.5 / phpCAS 1.5 / Blade / jQuery 2.1.3 / Bootstrap 4 / TinyMCE 5 / dompdf / MariaDB | TypeScript 6.0 / React 19 / Vite 8 / Hono 4 / Bun 1.4.2 / Drizzle 1.0.0-rc.5-5935859 / PostgreSQL 18 + Bun.SQL / PGlite 0.5 |
| ¿Se instala con las herramientas actuales? | No (repositorio `larapack.io` 404; Composer bloquea phpCAS y dompdf por avisos) | Sí (`bun ci`) |
| Ficheros de código escritos a mano | 389 (PHP, Blade, JS, Vue, Sass) | 112 (TS/TSX/CSS) + 31 ficheros de tests Vitest + 6 especificaciones E2E |
| Líneas de código | 36 525 (scc, BASELINE) | 20 465 de código fuente (sin tests) |
| Dependencias directas | 18 PHP + 21 npm | 27 del registro npm (todos los workspaces, incluido tooling) |
| Paquetes de producción instalados | 92 PHP + 884 entradas npm en el lock | 46 del registro npm (incluye el migrador; no todos van en la imagen) |
| Vulnerabilidades en dependencias de producción | 16 Composer (1 crítica) + 25 npm (1 crítica) + jQuery 2.1.3 servido (4 CVE) | 0 (`bun audit`, incluidas las dependencias de desarrollo) |
| Rutas | 48 declaraciones (+ `Auth::routes()` y `Voyager::routes()` ×2); 21 GET que modifican datos (`FUNCTIONAL_INVENTORY.md` §7) | 39 operaciones REST en una tabla única; ningún GET modifica datos |
| Tablas | 45 (`Schema::create`) | 19 (`pgTable`) + 10 enums |
| Controladores / modelos | 33 / 36 | sin controladores: rutas generadas desde `operations.ts`; servicios en `packages/app` |
| Tests | 8 métodos PHPUnit que no pueden ejecutarse | 299 tests Vitest; 43 E2E pasan y 9 se omiten por ámbito de proyecto; WebKit desactivado en CI |
| Cobertura (líneas, `packages/*`, `apps/api`, `tools/*`) | — | 98,26 % (2321/2362); PostgreSQL y MariaDB presentes, umbral bloqueante 90 % |
| CI | No | 4 workflows (CI, Pages, release y Docker reutilizable), acciones fijadas por SHA, permisos mínimos; publica la imagen probada sin reconstruir |
| Interoperabilidad lexicográfica | CSV y JSON internos/ad hoc, sin DMLex | Exportación OASIS DMLex 1.0 JSON, validada automáticamente contra el JSON Schema oficial; se mantienen CSV y JSON propio ([DMLEX-MAPPING.md](DMLEX-MAPPING.md)) |
| Bundle web de producción | Laravel Mix + TinyMCE + jQuery + vendor en `public/` | 1 350 093 bytes totales; 6 peticiones iniciales declaradas, 474 022 bytes JS+CSS (154 285 gzip); sin WASM |
| Bundle demo (GitHub Pages) | No existe | 25 170 872 bytes totales; 16 778 719 bytes de WASM/datos de PGlite en el Worker; JS+CSS inicial 155 655 bytes gzip |
| Tiempo de build | No medible (no instala) | `bun run build` 0,42 s; `bun run build:demo` 0,57 s (ejecución local) |
| Servicios obligatorios en producción | Apache + PHP-FPM, MariaDB, NFS, SMTP, CAS, CAUCE | Bun + PostgreSQL + volumen de medios (+ CAS y CAUCE externos; SMTP no usado) |
| Ficheros binarios/medios versionados | 12,5 MB; `composer.phar`; 8 `.DS_Store` | 2 ilustraciones CC0 propias (11 KB) + fixtures de migración |
| Migrador legacy | — | MariaDB → PostgreSQL con Bun.SQL, idempotente, con dry-run, informe y medios; probado en CI |
| Accesibilidad automática | — | axe (WCAG 2.0/2.1/2.2 A+AA) sin violaciones en acceso, diccionario, ficha y editor |
| Errores de consola en flujos principales | No medido (el legacy no se ejecuta) | 0: todo E2E falla ante un error de consola o una petición externa |

## Qué empeoró o queda peor medido

- **Primera visita a la demo**: descarga ≈5,5 MB comprimidos de PGlite (solo en la demo, después de pintar el acceso).
- **Carga inicial de producción**: unos 151 KiB de JS+CSS gzip, dominada por React, react-router y Zod. Aceptable, pero mejorable separando
  Zod del primer *chunk*.
- **Cobertura**: 98,26 % de líneas en el gate de CI, con umbral del 90 % en las cuatro métricas. No cubre React,
  CAS/CAUCE institucionales ni un Android real ([TESTING.md](TESTING.md)).
- **WebKit E2E** sigue desactivado en CI por su duración; se ejecuta manualmente en local. Android real está pendiente.

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
| Invitación de docentes por correo | Bajo | `MailAdapter` (hoy se comparte el código de unión) |
| Importar entradas de otro aula al crearla | Bajo | Caso de uso nuevo con procedencia |
| Pautas maestras (`app_settings.master_guidelines`) migradas pero no mostradas | Bajo | Mostrarlas como valor por defecto al crear un aula |
| Grabación de audio/vídeo desde el navegador | Bajo | Hoy se sube un archivo; evaluar `MediaRecorder` |
| Borrado/anonimización de usuarios y purga de medios huérfanos | Medio (privacidad) | Casos de uso de administración (`docs/PRIVACY.md`) |
| Comprobación de dimensiones de imagen (bombas de descompresión) | Bajo | `image-size` en `packages/app/src/media.ts` |
| Límites de peticiones en memoria (una instancia) | Bajo | Almacén compartido solo si se escala horizontalmente |
