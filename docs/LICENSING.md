# Licencias

Estado a 2026-10-04. No es asesoramiento jurídico. Avisos de terceros: [THIRD_PARTY_NOTICES.md](../THIRD_PARTY_NOTICES.md).

## Decisión

El 2026-10-04 la representación del titular de los derechos —Consejería de Educación del Gobierno de Canarias / Área
de Tecnología Educativa (ATE)— decidió que LexiCán se distribuye bajo la **GNU Affero General Public License v3.0 o
posterior** (`AGPL-3.0-or-later`):

- **Código nuevo y repositorio**: AGPL-3.0-or-later. Texto completo en [`LICENSE`](../LICENSE); todos los
  `package.json` declaran `"license": "AGPL-3.0-or-later"`.
- **Código legacy** (rama `upstream`, `c80ff652`): se conserva como instantánea histórica y, por la misma decisión, se
  distribuye en este repositorio bajo los mismos términos. No se reutiliza en el código nuevo.

La AGPL obliga a ofrecer el código fuente a quien use LexiCán a través de la red. La interfaz enlaza el repositorio
público (`SOURCE_URL` en `apps/web/src/env.ts`).

## Estado por componente

| Elemento | Licencia | Evidencia |
|---|---|---|
| Código nuevo (`apps/`, `packages/`, `tools/`, `scripts/`, `e2e/`) y documentación | AGPL-3.0-or-later | `LICENSE`, `package.json` |
| Código legacy (rama `upstream`) | AGPL-3.0-or-later (misma decisión) | `composer.json` dice `"license": "MIT"`, pero es el valor heredado del esqueleto `laravel/laravel`, no una declaración del titular |
| Ilustraciones de la demo (`apps/web/public/demo/`) | CC0-1.0 | `apps/web/public/demo/README.txt` |
| Tipografías Lexend y Literata (autoalojadas) | OFL-1.1 | paquetes `@fontsource-variable/lexend`, `@fontsource/literata` |
| Esquema DMLex 1.0 (`packages/core/src/dmlex-1.0.schema.json`) | © OASIS Open 2025, política IPR de OASIS | Las copias están permitidas «sin restricción» si incluyen el aviso de copyright y la sección *Notices*; ese aviso se reproduce en `THIRD_PARTY_NOTICES.md` ([DMLEX-MAPPING.md](DMLEX-MAPPING.md)) |
| Dependencias de producción (registro npm) | MIT, ISC, BSD-3-Clause, BlueOak-1.0.0, Apache-2.0, OFL-1.1 | `bun run licenses` (`bun pm licenses --prod`; lista permitida en `scripts/licenses.mjs`; CI falla si aparece otra). SBOM SPDX con `bun run sbom` |
| Activos vendor del legacy (`public/`: jQuery, Bootstrap, TinyMCE, etc.) | las de cada proyecto (ver avisos) | cabeceras de cada fichero; no los usa el código nuevo y se retiran en la fase de limpieza |

Todas estas licencias son compatibles con distribuir el conjunto bajo AGPL-3.0-or-later.

## Código de terceros reutilizado

Ninguno, salvo el esquema DMLex citado arriba. El código nuevo no copia fragmentos de otros proyectos; Lexonomy y las
librerías CAS de npm se estudiaron y se reimplementó la idea ([ADR 0005](adr/0005-autenticacion.md)).

Reglas para lo que venga:

- Solo código o contenidos con licencia compatible con AGPL-3.0-or-later. Un repositorio público sin licencia no
  autoriza a copiar.
- Registrar en `THIRD_PARTY_NOTICES.md` proyecto, URL, versión/commit, titular, licencia y modificaciones.
- Una dependencia de producción con una licencia nueva exige ampliar la lista de `scripts/licenses.mjs` en la misma PR,
  justificando la compatibilidad.
