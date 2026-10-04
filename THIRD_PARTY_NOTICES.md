# Avisos de terceros

Qué software de terceros incluye o distribuye LexiCán, con su licencia. LexiCán se distribuye bajo
AGPL-3.0-or-later ([LICENSE](LICENSE), [docs/LICENSING.md](docs/LICENSING.md)).

**Compatibilidad.** Todas las licencias de esta lista permiten distribuir los componentes junto con LexiCán bajo
AGPL-3.0-or-later: MIT, ISC, BSD-3-Clause, BlueOak-1.0.0 y Apache-2.0 (dependencias npm), OFL-1.1 (tipografías),
CC0-1.0 (ilustraciones de la demo) y el aviso de OASIS para el esquema DMLex. Los textos completos de cada licencia están en el `LICENSE` de cada paquete
(`node_modules/<paquete>/`) y la lista se adjunta como SBOM SPDX (`npm run sbom`) a cada *release*.

## Dependencias npm de producción

Generado con `npm query ':root .prod'` (licencia del `package.json` de cada paquete) el 2026-10-04: 134 paquetes
(MIT 113, ISC 6, BSD-3-Clause 5, BlueOak-1.0.0 5, Apache-2.0 3, OFL-1.1 2). La tabla lista los 132 de código; las dos
tipografías están en su propia sección. `npm run licenses` lo vuelve a calcular y falla si
aparece una licencia fuera de la lista permitida.

Dónde acaba cada grupo:

- **Bundle web** (`apps/web/dist`): `react`, `react-dom`, `react-router` (y sus dependencias), `zod`.
- **Bundle de la demo** (`apps/web/dist-demo`): lo anterior más `@electric-sql/pglite` y `drizzle-orm`.
  PGlite declara Apache-2.0 en su `package.json`; su README indica doble licencia Apache-2.0 / PostgreSQL License y
  que los cambios al código de PostgreSQL son PostgreSQL License.
- **Imagen de la API**: `fastify` y plugins `@fastify/*`, `pg`, `drizzle-orm`, `fast-xml-parser`, `file-type`, `zod`
  y sus dependencias.
- **Solo herramienta de migración** (no va en la imagen ni en los bundles): `mysql2` y sus dependencias
  (`aws-ssl-profiles`, `generate-function`, `iconv-lite`, `long`, `lru.min`, `named-placeholders`, `sql-escaper`…).
- `@types/node` y `@types/pg` aparecen por dependencias de tipos; no se ejecutan.

