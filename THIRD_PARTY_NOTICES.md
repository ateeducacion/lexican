# Avisos de terceros

Qué software de terceros incluye o distribuye LexiCán, con su licencia. LexiCán se distribuye bajo
AGPL-3.0-or-later ([LICENSE](LICENSE), [docs/LICENSING.md](docs/LICENSING.md)).

**Compatibilidad.** Todas las licencias de esta lista permiten distribuir los componentes junto con LexiCán bajo
AGPL-3.0-or-later: MIT, ISC, BSD-3-Clause, BlueOak-1.0.0 y Apache-2.0 (dependencias npm), OFL-1.1 (tipografías),
CC0-1.0 (ilustraciones de la demo) y el aviso de OASIS para el esquema DMLex. Los textos completos de cada licencia están en el `LICENSE` de cada paquete
(`node_modules/<paquete>/`) y la lista se adjunta como SBOM SPDX (`bun run sbom`) a cada *release*.

## Dependencias npm de producción

Generado con `bun pm licenses --prod --json` el 2026-10-05: 46 paquetes
(MIT: 40, Apache-2.0: 3, OFL-1.1: 2, BSD-3-Clause: 1). Las tipografías también se describen en su propia sección.
`bun run licenses` vuelve a calcularlo y comprueba la lista de licencias permitidas.

Dónde acaba cada grupo:

- **Bundle web** (`apps/web/dist`): `react`, `react-dom`, `react-router` (y sus dependencias), `zod`.
- **Bundle de la demo** (`apps/web/dist-demo`): lo anterior más `@electric-sql/pglite` y `drizzle-orm`.
  PGlite declara Apache-2.0 en su `package.json`; su README indica doble licencia Apache-2.0 / PostgreSQL License y
  que los cambios al código de PostgreSQL son PostgreSQL License.
- **Imagen de la API**: `hono`, `drizzle-orm` (Bun.SQL), `proxy-addr`, `fast-xml-parser`, `file-type`, `zod`
  y sus dependencias.
- **Solo herramienta de migración** (no va en la imagen ni en los bundles): `mysql2` y sus dependencias
  (`aws-ssl-profiles`, `generate-function`, `iconv-lite`, `long`, `lru.min`, `named-placeholders`, `sql-escaper`…).
- `@types/node` aparece por dependencias de tipos; no se ejecuta.

| Paquete | Versión | Licencia |
|---|---|---|
| `@borewit/text-codec` | 0.2.2 | MIT |
| `@electric-sql/pglite` | 0.5.8 | Apache-2.0 |
| `@nodable/entities` | 3.1.0 | MIT |
| `@remix-run/route-pattern` | 0.22.1 | MIT |
| `@tokenizer/inflate` | 0.4.1 | MIT |
| `@tokenizer/token` | 0.3.0 | MIT |
| `@types/node` | 26.6.4 | MIT |
| `anynum` | 1.0.1 | MIT |
| `aws-ssl-profiles` | 1.1.2 | MIT |
| `bun-types` | 1.4.2 | MIT |
| `cookie-es` | 3.1.1 | MIT |
| `debug` | 4.4.3 | MIT |
| `drizzle-orm` | 1.0.0-rc.5-5935859 | Apache-2.0 |
| `fast-xml-builder` | 1.3.1 | MIT |
| `fast-xml-parser` | 5.11.2 | MIT |
| `file-type` | 22.1.1 | MIT |
| `forwarded` | 0.2.0 | MIT |
| `generate-function` | 2.3.1 | MIT |
| `hono` | 4.13.13 | MIT |
| `iconv-lite` | 0.7.3 | MIT |
| `ieee754` | 1.2.1 | BSD-3-Clause |
| `ipaddr.js` | 1.9.1 | MIT |
| `is-property` | 1.0.2 | MIT |
| `is-unsafe` | 2.0.2 | MIT |
| `long` | 5.3.2 | Apache-2.0 |
| `lru.min` | 1.1.5 | MIT |
| `ms` | 2.1.3 | MIT |
| `mysql2` | 3.24.5 | MIT |
| `named-placeholders` | 1.1.6 | MIT |
| `path-expression-matcher` | 1.6.2 | MIT |
| `proxy-addr` | 2.0.8 | MIT |
| `react-dom` | 19.3.0 | MIT |
| `react-router` | 8.4.0 | MIT |
| `react` | 19.3.0 | MIT |
| `safer-buffer` | 2.1.2 | MIT |
| `scheduler` | 0.28.0 | MIT |
| `sql-escaper` | 1.5.2 | MIT |
| `strnum` | 2.4.2 | MIT |
| `strtok3` | 10.3.5 | MIT |
| `token-types` | 6.1.2 | MIT |
| `uint8array-extras` | 1.6.0 | MIT |
| `undici-types` | 8.9.0 | MIT |
| `xml-naming` | 0.3.0 | MIT |
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

`apps/web/public/ate-logo.png` (tomado de `ateeducacion/elpx-optimizer`, como en Tonga) © Gobierno de Canarias (Área de Tecnología Educativa); se usa solo en la tarjeta de vista previa de enlaces (`og-image.jpg`, generada con `bun run og-image`).
