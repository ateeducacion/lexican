# Licencias

Estado a 2026-10-04. No es asesoramiento jurídico. Avisos de terceros: [THIRD_PARTY_NOTICES.md](../THIRD_PARTY_NOTICES.md).

## Decisión

**No se añade ninguna licencia de proyecto** (ni `LICENSE`, ni campo `license` en `package.json`, ni REUSE/SPDX) hasta
que el titular de los derechos —Consejería de Educación del Gobierno de Canarias / Área de Tecnología Educativa (ATE)—
decida cuál aplicar (§92 de `1er-prompt.md`). Que Aritmates y Tonga sean AGPL-3.0-or-later no implica que LexiCán lo
sea. Mientras tanto, el código es visible públicamente pero **no se concede ninguna licencia** sobre él.

## Estado por componente

| Elemento | Licencia | Evidencia | Estado |
|---|---|---|---|
| Código legacy (Laravel/PHP, Blade, JS propio) en `upstream` y aún en `main` | **ninguna declarada** | No hay `LICENSE` ni cabeceras de licencia en `app/`, `resources/`, `routes/`, `database/`. `composer.json` dice `"license": "MIT"`, pero es el valor heredado del esqueleto `laravel/laravel` (`"name": "laravel/laravel"`, `"description": "The Laravel Framework."`), no una declaración del titular. No hay documentación contractual en el repositorio | Duda: titularidad y licencia por confirmar (desarrollo por encargo) |
| Código nuevo TypeScript (`apps/`, `packages/`, `tools/`, `scripts/`, `e2e/`) | ninguna todavía | Escrito desde cero; no copia código del legacy ni de terceros | Duda: misma decisión pendiente que el legacy |
| Documentación (`docs/`, `analysis/`, `README.md`, etc.) | ninguna todavía | — | Duda |
| Ilustraciones de la demo (`apps/web/public/demo/*.svg`, `*.png`) | CC0-1.0 | `apps/web/public/demo/README.txt` | Decidido |
| `apps/web/public/favicon.svg` | sin declarar | creado para LexiCán | Duda: incluir en la decisión del proyecto |
| Esquema DMLex 1.0 (`packages/core/src/dmlex-1.0.schema.json`) | sin cabecera de licencia en el fichero | Copia de `dmlex_no-crosslingual.schema.json` publicado por OASIS ([DMLEX-MAPPING.md](DMLEX-MAPPING.md)). Su `$id` es `http://docs.oasis-open.org/lexidma/ns/dmlex-1.0` | Duda: confirmar que el aviso de copyright y la política IPR de OASIS Open permiten redistribuirlo; si no, descargarlo en build/test en lugar de versionarlo |
| Dependencias npm de producción | MIT, ISC, BSD-3-Clause, BlueOak-1.0.0, Apache-2.0 | `npm run licenses` (lista permitida en `scripts/licenses.mjs`; CI falla si aparece otra) | Decidido |
| Activos vendor del legacy en `public/` (jQuery, Bootstrap, TinyMCE, etc.) | varias (ver avisos) | cabeceras de cada fichero | Se eliminan de `main` en la fase de limpieza; no los usa el código nuevo |
| Imágenes, avatares y documentos del legacy (`public/imagenes/`, `archivos/avatares/`, `documentos/`) | sin declarar | sin metadatos de autoría | Duda: no se reutilizan en el código nuevo |

## Código de terceros reutilizado

Ninguno, salvo el esquema DMLex citado arriba. El código nuevo no copia fragmentos de otros proyectos; Lexonomy y las librerías CAS de npm se estudiaron y
se reimplementó la idea ([ADR 0005](adr/0005-autenticacion.md)). Si en el futuro se reutiliza algo, hay que registrar
proyecto, URL, versión/commit, titular, licencia y modificaciones en `THIRD_PARTY_NOTICES.md`. Un repositorio público
sin licencia no autoriza a copiar.

## Dudas abiertas

1. ¿Quién es el titular del código legacy (Consejería o empresa contratista) y qué dice el contrato sobre derechos de
   explotación?
2. ¿Qué licencia se aplica al proyecto (código nuevo y documentación)? Propuesta no aplicada: la misma que los
   proyectos hermanos (AGPL-3.0-or-later para código), si el titular lo aprueba.
3. ¿Se puede redistribuir el esquema JSON de DMLex en el repositorio?
4. Autoría y licencia de las imágenes del legacy (`public/imagenes/`, `public/imagenes/creditos/`, `archivos/avatares/`). Los avatares nuevos son iniciales sobre colores, sin imágenes (`apps/web/src/pages/personal/Avatar.tsx`).

Cuando se decida: añadir `LICENSE`, campo `license` en `package.json`, `REUSE.toml` con las excepciones por ruta de la
tabla de arriba y `reuse lint` en CI.