| Paquete | Versión | Licencia |
|---|---|---|
| `@borewit/text-codec` | 0.2.2 | MIT |
| `@electric-sql/pglite` | 0.5.8 | Apache-2.0 |
| `@fastify/accept-negotiator` | 2.1.0 | MIT |
| `@fastify/ajv-compiler` | 4.0.6 | MIT |
| `@fastify/busboy` | 3.2.2 | MIT |
| `@fastify/cookie` | 11.1.2 | MIT |
| `@fastify/deepmerge` | 3.2.1 | MIT |
| `@fastify/error` | 4.2.0 | MIT |
| `@fastify/fast-json-stringify-compiler` | 5.1.0 | MIT |
| `@fastify/formbody` | 9.0.0 | MIT |
| `@fastify/forwarded` | 3.0.2 | MIT |
| `@fastify/helmet` | 13.1.1 | MIT |
| `@fastify/merge-json-schemas` | 0.2.1 | MIT |
| `@fastify/multipart` | 10.1.2 | MIT |
| `@fastify/proxy-addr` | 5.1.1 | MIT |
| `@fastify/rate-limit` | 11.2.0 | MIT |
| `@fastify/send` | 4.1.1 | MIT |
| `@fastify/static` | 10.1.5 | MIT |
| `@lukeed/ms` | 2.0.2 | MIT |
| `@nodable/entities` | 3.1.0 | MIT |
| `@pinojs/redact` | 0.4.0 | MIT |
| `@remix-run/route-pattern` | 0.22.1 | MIT |
| `@tokenizer/inflate` | 0.4.1 | MIT |
| `@tokenizer/token` | 0.3.0 | MIT |
| `@types/node` | 24.19.1 | MIT |
| `@types/pg` | 8.23.1 | MIT |
| `abstract-logging` | 2.0.1 | MIT |
| `ajv` | 8.20.0 | MIT |
| `ajv-formats` | 3.0.1 | MIT |
| `anynum` | 1.0.1 | MIT |
| `atomic-sleep` | 1.0.0 | MIT |
| `avvio` | 9.3.0 | MIT |
| `aws-ssl-profiles` | 1.1.2 | MIT |
| `balanced-match` | 4.0.4 | MIT |
| `brace-expansion` | 5.0.12 | MIT |
| `content-disposition` | 3.0.0 | MIT |
| `cookie` | 2.0.1, 1.1.1 | MIT |
| `cookie-es` | 3.1.1 | MIT |
| `debug` | 4.4.3 | MIT |
| `depd` | 2.0.0 | MIT |
| `dequal` | 2.0.3 | MIT |
| `drizzle-orm` | 0.45.3 | Apache-2.0 |
| `escape-html` | 1.0.3 | MIT |
| `fast-decode-uri-component` | 1.0.1 | MIT |
| `fast-deep-equal` | 3.1.3 | MIT |
| `fast-json-stringify` | 7.0.1 | MIT |
| `fast-querystring` | 1.1.2 | MIT |
| `fast-uri` | 3.1.8, 4.2.1 | BSD-3-Clause |
| `fast-xml-builder` | 1.3.1 | MIT |
| `fast-xml-parser` | 5.11.2 | MIT |
| `fastify` | 5.12.5 | MIT |
| `fastify-plugin` | 6.0.0 | MIT |
| `fastq` | 1.20.3 | ISC |
| `file-type` | 22.1.1 | MIT |
| `find-my-way` | 9.9.0 | MIT |
| `generate-function` | 2.3.1 | MIT |
| `glob` | 13.0.6 | BlueOak-1.0.0 |
| `helmet` | 8.3.0 | MIT |
| `http-errors` | 2.0.1 | MIT |
| `iconv-lite` | 0.7.3 | MIT |
| `ieee754` | 1.2.1 | BSD-3-Clause |
| `inherits` | 2.0.4 | ISC |
| `ip-address` | 10.7.3 | MIT |
| `ipaddr.js` | 2.5.0 | MIT |
| `is-property` | 1.0.2 | MIT |
| `is-unsafe` | 2.0.2 | MIT |
| `json-schema-ref-resolver` | 3.0.0 | MIT |
| `json-schema-traverse` | 1.0.0 | MIT |
| `light-my-request` | 6.6.0 | BSD-3-Clause |
| `long` | 5.3.2 | Apache-2.0 |
| `lru-cache` | 11.5.3 | BlueOak-1.0.0 |
| `lru.min` | 1.1.5 | MIT |
| `mime` | 3.0.0 | MIT |
| `minimatch` | 10.2.6 | BlueOak-1.0.0 |
| `minipass` | 7.1.3 | BlueOak-1.0.0 |
| `ms` | 2.1.3 | MIT |
| `mysql2` | 3.24.5 | MIT |
| `named-placeholders` | 1.1.6 | MIT |
| `on-exit-leak-free` | 2.1.2 | MIT |
| `path-expression-matcher` | 1.6.2 | MIT |
| `path-scurry` | 2.0.2 | BlueOak-1.0.0 |
| `pg` | 8.23.1 | MIT |
| `pg-cloudflare` | 1.4.1 | MIT |
| `pg-connection-string` | 2.14.1 | MIT |
| `pg-int8` | 1.0.1 | ISC |
| `pg-pool` | 3.14.0 | MIT |
| `pg-protocol` | 1.16.1 | MIT |
| `pg-types` | 2.2.0 | MIT |
| `pgpass` | 1.0.5 | MIT |
| `pino` | 10.4.0 | MIT |
| `pino-abstract-transport` | 3.0.0 | MIT |
| `pino-std-serializers` | 7.1.0 | MIT |
| `postgres-array` | 2.0.0 | MIT |
| `postgres-bytea` | 1.0.1 | MIT |
| `postgres-date` | 1.0.7 | MIT |
| `postgres-interval` | 1.2.0 | MIT |
| `process-warning` | 4.0.1, 5.1.0 | MIT |
| `quick-format-unescaped` | 4.0.4 | MIT |
| `react` | 19.3.0 | MIT |
| `react-dom` | 19.3.0 | MIT |
| `react-router` | 8.4.0 | MIT |
| `real-require` | 1.0.0 | MIT |
| `require-from-string` | 2.0.2 | MIT |
| `ret` | 0.5.0 | MIT |
| `reusify` | 1.1.0 | MIT |
| `rfdc` | 1.4.1 | MIT |
| `safe-regex2` | 5.1.1 | MIT |
| `safe-stable-stringify` | 2.5.0 | MIT |
| `safer-buffer` | 2.1.2 | MIT |
| `scheduler` | 0.28.0 | MIT |
| `secure-json-parse` | 4.1.0 | BSD-3-Clause |
| `semver` | 7.8.5 | ISC |
| `set-cookie-parser` | 2.7.2 | MIT |
| `setprototypeof` | 1.2.0 | ISC |
| `sonic-boom` | 4.2.1 | MIT |
| `split2` | 4.2.0 | ISC |
| `sql-escaper` | 1.5.2 | MIT |
| `statuses` | 2.0.2 | MIT |
| `strnum` | 2.4.2 | MIT |
| `strtok3` | 10.3.5 | MIT |
| `thread-stream` | 4.2.0 | MIT |
| `toad-cache` | 3.7.4 | MIT |
| `toidentifier` | 1.0.1 | MIT |
| `token-types` | 6.1.2 | MIT |
| `uint8array-extras` | 1.6.0 | MIT |
| `undici-types` | 7.24.6 | MIT |
| `xml-naming` | 0.3.0 | MIT |
| `xtend` | 4.0.2 | MIT |
| `zod` | 4.6.5 | MIT |

## Contenidos incluidos en el repositorio

| Componente | Ubicación | Licencia | Origen |
|---|---|---|---|
| Esquema JSON de OASIS DMLex 1.0 (variante sin módulo *Crosslingual*) | `packages/core/src/dmlex-1.0.schema.json` | © OASIS Open 2025, política IPR de OASIS (aviso abajo) | <https://docs.oasis-open.org/lexidma/dmlex/v1.0/os/> |
| Ilustraciones de la demo (guagua, gofio) | `apps/web/public/demo/` | CC0-1.0 | creadas para LexiCán (`README.txt`) |

### Aviso de OASIS (esquema DMLex)

> Copyright © OASIS Open 2025. All Rights Reserved. Distributed under the terms of the OASIS IPR Policy.
>
> This document and translations of it may be copied and furnished to others, and derivative works that comment on or
> otherwise explain it or assist in its implementation may be prepared, copied, published, and distributed, in whole
> or in part, without restriction of any kind, provided that the above copyright notice and this section are included
> on all such copies and derivative works. However, this document itself may not be modified in any way, including by
> removing the copyright notice or references to OASIS, except as needed for the purpose of developing any document or
> deliverable produced by an OASIS Technical Committee (in which case the rules applicable to copyrights, as set forth
> in the OASIS IPR Policy, must be followed) or as required to translate it into languages other than English.
>
> The limited permissions granted above are perpetual and will not be revoked by OASIS or its successors or assigns.
>
> This document and the information contained herein is provided on an "AS IS" basis and OASIS DISCLAIMS ALL
> WARRANTIES, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO ANY WARRANTY THAT THE USE OF THE INFORMATION HEREIN WILL
> NOT INFRINGE ANY OWNERSHIP RIGHTS OR ANY IMPLIED WARRANTIES OF MERCHANTABILITY OR FITNESS FOR A PARTICULAR PURPOSE.

Fuente: sección *Notices* de <https://docs.oasis-open.org/lexidma/dmlex/v1.0/os/dmlex-v1.0-os.html>.

## Activos vendor del legacy

Forman parte del código legacy (rama `upstream`). No los usa el código nuevo ni van en sus bundles; se retiran del
árbol en la fase de limpieza. Versiones y licencias tomadas de la cabecera de cada fichero.

| Componente | Versión | Ubicación | Licencia |
|---|---|---|---|
| jQuery | 2.1.3 | `public/js/jquery.min.js` | MIT (jquery.org/license) |
| Bootstrap | 4.6.2 | `public/js/bootstrap.min.js`, `public/css/bootstrap.min.css` | MIT |
| bootstrap-table | 1.22.1 | `public/js/bootstrap-table*.min.js`, `public/css/bootstrap-table.min.css` | MIT |
| Swiper | 6.2.0 | `public/js/swiper-bundle.min.js`, `public/css/swiper-bundle.min.css` | MIT |
| Dropify | 0.2.1 | `public/js/dropify.min.js`, `public/css/dropify.min.css`, `public/fonts/dropify.*` | MIT (proyecto de origen; el fichero no la cita). Fuente de iconos de fontello.com |
| TinyMCE (pieles) | 5.x | `public/js/skins/` | LGPL o licencia comercial (cabecera de Tiny Technologies) |
| Font Awesome | 5.6.3 | `public/css/fontawesome.css`, `resources/sass/fontawesome.5.6.3.all.css` | Font Awesome Free (código MIT, iconos CC BY 4.0, fuentes OFL-1.1), según el proyecto de origen |
| Carlito | — | `public/fonts/carlito*`, `public/fonts/carlito/` | SIL OFL-1.1, según el proyecto de origen (el repositorio no incluye el texto) |
| Bundle Laravel Mix | — | `public/js/app.js` (+ `app.js.LICENSE.txt`), `public/js/acepciones.js` | varias, listadas en `app.js.LICENSE.txt` |
| `composer.phar` | — | raíz | MIT (Composer) |

## Tipografías autoalojadas (interfaz 2026-10)

| Paquete | Licencia | Uso |
|---|---|---|
| `@fontsource-variable/lexend` (Lexend, Bonnie Shaver-Troup, Thomas Jockin y colaboradores) | OFL-1.1 | Texto de la interfaz |
| `@fontsource/literata` (Literata, TypeTogether) | OFL-1.1 | Palabras y ejemplos del diccionario |

Se sirven desde el propio despliegue (sin CDN de terceros). La OFL permite incluirlas y redistribuirlas con la aplicación.

## Logotipo del Área de Tecnología Educativa

`apps/web/public/ate-logo.png` (tomado de `ateeducacion/elpx-optimizer`, como en Tonga) © Gobierno de Canarias (Área de Tecnología Educativa); se usa solo en la tarjeta de vista previa de enlaces (`og-image.jpg`, generada con `npm run og-image`).
