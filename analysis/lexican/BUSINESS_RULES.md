# Business Rules — lexican

Generated 2026-10-04 by `/code-modernization:modernize-extract-rules lexican` (Method B: 9 parallel business-rules-extractor shards by directory; citations checked for file existence and line range). Source: `legacy/lexican` → `/Users/ernesto/Dropbox/Trabajo/ate/lexican`. Paths are relative to the repo root. Personal data and credentials are masked.

## Counts by domain and priority

| Domain | P0 | P1 | P2 | Total |
|---|---|---|---|---|
| Admin-Voyager | 3 | 1 | 7 | 11 |
| Audit | 3 | 1 | 1 | 5 |
| Auth-CAS-CAUCE | 19 | 3 | 0 | 22 |
| Classroom-Dictionary | 8 | 7 | 6 | 21 |
| Comments-Notifications | 9 | 8 | 6 | 23 |
| Exports | 5 | 4 | 4 | 13 |
| Fields-Config | 3 | 7 | 0 | 10 |
| Join-Codes | 5 | 1 | 1 | 7 |
| Master-Data | 1 | 7 | 4 | 12 |
| Personal-Dictionary | 6 | 8 | 5 | 19 |
| Publication-Moderation | 13 | 3 | 1 | 17 |
| Roles-Authorization | 39 | 2 | 1 | 42 |
| SchoolYear-Vigencia | 17 | 9 | 2 | 28 |
| Senses-Acepciones | 5 | 12 | 2 | 19 |
| Submissions-Envios | 21 | 9 | 5 | 35 |
| Uploads-Media | 6 | 13 | 1 | 20 |
| **Total** | 163 | 95 | 46 | 304 |

## Summary

| ID | Name | Category | Domain | Priority | Source | Confidence |
|---|---|---|---|---|---|---|
| RULE-001 | Persisted attributes and forced defaults | Calculation | Classroom-Dictionary | P0 | `app/Helpers/dicAula_helper.php:67-88` | High |
| RULE-002 | Join-code generation | Calculation | Join-Codes | P0 | `resources/js/dic_aula.js:968-997` | High |
| RULE-003 | Role choice for single vs. multiple centres | Calculation | Roles-Authorization | P0 | `app/Helpers/global_helper.php:824-862` | High |
| RULE-004 | CAUCE role code to app role | Calculation | Roles-Authorization | P0 | `app/Helpers/global_helper.php:1116-1129` | High |
| RULE-005 | School-year start year from INICIO_CURSO | Calculation | SchoolYear-Vigencia | P0 | `app/Helpers/global_helper.php:410-438` | High |
| RULE-006 | Is a classroom dictionary still in force (vigente) | Calculation | SchoolYear-Vigencia | P0 | `app/Helpers/dicAula_helper.php:2022-2037` | High |
| RULE-007 | Reactivate a dictionary for the current school year | Calculation | SchoolYear-Vigencia | P0 | `app/Http/Controllers/Voyager/VoyagerCompassController.php:531-552` | High |
| RULE-008 | Last-submission lookup: published > sent > any, current school year only | Calculation | Submissions-Envios | P0 | `app/Helpers/dpEnvios_helper.php:161-247` | High |
| RULE-009 | Submission window state (inactive / active / planned) | Calculation | Submissions-Envios | P0 | `app/Models/DicAula.php:79-98` | Medium |
| RULE-010 | Classroom PDF shows every optional field and renumbers senses | Calculation | Exports | P1 | `resources/views/layouts/partials/pdf/daMainPDFView.blade.php:70-128` | Medium |
| RULE-011 | Join-code generation | Calculation | Join-Codes | P1 | `app/Helpers/dicAula_helper.php:1613-1622` | High |
| RULE-012 | Publish-all preview | Calculation | Publication-Moderation | P1 | `resources/views/layouts/partials/components/modal-publicar-listado.blade.php:10-61` | High |
| RULE-013 | Statistics school year starts in September | Calculation | SchoolYear-Vigencia | P1 | `app/Http/Controllers/Voyager/VoyagerCompassController.php:163-171` | High |
| RULE-014 | School-year bucketing in statistics is inconsistent | Calculation | SchoolYear-Vigencia | P1 | `app/Http/Controllers/Voyager/VoyagerCompassController.php:163-240` | High |
| RULE-015 | School-year label "YYYY/YYYY+1" | Calculation | SchoolYear-Vigencia | P1 | `app/Helpers/global_helper.php:364-382` | High |
| RULE-016 | Admin dictionary classification | Calculation | SchoolYear-Vigencia | P1 | `app/Http/Controllers/Voyager/VoyagerCompassController.php:441-506` | High |
| RULE-017 | Last valid school year = start year + vigencia − 1 | Calculation | SchoolYear-Vigencia | P1 | `documentos/vistas.sql:6-20` | High |
| RULE-018 | Server assigns sense order (renumber 1..n, then add at the end) | Calculation | Senses-Acepciones | P1 | `app/Helpers/dpEntradas_helper.php:310-348` | High |
| RULE-019 | Moving a sense up/down swaps it with its neighbour | Calculation | Senses-Acepciones | P1 | `app/Helpers/dpAcepciones_helper.php:264-309` | High |
| RULE-020 | Later senses move up one place after a delete | Calculation | Senses-Acepciones | P1 | `app/Helpers/dpAcepciones_helper.php:167-204` | High |
| RULE-021 | Submissions per entry (count and latest) | Calculation | Submissions-Envios | P1 | `app/Helpers/dicAula_helper.php:1489-1522` | High |
| RULE-022 | Image thumbnail: longest side 250 px, JPEG/PNG/GIF only | Calculation | Uploads-Media | P1 | `app/Helpers/create-thumbnail.php:39-150` | High |
| RULE-023 | Stats count users by global role, once per person | Calculation | Admin-Voyager | P2 | `documentos/vistas.sql:56-97` | High |
| RULE-024 | Admin statistics on dictionaries and participants | Calculation | Admin-Voyager | P2 | `app/Http/Controllers/Voyager/VoyagerCompassController.php:95-147` | High |
| RULE-025 | Published-entries statistic | Calculation | Admin-Voyager | P2 | `app/Http/Controllers/Voyager/VoyagerCompassController.php:174-186` | Medium |
| RULE-026 | Minutes until a timestamp (cookie consent lasts 3 months) | Calculation | Admin-Voyager | P2 | `app/Helpers/global_helper.php:1334-1349` | High |
| RULE-027 | Theme-tag filter list = tags used in published entries | Calculation | Classroom-Dictionary | P2 | `app/Helpers/dicAula_helper.php:1133-1148` | High |
| RULE-028 | A classroom dictionary is flagged active (selectable) when it has visible comments | Calculation | Comments-Notifications | P2 | `app/Helpers/ComentariosHelper.php:22-43` | High |
| RULE-029 | Hidden-entry count shown in the PDF | Calculation | Exports | P2 | `app/Helpers/dpEntradas_helper.php:697-704` | High |
| RULE-030 | Join-code display split | Calculation | Join-Codes | P2 | `app/Helpers/global_helper.php:1174-1178` | High |
| RULE-031 | Stress-test data only outside production; NIF check letter | Calculation | Master-Data | P2 | `database/seeders/StressTestUserDataSeeder.php:29-43` | High |
| RULE-032 | Person display-name format | Calculation | Master-Data | P2 | `app/Models/Persona.php:58-67` | High |
| RULE-033 | Search: substring, tags (any match), alphabetical, 10 per page | Calculation | Personal-Dictionary | P2 | `app/Helpers/dpEntradas_helper.php:530-569` | High |
| RULE-034 | Statistics infer school year from creation month | Calculation | SchoolYear-Vigencia | P2 | `documentos/vistas.sql:25-27` | High |
| RULE-035 | Grammatical attribute abbreviation | Calculation | Senses-Acepciones | P2 | `app/Models/DiccionarioPersonalAcepcion.php:86-124` | High |
| RULE-036 | Date-input conversion to start of day | Calculation | Submissions-Envios | P2 | `app/Helpers/global_helper.php:1254-1265` | High |
| RULE-037 | Persona matching order | Validation | Auth-CAS-CAUCE | P0 | `app/Helpers/global_helper.php:779-792` | High |
| RULE-038 | Session userData requires persona and centres, but this is not enforced | Validation | Auth-CAS-CAUCE | P0 | `app/Helpers/global_helper.php:466-497` | High |
| RULE-039 | Access is denied only when CAUCE reports an error | Validation | Auth-CAS-CAUCE | P0 | `app/Helpers/global_helper.php:770-778` | High |
| RULE-040 | Single centre handling | Validation | Auth-CAS-CAUCE | P0 | `app/Helpers/global_helper.php:1019-1070` | High |
| RULE-041 | Students can't select a classroom dictionary that is hidden from students | Validation | Classroom-Dictionary | P0 | `resources/views/diccionario/home.blade.php:60-102` | High |
| RULE-042 | Maximum senses per entry is offered but never stored or enforced (suspected defect) | Validation | Classroom-Dictionary | P0 | `resources/views/diccionario/crearDicAula.blade.php:244-253` | High |
| RULE-043 | Maximum senses per entry defaults to 10 but is stored as a boolean | Validation | Classroom-Dictionary | P0 | `app/Helpers/dicAula_helper.php:79` | High |
| RULE-044 | Has-submissions check (blocks dictionary deletion) | Validation | Classroom-Dictionary | P0 | `app/Helpers/dicAula_helper.php:1546-1563` | High |
| RULE-045 | General comments to a student (coordinator only) | Validation | Comments-Notifications | P0 | `resources/views/comentarios/comentariosDiccionarioEnviar.blade.php:45-83` | High |
| RULE-046 | Entry comments can be edited or deleted by any teacher, and are rendered as raw HTML | Validation | Comments-Notifications | P0 | `resources/views/layouts/partials/components/botones/entrada/comentarios.blade.php:1-24` | High |
| RULE-047 | Per-dictionary required-field validation of each sense | Validation | Fields-Config | P0 | `app/Helpers/dpEnvios_helper.php:534-612` | High |
| RULE-048 | Join-code validation and server-side regeneration | Validation | Join-Codes | P0 | `app/Http/Controllers/DicAulaController.php:143-158` | High |
| RULE-049 | Join-code validation (4–6 characters, unique) | Validation | Join-Codes | P0 | `app/Helpers/dicAula_helper.php:1593-1600` | High |
| RULE-050 | Join-code lookup only finds active dictionaries | Validation | Join-Codes | P0 | `app/Helpers/dicAula_helper.php:404-411` | High |
| RULE-051 | Entry text is unique per dictionary, ignoring case | Validation | Personal-Dictionary | P0 | `app/Helpers/dpEntradas_helper.php:113-139` | High |
| RULE-052 | Bulk publish pre-check and execution | Validation | Publication-Moderation | P0 | `app/Helpers/dicAula_helper.php:586-668` | Medium |
| RULE-053 | Edit-entry and publish buttons only for coordinators of that dictionary | Validation | Publication-Moderation | P0 | `resources/views/layouts/partials/consulta/entradaAula.blade.php:11-25` | High |
| RULE-054 | Renaming a published entry | Validation | Publication-Moderation | P0 | `app/Http/Controllers/DicAulaController.php:1425-1494` | High |
| RULE-055 | Editing a submitted sense: editable fields and validation limits | Validation | Publication-Moderation | P0 | `app/Http/Controllers/EnvioAcepcionController.php:94-126` | High |
| RULE-056 | A published submission can't be deleted (server check broken) | Validation | Publication-Moderation | P0 | `resources/views/layouts/partials/components/botones/entrada/eliminarAula.blade.php:1-22` | High |
| RULE-057 | Duplicate-title block on publication | Validation | Publication-Moderation | P0 | `app/Helpers/dicAula_helper.php:672-680` | High |
| RULE-058 | Guards on disabling a participant | Validation | Roles-Authorization | P0 | `app/Helpers/dicAula_helper.php:1808-1864` | High |
| RULE-059 | No authorization on editing submitted senses or deleting their media | Validation | Roles-Authorization | P0 | `app/Http/Controllers/EnvioAcepcionController.php:90-136` | High |
| RULE-060 | Saving edits is rejected for inactive dictionaries, but saving is not authorized | Validation | Roles-Authorization | P0 | `app/Http/Controllers/DicAulaController.php:297-336` | High |
| RULE-061 | Only teachers see the "Create classroom dictionary" action | Validation | Roles-Authorization | P0 | `resources/views/diccionario/aula.blade.php:67-100` | High |
| RULE-062 | Who counts as a coordinator | Validation | Roles-Authorization | P0 | `app/Helpers/dicAula_helper.php:1964-1969` | Medium |
| RULE-063 | Teacher / admin role helpers | Validation | Roles-Authorization | P0 | `app/Helpers/global_helper.php:1183-1197` | High |
| RULE-064 | Validity (vigencia) selection and minimum on edit | Validation | SchoolYear-Vigencia | P0 | `resources/views/diccionario/crearDicAula.blade.php:135-181` | High |
| RULE-065 | Vigencia list filter (off by one compared with B03) | Validation | SchoolYear-Vigencia | P0 | `app/Helpers/dicAula_helper.php:1973-1986` | Medium |
| RULE-066 | Atemporal dictionaries in the connected list | Validation | SchoolYear-Vigencia | P0 | `app/Helpers/dicAula_helper.php:246-252` | Low |
| RULE-067 | The school year of a new classroom dictionary is supplied by the browser | Validation | SchoolYear-Vigencia | P0 | `resources/views/diccionario/crearDicAula.blade.php:598-608` | High |
| RULE-068 | Reactivation window: start year + 9 | Validation | SchoolYear-Vigencia | P0 | `app/Models/DicAula.php:184-190` | Low |
| RULE-069 | A dictionary's school year comes from the submitted form | Validation | SchoolYear-Vigencia | P0 | `app/Http/Controllers/DicAulaController.php:131-138` | High |
| RULE-070 | Hide/edit sense buttons in the classroom dictionary | Validation | Senses-Acepciones | P0 | `resources/views/layouts/partials/consulta/acepcionAula.blade.php:9-27` | High |
| RULE-071 | Single-entry submission requires the classroom dictionary to accept submissions | Validation | Submissions-Envios | P0 | `app/Helpers/dpEnvios_helper.php:280-285` | High |
| RULE-072 | Target classroom dictionary membership is not verified server-side | Validation | Submissions-Envios | P0 | `app/Helpers/dpEnvios_helper.php:269-293` | High |
| RULE-073 | Only the owner can submit personal entries | Validation | Submissions-Envios | P0 | `app/Http/Controllers/EnviosController.php:213-223` | High |
| RULE-074 | Bulk / selected-entries submission bypasses the acceptance-window check | Validation | Submissions-Envios | P0 | `app/Helpers/dpEnvios_helper.php:66-94` | High |
| RULE-075 | Submission-window state filter (inactive / planned / active) | Validation | Submissions-Envios | P0 | `app/Helpers/dicAula_helper.php:278-324` | High |
| RULE-076 | Pre-check requires at least one visible sense and reports field errors per sense | Validation | Submissions-Envios | P0 | `app/Helpers/dpEnvios_helper.php:616-645` | High |
| RULE-077 | Single-entry send only when the dictionary accepts submissions | Validation | Submissions-Envios | P0 | `resources/views/layouts/partials/components/modal-enviarEntradaUnDiccionario.blade.php:8-53` | High |
| RULE-078 | Any sense missing a required field aborts the send | Validation | Submissions-Envios | P0 | `app/Helpers/dpEnvios_helper.php:375-385` | High |
| RULE-079 | Allowed extensions and size limit are checked in the browser only | Validation | Uploads-Media | P0 | `config/ctes.php:113-117` | High |
| RULE-080 | Media upload type and size limits are client-only | Validation | Uploads-Media | P0 | `resources/views/layouts/partials/dpAcepcion/dpAcepcionFormulario.blade.php:140-248` | High |
| RULE-081 | TinyMCE upload has no validation | Validation | Uploads-Media | P0 | `app/Http/Controllers/HomeController.php:55-59` | High |
| RULE-082 | Deletion eligibility (advisory only) | Validation | Classroom-Dictionary | P1 | `app/Http/Controllers/DicAulaController.php:1610-1626` | High |
| RULE-083 | Dictionaries a person was disabled in / inactive dictionaries | Validation | Classroom-Dictionary | P1 | `app/Helpers/dicAula_helper.php:357-371` | High |
| RULE-084 | "Is joined" check ignores participant status | Validation | Classroom-Dictionary | P1 | `app/Helpers/dicAula_helper.php:515-522` | High |
| RULE-085 | Comment visibility mode and cut-off date | Validation | Comments-Notifications | P1 | `resources/views/diccionario/crearDicAula.blade.php:438-470` | High |
| RULE-086 | Generic confirm modal renders caller-supplied text without escaping | Validation | Comments-Notifications | P1 | `app/Http/Controllers/ModalAjaxController.php:35-54` | High |
| RULE-087 | Pre-submission requirements check | Validation | Fields-Config | P1 | `app/Http/Controllers/DicAulaController.php:1296-1308` | High |
| RULE-088 | Every level is allowed with every subject and every stage | Validation | Master-Data | P1 | `database/seeders/MasterTablesDataSeeder.php:345-369` | High |
| RULE-089 | Rename dictionary and change avatar | Validation | Personal-Dictionary | P1 | `app/Http/Controllers/DiccionarioPersonalController.php:1121-1136` | High |
| RULE-090 | Renaming an entry cannot clash with another entry | Validation | Personal-Dictionary | P1 | `app/Http/Controllers/DiccionarioPersonalController.php:401-415` | High |
| RULE-091 | Entry text is normalised (trim, collapse spaces, keep case) | Validation | Personal-Dictionary | P1 | `app/Helpers/global_helper.php:1229-1240` | High |
| RULE-092 | Entry text is required, at most 150 characters | Validation | Personal-Dictionary | P1 | `app/Http/Controllers/DiccionarioPersonalController.php:177-188` | High |
| RULE-093 | Field length limits | Validation | Personal-Dictionary | P1 | `database/migrations/2020_04_19_113507_create_initial_structure.php:140-162` | High |
| RULE-094 | Entry-title uniqueness lookup (case-insensitive, accent-sensitive) | Validation | Publication-Moderation | P1 | `app/Helpers/dicAula_helper.php:1637-1648` | Medium |
| RULE-095 | Vigencia bounds are enforced in the UI only | Validation | SchoolYear-Vigencia | P1 | `app/Http/Controllers/DicAulaController.php:263-266` | High |
| RULE-096 | Sense field validation | Validation | Senses-Acepciones | P1 | `app/Http/Controllers/DiccionarioPersonalController.php:491-499` | High |
| RULE-097 | Sense text limits | Validation | Senses-Acepciones | P1 | `resources/views/layouts/partials/dpAcepcion/acepcionFormFieldComun.blade.php:80-101` | High |
| RULE-098 | The dictionary picker's grey/green rule disagrees with the server (suspected defect) | Validation | Submissions-Envios | P1 | `resources/views/layouts/partials/components/listaDicAula-customRadio.blade.php:19-55` | Medium |
| RULE-099 | Submission search filters (limit 200) | Validation | Submissions-Envios | P1 | `app/Helpers/dicAula_helper.php:1679-1760` | High |
| RULE-100 | A hidden personal entry can't be sent | Validation | Submissions-Envios | P1 | `resources/views/layouts/partials/components/botones/entrada/enviar.blade.php:1-27` | High |
| RULE-101 | Media types and accepted extensions | Validation | Uploads-Media | P1 | `config/ctes.php:82-117` | High |
| RULE-102 | Allowed upload types per media kind | Validation | Uploads-Media | P1 | `app/Helpers/global_helper.php:514-542` | High |
| RULE-103 | The recorded-media size check never fires (suspected defect) | Validation | Uploads-Media | P1 | `resources/views/layouts/partials/dpAcepcion/dpAcepcionFormulario.blade.php:1027-1034` | High |
| RULE-104 | Upload size limit | Validation | Uploads-Media | P1 | `config/ctes.php:317-322` | High |
| RULE-105 | Search by initial, text and theme tag | Validation | Classroom-Dictionary | P2 | `app/Helpers/dicAula_helper.php:1271-1290` | High |
| RULE-106 | Classroom search input limits | Validation | Classroom-Dictionary | P2 | `app/Http/Controllers/DicAulaController.php:1059-1100` | High |
| RULE-107 | Comment dialog served for any submitted entry with no authorization | Validation | Comments-Notifications | P2 | `app/Http/Controllers/EnviosController.php:525-544` | High |
| RULE-108 | "Only tagged" export requires at least one theme | Validation | Exports | P2 | `app/Http/Controllers/PDFController.php:46-52` | High |
| RULE-109 | A search needs a word or at least one tag | Validation | Personal-Dictionary | P2 | `app/Http/Controllers/DiccionarioPersonalController.php:290-306` | Medium |
| RULE-110 | Maximum 10 theme tags in search | Validation | Personal-Dictionary | P2 | `resources/js/searchAutocompletion.js:136-153` | High |
| RULE-111 | Entry headword maximum length | Validation | Personal-Dictionary | P2 | `resources/views/layouts/partials/dpEntrada/dpEntradaEdit.blade.php:24` | High |
| RULE-112 | "Send entries" on an empty personal dictionary | Validation | Submissions-Envios | P2 | `resources/views/layouts/partials/components/actionbuttonPersonal.blade.php:41-63` | High |
| RULE-113 | CAS callback flow | Lifecycle | Auth-CAS-CAUCE | P0 | `app/Http/Controllers/Auth/CasController.php:28-41` | High |
| RULE-114 | Multiple centres are created on demand and linked | Lifecycle | Auth-CAS-CAUCE | P0 | `app/Helpers/global_helper.php:970-1010` | High |
| RULE-115 | Centre membership replaced on every login | Lifecycle | Auth-CAS-CAUCE | P0 | `app/Helpers/global_helper.php:1085-1111` | High |
| RULE-116 | Persona identity fields are overwritten on every login | Lifecycle | Auth-CAS-CAUCE | P0 | `app/Helpers/global_helper.php:794-810` | High |
| RULE-117 | User–persona link | Lifecycle | Auth-CAS-CAUCE | P0 | `app/Helpers/global_helper.php:891-895` | High |
| RULE-118 | Deleting a classroom dictionary soft-deletes its related data | Lifecycle | Classroom-Dictionary | P0 | `app/Models/DicAula.php:376-448` | High |
| RULE-119 | Pre-checks before deleting a classroom dictionary | Lifecycle | Classroom-Dictionary | P0 | `resources/js/dic_aula.js:434-470` | High |
| RULE-120 | Creating or editing a general comment | Lifecycle | Comments-Notifications | P0 | `app/Http/Controllers/ComentariosController.php:287-351` | High |
| RULE-121 | A student sees only general comments aimed at their latest personal dictionary | Lifecycle | Comments-Notifications | P0 | `app/Helpers/ComentariosHelper.php:202-242` | High |
| RULE-122 | Joining a classroom dictionary by code | Lifecycle | Join-Codes | P0 | `app/Http/Controllers/DicAulaController.php:433-473` | High |
| RULE-123 | Deselecting an unsent entry hides it in the personal dictionary | Lifecycle | Personal-Dictionary | P0 | `resources/views/diccionario/dpDiccionario/dpDiccionarioEnviarFormulario.blade.php:446-487` | High |
| RULE-124 | One personal dictionary per person, created on first use | Lifecycle | Personal-Dictionary | P0 | `app/Helpers/dpDiccionarios_helper.php:43-104` | High |
| RULE-125 | Saving the first sense creates the entry | Lifecycle | Personal-Dictionary | P0 | `app/Http/Controllers/DiccionarioPersonalController.php:515-530` | High |
| RULE-126 | Entry and sense visibility states | Lifecycle | Personal-Dictionary | P0 | `config/ctes.php:267-271` | High |
| RULE-127 | Soft-deleting a personal entry cascades to its senses | Lifecycle | Personal-Dictionary | P0 | `app/Models/DiccionarioPersonalEntrada.php:66-81` | High |
| RULE-128 | Unpublishing and hiding an entry | Lifecycle | Publication-Moderation | P0 | `app/Helpers/dicAula_helper.php:853-905` | Medium |
| RULE-129 | Publish transition | Lifecycle | Publication-Moderation | P0 | `app/Helpers/dicAula_helper.php:697-748` | High |
| RULE-130 | Publishing a single entry | Lifecycle | Publication-Moderation | P0 | `app/Helpers/dicAula_helper.php:761-839` | High |
| RULE-131 | Publish / unpublish toggle | Lifecycle | Publication-Moderation | P0 | `resources/views/layouts/partials/components/botones/entrada/publicar.blade.php:1-54` | High |
| RULE-132 | Hide entry from a classroom dictionary | Lifecycle | Publication-Moderation | P0 | `app/Helpers/dicAula_helper.php:1425-1455` | Medium |
| RULE-133 | Participant access and editing-permission toggles | Lifecycle | Roles-Authorization | P0 | `resources/views/diccionario/participantesAjax.blade.php:13-74` | High |
| RULE-134 | Joining a dictionary: role assignment and idempotency | Lifecycle | Roles-Authorization | P0 | `app/Helpers/dicAula_helper.php:426-452` | High |
| RULE-135 | Grant / revoke edit rights | Lifecycle | Roles-Authorization | P0 | `app/Helpers/dicAula_helper.php:1899-1960` | Medium |
| RULE-136 | Promote/demote a participant's dictionary role | Lifecycle | Roles-Authorization | P0 | `app/Helpers/dicAula_helper.php:1868-1960` | High |
| RULE-137 | App role set only on first provisioning | Lifecycle | Roles-Authorization | P0 | `app/Helpers/global_helper.php:824-875` | High |
| RULE-138 | Admin bulk deactivation of expired dictionaries | Lifecycle | SchoolYear-Vigencia | P0 | `app/Http/Controllers/Voyager/VoyagerCompassController.php:284-332` | High |
| RULE-139 | Atemporal (permanent) dictionary flag | Lifecycle | SchoolYear-Vigencia | P0 | `app/Models/DicAula.php:176-211` | High |
| RULE-140 | Per-dictionary validity check needs no login | Lifecycle | SchoolYear-Vigencia | P0 | `routes/web.php:298-299` | High |
| RULE-141 | Auto-deactivate dictionaries that are no longer in force | Lifecycle | SchoolYear-Vigencia | P0 | `app/Helpers/dicAula_helper.php:2047-2057` | High |
| RULE-142 | Making a dictionary timeless also activates it | Lifecycle | SchoolYear-Vigencia | P0 | `app/Models/DicAula.php:176-211` | High |
| RULE-143 | Mark a dictionary as timeless (atemporal) or remove the mark | Lifecycle | SchoolYear-Vigencia | P0 | `app/Http/Controllers/Voyager/VoyagerCompassController.php:334-370` | High |
| RULE-144 | Hidden classroom senses are still displayed to everyone (suspected defect) | Lifecycle | Senses-Acepciones | P0 | `resources/views/layouts/partials/consulta/entradaAula.blade.php:31-35` | Medium |
| RULE-145 | Deleting a sense (the last one deletes the entry) | Lifecycle | Senses-Acepciones | P0 | `app/Http/Controllers/DiccionarioPersonalController.php:935-984` | High |
| RULE-146 | Deleting a sense permanently deletes its media | Lifecycle | Senses-Acepciones | P0 | `app/Models/DiccionarioPersonalAcepcion.php:53-64` | High |
| RULE-147 | Deleting an entry depends on its submission state this school year | Lifecycle | Submissions-Envios | P0 | `app/Helpers/dpEntradas_helper.php:44-98` | Medium |
| RULE-148 | Delete an unpublished submission (guard broken) | Lifecycle | Submissions-Envios | P0 | `app/Helpers/dicAula_helper.php:919-962` | High |
| RULE-149 | Each send creates a dp_envios header stamped with the school year and status "sent" | Lifecycle | Submissions-Envios | P0 | `app/Helpers/dpEnvios_helper.php:287-293` | High |
| RULE-150 | Submission and submitted-entry states | Lifecycle | Submissions-Envios | P0 | `config/ctes.php:282-287` | High |
| RULE-151 | Logout | Lifecycle | Auth-CAS-CAUCE | P1 | `routes/web.php:402-407` | High |
| RULE-152 | Importing entries from another dictionary | Lifecycle | Classroom-Dictionary | P1 | `app/Helpers/dicAula_helper.php:129-143` | High |
| RULE-153 | Teacher-invite email and recipient record | Lifecycle | Comments-Notifications | P1 | `app/Http/Controllers/DicAulaController.php:349-420` (outside the shard, but the only writer of `dic_aula_destinatario_avisos`) | High |
| RULE-154 | Comment on a classroom entry | Lifecycle | Comments-Notifications | P1 | `app/Helpers/dicAula_helper.php:1338-1374` | High |
| RULE-155 | Comment read-state lifecycle | Lifecycle | Comments-Notifications | P1 | `config/ctes.php:349-353` | High |
| RULE-156 | The bulk-send selection rewrites personal entry visibility | Lifecycle | Personal-Dictionary | P1 | `app/Http/Controllers/EnviosController.php:104-123` | High |
| RULE-157 | Hide/show toggles in the personal dictionary | Lifecycle | Personal-Dictionary | P1 | `resources/views/layouts/partials/components/botones/entrada/ocultar.blade.php:1-24` | High |
| RULE-158 | Show/hide an entry | Lifecycle | Personal-Dictionary | P1 | `app/Http/Controllers/DiccionarioPersonalController.php:446-464` | High |
| RULE-159 | Marking a dictionary atemporal reactivates it | Lifecycle | SchoolYear-Vigencia | P1 | `app/Models/DicAula.php:192-211` | High |
| RULE-160 | Saving a sense always makes it visible | Lifecycle | Senses-Acepciones | P1 | `app/Http/Controllers/DiccionarioPersonalController.php:660-675` | High |
| RULE-161 | Show/hide a sense; no visible senses hides the entry | Lifecycle | Senses-Acepciones | P1 | `app/Http/Controllers/DiccionarioPersonalController.php:1393-1420` | High |
| RULE-162 | Hide / show a sense, never the last one | Lifecycle | Senses-Acepciones | P1 | `app/Helpers/dicAula_helper.php:1386-1413` | High |
| RULE-163 | Deleting the last sense deletes the entry | Lifecycle | Senses-Acepciones | P1 | `resources/views/layouts/partials/consulta/dpEntradaAcepcion.blade.php:42-64` | High |
| RULE-164 | Removing a submitted sense hard-deletes it | Lifecycle | Submissions-Envios | P1 | `app/Models/EnvioAcepcion.php:128-144` | Medium |
| RULE-165 | Hide/show entries in bulk from the send screen (one-way) | Lifecycle | Submissions-Envios | P1 | `app/Helpers/dpEntradas_helper.php:663-688` | High |
| RULE-166 | One medium per type per sense; a new upload replaces the old one | Lifecycle | Uploads-Media | P1 | `app/Helpers/dpMedios_helper.php:46-67` | High |
| RULE-167 | Admin routes require `admin.user` | Policy | Admin-Voyager | P0 | `routes/web.php:282-367` | Medium |
| RULE-168 | Unrouted backdoor that creates or resets an admin account | Policy | Admin-Voyager | P0 | `app/Http/Controllers/HomeController.php:70-98` | High |
| RULE-169 | Admin data listing skips the per-type permission check | Policy | Admin-Voyager | P0 | `app/Http/Controllers/Voyager/VoyagerBaseController.php:13-30` | High |
| RULE-170 | Audit trail configuration | Policy | Audit | P0 | `config/audit.php:5-136` | High |
| RULE-171 | Soft-delete scope: which tables are recoverable | Policy | Audit | P0 | `app/Models/DicAula.php:20-23` | High |
| RULE-172 | Audit trail on domain models | Policy | Audit | P0 | `config/audit.php:59-64` | High |
| RULE-173 | Provisioned user account fields (shared password hash) | Policy | Auth-CAS-CAUCE | P0 | `app/Helpers/global_helper.php:863-875` | High |
| RULE-174 | CAUCE web service call | Policy | Auth-CAS-CAUCE | P0 | `app/Helpers/global_helper.php:739-760` | High |
| RULE-175 | Password login stays available in every environment | Policy | Auth-CAS-CAUCE | P0 | `routes/web.php:280-280` | High |
| RULE-176 | Unauthenticated users go to CAS, or to the Voyager form in local | Policy | Auth-CAS-CAUCE | P0 | `app/Http/Middleware/Authenticate.php:26-35` | High |
| RULE-177 | Any logged-in user can run the CAUCE validator test page | Policy | Auth-CAS-CAUCE | P0 | `app/Http/Controllers/HomeController.php:101-205` | High |
| RULE-178 | Placeholder centre for users with no centre | Policy | Auth-CAS-CAUCE | P0 | `app/Helpers/global_helper.php:945-955` | High |
| RULE-179 | CAS server certificate validation | Policy | Auth-CAS-CAUCE | P0 | `app/Cas/CasManager.php:182-191` | High |
| RULE-180 | Only the CAS login ID is used | Policy | Auth-CAS-CAUCE | P0 | `app/Cas/CasManager.php:233-256` | High |
| RULE-181 | CAS masquerade bypasses authentication | Policy | Auth-CAS-CAUCE | P0 | `app/Cas/CasManager.php:202-205` | High |
| RULE-182 | `/test` route provisions test accounts | Policy | Auth-CAS-CAUCE | P0 | `routes/web.php:265-266` | High |
| RULE-183 | Defaults when creating or updating a classroom dictionary | Policy | Classroom-Dictionary | P0 | `app/Helpers/dicAula_helper.php:54-88` | High |
| RULE-184 | Comments are only aggregated from "connected" classroom dictionaries | Policy | Comments-Notifications | P0 | `app/Http/Controllers/ComentariosController.php:43-81` | High |
| RULE-185 | Classroom comment-visibility modes | Policy | Comments-Notifications | P0 | `app/Models/DicAula.php:216-260` | High |
| RULE-186 | Entry-comment modal is owner-only and lists visible comments from every classroom | Policy | Comments-Notifications | P0 | `app/Http/Controllers/ComentariosController.php:95-131` | High |
| RULE-187 | A student sees only entry comments on their own submissions | Policy | Comments-Notifications | P0 | `app/Helpers/ComentariosHelper.php:327-363` | High |
| RULE-188 | Editing/deleting comments on classroom entries has no authorization | Policy | Comments-Notifications | P0 | `app/Http/Controllers/DicAulaController.php:1173-1243` | High |
| RULE-189 | CSV statistics export open to any logged-in user | Policy | Exports | P0 | `app/Http/Controllers/CSVController.php:21-98` | High |
| RULE-190 | Classroom PDF content: published and visible entries only | Policy | Exports | P0 | `app/Helpers/pdf_helper.php:141-196` | High |
| RULE-191 | Personal-dictionary PDF has no ownership check | Policy | Exports | P0 | `app/Http/Controllers/PDFController.php:36-75` | High |
| RULE-192 | CSV export calls any controller method named in the request | Policy | Exports | P0 | `app/Http/Controllers/CSVController.php:101-139` | High |
| RULE-193 | Classroom PDF only for participants | Policy | Exports | P0 | `app/Http/Controllers/PDFController.php:85-87` | High |
| RULE-194 | Field catalogue ids (`mst_campos_entrada`) | Policy | Fields-Config | P0 | `database/seeders/MasterTablesDataSeeder.php:49-71` | Medium |
| RULE-195 | Per-dictionary visible/required entry fields | Policy | Fields-Config | P0 | `app/Helpers/dicAula_helper.php:89-98` | High |
| RULE-196 | Master tables are wiped and ids reset on every seeding run | Policy | Master-Data | P0 | `database/seeders/MasterTablesDataSeeder.php:26-44` | High |
| RULE-197 | Publish/unpublish/delete submission need teacher + coordinator; the entry–dictionary link is not checked | Policy | Publication-Moderation | P0 | `app/Http/Controllers/DicAulaController.php:555-608` | High |
| RULE-198 | Which entries are publicly visible in a classroom dictionary | Policy | Publication-Moderation | P0 | `app/Helpers/dicAula_helper.php:1033-1056` | High |
| RULE-199 | Only a docente participant can open the general-comment screen | Policy | Roles-Authorization | P0 | `app/Http/Controllers/ComentariosController.php:215-271` | High |
| RULE-200 | Only the dictionary owner may act on an entry | Policy | Roles-Authorization | P0 | `app/Policies/DiccionarioPersonalPolicy.php:24-28` | High |
| RULE-201 | Seeded accounts with shared hardcoded passwords | Policy | Roles-Authorization | P0 | `database/seeders/VoyagerCustomization.php:1932-1947` | High |
| RULE-202 | Open self-registration probably grants the admin-panel role | Policy | Roles-Authorization | P0 | `app/Http/Controllers/Auth/RegisterController.php:60-82` | Medium |
| RULE-203 | Application roles docente/alumno; role ids depend on the environment | Policy | Roles-Authorization | P0 | `config/ctes.php:68-72` | Medium |
| RULE-204 | Admin gets every permission; "user" (Oficina técnica) is read-only | Policy | Roles-Authorization | P0 | `database/seeders/VoyagerCustomization.php:1899-1929` | High |
| RULE-205 | Choosing the user's active classroom dictionary | Policy | Roles-Authorization | P0 | `app/Helpers/dicAula_helper.php:1175-1231` | High |
| RULE-206 | Numeric role IDs depend on environment | Policy | Roles-Authorization | P0 | `app/Providers/AppServiceProvider.php:33-36` | High |
| RULE-207 | Editing a sense does not check it belongs to the entry | Policy | Roles-Authorization | P0 | `app/Http/Controllers/DiccionarioPersonalController.php:650-675` | High |
| RULE-208 | Global "teacher" role check | Policy | Roles-Authorization | P0 | `app/Policies/DicAulaPolicy.php:43-52` | High |
| RULE-209 | Classroom home differs for teachers and students | Policy | Roles-Authorization | P0 | `resources/views/diccionario/aula.blade.php:29-65` | High |
| RULE-210 | Only the comment's author can delete a general comment | Policy | Roles-Authorization | P0 | `app/Http/Controllers/ComentariosController.php:366-390` | High |
| RULE-211 | Saving a general comment has no authorization check | Policy | Roles-Authorization | P0 | `app/Http/Controllers/ComentariosController.php:314-341` | High |
| RULE-212 | Participant management needs only the global teacher role | Policy | Roles-Authorization | P0 | `app/Http/Controllers/DicAulaController.php:702-738` | High |
| RULE-213 | Only teachers may open the "create classroom dictionary" form | Policy | Roles-Authorization | P0 | `app/Http/Controllers/DicAulaController.php:61-64` | High |
| RULE-214 | One admin vigencia route is reachable without auth | Policy | Roles-Authorization | P0 | `routes/web.php:282-299` | Medium |
| RULE-215 | Reordering senses has no ownership check | Policy | Roles-Authorization | P0 | `app/Http/Controllers/DiccionarioPersonalController.php:724-774` | High |
| RULE-216 | Access to the edit page needs teacher + coordinator + active dictionary | Policy | Roles-Authorization | P0 | `app/Http/Controllers/DicAulaController.php:226-239` | High |
| RULE-217 | Reading published entries by explicit dicId skips the membership check | Policy | Roles-Authorization | P0 | `app/Http/Controllers/DicAulaController.php:1496-1547` | High |
| RULE-218 | Deleting a classroom dictionary has no authorization and no preconditions | Policy | Roles-Authorization | P0 | `app/Http/Controllers/DicAulaController.php:1598-1608` | High |
| RULE-219 | Classroom coordinator = participant whose dictionary role is "docente" | Policy | Roles-Authorization | P0 | `app/Policies/DicAulaPolicy.php:114-126` | High |
| RULE-220 | Any logged-in user can trigger a test email | Policy | Roles-Authorization | P0 | `app/Http/Controllers/MailController.php:62-99` | High |
| RULE-221 | Landing page after login depends on role | Policy | Roles-Authorization | P0 | `routes/web.php:31-38` | High |
| RULE-222 | The submission list for moderation is visible to any teacher | Policy | Roles-Authorization | P0 | `app/Http/Controllers/DicAulaController.php:527-544` | High |
| RULE-223 | Hiding entries/senses is restricted to the dictionary owner | Policy | Roles-Authorization | P0 | `app/Http/Controllers/DicAulaController.php:1258-1283` | High |
| RULE-224 | Participant check | Policy | Roles-Authorization | P0 | `app/Policies/DicAulaPolicy.php:135-141` | High |
| RULE-225 | A user's visible ("connected") classroom dictionaries | Policy | SchoolYear-Vigencia | P0 | `app/Helpers/dicAula_helper.php:225-259` | High |
| RULE-226 | Vigencia defaults to one school year | Policy | SchoolYear-Vigencia | P0 | `app/Models/DicAula.php:62-64` | High |
| RULE-227 | Sense copy keeps only visible senses, with their fields and order | Policy | Senses-Acepciones | P0 | `app/Helpers/dpEnvios_helper.php:387-402` | High |
| RULE-228 | Whole-dictionary send: automatic selection rules | Policy | Submissions-Envios | P0 | `resources/views/diccionario/dpDiccionario/dpDiccionarioEnviarFormulario.blade.php:300-372` | High |
| RULE-229 | No duplicate check; each send makes a new version | Policy | Submissions-Envios | P0 | `app/Helpers/dpEnvios_helper.php:287-300` | Medium |
| RULE-230 | Submission window | Policy | Submissions-Envios | P0 | `app/Helpers/dicAula_helper.php:1470-1485` | High |
| RULE-231 | Student visibility and submission permission with start date | Policy | Submissions-Envios | P0 | `resources/views/diccionario/crearDicAula.blade.php:359-419` | High |
| RULE-232 | What is copied into envios_entradas | Policy | Submissions-Envios | P0 | `app/Helpers/dpEnvios_helper.php:358-366` | High |
| RULE-233 | A send is all-or-nothing per target dictionary | Policy | Submissions-Envios | P0 | `app/Helpers/dpEnvios_helper.php:66-99` | High |
| RULE-234 | Only visible senses are copied into a submission | Policy | Submissions-Envios | P0 | `app/Helpers/dpAcepciones_helper.php:220-228` | High |
| RULE-235 | A media file is kept if its entry was ever submitted (protection is defeated) | Policy | Uploads-Media | P0 | `app/Helpers/dpMedios_helper.php:134-207` | High |
| RULE-236 | Media files are served from public storage without authorization | Policy | Uploads-Media | P0 | `app/Helpers/dpMedios_helper.php:287-302` | Medium |
| RULE-237 | TinyMCE upload has no CSRF protection | Policy | Uploads-Media | P0 | `app/Http/Middleware/VerifyCsrfToken.php:30-32` | High |
| RULE-238 | Log viewer access and deletion | Policy | Admin-Voyager | P1 | `app/Http/Controllers/Voyager/VoyagerCompassController.php:27-69` | High |
| RULE-239 | Teacher edit stamps updated_by_persona_id on the submitted entry | Policy | Audit | P1 | `app/Http/Controllers/EnvioAcepcionController.php:129-136` | High |
| RULE-240 | Session lifetimes | Policy | Auth-CAS-CAUCE | P1 | `app/Cas/CasManager.php:147-173` | High |
| RULE-241 | HTTPS forced outside local | Policy | Auth-CAS-CAUCE | P1 | `app/Providers/AppServiceProvider.php:37-42` | High |
| RULE-242 | Importing entries from another dictionary at creation | Policy | Classroom-Dictionary | P1 | `resources/views/diccionario/crearDicAula.blade.php:187-227` | High |
| RULE-243 | Master (global) guidelines | Policy | Classroom-Dictionary | P1 | `database/seeders/MstPautasSeeder.php:20-22` | High |
| RULE-244 | Specific guidelines (pautas) | Policy | Classroom-Dictionary | P1 | `app/Helpers/dicAula_helper.php:100-108` | High |
| RULE-245 | Student "view comments" on a personal entry | Policy | Comments-Notifications | P1 | `resources/views/layouts/partials/components/botones/entrada/verComentariosEntrada.blade.php:1-30` | High |
| RULE-246 | The notification e-mail field is displayed but never saved (suspected defect) | Policy | Comments-Notifications | P1 | `resources/views/diccionario/crearDicAula.blade.php:349-355` | High |
| RULE-247 | Teacher view lists every general comment of the classroom, ignoring visibility | Policy | Comments-Notifications | P1 | `app/Helpers/ComentariosHelper.php:374-382` | High |
| RULE-248 | Personal-dictionary PDF content and filtering | Policy | Exports | P1 | `app/Helpers/pdf_helper.php:24-125` | High |
| RULE-249 | Classroom PDF theme filter | Policy | Exports | P1 | `app/Helpers/pdf_helper.php:206-277` | High |
| RULE-250 | CSV export file retention (today only) | Policy | Exports | P1 | `app/Http/Controllers/CSVController.php:143-156` | High |
| RULE-251 | Per-classroom visibility of optional text fields | Policy | Fields-Config | P1 | `resources/views/layouts/partials/consulta/acepcionAula-texto.blade.php:11-55` | High |
| RULE-252 | Language/translation visibility is gated by the wrong field id (suspected defect) | Policy | Fields-Config | P1 | `resources/views/layouts/partials/consulta/acepcionAula-texto.blade.php:35-43` | High |
| RULE-253 | Per-classroom media visibility (audio, video, image) | Policy | Fields-Config | P1 | `resources/views/layouts/partials/consulta/acepcionAula-medios.blade.php:2-18` | High |
| RULE-254 | Defaults for field visibility and required flags in classroom configuration | Policy | Fields-Config | P1 | `resources/views/diccionario/crearDicAula.blade.php:268-310` | High |
| RULE-255 | "Ejemplo de uso" is a separate optional field | Policy | Fields-Config | P1 | `database/seeders/NuevoCampoEjemploSeeder.php:18-26` | High |
| RULE-256 | Field display-name renames | Policy | Fields-Config | P1 | `database/seeders/MasterTablesDataSeederUpdate.php:23-27` | High |
| RULE-257 | Fixed value ids for grammatical category, gender and number | Policy | Master-Data | P1 | `database/seeders/MasterTablesDataSeeder.php:76-86` | High |
| RULE-258 | Subjects catalogue; "Interdisciplinar" is the default | Policy | Master-Data | P1 | `database/seeders/MasterTablesDataSeeder.php:234-342` | High |
| RULE-259 | Course levels catalogue | Policy | Master-Data | P1 | `database/seeders/MasterTablesDataSeeder.php:197-229` | High |
| RULE-260 | Generic active/inactive status flag | Policy | Master-Data | P1 | `config/ctes.php:51-54` | High |
| RULE-261 | Education stages; "Multienseñanza" is the default | Policy | Master-Data | P1 | `database/seeders/MasterTablesDataSeeder.php:176-192` | High |
| RULE-262 | Classroom dictionary types | Policy | Master-Data | P1 | `database/seeders/MasterTablesDataSeeder.php:157-171` | High |
| RULE-263 | Teacher topic edit is replace-all from a comma list with a leading comma | Policy | Publication-Moderation | P1 | `app/Helpers/envioAcepcion_helper.php:21-36` | High |
| RULE-264 | Inviting a teacher | Policy | Roles-Authorization | P1 | `resources/views/layouts/partials/components/modal-formulario-invitar-docente.blade.php:3-31` | High |
| RULE-265 | Admins are sent to /admin, not the personal dictionary | Policy | Roles-Authorization | P1 | `app/Http/Controllers/DiccionarioPersonalController.php:1165-1185` | High |
| RULE-266 | Vigencia maximum / minimum | Policy | SchoolYear-Vigencia | P1 | `config/ctes.php:479-484` | Medium |
| RULE-267 | Validity and school-year config defaults | Policy | SchoolYear-Vigencia | P1 | `config/ctes.php:482-484` | High |
| RULE-268 | Which sense fields are saved (tipologia is dropped) | Policy | Senses-Acepciones | P1 | `app/Helpers/dpAcepciones_helper.php:77-104` | High |
| RULE-269 | Saving tags replaces all of a sense's tags | Policy | Senses-Acepciones | P1 | `app/Helpers/dpAcepciones_helper.php:119-134` | High |
| RULE-270 | Topics (temáticas) are copied with each sense | Policy | Senses-Acepciones | P1 | `app/Helpers/dpEnvios_helper.php:459-488` | High |
| RULE-271 | Only classrooms with sending active are offered | Policy | Submissions-Envios | P1 | `app/Helpers/dpEntradas_helper.php:629-629` | Medium |
| RULE-272 | Only visible personal entries are copied; a hidden entry yields an empty submission | Policy | Submissions-Envios | P1 | `app/Helpers/dpEnvios_helper.php:288-301` | High |
| RULE-273 | Bulk send goes only to the first target dictionary | Policy | Submissions-Envios | P1 | `app/Http/Controllers/EnviosController.php:98-140` | High |
| RULE-274 | Media are copied by reference (shared file), regardless of status | Policy | Uploads-Media | P1 | `app/Helpers/dpEnvios_helper.php:418-441` | High |
| RULE-275 | Storage folder and file naming convention | Policy | Uploads-Media | P1 | `app/Helpers/dpMedios_helper.php:105-120` | High |
| RULE-276 | One media item per type on a submitted sense; an upload replaces the old one | Policy | Uploads-Media | P1 | `app/Helpers/envioAcepcion_helper.php:70-114` | High |
| RULE-277 | Media upload accepted only if valid; failures are hidden | Policy | Uploads-Media | P1 | `app/Http/Controllers/DiccionarioPersonalController.php:566-595` | High |
| RULE-278 | Deleting submitted media removes the database row only; the file is kept | Policy | Uploads-Media | P1 | `app/Helpers/envioAcepcion_helper.php:152-160` | High |
| RULE-279 | Voyager media manager accepts every file type | Policy | Uploads-Media | P1 | `config/voyager.php:212-214` | High |
| RULE-280 | Media delete is limited to the owner's own sense | Policy | Uploads-Media | P1 | `app/Http/Controllers/DiccionarioPersonalController.php:1014-1036` | High |
| RULE-281 | Admin data listing is empty until a search | Policy | Admin-Voyager | P2 | `app/Http/Controllers/Voyager/VoyagerBaseController.php:71-164` | High |
| RULE-282 | Default site settings (not applied) | Policy | Admin-Voyager | P2 | `database/seeders/VoyagerCustomization.php:2153-2180` | High |
| RULE-283 | Health check always reports OK | Policy | Admin-Voyager | P2 | `app/Http/Controllers/HealthCheckController.php:32-47` | High |
| RULE-284 | Cookie-consent acceptance lasts 3 months | Policy | Audit | P2 | `app/Http/Controllers/HomeController.php:207-219` | High |
| RULE-285 | Guidelines (pautas) defaults and display | Policy | Classroom-Dictionary | P2 | `resources/views/diccionario/crearDicAula.blade.php:318-336` | High |
| RULE-286 | Management menus are disabled until a classroom dictionary is active | Policy | Classroom-Dictionary | P2 | `resources/views/diccionario/aula.blade.php:103-147` | High |
| RULE-287 | Spanish alphabet for the A–Z index | Policy | Classroom-Dictionary | P2 | `app/Helpers/global_helper.php:682-685` | High |
| RULE-288 | Generic HTML mail sending | Policy | Comments-Notifications | P2 | `app/Helpers/mail_helper.php:26-55` | High |
| RULE-289 | Student comments dialog: which dictionaries are selectable | Policy | Comments-Notifications | P2 | `resources/views/comentarios/comentariosDiccionarioVer.blade.php:61-84` | High |
| RULE-290 | "Has comments" check | Policy | Comments-Notifications | P2 | `app/Http/Controllers/ComentariosController.php:146-200` | High |
| RULE-291 | Comment-visibility banner misreports mode 2 | Policy | Comments-Notifications | P2 | `resources/views/layouts/partials/components/modal-formulario-comentarEntrada.blade.php:26-28` | High |
| RULE-292 | PDF back-cover credits | Policy | Exports | P2 | `resources/views/layouts/partials/pdf/daContraPortada.blade.php:9-36` | High |
| RULE-293 | PDF export options | Policy | Exports | P2 | `resources/views/layouts/partials/dpOptionsPDFform.blade.php:9-33` | High |
| RULE-294 | Theme and language catalogues | Policy | Master-Data | P2 | `database/seeders/MasterTablesDataSeederUpdate.php:69-76` | Medium |
| RULE-295 | Study-level ordering (Multiestudio, Infantil first) | Policy | Master-Data | P2 | `app/Helpers/global_helper.php:1309-1325` | Medium |
| RULE-296 | Tag filter list is built from all users' tags | Policy | Personal-Dictionary | P2 | `app/Helpers/dpEntradas_helper.php:636-644` | High |
| RULE-297 | "Modified by teacher" marker | Policy | Publication-Moderation | P2 | `resources/views/layouts/partials/consulta/entradasAulaListado.blade.php:79-81` | Medium |
| RULE-298 | The esAdministrador policy is broken | Policy | Roles-Authorization | P2 | `app/Policies/DicAulaPolicy.php:54-58` | High |
| RULE-299 | List of a user's non-vigente dictionaries | Policy | SchoolYear-Vigencia | P2 | `app/Helpers/dicAula_helper.php:2075-2088` | High |
| RULE-300 | Sense up/down arrows | Policy | Senses-Acepciones | P2 | `resources/views/layouts/partials/consulta/dpEntradaAcepcion.blade.php:10-25` | High |
| RULE-301 | Send-entry dialog choice depends on the number of joined dictionaries | Policy | Submissions-Envios | P2 | `app/Http/Controllers/EnviosController.php:338-414` | High |
| RULE-302 | Send screen lists joined dictionaries by sending status; flag for non-teachers | Policy | Submissions-Envios | P2 | `app/Http/Controllers/EnviosController.php:49-72` | High |
| RULE-303 | Delete warning differs for an entry already sent | Policy | Submissions-Envios | P2 | `app/Helpers/dpEntradas_helper.php:362-382` | High |
| RULE-304 | Video thumbnail is the frame at second 1 | Policy | Uploads-Media | P2 | `app/Helpers/dpMedios_helper.php:83-85` | Medium |

## Calculation

### RULE-001: Persisted attributes and forced defaults
**Category:** Calculation
**Domain:** Classroom-Dictionary
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:67-88`
**Plain English:** Create and edit upsert `dic_aula` with:
- owner = caller;
- type default 1; letra_grupo default 0; area default 1;
- estado always 1, so saving reactivates the dictionary;
- envios_fecha_ini = envioFecha;
- vigencia as sent.
**Specification:**
  Given the form sends maxAcepciones=5
  When saving
  Then max_acepciones_entrada = 1, because `isset(...) ?? 10` evaluates to the boolean true.
**Parameters:** `ctes.dic_aula.max_acepciones`='10' (never applied); estado '1'
**Edge cases handled:** null study level
**Suspected defect:**
- The max-senses setting is lost (1 if sent, 0 if not), and nothing else in the app enforces `max_acepciones_entrada` (grep finds it only in the form).
- `envios_fecha_fin` is set to "now" when envioFecha is given, but it is not in `$fillable`, so it is silently dropped.
- Upserting `persona_id` changes the owner on edit.
**Confidence:** High.

### RULE-002: Join-code generation
**Category:** Calculation
**Domain:** Join-Codes
**Priority:** P0
**Source:** `resources/js/dic_aula.js:968-997`
**Plain English:** On create, the browser generates the code as 6 random base-36 characters in upper case (`Math.random().toString(36).substr(2,6)`). On edit, the existing code is kept. The server re-validates it as 4–6 chars and globally unique, and regenerates if invalid (`dicAula_helper.php:1593-1621`).
**Specification:**
  Given a new dictionary
  When "Crear" is clicked
  Then a code like "K3F9QZ" is generated and must not already exist in `dic_aula.codigo`
**Parameters:** length 6; server range 4–6; `daGenerateCode` alphabet A–Z0–9
**Edge cases handled:** `Math.random` can yield fewer than 6 chars (the server accepts ≥4).
**Suspected defect:** `create()` retry loop never increments (see H03). `daGenerateCode` uses `rand(0, strlen)`, which can index past the alphabet and yield shorter codes.
**Confidence:** High.

### RULE-003: Role choice for single vs. multiple centres
**Category:** Calculation
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:824-862`
**Plain English:** When creating the user: with several centre records, the user becomes *docente* if any record's role is a teacher-type code, otherwise *alumno*. With a single record, that record's role code is mapped directly (F10).
**Specification:**
  Given CAUCE multi-centre roles [3, 1]
  Then  role = docente
  Given CAUCE multi-centre roles [3, 3]
  Then  role = alumno
  Given a single centre with Rol=5
  Then  role = docente
**Parameters:** teacher-type codes = `ctes.roles_ws_cauce.id_rol_docente_pincel`(1), `id_rol_docente_centro_profesorado`(4), `id_rol_tecnicos_educativos`(5); student code `id_rol_alumnado_pincel`(3) (config/ctes.php:472-477)
**Edge cases handled:**
- Multi-centre is detected when `InfoCentro.Rol` is not set and the first element is a non-empty array.
- A multi-centre entry with no `Rol` key raises an error.
**Confidence:** High.

### RULE-004: CAUCE role code to app role
**Category:** Calculation
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:1116-1129`
**Plain English:** CAUCE role codes 1 (Pincel teacher), 4 (teacher-centre staff) and 5 (educational technician) map to app role "docente". Code 3 (Pincel student) and **any other or missing code** map to "alumno". The role is looked up by name in `roles`.
**Specification:**
  Given CAUCE Rol=4
  Then  `users.role_id` = id of roles.name='docente'
  Given CAUCE Rol=7 (unknown) or empty
  Then  `role_id` = id of roles.name='alumno'
**Parameters:** codes '1','3','4','5' (config/ctes.php:472-477); role names 'docente', 'alumno'
**Edge cases handled:** missing 'docente'/'alumno' role rows cause a null-property error.
**Confidence:** High.
**SME question:** Should unknown CAUCE profiles (e.g. families, admin staff, inspectors) really get "alumno", or be refused?

### RULE-005: School-year start year from INICIO_CURSO
**Category:** Calculation
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:410-438`
**Plain English:** The school year starts on the day/month in `INICIO_CURSO` (default 30 August). A date on or after that day/month belongs to the school year starting that calendar year; an earlier date belongs to the year that started the previous calendar year.
**Specification:**
  Given INICIO_CURSO = "30/8/2000" (only day and month are used, as "0830")
  When getAnoIniCursoEscolar(2023-08-29) is computed
  Then the result is 2022 ("0829" < "0830")
  And for 2023-08-30 the result is 2023; for 2024-01-15 the result is 2023
**Parameters:** `ctes.inicio_curso` = env `INICIO_CURSO`, default `'30/8/2000'` (`config/ctes.php:483`). The year part is ignored.
**Edge cases handled:**
- Compares zero-padded "md" strings, so the string comparison is correct.
- `getAnoIniCurrentCursoEscolar()` (`global_helper.php:451-455`) applies the rule to "now". Its docblock says "1 de Agosto", which is stale; the code uses config.
- Uses server timezone `date()`.
**Suspected defect:**
- If the timestamp is 0, `$diames` is undefined.
- The docs file corroborates an earlier bug (Sept–Dec dates mapping to the previous year) that is now fixed. The "corrected" table matches this code.
**Confidence:** High — explicit code, corroborated by `documentos/Pruebas Fechas vigencia y cursoActual.md`.
**Also found as:** shard A card "School-year start computation" (`app/Helpers/global_helper.php:410-438`) — folded in as the same behavior.

### RULE-006: Is a classroom dictionary still in force (vigente)
**Category:** Calculation
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:2022-2037`
**Plain English:** An atemporal (timeless) dictionary is always in force. Otherwise a dictionary is in force while its `vigencia` (number of school years) is greater than the number of school years since it was created.
**Specification:**
  Given dic_aula.ano_ini_curso_escolar = 2022, vigencia = 3, not atemporal
  When daEsVigente is evaluated in school year 2024 (elapsed 2)
  Then it is in force (3 > 2); in school year 2025 (elapsed 3) it is NOT in force
**Parameters:** The atemporal flag comes from `DicAula::esAtemporal()`: a `dic_aula_atemporales` row exists with estado = '1' (`app/Models/DicAula.php:176-182`). The `hasta` date is ignored here.
**Edge cases handled:** Atemporal short-circuit. vigencia = 1 means "creation year only".
**Suspected defect:** This disagrees with the list filter in B06 (`>=` there vs `>` here). `DicAula::activarDiccionarioCursoActual` (`app/Models/DicAula.php:184-190`) hardcodes `+9`, which corresponds to a fixed vigencia of 10.
**Confidence:** High.
**Also found as:** shard A card "Vigencia (multi-year validity) definition" (`app/Helpers/dicAula_helper.php:2022-2037`) — folded in as the same behavior.

### RULE-007: Reactivate a dictionary for the current school year
**Category:** Calculation
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Http/Controllers/Voyager/VoyagerCompassController.php:531-552`
**Plain English:** Reactivating sets estado=1 and stretches the validity (in years) so it covers the current school year: vigencia = current school-year start year − dictionary start year + 1.
**Specification:**
  Given dictionary ano_ini_curso_escolar=2022 and today in school year 2025/26 (start year 2025)
  When  an admin opens /admin/vigencia/activacion/{id}
  Then  estado=1, vigencia = 2025 − 2022 + 1 = 4
**Parameters:** `getAnoIniCursoEscolar(now)` (helper; start date `ctes.inicio_curso`)
**Edge cases handled:** none
**Suspected defect:** No check against `ctes.vigencia_max` (10), so the computed vigencia can exceed the maximum.
**Confidence:** High.

### RULE-008: Last-submission lookup: published > sent > any, current school year only
**Category:** Calculation
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:161-247`
**Plain English:** To decide what a personal entry's "last submission" is, the system looks first for the newest one where it is published (3), then sent (1), then any status, but only among submissions from the current school year.
**Specification:**
  Given entry 5 has a submission from 2026-09-10 with envios_entradas.estado=3 and another from 2026-10-01 with estado=1, and the current school year is 2026
  When the last submission is looked up
  Then the 2026-09-10 (published) one is returned
  Given entry 5 was published only in school year 2025
  Then the result is null (treated as never sent)
**Parameters:** estados_envios.publicado=3, enviado=1; ano_ini_curso_escolar = getAnoIniCursoEscolar(now)
**Edge cases handled:** Status is filtered on envios_entradas.estado, not dp_envios.estado. Soft-deleted envios_entradas are excluded.
**Suspected defect:** This feeds personal entry deletion (dpEntradas_helper.php:44-90). An entry published in an earlier school year can be deleted from the personal dictionary as if it had never been sent.
**Confidence:** High. SME question: "Should 'already published' protection on personal entries apply across school years?"

### RULE-009: Submission window state (inactive / active / planned)
**Category:** Calculation
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Models/DicAula.php:79-98`
**Plain English:** Submissions are off (0), on (1), or planned (2). A planned window resolves to on or off by comparing its start date with now.
**Specification:**
  Given envios_habilitados = '2' and envios_fecha_ini = 2030-01-01
  When `enviosHabilitados()` is evaluated on 2026-10-04
  Then it returns '1' (active), because the start date is later than now; once the date has passed it returns '0'
**Parameters:** `ctes.estado_envio_habilitado` 0/1/2.
**Edge cases handled:** NULL start date with planned mode compares NULL against now and resolves to inactive.
**Suspected defect:** The comparison looks inverted (submissions open **before** the planned start and close after it). `envios_fecha_fin` is never persisted (not fillable).
**Confidence:** Medium — SME: "For a planned window, should submissions open on envios_fecha_ini? Is there an end date?"
**Also found as:** shard G card "Submission-window state (inactive/active/planned)" (`app/Models/DicAula.php:79-98`) — folded in as the same behavior.

### RULE-010: Classroom PDF shows every optional field and renumbers senses
**Category:** Calculation
**Domain:** Exports
**Priority:** P1
**Source:** `resources/views/layouts/partials/pdf/daMainPDFView.blade.php:70-128`
**Plain English:** The classroom PDF prints "más datos", the usage example, the language translation, the themes and the image whenever they are non-empty, without consulting `dic_aula_campos`. Senses are numbered `index + 1` (consecutive), not by the stored `orden`.
**Specification:**
  Given dictionary D with field 5 hidden and an entry whose stored sense orders are 1 and 3
  When the PDF is exported
  Then "más datos" is printed and the senses are numbered 1 and 2
**Parameters:** none
**Suspected defect:** the field-visibility configuration is ignored in the export.
**Confidence:** Medium. I didn't verify whether the export helper strips the fields beforehand. SME: "Must the classroom PDF respect the dictionary's field-visibility configuration?"

### RULE-011: Join-code generation
**Category:** Calculation
**Domain:** Join-Codes
**Priority:** P1
**Source:** `app/Helpers/dicAula_helper.php:1613-1622`
**Plain English:**
- The server-side code takes N random characters from A-Z0-9.
- The client proposes the first code (dic_aula.js:982-983): 6 uppercase base-36 characters.
- The client sends a placeholder "0" when it has no code; that value fails validation and the server regenerates.
**Specification:**
  Given cifras = 6
  When generating
  Then the result is up to 6 characters from [A-Z0-9].
**Parameters:** alphabet 'A-Z0-9' (36 chars); length 6
**Edge cases handled:** none
**Suspected defect:**
- `rand(0, strlen)` is inclusive of 36, so about 1 in 37 picks returns an empty string and codes can be 5 characters or shorter.
- `rand` is not cryptographically random.
**Confidence:** High.
**Also found as:** shard B card "Join-code generation" (`app/Helpers/dicAula_helper.php:1613-1622`) — folded in as the same behavior.

### RULE-012: Publish-all preview
**Category:** Calculation
**Domain:** Publication-Moderation
**Priority:** P1
**Source:** `resources/views/layouts/partials/components/modal-publicar-listado.blade.php:10-61`
**Plain English:** "Publicar todo" summarises the current filtered list. It counts entries that will be published, entries already published under the same headword, and headwords duplicated within the list (only one copy is published). The publish button appears only if at least 1 entry is publishable, and only those ids are sent.
**Specification:**
  Given 12 listed submissions: 2 headwords already published, and "casa" appearing 3 times
  When the coordinator opens publish-all
  Then it shows 12 in search, 8 to publish, 2 already published, 2 duplicates
**Parameters:** `comprobarPublicarListadoEntradas` (`dicAula_helper.php:586`)
**Edge cases handled:** the server re-checks duplicates per entry (`publicarEntradaSimple`).
**Confidence:** High.

### RULE-013: Statistics school year starts in September
**Category:** Calculation
**Domain:** SchoolYear-Vigencia
**Priority:** P1
**Source:** `app/Http/Controllers/Voyager/VoyagerCompassController.php:163-171`
**Plain English:** For the exported statistics, a record created in September or later counts toward the school year starting that calendar year. Records from January to August count toward the previous year.
**Specification:**
  Given personal dictionaries created on 2025-09-01 and 2026-08-31
  When the `dicsPersonales` CSV is generated
  Then both count under ano_ini_curso = 2025
**Parameters:** cutoff month > 8. The same rule appears for entries at lines 188-197 and for schools at lines 213-241.
**Edge cases handled:** Soft-deleted rows are excluded (`deleted_at IS NULL`).
**Suspected defect:** The school (centros) query labels years inconsistently: "2025" for January–August rows but "2025/2026" for September–December rows, so the same school year is split into two groups.
**Confidence:** High

### RULE-014: School-year bucketing in statistics is inconsistent
**Category:** Calculation
**Domain:** SchoolYear-Vigencia
**Priority:** P1
**Source:** `app/Http/Controllers/Voyager/VoyagerCompassController.php:163-240`
**Plain English:**
- Personal dictionaries and entries are assigned to school year Y when created after August (month>8), otherwise Y−1.
- Centres use month<9 → "Y−1" (a number), else the string "Y/Y+1", so the same school year appears as two different labels.
- None of these use the configured `INICIO_CURSO`.
**Specification:**
  Given a personal dictionary created 2025-09-01
  Then  ano_ini_curso=2025
  Given created 2025-08-31
  Then  2024
  Given a centre created 2025-10-01
  Then  label "2025/2026"
  Given a centre created 2026-03-01
  Then  label "2025"
**Parameters:** hard-coded cutover September 1st
**Edge cases handled:** none
**Suspected defect:** Mixed labels split the same school year. The hard-coded month differs from `ctes.inicio_curso` (30/8).
**Confidence:** High.

### RULE-015: School-year label "YYYY/YYYY+1"
**Category:** Calculation
**Domain:** SchoolYear-Vigencia
**Priority:** P1
**Source:** `app/Helpers/global_helper.php:364-382`
**Plain English:** The school year shown for a timestamp is "Y/Y+1" when the day/month is on or after the start day/month, otherwise "Y-1/Y".
**Specification:**
  Given INICIO_CURSO = "30/8"
  When getFormattedCursoEscolar(timestamp of 2022-09-05) is called
  Then it returns "2022/2023"; for 2022-08-09 it returns "2021/2022"
**Parameters:** `ctes.inicio_curso`. The companion `getStrCursoByAnoIni(2022)` returns "2022/2023" (`global_helper.php:392-394`).
**Edge cases handled:** None beyond B01.
**Suspected defect:** `$diamesInicioCurso` is only assigned `if ($t)`. If the config date parses to timestamp 0 or false, the comparison uses an undefined variable. This is duplicate logic of B01; the rewrite should keep one implementation.
**Confidence:** High.

### RULE-016: Admin dictionary classification
**Category:** Calculation
**Domain:** SchoolYear-Vigencia
**Priority:** P1
**Source:** `app/Http/Controllers/Voyager/VoyagerCompassController.php:441-506`
**Plain English:** The admin validity screen sorts classroom dictionaries into four lists:
- 0 = not valid: inactive, not valid, not timeless
- 1 = valid: active, valid, not timeless
- 2 = timeless: has an active atemporal record
- 3 = state errors: active but not valid and not timeless

Lists 0 and 1 are sorted by title.
**Specification:**
  Given dic A estado=1, expired, not timeless
  When  type 3 is selected
  Then  A is listed as a state error
**Parameters:** `ctes.estados.activo`='1', `inactivo`='0'
**Edge cases handled:** a type index outside 0-3 errors (array index).
**Confidence:** High.

### RULE-017: Last valid school year = start year + vigencia − 1
**Category:** Calculation
**Domain:** SchoolYear-Vigencia
**Priority:** P1
**Source:** `documentos/vistas.sql:6-20`
**Plain English:** Statistics label a dictionary's first and last school years from its start year and vigencia.
**Specification:**
  Given ano_ini_curso_escolar = 2021 and vigencia = 3
  When `diccionariosDatosCursoView` is queried
  Then curso_inicio = "2021/2022" and curso_fin = "2023/2024"
**Parameters:** none.
**Edge cases handled:** Only rows with estado = 1 and not soft-deleted are counted.
**Confidence:** High — explicit SQL. It should match `queryVigencia` in the app (another shard).

### RULE-018: Server assigns sense order (renumber 1..n, then add at the end)
**Category:** Calculation
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `app/Helpers/dpEntradas_helper.php:310-348`
**Plain English:** Before a sense is added, the entry's senses are renumbered 1, 2, 3... in their current order, closing any gaps and duplicates. The new sense gets last + 1. The order sent by the form is ignored on insert.
**Specification:**
  Given an entry with senses at orden 1, 3, 3
  When  a new sense is saved
  Then  the existing senses become 1, 2, 3 and the new sense gets 4
**Parameters:** `ctes.primer_registro` = '1'
**Edge cases handled:** An entry with no senses gets orden 1. Soft-deleted senses are left out of the renumbering.
**Confidence:** High.

### RULE-019: Moving a sense up/down swaps it with its neighbour
**Category:** Calculation
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `app/Helpers/dpAcepciones_helper.php:264-309`
**Plain English:** Moving up swaps the sense's order with the sense at order − 1. Moving down swaps it with the sense at order + 1. At either end it swaps with itself, so nothing changes.
**Specification:**
  Given senses A(1), B(2)
  When  B is moved up
  Then  B = 1 and A = 2
**Parameters:** `ctes.primer_registro` = '1'
**Edge cases handled:** first and last positions.
**Suspected defect:** If there is a gap in the numbering (for example 1, 3), moving 3 up finds no sense at 2 and crashes on null. See also C02.
**Confidence:** High.

### RULE-020: Later senses move up one place after a delete
**Category:** Calculation
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `app/Helpers/dpAcepciones_helper.php:167-204`
**Plain English:** When a sense at position N is deleted, every sense of that entry with order > N has its order reduced by 1.
**Specification:**
  Given senses at orden 1, 2, 3, 4
  When  the sense at orden 2 is deleted
  Then  the remaining senses are 1, 2, 3
**Parameters:** none
**Edge cases handled:** none
**Confidence:** High.

### RULE-021: Submissions per entry (count and latest)
**Category:** Calculation
**Domain:** Submissions-Envios
**Priority:** P1
**Source:** `app/Helpers/dicAula_helper.php:1489-1522`
**Plain English:** For a dictionary (optionally one student), submissions are grouped by personal entry. The result gives the number of times each entry was submitted and the latest submission (max id).
**Specification:**
  Given a student sent "luna" 3 times to dic 3
  When listed
  Then one row: count = 3, id = the latest envio_entrada id
**Parameters:** None.
**Edge cases handled:** None.
**Confidence:** High.

### RULE-022: Image thumbnail: longest side 250 px, JPEG/PNG/GIF only
**Category:** Calculation
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `app/Helpers/create-thumbnail.php:39-150`
**Plain English:** After an image upload, a thumbnail is made whose longer side is 250 px, keeping the aspect ratio. It is saved next to the original as `<name>_thumb.jpg`.
**Specification:**
  Given a 1000x500 JPEG
  When it is uploaded
  Then the thumbnail is 250x125 (height = floor(250 / 2.0)). A 600x900 image gives 166x250 (width = floor(250 × 0.667)).
**Parameters:** `ctes.imagen_thumbnail_tamano`=250; suffix `ctes.video_thumbnail_sufijo`="_thumb" (reused for images); JPEG quality 100; PNG compression 0; GIF/PNG keep transparency. Called from `dpMedios_helper.php:70-80`.
**Edge cases handled:** Unreadable images return null and no thumbnail is made. GIF uploads are logged.
**Suspected defect:** (1) BMP, TIF/TIFF and ICO are allowed (G02) but have no handler. `IMAGE_HANDLERS[$type]` hits an undefined index, which Laravel turns into an exception after the DB row is saved, so these images get no thumbnail. (2) PNG and GIF thumbnails are written in their own format into a file named `.jpg`. (3) The PDFs embed the thumbnail (G10, G14), so images without one have a broken reference.
**Confidence:** High

### RULE-023: Stats count users by global role, once per person
**Category:** Calculation
**Domain:** Admin-Voyager
**Priority:** P2
**Source:** `documentos/vistas.sql:56-97`
**Plain English:** "Users with role X in classroom dictionaries" counts distinct active people per start year, grouped by their **account** role (`users.role_id`), not their per-dictionary role.
**Specification:**
  Given a teacher account who participates in 3 active dictionaries from 2021
  When the view is queried
  Then they count once under "docente" for 2021/2022
**Parameters:** filters on participant, persona and dictionary estado = 1 and not deleted.
**Edge cases handled:** Persons without a linked user are excluded (inner joins).
**Suspected defect:** It ignores `rol_diccionario_id`. `ensenanzasNivelAreaMateria21View` filters year 2020 despite its name (`documentos/vistas.sql:333`). The `ensenanzas*` views do not exclude deleted or inactive dictionaries.
**Confidence:** High.

### RULE-024: Admin statistics on dictionaries and participants
**Category:** Calculation
**Domain:** Admin-Voyager
**Priority:** P2
**Source:** `app/Http/Controllers/Voyager/VoyagerCompassController.php:95-147`
**Plain English:**
- Classroom dictionaries (not soft-deleted) are counted per start year.
- Active dictionaries with vigencia>0 are counted per start year.
- Participants of active, vigencia>0 dictionaries are counted per dictionary role.
**Specification:**
  Given 5 dic_aula for 2024 (1 deleted) and 3 for 2025
  Then  counts {2024:4, 2025:3}
**Parameters:** filters estado=1, vigencia>0, deleted_at IS NULL
**Edge cases handled:** none
**Suspected defect:** The participant count does not exclude soft-deleted dictionaries or participants.
**Confidence:** High.

### RULE-025: Published-entries statistic
**Category:** Calculation
**Domain:** Admin-Voyager
**Priority:** P2
**Source:** `app/Http/Controllers/Voyager/VoyagerCompassController.php:174-186`
**Plain English:** Counts submitted entries with estado=3 (published) that are linked to a classroom dictionary that is active. The name says "in valid dictionaries", but vigencia is not checked.
**Specification:**
  Given 10 envios_entradas estado=3 in active dic_aula
  Then  count=10
**Parameters:** `envios_entradas.estado=3` (ctes.estados_envios.publicado)
**Edge cases handled:** excludes soft-deleted envios_entradas.
**Confidence:** Medium.
**SME question:** Should "vigentes" also filter vigencia and exclude deleted dictionaries?

### RULE-026: Minutes until a timestamp (cookie consent lasts 3 months)
**Category:** Calculation
**Domain:** Admin-Voyager
**Priority:** P2
**Source:** `app/Helpers/global_helper.php:1334-1349`
**Plain English:** Returns the whole minutes between now and a timestamp (days×1440 + hours×60 + minutes). It is used to make the cookie-consent cookie last until 3 months from now (`HomeController.php:208-214`).
**Specification:**
  Given now + 1 day 2 h 5 min
  When computed
  Then 1565
**Parameters:** "+3 month" lives in the controller.
**Edge cases handled:** None.
**Suspected defect:** Returns the absolute difference, so past timestamps give positive minutes.
**Confidence:** High.

### RULE-027: Theme-tag filter list = tags used in published entries
**Category:** Calculation
**Domain:** Classroom-Dictionary
**Priority:** P2
**Source:** `app/Helpers/dicAula_helper.php:1133-1148`
**Plain English:** The theme filter shows only themes used by senses of the dictionary's published, visible entries, sorted by name. With no dictionary it shows all themes (`1107`).
**Specification:**
  Given published entries whose senses use themes {Animales, Cuerpo}
  When the query screen loads
  Then the list = [Animales, Cuerpo]
**Parameters:** `ctes.campos_acepcion.tematica` = 4.
**Edge cases handled:** None.
**Suspected defect:** Line 1116 reads `ctes.estado_envios.publicado` (wrong key, which resolves to null). The variable is unused.
**Confidence:** High.

### RULE-028: A classroom dictionary is flagged active (selectable) when it has visible comments
**Category:** Calculation
**Domain:** Comments-Notifications
**Priority:** P2
**Source:** `app/Helpers/ComentariosHelper.php:22-43`
**Plain English:** In the comment views, a connected classroom dictionary is shown as selectable (green) when it has at least one visible entry comment or general comment. Otherwise it is grey.
**Specification:**
  Given classroom D with visibility 1, 0 entry comments and 2 general comments
  When the list is built
  Then `D.estadoActual='1'` (selectable).
**Parameters:** `ctes.estados.activo='1'` and `ctes.estados.inactivo='0'`.
**Edge cases handled:** none.
**Suspected defect:** the counts include all comments of the classroom (for every student), not only the viewer's, so a classroom can be green while the student has no comment there. The view compares `estadoActual` with `comentarios_visibles.visible`, which only works because both values happen to be '1' (`modal-comentarosentrada.blade.php:46`).
**Confidence:** High.

### RULE-029: Hidden-entry count shown in the PDF
**Category:** Calculation
**Domain:** Exports
**Priority:** P2
**Source:** `app/Helpers/dpEntradas_helper.php:697-704`
**Plain English:** The number of hidden entries is the count of non-deleted entries in the dictionary with estado '2'.
**Specification:**
  Given 10 entries, 3 hidden
  When  the PDF is built (`PDFController.php:156`)
  Then  n_entradasOcultas = 3
**Parameters:** `estados_entrada.oculta` = '2'
**Edge cases handled:** none
**Confidence:** High.

### RULE-030: Join-code display split
**Category:** Calculation
**Domain:** Join-Codes
**Priority:** P2
**Source:** `app/Helpers/global_helper.php:1174-1178`
**Plain English:** A code is displayed with a space before its last 4 characters.
**Specification:**
  Given "AB12CD"
  When displayed
  Then "AB 12CD"
**Parameters:** 4.
**Edge cases handled:** None.
**Confidence:** High.

### RULE-031: Stress-test data only outside production; NIF check letter
**Category:** Calculation
**Domain:** Master-Data
**Priority:** P2
**Source:** `database/seeders/StressTestUserDataSeeder.php:29-43`
**Plain English:** Synthetic NIFs get the official check letter (number mod 23 indexed into "TRWAGMYFPDXBNJZSQVHLCKEO"). The stress seeder (500 teachers and 2,000 students) refuses to run in production (:270).
**Specification:**
  Given DNI number 12345678
  When `letraNIF` runs
  Then 12345678 mod 23 = 14, so the letter is "Z"
**Parameters:** letter table; 500 teachers, 2,000 students, 5 and 20 entries each; join code prefix "TES".
**Edge cases handled:** Skipped when `APP_ENV=production`.
**Confidence:** High.

### RULE-032: Person display-name format
**Category:** Calculation
**Domain:** Master-Data
**Priority:** P2
**Source:** `app/Models/Persona.php:58-67`
**Plain English:** A full name is "Nombre Apellidos", or "Apellidos, Nombre" when surname-first is requested. PDFs use the first form for the author and the file name.
**Specification:**
  Given nombre="Ana", apellidos="Pérez Gil"
  When nombreCompleto() or nombreCompleto(true) is called
  Then "Ana Pérez Gil" or "Pérez Gil, Ana"
**Parameters:** —
**Edge cases handled:** —
**Confidence:** High

### RULE-033: Search: substring, tags (any match), alphabetical, 10 per page
**Category:** Calculation
**Domain:** Personal-Dictionary
**Priority:** P2
**Source:** `app/Helpers/dpEntradas_helper.php:530-569`
**Plain English:** Searching your own dictionary returns entries whose text contains the normalised term. If tags are given, an entry must have at least one sense with any of those tags. Results are sorted A-Z, 10 per page.
**Specification:**
  Given entries "casa", "casero", "perro"
  When  the user searches "cas" from offset 0
  Then  results are ["casa", "casero"]
**Parameters:** `ctes.scrollEntradas` = 10
**Edge cases handled:**
  - Tag-only search: `dpEntradas_helper.php:580-606`.
  - Starts-with-letter search: `:493-513`.
  - Hidden entries are included, because the owner sees everything.
**Suspected defect:** LIKE wildcards in the user's term are not escaped.
**Confidence:** High.

### RULE-034: Statistics infer school year from creation month
**Category:** Calculation
**Domain:** SchoolYear-Vigencia
**Priority:** P2
**Source:** `documentos/vistas.sql:25-27`
**Plain English:** For personal dictionaries and centres, a record created before September belongs to the previous school year.
**Specification:**
  Given a personal dictionary created on 2022-08-31
  When `diccionariosDatosCursoView` is queried
  Then it is counted in "2021/2022"
**Parameters:** month cut-over 9 (September).
**Edge cases handled:** None.
**Suspected defect:** The app's `getAnoIniCursoEscolar` uses `INICIO_CURSO` = 30 August (`config/ctes.php:483`; `app/Helpers/global_helper.php:410-424`). Records created on 30–31 August land in different years in the stats and in the app.
**Confidence:** High.

### RULE-035: Grammatical attribute abbreviation
**Category:** Calculation
**Domain:** Senses-Acepciones
**Priority:** P2
**Source:** `app/Models/DiccionarioPersonalAcepcion.php:86-124`
**Plain English:** A sense's attribute line is built from category, gender and number. Each uses its configured abbreviation, or the full description if none is configured. The gender "No tiene" is omitted, and the result is lower-cased.
**Specification:**
  Given category Sustantivo, gender Femenino, number Plural
  When attributes are rendered
  Then "sust. f. pl.  " (trailing spaces included)
**Parameters:** `ctes.abreviatura` (Sustantivo→sust., Adjetivo→adj., Verbo→v., Masculino→m., Femenino→f., Masculino y femenino→m. y f., Singular→sing., Plural→pl., …). `abreviatura_tamano`=4 and `abreviatura_personalizada` are not used.
**Edge cases handled:** Missing values are skipped. The same logic is duplicated in `app/Models/EnvioAcepcion.php:86-124`.
**Confidence:** High
**Also found as:** shard I card "Grammatical attribute abbreviation" (`app/Models/DiccionarioPersonalAcepcion.php:86-125`) — folded in as the same behavior.

### RULE-036: Date-input conversion to start of day
**Category:** Calculation
**Domain:** Submissions-Envios
**Priority:** P2
**Source:** `app/Helpers/global_helper.php:1254-1265`
**Plain English:** A date posted as YYYY-MM-DD is stored as that day at 00:00:00. Other formats pass through unchanged.
**Specification:**
  Given "2024-03-01"
  When converted
  Then "2024-03-01 00:00:00"
**Parameters:** None.
**Edge cases handled:** Non-matching input is passed through.
**Confidence:** High.


## Validation

### RULE-037: Persona matching order
**Category:** Validation
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:779-792`
**Plain English:** The person is looked up by NIF/NIE if CAUCE gives one, otherwise by passport, otherwise by CIAL (student ID). Only the first identifier present is used. If no persona matches, a new one is created as active with the default avatar.
**Specification:**
  Given CAUCE InfoUsuario has NifNie=<masked> and CIAL=<masked>
  When  provisioning runs
  Then  the persona is searched by `personas.NIF_NIE` only; if none is found, a new Persona is created with avatar_URL='default.svg', estado=1
**Parameters:** CAUCE fields `Usuario.InfoUsuario.NifNie | Pasaporte | CIAL`; defaults avatar `default.svg`, estado `1`
**Edge cases handled:** an existing persona keeps its avatar and estado.
**Suspected defect:**
- If all three identifiers are empty, `$persona` is undefined and PHP 8 / Laravel raise an ErrorException, giving a 500 instead of a clean denial.
- A persona found only by CIAL is not found again later if CAUCE starts sending a NIF (a duplicate persona is created).
- An existing persona with estado=0 is not blocked.
**Confidence:** High.
**SME question:** Should an inactive persona (estado 0) be refused at login?

### RULE-038: Session userData requires persona and centres, but this is not enforced
**Category:** Validation
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:466-497`
**Plain English:** After login the app stores in session `userData` = {user, persona, centros, roles}. The function treats a missing persona or empty centre list as forbidden, but it only *returns* a 403 view. The callers (CasController.php:37, VoyagerAuthController.php:50) ignore the return value, so the user stays logged in with no `userData`.
**Specification:**
  Given a logged-in user with no linked persona (e.g. a Voyager password-only account)
  When  `setUserDataSession()` runs
  Then  intended: 403; actual: login continues and `session('userData')` is missing, so `getSessionPersona()` returns false
**Parameters:** session key `userData` with keys `user`, `persona`, `centros`, `roles` (all roles via `roles_all()`)
**Edge cases handled:** exceptions also return a 403 view, which is likewise ignored.
**Suspected defect:** The 403 is never sent, so the check does nothing.
**Confidence:** High.
**Also found as:** shard B card "Session needs a linked person and at least one school" (`app/Helpers/global_helper.php:466-497`) — folded in as the same behavior.

### RULE-039: Access is denied only when CAUCE reports an error
**Category:** Validation
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:770-778`
**Plain English:** CAUCE is the access gate. If its reply has a non-empty `MensajeError`, login is refused. Otherwise anyone authenticated by CAS is admitted, whatever their role.
**Specification:**
  Given CAUCE returns MensajeError="Usuario no autorizado"
  When  the user logs in
  Then  `userValidatorCAUCE` returns false and the callback answers 403
  Given MensajeError is empty (`<MensajeError/>` parses to [])
  Then  provisioning continues
**Parameters:** XML element `MensajeError`
**Edge cases handled:**
- Persona/user/link save failures also return false (403).
- In the multi-centre branch, an empty role list also returns false (857), but that is unreachable because the list is built from a non-empty array.
**Confidence:** High.
**SME question:** Is "no MensajeError" really the only authorization rule, or should certain CAUCE roles (families, administrative staff) be refused?

### RULE-040: Single centre handling
**Category:** Validation
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:1019-1070`
**Plain English:** With a single centre record, a non-empty scalar `Codigo` means that centre is found or created and assigned. Otherwise the placeholder centre is assigned.
**Specification:**
  Given InfoCentro {Codigo:"38000001",Nombre:"IES A",Rol:1}
  Then  centros_ids=[id of 38000001]
  Given InfoCentro {Codigo:<empty>,Nombre:<empty>,Rol:5}
  Then  centros_ids=[id of 00000000]
**Parameters:** none
**Edge cases handled:**
- An empty XML element (parsed as an array) is treated as no code.
- If centre creation fails, `$center` is undefined, line 1070 errors and the error is not caught.
**Confidence:** High.

### RULE-041: Students can't select a classroom dictionary that is hidden from students
**Category:** Validation
**Domain:** Classroom-Dictionary
**Priority:** P0
**Source:** `resources/views/diccionario/home.blade.php:60-102`
**Plain English:** In the classroom-dictionary switcher, teachers see every dictionary they joined (non-visible ones flagged with a tooltip). Students see non-visible ones greyed out and without a `data-id`, so they can't make them active.
**Specification:**
  Given a student joined to dictionary D with `visible_estudiante = 0`
  When they open the classroom-dictionary menu
  Then D is listed greyed with "Diccionario de aula temporalmente no visible" and clicking it does nothing (no `data-id`, so `selectDicAula.js:108-121` can't select it)
**Parameters:** `ctes.estados.activo = '1'`; lang key `mensaje_dicAula_no_visible`
**Edge cases handled:** the active dictionary is highlighted; the same logic is duplicated in `resources/views/layouts/partials/components/select-DicAula.blade.php:80-120`.
**Suspected defect:** Client-only. `DicAulaController::setDicAulaActivo` → `daDiccionarioAulaSelDiccionarioActivo` (`app/Helpers/dicAula_helper.php:488-500`) puts any `DicAula::find($id)` into the session, with no membership or visibility check. Also, line 70 uses strict `!==` against the string `'1'`; if the DB returns an int, every dictionary gets the "not visible" tooltip for teachers.
**Confidence:** High for the view behaviour. SME: "Must a student be blocked server-side from activating, or browsing, a classroom dictionary that is not visible to students, or one they haven't joined?"

### RULE-042: Maximum senses per entry is offered but never stored or enforced (suspected defect)
**Category:** Validation
**Domain:** Classroom-Dictionary
**Priority:** P0
**Source:** `resources/views/diccionario/crearDicAula.blade.php:244-253`
**Plain English:** The teacher picks a maximum number of senses per entry from 1–10 (literal `$maxAcepciones = 10` in the view). The server stores `isset($params['maxAcepciones']) ?? 10`, which evaluates to boolean true (1) whenever the field is posted (`dicAula_helper.php:78`). No code reads `max_acepciones_entrada` to limit senses.
**Specification:**
  Given a teacher selecting a maximum of 3
  When saved
  Then `dic_aula.max_acepciones_entrada` = 1, and students can still submit entries with 10 senses
**Parameters:** view literal 10; `ctes.dic_aula.max_acepciones = '10'`
**Suspected defect:** yes, the `?:`/`??` misuse plus the limit is never enforced.
**Confidence:** High. SME: "Should the limit be enforced when students submit entries (reject, or send only the first N senses)?"

### RULE-043: Maximum senses per entry defaults to 10 but is stored as a boolean
**Category:** Validation
**Domain:** Classroom-Dictionary
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:79`
**Plain English:** A classroom dictionary limits how many senses an entry may have (default 10). The save code stores `isset(...) ?? default`, which yields 1 or 0 instead of the number.
**Specification:**
  Given a teacher sets maxAcepciones = 5
  When the dictionary is saved
  Then max_acepciones_entrada = 1 (true); if no value is given, 0 (false)
**Parameters:** `ctes.dic_aula.max_acepciones = '10'` (`config/ctes.php:182-184`). Column smallint (`create_initial_structure.php:214`).
**Edge cases handled:** None.
**Suspected defect:** The PHP precedence error makes the limit unusable.
**Confidence:** High — SME: "What maximum senses per entry should apply, and is it enforced anywhere today?"

### RULE-044: Has-submissions check (blocks dictionary deletion)
**Category:** Validation
**Domain:** Classroom-Dictionary
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:1546-1563`
**Plain English:** A dictionary "has pending submissions" if any submitted entry exists for it, in any state. The delete check (`DicAulaController.php:1610-1625`) refuses deletion when published entries or any submissions exist.
**Specification:**
  Given dic 9 has one envio_entrada in estado '1'
  When deletion is tested
  Then borrable = false, error 'envios'
**Parameters:** None.
**Edge cases handled:** None. The status is not filtered, so "pending" really means "any".
**Confidence:** High.

### RULE-045: General comments to a student (coordinator only)
**Category:** Validation
**Domain:** Comments-Notifications
**Priority:** P0
**Source:** `resources/views/comentarios/comentariosDiccionarioEnviar.blade.php:45-83`
**Plain English:** A coordinator writes general comments addressed to one participant chosen from a list. The last chosen participant is remembered. If there are no participants, a notice is shown.
**Specification:**
  Given coordinator C of D with 25 participants
  When C picks student S and saves comment text
  Then a `comentarios_generales` row (D, S, C, now) is created
**Parameters:** server requires `dic_aula_id`, `estudiante`, `tinyComentario` and `radio`
**Suspected defect:** GET is protected (`esCoordinador`, `ComentariosController.php:220`), but POST `comentariosPost` (lines 287-330) has no authorization, so any user can post general comments to any dictionary or student. The participant list comes from the session's active dictionary, not route `{id}`, and includes teachers.
**Confidence:** High.

### RULE-046: Entry comments can be edited or deleted by any teacher, and are rendered as raw HTML
**Category:** Validation
**Domain:** Comments-Notifications
**Priority:** P0
**Source:** `resources/views/layouts/partials/components/botones/entrada/comentarios.blade.php:1-24`
**Plain English:** The comment history for a submitted entry (newest first) shows edit and delete icons on **every** comment, whatever its author. The text is output unescaped (`{!! !!}`). The comment icon is green when comments exist and grey otherwise (`comentar.blade.php:1-28`).
**Specification:**
  Given entry E with a comment written by coordinator A
  When coordinator B opens E's comment dialog
  Then B can edit or delete A's comment
**Parameters:** none
**Suspected defect:** Client-only. `DicAulaController::comentarioEdit/comentarioDelete` (lines 1173-1256) have no authorization and don't verify the comment belongs to `{identrada}`/`{id}`. Unescaped HTML is a stored-XSS risk.
**Confidence:** High.

### RULE-047: Per-dictionary required-field validation of each sense
**Category:** Validation
**Domain:** Fields-Config
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:534-612`
**Plain English:** Each classroom dictionary sets which sense fields are mandatory. A sense fails if any active mandatory field is empty.
**Specification:**
  Given dictionary D has active (estado=1) and mandatory (obligatorio=1) fields 1 (grammatical category) and 8 (image)
  And a sense has cat_gramatical_id=3 and no image media
  When the sense is checked
  Then result.ok=false and errors=[<nombre_campo of field 8>]
**Parameters:** Hard-coded mapping of mst_campo_entrada_id:
- 1 = cat_gramatical_id not null
- 2 = genero_id not null
- 3 = numero_id not null
- 4 = at least one topic
- 5 = frase_ejemplo not null
- 6 = a video media exists
- 7 = an audio media exists
- 8 = an image media exists
- 9 = idioma_palabra not null and not ""
**Edge cases handled:**
- An empty string frase_ejemplo passes field 5 (only null is checked).
- Field 9 checks only idioma_palabra, not idioma_id.
- The media checks look at personal media rows of any status.
- Field ids above 9 are ignored.
**Confidence:** High. SME question: "Should an empty example sentence count as missing, and does 'language' require idioma_id, idioma_palabra, or both?"

### RULE-048: Join-code validation and server-side regeneration
**Category:** Validation
**Domain:** Join-Codes
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:143-158`
**Plain English:** On create, the client-proposed code must be:
- present;
- a string of 4 to 6 characters;
- unique across all `dic_aula.codigo`, including soft-deleted rows (dicAula_helper.php:1593-1600).

If it fails, the server generates new 6-character codes until one is valid.
**Specification:**
  Given the client sends codigoAleatorio "AB12CD", which already exists
  When creating
  Then a new random code (e.g. "Q7X2LM") replaces it, and the success message shows the final code.
**Parameters:**
- length 4..6
- regeneration length 6
- intended max retries 5 (`$whileMax`)
- DB column `codigo` varchar(7)
**Edge cases handled:** collision handling
**Suspected defect:**
- `$countWhile` is never incremented, so the 5-retry limit never triggers and the loop is unbounded.
- On edit, `codigo` is taken from the hidden form field with no validation (dicAula_helper.php:63,83), so it can be changed to a duplicate or empty value.
- The uniqueness check is global, not per school year (an older per-year version is commented out).
**Confidence:** High.

### RULE-049: Join-code validation (4–6 characters, unique)
**Category:** Validation
**Domain:** Join-Codes
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:1593-1600`
**Plain English:** A classroom dictionary's join code is required, is a string of 4 to 6 characters, and must be unique across all dictionaries in every school year (whatever their status).
**Specification:**
  Given a dictionary from 2019 (inactive) already uses code "AB12CD"
  When a teacher creates a new dictionary with "AB12CD"
  Then validation fails, and the controller generates a new 6-character code (`DicAulaController.php:145-157`)
**Parameters:** Length between 4 and 6. Column `dic_aula.codigo` is varchar(7).
**Edge cases handled:** None. An earlier per-school-year uniqueness version is commented out (`1579-1590`).
**Suspected defect:** In the controller retry loop, `$countWhile` is never incremented, so the loop has no real bound (controller shard).
**Confidence:** High.

### RULE-050: Join-code lookup only finds active dictionaries
**Category:** Validation
**Domain:** Join-Codes
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:404-411`
**Plain English:** Joining by code finds only a dictionary with exactly that code and status active.
**Specification:**
  Given dic codigo = "XY99" with estado = '0'
  When a student enters "XY99"
  Then no dictionary is found
**Parameters:** `ctes.estados.activo`.
**Edge cases handled:** Takes the first match.
**Confidence:** High.

### RULE-051: Entry text is unique per dictionary, ignoring case
**Category:** Validation
**Domain:** Personal-Dictionary
**Priority:** P0
**Source:** `app/Helpers/dpEntradas_helper.php:113-139`
**Plain English:** A dictionary cannot hold two entries whose text matches when case is ignored. Creating an entry that already exists opens the existing entry instead (controller lines 198-210).
**Specification:**
  Given the user's dictionary contains "Casa"
  When  they try to create "casa"
  Then  no new entry is created; they are redirected to "Casa" with the message "entrada_existe"
**Parameters:** none
**Edge cases handled:** An optional tag filter (`$listaCategorias`) is applied when searching.
**Suspected defect:** Yes, three problems:
  - **SQL injection:** the query is built with `whereRaw('LOWER(\`entrada\`) LIKE "'.mb_strtolower($entrada_entrada).'"')`, so the user's text goes straight into the SQL. This is reachable by any logged-in user through GET `/personal/entrada/insert?entrada_entrada=...`.
  - **Wildcards:** because it uses LIKE, `_` and `%` act as wildcards, so "c_sa" matches "casa" and blocks creating it.
  - **No DB constraint:** there is no unique index on (dic_personal_id, entrada).
**Confidence:** High.

### RULE-052: Bulk publish pre-check and execution
**Category:** Validation
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:586-668`
**Plain English:**
- The pre-check splits the filtered submissions into: already published (same title published), duplicated in the list (a title appearing more than once among the rest; all copies are excluded), and ok.
- The bulk action (DicAulaController.php:564-589) publishes each id with the single-publish rules.
- It returns 400 with per-id errors if any fail.
**Specification:**
  Given candidates "sol"(id1), "sol"(id2), "mar"(id3)
  When the pre-check runs
  Then ok=[mar] and duplicadas=[sol, sol].
  And if ids 1, 2, 3 are posted, id1 publishes, id2 fails as a duplicate, and id3 publishes.
**Parameters:** none
**Edge cases handled:** sequential duplicate detection
**Suspected defect:** The pre-check and execution disagree on duplicates. An empty `enviosId` leaves `$enviosIds` undefined.
**Confidence:** Medium. SME question: "When several students submit the same word, should none, the first, or a teacher-chosen one be published?"
**Also found as:** shard B card "Batch-publish pre-check" (`app/Helpers/dicAula_helper.php:586-668`) — folded in as the same behavior.

### RULE-053: Edit-entry and publish buttons only for coordinators of that dictionary
**Category:** Validation
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `resources/views/layouts/partials/consulta/entradaAula.blade.php:11-25`
**Plain English:** On a classroom entry, the edit and publish icons appear only if the user is a coordinator of that dictionary: a `dic_aula_participantes` row with `rol_diccionario_id = 1`.
**Specification:**
  Given teacher T is a participant of dictionary D with `rol_diccionario_id = 2` (not coordinator)
  When T views an entry of D
  Then no edit or publish icon is shown
**Parameters:** `esCoordinador` = participant with `rol_diccionario_id == ctes.rol.docente (1)` (`DicAulaPolicy.php:114-126`)
**Edge cases handled:** in `diccionario/aula/entrada.blade.php:26-39` the rename icon is gated only by `createDicAula` (any teacher).
**Suspected defect:** Client-only for renaming. `DicAulaController::daEditEntradaGET/POST` (lines 1377-1495) have no `authorize`, so any authenticated user can rename any classroom entry. `publicar` does check `esDocente` + `esCoordinador` (lines 555-562).
**Confidence:** High.

### RULE-054: Renaming a published entry
**Category:** Validation
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:1425-1494`
**Plain English:**
- The new name is required and ≤150 characters.
- It is normalized: trimmed, internal whitespace collapsed.
- It is rejected if another submission in the same dictionary already has that name, case-insensitively but accent-sensitively.
- `updated_by_persona_id` is recorded.
**Specification:**
  Given dic 7 has "Árbol" (id 10)
  When renaming id 11 to "  árbol "
  Then it normalizes to "árbol", matches id 10, and the error "entrada_existe" is returned.
**Parameters:** max 150
**Edge cases handled:** renaming to the same name
**Suspected defect:**
- No authorization on GET or POST (lines 1377-1494).
- The `if ($entrada)` check tests a query builder and is always true.
- `daGetEntradaByEntradaByDiccionario` (dicAula_helper.php:1637-1648) concatenates user input into `whereRaw` LIKE: SQL injection, and `%`/`_` act as wildcards.
- Its utf8_bin collation hardcode may break on utf8mb4.
**Confidence:** High.

### RULE-055: Editing a submitted sense: editable fields and validation limits
**Category:** Validation
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `app/Http/Controllers/EnvioAcepcionController.php:94-126`
**Plain English:** A submitted sense can be edited in the classroom dictionary. Definition is required, up to 1,000 characters; the example sentence is up to 255 characters; category, gender, number, language, language word, definition, example and second example can change; sense order, parent entry and status cannot.
**Specification:**
  Given submitted sense 300 with orden=2
  When it is edited with definicion of 1,001 characters
  Then it is rejected with a validation error
  When it is edited with definicion="Lugar donde se vive", frase_ejemplo of 120 characters, orden=5
  Then the fields are saved and orden stays 2
**Parameters:** ctes.constantes_acepciones.max_definicion=1000, max_frase=255; orden required|integer (validated but ignored)
**Edge cases handled:** ejemplo2 has no length limit. The dictionary's mandatory-field rules (RULE-D10) are NOT checked on teacher edit.
**Confidence:** High.

### RULE-056: A published submission can't be deleted (server check broken)
**Category:** Validation
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `resources/views/layouts/partials/components/botones/entrada/eliminarAula.blade.php:1-22`
**Plain English:** The delete-submission icon is active only when `envios_entradas.estado ≠ 3` (published). Otherwise a disabled trash icon is shown.
**Specification:**
  Given submitted entry E with `estado = 3`
  When the coordinator views the submissions list
  Then the trash icon is disabled
**Parameters:** `ctes.estados_envios`: 0 deleted, 1 sent, 2 deleted by student (unused), 3 published
**Suspected defect:** Client-only. The server guard in `eliminarEnvioEntrada` checks `$envioEntrada->publicada` (`dicAula_helper.php:936`), but no such attribute or column exists, so it's always null. A direct POST hard-deletes a published entry. Also, unpublishing (`despublicarEntrada`) flips `estado` to 2/1 rather than 1, so the UI's published state (`dic_aula_entradas.estado`, used in H33) and this check use different sources.
**Confidence:** High.

### RULE-057: Duplicate-title block on publication
**Category:** Validation
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:672-680`
**Plain English:** An entry cannot be published if another submission with exactly the same title already has an active publication link in the same classroom dictionary.
**Specification:**
  Given "casa" is already published (dic_aula_entradas.estado = 1) in dic 3
  When a teacher publishes another student's "casa" to dic 3
  Then publication is refused with "entrada duplicada" (`714-720`, `784-797`)
**Parameters:** None.
**Edge cases handled:** Matching uses the database collation (likely case- and accent-insensitive in MySQL).
**Suspected defect:** The query also matches the same submission itself, so re-publishing an already-linked one is refused.
**Confidence:** High.

### RULE-058: Guards on disabling a participant
**Category:** Validation
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:1808-1864`
**Plain English:**
- You cannot disable yourself.
- If the target's global role is teacher and they are the dictionary's only dictionary-role teacher, they cannot be disabled.
- Otherwise the participant's estado becomes 0.

Enabling (lines 1777-1804) has no guards.
**Specification:**
  Given dic 7 with exactly one dictionary-role teacher, participant 40
  When another user disables participant 40
  Then status=false with msg 'no_puedes_eliminar_ultimo_docente'.
**Parameters:** `ctes.rol.docente`='1', `ctes.estados.inactivo`='0'
**Edge cases handled:** self-exclusion; last teacher
**Suspected defect:**
- The teacher count includes already-disabled teachers. With one active and one disabled teacher, the last active one can be disabled.
- If the target is a global teacher but no participant has the dictionary-role teacher, `$docentes->first()->id` fails on null.
- On exception, null is returned and the controller then reads `$result->status`.
**Confidence:** High.
**Also found as:** shard B card "Enable / disable a participant (no self, not the last teacher)" (`app/Helpers/dicAula_helper.php:1808-1864`) — folded in as the same behavior.

### RULE-059: No authorization on editing submitted senses or deleting their media
**Category:** Validation
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/EnvioAcepcionController.php:90-136`
**Plain English:** The comments call this a teacher function, but any authenticated user can view or edit any submitted sense in any classroom dictionary and delete its media.
**Specification:**
  Given student S, not a teacher of dictionary D
  When S POSTs /aula/acepcion/300/edit or GETs /aula/acepcion/300/dpmedio/9/delete
  Then the change is applied
**Parameters:** The routes sit under middleware ['auth'] only (routes/web.php:30, 227-231). There is no policy or usuarioEsDocente check in EnvioAcepcionController.
**Edge cases handled:** Media delete is scoped to the sense's own media (EnvioAcepcionController.php:206).
**Suspected defect:** Missing authorization (and an IDOR on acepcion_id). The GET edit form (lines 36-76) is also unprotected.
**Confidence:** High that the code has no check. SME question: "Who may edit a submitted sense: only teachers/owners of that classroom dictionary, or also co-teachers?"

### RULE-060: Saving edits is rejected for inactive dictionaries, but saving is not authorized
**Category:** Validation
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:297-336`
**Plain English:** POST /aula/{id}/edit refuses inactive dictionaries and validates fields. It never checks that the caller is a teacher, coordinator or owner. The update also sets `persona_id` (owner) to the caller (dicAula_helper.php:70).
**Specification:**
  Given a student who participates in nothing, and an active dic 7
  When they POST valid fields to /aula/7/edit
  Then dic 7 is updated and its owner (persona_id) becomes that student.
**Parameters:** none
**Edge cases handled:** inactive dictionary
**Suspected defect:**
- Missing authorization.
- Ownership is transferred on every save.
- Line 302 builds the error message with `__() + __()`, which is numeric addition of two strings. This produces a TypeError or 0 when the dictionary is inactive.
**Confidence:** High.

### RULE-061: Only teachers see the "Create classroom dictionary" action
**Category:** Validation
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `resources/views/diccionario/aula.blade.php:67-100`
**Plain English:** The create button is rendered only inside `@can('createDicAula')`.
**Specification:**
  Given a user with role alumno (role_id 2)
  When they view /aula
  Then no "Crear diccionario de aula" button is rendered
**Parameters:** role ids `ctes.rol.docente = 1` and `alumno = 2` (production values; the config comment says local installs differ)
**Suspected defect:** Client-only for the write path. GET `/aula/create` (`DicAulaController::index`, line 64) authorizes `esDocente`, but POST `/aula/create` (`DicAulaController::create`, lines 119-174) has no `authorize`. A student can POST and create a dictionary. Also, `create()`'s code-retry loop never increments `$countWhile` (lines 148-157), so it can loop forever.
**Confidence:** High.

### RULE-062: Who counts as a coordinator
**Category:** Validation
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:1964-1969`
**Plain English:** A participant is a "coordinator" (teacher) if their global user role id equals `ctes.rol.docente` (1). Their per-dictionary role is not considered.
**Specification:**
  Given a participant whose users.role_id = 1 but rol_diccionario_id = 2 (joined by code)
  When participanteEsCoordinador is checked
  Then true
**Parameters:** `ctes.rol.docente` = '1' (production ids; local seeds differ, see `config/ctes.php:64-66`).
**Edge cases handled:** None.
**Confidence:** Medium — SME: "Is 'teacher in this dictionary' defined by the global role or by the per-dictionary role?"

### RULE-063: Teacher / admin role helpers
**Category:** Validation
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:1183-1197`
**Plain English:** A user is a teacher if their role is the role named 'docente' (looked up by name), and an administrator if their role name is 'admin'.
**Specification:**
  Given user.role_id = id of role 'docente'
  When usuarioEsDocente runs
  Then true
**Parameters:** Role names 'docente' and 'admin'.
**Edge cases handled:** None.
**Suspected defect:** These helpers use role names, while B14/B30–B32 use hardcoded ids from `ctes.rol`. These give different answers if the ids drift.
**Confidence:** High.

### RULE-064: Validity (vigencia) selection and minimum on edit
**Category:** Validation
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `resources/views/diccionario/crearDicAula.blade.php:135-181`
**Plain English:** Validity is chosen as 1..VIGENCIA_MAX school years, each labelled with its school year. On edit, the selector is locked until the edit icon is clicked, and values below the years already elapsed are disabled. The posted value travels in the hidden `vigencia` input.
**Specification:**
  Given a dictionary created for 2023-24, edited during 2025-26 (`vigenciaMin` = 2025 − 2023 = 2)
  When the teacher opens the selector
  Then option 1 is disabled and options 2..10 are selectable
**Parameters:** `ctes.vigencia_max = env VIGENCIA_MAX (10)`; `vigenciaMin = getAnoIniCurrentCursoEscolar() − ano_ini_curso_escolar` (`DicAulaController.php:262-263`); default 1
**Edge cases handled:** `dic_aula.js:811-818` copies the select value into the hidden field.
**Suspected defect:** Client-only. `DicAulaController::save` (lines 297-334) and `create` don't validate `vigencia` at all (no min/max/integer). `save()` also has **no authorization**, and `daCreateOrUpdateDicAulaByPersonaId` overwrites `persona_id` with the editor (`dicAula_helper.php:70`), so ownership silently moves to whoever saves.
**Confidence:** High. SME: "Must the server reject a validity shorter than the years already elapsed, or longer than 10?"

### RULE-065: Vigencia list filter (off by one compared with B03)
**Category:** Validation
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:1973-1986`
**Plain English:** In listings, a dictionary from a past school year still counts as current if it is active, its vigencia is greater than 1, and vigencia ≥ the number of school years elapsed.
**Specification:**
  Given ano_ini = 2022, vigencia = 3, estado = '1'
  When the connected-dictionaries list is built in school year 2025 (elapsed 3)
  Then it is still listed (3 ≥ 3), although daEsVigente says it is NOT in force (3 > 3 is false)
**Parameters:** Hardcoded threshold `vigencia > 1`.
**Edge cases handled:** vigencia = 1 dictionaries rely only on the current-year match.
**Suspected defect:** Off by one compared with B03: listings show a dictionary for one extra school year beyond the deactivation rule. `daGetDiccionariosConVigencias` (`dicAula_helper.php:1990-2012`) uses yet another test (`vigencia > 0`, elapsed-years comparison commented out).
**Confidence:** Medium — SME: "Does vigencia = N mean the dictionary is usable in N school years in total (creation year included), or for N more years after creation?"

### RULE-066: Atemporal dictionaries in the connected list
**Category:** Validation
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:246-252`
**Plain English:** A dictionary from another school year is listed if it has an atemporal record that is active with `hasta` ≤ today, or any atemporal record whose `hasta` is null.
**Specification:**
  Given an atemporal record with estado = '1' and hasta = 2099-01-01
  When the connected list is built today
  Then it is NOT matched by this clause (hasta < tomorrow fails); with hasta = null it IS matched, even if estado = '0'
**Parameters:** "Tomorrow" is computed as today + 1 day.
**Edge cases handled:** None.
**Suspected defect:**
- The comparison looks inverted: atemporal records whose end date is in the future are excluded, past ones included.
- `orWhereNull('hasta')` is not grouped, so it bypasses the estado = active check (SQL `estado=1 AND hasta<x OR hasta IS NULL`).
- B03 ignores `hasta` entirely.
**Confidence:** Low — SME: "Is `dic_aula_atemporales.hasta` the date the timeless status ends? Should a disabled (estado = 0) atemporal record still make a dictionary visible?"
**Also found as:** shard A card "Atemporal (timeless) dictionary validity" (`app/Helpers/dicAula_helper.php:246-252`) — folded in as the same behavior.

### RULE-067: The school year of a new classroom dictionary is supplied by the browser
**Category:** Validation
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `resources/views/diccionario/crearDicAula.blade.php:598-608`
**Plain English:** The school year comes from a hidden `ano_ini_curso_escolar` input filled at render time: the current school year on create, the stored one on edit. The server only checks `required` and saves it as-is.
**Specification:**
  Given a teacher creating a dictionary in October 2025 (school year 2025-26)
  When the form is posted with `ano_ini_curso_escolar = 2019` (tampered)
  Then the dictionary is stored as 2019-20 (`DicAulaController.php:136`; `dicAula_helper.php:77`)
**Parameters:** `ctes.inicio_curso = env INICIO_CURSO ('30/8/2000')`, used by `getAnoIniCursoEscolar`
**Suspected defect:** client-trusted school year.
**Confidence:** High.

### RULE-068: Reactivation window: start year + 9
**Category:** Validation
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Models/DicAula.php:184-190`
**Plain English:** A dictionary may be reactivated for the current year only if the current school-year start is at most 9 years after its own start year.
**Specification:**
  Given ano_ini_curso_escolar=2017, and the current school year starts in 2026
  When eligibility is checked
  Then true (2026 ≤ 2026). With a 2016 start, false.
**Parameters:** hardcoded 9 (roughly a 10-year limit, matching `VIGENCIA_MAX`=10)
**Edge cases handled:** —
**Confidence:** Low. The method has no callers in `app/` or `resources/`. SME question: "Is there a 10-school-year cap on reactivating a classroom dictionary, and should it come from `VIGENCIA_MAX` instead of a hardcoded 9?"
**Also found as:** shard I card "Reactivation horizon of 10 school years" (`app/Models/DicAula.php:184-190`) — folded in as the same behavior.
**Also found as:** shard A card "Reactivation for the current year is allowed up to start year + 9" (`app/Models/DicAula.php:184-190`) — folded in as the same behavior.

### RULE-069: A dictionary's school year comes from the submitted form
**Category:** Validation
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:131-138`
**Plain English:** `ano_ini_curso_escolar` is required on create and saved as sent. The form fills it from a hidden field: the current school year on create, the original year on edit (crearDicAula.blade.php:603-607). The server does not derive or check it, and edit does not validate it.
**Specification:**
  Given today 2026-10-04
  When a teacher creates a dictionary
  Then ano_ini_curso_escolar = 2026, unless the hidden field is tampered with (e.g. 2030).
**Parameters:** none
**Edge cases handled:** required on create only
**Suspected defect:** The value can be tampered with; the server should compute it.
**Confidence:** High.
**Also found as:** shard A card "Create/edit field validation" (`app/Http/Controllers/DicAulaController.php:131-138`) — folded in as the same behavior.

### RULE-070: Hide/edit sense buttons in the classroom dictionary
**Category:** Validation
**Domain:** Senses-Acepciones
**Priority:** P0
**Source:** `resources/views/layouts/partials/consulta/acepcionAula.blade.php:9-27`
**Plain English:** Any teacher sees the hide/show-sense toggle. Only coordinators of that dictionary also see the edit-sense icon.
**Specification:**
  Given invited coordinator teacher T (not the creator) of dictionary D
  When T clicks the hide-sense icon on an entry of D
  Then the UI offers it, but the server rejects with 403 (`ocultarAcepcion` requires `isOwner` = `dic_aula.persona_id`, `DicAulaController.php:1258-1263`)
**Parameters:** none
**Suspected defect:** (1) There's a visibility mismatch: the button is shown to any teacher, but the server accepts only the creator. (2) Client-only: `EnvioAcepcionController::editAcepcionGet/editAcepcionPost` (lines 36-200) have no authorization, so any logged-in user can edit any submitted sense.
**Confidence:** High.

### RULE-071: Single-entry submission requires the classroom dictionary to accept submissions
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:280-285`
**Plain English:** A student can send one entry to a classroom dictionary only if that dictionary has submissions enabled and its submission start date (if set) is today or earlier.
**Specification:**
  Given classroom dictionary D with envios_habilitados=1 and envios_fecha_ini=2026-10-10, and today is 2026-10-04
  When the student sends entry "casa" to D (POST /personal/{dic}/entrada/{id}/enviar)
  Then nothing is created for D (rolled back) and the user sees the generic "could not send entry X to dictionary D" error
  Given the same D with envios_fecha_ini=NULL or 2026-10-01
  When the same send happens
  Then the submission is created.
**Parameters:** dic_aula.envios_habilitados (1 = enabled); dic_aula.envios_fecha_ini compared with Carbon::today() (aceptaEnvios, dicAula_helper.php:1470-1485)
**Edge cases handled:** null start date means open now. There is no end date or close date check, and no check on the dictionary's own school year here.
**Suspected defect:** The listing query (dicAula_helper.php:298-316) compares envios_fecha_ini with `new DateTime()` (now), but aceptaEnvios compares with today at midnight. If the column holds a time, a dictionary can show as "planned" yet accept submissions.
**Confidence:** High. The logic is explicit.

### RULE-072: Target classroom dictionary membership is not verified server-side
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:269-293`
**Plain English:** The server accepts any classroom dictionary id as a target. It does not check that the student is an active participant, that the dictionary is active, or that it belongs to the current or a valid school year.
**Specification:**
  Given student S is not a participant of classroom dictionary D (id 42), and D has submissions enabled
  When S POSTs listaDiccionariosAula=42 for one of S's own entries
  Then a submission into D is created
**Parameters:** none
**Edge cases handled:** The UI only offers dictionaries from daConectadosByPersonaId: participant estado=1, dictionary estado=1, current year, timeless (atemporal) or still valid. This is a client-side filter only.
**Suspected defect:** Authorization gap. It applies to both paths (EnviosController.php:98-140 too).
**Confidence:** High. SME question: "Should only active participants of a dictionary that is valid this school year be able to submit to it?"

### RULE-073: Only the owner can submit personal entries
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Http/Controllers/EnviosController.php:213-223`
**Plain English:** Sending one entry requires the logged-in person to own the personal dictionary that holds it. Bulk send only ever takes entries from the logged-in person's own dictionary.
**Specification:**
  Given entry 10 belongs to student A's personal dictionary
  When student B POSTs entrada_id=10 to the send endpoint
  Then the policy `isOwner` (persona_id == entry's dictionary persona_id) fails with 403
  Given bulk send with entradasSelecionadas="10, 11" where 10 is B's entry and 11 is A's
  When B submits
  Then only entry 10 is sent: entries are filtered by dic_personal_id = B's dictionary (EnviosController.php:133-137)
**Parameters:** policy DiccionarioPersonalPolicy::isOwner
**Edge cases handled:** Entry not found gives a "bad URL" error. The {diccionario_id} route parameter is ignored; the session persona's dictionary is used.
**Confidence:** High.

### RULE-074: Bulk / selected-entries submission bypasses the acceptance-window check
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:66-94`
**Plain English:** When a student sends a selection of entries from the "send dictionary" screen, the code never checks whether the target dictionary accepts submissions.
**Specification:**
  Given classroom dictionary D with envios_habilitados=0
  When a POST to /personal/{dic}/enviar carries listaDiccionariosAula=D.id and entradasSelecionadas="5, 7"
  Then a dp_envios row and envios_entradas copies are still created for D (the screen only greys D out)
**Parameters:** none
**Edge cases handled:** none
**Suspected defect:** This is inconsistent with RULE-D01. The single-entry path checks `aceptaEnvios`; this path (`dpEnvio_EnviarEntradasADicAula`, called from EnviosController.php:140) does not.
**Confidence:** High that the check is missing. SME question: "Must the 'submissions enabled / start date' window apply to bulk sends too?"

### RULE-075: Submission-window state filter (inactive / planned / active)
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:278-324`
**Plain English:** Connected dictionaries can be filtered by submission state:
- **inactive:** submissions disabled
- **planned:** enabled, but the start date is in the future
- **active (default):** enabled, and the start date is null or already reached
- **null:** no filter
**Specification:**
  Given dic.envios_habilitados = '1', envios_fecha_ini = 2024-05-10 10:00 and now = 2024-05-09
  When filtered with estado_envio_habilitado = '2' (planned)
  Then it is returned; with '1' (active) it is not
**Parameters:** `ctes.estado_envio_habilitado`: 0 = inactivo, 1 = activo, 2 = planificado.
**Edge cases handled:** Unknown values fall through to "active".
**Suspected defect:** `DicAula` model lines 91-94 map "future start" to activo, which contradicts this helper. That belongs to another shard.
**Confidence:** High.

### RULE-076: Pre-check requires at least one visible sense and reports field errors per sense
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:616-645`
**Plain English:** Before sending, an entry is checked. With no visible senses it fails with FALTA_ACEPCION; otherwise each failing sense is reported (keyed by its order) with FALTAN_CAMPOS; if nothing fails the result is "ok".
**Specification:**
  Given entry "sol" with 0 visible senses
  When it is pre-checked against D (POST /aula/{id}/comprobarEntradas via DicAulaController.php:1304)
  Then the result is [{tipo: FALTA_ACEPCION}]
**Parameters:** none
**Edge cases handled:** The check stops after the first error type (no senses).
**Suspected defect:** The actual send (RULE-D09/D14) does not enforce "at least one sense". A direct POST can submit an entry with no senses.
**Confidence:** High. SME question: "Must the server reject entries with zero visible senses at send time?"

### RULE-077: Single-entry send only when the dictionary accepts submissions
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `resources/views/layouts/partials/components/modal-enviarEntradaUnDiccionario.blade.php:8-53`
**Plain English:** With a single target dictionary, the confirm button appears only if `aceptaEnvios`: submissions enabled and start date ≤ today. Otherwise the modal shows "envíos deshabilitados" with only Back. The date of the last submission is shown.
**Specification:**
  Given D with submissions enabled from 2025-11-01, today 2025-10-20
  When the student opens send for entry "casa"
  Then the message reads "\"D\": Diccionario con envíos deshabilitados" and no Accept button is shown
**Parameters:** `envios_habilitados`, `envios_fecha_ini`
**Edge cases handled:** the server enforces the same check (`dpEnvios_helper.php:668-670`).
**Confidence:** High.

### RULE-078: Any sense missing a required field aborts the send
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:375-385`
**Plain English:** While copying, if any visible sense of the entry fails RULE-D10, the whole send is aborted with a FALTAN_CAMPOS error that lists the missing field names.
**Specification:**
  Given entry "casa" whose 2nd visible sense lacks a mandatory image
  When it is sent to D
  Then nothing is saved for D, and the user sees "could not send ... missing fields:" followed by the list of field names
**Parameters:** error prefix "FALTAN_CAMPOS" (the controller parses the first 13 characters; EnviosController.php:144-158, 230-242)
**Edge cases handled:** The `&& $camposOk` on line 387 is always true (it tests an object). This is harmless because the failure has already thrown.
**Confidence:** High.

### RULE-079: Allowed extensions and size limit are checked in the browser only
**Category:** Validation
**Domain:** Uploads-Media
**Priority:** P0
**Source:** `config/ctes.php:113-117`
**Plain English:** Images may be jpg/jpeg/jpe/gif/png/bmp/tif/tiff/ico, audio mp3/ogg/wav, video mp4/ogg/webm, each up to UPLOAD_MAXSIZE (default 200M). Only the file-picker widget enforces this. The server stores any valid upload.
**Specification:**
  Given the sense form's file picker has accept/data-allowed-file-extensions built from this list, and data-max-file-size = "200M"
  When a user picks "x.exe" in the browser
  Then the widget rejects it. A crafted POST with `medio_imagen=x.php` is stored by `dpMedio_SaveMedio` without any check on type, extension or size.
**Parameters:** `ctes.extensiones_medios.imagen`="jpg jpeg jpe gif png bmp tif tiff ico"; `.audio`="mp3 ogg wav"; `.video`="mp4 ogg webm"; `ctes.dropify.*` = env `UPLOAD_MAXSIZE` default "200M" (`config/ctes.php:318-321`). The UI text says about 5 minutes of recording (`dpAcepcionFormulario.blade.php:304-305`).
**Edge cases handled:** None on the server. The sense validators (`DiccionarioPersonalController.php:491-499`, `627-633`) have no `mimes`/`max` rule for the files.
**Suspected defect:** There is no server-side whitelist. Files go to the publicly served `storage/app/public` tree (G04) with an extension taken from `$file->extension()`, which is guessed from the MIME type.
**Confidence:** High. SME question: "Must the server reject types outside this list and files over 200 MB, or is the browser check accepted as the policy?"

### RULE-080: Media upload type and size limits are client-only
**Category:** Validation
**Domain:** Uploads-Media
**Priority:** P0
**Source:** `resources/views/layouts/partials/dpAcepcion/dpAcepcionFormulario.blade.php:140-248`
**Plain English:** One image, one audio and one video per sense. Allowed extensions and maximum size are enforced by the dropify widget / `accept` attribute only. The classroom edit form has the same (`diccionario/aula/acepcionEdit.blade.php:100-196`).
**Specification:**
  Given a student uploads `virus.exe` renamed `foto.php`
  When posting the sense form directly
  Then the server stores it (it checks only `$file->isValid()`, `DiccionarioPersonalController.php:566-568,683-685`; `EnvioAcepcionController.php:142-144`)
**Parameters:** `ctes.extensiones_medios`: image "jpg jpeg jpe gif png bmp tif tiff ico", audio "mp3 ogg wav", video "mp4 ogg webm"; `ctes.dropify.*` = `env UPLOAD_MAXSIZE ('200M')`
**Suspected defect:** no server-side `mimes`/`max` rules.
**Confidence:** High.

### RULE-081: TinyMCE upload has no validation
**Category:** Validation
**Domain:** Uploads-Media
**Priority:** P0
**Source:** `app/Http/Controllers/HomeController.php:55-59`
**Plain English:** Any authenticated user can upload any file. It is stored under the public disk path `tinyUploads/` with the client's original filename and served publicly.
**Specification:**
  Given a user uploads "x.html"
  When `POST /upload` runs
  Then it is stored at `storage/tinyUploads/x.html` and its public URL is returned. An existing file with the same name is overwritten.
**Parameters:** `ctes.path_medios.tinyUploads='tinyUploads'`.
**Edge cases handled:** none.
**Suspected defect:** there is no MIME, size or extension check; uploads can overwrite each other; hosting HTML/SVG files enables stored XSS.
**Confidence:** High.

### RULE-082: Deletion eligibility (advisory only)
**Category:** Validation
**Domain:** Classroom-Dictionary
**Priority:** P1
**Source:** `app/Http/Controllers/DicAulaController.php:1610-1626`
**Plain English:** The UI asks the server whether a dictionary can be deleted (caller must be a global teacher). It cannot be deleted if:
- it has any visible published entries → 'publicadas';
- it has any submissions (dicAula_helper.php:1546-1563) → 'envios'.

Otherwise it can be deleted; the participant count is reported when > 1, since the creator counts as one.
**Specification:**
  Given dic 7 with 0 published, 0 submissions, 3 participants
  When the client calls testdelete
  Then {borrable:true, participantes:3}.
**Parameters:** participant threshold 1
**Edge cases handled:** none
**Suspected defect:** The real delete (A08) ignores this. The language file has a "delete although it has submissions" prompt, which contradicts 'envios' = not deletable.
**Confidence:** High.

### RULE-083: Dictionaries a person was disabled in / inactive dictionaries
**Category:** Validation
**Domain:** Classroom-Dictionary
**Priority:** P1
**Source:** `app/Helpers/dicAula_helper.php:357-371`
**Plain English:** This lists active dictionaries in which the person's participation is disabled. Its companion (`2075-2088`) lists inactive (expired) dictionaries in which the person is still an active participant.
**Specification:**
  Given persona 7 has a participant row estado '0' in active dic A
  When the first function is called
  Then A is returned
**Parameters:** `ctes.estados`.
**Edge cases handled:** None.
**Confidence:** High.

### RULE-084: "Is joined" check ignores participant status
**Category:** Validation
**Domain:** Classroom-Dictionary
**Priority:** P1
**Source:** `app/Helpers/dicAula_helper.php:515-522`
**Plain English:** A person counts as joined if any participant row exists for them in that dictionary, including disabled ones.
**Specification:**
  Given participant row estado '0'
  When daDiccionarioAulaComprobarUsuario is called
  Then true
**Parameters:** None.
**Edge cases handled:** None.
**Confidence:** High.

### RULE-085: Comment visibility mode and cut-off date
**Category:** Validation
**Domain:** Comments-Notifications
**Priority:** P1
**Source:** `resources/views/diccionario/crearDicAula.blade.php:438-470`
**Plain English:** Comment visibility for students is 0 not visible, 1 visible, or 2 "only those before date X". The date input is enabled only for mode 2 (`dic_aula.js:102-107`).
**Specification:**
  Given mode 2 with date 2025-12-01
  When saving on 2025-11-15
  Then the server rejects it (`comentariosVisibleFecha` must be ≤ today; required when mode 2, `DicAulaController.php:135,313`)
**Parameters:** `ctes.comentarios_visibles` 0/1/2. The view uses the `estado_envio_habilitado` constants for the 0/1 option values (same numbers, wrong semantic constant).
**Edge cases handled:** the server enforces both `required_if` and `before_or_equal:today`.
**Confidence:** High.

### RULE-086: Generic confirm modal renders caller-supplied text without escaping
**Category:** Validation
**Domain:** Comments-Notifications
**Priority:** P1
**Source:** `app/Http/Controllers/ModalAjaxController.php:35-54`
**Plain English:** The confirm/cancel modal shows whatever title, message and action URL the request supplies. The message is rendered unescaped (`modal-AceptarCancelar.blade.php:8`), and "Accept" navigates to the supplied action.
**Specification:**
  Given a POST with message `"<b>x</b>"` and action "/aula/12/delete"
  When the modal is requested
  Then the HTML renders bold text, and Accept goes to `/aula/12/delete`.
**Parameters:** none.
**Edge cases handled:** CSRF-protected POST.
**Suspected defect:** reflected HTML and open navigation.
**Confidence:** High.

### RULE-087: Pre-submission requirements check
**Category:** Validation
**Domain:** Fields-Config
**Priority:** P1
**Source:** `app/Http/Controllers/DicAulaController.php:1296-1308`
**Plain English:** For each selected personal entry, a status is returned per entry against the target classroom dictionary (dpEnvios_helper.php:616-645):
- no senses → `FALTA_ACEPCION`;
- a sense missing required fields → `FALTAN_CAMPOS`, keyed by sense order;
- otherwise 'ok'.
**Specification:**
  Given personal entry 12 with 0 senses
  When it is checked against dic 7
  Then status[12] = [{tipo: FALTA_ACEPCION}].
**Parameters:** none
**Edge cases handled:** none
**Suspected defect:** No ownership check on the entries or the dictionary (IDOR on entry metadata).
**Confidence:** High.

### RULE-088: Every level is allowed with every subject and every stage
**Category:** Validation
**Domain:** Master-Data
**Priority:** P1
**Source:** `database/seeders/MasterTablesDataSeeder.php:345-369`
**Plain English:** The "allowed combinations" tables are filled with the full cross product, so they impose no curricular restriction.
**Specification:**
  Given 19 levels, about 93 subjects and 4 stages
  When the seeder runs
  Then about 1,767 level×subject rows and 76 stage×level rows exist (for example "1º Primaria" × "Griego" is allowed)
**Parameters:** none.
**Edge cases handled:** None.
**Confidence:** High — SME: "Should level/subject/stage combinations actually be restricted?"

### RULE-089: Rename dictionary and change avatar
**Category:** Validation
**Domain:** Personal-Dictionary
**Priority:** P1
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:1121-1136`
**Plain English:** A non-empty `nombre_diccionario` replaces the current user's dictionary title. A non-empty `avatar_nuevo` replaces their avatar in the database and in the session.
**Specification:**
  Given the user's dictionary is titled "Mi diccionario personal"
  When  they POST nombre_diccionario = "Palabras de 3ºB"
  Then  dic_personal.titulo = "Palabras de 3ºB"
**Parameters:** Avatars offered are only `*.svg` files in `storage/app/public/avatares/oficiales` (lines 1065-1077).
**Edge cases handled:** Empty values are ignored. The target dictionary always comes from the session, not from the `{diccionario_id}` in the URL.
**Suspected defect:** There is no validation (marked TODO at line 1111). Titles over 150 characters (the DB limit) cause a DB error. `avatar_nuevo` is not checked against the .svg list, so any string is stored as avatar_URL. `formBackUrl` is an open redirect.
**Confidence:** High.

### RULE-090: Renaming an entry cannot clash with another entry
**Category:** Validation
**Domain:** Personal-Dictionary
**Priority:** P1
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:401-415`
**Plain English:** The new text is normalised. If another entry (a different id) in the same dictionary already has that text (case ignored), the rename is rejected. Renaming an entry to itself is allowed.
**Specification:**
  Given entries "Casa" (id 1) and "Perro" (id 2)
  When  the user renames entry 2 to "casa"
  Then  the error "entrada_existe" is shown and nothing changes
**Parameters:** none
**Edge cases handled:** Renaming to a different capitalisation of itself is allowed.
**Suspected defect:** Renaming does not check submission or publication state, so an entry already sent or published can be renamed. Whether that should be allowed is an SME question.
**Confidence:** High.

### RULE-091: Entry text is normalised (trim, collapse spaces, keep case)
**Category:** Validation
**Domain:** Personal-Dictionary
**Priority:** P1
**Source:** `app/Helpers/global_helper.php:1229-1240`
**Plain English:** Before an entry is looked up, created or renamed, leading and trailing spaces are removed and any run of whitespace becomes a single space. Letter case is not changed.
**Specification:**
  Given the input "  Casa   grande "
  When  an entry is created or searched
  Then  the stored or searched text is "Casa grande"
**Parameters:** none
**Edge cases handled:** Only the space character " " is trimmed at the ends; tabs and newlines inside the text are collapsed. Lowercasing and capitalising the first letter are commented out.
**Confidence:** High.
**Also found as:** shard B card "Entry-title normalisation" (`app/Helpers/global_helper.php:1229-1240`) — folded in as the same behavior.

### RULE-092: Entry text is required, at most 150 characters
**Category:** Validation
**Domain:** Personal-Dictionary
**Priority:** P1
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:177-188`
**Plain English:** Creating, renaming or saving a sense requires entry text of at most 150 characters. The same limit applies to search terms, which are optional.
**Specification:**
  Given entrada_entrada of 151 characters
  When  the user creates the entry
  Then  they are sent back to the form with a validation error
**Parameters:** `ctes.constantes_entradas.max_entrada` = 150 (matches DB column `dp_entradas.entrada` varchar(150))
**Edge cases handled:** The length check runs before normalisation.
**Confidence:** High.

### RULE-093: Field length limits
**Category:** Validation
**Domain:** Personal-Dictionary
**Priority:** P1
**Source:** `database/migrations/2020_04_19_113507_create_initial_structure.php:140-162`
**Plain English:** Headwords are at most 150 characters, definitions at most 1,000, example sentences at most 255, and words in other languages at most 150. A definition is mandatory.
**Specification:**
  Given a definition of 1,001 characters
  When saved
  Then it is rejected (app constants `config/ctes.php:128-144`, or the DB limit)
**Parameters:** max_entrada 150, max_definicion 1000, max_frase 255. ejemplo2 is 255.
**Edge cases handled:** None.
**Confidence:** High.

### RULE-094: Entry-title uniqueness lookup (case-insensitive, accent-sensitive)
**Category:** Validation
**Domain:** Publication-Moderation
**Priority:** P1
**Source:** `app/Helpers/dicAula_helper.php:1637-1648`
**Plain English:** When renaming a classroom entry, it finds another entry in the same dictionary whose lowercased title matches with binary collation. "Árbol" and "árbol" match; "arbol" and "árbol" do not. The rename is then blocked if the match is a different entry (`DicAulaController.php:1455-1463`).
**Specification:**
  Given dic 3 has "Árbol"
  When renaming another entry to "árbol"
  Then rename refused "entrada_existe"
**Parameters:** None.
**Edge cases handled:** None.
**Suspected defect:**
- The title is concatenated into raw SQL (SQL injection, and breaks on `"` or `%`).
- This is inconsistent with B19, which uses default collation.
**Confidence:** Medium — SME: "Should 'arbol' and 'árbol' be different entries?"

### RULE-095: Vigencia bounds are enforced in the UI only
**Category:** Validation
**Domain:** SchoolYear-Vigencia
**Priority:** P1
**Source:** `app/Http/Controllers/DicAulaController.php:263-266`
**Plain English:** The vigencia selector offers 1 to `vigencia_max` (10). On edit, values below the years already elapsed are disabled. The server does not validate `vigencia` at all.
**Specification:**
  Given dic start 2023, current 2026 → vigenciaMin 3
  When editing
  Then options 1-2 are disabled in the UI, but a POST with vigencia=1 is accepted.
**Parameters:** `ctes.vigencia_max` env VIGENCIA_MAX default '10'; default vigencia 1
**Edge cases handled:** UI only
**Suspected defect:** No server-side range check.
**Confidence:** High.

### RULE-096: Sense field validation
**Category:** Validation
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:491-499`
**Plain English:** A sense needs a definition of at most 1000 characters. The example sentence (`frase_ejemplo`) is optional, at most 255 characters. `orden` must be an integer. Nothing else is required.
**Specification:**
  Given definicion = "" (empty)
  When  the user saves the sense
  Then  it is rejected with the message "campo_definicion". AJAX/recording requests (`grabacion`) get JSON with the error instead.
**Parameters:**
  - `ctes.constantes_acepciones.max_definicion` = 1000
  - `ctes.constantes_acepciones.max_frase` = 255
**Edge cases handled:** Edit uses the same rules (lines 627-634) but without `entrada_entrada`.
**Suspected defect:** `ejemplo2` (second example, DB varchar(255)), `idioma_palabra` (varchar(150)) and the category/gender/number/language ids are not validated, so values that are too long or invalid ids reach the DB.
**Confidence:** High.

### RULE-097: Sense text limits
**Category:** Validation
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `resources/views/layouts/partials/dpAcepcion/acepcionFormFieldComun.blade.php:80-101`
**Plain English:** Definition is required and at most 1,000 characters. "Ejemplo de uso" (`ejemplo2`) and "más datos" (`frase_ejemplo`) are at most 255 each. The translation word has no client limit.
**Specification:**
  Given a definition of 1,001 characters
  When the form is submitted
  Then the browser blocks it (maxlength), and the server would also reject it
**Parameters:** `ctes.constantes_acepciones.max_definicion = 1000`, `max_frase = 255`; DB `ejemplo2 varchar(255)`, `idioma_palabra varchar(150)`
**Suspected defect:** The server validates `definicion` and `frase_ejemplo` only (`DiccionarioPersonalController.php:493-494,628-629`; `EnvioAcepcionController.php:95-96`). `ejemplo2` (255) and `idioma_palabra` (150) are unvalidated server-side, so overlong values cause a DB error or truncation.
**Confidence:** High.

### RULE-098: The dictionary picker's grey/green rule disagrees with the server (suspected defect)
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P1
**Source:** `resources/views/layouts/partials/components/listaDicAula-customRadio.blade.php:19-55`
**Plain English:** In the whole-dictionary send picker, a dictionary is greyed (unselectable) when `DicAula::enviosHabilitados()` returns 0. For students, dictionaries not visible to students are hidden. `enviosHabilitados()` (`app/Models/DicAula.php:79-98`) ignores the start date when the mode is 1 and treats "scheduled" (2) as active only when the start date is in the **future**.
**Specification:**
  Given D with `envios_habilitados = 1` and start date 2025-11-01, today 2025-10-20
  When the student opens the picker
  Then D is shown green and selectable, but the server rejects the send (`aceptaEnvios`)
**Parameters:** config key `ctes.visibilidad.novisible` (doesn't exist; the correct key is `no_visible`)
**Suspected defect:** (1) There are two inconsistent "accepts submissions" rules. (2) The hide-non-visible check compares to `null`, so it works only if `visible_estudiante` loads as int 0.
**Confidence:** Medium. SME: "Is a scheduled dictionary open from the start date onward (yes, per `aceptaEnvios`), and should the picker match that?"

### RULE-099: Submission search filters (limit 200)
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P1
**Source:** `app/Helpers/dicAula_helper.php:1679-1760`
**Plain English:** The teacher's submission search can filter by:
- student
- title substring
- send date from 00:00 of `desde` to 23:59:59 of `hasta`
- first letter
- status ('0' means all)

Results are sorted by title then newest first, capped at 200.
**Specification:**
  Given desde = 2024-03-01, hasta = 2024-03-31, estado = '3'
  When searched
  Then published submissions sent in March 2024 are returned, at most 200
**Parameters:** Limit 200. The estado value '0' means "no filter", so status 0 (logically deleted) cannot be searched.
**Edge cases handled:** Inclusive day bounds.
**Confidence:** High.
**Also found as:** shard A card "Submission search filters (moderation list)" (`app/Helpers/dicAula_helper.php:1679-1760`) — folded in as the same behavior.

### RULE-100: A hidden personal entry can't be sent
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P1
**Source:** `resources/views/layouts/partials/components/botones/entrada/enviar.blade.php:1-27`
**Plain English:** On a hidden entry (`estado = 2`), the send icon opens an explanatory alert instead of the send dialog.
**Specification:**
  Given personal entry "casa" with `estado = 2`
  When the student clicks send
  Then the alert `modal_enviar_entrada_oculta_body` appears
**Parameters:** `ctes.estados_entrada.oculta = '2'`
**Edge cases handled:** the server also copies only visible entries (`dpEnvios_helper.php:680-684`). Note that it still creates an empty `envios` row.
**Confidence:** High.

### RULE-101: Media types and accepted extensions
**Category:** Validation
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `config/ctes.php:82-117`
**Plain English:** A sense can carry images (1), audio (2) or video (3). Each type has an extension allow-list and a storage folder.
**Specification:**
  Given an upload "clip.mov" as video
  When validated
  Then it is rejected (allowed: mp4, ogg, webm); "foto.png" is accepted as image
**Parameters:**
- image: jpg jpeg jpe gif png bmp tif tiff ico
- audio: mp3 ogg wav
- video: mp4 ogg webm
- folders: `dp/medios/{imagenes,audios,videos}`
- UPLOAD_MAXSIZE default 200M
- thumbnail width 250, video thumbnail suffix `_thumb`
**Edge cases handled:** None.
**Confidence:** High.

### RULE-102: Allowed upload types per media kind
**Category:** Validation
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `app/Helpers/global_helper.php:514-542`
**Plain English:** The file picker accepts, per media type:
- image: jpg jpeg jpe gif png bmp tif tiff ico
- audio: mp3 ogg wav
- video: mp4 ogg webm
**Specification:**
  Given tipo_medio = 2 (audio)
  When the accept string is built
  Then "audio/mp3,audio/ogg,audio/wav"
**Parameters:**
- `ctes.tipos_medios`: 1 imagen, 2 audio, 3 video
- `ctes.extensiones_medios` (`config/ctes.php:113-117`)
- Max upload size `UPLOAD_MAXSIZE`, default '200M' (`config/ctes.php:317-322`)
**Edge cases handled:** None. This is client-side only.
**Confidence:** High.

### RULE-103: The recorded-media size check never fires (suspected defect)
**Category:** Validation
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `resources/views/layouts/partials/dpAcepcion/dpAcepcionFormulario.blade.php:1027-1034`
**Plain English:** In-browser audio/video recordings are supposed to be rejected above the maximum size (the UI says about 5 minutes). The code compares megabytes (a number) with the string "200M", which is always false.
**Specification:**
  Given a 350 MB recording
  When "Guardar" is clicked
  Then it is accepted, contrary to the stated limit
**Parameters:** `UPLOAD_MAXSIZE` (200M); also it checks `> audioMax || > videoMax` regardless of media type
**Suspected defect:** yes.
**Confidence:** High.

### RULE-104: Upload size limit
**Category:** Validation
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `config/ctes.php:317-322`
**Plain English:** The client-side upload widgets (Dropify) for image, audio and video limit files to `UPLOAD_MAXSIZE`, default 200M.
**Specification:**
  Given UPLOAD_MAXSIZE unset
  When  a user picks a 250 MB video
  Then  the widget rejects it (client side only)
**Parameters:** `UPLOAD_MAXSIZE`='200M'. Allowed extensions (config/ctes.php:113-117): image `jpg jpeg jpe gif png bmp tif tiff ico`; audio `mp3 ogg wav`; video `mp4 ogg webm`.
**Edge cases handled:** the server side depends on PHP `upload_max_filesize` and post_max_size.
**Confidence:** High.

### RULE-105: Search by initial, text and theme tag
**Category:** Validation
**Domain:** Classroom-Dictionary
**Priority:** P2
**Source:** `app/Helpers/dicAula_helper.php:1271-1290`
**Plain English:**
- Text search matches titles containing the text, optionally limited to entries with at least one sense tagged with a selected theme.
- Search by initial (`1247-1256`) uses prefix match.
- Theme-only search uses `1303-1321`.
**Specification:**
  Given the query "sol" and themes [42]
  When searched
  Then entries whose title contains "sol" and which have a sense with theme 42 are returned, 10 per page
**Parameters:** scrollEntradas = 10.
**Edge cases handled:** None.
**Confidence:** High.

### RULE-106: Classroom search input limits
**Category:** Validation
**Domain:** Classroom-Dictionary
**Priority:** P2
**Source:** `app/Http/Controllers/DicAulaController.php:1059-1100`
**Plain English:**
- Search term ≤150 characters; up to 10 integer tag (temática) ids.
- At least a term or tags is required; otherwise the error "buscador_sindatos".
- Results are paged 10 at a time.
**Specification:**
  Given empty term and no tags
  When searching
  Then an error is shown.
**Parameters:** `ctes.constantes_entradas.max_entrada`=150, `ctes.tematicas_max`=10, `scrollEntradas`=10
**Edge cases handled:** term-only / tags-only / both routes
**Confidence:** High.

### RULE-107: Comment dialog served for any submitted entry with no authorization
**Category:** Validation
**Domain:** Comments-Notifications
**Priority:** P2
**Source:** `app/Http/Controllers/EnviosController.php:525-544`
**Plain English:** Any authenticated user can load the "comment on entry" form for any submitted entry id, which reveals the entry and its classroom dictionary.
**Specification:**
  Given envios_entradas id 800 in dictionary D
  When any logged-in user GETs /personal/ajax/getComentarEntradaAjax/800
  Then the form HTML with the entry and dictionary data is returned
**Parameters:** none
**Edge cases handled:** An unknown id returns false.
**Confidence:** High. SME question: "Who may comment on a submitted entry? Only teachers of D?"

### RULE-108: "Only tagged" export requires at least one theme
**Category:** Validation
**Domain:** Exports
**Priority:** P2
**Source:** `app/Http/Controllers/PDFController.php:46-52`
**Plain English:** If the user asks to export only tagged senses but selects no theme, they go back to the options page with an error. The classroom PDF has the same rule at lines 89-93.
**Specification:**
  Given exportOnlyTagged=1 and listaTematicas empty
  When the PDF is requested
  Then redirect to the options page with message key `diccionario.exportarpdf__no_tematicas_seleccionadas`
**Parameters:** —
**Edge cases handled:** —
**Confidence:** High

### RULE-109: A search needs a word or at least one tag
**Category:** Validation
**Domain:** Personal-Dictionary
**Priority:** P2
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:290-306`
**Plain English:** If both the word and the tags are empty, the search is rejected. Otherwise it redirects to the word-only, tag-only or combined search.
**Specification:**
  Given entrada_entrada = "" and tematicas_ids = ""
  When  the user submits the search
  Then  they are sent back with the error "buscador_sindatos"
**Parameters:** none
**Edge cases handled:** none
**Suspected defect:** Validation and the existence check use `list_tematica_id`, but routing uses `tematicas_ids`, so the two can disagree.
**Confidence:** Medium — SME: is the tag filter supposed to be one field (`tematicas_ids`, comma-separated)?

### RULE-110: Maximum 10 theme tags in search
**Category:** Validation
**Domain:** Personal-Dictionary
**Priority:** P2
**Source:** `resources/js/searchAutocompletion.js:136-153`
**Plain English:** Search can filter by at most `tematicas_max` theme tags. Selecting one more shows an alert.
**Specification:**
  Given 10 tags selected
  When the user picks an 11th
  Then it's refused with "solo está permitido seleccionar un máximo de 10 etiquetas"
**Parameters:** `ctes.tematicas_max = env TEMATICAS_MAX (10)`, exposed in `layouts/app.blade.php:17`
**Suspected defect:** Enforced server-side for classroom search (`DicAulaController.php:968,1065`) but not personal search (`DiccionarioPersonalController.php:1450-1456`).
**Confidence:** High.

### RULE-111: Entry headword maximum length
**Category:** Validation
**Domain:** Personal-Dictionary
**Priority:** P2
**Source:** `resources/views/layouts/partials/dpEntrada/dpEntradaEdit.blade.php:24`
**Plain English:** Headword input is limited to 150 characters. The classroom rename uses the same limit (`diccionario/aula/daEntradaEdit.blade.php:42`).
**Specification:**
  Given a 151-character headword
  When saving
  Then it's rejected (the server also validates `max:150`)
**Parameters:** `ctes.constantes_entradas.max_entrada = 150`
**Confidence:** High.

### RULE-112: "Send entries" on an empty personal dictionary
**Category:** Validation
**Domain:** Submissions-Envios
**Priority:** P2
**Source:** `resources/views/layouts/partials/components/actionbuttonPersonal.blade.php:41-63`
**Plain English:** If the personal dictionary has no entries, "Enviar entradas" only shows an info modal. Otherwise it opens the dictionary picker.
**Specification:**
  Given a student with 0 personal entries
  When they click "Enviar entradas"
  Then the modal `modal_enviar_diccionario_noentradas` is shown
**Parameters:** none
**Confidence:** High.


## Lifecycle

### RULE-113: CAS callback flow
**Category:** Lifecycle
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Http/Controllers/Auth/CasController.php:28-41`
**Plain English:** After CAS succeeds, the app checks the user against CAUCE (creating or updating their records). If that passes, it loads the local user whose `name` equals the CAS login ID, logs them in, builds the session data and sends them to their personal dictionary. Any failure gives HTTP 403.
**Specification:**
  Given CAS returns login ID "U1" and CAUCE returns no error
  When  GET /cas/callback
  Then  persona, user and centres are provisioned; `users.name='U1'` is logged in; redirect to `diccionariopersonal.get`
  Given CAUCE returns a non-empty MensajeError
  Then  HTTP 403
**Parameters:** route `/cas/callback` (routes/web.php:400), default callback URL `/cas/callback` (app/Cas/CasManager.php:202)
**Edge cases handled:** user not found by name → 404 (`firstOrFail`).
**Suspected defect:**
- If the persona was already linked to a user with a *different* `name` (e.g. the CAS ID changed), provisioning reuses that user. The lookup by the new CAS ID then fails with 404.
- The callback route has no `cas.auth` middleware. Calling it without a valid CAS session passes a null login ID to CAUCE.
- The return value of `setUserDataSession()` is ignored (see F18).
**Confidence:** High.

### RULE-114: Multiple centres are created on demand and linked
**Category:** Lifecycle
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:970-1010`
**Plain English:** When CAUCE sends several centre records, each centre is found or created by code (new ones are active and named from CAUCE). All their IDs are collected for the user.
**Specification:**
  Given CAUCE returns InfoCentro [{Codigo:"38000001",Nombre:"IES A",Rol:1},{Codigo:"35000002",Nombre:"CEIP B",Rol:3}]
  When  the user logs in
  Then  both centres exist (created if missing with estado=1) and centros_ids=[idA,idB]
**Parameters:** detection rule: `InfoCentro.Codigo` is not set and the first element is a non-empty array
**Edge cases handled:**
- An entry with no code or name uses the placeholder values.
- One centre failing to save is skipped (logged).
- Existing centres keep their stored name (CAUCE's name is not updated).
**Confidence:** High.

### RULE-115: Centre membership replaced on every login
**Category:** Lifecycle
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:1085-1111`
**Plain English:** On every login the user's centre memberships are replaced by exactly the centres CAUCE currently reports. Centres no longer reported are removed. A failure here is only logged and login still succeeds.
**Specification:**
  Given user linked to centres {A,B} and CAUCE now reports only {B}
  When  the user logs in
  Then  `users_centros` for the user = {B}
**Parameters:** pivot `users_centros`
**Edge cases handled:**
- A sync error is swallowed. The outer transaction still commits (global_helper.php:918), so login proceeds with stale or empty centres.
**Confidence:** High.
**SME question:** Should losing a centre also remove the user from that centre's classroom dictionaries? Today only `users_centros` changes.

### RULE-116: Persona identity fields are overwritten on every login
**Category:** Lifecycle
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:794-810`
**Plain English:** On every login the persona's CIAL, NIF/NIE, passport, first name and surnames are replaced with CAUCE's values. Empty values become NULL. The save runs inside a DB transaction.
**Specification:**
  Given persona has pasaporte=<masked> and CAUCE now sends an empty Pasaporte
  When  the user logs in
  Then  `personas.pasaporte` is set to NULL
**Parameters:** columns `cial`, `NIF_NIE`, `pasaporte`, `nombre`, `apellidos`
**Edge cases handled:** a save failure rolls back and denies login.
**Confidence:** High.

### RULE-117: User–persona link
**Category:** Lifecycle
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:891-895`
**Plain English:** When the persona has no linked user, the new or found user is linked to it in `users_personas` (find or create).
**Specification:**
  Given persona P1 with no `users_personas` row and user U1
  When  provisioning runs
  Then  row (user_id=U1.id, persona_id=P1.id) exists
**Parameters:** none
**Edge cases handled:** a failure rolls back and denies login.
**Confidence:** High.

### RULE-118: Deleting a classroom dictionary soft-deletes its related data
**Category:** Lifecycle
**Domain:** Classroom-Dictionary
**Priority:** P0
**Source:** `app/Models/DicAula.php:376-448`
**Plain English:** Deleting a classroom dictionary sets `deleted_at=NOW()`, in one transaction, on the dictionary and its alerts (avisos), entry comments, general comments, timeless flag, field configuration, alert recipients, participants, submissions (dp_envios) and guidelines (pautas).
**Specification:**
  Given dictionary 40 with participants, submissions and comments
  When it is deleted
  Then all listed tables get `deleted_at` for dic_aula_id=40, and dic_aula 40 is soft-deleted
**Parameters:** tables: avisos, comentarios_entradas, comentarios_generales, dic_aula_atemporales, dic_aula_campos, dic_aula_destinatario_avisos, dic_aula_participantes, dp_envios, dic_aula_pautas, dic_aula
**Edge cases handled:** Rolls back on error.
**Suspected defect:** `dic_aula_entradas` is only counted, not soft-deleted. `centros_dic_aula` and `envios_entradas` are left untouched. Errors are swallowed and the method returns null. Raw SQL bypasses auditing and model events. The related models other than DicAula, EnvioEntrada and the DP entry/sense models do not use SoftDeletes, so their `deleted_at` is not applied by default scopes.
**Confidence:** High
**Also found as:** shard I card "Deleting a classroom dictionary soft-deletes its dependents" (`app/Models/DicAula.php:376-448`) — folded in as the same behavior.
**Also found as:** shard A card "Deleting a dictionary soft-deletes it and its related data" (`app/Models/DicAula.php:376-449`) — folded in as the same behavior.

### RULE-119: Pre-checks before deleting a classroom dictionary
**Category:** Lifecycle
**Domain:** Classroom-Dictionary
**Priority:** P0
**Source:** `resources/js/dic_aula.js:434-470`
**Plain English:** Before deleting, the UI calls `testdelete`:
- if the dictionary has published entries, deletion is blocked (the modal has only Cancel);
- if it has pending submissions, a warning is shown but deletion is allowed;
- if it has more than one participant, a "participants joined" warning is added.
**Specification:**
  Given dictionary D with 5 published entries
  When the coordinator clicks "Eliminar diccionario"
  Then the modal "No es posible eliminar un diccionario de aula que tiene entradas publicadas" offers only Cancel
**Parameters:** participants > 1 (the creator counts as 1) (`DicAulaController.php:1610-1626`)
**Suspected defect:** Client-only. GET `/aula/{id}/delete` (`DicAulaController::delete`, lines 1598-1607) has no authorization and doesn't re-check published entries or submissions, so any logged-in user can delete any dictionary by URL.
**Confidence:** High.

### RULE-120: Creating or editing a general comment
**Category:** Lifecycle
**Domain:** Comments-Notifications
**Priority:** P0
**Source:** `app/Http/Controllers/ComentariosController.php:287-351`
**Plain English:** A general comment needs a classroom, a student and a text. It is stored against the student's latest personal dictionary, authored by the current user, and stamped with the current time. Every save (create or edit) resets its state to "visible, not read" and refreshes `fecha_envio`.
**Specification:**
  Given a teacher posts `dic_aula_id=12`, `estudiante=77`, a comment text, `radio=opt_nuevo_comentario`, and an empty `hiddenComentarioOriginalId`
  When saved at 2024-05-10 10:00
  Then a `comentarios_generales` row is created with `dic_personal_id` = latest personal dictionary of persona 77, `persona_id` = the teacher, `dic_aula_id=12`, `fecha_envio=2024-05-10 10:00:00`, `estado=1`. If `hiddenComentarioOriginalId=40`, row 40 is overwritten with the same values instead.
**Parameters:** `ctes.estado_comentario_entrada` = {no_visible:'0', visible_no_leido:'1', visible_si_leido:'2'}. Validation: `dic_aula_id` required integer, `estudiante` required integer, `tinyComentario` required, `radio` required (it has no other effect).
**Edge cases handled:** validation failure redirects back with errors.
**Suspected defect:** editing moves `fecha_envio` to now. Under visibility mode 2, that can hide a previously visible comment. Nothing in the shard or the codebase ever sets `estado=2` (read), so the read state is never tracked. A student with no personal dictionary causes a null-pointer error.
**Confidence:** High. SME question: "Should editing a comment keep its original send date, and should read or unread be tracked?"

### RULE-121: A student sees only general comments aimed at their latest personal dictionary
**Category:** Lifecycle
**Domain:** Comments-Notifications
**Priority:** P0
**Source:** `app/Helpers/ComentariosHelper.php:202-242`
**Plain English:** A student sees a classroom's general comments only when the comment targets the student's most recent personal dictionary and the classroom's visibility mode allows it. Results are newest first.
**Specification:**
  Given student P with personal dictionaries id 5 and id 9, and classroom D (`comentarios_visibles=1`) with comments targeting `dic_personal_id` 9 and 5
  When the student's general comments for D are fetched
  Then only the comment targeting dictionary 9 (the highest id) is returned, newest first. With `comentarios_visibles=0`, an empty list is returned.
**Parameters:** "latest" personal dictionary = `ORDER BY id DESC` first row. The classroom is always re-read from the database because the session copy may be stale (line 208).
**Edge cases handled:** a null classroom returns an empty list.
**Suspected defect:** `->get()[0]` (line 211) throws an error instead of returning null when the student has no personal dictionary, so the `is_null` guard on line 213 never runs. Comments addressed to an older personal dictionary (for example from a previous school year) become invisible.
**Confidence:** High.

### RULE-122: Joining a classroom dictionary by code
**Category:** Lifecycle
**Domain:** Join-Codes
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:433-473`
**Plain English:**
- The code must match a dictionary with estado 1 (not soft-deleted). School year, vigencia, student visibility and submission windows are not checked.
- If the user is not yet a participant, they are added with dictionary role alumno and estado 1, even if their global role is teacher (dicAula_helper.php:426-452 with `$fromUnirse=true`).
- If they are already a participant and are a disabled alumno → error "not authorized".
- Otherwise → info "already joined".
**Specification:**
  Given active dic with code "AB12CD" and student S not joined
  When S POSTs /aula/unirse codigo=AB12CD
  Then a participant row is created (rol 2, estado 1) with a success message.
  And when S's row is (rol 2, estado 0)
  Then the result is the error "modal_unirse_diccionario_error_noautorizado".
**Parameters:** `ctes.rol.alumno`='2'
**Edge cases handled:** unknown code; already joined; disabled student
**Suspected defect:** A disabled teacher-role participant gets "already joined" rather than an error. Codes for past-year (non-vigente but still active) dictionaries still work.
**Confidence:** High.

### RULE-123: Deselecting an unsent entry hides it in the personal dictionary
**Category:** Lifecycle
**Domain:** Personal-Dictionary
**Priority:** P0
**Source:** `resources/views/diccionario/dpDiccionario/dpDiccionarioEnviarFormulario.blade.php:446-487`
**Plain English:** In the send form, clicking a selected, never-sent entry deselects it **and marks it hidden**. Selecting it again un-hides it. On submit the server applies these as permanent state changes: `dpEntrada_ocultar`/`dpEntrada_mostrar` with an owner check (`EnviosController.php:117-122`).
**Specification:**
  Given unsent visible entry "perro" pre-selected for D
  When the student clicks it to deselect and then sends
  Then "perro" becomes `estado = 2` (hidden) in the personal dictionary
**Parameters:** `entradasSelecionadas`, `entradasOcultas` (comma-separated ids)
**Edge cases handled:** deselecting an already-sent entry doesn't hide it. Entries with errors can't be toggled to selected.
**Confidence:** High for the behaviour. SME: "Is it intended that 'don't send' permanently hides the entry in the student's own dictionary?"

### RULE-124: One personal dictionary per person, created on first use
**Category:** Lifecycle
**Domain:** Personal-Dictionary
**Priority:** P0
**Source:** `app/Helpers/dpDiccionarios_helper.php:43-104`
**Plain English:** Each person has one personal dictionary, found by persona_id. If none exists, one is created automatically with the default title and active state.
**Specification:**
  Given persona 42 has no row in dic_personal
  When  they open the personal home page, or save their first sense (`dpEntrada_Add`, `dpEntradas_helper.php:188-193`)
  Then  dic_personal {persona_id: 42, titulo: "Mi diccionario personal", estado: '1'} is created
**Parameters:** default title "Mi diccionario personal"; `ctes.estados.activo` = '1'
**Edge cases handled:** A save failure is only logged; the unsaved object is still returned.
**Suspected defect:** There is no unique DB constraint on dic_personal.persona_id (migration `2020_04_19_113507:124-133`). "One per person" is enforced only by `->first()`, so two simultaneous first visits could create duplicates.
**Confidence:** High.

### RULE-125: Saving the first sense creates the entry
**Category:** Lifecycle
**Domain:** Personal-Dictionary
**Priority:** P0
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:515-530`
**Plain English:** "Create entry" only shows a form. The entry row is created (estado '1') when its first sense is saved, unless an entry with the same text already exists; then the sense is added to that entry.
**Specification:**
  Given the user has no entry "árbol"
  When  they submit the first sense form for "árbol"
  Then  dp_entradas {entrada: "árbol", estado: '1'} is created and the sense is attached with orden 1
**Parameters:** `ctes.estados.activo` = '1' (same value as `estados_entrada.visible`)
**Edge cases handled:** If an entry with the same text exists (C08), it is reused.
**Suspected defect:** Errors while saving the sense, tags or media are caught and only logged (lines 587-595). The user still sees "sense added", and the entry may be left with no senses.
**Confidence:** High.

### RULE-126: Entry and sense visibility states
**Category:** Lifecycle
**Domain:** Personal-Dictionary
**Priority:** P0
**Source:** `config/ctes.php:267-271`
**Plain English:** A personal-dictionary entry or sense is logically deleted (0), visible (1) or hidden (2).
**Specification:**
  Given a personal entry "guagua" with estado '2'
  When views filter on visible senses (estado = '1')
  Then the entry and its senses are excluded, but the rows are kept
**Parameters:** `estados_entrada`: borrado_logico '0', visible '1', oculta '2`.
**Edge cases handled:** The `dp_entradas` and `dp_acepciones` models also use `deleted_at` soft deletes, so there are two independent deletion mechanisms.
**Confidence:** High.

### RULE-127: Soft-deleting a personal entry cascades to its senses
**Category:** Lifecycle
**Domain:** Personal-Dictionary
**Priority:** P0
**Source:** `app/Models/DiccionarioPersonalEntrada.php:66-81`
**Plain English:** Deleting a personal entry soft-deletes all its senses. A force delete permanently deletes them.
**Specification:**
  Given entry 12 has senses 77 and 78
  When entry 12 is deleted (soft)
  Then `dp_acepciones` 77 and 78 get `deleted_at` set, and each sense's own delete hook runs (G09)
**Parameters:** —
**Edge cases handled:** Force versus soft delete is distinguished.
**Suspected defect:** Restoring the entry does not restore its senses, and the media are already gone (G09).
**Confidence:** High
**Also found as:** shard I card "Deleting a personal entry cascades to its senses and media files" (`app/Models/DiccionarioPersonalEntrada.php:66-82`) — folded in as the same behavior.

### RULE-128: Unpublishing and hiding an entry
**Category:** Lifecycle
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:853-905`
**Plain English:** Unpublishing hard-deletes the `dic_aula_entradas` row and toggles the `envio_entrada` estado: 2 (oculta) becomes 1, anything else becomes 2. `ocultaEntrada` (lines 1425-1455, owner only) does the same.
**Specification:**
  Given published entry estado 3
  When unpublished
  Then the dic_aula_entradas row is deleted and estado = 2.
  And when it is unpublished again (row missing)
  Then "Entrada no encontrada".
**Parameters:** `estados_entrada` 0 borrado / 1 visible / 2 oculta; `estados_envios` 0 borrado / 1 enviado / 2 borrado_estudiante / 3 publicado
**Edge cases handled:** missing publication row (unpublish only)
**Suspected defect:**
- It writes `estados_entrada` values into a column that uses `estados_envios`, so 2 reads as "borrado_estudiante".
- `ocultaEntrada` calls `->delete()` on a null row when the entry is not published, which throws an Error that is not caught.
**Confidence:** Medium. SME question: "What status should an unpublished submission return to: 1 (enviado) or a distinct 'hidden'?"
**Also found as:** shard B card "Unpublish transition (status enum collision)" (`app/Helpers/dicAula_helper.php:853-905`) — folded in as the same behavior.

### RULE-129: Publish transition
**Category:** Lifecycle
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:697-748`
**Plain English:** Publishing sets the submission status to "published" (3) and creates or updates its dictionary link with origen = 1 (from a submission) and estado = 1 (visible). The interactive version (`761-840`) does the same when action = 'publicar'; any other action unpublishes (B22).
**Specification:**
  Given envio_entrada 50 with estado '1' (enviado)
  When publicarEntradaSimple(req, 3, 50) runs
  Then envios_entradas.estado = '3' and dic_aula_entradas(3, 50) has origen '1', estado '1'
**Parameters:** `ctes.estados_envios`: 0 borrado_logico, 1 enviado, 2 borrado_estudiante (marked unused), 3 publicado. `ctes.origen.envio` = '1'.
**Edge cases handled:** Missing entry or dictionary returns an error.
**Suspected defect:**
- These helpers do no authorization check (it may be done in the controller).
- The two operations are not in a transaction.
- Nothing checks that the submission was sent to that dictionary.
**Confidence:** High.

### RULE-130: Publishing a single entry
**Category:** Lifecycle
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:761-839`
**Plain English:** With action=publicar:
- If an entry with the exact same title is already published (active) in the dictionary, the error "ya publicada" is returned (lines 672-680).
- Otherwise the `envio_entrada` estado becomes 3 (publicado), and `dic_aula_entradas` (dic, entry) is upserted with origen 1 and estado 1.

Any other action unpublishes (A50).
**Specification:**
  Given dic 7 already publishes "casa", and a new submission "casa"
  When publish is clicked
  Then the error "pubilcarEntrada_ya_publicada" is returned.
**Parameters:** `estados_envios.publicado`='3', `origen.envio`='1', estado '1'
**Edge cases handled:** missing entry or dictionary
**Suspected defect:** Duplicate detection uses exact `entrada` equality, so case or accent behaviour depends on the DB collation. This differs from rename (A53).
**Confidence:** High.

### RULE-131: Publish / unpublish toggle
**Category:** Lifecycle
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `resources/views/layouts/partials/components/botones/entrada/publicar.blade.php:1-54`
**Plain English:** If the submission has an active `dic_aula_entradas` row, the coordinator sees "Anular publicación" (coloured scroll). Otherwise they see "Publicar" (grey scroll). Both post to the same toggle endpoint.
**Specification:**
  Given submission E by student S, not yet published in D
  When the coordinator confirms "Publicar"
  Then E is published unless an entry with the same headword is already published in D (`dicAula_helper.php:714-720`)
**Parameters:** `ctes.estados.activo = '1'`
**Edge cases handled:** the confirm text names the student and the dictionary. The server enforces `esDocente` + `esCoordinador`.
**Confidence:** High.

### RULE-132: Hide entry from a classroom dictionary
**Category:** Lifecycle
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:1425-1455`
**Plain English:** This is the same transition as B22, with a redirect response ("entrada no publicar" message).
**Specification:**
  Given a published entry
  When ocultaEntrada runs
  Then the link is deleted and the status toggles to 2
**Parameters:** As in B22.
**Edge cases handled:** None.
**Suspected defect:**
- If no link exists, `->delete()` on null throws an Error that `catch(Exception)` does not catch, so the result is a 500.
- Same enum collision as B22.
**Confidence:** Medium — same SME question as B22.

### RULE-133: Participant access and editing-permission toggles
**Category:** Lifecycle
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `resources/views/diccionario/participantesAjax.blade.php:13-74`
**Plain English:** In the edit screen, students and teachers are listed separately by their global role. Each row toggles access (`dic_aula_participantes.estado` 1/0). For teachers, a second toggle grants or removes coordinator rights (`rol_diccionario_id` 1). The "owner only" checks are commented out.
**Specification:**
  Given coordinator C viewing participant S with `estado = 1`
  When C clicks the eye icon
  Then S becomes `estado = 0` and can no longer join or re-join (`DicAulaController::unirse`, lines 447-452)
**Parameters:** `ctes.estados` 0/1; `ctes.rol.docente = 1`
**Suspected defect:** `DicAulaController::participantesAjax` (lines 702-737) only checks `esDocente`. `isOwner`/`esCoordinador` are commented out and the participant id isn't tied to `{id}`, so any teacher can enable or disable participants or coordinators of any dictionary.
**Confidence:** High.

### RULE-134: Joining a dictionary: role assignment and idempotency
**Category:** Lifecycle
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:426-452`
**Plain English:**
- The creator is added as a participant with their global role (from `users.role_id`).
- Anyone joining via code (`fromUnirse`, B13 path, `466-474`) is always added with the student role, even if they are a teacher.
- Joining never creates a duplicate participant and never re-enables a disabled one.
**Specification:**
  Given a teacher (users.role_id = 1) joins dic 5 by code
  When daDiccionarioAulaUnirUsuarioActual runs
  Then a dic_aula_participantes row is created with rol_diccionario_id = 2 (alumno), estado '1'
  And if a row already exists with estado '0', it is returned unchanged (still disabled)
**Parameters:** `ctes.rol.alumno` = '2', `ctes.rol.docente` = '1`. Comments in `config/ctes.php:64-66` say local installs use 3/4, so the ids depend on the environment.
**Edge cases handled:** Lookup includes soft-delete scope defaults.
**Confidence:** High.
**Also found as:** shard A card "The creator auto-joins with their global role" (`app/Helpers/dicAula_helper.php:426-452`) — folded in as the same behavior.

### RULE-135: Grant / revoke edit rights
**Category:** Lifecycle
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:1899-1960`
**Plain English:**
- Granting edit rights sets the participant's dictionary role to teacher (1), with no checks (`1868-1895`).
- Revoking sets it to student (2), refused for yourself and for the last teacher-role participant.
**Specification:**
  Given participant 11 with role 2
  When granted
  Then rol_diccionario_id = 1; revoking your own row returns "no_puedes_auto_autodeshabilitarte_edicion"
**Parameters:** `ctes.rol.docente` = '1', `ctes.rol.alumno` = '2'.
**Edge cases handled:** The owner-only restriction is commented out (`1914-1918`).
**Suspected defect:** Same counting issues as B30. Grant has no authorization check in the helper.
**Confidence:** Medium — SME: "Who may grant edit rights: the owner only, or any teacher participant?"

### RULE-136: Promote/demote a participant's dictionary role
**Category:** Lifecycle
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:1868-1960`
**Plain English:** "Habilitar admin" sets `rol_diccionario_id` to teacher (1), which makes the participant a coordinator. "Deshabilitar admin" sets it to alumno (2), with the same self and last-teacher guards as A12.
**Specification:**
  Given participant 41 with rol_diccionario_id 2
  When habilitarParticipanteAdmin(41) runs
  Then rol_diccionario_id = 1 and they pass esCoordinador.
**Parameters:** `ctes.rol.docente`='1', `ctes.rol.alumno`='2'
**Edge cases handled:** self, last teacher (demote only)
**Suspected defect:** Promotion has no restriction on the target's global role, so a student can be made coordinator.
**Confidence:** High.

### RULE-137: App role set only on first provisioning
**Category:** Lifecycle
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:824-875`
**Plain English:** The role is computed only when the persona has no linked user yet, and `firstOrNew` does not update an existing user. A student who later becomes a teacher in CAUCE stays "alumno" for good (centres are still refreshed, F17).
**Specification:**
  Given user U1 provisioned as alumno; CAUCE now reports Rol=1
  When  U1 logs in
  Then  `role_id` stays alumno
**Parameters:** none
**Edge cases handled:** an admin can change the role manually through the Voyager users BREAD.
**Confidence:** High.
**SME question:** Should the role be re-synced from CAUCE on every login?

### RULE-138: Admin bulk deactivation of expired dictionaries
**Category:** Lifecycle
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Http/Controllers/Voyager/VoyagerCompassController.php:284-332`
**Plain English:** An admin triggers a sweep. Every dictionary returned by `daGetDiccionariosConVigencias()` that is active (estado=1) is passed to `daDesactivarSiNoVigente`. The number deactivated is reported and the details (id, title, start year, years of validity, timestamp) are written to the app log.
**Specification:**
  Given 3 active dictionaries, 2 of them past their validity
  When  an admin opens /admin/vigencia/comprobar
  Then  2 are deactivated; message "vigencia_desactivados_diccionarios num=2"; log entry type app_info
**Parameters:** ACTIVADO=1. The actual validity rule lives in the helpers `daEsVigente` / `daDesactivarSiNoVigente` (another shard). No scheduled job exists: `app/Console/Kernel.php:25-29` schedule is empty, so this is manual only.
**Edge cases handled:** inactive dictionaries are skipped.
**Suspected defect:** Logs `ctes.log_types.app_info`, a key not defined in `ctes.log_types` (null type).
**Confidence:** High.
**SME question:** Should expiry run automatically at school-year rollover rather than on admin click?

### RULE-139: Atemporal (permanent) dictionary flag
**Category:** Lifecycle
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Models/DicAula.php:176-211`
**Plain English:** A dictionary is atemporal when its `dic_aula_atemporales` row has estado 1. Turning it on also reactivates the dictionary (estado = 1).
**Specification:**
  Given dictionary 7 with estado '0' and no atemporal row
  When `setAtemporal(true)` runs
  Then an atemporal row with estado '1' is created and the dictionary's estado becomes '1'; `setAtemporal(false)` sets the row to '0' and leaves the dictionary's estado unchanged
**Parameters:** `hasta` (optional end date) is used by the listing query in another shard (`dicAula_helper.php:246-252`).
**Edge cases handled:** Creates the row if missing.
**Suspected defect:** The listing query is `whereDate('hasta','<', tomorrow) OR hasta IS NULL`, so it includes atemporal dictionaries whose end date has passed. The orWhere also escapes the estado filter.
**Confidence:** High for the model behaviour. The listing logic belongs to another shard.

### RULE-140: Per-dictionary validity check needs no login
**Category:** Lifecycle
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `routes/web.php:298-299`
**Plain English:** GET /admin/vigencia/comprobar/{id} has no auth or admin middleware. Any anonymous visitor can make the system deactivate classroom dictionary {id} if it is active but no longer valid (logic in VoyagerCompassController.php:372-416, using `daDesactivarSiNoVigente`).
**Specification:**
  Given dic_aula 42 with estado=1 whose validity has expired
  When  an anonymous GET /admin/vigencia/comprobar/42
  Then  dic_aula 42 is deactivated and the change is logged
**Parameters:** ACTIVADO=1
**Edge cases handled:** a non-existent ID errors (null object).
**Suspected defect:** State-changing GET with no authentication.
**Confidence:** High.

### RULE-141: Auto-deactivate dictionaries that are no longer in force
**Category:** Lifecycle
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:2047-2057`
**Plain English:** When a dictionary is checked and is not in force (B03), its status is set to inactive (0) and saved.
**Specification:**
  Given dic_aula estado = '1', ano_ini = 2020, vigencia = 2, current school year 2023
  When daDesactivarSiNoVigente runs (called from the Voyager compass maintenance, `VoyagerCompassController.php:296,379`)
  Then dic_aula.estado = 0 and the function returns true
**Parameters:** Writes the literal 0 (equivalent to `ctes.estados.inactivo`).
**Edge cases handled:** Atemporal dictionaries are never deactivated. Nothing reactivates a dictionary automatically.
**Confidence:** High.
**Also found as:** shard A card "Deactivate dictionaries that are no longer vigente" (`app/Helpers/dicAula_helper.php:2047-2057`) — folded in as the same behavior.

### RULE-142: Making a dictionary timeless also activates it
**Category:** Lifecycle
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Models/DicAula.php:176-211`
**Plain English:** A dictionary is timeless (atemporal) when it has a `dic_aula_atemporales` row with estado=1. Setting it timeless also sets the dictionary estado=1. Unsetting it only sets the flag to 0 and leaves the dictionary state unchanged.
**Specification:**
  Given dictionary 40 with estado=0 and no atemporal row
  When it is set timeless
  Then an atemporal row with estado=1 is created and dic_aula.estado becomes 1
**Parameters:** `ctes.estados` inactivo=0, activo=1
**Edge cases handled:** Updates the existing atemporal row or creates one.
**Confidence:** High

### RULE-143: Mark a dictionary as timeless (atemporal) or remove the mark
**Category:** Lifecycle
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Http/Controllers/Voyager/VoyagerCompassController.php:334-370`
**Plain English:** An admin can make a classroom dictionary timeless (exempt from expiry) or remove that status, through `DicAula::setAtemporal(true|false)`.
**Specification:**
  Given dic_aula 42
  When  an admin opens /admin/vigencia/atemporal/42
  Then  42 has an active atemporal record and is excluded from the valid/not-valid lists (F28)
**Parameters:** none
**Edge cases handled:** errors are dumped with `dd()` (debug output in production).
**Confidence:** High.

### RULE-144: Hidden classroom senses are still displayed to everyone (suspected defect)
**Category:** Lifecycle
**Domain:** Senses-Acepciones
**Priority:** P0
**Source:** `resources/views/layouts/partials/consulta/entradaAula.blade.php:31-35`
**Plain English:** The classroom entry view renders every `envios_acepciones` row. `estado` (1 visible / 2 hidden) only changes the teacher's eye icon; nothing filters hidden senses out for students.
**Specification:**
  Given entry E in dictionary D with 2 senses, sense 2 having `estado = 2` (hidden by the teacher)
  When a student views E
  Then both senses are rendered, because the controllers load `EnvioAcepcion::where('envio_entrada_id', …)->get()` with no `estado` filter (`DicAulaController.php:837,911,1012,1536,1585`)
**Parameters:** `ctes.estados_entrada`: 0 = logically deleted, 1 = visible, 2 = hidden
**Edge cases handled:** the server refuses to hide the last remaining sense (`dicAula_helper.php:1390-1393`).
**Suspected defect:** Hiding a sense has no effect on what students see.
**Confidence:** Medium. SME: "When a coordinator hides a sense in the classroom dictionary, should students (and the PDF) stop seeing it?"

### RULE-145: Deleting a sense (the last one deletes the entry)
**Category:** Lifecycle
**Domain:** Senses-Acepciones
**Priority:** P0
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:935-984`
**Plain English:** Deleting the only remaining sense runs the entry-deletion rules (C16), so an entry is never left with no senses. Otherwise the sense is soft-deleted and the later senses are renumbered (C18).
**Specification:**
  Given entry E has 1 sense and was published this school year
  When  the owner deletes that sense
  Then  nothing is deleted and the error "entrada_publicada" is shown
**Parameters:** none
**Edge cases handled:** The sense must belong to the entry (`$entrada->dpAcepciones()->find`).
**Suspected defect:** Deleting a non-last sense of a sent or published entry is allowed without any check. The success message is passed through `withErrors`.
**Confidence:** High — SME: may students delete senses of an entry that is already sent or published?

### RULE-146: Deleting a sense permanently deletes its media
**Category:** Lifecycle
**Domain:** Senses-Acepciones
**Priority:** P0
**Source:** `app/Models/DiccionarioPersonalAcepcion.php:53-64`
**Plain English:** When a sense is deleted, even a soft delete, every medium row is hard-deleted and its file is removed from disk.
**Specification:**
  Given sense 77 has an image and an audio, and the entry was submitted to a classroom dictionary
  When sense 77 is soft-deleted (directly or via G08)
  Then both `dp_acepciones_medios` rows are hard-deleted and both files are removed, so the classroom copy loses its media
**Parameters:** —
**Edge cases handled:** None.
**Suspected defect:** The soft delete is not reversible for media, and it bypasses the G07 "submitted" protection.
**Confidence:** High
**Also found as:** shard C card "Deleting a sense deletes its media" (`app/Models/DiccionarioPersonalAcepcion.php:53-64`) — folded in as the same behavior.

### RULE-147: Deleting an entry depends on its submission state this school year
**Category:** Lifecycle
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpEntradas_helper.php:44-98`
**Plain English:** The outcome depends on the entry's submissions in the current school year:
  - **Never sent:** deleted.
  - **Sent at least once and published:** refused.
  - **Latest submission is "sent" (pending teacher):** the entry and its submission line are soft-deleted (the line is set to state 0 first).
  - **Latest is already logically deleted:** error 5.
  - **Anything else (e.g. state 2):** generic error, 403.
**Specification:**
  Given entry E with an envios_entradas row in state '1' (sent) in a submission for this school year
  When  the owner deletes E
  Then  E is soft-deleted, the envios_entradas row is set to estado '0' and soft-deleted, and the message "entrada_borrada" is shown
**Parameters:**
  - `ctes.estados_envios` = {borrado_logico: '0', enviado: '1', borrado_estudiante: '2', publicado: '3'}
  - `ctes.resultados` = {entrada_borrada: 1, entrada_publicada: 3, error_generico: 4, error_entrada_previamente_borrada: 5}
  - Submissions are filtered by `ano_ini_curso_escolar` = current school year
**Edge cases handled:** Published-anywhere is checked before the latest submission's state.
**Suspected defect:**
  - **Wrong state:** `$entrada->estado = borrado_logico` is set just before `$entrada->delete()`. With SoftDeletes, delete() only updates deleted_at/updated_at, so estado '0' is probably never saved.
  - **Not a full delete:** "never sent → deleted completely" is a soft delete (forceDelete is a TODO).
  - **Previous years ignored:** an entry published in a previous school year counts as "never sent" and can be deleted.
  - **Dead case:** the controller's `case config('ctes.resultados.resultados.error_generico')` uses a wrong key (null), but the default branch catches it.
**Confidence:** Medium. SME questions:
  1. Should an entry published in a previous school year be deletable?
  2. Should a deleted-pending entry keep estado '0'?

### RULE-148: Delete an unpublished submission (guard broken)
**Category:** Lifecycle
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:919-962`
**Plain English:** Deleting a submitted entry is meant to be refused when it is published. Otherwise it permanently deletes the entry, its senses, their theme tags and their media.
**Specification:**
  Given envio_entrada 50 published in dic 3
  When eliminarEnvioEntrada(3, 50) runs
  Then intended: refused "No se puede eliminar un envío de entrada publicado". Actual: it is deleted (see defect)
**Parameters:** None.
**Edge cases handled:** Missing entry or dictionary.
**Suspected defect:**
- `$envioEntrada->publicada` does not exist: there is no column, accessor or relation; the relation is `publicadas`. It is always null, so the guard never fires and published entries can be force-deleted.
- The `dic_aula_entradas` link is left dangling (foreign-key error or orphan).
- Nothing checks that the entry belongs to `$dicAulaId`.
**Confidence:** High.
**Also found as:** shard A card "Deleting a student submission" (`app/Helpers/dicAula_helper.php:919-962`) — folded in as the same behavior.

### RULE-149: Each send creates a dp_envios header stamped with the school year and status "sent"
**Category:** Lifecycle
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:287-293`
**Plain English:** Every send creates one submission header per target dictionary. It records the sender's personal dictionary, the target, status 1 (sent), and the start year of the current school year.
**Specification:**
  Given today is 2026-10-04 and INICIO_CURSO=30/8
  When a send to dictionary 12 happens
  Then dp_envios gets {dic_personal_id: sender's, dic_aula_id: 12, estado: 1, ano_ini_curso_escolar: 2026}
  Given today is 2026-08-29
  Then ano_ini_curso_escolar = 2025
**Parameters:** ctes.estados_envios.enviado=1; ctes.inicio_curso = env INICIO_CURSO, default "30/8/2000" (only day and month are used, compared as MMDD)
**Edge cases handled:** The same header logic appears at dpEnvios_helper.php:78-83 (bulk).
**Confidence:** High.

### RULE-150: Submission and submitted-entry states
**Category:** Lifecycle
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `config/ctes.php:282-287`
**Plain English:** A submission is sent (1) and becomes published (3) when the teacher publishes it. Logical delete (0) applies only to submitted entries. "Deleted by student" (2) is reserved but unused.
**Specification:**
  Given a submitted entry with estado '1'
  When the teacher publishes it to the classroom dictionary
  Then its estado becomes '3' and a `dic_aula_entradas` row is created with origen '1' and estado '1'
**Parameters:** `estados_envios`: 0 borrado_logico, 1 enviado, 2 borrado_estudiante (unused), 3 publicado.
**Edge cases handled:** Duplicate headword titles are rejected at publication (`dicAula_helper.php:715-720`, another shard).
**Confidence:** High.

### RULE-151: Logout
**Category:** Lifecycle
**Domain:** Auth-CAS-CAUCE
**Priority:** P1
**Source:** `routes/web.php:402-407`
**Plain English:** POST /cas/logout logs the user out locally, clears the whole session, then sends them to CAS single logout with a return URL of the app root.
**Specification:**
  Given a logged-in user
  When  POST /cas/logout
  Then  the Laravel auth and session are cleared, `cas_user` is removed and the browser goes to the CAS logout with `url=<app root>`
**Parameters:** `CAS_LOGOUT_URL` (default placeholder `https://cas.server.com/cas/logout`), `CAS_LOGOUT_REDIRECT` (default '') (config/cas.php:105,114)
**Edge cases handled:** none
**Confidence:** High.

### RULE-152: Importing entries from another dictionary
**Category:** Lifecycle
**Domain:** Classroom-Dictionary
**Priority:** P1
**Source:** `app/Helpers/dicAula_helper.php:129-143`
**Plain English:** If importar ≠ '0' and a source is chosen (`chk_<id>`), every `dic_aula_entradas` row of the source (all estados) is copied into the new dictionary with the same `envio_entrada_id`, origen and estado.
**Specification:**
  Given source dic 3 with 40 dic_aula_entradas rows
  When creating dic 9 with importar=1, importarRadio=chk_3
  Then dic 9 gets 40 rows pointing to the same submissions.
**Parameters:** prefix 'chk_'
**Edge cases handled:** 'undefined' radio value
**Suspected defect:**
- The server does not check that the caller can access the source dictionary.
- Import also runs on edit with plain create, which can duplicate rows.
- Imported entries still belong to the source dictionary's `dp_envios`, which affects the A19 and A53 lookups.
**Confidence:** High.
**Also found as:** shard B card "Import published entries from another dictionary" (`app/Helpers/dicAula_helper.php:129-143`) — folded in as the same behavior.

### RULE-153: Teacher-invite email and recipient record
**Category:** Lifecycle
**Domain:** Comments-Notifications
**Priority:** P1
**Source:** `app/Http/Controllers/DicAulaController.php:349-420` (outside the shard, but the only writer of `dic_aula_destinatario_avisos`)
**Plain English:** A coordinator can invite another teacher by email to a classroom dictionary identified by its join code. After a successful send, a recipient row is stored with state 3 ("invited teacher").
**Specification:**
  Given a coordinator of the classroom with code "ABC123" submits an email (max 150), name (max 150), surname (max 150) and code (max 100)
  When the mail sends successfully
  Then the invite (subject "LexiCán: Invitación a unirse a un diccionario.", containing the code and app URL) is sent, and `dic_aula_destinatario_avisos{email, dic_aula_id, estado=3}` is saved. If sending fails, nothing is saved and an error is shown.
**Parameters:** `estado=3` is a magic number (TODO in code). The From address is hard-coded (`<masked>`). There is no email-format validation.
**Edge cases handled:** the code is matched without regard to school year (that filter is commented out). Authorization runs before validation.
**Suspected defect:** no email-format rule is applied.
**Confidence:** High. SME question: "What are the other `estado` values of `dic_aula_destinatario_avisos` (0-2)?"
**Also found as:** shard A card "Inviting a teacher by e-mail" (`app/Http/Controllers/DicAulaController.php:349-420`) — folded in as the same behavior.

### RULE-154: Comment on a classroom entry
**Category:** Lifecycle
**Domain:** Comments-Notifications
**Priority:** P1
**Source:** `app/Helpers/dicAula_helper.php:1338-1374`
**Plain English:** Commenting creates an active comment with the current timestamp, linked to the dictionary and the entry.
**Specification:**
  Given user 15 (persona 22) comments on entry 50 in dic 3
  When saved
  Then comentarios_entrada has estado '1' and fecha_envio = now, but persona_id = 15
**Parameters:** `ctes.estado_comentario_entrada`: 0 no_visible, 1 visible_no_leido, 2 visible_si_leido. The code writes estado 1.
**Edge cases handled:** None.
**Suspected defect:** `persona_id` is set to `Auth::user()->id` (a user id, not a persona id), so comments are attributed to the wrong person when the ids differ. The comment HTML is stored unsanitised.
**Confidence:** High.
**Also found as:** shard A card "A coordinator comments on a submission" (`app/Helpers/dicAula_helper.php:1338-1374`) — folded in as the same behavior.

### RULE-155: Comment read-state lifecycle
**Category:** Lifecycle
**Domain:** Comments-Notifications
**Priority:** P1
**Source:** `config/ctes.php:349-353`
**Plain English:** A comment is not visible (0), visible and unread (1), or visible and read (2). New teacher comments start as unread.
**Specification:**
  Given a teacher saves a general comment on a student's dictionary
  When it is stored
  Then estado = '1' (visible, unread); it becomes '2' once the student reads it
**Parameters:** `estado_comentario_entrada` 0/1/2. These codes are also used for `comentarios_generales.estado`.
**Edge cases handled:** Editing a comment overwrites it (updateOrCreate by id) and resets it to unread.
**Confidence:** High.

### RULE-156: The bulk-send selection rewrites personal entry visibility
**Category:** Lifecycle
**Domain:** Personal-Dictionary
**Priority:** P1
**Source:** `app/Http/Controllers/EnviosController.php:104-123`
**Plain English:** On the bulk-send screen, unticked entries become hidden in the personal dictionary and ticked entries become visible. Each change is owner-checked, and it happens before and outside the submission transaction.
**Specification:**
  Given personal entries 5 (visible=1) and 7 (hidden=2)
  When the student submits with entradasOcultas="5" and entradasSelecionadas="7"
  Then entry 5 becomes oculta (2) and entry 7 becomes visible (1), and entry 7 is then sent
**Parameters:** estados_entrada: visible=1, oculta=2. The list separator is the literal ", " (comma plus space).
**Edge cases handled:** Only visible→hidden and hidden→visible change; soft-deleted (0) entries are not touched. Visibility changes persist even if no dictionary was chosen or the send fails.
**Suspected defect:** The "no dictionary" guard `$listaDiccionariosAula = ""` (line 126) is an assignment, not a comparison. It works only because "" is falsy. Also, the side effects are not rolled back on failure.
**Confidence:** High.

### RULE-157: Hide/show toggles in the personal dictionary
**Category:** Lifecycle
**Domain:** Personal-Dictionary
**Priority:** P1
**Source:** `resources/views/layouts/partials/components/botones/entrada/ocultar.blade.php:1-24`
**Plain English:** Personal entries (and senses, `botones/acepcion/ocultar.blade.php:1-21`) toggle between visible (1) and hidden (2) after confirmation. Hidden items show a red eye.
**Specification:**
  Given visible entry "casa"
  When the student confirms "ocultar"
  Then `estado = 2`; it can't be sent (H27) and is excluded from the PDF unless "include hidden" is ticked (H12)
**Parameters:** `ctes.estados_entrada` 1/2
**Edge cases handled:** `botones/entrada/ocultarAula`, `entrada/verComentarios`, `entrada/edit|delete` and `acepcion/delete` are not included anywhere (dead code).
**Confidence:** High.

### RULE-158: Show/hide an entry
**Category:** Lifecycle
**Domain:** Personal-Dictionary
**Priority:** P1
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:446-464`
**Plain English:** The owner can switch an entry between hidden (2) and visible (1).
**Specification:**
  Given entry E with estado '2' (hidden)
  When  the owner clicks hide/show
  Then  E.estado = '1'. Clicking again sets '2'.
**Parameters:** `ctes.estados_entrada` = {borrado_logico: '0', visible: '1', oculta: '2'}
**Edge cases handled:** Any state other than '2' (including '0') becomes '2'.
**Confidence:** High.

### RULE-159: Marking a dictionary atemporal reactivates it
**Category:** Lifecycle
**Domain:** SchoolYear-Vigencia
**Priority:** P1
**Source:** `app/Models/DicAula.php:192-211`
**Plain English:** Setting atemporal on also sets the dictionary estado to 1 and the atemporal row estado to 1, creating the row if needed. Unsetting only sets the atemporal row to 0; the dictionary estado is unchanged.
**Specification:**
  Given inactive dic 7
  When an admin sets it atemporal
  Then dic 7 estado = 1 and atemporal estado = 1.
**Parameters:** none
**Edge cases handled:** creates the atemporal row if missing
**Confidence:** High.

### RULE-160: Saving a sense always makes it visible
**Category:** Lifecycle
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:660-675`
**Plain English:** Creating or editing a sense always sets its estado to '1' (visible), so editing a hidden sense makes it visible again.
**Specification:**
  Given sense S with estado '2' (hidden)
  When  the owner edits and saves S
  Then  S.estado = '1'
**Parameters:** `ctes.estados.activo` = '1' (same as `estados_entrada.visible`). Insert path: line 548.
**Edge cases handled:** none. The entry's own estado is not recalculated, so it can stay hidden while it has a visible sense.
**Confidence:** High that the code does this. SME question: is un-hiding a sense on edit intended?

### RULE-161: Show/hide a sense; no visible senses hides the entry
**Category:** Lifecycle
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:1393-1420`
**Plain English:** The owner switches a sense between hidden (2) and visible (1). If afterwards the entry has no visible senses, the entry is hidden too. Making a sense visible again does not un-hide the entry; a message tells the user instead.
**Specification:**
  Given entry E (visible) with one visible sense S
  When  the owner hides S
  Then  S.estado = '2' and E.estado = '2'
**Parameters:** estados_entrada visible = '1', oculta = '2'
**Edge cases handled:** Message "entradas_visibles" when the entry stays hidden but has a visible sense.
**Confidence:** High.

### RULE-162: Hide / show a sense, never the last one
**Category:** Lifecycle
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `app/Helpers/dicAula_helper.php:1386-1413`
**Plain English:** A teacher can toggle a sense of a published entry between hidden (2) and visible (1). This is refused when the entry has only one sense.
**Specification:**
  Given an entry with 2 senses, sense A estado '1'
  When ocultaAcepcion(A) runs
  Then A.estado = '2'; with only 1 sense the error is "ultima_acepcion"
**Parameters:** `ctes.estados_entrada`.
**Edge cases handled:** None.
**Suspected defect:** It counts all senses, not visible ones. With 2 senses, both can be hidden one after the other, leaving the entry with no visible sense.
**Confidence:** High.
**Also found as:** shard A card "Hiding/showing a sense of a published entry" (`app/Helpers/dicAula_helper.php:1386-1413`) — folded in as the same behavior.

### RULE-163: Deleting the last sense deletes the entry
**Category:** Lifecycle
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `resources/views/layouts/partials/consulta/dpEntradaAcepcion.blade.php:42-64`
**Plain English:** When the entry has exactly one sense, the delete confirmation warns that the whole entry will be deleted.
**Specification:**
  Given entry "casa" with 1 sense
  When the student deletes it
  Then the entry is deleted too (server: `DiccionarioPersonalController.php:940-950`)
**Parameters:** lang key `confirm_acepcion_borrar_entrada`
**Edge cases handled:** with 2 or more senses, the message names the sense number.
**Confidence:** High.

### RULE-164: Removing a submitted sense hard-deletes it
**Category:** Lifecycle
**Domain:** Submissions-Envios
**Priority:** P1
**Source:** `app/Models/EnvioAcepcion.php:128-144`
**Plain English:** Removing a submitted sense permanently deletes its media rows (files are kept), its theme links and the sense itself, in one transaction.
**Specification:**
  Given envio sense 300 with 1 image and 2 themes
  When `borrar()` is called
  Then the 1 media row, 2 theme rows and the sense row are hard-deleted. The shared file stays on disk.
**Parameters:** —
**Edge cases handled:** Rolls back on error.
**Suspected defect:** Query-builder deletes produce no audit rows. `Envio::borrar()` (`app/Models/Envio.php:50-69`) calls `EnvioEntrada::borrar()`, which is commented out, so it would throw. It has no callers.
**Confidence:** Medium. I did not trace the callers of `EnvioAcepcion::borrar`. SME question: "Should removing a submitted sense be recoverable or audited?"

### RULE-165: Hide/show entries in bulk from the send screen (one-way)
**Category:** Lifecycle
**Domain:** Submissions-Envios
**Priority:** P1
**Source:** `app/Helpers/dpEntradas_helper.php:663-688`
**Plain English:** On the send screen, entries the student unticks are hidden (visible → hidden only) and ticked entries are shown (hidden → visible only). Each entry is ownership-checked.
**Specification:**
  Given entry E visible
  When  the send form lists E among `entradasOcultas` (`EnviosController.php:118-123`)
  Then  E.estado = '2'
**Parameters:** estados_entrada
**Edge cases handled:** No change if already in the target state.
**Confidence:** High.

### RULE-166: One medium per type per sense; a new upload replaces the old one
**Category:** Lifecycle
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `app/Helpers/dpMedios_helper.php:46-67`
**Plain English:** Each personal-dictionary sense (acepción) holds at most one image, one audio and one video. Uploading a new file of a type deletes the previous one of that type, then saves the new one as active.
**Specification:**
  Given sense 77 already has an image (tipo_medio=1) "old.png"
  When the owner uploads "perro.jpg" as the image of sense 77 in entry 12
  Then the old image record is deleted (file deletion follows G07), and a new `dp_acepciones_medios` row is created with tipo_medio=1, nombre="perro.jpg", url_externa="", url_interna=<generated name>, estado=1
**Parameters:** `ctes.tipos_medios` imagen=1, audio=2, video=3; `ctes.estados.activo`=1
**Edge cases handled:** A sense with no previous medium of that type skips the delete. Callers process only files where `isValid()` is true (`app/Http/Controllers/DiccionarioPersonalController.php:566-586`).
**Suspected defect:** The new medium is saved after the old one is deleted, with no transaction. If saving fails, the sense is left with no medium. The caller catches the error and only logs it.
**Confidence:** High


## Policy

### RULE-167: Admin routes require `admin.user`
**Category:** Policy
**Domain:** Admin-Voyager
**Priority:** P0
**Source:** `routes/web.php:282-367`
**Plain English:** The /admin custom screens are guarded by Voyager's `admin.user` middleware (assumed: logged in plus `browse_admin` permission). This covers vigencia, statistics, recording, maintenance, configuration and warnings.
**Specification:**
  Given a user with role 'alumno' (no browse_admin)
  When  GET /admin/vigencia
  Then  access refused (redirect or 403)
**Parameters:** permission `browse_admin`; roles holding it: admin (all permissions), user (seeder)
**Edge cases handled:** `Voyager::routes()` is registered twice; later duplicates override earlier ones (e.g. /admin/logs at 357 replaces the unguarded one at 289).
**Suspected defect:**
- Exceptions without `admin.user`: `/admin/vigencia/comprobar/{id}` (F24), POST `/admin/sendFileJs` and `/admin/sendFotosJs` (no auth; they only echo data and store nothing, vRecordingController.php:27-58).
- The warnings routes (360-366) point to methods that do not exist in VoyagerCompassController, so they would error.
**Confidence:** Medium — middleware semantics come from vendor code.

### RULE-168: Unrouted backdoor that creates or resets an admin account
**Category:** Policy
**Domain:** Admin-Voyager
**Priority:** P0
**Source:** `app/Http/Controllers/HomeController.php:70-98`
**Plain English:** `createAdmin` creates or resets a Voyager admin account with a hard-coded email and password. `downloadLogs` and `logs` read log files from a hard-coded absolute path. None of these methods is routed at present; the `down` route is commented out at `routes/web.php:268`.
**Specification:**
  Given the method gets routed
  When it is called
  Then user `<masked>` gets `role=admin` and password `<credential — masked, see HomeController.php:76>`.
**Parameters:** credential masked; the log path is an internal host path (`<masked>`).
**Edge cases handled:** `basename()` prevents path traversal in `downloadLogs`.
**Suspected defect:** a credential is committed in a public repo and must be rotated. `logs()` references the non-existent route `down`.
**Confidence:** High. These must not be migrated.

### RULE-169: Admin data listing skips the per-type permission check
**Category:** Policy
**Domain:** Admin-Voyager
**Priority:** P0
**Source:** `app/Http/Controllers/Voyager/VoyagerBaseController.php:13-30`
**Plain English:** The overridden Voyager list ("browse") screen has its `authorize('browse', …)` call commented out. Anyone who passes `admin.user` can list any data type's rows, regardless of per-table browse permissions.
**Specification:**
  Given role 'user' without browse_personas
  When  GET /admin/personas?key0=…&value0=…
  Then  persona rows are listed
**Parameters:** none
**Edge cases handled:** none
**Confidence:** High.

### RULE-170: Audit trail configuration
**Category:** Policy
**Domain:** Audit
**Priority:** P0
**Source:** `config/audit.php:5-136`
**Plain English:**
- Audit runs by default (`AUDITING_ENABLED`=true).
- Every create, update, delete and restore on the 33 models implementing `Auditable` is recorded in table `audits`, with the acting user (web/api guard), IP, user agent and URL.
- Records are kept without limit (threshold 0). Console and seeder actions are not audited.
**Specification:**
  Given a teacher updates a DicAula title through the web
  Then  an `audits` row is written with event='updated', old/new values, user_id, IP, UA, URL
**Parameters:**
- `enabled`=env AUDITING_ENABLED (true)
- events created/updated/deleted/restored
- strict=false
- timestamps=false (created_at/updated_at changes not audited)
- threshold=0
- driver=database, table `audits`
- console=false
- Audited models include Persona, UserPersona, UserCentro, Centro, DicAula*, DiccionarioPersonal*, Envio*, Comentario*, CentroDicAula, master tables.
**Edge cases handled:**
- `users`, `roles` and `permission_role` are **not** audited, so role changes leave no audit trail.
- Users-centres sync (F17) uses the pivot through `belongsToMany->sync`, which bypasses UserCentro model events, so it is likely not audited.
**Confidence:** High.
**SME question:** Is there a required retention period for audits, and must user role changes be audited?

### RULE-171: Soft-delete scope: which tables are recoverable
**Category:** Policy
**Domain:** Audit
**Priority:** P0
**Source:** `app/Models/DicAula.php:20-23`
**Plain English:** Only classroom dictionaries, personal entries, personal senses and submitted entries (`envios_entradas`) are soft-deleted (recoverable, excluded by default scopes). Every other model is hard-deleted through Eloquent.
**Specification:**
  Given a personal medium, a submitted sense or a participant
  When it is deleted through Eloquent
  Then the row is permanently removed. A DicAula, DP entry, DP sense or EnvioEntrada instead gets `deleted_at` and disappears from default queries.
**Parameters:** SoftDeletes in `DicAula.php:23`, `DiccionarioPersonalEntrada.php:22`, `DiccionarioPersonalAcepcion.php:21`, `EnvioEntrada.php:21`
**Edge cases handled:** —
**Suspected defect:** Several tables have a `deleted_at` column that G21 writes to, but their models have no SoftDeletes, so Eloquent reads still return the "deleted" rows. `DiccionarioPersonal` has no SoftDeletes, yet statistics filter `dic_personal.deleted_at`.
**Confidence:** High

### RULE-172: Audit trail on domain models
**Category:** Policy
**Domain:** Audit
**Priority:** P0
**Source:** `config/audit.php:59-64`
**Plain English:** Every domain model except `Pautas` (`mst_pautas`) records created, updated, deleted and restored events with no history cap. Changes made from the console and through raw SQL are not audited.
**Specification:**
  Given a teacher edits a classroom dictionary via Eloquent
  When it is saved
  Then an `audits` row records the old and new values
  And deletes done with `\DB::statement` (G21) or query-builder `->delete()` (G28) produce no audit row
**Parameters:** threshold=0 (unlimited, line 98); console=false (line 136); driver=database. Every `app/Models/*.php` uses `\OwenIt\Auditing\Auditable` except `Pautas.php:17` and `Voyager/*`.
**Edge cases handled:** —
**Confidence:** High

### RULE-173: Provisioned user account fields (shared password hash)
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:863-875`
**Plain English:** On first login a local user is found or created by `name` = CAS login ID. A new account gets email = the same login ID, the computed role, avatar `users/default.png`, and one hard-coded bcrypt password hash that every provisioned user shares.
**Specification:**
  Given no user named "U1"
  When  U1 logs in through CAS for the first time
  Then  users row: name='U1', email='U1', role_id=<docente|alumno id>, avatar='users/default.png', password=<credential — masked, see app/Helpers/global_helper.php:873 (preview "$2y$")>
**Parameters:** shared password hash (masked); default avatar `users/default.png`
**Edge cases handled:** an existing user with that name is reused unchanged (`firstOrNew`).
**Suspected defect:**
- All CAS-provisioned accounts share one password. Together with F21 (password login still active), anyone who knows the plaintext and a login ID can sign in as that user through POST /login or /admin/login, bypassing CAS and CAUCE.
- The `email` column holds a login ID, not an email address.
**Confidence:** High.

### RULE-174: CAUCE web service call
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:739-760`
**Plain English:** The app asks CAUCE about the user with a GET to `CAUCE_WEBSERVICE` + login ID, sending a Bearer token and parsing the XML reply. TLS certificate checking is turned off and HTTP error codes do not raise errors.
**Specification:**
  Given CAUCE_WEBSERVICE=<masked base URL> and login ID "U1"
  When  a user logs in
  Then  GET <base>U1 with header `Authorization: Bearer <token>` is sent; the XML body becomes a nested array
**Parameters:**
- `CAUCE_WEBSERVICE` (no default, read with `env()` at runtime)
- `CAUCE_BEARER`: `<credential — masked, see .env.example:63>`
- `verify=false`
- `http_errors=false`
**Edge cases handled:** a test-XML path (`$xmlTest`) skips the HTTP call (lines 715-728).
**Suspected defect:**
- `env()` is called outside config. If `config:cache` is used it returns null and the endpoint/token become empty.
- XML with HTML entities such as accents fails to parse (TODO at 754-758).
- A real-looking bearer token is committed in the tracked `.env.example`. It should be rotated.
**Confidence:** High.

### RULE-175: Password login stays available in every environment
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `routes/web.php:280-280`
**Plain English:** Besides CAS, standard Laravel auth routes are registered: POST /login, /register, password reset and logout. Voyager's /admin/login (VoyagerAuthController.php:34-60, with login throttling) is also registered. So email+password login works in production too, not only in local.
**Specification:**
  Given APP_ENV=production and a user row with email "U1" and a known password
  When  POST /admin/login or POST /login with those credentials
  Then  the user is logged in without CAS or CAUCE validation (Voyager path also builds `userData`)
**Parameters:** Laravel default throttling (5 attempts, 1 min); Voyager redirect after login = `voyager.user.redirect` '/admin' (config/voyager.php:17)
**Edge cases handled:** only GET /login is overridden to redirect to CAS (routes/web.php:409-411).
**Suspected defect:** Combined with F12's shared hash this is a CAS bypass.
**Confidence:** High.
**SME question:** Must the target system keep a non-CAS admin login? If so, for which accounts only?

### RULE-176: Unauthenticated users go to CAS, or to the Voyager form in local
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Http/Middleware/Authenticate.php:26-35`
**Plain English:** A visitor who is not logged in and opens a protected page is sent to the CAS login in every environment except `local`, where they get the Voyager email/password form.
**Specification:**
  Given APP_ENV=production and no logged-in user
  When  the user requests /personal
  Then  they are redirected to route `cas.login` (/cas/login, which calls `cas()->authenticate()`)
  Given APP_ENV=local
  When  the same request is made
  Then  they are redirected to `voyager.login` (/admin/login, password form)
**Parameters:** APP_ENV (default `production`, config/app.php:29); route `/login` also redirects to `cas.login` (routes/web.php:409-411)
**Edge cases handled:** AJAX/JSON requests get 401 from Laravel's base middleware.
**Confidence:** High — explicit branch.

### RULE-177: Any logged-in user can run the CAUCE validator test page
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Http/Controllers/HomeController.php:101-205`
**Plain English:** `GET /test` (auth only, `routes/web.php:266`) runs `userValidatorCAUCE` against 4 embedded CAUCE fixtures and dumps the results. The fixtures cover: a user with no centre (role 5), several centres and roles, one centre (role 3), and a centre with an empty code.
**Specification:**
  Given any logged-in user
  When `/test` is opened
  Then the internal CAUCE login decisions are dumped (dd) to the browser.
**Parameters:** the fixture identifiers (NIF, CIAL, centre codes) are partly masked in the source; they are `<masked>` here.
**Edge cases handled:** none.
**Suspected defect:** debug endpoint exposed in production. The fixtures are a useful test oracle for the CAUCE rules (roles 2, 3 and 5; a centre with an empty code).
**Confidence:** High.

### RULE-178: Placeholder centre for users with no centre
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Helpers/global_helper.php:945-955`
**Plain English:** A placeholder centre with code "00000000", named "Sin centro educativo" and active, is always ensured to exist. Users whose CAUCE data has no usable centre code are attached to it, so nobody is left without a centre.
**Specification:**
  Given CAUCE InfoCentro has an empty Codigo
  When  the user logs in
  Then  the user's centres = [centro with cod_centro '00000000']
**Parameters:** `cod_centro='00000000'`, `denominacion='Sin centro educativo'`, `estado=1` (hard-coded)
**Edge cases handled:**
- A creation failure is logged. `$centerNoCenter` is then undefined, and the later use would error.
- See also lines 1056-1066 and 985-986 (multi-centre entries with an empty code or name fall back to the placeholder values).
**Confidence:** High.

### RULE-179: CAS server certificate validation
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Cas/CasManager.php:182-191`
**Plain English:** The CAS server's TLS certificate is checked only if `CAS_VALIDATION` is exactly 'ca' or 'self'. Any other value turns validation off.
**Specification:**
  Given CAS_VALIDATION is set to a URL (as in the committed .env.example:58)
  When  tickets are validated
  Then  `phpCAS::setNoCasServerValidation()` is used (no certificate check)
**Parameters:**
- `CAS_VALIDATION` default ''
- `CAS_CERT` default ''
- `CAS_VALIDATE_CN` default true
- `CAS_VERSION` default "3.0"
- `CAS_PORT` 443
- `CAS_URI` '/cas'
- `CAS_HOSTNAME` (placeholder default)
- `CAS_ENABLE_SAML` false
- `CAS_CONTROL_SESSIONS` false

All in config/cas.php.
**Edge cases handled:** an invalid CAS version falls back to 2.0.
**Confidence:** High.

### RULE-180: Only the CAS login ID is used
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Cas/CasManager.php:233-256`
**Plain English:** From CAS the app only uses the login ID (the user "name"). It reads CAS attributes but no business logic uses them. All profile, role and centre data come from the CAUCE web service.
**Specification:**
  Given CAS returns user "U1" with attributes {…}
  When  the callback runs
  Then  only "U1" is passed to `userValidatorCAUCE`; the attributes are stored in CasUser but never read
**Parameters:** none
**Edge cases handled:** the CAS ID is also stored in session key `cas_user`.
**Confidence:** High — grep shows no caller of `getAttribute(s)` in business code.

### RULE-181: CAS masquerade bypasses authentication
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `app/Cas/CasManager.php:202-205`
**Plain English:** If `CAS_MASQUERADE` is set, CAS is skipped. Every visitor is treated as authenticated with that fixed login ID and goes straight to the callback.
**Specification:**
  Given CAS_MASQUERADE="U9"
  When  anyone opens /cas/login
  Then  they are redirected to /cas/callback and logged in as the user whose name is "U9"
**Parameters:** `cas.cas_masquerade` = env CAS_MASQUERADE (default '' = off) (config/cas.php:169); `check()`/`isAuthenticated()` always return true while masquerading (CasManager.php:366-377)
**Edge cases handled:** none
**Confidence:** High.

### RULE-182: `/test` route provisions test accounts
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P0
**Source:** `routes/web.php:265-266`
**Plain English:** Any logged-in user can call GET /test. It runs `userValidatorCAUCE` on 4 built-in XML samples (HomeController@test), creating or updating 4 test personas, users and centres in whatever database the app is connected to.
**Specification:**
  Given any authenticated user in production
  When  GET /test
  Then  4 test persona/user/centre sets are written to the DB and dumped to the browser
**Parameters:** none
**Edge cases handled:** none
**Suspected defect:** Test fixtures reachable in production put junk data in the database.
**Confidence:** High.

### RULE-183: Defaults when creating or updating a classroom dictionary
**Category:** Policy
**Domain:** Classroom-Dictionary
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:54-88`
**Plain English:** Saving a dictionary sets the owner, title and description, school year, visibility, submission and comment settings. It fills these defaults when values are missing:
- tipo = 1
- letra_grupo = 0
- area/materia = 1
- nivel = null
- estado = active
**Specification:**
  Given a form without tipoDiccionario, grupoLetra, areaMateria
  When the dictionary is created
  Then mst_tipo_dic_id = 1, letra_grupo = '0', mst_area_materia_id = 1, estado = '1', and the creator is joined (B14)
**Parameters:**
- `ctes.dic_aula.max_acepciones` = '10'
- `comentarios_visibles`: 0 no_visible, 1 visible, 2 anteriores_a (date in `comentarios_visibles_anteriores_a`)
**Edge cases handled:** On update the owner `persona_id` is overwritten with the editor's persona.
**Suspected defect:**
- Line 78: `isset($params['maxAcepciones']) ?? config(...)` stores `1` or `0` (a boolean) instead of the chosen value or the default 10.
- Line 82: `envios_fecha_fin` is set to "now" whenever a start date is given.
- Line 66+: updating overwrites `persona_id` (ownership transfer on edit).
**Confidence:** High.

### RULE-184: Comments are only aggregated from "connected" classroom dictionaries
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P0
**Source:** `app/Http/Controllers/ComentariosController.php:43-81`
**Plain English:** The student's "my comments" page merges general and entry comments from every classroom dictionary the student is actively connected to. The `{diccionario_id}` in the URL is ignored; the page always uses the logged-in user.
**Specification:**
  Given student P is an active participant of active classroom dictionaries A (current school year) and B (previous year, not atemporal or vigente)
  When P opens `/personal/{any_id}/comentarios`
  Then only comments from A are shown.
**Parameters:** "connected" is defined in `app/Helpers/dicAula_helper.php:225-259`: the participant is active (`estado='1'`), the classroom is active, and the classroom is in the current school year, OR is atemporal and active with `hasta` before tomorrow or null, OR is vigente.
**Edge cases handled:** merged lists are not re-sorted across classrooms. Comments in a classroom the student has left (participant `estado=0`) disappear.
**Suspected defect:** the atemporal clause `whereDate('hasta','<',tomorrow)->orWhereNull('hasta')` keeps only atemporal links whose end date is today or earlier. That looks inverted (expired links would match). Shard owning `dicAula_helper` should confirm.
**Confidence:** High for the aggregation. SME question: "Should a student still see comments from previous-year classrooms?"

### RULE-185: Classroom comment-visibility modes
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P0
**Source:** `app/Models/DicAula.php:216-260`
**Plain English:** Each classroom dictionary decides whether students can see teacher comments: never (0), always (1), or only comments sent before a cut-off date (2).
**Specification:**
  Given classroom dictionary D with `comentarios_visibles=2` and `comentarios_visibles_anteriores_a=2024-03-01`, and general comments sent on 2024-02-28 and 2024-03-02
  When the visible comments of D are requested
  Then only the 2024-02-28 comment is returned (strictly `fecha_envio < 2024-03-01`). With value 0 nothing is returned; with value 1 everything is returned.
**Parameters:** `ctes.comentarios_visibles` = {no_visible:'0', visible:'1', anteriores_a:'2'}; the cut-off column is `dic_aulas.comentarios_visibles_anteriores_a`. The entry-comment twin is `DicAula.php:265-310`.
**Edge cases handled:** sort order can be asc, desc or none. A comment sent exactly on the cut-off timestamp is hidden.
**Suspected defect:** the code comment at `DicAula.php:240` says "posteriores" (after the date), but the code shows comments sent *before* the date. The teacher-screen label also calls it "anteriores_a". Mode 0 returns a Collection instead of a relation, which is inconsistent.
**Confidence:** High. The logic is explicit. SME question: "Is mode 2 meant to hide recent comments (sent on or after the date) or old ones?"
**Also found as:** shard G card "Comment-visibility modes" (`app/Models/DicAula.php:216-260`) — folded in as the same behavior.
**Also found as:** shard I card "Comment visibility mode per classroom dictionary" (`app/Models/DicAula.php:216-260`) — folded in as the same behavior.
**Also found as:** shard A card "Comment visibility policy" (`app/Models/DicAula.php:216-260`) — folded in as the same behavior.

### RULE-186: Entry-comment modal is owner-only and lists visible comments from every classroom
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P0
**Source:** `app/Http/Controllers/ComentariosController.php:95-131`
**Plain English:** Only the owner of a personal-dictionary entry can open its comments modal. The modal lists every visible teacher comment on any submission of that entry, from any classroom, newest first.
**Specification:**
  Given entry E belongs to student P
  When user Q (not P) requests `getComentariosEntradaAjax?entrada_id=E`
  Then access is denied (403, policy `DiccionarioPersonalPolicy::isOwner`, `app/Policies/DiccionarioPersonalPolicy.php:24-28`).
  When P requests it
  Then the modal lists the comments from `daGetComentariosEntradasByEntradaByComentariosVisible` (`app/Helpers/ComentariosHelper.php:57-73`).
**Parameters:** the modal is 950px wide (UI only).
**Edge cases handled:** an unknown entry id returns an empty 200 response, with no error.
**Suspected defect:** the visibility subquery at `ComentariosHelper.php:62-66` compares unqualified `fecha_envio` (a comment column) inside a `dic_aulas` subquery. It works only if SQL resolves the column to the outer table, and that is fragile. Comment bodies are rendered unescaped (`{!! !!}` in `modal-comentarosentrada.blade.php:103`), which is a stored-XSS risk from teacher HTML.
**Confidence:** High.

### RULE-187: A student sees only entry comments on their own submissions
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P0
**Source:** `app/Helpers/ComentariosHelper.php:327-363`
**Plain English:** A student sees a classroom's entry comments only when the commented entry was submitted from their own personal dictionary, subject to the same visibility modes.
**Specification:**
  Given classroom D with `comentarios_visibles=2` and a cut-off of 2024-03-01, and an entry comment dated 2024-02-10 on an entry submitted by student P
  When P's entry comments for D are fetched
  Then the comment is returned. A comment on another student's submission, or one dated 2024-03-05, is not.
**Parameters:** ownership path `comentarios_entradas → envioEntrada → dpPersonal → persona.id = P`.
**Edge cases handled:** a null classroom returns an empty list. Mode 0 returns an empty list.
**Confidence:** High.

### RULE-188: Editing/deleting comments on classroom entries has no authorization
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:1173-1243`
**Plain English:** Any authenticated user can edit the text of any `comentarios_entradas` row, or delete it, by id.
**Specification:**
  Given comment 300 written by teacher A
  When student S POSTs /aula/1/entrada/1/comentario/300/delete
  Then comment 300 is destroyed.
**Parameters:** none
**Edge cases handled:** none
**Suspected defect:** Missing authorization, and the comment is not checked to belong to the dictionary or entry in the URL. The `ComentarioEntrada` model has no `SoftDeletes` trait, so `destroy` is permanent.
**Confidence:** High.

### RULE-189: CSV statistics export open to any logged-in user
**Category:** Policy
**Domain:** Exports
**Priority:** P0
**Source:** `app/Http/Controllers/CSVController.php:21-98`
**Plain English:** `/getAllDatesCSV?option=` builds a usage-statistics CSV bundle from admin dashboard queries. The route has only the `auth` middleware, not `admin.user`, so any student or teacher can download it.
**Specification:**
  Given a logged-in student
  When they call `/getAllDatesCSV?option=dicsCursos`
  Then they receive `dataAll_export_<YYYY-MM-DD>.csv` with dictionaries per school year, active (vigentes) dictionaries per year, and participant counts per role in active dictionaries
**Parameters:** option map: `dicsCursos` → queryNumDicsCursoEscolar, queryDicsVigentesCursoActual, queryPersonasParticipantesDiccionarios_AulasVigentes; `dicsPersonales` → queryDicsCreadosCursoEscolar, queryEntradasPublicadasDiccionarioAula, queryEntradasCursoEscolar; `centros` → queryCentrosAnoEscolar. "Vigente" here means `dic_aula.estado=1 AND vigencia>0` (`VoyagerCompassController.php:107-119`); a published entry means `envios_entradas.estado=3` in a dictionary with estado=1. Export dir is env `EXPORT_PATH`, default `./public/exports`.
**Edge cases handled:** An unknown option gives an empty list. Query blocks with no rows are skipped.
**Suspected defect:** Missing admin authorization (`routes/web.php:244-245`). No view links to these routes. Files land in the web root (`public/exports`) and can be fetched directly by URL.
**Confidence:** High. SME question: "Should these statistics exports be limited to Voyager admins?"

### RULE-190: Classroom PDF content: published and visible entries only
**Category:** Policy
**Domain:** Exports
**Priority:** P0
**Source:** `app/Helpers/pdf_helper.php:141-196`
**Plain English:** The classroom PDF includes only submitted entries that are published (`envios_entradas.estado=3`) and visible in this dictionary (`dic_aula_entradas.estado=1`). Entries are alphabetical and senses ordered by `orden`. Images use the thumbnail. The participant list is attached. An empty result is an error ("no hay entradas", code 204).
**Specification:**
  Given dictionary 40 with published entries "sol" (visible) and "luna" (dic_aula_entradas.estado=0), and "mar" only submitted (estado=1)
  When the PDF is generated
  Then only "sol" appears. The title is the dictionary title, the author is the teacher's full name, and the file is `da_<titulo>.pdf`.
**Parameters:** `ctes.estados_envios` enviado=1, publicado=3; filter in `peticionEntradasAula` (`app/Helpers/dicAula_helper.php:1033-1056`)
**Edge cases handled:** Entries with zero senses are skipped.
**Suspected defect:** `PDFController.php:96` calls this outside the try block, so an empty dictionary gives an unhandled exception (500) instead of the friendly "no entries" redirect, which is only reachable on the tagged path.
**Confidence:** High

### RULE-191: Personal-dictionary PDF has no ownership check
**Category:** Policy
**Domain:** Exports
**Priority:** P0
**Source:** `app/Http/Controllers/PDFController.php:36-75`
**Plain English:** Any logged-in user can download the PDF of any personal dictionary by ID, including hidden content via `showHidden=1`. The `authorize` call is missing, and the persona lookup at line 39 is commented out.
**Specification:**
  Given student A (persona 5) and student B's dictionary id 9
  When A requests GET `/pdf/9/getdp?showHidden=1`
  Then the PDF of B's dictionary, hidden entries included, is returned. Only the `auth` middleware applies (`routes/web.php:30, 252-253`).
**Parameters:** —
**Edge cases handled:** The options page (`PDFController.php:135-167`) always uses the caller's own dictionary, but the download endpoint does not enforce it.
**Suspected defect:** IDOR: a user can read another user's personal dictionary by changing the ID. A non-existent ID causes a null error, not a 404.
**Confidence:** High

### RULE-192: CSV export calls any controller method named in the request
**Category:** Policy
**Domain:** Exports
**Priority:** P0
**Source:** `app/Http/Controllers/CSVController.php:101-139`
**Plain English:** `/getCSV?methodName=X` runs public method X of `VoyagerCompassController` and writes its rows to `data_export_<date>.csv`. Nothing restricts the method name to report queries, and any authenticated user can call it.
**Specification:**
  Given any logged-in user
  When they call `/getCSV?methodName=comprobarVigenciaDicionariosAula`
  Then the admin vigencia batch runs and deactivates expired classroom dictionaries (`VoyagerCompassController.php:284-332`) before the CSV step fails on the redirect response
**Parameters:** —
**Edge cases handled:** Zero rows returns null.
**Suspected defect:** **Authorization bypass with state change.** Admin-only, parameterless actions can be triggered by any authenticated user. A whitelist and the `admin.user` middleware are required.
**Confidence:** High

### RULE-193: Classroom PDF only for participants
**Category:** Policy
**Domain:** Exports
**Priority:** P0
**Source:** `app/Http/Controllers/PDFController.php:85-87`
**Plain English:** Only people listed as participants of a classroom dictionary may export it or see its PDF options page (lines 177-184).
**Specification:**
  Given dictionary 40 whose participants are personas {5, 8}
  When persona 9 requests `/pdf/40/getda`
  Then 403 (policy `esParticipante`, `app/Policies/DicAulaPolicy.php:135-141`, matches `dic_aula_participantes.persona_id`)
**Parameters:** —
**Edge cases handled:** —
**Suspected defect:** A non-existent ID passes `null` to the policy, which gives a type error rather than a 404. The owning teacher can export only if they are also a participant row.
**Confidence:** High

### RULE-194: Field catalogue ids (`mst_campos_entrada`)
**Category:** Policy
**Domain:** Fields-Config
**Priority:** P0
**Source:** `database/seeders/MasterTablesDataSeeder.php:49-71`
**Plain English:** The configurable sense fields are a fixed catalogue whose ids are hardcoded in config. The current seeder inserts them in an order that does not match those ids.
**Specification:**
  Given the code constants: 1 categoría gramatical, 2 género, 3 número, 4 temáticas, 5 frase_ejemplo ("Más datos"), 6 vídeo, 7 audio, 8 imagen, 9 lengua/idioma, 10 ejemplo2 ("Ejemplo de uso") (`config/ctes.php:451-462`)
  When a fresh database is seeded with MasterTablesDataSeeder
  Then the ids become 5 "Más datos", **6 "Ejemplo de uso", 7 Vídeo, 8 Audio, 9 Imagen, 10 "Otros lenguajes"**, and the language list (seeded as values of field '9') is attached to "Imagen"
**Parameters:** `ctes.mst_campos_entrada`; `ctes.campos_acepcion` (categoria 1, genero 2, numero 3, tematica 4, idioma 9).
**Edge cases handled:** `firstOrCreate` by name keeps existing production rows untouched.
**Suspected defect:** On a fresh install the field ids for video, audio, image, language and example are off by one compared with the code constants, so per-dictionary visible/required flags apply to the wrong fields.
**Confidence:** Medium — SME/DBA: "Confirm the production ids of mst_campos_entrada match ctes.mst_campos_entrada (5 = Más datos … 10 = Ejemplo de uso)."

### RULE-195: Per-dictionary visible/required entry fields
**Category:** Policy
**Domain:** Fields-Config
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:89-98`
**Plain English:** For every master field (`mst_campos_entrada`), one `dic_aula_campos` row is upserted:
- visible = 1 if checkbox `<fieldId>_visible` was sent;
- obligatorio = 1 if `<fieldId>_obligatorio` was sent;
- estado 1.
**Specification:**
  Given master field 8 (imagen), form sends 8_visible only
  When saving
  Then row (dic, 8): visible 1, obligatorio 0.
**Parameters:** master ids: 1 categoría gramatical, 2 género, 3 número, 4 temáticas, 5 frase ejemplo, 6 vídeo, 7 audio, 8 imagen, 9 lengua/idioma, 10 ejemplo2 (config/ctes.php `mst_campos_entrada`)
**Edge cases handled:** all fields are always written
**Suspected defect:** Nothing enforces "required implies visible", so obligatorio=1 with visible=0 is possible.
**Confidence:** High.
**Also found as:** shard B card "Visible / mandatory settings per field" (`app/Helpers/dicAula_helper.php:89-98`) — folded in as the same behavior.

### RULE-196: Master tables are wiped and ids reset on every seeding run
**Category:** Policy
**Domain:** Master-Data
**Priority:** P0
**Source:** `database/seeders/MasterTablesDataSeeder.php:26-44`
**Plain English:** Running the master seeder deletes every master row and resets auto-increment to 1 before re-inserting. The initial migration runs it automatically.
**Specification:**
  Given a production database where dp_acepciones reference mst_campos_valores
  When MasterTablesDataSeeder runs (directly or via `DatabaseSeeder`)
  Then the DELETE fails on FK constraints; on a database without FK enforcement, ids are regenerated and existing references would point to different values
**Parameters:** the migration calls it at `create_initial_structure.php:541-543`.
**Edge cases handled:** None.
**Confidence:** High.

### RULE-197: Publish/unpublish/delete submission need teacher + coordinator; the entry–dictionary link is not checked
**Category:** Policy
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:555-608`
**Plain English:** Publish, bulk publish and delete submission each check global teacher and coordinator of {id}. None check that the `envio_entrada` was submitted to {id}.
**Specification:**
  Given coordinator C of dic 7, and envio_entrada 500 submitted to dic 8
  When C POSTs /aula/7/entrada/500/publicar with action=publicar
  Then entry 500 is published in dic 7.
**Parameters:** none
**Edge cases handled:** entry or dictionary not found
**Suspected defect:** Cross-dictionary publish or delete of another class's submissions.
**Confidence:** High.

### RULE-198: Which entries are publicly visible in a classroom dictionary
**Category:** Policy
**Domain:** Publication-Moderation
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:1033-1056`
**Plain English:** Consultation shows submissions joined to `dic_aula_entradas` where `envios_entradas.estado` = 3 and `dic_aula_entradas.estado` = 1, ordered A→Z, 10 per page.
**Specification:**
  Given dic 7 with entries estado 3/1 "árbol", estado 2 "casa"
  When students browse
  Then only "árbol" is shown.
**Parameters:** hardcoded 3 and 1; `scrollEntradas` 10
**Edge cases handled:** entries whose personal entry was deleted are skipped and logged (DicAulaController.php:831-858)
**Confidence:** High.
**Also found as:** shard B card "What counts as a visible classroom entry, and paging" (`app/Helpers/dicAula_helper.php:1033-1056`) — folded in as the same behavior.

### RULE-199: Only a docente participant can open the general-comment screen
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/ComentariosController.php:215-271`
**Plain English:** Only a participant with the docente role in the classroom dictionary (a coordinator) can open the screen that writes general comments to students. The screen also tells the teacher the current visibility mode.
**Specification:**
  Given classroom D (id 12), and user U who is a participant of D with `rol_diccionario_id=2` (alumno)
  When U opens `/aula/12/comentarios`
  Then the response is 403. With `rol_diccionario_id=1` (docente) the screen opens, showing the message "visible", "not visible" or "visible before dd/mm/YYYY".
**Parameters:** `ctes.rol.docente='1'`, `ctes.rol.alumno='2'`. The policy is `app/Policies/DicAulaPolicy.php:114-126`. It does not check the participant's `estado`.
**Edge cases handled:** an unknown classroom id is denied.
**Suspected defect:** the list of students to comment on, and the breadcrumb, come from the session's active classroom dictionary (`getDicAulaActivo()`, lines 242, 247-250), not from the `{id}` in the URL. The teacher can see students of classroom A while posting to classroom B. The list also includes teachers. A docente whose participation is disabled is still authorized.
**Confidence:** High. SME question: "Should comment recipients be limited to active alumno participants of this classroom?"

### RULE-200: Only the dictionary owner may act on an entry
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Policies/DiccionarioPersonalPolicy.php:24-28`
**Plain English:** A user may view, edit, hide or delete an entry (and its senses and media) only if the personal dictionary holding that entry belongs to that user.
**Specification:**
  Given user U (persona 42) and entry E in dictionary D, where D.persona_id = 43
  When  U opens, edits, hides or deletes E, or adds/edits/deletes/hides its senses or media
  Then  the request is refused with 403 (`$this->authorize('isOwner', $entrada)`)
**Parameters:** none
**Edge cases handled:** The controller runs this check in dpGetEntradaGET, dpDeleteEntradaGET, dpInsertEntradaGET (existing entry), dpEditEntradaGET/POST, dpOcultarEntradaGET, dpInsertAcepcionPOST, dpEditAcepcionPOST, dpCreateAcepcionGET, dpEditAcepcionGET, dpDeleteAcepcionGET, dpDeleteMedioGET and dpOcultarAcepcionGET. It is NOT run in the up/down endpoints (C02). The `{diccionario_id}` value in the URL is ignored everywhere.
**Suspected defect:** dpDeleteEntradaGET and dpCreateAcepcionGET call the check without first confirming the entry exists, so a missing or deleted id causes a crash (500) instead of a 404 (`DiccionarioPersonalController.php:110-115`, `792-795`).
**Confidence:** High — explicit.

### RULE-201: Seeded accounts with shared hardcoded passwords
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `database/seeders/VoyagerCustomization.php:1932-1947`
**Plain English:** The seeders create SuperAdmin (admin role), "Oficina técnica" (user role) and test docente/alumno/admin accounts with plaintext passwords committed to the public repo.
**Specification:**
  Given a database seeded with VoyagerCustomization or UsersRolesTablesSeeder
  When someone logs in to `/admin` as SuperAdmin
  Then the shared default password works. UsersRolesTablesSeeder re-sets the SuperAdmin password on every run via updateOrCreate (`UsersRolesTablesSeeder.php:251-260`)
**Parameters:**
- Password: `<credential — masked, see database/seeders/VoyagerCustomization.php:1941>` (trivially weak and shared).
- Other passwords: `<credential — masked, see UsersRolesTablesSeeder.php:72,82,92,103>` and `<credential — masked, see UsersTableSeeder.php:25>`.
- Emails, user names and NIF-like identifiers of real staff: `<masked>`. `StressTestUserDataSeeder.php:56` uses a fixed test password `<masked>`.
**Edge cases handled:** None.
**Suspected defect:** This is a security finding. Credentials and personal identifiers are committed to a public repository and seeded into any environment that runs `DatabaseSeeder`.
**Confidence:** High.

### RULE-202: Open self-registration probably grants the admin-panel role
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/Auth/RegisterController.php:60-82`
**Plain English:** /register (via `Auth::routes()`) lets anyone create an account with name, email and password. No role is set in code. Voyager's `add_default_role_on_register=true` / `default_role='user'` (config/voyager.php:13-18) very likely assigns role "user". The seeder gives that role browse_admin, browse_compass and audit read permissions (database/seeders/VoyagerCustomization.php:1906-1929).
**Specification:**
  Given an anonymous visitor
  When  POST /register name="x", email="x@y.z", password(8+ chars, confirmed)
  Then  user created and logged in; likely role 'user', so / redirects to the admin panel
**Parameters:**
- validation: name required, max 255
- email required, valid, unique, max 255
- password required, min 8, confirmed
- voyager `default_role`='user'
**Edge cases handled:** a verification email is sent on Registered (EventServiceProvider), but the routes don't require `verified`.
**Confidence:** Medium — the role assignment happens in vendor Voyager code, which is not in the repo.
**SME question:** Is /register reachable in production (web server or WAF block)? Should self-registration exist at all?

### RULE-203: Application roles docente/alumno; role ids depend on the environment
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `config/ctes.php:68-72`
**Plain English:** Authorization compares role ids against hardcoded docente = 1 and alumno = 2. The seeders create the roles by name, so their ids depend on install order.
**Specification:**
  Given a fresh install via `provision.sh` (voyager:install creates admin = 1 and user = 2, then UsersRolesTablesSeeder creates docente = 3 and alumno = 4 at `UsersRolesTablesSeeder.php:56-65`)
  When `rol_diccionario_id` is compared with `ctes.rol.docente` ('1')
  Then admin-role participants are treated as coordinators and real docentes are not; in production the ids are reportedly docente = 1 and alumno = 2
**Parameters:** `ctes.rol.docente='1'`, `ctes.rol.alumno='2'`. CAUCE web-service role ids: docente Pincel 1, alumnado Pincel 3, docente centro profesorado 4, técnicos educativos 5 (`config/ctes.php:472-477`).
**Edge cases handled:** None.
**Confidence:** Medium — the mismatch is stated in a code comment and is consistent with the seeder order. SME/DBA: "What are the production ids of roles admin, user, docente and alumno?"

### RULE-204: Admin gets every permission; "user" (Oficina técnica) is read-only
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `database/seeders/VoyagerCustomization.php:1899-1929`
**Plain English:** The admin role is synced with all permissions. The "user" role (Oficina técnica) may only open the admin panel, view Compass statistics, and browse/read audits.
**Specification:**
  Given a Voyager user with role "user"
  When they open the admin panel
  Then they can browse_admin, browse_compass, browse/read audits, audits_eventos and audits_eventos_tipos, and nothing else; their menu (menu 2) shows Logs, Vigencia, Audits, Diccionarios curso escolar, Diccionarios personales and Centros curso escolar (`MenuItemsTableSeeder.php:583-789`)
**Parameters:** 8 permission keys. Fixed permission ids 1-5 and 26.
**Edge cases handled:** The seeder deletes all permissions first and rebuilds them.
**Suspected defect:** Oficina técnica menu items point to BREAD routes (`centrosBread`) for which the role has no browse permission.
**Confidence:** High.

### RULE-205: Choosing the user's active classroom dictionary
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:1175-1231`
**Plain English:**
- A teacher defaults to the first connected dictionary.
- A student defaults to the first connected dictionary that is visible to students.
- A dictionary saved in the session wins if it is still among the connected ones and, for students, visible to students.
- Otherwise there is no active dictionary.
**Specification:**
  Given student connected to [A (visible_estudiante '0'), B ('1')] and session holds A
  When getDicAulaActivo runs
  Then B is returned
**Parameters:** `visible_estudiante` = `ctes.estados.activo`.
**Edge cases handled:** No dictionaries returns null.
**Suspected defect:**
- Passes `auth()->user()->userPersona->id` as `persona_id`. `userPersona` may be the pivot rather than Persona; verify.
- The "last joined" order is just query order, not join date.
**Confidence:** High.
**Also found as:** shard A card "Choosing the active classroom dictionary" (`app/Helpers/dicAula_helper.php:1175-1231`) — folded in as the same behavior.

### RULE-206: Numeric role IDs depend on environment
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Providers/AppServiceProvider.php:33-36`
**Plain English:** Policies and controllers compare `role_id` against `ctes.rol.docente` / `ctes.rol.alumno`. Those are 1/2 in pre/pro (config/ctes.php:68-72) but overridden to 3/4 in local. Provisioning (F10), by contrast, looks roles up by name. The same constants are also reused as `dic_aula_participantes.rol_diccionario_id`.
**Specification:**
  Given APP_ENV=production and roles table docente.id=1
  Then  DicAulaPolicy treats `role_id==1` as docente
  Given APP_ENV=local
  Then  docente is `role_id==3`
**Parameters:** `ctes.rol.docente`=1 (local 3), `ctes.rol.alumno`=2 (local 4)
**Edge cases handled:** none
**Suspected defect:** Any DB where role IDs differ from these constants silently breaks teacher permissions. Two role-identification methods (by name and by ID) coexist.
**Confidence:** High.

### RULE-207: Editing a sense does not check it belongs to the entry
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:650-675`
**Plain English:** When a sense is edited, ownership is checked against the posted `entrada_id`, but the posted `acepcion_id` is loaded by id alone. The sense is then saved with `dic_entrada_id` set to the posted entry.
**Specification:**
  Given B owns entry E_B and A owns sense S_A (on entry E_A)
  When  B POSTs an update with entrada_id = E_B and acepcion_id = S_A
  Then  the check passes, S_A's content is overwritten and S_A is moved under E_B
**Parameters:** none
**Edge cases handled:** none. The `orden` value is also taken from the client as-is (it only has to be an integer).
**Suspected defect:** Yes. A user can take over or overwrite another user's sense. Compare dpEditAcepcionGET, which correctly uses `$entrada->dpAcepciones()->find(...)`.
**Confidence:** High.

### RULE-208: Global "teacher" role check
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Policies/DicAulaPolicy.php:43-52`
**Plain English:** A user is a teacher when `users.role_id` equals the configured teacher role id.
**Specification:**
  Given users.role_id = 1 and config ctes.rol.docente = '1'
  When esDocente is evaluated
  Then it returns true. With role_id = 2 it returns false.
**Parameters:** `ctes.rol.docente`='1', `ctes.rol.alumno`='2` (config/ctes.php:68-72). The config comment says a fresh local DB uses 3/4.
**Edge cases handled:** none
**Suspected defect:** There are three different "is teacher" implementations:
- `createDicAula` (DicAulaPolicy.php:68-73) and `usuarioEsDocente` (global_helper.php:1183-1188) look the role up by name 'docente'.
- `esDocente` and `participanteEsCoordinador` use the hardcoded config id.
- In environments where the ids differ, these disagree.
**Confidence:** High.

### RULE-209: Classroom home differs for teachers and students
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `resources/views/diccionario/aula.blade.php:29-65`
**Plain English:** A user without the `createDicAula` ability (any non-teacher) sees the alphabet, search and entry count only if they have an active classroom dictionary; otherwise they see "not joined to any dictionary". Teachers (lines 67-240) always see search plus the management action buttons.
**Specification:**
  Given a student with no active classroom dictionary
  When they open /aula
  Then only the message `aula_no_unido_diccionario` is shown (no search, no alphabet)
**Parameters:** `createDicAula` policy = `users.role_id` equals the id of the role named 'docente' (`app/Policies/DicAulaPolicy.php:68-73`)
**Edge cases handled:** when there are no dictionaries at all, a different info message is shown for teachers and students (lines 7-20).
**Confidence:** High.

### RULE-210: Only the comment's author can delete a general comment
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/ComentariosController.php:366-390`
**Plain English:** A general comment can be deleted only by the user who wrote it. The delete is permanent and runs on a GET request.
**Specification:**
  Given comment 40 with `persona_id`=T
  When user T requests `GET /aula/12/comentario/40/delete`
  Then row 40 is destroyed (`ComentariosHelper.php:397-400`). Any other user gets 403 (`app/Policies/ComentarioGeneralPolicy.php:24-28`).
**Parameters:** none.
**Edge cases handled:** an unknown comment id returns an empty 200 response.
**Suspected defect:** a state-changing GET is CSRF-exposed. The `{id}` classroom value is ignored. Combined with RULE-E10, anyone can first take over authorship and then delete. The model has no soft delete; only the audit log keeps a trace.
**Confidence:** High.

### RULE-211: Saving a general comment has no authorization check
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/ComentariosController.php:314-341`
**Plain English:** The save endpoint does not check that the caller is a coordinator of the classroom, that the student belongs to it, or that the caller wrote the comment being edited. The authorization line is commented out (318).
**Specification:**
  Given user S (a student, logged in) and an existing comment id 40 written by teacher T
  When S POSTs `/aula/12/comentarios` with `hiddenComentarioOriginalId=40` and new text
  Then comment 40 is overwritten, and its author (`persona_id`) becomes S.
**Parameters:** none.
**Edge cases handled:** none.
**Suspected defect:** IDOR / privilege escalation. Any authenticated user can create comments in any classroom for any persona, and can hijack any comment.
**Confidence:** High.

### RULE-212: Participant management needs only the global teacher role
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:702-738`
**Plain English:** Four POST routes enable, disable, promote or demote a participant by participant id. The action is chosen from the route name. The only check is that the caller is a global teacher; the coordinator check is commented out.
**Specification:**
  Given teacher T, who is not a participant of dic 7, and participant id 99 of dic 7
  When T POSTs /aula/7/edit/deshabilitarParticipante/99
  Then participant 99 is disabled.
**Parameters:** route names in `ctes.rutas.aula.*` (config/ctes.php ~385-392)
**Edge cases handled:** see A12/A13
**Suspected defect:**
- No coordinator check.
- The participant id is not checked against dic {id}.
- If none of the four route names match, `$result` is undefined.
**Confidence:** High.

### RULE-213: Only teachers may open the "create classroom dictionary" form
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:61-64`
**Plain English:** The create form is only shown to users whose global role is teacher. The POST that actually creates the dictionary (`create()`, lines 119-175) checks no role at all.
**Specification:**
  Given a user with global role_id 2 (alumno)
  When they GET /aula/create
  Then they get 403. But if they POST /aula/create with valid fields, a dictionary is created and they become its owner and a participant.
**Parameters:** `ctes.rol.docente` = '1'
**Edge cases handled:** none on POST
**Suspected defect:** `create()` (DicAulaController.php:119-175) has no `authorize('esDocente')`, so a student can create classroom dictionaries.
**Confidence:** High. The authorize call is present in `index()` and absent in `create()`.

### RULE-214: One admin vigencia route is reachable without auth
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `routes/web.php:282-299`
**Plain English:** The /admin group sits outside the `auth` group and its `admin.user` middleware is commented out. Each route adds `admin.user` individually, except `/admin/vigencia/comprobar/{diccionario_id}`, which can deactivate a non-vigente dictionary (A29).
**Specification:**
  Given an anonymous request
  When GET /admin/vigencia/comprobar/7
  Then if dic 7 is active but non-vigente, it is set to estado 0.
**Parameters:** none
**Edge cases handled:** none
**Suspected defect:** Missing middleware.
**Confidence:** Medium. VoyagerCompassController has no constructor middleware, but I did not trace any global middleware. SME question: "Is /admin/vigencia/comprobar/{id} protected by any global or Voyager middleware in production?"

### RULE-215: Reordering senses has no ownership check
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:724-774`
**Plain English:** The "move sense up/down" actions swap sense order using only the sense id from the URL. The ownership check is commented out, so any logged-in user can reorder any other user's senses.
**Specification:**
  Given student A owns sense S (orden 2) on entry E, and student B is logged in
  When  B requests `/personal/x/entrada/y/acepcion/{S}/up`
  Then  S and its neighbour swap order values. No 403 is returned.
**Parameters:** none
**Edge cases handled:** none
**Suspected defect:** Yes. This is an IDOR (insecure direct object reference): `// $this->authorize('isOwner', $entrada);` is commented out at lines 733-734, and the down action has no check at all.
**Confidence:** High.

### RULE-216: Access to the edit page needs teacher + coordinator + active dictionary
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:226-239`
**Plain English:** To open the edit page, the user must have the global teacher role (`canEditDic`) and be a coordinator of the dictionary. A dictionary with estado 0 cannot be edited; the user is redirected with "diccionario inactivo".
**Specification:**
  Given dic 7 with estado '0' and a teacher-coordinator user
  When they GET /aula/7/edit
  Then they are redirected to /aula with an error.
**Parameters:** `ctes.estados.inactivo`='0'
**Edge cases handled:** inactive dictionary
**Suspected defect:** `canEditDic` (DicAulaPolicy.php:85-101) ignores the dictionary and only checks the global role.
**Confidence:** High.

### RULE-217: Reading published entries by explicit dicId skips the membership check
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:1496-1547`
**Plain English:** When `/aula/{dicId}/entradas/n/{offset}` (and the by-letter variant, lines 1549-1596) gets a dicId, it loads that dictionary directly. Without a dicId it uses the session's validated active dictionary (A41).
**Specification:**
  Given student S not joined to dic 9
  When S GETs /aula/9/entradas/n/0
  Then the first 10 published entries of dic 9 are returned.
**Parameters:** `ctes.scrollEntradas`=10
**Edge cases handled:** returns `{status:false}` when the page is empty
**Suspected defect:** IDOR. Any dictionary's content can be read, including ones not visible to students.
**Confidence:** High.

### RULE-218: Deleting a classroom dictionary has no authorization and no preconditions
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:1598-1608`
**Plain English:** Any authenticated user can soft-delete any classroom dictionary by sending a GET to /aula/{id}/delete. The published-entries and pending-submissions checks are only advisory (A09).
**Specification:**
  Given dic 7 with 30 published entries
  When any logged-in user GETs /aula/7/delete
  Then dic 7 and its related rows are soft-deleted (A10).
**Parameters:** none
**Edge cases handled:** none
**Suspected defect:**
- No authorization.
- Destructive action on GET (CSRF-able).
- If the delete fails, `delete()` returns null and the controller returns that null as the response.
**Confidence:** High.

### RULE-219: Classroom coordinator = participant whose dictionary role is "docente"
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Policies/DicAulaPolicy.php:114-126`
**Plain English:** A user coordinates a classroom dictionary when they have a `dic_aula_participantes` row for it with `rol_diccionario_id` = teacher id.
**Specification:**
  Given participant row (dic 7, persona 50, rol_diccionario_id 1, estado 0)
  When persona 50 calls a coordinator-only action on dic 7
  Then it is allowed. Participant estado is not checked.
**Parameters:** `ctes.rol.docente`='1'
**Edge cases handled:** soft-deleted participant rows are excluded (Eloquent default)
**Suspected defect:** A coordinator who was disabled (estado 0) keeps coordinator rights.
**Confidence:** High.

### RULE-220: Any logged-in user can trigger a test email
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/MailController.php:62-99`
**Plain English:** `GET /mail/get` (needs auth only, `routes/web.php:260-263`) sends a test email to two hard-coded external addresses with a spoofed From.
**Specification:**
  Given any authenticated user
  When `/mail/get` is requested N times
  Then N emails go to `<masked>` addresses.
**Parameters:** recipients and From are hard-coded (`<masked>`).
**Edge cases handled:** none.
**Suspected defect:** debug endpoint left in production. It can be abused to spam, and it leaks personal addresses in a public repo. `sendPlainTextMail` exists but is unrouted.
**Confidence:** High.

### RULE-221: Landing page after login depends on role
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `routes/web.php:31-38`
**Plain English:** After login, users with the Voyager role `admin` or `user` go to the admin dashboard. Everyone else (students and teachers) goes to their personal dictionary.
**Specification:**
  Given a logged-in user with role "admin"
  When they open "/"
  Then they are redirected to `voyager.dashboard`. For role "alumno" or "docente", the redirect is to `/personal`.
**Parameters:** role names 'admin' and 'user'.
**Edge cases handled:** none.
**Suspected defect:** `HomeController::index` (`HomeController.php:39-42`) is not routed; the `home` view is dead code.
**Confidence:** High.
**Also found as:** shard F card "Landing page by role" (`routes/web.php:30-38`) — folded in as the same behavior.
**Also found as:** shard A card "Home redirect by global role" (`routes/web.php:31-38`) — folded in as the same behavior.

### RULE-222: The submission list for moderation is visible to any teacher
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:527-544`
**Plain English:** The AJAX submission list checks only the global teacher role, while the page that hosts it (lines 486-492) requires coordinator.
**Specification:**
  Given teacher T, not a coordinator of dic 7
  When T POSTs /aula/7/entradas
  Then T receives the dictionary's submissions.
**Parameters:** none
**Edge cases handled:** none
**Suspected defect:**
- Missing coordinator check.
- `getDicAulaEntradasBusquedaEstudiante` is called with 2 arguments but declared with 1, so the selected student is always null.
**Confidence:** High.

### RULE-223: Hiding entries/senses is restricted to the dictionary owner
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Http/Controllers/DicAulaController.php:1258-1283`
**Plain English:** Only the dictionary creator (`dic_aula.persona_id`) can hide or show an entry or a sense. Co-coordinators cannot.
**Specification:**
  Given dic 7 owned by persona 10, and coordinator persona 11
  When persona 11 GETs /aula/7/entrada/5/ocultar
  Then 403.
**Parameters:** none
**Edge cases handled:** none
**Suspected defect:** This is inconsistent with publish/unpublish, which any coordinator can do. Also, the entry/sense is not checked to belong to dic 7.
**Confidence:** High.

### RULE-224: Participant check
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P0
**Source:** `app/Policies/DicAulaPolicy.php:135-141`
**Plain English:** A user is a participant if any participant row exists for them in that dictionary, whatever its role or status. PDFController uses this check for exports.
**Specification:**
  Given persona 50 has a disabled (estado 0) participant row in dic 7
  When they export dic 7 to PDF
  Then the export is authorized.
**Parameters:** none
**Edge cases handled:** none
**Suspected defect:** Disabled participants can still export.
**Confidence:** High.

### RULE-225: A user's visible ("connected") classroom dictionaries
**Category:** Policy
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:225-259`
**Plain English:** A user sees a dictionary if all of these hold:
- their participant row is active;
- the dictionary estado = 1;
- and at least one of: its start year = the current start year; it has a qualifying atemporal row (A28); or it passes `queryVigencia` (lines 1973-1986): vigencia > 1 AND vigencia ≥ elapsed years.
**Specification:**
  Given dic start 2024, vigencia 2, estado 1, current year 2026 (elapsed 2)
  When listing the user's dictionaries
  Then it IS listed (2 ≥ 2), although A26 says it is not vigente.
**Parameters:** none
**Edge cases handled:** disabled participants excluded; inactive dictionaries excluded
**Suspected defect:**
- Off-by-one: `>=` here vs `>` in A26, so a dictionary stays visible one extra year until the admin deactivation (A29) runs.
- Vigencia 1 is excluded by `vigencia > 1`, which is consistent only because the current-year clause covers it.
**Confidence:** High (the code is explicit). SME question: "Is vigencia N meant to cover N school years or N+1?"
**Also found as:** shard B card "Which classroom dictionaries a person sees ("connected")" (`app/Helpers/dicAula_helper.php:225-259`) — folded in as the same behavior.

### RULE-226: Vigencia defaults to one school year
**Category:** Policy
**Domain:** SchoolYear-Vigencia
**Priority:** P0
**Source:** `app/Models/DicAula.php:62-64`
**Plain English:** A new classroom dictionary is valid for 1 school year unless a vigencia is supplied (maximum 10).
**Specification:**
  Given a new DicAula created without vigencia
  When it is saved
  Then vigencia = 1
**Parameters:** model default 1. `ctes.vigencia_max = env VIGENCIA_MAX` default 10 (`config/ctes.php:482`). The DB column is `int NOT NULL` with no default (`2021_06_16_074933_add_vigencia_to_dic_aula.php:17`).
**Edge cases handled:** None at the DB level.
**Confidence:** High.
**Also found as:** shard G card "New classroom dictionary gets vigencia = 1 by default" (`app/Models/DicAula.php:62-64`) — folded in as the same behavior.

### RULE-227: Sense copy keeps only visible senses, with their fields and order
**Category:** Policy
**Domain:** Senses-Acepciones
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:387-402`
**Plain English:** Each visible personal sense is copied into envios_acepciones in its original order, with category, gender, number, language, language word, definition, example, second example and status.
**Specification:**
  Given entry 5 with senses orden 1 (visible), orden 2 (oculta=2), orden 3 (visible)
  When it is sent
  Then envios_acepciones gets 2 rows with orden 1 and 3, keeping the gap
**Parameters:** The source list is filtered to estado=1 and ordered by orden ascending (dpAcepciones_helper.php:220-226).
**Edge cases handled:** Order numbers are not renumbered.
**Confidence:** High.

### RULE-228: Whole-dictionary send: automatic selection rules
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `resources/views/diccionario/dpDiccionario/dpDiccionarioEnviarFormulario.blade.php:300-372`
**Plain English:** When a target dictionary is picked, entries are pre-selected if they were never sent to it, aren't hidden, and pass the server completeness check. Entries with errors (missing required fields per the dictionary config, or no sense) can't be selected. Send is disabled when 0 are selected (lines 388-395). If only one selectable dictionary exists, it is auto-picked (lines 436-440).
**Specification:**
  Given 10 entries: 3 already sent to D, 2 hidden, 1 missing a required field
  When the student picks D
  Then 4 are pre-selected, and the legend shows enviadas 3, ocultas 2, con errores 1
**Parameters:** endpoints `/aula/{id}/entradas.json` and `/aula/{id}/comprobarEntradas`; error types `FALTAN_CAMPOS`, `FALTA_ACEPCION`
**Edge cases handled:** previously sent entries can be re-selected manually. Only one target dictionary is used (the server takes `arrayDiccionariosAulaIds[0]`).
**Confidence:** High.

### RULE-229: No duplicate check; each send makes a new version
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:287-300`
**Plain English:** Sending the same entry to the same dictionary again always creates new dp_envios, envios_entradas and envios_acepciones rows. Earlier submissions, including published ones, are kept.
**Specification:**
  Given entry 5 already sent to D on 2026-09-20 (envios_entradas id 800)
  When the student sends it again on 2026-10-04
  Then a new envios_entradas row (id 950) is created and row 800 remains
**Parameters:** none
**Edge cases handled:** The UI shows past sends of this entry to each dictionary (EnviosController.php:359-396) but does not block a resend. Outside this shard, the classroom view groups by dp_entrada_id and takes max(id) (dicAula_helper.php getDAulaEnviosDiccionario).
**Confidence:** Medium. SME question: "When a student resends an entry, should it replace the pending or published version in the classroom dictionary, or queue a new version for teacher review?"

### RULE-230: Submission window
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dicAula_helper.php:1470-1485`
**Plain English:** A dictionary accepts submissions when `envios_habilitados` is on AND (no start date OR start date ≤ today). The dictionary lists apply the same rule (lines 306-319); "planificado" means enabled with a future start date. There is no end date.
**Specification:**
  Given envios_habilitados '1', envios_fecha_ini 2026-10-10, today 2026-10-04
  When a student submits
  Then the submission is rejected (not accepted until 10 Oct).
**Parameters:** `ctes.estado_envio_habilitado` 0 inactivo / 1 activo / 2 planificado
**Edge cases handled:** null start date
**Suspected defect:**
- `envios_fecha_fin` is never stored or enforced (see A39).
- `DicAula::enviosHabilitados()` (DicAula.php:79-98) returns "activo" for value 2 when the start date is in the future, which is inverted. The form only stores 0/1.
**Confidence:** High.
**Also found as:** shard B card "Dictionary accepts submissions today" (`app/Helpers/dicAula_helper.php:1470-1485`) — folded in as the same behavior.

### RULE-231: Student visibility and submission permission with start date
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `resources/views/diccionario/crearDicAula.blade.php:359-419`
**Plain English:** The teacher sets the dictionary visible or not visible to students. Submissions from personal dictionaries are either allowed (1) or not allowed (0). A start date applies only when allowed: the date input is disabled when submissions are off (`resources/js/dic_aula.js:88-98`).
**Specification:**
  Given `habilitarEnvio = 1` and `envioFecha = 2025-11-01`
  When a student tries to submit on 2025-10-20
  Then the server rejects it (`aceptaEnvios`: enabled and start ≤ today, `dicAula_helper.php:1470-1485`)
**Parameters:** `ctes.visibilidad` 0/1; `ctes.estado_envio_habilitado` 0 inactive / 1 active / 2 scheduled (2 is not offered in the UI)
**Edge cases handled:** with no start date, submissions are open immediately.
**Confidence:** High.

### RULE-232: What is copied into envios_entradas
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:358-366`
**Plain English:** The submitted entry is a snapshot. It records the source personal entry id, the headword text, and the personal entry's status, and it starts with updated_by_persona_id = 0, meaning no teacher has modified it.
**Specification:**
  Given personal entry id 5, entrada="casa", estado=1
  When it is sent under dp_envios id 99
  Then envios_entradas = {dp_envio_id: 99, dp_entrada_id: 5, entrada: "casa", estado: 1, updated_by_persona_id: 0}
**Parameters:** updated_by_persona_id sentinel 0 = unmodified (the view shows a "modified" icon when it is not 0)
**Edge cases handled:** None. Note that estado here mixes two enums: the personal visible value 1 happens to equal estados_envios.enviado=1.
**Confidence:** High.

### RULE-233: A send is all-or-nothing per target dictionary
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpEnvios_helper.php:66-99`
**Plain English:** All entries, senses, topics and media sent to one dictionary in one action are saved together or not at all. With several targets (single-entry path, dpEnvios_helper.php:269-336), each dictionary succeeds or fails on its own.
**Specification:**
  Given a bulk send of 30 entries to D where entry #17 has a sense missing a required field
  When the send runs
  Then 0 entries are stored for D and the user gets one error for D
**Parameters:** none
**Edge cases handled:** On the single-entry path, sending to D1 and D2 where only D2 has submissions disabled gives D1 success and D2 error.
**Confidence:** High.

### RULE-234: Only visible senses are copied into a submission
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P0
**Source:** `app/Helpers/dpAcepciones_helper.php:220-228`
**Plain English:** The senses copied into a submission are only the entry's visible (estado '1') senses that are not deleted, in order. Hidden senses are never sent.
**Specification:**
  Given entry E has senses 1 (visible) and 2 (hidden)
  When  E is sent to a classroom dictionary (`dpEnvios_helper.php:370`, `:618`)
  Then  only sense 1 is copied
**Parameters:** `estados_entrada.visible` = '1'
**Edge cases handled:** none
**Confidence:** High.

### RULE-235: A media file is kept if its entry was ever submitted (protection is defeated)
**Category:** Policy
**Domain:** Uploads-Media
**Priority:** P0
**Source:** `app/Helpers/dpMedios_helper.php:134-207`
**Plain English:** When a personal medium is deleted, the intent is to keep the physical file (and thumbnail) if the parent entry has any submission, because submitted copies point at the same file. Otherwise the file and its thumbnail are deleted.
**Specification:**
  Given entry 12 was submitted (it has ≥1 `envios_entradas` row), and its sense image is `5_12_77_r.jpeg`
  When the owner deletes or replaces that image
  Then by intent the file stays on disk because the classroom copy still uses it. In practice, `destroy()` at line 138 fires the model's `deleting` hook (`app/Models/DiccionarioPersonalAcepcionMedio.php:36-54`), which deletes the file unconditionally first.
**Parameters:** check is `dpEntrada->enviosEntrada->count() > 0`
**Edge cases handled:** Audio has no thumbnail. Errors are logged, not raised.
**Suspected defect:** **Data loss.** Submitted copies share the same `url_interna` (`app/Helpers/dpEnvios_helper.php:418-428`). The unconditional delete in the model hook breaks images, audio and video in published classroom dictionaries whenever the student replaces or deletes the medium. The hook also skips the thumbnail, so in the non-submitted case only the helper removes it.
**Confidence:** High

### RULE-236: Media files are served from public storage without authorization
**Category:** Policy
**Domain:** Uploads-Media
**Priority:** P0
**Source:** `app/Helpers/dpMedios_helper.php:287-302`
**Plain English:** Media URLs are plain `/storage/<type folder>/<file>` paths through the public storage link. Anyone with the URL can download the file, with no login or ownership check. The only protection is the 20-character random part of the name.
**Specification:**
  Given an image stored as `5_12_77_<rand>.jpeg`
  When anyone requests `/storage/dp/medios/imagenes/5_12_77_<rand>.jpeg`
  Then the web server returns the file directly. No Laravel route or policy is involved.
**Parameters:** URL prefix `/storage/`
**Edge cases handled:** None.
**Confidence:** Medium. Inferred from the URL builders; I did not inspect the web-server config. SME question: "Are students' images, audio and video allowed to be publicly downloadable by URL, or must access be limited to the owner and the participants of the classroom dictionaries where they are published?"

### RULE-237: TinyMCE upload has no CSRF protection
**Category:** Policy
**Domain:** Uploads-Media
**Priority:** P0
**Source:** `app/Http/Middleware/VerifyCsrfToken.php:30-32`
**Plain English:** POST /upload (logged-in users, HomeController@upload) has no CSRF protection. It stores the file under `storage/public/tinyUploads` with the client's original filename and no type or size check.
**Specification:**
  Given a logged-in user visits a hostile page
  When  that page auto-posts a file to /upload
  Then  the file is stored publicly under its original name (overwriting any same-named file)
**Parameters:** `ctes.path_medios.tinyUploads`='tinyUploads'
**Edge cases handled:** none
**Confidence:** High.

### RULE-238: Log viewer access and deletion
**Category:** Policy
**Domain:** Admin-Voyager
**Priority:** P1
**Source:** `app/Http/Controllers/Voyager/VoyagerCompassController.php:27-69`
**Plain English:** The app log viewer needs permission `browse_compass` and is allowed in production because `voyager.compass_in_production=true` (config/voyager.php:210). Holders can download, delete one, or delete all log files under storage/logs. Files over 50 MB are not displayed.
**Specification:**
  Given role 'user' (has browse_compass per seeder)
  When  GET /admin/logs?delall=1
  Then  every storage/logs/*.log file is deleted
**Parameters:** MAX_FILE_SIZE=52,428,800 bytes; `compass_in_production`=true
**Edge cases handled:**
- Paths outside storage/logs are rejected.
- However, any existing absolute path is accepted first (pathToLogFile 632-634), allowing download or deletion of arbitrary readable files.
**Suspected defect:** Arbitrary file download or delete through base64 `download`/`del` parameters holding an absolute path. Audit logs can be erased by the non-admin 'user' role.
**Confidence:** High.

### RULE-239: Teacher edit stamps updated_by_persona_id on the submitted entry
**Category:** Policy
**Domain:** Audit
**Priority:** P1
**Source:** `app/Http/Controllers/EnvioAcepcionController.php:129-136`
**Plain English:** After a submitted sense is saved, the parent submitted entry records who last modified it. Any value other than 0 shows a "modified" icon in the classroom list.
**Specification:**
  Given envios_entradas 800 with updated_by_persona_id=0
  When persona 77 saves an edit of one of its senses
  Then envios_entradas 800 has updated_by_persona_id=77
**Parameters:** sentinel 0 = unmodified (entradasAulaListado.blade.php:79)
**Edge cases handled:** Only set if the sense save succeeded. Topic and media changes are not stamped on their own.
**Suspected defect:** The sense is saved before checking that a session persona exists. If it is missing, the change persists unstamped and the user is redirected to the literal path "cas.login", not the named route.
**Confidence:** High.

### RULE-240: Session lifetimes
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P1
**Source:** `app/Cas/CasManager.php:147-173`
**Plain English:** The phpCAS native session cookie lasts 7200 seconds (2 h), HttpOnly, path '/'. The Laravel session lasts `SESSION_LIFETIME` minutes (default 120, config/session.php:34) with the file driver.
**Specification:**
  Given a user inactive for 121 minutes
  When  they next request a page
  Then  the Laravel session has expired and they are sent back to CAS (F01)
**Parameters:**
- `cas_session_lifetime`=7200 s
- `cas_session_name`='cas_session'
- `SESSION_LIFETIME`=120 min
- `SESSION_SECURE_COOKIE`=false
- cookie domain/secure from env `APP_DOMAIN`, `HTTPS_ONLY_COOKIES`
**Edge cases handled:** none
**Confidence:** High.

### RULE-241: HTTPS forced outside local
**Category:** Policy
**Domain:** Auth-CAS-CAUCE
**Priority:** P1
**Source:** `app/Providers/AppServiceProvider.php:37-42`
**Plain English:** In production, preproduction and develop, all generated URLs use https.
**Specification:**
  Given APP_ENV=preproduction
  When  any route URL is generated
  Then  it starts with https://
**Parameters:** environments `production`, `preproduction`, `develop`
**Edge cases handled:** `local` and other environment names are not forced.
**Confidence:** High.

### RULE-242: Importing entries from another dictionary at creation
**Category:** Policy
**Domain:** Classroom-Dictionary
**Priority:** P1
**Source:** `resources/views/diccionario/crearDicAula.blade.php:187-227`
**Plain English:** Only when creating, the teacher can start empty (default) or import the published entries of one of their own dictionaries: current ones, or non-current ones in a collapsible "inactive" group (lines 662-687). The option is disabled if they have none.
**Specification:**
  Given a teacher with dictionary "1ºA 2024-25" holding 120 entries
  When they choose import from it
  Then the new dictionary gets copies of its `dic_aula_entradas` rows (`dicAula_helper.php:129-140`)
**Parameters:** radio `importar` 0/1; `importarRadio = chk_<id>`
**Suspected defect:** On create the list is limited to the user's own dictionaries (`daGetDiccionariosAulaByUserConectado`). The server accepts any `importarRadio` id, so entries can be imported from any dictionary.
**Confidence:** High.

### RULE-243: Master (global) guidelines
**Category:** Policy
**Domain:** Classroom-Dictionary
**Priority:** P1
**Source:** `database/seeders/MstPautasSeeder.php:20-22`
**Plain English:** One global guidelines text exists in `mst_pautas`. The first active row is shown alongside each dictionary's own guidelines.
**Specification:**
  Given mst_pautas has one row with estado 1
  When a teacher creates a classroom dictionary
  Then that text is shown as the master guidelines (`DicAulaController.php:94,249-261`); if none is active, an error 404 is returned
**Parameters:** tipo default 1, estado default 1 (`2021_02_03_124026_create_mst_pautas_table.php:18-20`).
**Edge cases handled:** None.
**Suspected defect:** The per-dictionary switch `dic_aula.pautas_maestras` is never consulted.
**Confidence:** High.

### RULE-244: Specific guidelines (pautas)
**Category:** Policy
**Domain:** Classroom-Dictionary
**Priority:** P1
**Source:** `app/Helpers/dicAula_helper.php:100-108`
**Plain English:** Each dictionary has one `dic_aula_pautas` row (upsert), with texto = `pautasEspecificas` (required) and estado 1. The edit page also needs an active master `Pautas` record, otherwise it returns 404 (DicAulaController.php:249-260).
**Specification:**
  Given no Pautas row with estado 1
  When a coordinator opens /aula/7/edit
  Then 404 {error}.
**Parameters:** estado 1
**Edge cases handled:** a pautas save failure is logged but does not abort the save
**Confidence:** High.

### RULE-245: Student "view comments" on a personal entry
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P1
**Source:** `resources/views/layouts/partials/components/botones/entrada/verComentariosEntrada.blade.php:1-30`
**Plain English:** On a personal entry, the comments icon opens a "no comments" modal when there are 0 student-visible comments for that entry. A comment is student-visible if its dictionary is in mode 1, or in mode 2 with `fecha_envio` before the cut-off date. Otherwise it loads the comments dialog, and the server checks the student owns the entry (`ComentariosController.php:106`).
**Specification:**
  Given entry "casa" with 2 comments in a mode-0 dictionary
  When the student clicks comments
  Then the modal `comentarios_entrada_sincomentarios` is shown
**Parameters:** `daGetComentariosEntradasByEntradaByComentariosVisible` (`ComentariosHelper.php:57-71`)
**Edge cases handled:** the dialog renders all visible comments hidden and shows those of the picked dictionary client-side (`modal-comentarosentrada.blade.php:85-105`).
**Suspected defect:** the unused sibling `botones/entrada/verComentarios.blade.php:15` counts visible comments across **all** dictionaries and users (dead code; don't port).
**Confidence:** High.

### RULE-246: The notification e-mail field is displayed but never saved (suspected defect)
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P1
**Source:** `resources/views/diccionario/crearDicAula.blade.php:349-355`
**Plain English:** The form offers "send submission notices to e-mail" (prefilled from `destinatarioAvisos`), but no server code reads `correoAvisos`. There is no e-mail format validation on either side.
**Specification:**
  Given a teacher enters an address in the notification field
  When saving
  Then nothing is stored and no notices go to that address
**Parameters:** none
**Suspected defect:** yes.
**Confidence:** High for "not persisted" (grep of `app/` for `correoAvisos` returns nothing). SME: "Are submission notification e-mails a required feature?"

### RULE-247: Teacher view lists every general comment of the classroom, ignoring visibility
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P1
**Source:** `app/Helpers/ComentariosHelper.php:374-382`
**Plain English:** On the teacher screen, every general comment of the classroom is listed newest first, whatever the visibility mode. This includes comments by other teachers, which the teacher can see but not delete.
**Specification:**
  Given classroom 12 with `comentarios_visibles=0` and 3 comments
  When the teacher screen renders
  Then all 3 are listed. The view filters them client-side by the selected student.
**Parameters:** none.
**Edge cases handled:** none.
**Confidence:** High.

### RULE-248: Personal-dictionary PDF content and filtering
**Category:** Policy
**Domain:** Exports
**Priority:** P1
**Source:** `app/Helpers/pdf_helper.php:24-125`
**Plain English:** The personal PDF lists entries alphabetically with their senses ordered by `orden`. By default it includes only visible entries and skips hidden senses. Entries left with no senses are omitted. An optional theme filter keeps only senses tagged with a selected theme. Images appear as the 250 px thumbnail.
**Specification:**
  Given entries "casa" (visible, 2 senses: one estado=1, one estado=2) and "árbol" (estado=2 hidden), with showHidden off
  When the PDF is generated
  Then only "casa" appears, with 1 sense
  And with showHidden=1, both entries and all senses appear
  And the file is streamed as `dp_<owner full name>.pdf`
**Parameters:** `ctes.estados_entrada` borrado_logico=0, visible=1, oculta=2; listaTematicas is a comma string whose first element is dropped (`array_shift`), so the client sends a leading comma; time limit 300 s (`PDFController.php:64`)
**Edge cases handled:** Senses without an image get an empty image. Entries with zero remaining senses are skipped.
**Suspected defect:** With showHidden=1 the entry-state filter is removed entirely, so entries or senses with estado=0 (logical delete) are also exported. Without showHidden, senses with estado=0 are still included, because only estado=2 is skipped.
**Confidence:** High

### RULE-249: Classroom PDF theme filter
**Category:** Policy
**Domain:** Exports
**Priority:** P1
**Source:** `app/Helpers/pdf_helper.php:206-277`
**Plain English:** With "only tagged", the classroom PDF keeps published, visible entries but includes only senses tagged with one of the selected themes. Entries with no matching sense are dropped. An empty result redirects with `exportarpdf__no_entradas`.
**Specification:**
  Given "sol" has senses tagged {Astronomía} and {Clima}, and the user selects ",<id Astronomía>"
  When the PDF is generated
  Then "sol" appears with only the Astronomía sense
**Parameters:** Theme IDs come from `envios_acepciones_tematicas.tematica_id` (`pdf_helper.php:288-304`); the first comma-split element is dropped.
**Edge cases handled:** —
**Suspected defect:** On this path the image is the full-size original (line 254), not the thumbnail as in G10 and G14.
**Confidence:** High

### RULE-250: CSV export file retention (today only)
**Category:** Policy
**Domain:** Exports
**Priority:** P1
**Source:** `app/Http/Controllers/CSVController.php:143-156`
**Plain English:** After each export, every `.csv` in the export folder whose name contains "data" but not today's date is deleted. Same-day exports overwrite each other.
**Specification:**
  Given `data_export_2026-10-03.csv` and `dataAll_export_2026-10-04.csv` exist, and today is 2026-10-04
  When any CSV export runs
  Then the 10-03 file is deleted and the 10-04 file is kept (or overwritten)
**Parameters:** date format `Y-m-d`; match on substrings "data" and ".csv"
**Edge cases handled:** —
**Suspected defect:** There is no per-user file name. Two users exporting on the same day overwrite and serve each other's file.
**Confidence:** High

### RULE-251: Per-classroom visibility of optional text fields
**Category:** Policy
**Domain:** Fields-Config
**Priority:** P1
**Source:** `resources/views/layouts/partials/consulta/acepcionAula-texto.blade.php:11-55`
**Plain English:** On a classroom sense, three optional parts are shown only if the dictionary's `dic_aula_campos` row for that field has `visible = 1` and the value is non-empty: "más datos" (`frase_ejemplo`, field 5), the usage example (`ejemplo2`, field 10), and the themes (field id taken from the first theme).
**Specification:**
  Given dictionary D with `dic_aula_campos(mst_campo_entrada_id = 5).visible = 0`
  When a user views a sense whose `frase_ejemplo` is "se usa en…"
  Then the "más datos" text is not shown
**Parameters:** `ctes.mst_campos_entrada`: categoria_gramatical 1, genero 2, numero 3, tematicas_generales 4, frase_ejemplo 5, video 6, audio 7, imagen 8, lengua_idioma 9, ejemplo2 10; `ctes.visibilidad.visible = '1'`
**Edge cases handled:** a missing `dic_aula_campos` row is tolerated only for `ejemplo2` (null check, lines 22-26). For the other fields `->first()->visible` fatals when the row is missing.
**Suspected defect:** This is view-only filtering. Hidden field values still reach the JSON/AJAX endpoints and the PDF (see H11).
**Confidence:** High.

### RULE-252: Language/translation visibility is gated by the wrong field id (suspected defect)
**Category:** Policy
**Domain:** Fields-Config
**Priority:** P1
**Source:** `resources/views/layouts/partials/consulta/acepcionAula-texto.blade.php:35-43`
**Plain English:** The other-language translation line is shown when field id **5** (frase_ejemplo) is visible. The language field is id **9**, and the correct expression is commented out.
**Specification:**
  Given dictionary D with field 9 (lengua_idioma) `visible = 0` and field 5 `visible = 1`
  When viewing a sense with idioma "Inglés" and idioma_palabra "house"
  Then "Inglés: house" is shown, which contradicts the configuration
**Parameters:** hardcoded literal `5`
**Edge cases handled:** the line is shown only if the language description and the translated word are non-empty.
**Suspected defect:** yes, the field id is hardcoded wrong.
**Confidence:** High.

### RULE-253: Per-classroom media visibility (audio, video, image)
**Category:** Policy
**Domain:** Fields-Config
**Priority:** P1
**Source:** `resources/views/layouts/partials/consulta/acepcionAula-medios.blade.php:2-18`
**Plain English:** Audio and video play icons are active only if the sense has that medium and the dictionary marks field 7 (audio) or 6 (video) visible. Otherwise a disabled icon is shown. The image follows the same rule with field 8 (`acepcionAula-imagen.blade.php:1-11`).
**Specification:**
  Given dictionary D with field 6 (video) `visible = 0` and a sense with a video
  When viewing it
  Then the video icon is greyed and not clickable
**Parameters:** field ids 6/7/8; storage path `ctes.path_medios.*`
**Suspected defect:** Files sit under the public `/storage/dp/medios/...` path, so hiding is cosmetic only.
**Confidence:** High.

### RULE-254: Defaults for field visibility and required flags in classroom configuration
**Category:** Policy
**Domain:** Fields-Config
**Priority:** P1
**Source:** `resources/views/diccionario/crearDicAula.blade.php:268-310`
**Plain English:** On create, every entry field is pre-ticked visible and none is required. On edit, ticks reflect `dic_aula_campos.visible` and `obligatorio`. An unticked box is saved as 0 (`dicAula_helper.php:89-97`).
**Specification:**
  Given a teacher creating a new dictionary without touching the field checkboxes
  When they submit
  Then all `mst_campos_entrada` rows get `visible = 1, obligatorio = 0`
**Parameters:** `mst_campos_entrada` (1–10)
**Edge cases handled:** `old()` input is re-applied after a validation failure.
**Confidence:** High.

### RULE-255: "Ejemplo de uso" is a separate optional field
**Category:** Policy
**Domain:** Fields-Config
**Priority:** P1
**Source:** `database/seeders/NuevoCampoEjemploSeeder.php:18-26`
**Plain English:** A second example field, "Ejemplo de uso", was added (2022). It is stored in `ejemplo2` on personal and submitted senses.
**Specification:**
  Given the catalogue lacks "Ejemplo de uso"
  When the seeder runs
  Then it is created active; senses store it in `dp_acepciones.ejemplo2` and `envios_acepciones.ejemplo2` (varchar 255, nullable)
**Parameters:** expected id 10 (`ctes.mst_campos_entrada.ejemplo2`).
**Edge cases handled:** Idempotent.
**Confidence:** High.

### RULE-256: Field display-name renames
**Category:** Policy
**Domain:** Fields-Config
**Priority:** P1
**Source:** `database/seeders/MasterTablesDataSeederUpdate.php:23-27`
**Plain English:** Field "Frase ejemplo" is shown as "Más datos". The language field ("Lengua-idioma", later "Otros lenguajes") is shown as "Lengua".
**Specification:**
  Given a field named "Otros lenguajes"
  When MasterTablesDataSeederUpdate runs
  Then it is renamed "Lengua", keeping its id
**Parameters:** rename map of 3 pairs.
**Edge cases handled:** Skips names that do not exist.
**Confidence:** High.

### RULE-257: Fixed value ids for grammatical category, gender and number
**Category:** Policy
**Domain:** Master-Data
**Priority:** P1
**Source:** `database/seeders/MasterTablesDataSeeder.php:76-86`
**Plain English:** The grammatical category, gender and number values have fixed ids that code relies on.
**Specification:**
  Given a fresh seed
  When values are created
  Then ids are:
  - Category: 1 Adjetivo, 2 Sustantivo, 3 Verbo, 4 Preposición, 5 Conjunción, 6 Adverbio, 7 Determinante, 8 Interjección, 9 Pronombre
  - Gender: 10 Femenino, 11 Masculino, 12 Masculino y femenino, 13 Neutro, 14 No tiene
  - Number: 15 Singular, 16 Plural
  These match `config/ctes.php:216-239`
**Parameters:** `ctes.campos_valores`.
**Edge cases handled:** "No tiene" gender is omitted from rendered attributes (RULE-I16).
**Suspected defect:** `ctes.abreviatura_personalizada` keys 8 and 10 do not match these ids (acknowledged in a code comment) and are unused.
**Confidence:** High.

### RULE-258: Subjects catalogue; "Interdisciplinar" is the default
**Category:** Policy
**Domain:** Master-Data
**Priority:** P1
**Source:** `database/seeders/MasterTablesDataSeeder.php:234-342`
**Plain English:** Subjects follow the Canary curriculum list, with "Interdisciplinar" (id 1) used when no subject is chosen.
**Specification:**
  Given a dictionary saved without areaMateria
  When saved
  Then mst_area_materia_id = 1 (Interdisciplinar) (`dicAula_helper.php:76`)
**Parameters:** about 93 unique names. Duplicates in the source list (e.g. "Cultura Audiovisual", "Literatura canaria") are removed by firstOrCreate.
**Edge cases handled:** None.
**Suspected defect:** The value "Antropología y SociologíaBioestadística" looks like two subjects merged by a missing comma.
**Confidence:** High.

### RULE-259: Course levels catalogue
**Category:** Policy
**Domain:** Master-Data
**Priority:** P1
**Source:** `database/seeders/MasterTablesDataSeeder.php:197-229`
**Plain English:** Levels run from Multiestudio and Infantil through Primaria 1-6, ESO 1-4, Concreción Curricular Adaptada, Bachillerato 1-2, FP Básica and PMAR 1-2, to "Otros". A dictionary's level is optional.
**Specification:**
  Given a dictionary whose "estudio" parameter is empty
  When saved
  Then mst_nivel_estudios_id is NULL
**Parameters:** later fixes add "Infantil" and rename "FP 1" / "Formación profesiona/" to "Formación profesional" (`MasterTablesDataSeederUpdate27may.php:21-27`).
**Edge cases handled:** Idempotent firstOrNew.
**Confidence:** High.

### RULE-260: Generic active/inactive status flag
**Category:** Policy
**Domain:** Master-Data
**Priority:** P1
**Source:** `config/ctes.php:51-54`
**Plain English:** Every record's `estado` uses '0' for inactive and '1' for active, unless the table defines a richer state set.
**Specification:**
  Given a master value (for example "Primaria") with estado '1'
  When the catalogue is offered or counted
  Then it is treated as active; with estado '0' it is treated as inactive
**Parameters:** `ctes.estados.inactivo='0'`, `ctes.estados.activo='1'`. Every seeder inserts estado = 1.
**Edge cases handled:** None. The `fecha_baja` deactivation dates on master tables are never used.
**Confidence:** High — explicit constant used throughout.

### RULE-261: Education stages; "Multienseñanza" is the default
**Category:** Policy
**Domain:** Master-Data
**Priority:** P1
**Source:** `database/seeders/MasterTablesDataSeeder.php:176-192`
**Plain English:** Stages are Multienseñanza (1, any stage), Primaria, ESO and Formación profesional. A dictionary with no stage is Multienseñanza.
**Specification:**
  Given a new dic_aula with no mst_ensenanza_id
  When inserted
  Then the column default 1 (Multienseñanza) applies (`create_initial_structure.php:211`)
**Parameters:** `ctes.multi.multienseñanza='0'` is a UI sentinel and is not the DB id.
**Edge cases handled:** None.
**Confidence:** High.

### RULE-262: Classroom dictionary types
**Category:** Policy
**Domain:** Master-Data
**Priority:** P1
**Source:** `database/seeders/MasterTablesDataSeeder.php:157-171`
**Plain English:** A classroom dictionary is either a "Diccionario general" (1) or "Canarismos" (2). The app defaults to 1.
**Specification:**
  Given a dictionary created without a type
  When saved
  Then mst_tipo_dic_id = 1
**Parameters:** `tipo_diccionario` UNIQUE varchar(150). Default comes from `dicAula_helper.php:73`.
**Edge cases handled:** None.
**Confidence:** High.

### RULE-263: Teacher topic edit is replace-all from a comma list with a leading comma
**Category:** Policy
**Domain:** Publication-Moderation
**Priority:** P1
**Source:** `app/Helpers/envioAcepcion_helper.php:21-36`
**Plain English:** On save, all topics of the submitted sense are deleted and recreated from the posted list. The list is expected to start with a comma, so its first element is dropped.
**Specification:**
  Given listaTematicas=",12,40"
  When the edit is saved
  Then topics = {12, 40}
  Given listaTematicas="12,40" (no leading comma)
  Then topics = {40}, and 12 is silently lost
**Parameters:** none
**Edge cases handled:** An empty or missing list removes all topics. Ids are not validated against the topic master list.
**Confidence:** High.

### RULE-264: Inviting a teacher
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P1
**Source:** `resources/views/layouts/partials/components/modal-formulario-invitar-docente.blade.php:3-31`
**Plain English:** A teacher with an active dictionary can e-mail another teacher an invitation containing the dictionary's join code. The code comes from a hidden form field.
**Specification:**
  Given coordinator C of D (code "K3F9QZ")
  When C submits e-mail, name and surname
  Then an e-mail with code "K3F9QZ" is sent (server: `esCoordinador` check plus required/max 150 each, `DicAulaController.php:349-363`)
**Parameters:** max lengths 150/150/150/100
**Suspected defect:** `codigo_dic` is client-supplied (the code of another dictionary could be sent). E-mail format isn't validated.
**Confidence:** High.

### RULE-265: Admins are sent to /admin, not the personal dictionary
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P1
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:1165-1185`
**Plain English:** Users with role `admin` are redirected to `/admin`. If the current user's dictionary cannot be loaded (session missing the persona), the user is sent to the CAS login.
**Specification:**
  Given a user whose role name is "admin"
  When  they open `/personal`
  Then  they are redirected to `/admin`
**Parameters:** role name `admin` (`usuarioEsAdministrador`, `app/Helpers/global_helper.php:1192-1196`)
**Edge cases handled:** an exception while loading the dictionary sends the user to `cas.login`.
**Confidence:** High.

### RULE-266: Vigencia maximum / minimum
**Category:** Policy
**Domain:** SchoolYear-Vigencia
**Priority:** P1
**Source:** `config/ctes.php:479-484`
**Plain English:** A dictionary's vigencia can be set up to VIGENCIA_MAX school years (default 10). On edit, the minimum is the number of years already elapsed (`DicAulaController.php:263-266`).
**Specification:**
  Given VIGENCIA_MAX unset and a dictionary created in 2021, edited in school year 2024
  When the edit form is built
  Then vigenciaMin = 3 and vigenciaMax = 10
**Parameters:**
- `vigencia_max` = env `VIGENCIA_MAX`, default '10'
- `tematicas_max` = env `TEMATICAS_MAX`, default '10' (not used in this shard)
**Edge cases handled:** None. The helper persists `$params['vigencia']` unvalidated (`dicAula_helper.php:87`).
**Confidence:** Medium — SME: "Is the vigencia range enforced server-side, or only by the form?" The controller validator should be confirmed by the controller shard.

### RULE-267: Validity and school-year config defaults
**Category:** Policy
**Domain:** SchoolYear-Vigencia
**Priority:** P1
**Source:** `config/ctes.php:482-484`
**Plain English:** A classroom dictionary's validity is at most `VIGENCIA_MAX` years (default 10). The school year starts on `INICIO_CURSO` (day/month, default 30/8). Topics are capped at `TEMATICAS_MAX` (default 10).
**Specification:**
  Given no env overrides
  Then  vigencia_max=10, inicio_curso='30/8/2000' (only day/month used), tematicas_max=10
**Parameters:** as above (string values)
**Edge cases handled:** these are only definitions here; they are enforced in other shards' helpers.
**Confidence:** High.

### RULE-268: Which sense fields are saved (tipologia is dropped)
**Category:** Policy
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `app/Helpers/dpAcepciones_helper.php:77-104`
**Plain English:** A sense stores these fields:
  - entry
  - orden
  - grammatical category
  - gender
  - number
  - language and the word in that language
  - definition
  - example sentence (`frase_ejemplo`)
  - second example (`ejemplo2`)
  - estado
**Specification:**
  Given the form sends tipologia_id = 7
  When  the sense is saved
  Then  tipologia_id is not stored (the controller sets it at line 542, but `dpAcepcionSave` never copies it)
**Parameters:** combo lists come from mst_campos_valores: categoria = 1, genero = 2, numero = 3, tematica = 4, idioma = 9 (`ctes.campos_acepcion`)
**Edge cases handled:** returns false if the save fails.
**Suspected defect:** tipologia_id is silently dropped.
**Confidence:** High — SME: is "tipología" a field that should be kept?

### RULE-269: Saving tags replaces all of a sense's tags
**Category:** Policy
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `app/Helpers/dpAcepciones_helper.php:119-134`
**Plain English:** Saving a sense deletes all its tags and inserts the ones in `listaTematicas`, a comma list that starts with a comma (",4,9").
**Specification:**
  Given sense S has tags {4, 9}
  When  it is saved with listaTematicas = ",9,12"
  Then  S has tags {9, 12}
**Parameters:** none
**Edge cases handled:** The first element is always dropped because the list starts with a comma.
**Suspected defect:**
  - Ids are not checked as integers or as tag values (campo 4).
  - Duplicate ids are not removed.
  - If the list arrives without the leading comma, the first tag is lost.
  - On edit this runs even when the C03 check is bypassed.
**Confidence:** High.

### RULE-270: Topics (temáticas) are copied with each sense
**Category:** Policy
**Domain:** Senses-Acepciones
**Priority:** P1
**Source:** `app/Helpers/dpEnvios_helper.php:459-488`
**Plain English:** The submitted sense gets the same topic tags as the personal sense. Any existing topics on the submitted sense are deleted first.
**Specification:**
  Given a personal sense tagged with topics {12, 40} (mst values)
  When it is copied to envio_acepcion 300
  Then envios_acepciones_tematicas = {(300,12), (300,40)}
**Parameters:** Topics come via the dp_acepciones_tematicas pivot to CampoValor ids.
**Edge cases handled:** The topic's own status is not filtered.
**Confidence:** High.

### RULE-271: Only classrooms with sending active are offered
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P1
**Source:** `app/Helpers/dpEntradas_helper.php:629-629`
**Plain English:** The classroom dictionaries offered on personal-dictionary pages are those the user has joined whose sending status is active ('1'), not inactive ('0') or scheduled ('2').
**Specification:**
  Given the user has joined classrooms X (sending active) and Y (sending scheduled)
  When  they view their personal dictionary
  Then  only X is offered as a destination
**Parameters:** `ctes.estado_envio_habilitado` = {inactivo: '0', activo: '1', planificado: '2'}
**Edge cases handled:** none
**Confidence:** Medium — the filter logic lives in `daGetDiccionariosAulaByUserConectado` (`dicAula_helper.php:195`, another shard). SME: does "active" also depend on vigencia/start date?

### RULE-272: Only visible personal entries are copied; a hidden entry yields an empty submission
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P1
**Source:** `app/Helpers/dpEnvios_helper.php:288-301`
**Plain English:** The submission header is created first. The entry is copied only if it is visible (1); a hidden entry leaves an empty header, and the user is still told it succeeded.
**Specification:**
  Given personal entry "perro" with estado=2 (oculta)
  When it is sent to dictionary 12
  Then dp_envios has a new row, envios_entradas has none, and the user sees "entry sent successfully"
**Parameters:** estados_entrada.visible=1
**Edge cases handled:** Same logic in bulk at dpEnvios_helper.php:87-92. An empty bulk selection also creates an empty header.
**Suspected defect:** Orphan or empty dp_envios rows, and a misleading success message.
**Confidence:** High.

### RULE-273: Bulk send goes only to the first target dictionary
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P1
**Source:** `app/Http/Controllers/EnviosController.php:98-140`
**Plain English:** Even if several classroom dictionary ids are posted, the selected entries are sent only to the first one.
**Specification:**
  Given listaDiccionariosAula="12,15"
  When the bulk send runs
  Then a submission is created only for dictionary 12
**Parameters:** none
**Edge cases handled:** If no ids are given, the user gets the error `envio_error_no_diccionarios`.
**Confidence:** High. SME question: "Is bulk sending meant to target exactly one classroom dictionary?" (The UI seems to enforce one.)

### RULE-274: Media are copied by reference (shared file), regardless of status
**Category:** Policy
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `app/Helpers/dpEnvios_helper.php:418-441`
**Plain English:** Each media row of the personal sense (image, audio, video, external URL) is duplicated into envios_acepciones_medios pointing to the same stored file. The file itself is not copied.
**Specification:**
  Given a personal sense with an image whose url_interna is "img_5_9.jpg" and estado=0
  When it is sent
  Then envios_acepciones_medios gets {tipo_medio: 1, nombre, url_externa, url_interna: "img_5_9.jpg", estado: 0}
**Parameters:** tipos_medios: imagen=1, audio=2, video=3
**Edge cases handled:** No status filter: inactive media are copied too (dpMedios_helper.php:507-511).
**Suspected defect:** If the personal side later deletes the physical file, the classroom copy breaks. RULE-D24 shows the reverse direction is deliberately protected.
**Confidence:** High.

### RULE-275: Storage folder and file naming convention
**Category:** Policy
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `app/Helpers/dpMedios_helper.php:105-120`
**Plain English:** Files are stored under public storage in a folder per type. They are named `<persona_id>_<entrada_id>_<acepcion_id>_<20 random chars>.<ext>`. The original filename is kept only in the database.
**Specification:**
  Given logged-in persona 5, entry 12, sense 77, and a JPEG upload
  When it is saved
  Then the file is written to `storage/app/public/dp/medios/imagenes/5_12_77_<20 random alphanumeric>.jpeg`, and that name goes into `url_interna`
**Parameters:** `ctes.path_medios`: imagen="dp/medios/imagenes", audio="dp/medios/audios", video="dp/medios/videos" (`config/ctes.php:96-103`); random length 20
**Edge cases handled:** On Windows, slashes in the full path are converted (`dpMedios_helper.php:236-239`).
**Confidence:** High

### RULE-276: One media item per type on a submitted sense; an upload replaces the old one
**Category:** Policy
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `app/Helpers/envioAcepcion_helper.php:70-114`
**Plain English:** Uploading an image, audio or video on a submitted sense replaces any existing media of that type. The new media is active, and image and video get thumbnails.
**Specification:**
  Given submitted sense 300 already has an image row (id 9)
  When a teacher uploads a new valid image
  Then row 9 is deleted (the file is kept), a new row {tipo_medio: 1, nombre: original filename, url_interna: stored name, estado: 1} is created, and an image thumbnail is generated
**Parameters:** tipos_medios 1/2/3; ctes.imagen_thumbnail_tamano; ctes.video_thumbnail_sufijo
**Edge cases handled:** Invalid uploads are skipped silently (controller lines 142-162).
**Confidence:** High.

### RULE-277: Media upload accepted only if valid; failures are hidden
**Category:** Policy
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:566-595`
**Plain English:** A sense may get one image, one audio and one video per save. Each is processed only if the upload is valid. Any failure is logged and the user still sees success.
**Specification:**
  Given a valid image upload `medio_imagen`
  When  the sense is saved
  Then  a media record of type '1' is created for (entry, sense)
**Parameters:**
  - `ctes.tipos_medios` = {imagen: '1', audio: '2', video: '3'}
  - Client-side size limit `UPLOAD_MAXSIZE` default 200M (`ctes.dropify`)
**Edge cases handled:** Invalid files are skipped silently. The edit path (lines 683-703) does the same but without try/catch.
**Confidence:** High.

### RULE-278: Deleting submitted media removes the database row only; the file is kept
**Category:** Policy
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `app/Helpers/envioAcepcion_helper.php:152-160`
**Plain English:** Removing media from a submitted sense deletes only the database record. The stored file is kept because the personal dictionary probably still uses the same file.
**Specification:**
  Given envios_acepciones_medios row 9 pointing to "img_5_9.jpg", which is shared with a personal sense
  When it is deleted
  Then row 9 is gone and "img_5_9.jpg" stays in storage
**Parameters:** none
**Edge cases handled:** None. There is no reference counting, so orphaned files build up.
**Confidence:** High.

### RULE-279: Voyager media manager accepts every file type
**Category:** Policy
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `config/voyager.php:212-214`
**Plain English:** The admin media manager accepts any file type (`allowed_mimetypes='*'`). Upload, move, delete, folder creation and rename stay at Voyager defaults.
**Specification:**
  Given an admin
  When  they upload a .php file through Media
  Then  it is accepted
**Parameters:** `allowed_mimetypes`='*'; storage disk `FILESYSTEM_DRIVER` (default 'public' for Voyager, 'local' app-wide)
**Edge cases handled:** none
**Confidence:** High.

### RULE-280: Media delete is limited to the owner's own sense
**Category:** Policy
**Domain:** Uploads-Media
**Priority:** P1
**Source:** `app/Http/Controllers/DiccionarioPersonalController.php:1014-1036`
**Plain English:** A media item can be deleted only if it belongs to a sense of an entry the user owns.
**Specification:**
  Given medio M belongs to sense S of entry E owned by the user
  When  the user deletes M
  Then  M is removed and the sense edit form is shown again
**Parameters:** none
**Edge cases handled:** A mismatch anywhere in the chain → redirect with "URLmala".
**Confidence:** High.

### RULE-281: Admin data listing is empty until a search
**Category:** Policy
**Domain:** Admin-Voyager
**Priority:** P2
**Source:** `app/Http/Controllers/Voyager/VoyagerBaseController.php:71-164`
**Plain English:**
- With no query parameters, listings (except menus) show only rows with `created_at` NULL, which in practice is nothing until the admin searches.
- Searches support several key/value/filter triples. 'equals' means exact match; anything else means contains (LIKE %v%).
- Ordering is by an allowed field, default descending.
**Specification:**
  Given GET /admin/centros with no params
  Then  empty list
  Given key0=cod_centro, filter0=equals, value0=38000001
  Then  rows with cod_centro='38000001'
**Parameters:** `ctes.voyager.bread.views.params_number` (key not present in ctes.php, so null)
**Edge cases handled:** a missing value index stops the loop.
**Suspected defect:** `key{i}` (column name) comes from the user and is not checked against the table's columns.
**Confidence:** High.

### RULE-282: Default site settings (not applied)
**Category:** Policy
**Domain:** Admin-Voyager
**Priority:** P2
**Source:** `database/seeders/VoyagerCustomization.php:2153-2180`
**Plain English:** The intended settings are site title "LexiCán" and admin title "Panel de control", but the values are never written.
**Specification:**
  Given setting site.title exists
  When VoyagerCustomization runs
  Then only display_name is updated; value is not, because the third argument to updateOrCreate is ignored
**Parameters:** site.title, admin.title, admin.description.
**Edge cases handled:** None.
**Suspected defect:** Values are not set.
**Confidence:** High.

### RULE-283: Health check always reports OK
**Category:** Policy
**Domain:** Admin-Voyager
**Priority:** P2
**Source:** `app/Http/Controllers/HealthCheckController.php:32-47`
**Plain English:** The health check returns `{"ok":"ok"}` with status 200 and performs no checks.
**Specification:**
  Given the database is down
  When the health check runs
  Then it still returns 200.
**Parameters:** none.
**Edge cases handled:** none.
**Suspected defect:** this controller is not routed. The live endpoints `/health-check` and `/hc` are closures at `routes/web.php:21-26` that return `{status:up}`.
**Confidence:** High.

### RULE-284: Cookie-consent acceptance lasts 3 months
**Category:** Policy
**Domain:** Audit
**Priority:** P2
**Source:** `app/Http/Controllers/HomeController.php:207-219`
**Plain English:** Accepting the cookie banner sets the cookie `lxcn_acceptcookies=true` for 3 calendar months. The banner is hidden while the cookie exists.
**Specification:**
  Given accept is pressed on 2024-01-15
  When `/acceptCookies` (public route) is called
  Then the cookie expires about 2024-04-15.
**Parameters:** "+3 month", cookie name `lxcn_acceptcookies`.
**Edge cases handled:** none.
**Confidence:** High.

### RULE-285: Guidelines (pautas) defaults and display
**Category:** Policy
**Domain:** Classroom-Dictionary
**Priority:** P2
**Source:** `resources/views/diccionario/crearDicAula.blade.php:318-336`
**Plain English:** A new dictionary's guidelines are pre-filled from the active master guideline. "Restablecer" restores the master text. Guidelines are required server-side. The guidelines side panel is shown only when a classroom dictionary is active (`diccionario/aula/pautas.blade.php:1-33`).
**Specification:**
  Given the master guideline text T
  When a teacher creates a dictionary without editing the guidelines
  Then `dic_aula_pautas.texto = T`
**Parameters:** master `pautas` row with `estado = 1`
**Confidence:** High.

### RULE-286: Management menus are disabled until a classroom dictionary is active
**Category:** Policy
**Domain:** Classroom-Dictionary
**Priority:** P2
**Source:** `resources/views/diccionario/aula.blade.php:103-147`
**Plain English:** "Manage submitted entries" and "Manage classroom dictionary" are greyed out with a tooltip, and their submenus aren't rendered, when there is no active dictionary.
**Specification:**
  Given a teacher with no active classroom dictionary
  When they view /aula
  Then both buttons are greyed out with the tooltip `tooltip_faltaDiccionarioAula`
**Parameters:** none
**Confidence:** High.

### RULE-287: Spanish alphabet for the A–Z index
**Category:** Policy
**Domain:** Classroom-Dictionary
**Priority:** P2
**Source:** `app/Helpers/global_helper.php:682-685`
**Plain English:** The letter index is A–N, Ñ, O–Z (27 letters).
**Specification:**
  Given the index is rendered
  When letters are listed
  Then Ñ appears between N and O
**Parameters:** None.
**Edge cases handled:** None.
**Confidence:** High.

### RULE-288: Generic HTML mail sending
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P2
**Source:** `app/Helpers/mail_helper.php:26-55`
**Plain English:** All app email goes through one helper. Recipients are required; CC, BCC and From are optional, and From falls back to the configured default.
**Specification:**
  Given recipients [a], CC null, BCC null, From "x@…"
  When the helper is called
  Then the mail is sent to [a] from "x@…" with the given subject and view.
**Parameters:** none.
**Edge cases handled:** empty CC and BCC.
**Suspected defect:** `$attached` is not passed into the closure (`use` list, line 40), so attachments are silently never added. The docblock claims an env-configured From cannot be overridden, but the code overrides it whenever From is passed.
**Confidence:** High.

### RULE-289: Student comments dialog: which dictionaries are selectable
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P2
**Source:** `resources/views/comentarios/comentariosDiccionarioVer.blade.php:61-84`
**Plain English:** A dictionary is selectable (green) only if it has at least one student-visible comment, general or per entry. Otherwise it's grey with "sin comentarios".
**Specification:**
  Given a student joined to D1 (has visible comments) and D2 (none)
  When they open "Ver comentarios"
  Then D1 is selectable and D2 is grey
**Parameters:** `estadoActual` computed in `ComentariosHelper.php:22-43`
**Suspected defect:** `modal-comentarosentrada.blade.php:49` reuses the "envíos deshabilitados" tooltip for the same state (wrong text).
**Confidence:** High.

### RULE-290: "Has comments" check
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P2
**Source:** `app/Http/Controllers/ComentariosController.php:146-200`
**Plain English:** When a student clicks "view comments", the app shows a "no comments" modal if the student has zero visible comments across connected classrooms. Otherwise it redirects to the comments page.
**Specification:**
  Given P has 0 visible general comments and 0 visible entry comments
  When the comments button is pressed
  Then a modal with the "no comments" message is returned. With at least one comment, JSON `{redirect: /personal/{dp_id}/comentarios}` is returned.
**Parameters:** none.
**Edge cases handled:** none.
**Confidence:** High.

### RULE-291: Comment-visibility banner misreports mode 2
**Category:** Policy
**Domain:** Comments-Notifications
**Priority:** P2
**Source:** `resources/views/layouts/partials/components/modal-formulario-comentarEntrada.blade.php:26-28`
**Plain English:** The teacher's comment dialog says comments are "visibles" only when the mode equals 1. Mode 2 ("visible before date") is reported as "no visibles".
**Specification:**
  Given dictionary D in mode 2
  When the teacher opens the comment dialog
  Then the banner says "no visibles" (incorrect)
**Parameters:** `ctes.comentarios_visibles` 0/1/2
**Suspected defect:** yes.
**Confidence:** High.

### RULE-292: PDF back-cover credits
**Category:** Policy
**Domain:** Exports
**Priority:** P2
**Source:** `resources/views/layouts/partials/pdf/daContraPortada.blade.php:9-36`
**Plain English:** The back cover lists the owner and then other coordinators (`rol_diccionario_id = 1`). Next it lists all other participants. Anyone whose full name equals the owner's is skipped.
**Specification:**
  Given dictionary D (owner "A B") with a co-coordinator and 20 students
  When the PDF is generated
  Then "COORDINADORES" lists A B and the co-coordinator, and "PARTICIPANTES" lists the 20 students
**Parameters:** `ctes.rol.docente = 1`
**Suspected defect:** Deduplication is by name string, so a homonym of the owner is dropped. Disabled participants (`estado = 0`) are not filtered out.
**Confidence:** High.

### RULE-293: PDF export options
**Category:** Policy
**Domain:** Exports
**Priority:** P2
**Source:** `resources/views/layouts/partials/dpOptionsPDFform.blade.php:9-33`
**Plain English:** The personal PDF can optionally include hidden entries (the label shows the count) and can be limited to entries tagged with selected themes. The classroom form (`aulaOptionsPDFform.blade.php:9-33`) has the "include hidden" option commented out and offers only the theme filter.
**Specification:**
  Given a personal dictionary with 3 hidden entries
  When the options page loads
  Then the "mostrar ocultas (3)" checkbox is offered, unticked by default
**Parameters:** form fields `showHidden = 1`, `exportOnlyTagged = 1`, plus the theme list
**Confidence:** High.

### RULE-294: Theme and language catalogues
**Category:** Policy
**Domain:** Master-Data
**Priority:** P2
**Source:** `database/seeders/MasterTablesDataSeederUpdate.php:69-76`
**Plain English:** Themes (field 4) are a list of 42 school and Canary-culture topics. Languages (field 9) are an ISO-style list where alemán, notación científica, inglés, español, francés and italiano are stored with a leading space so they sort first. "dialecto canario" is removed from languages because it is a theme.
**Specification:**
  Given a language value "Inglés"
  When the update seeder runs
  Then it becomes " inglés", sorting before "afar"
**Parameters:** themes added 2022: Flora canaria, Literatura canaria, Personaje ilustre canario (:166). Maximum themes per sense TEMATICAS_MAX = 10.
**Edge cases handled:** The leading space also stops case-insensitive `firstOrCreate` from merging with the themes "Inglés", "Francés" and "Alemán".
**Confidence:** Medium — the purpose of the leading space is inferred from the code comment "destacar". SME: "Should the highlighted languages be modelled as an explicit sort order instead of a leading space?"

### RULE-295: Study-level ordering (Multiestudio, Infantil first)
**Category:** Policy
**Domain:** Master-Data
**Priority:** P2
**Source:** `app/Helpers/global_helper.php:1309-1325`
**Plain English:** Study levels are listed with "Multiestudio" first, "Infantil" second, then the rest.
**Specification:**
  Given levels [Primaria, Infantil, Multiestudio, ESO]
  When listed
  Then [Multiestudio, Infantil, Primaria, ESO]
**Parameters:** Names 'Multiestudio' and 'Infantil'.
**Edge cases handled:** None.
**Suspected defect:** If 'Multiestudio' does not exist, 'Infantil' disappears from the list (it is excluded from the base query and only re-added when both exist).
**Confidence:** Medium — SME: "Should Infantil always be offered?"

### RULE-296: Tag filter list is built from all users' tags
**Category:** Policy
**Domain:** Personal-Dictionary
**Priority:** P2
**Source:** `app/Helpers/dpEntradas_helper.php:636-644`
**Plain English:** The tag filter list shows every tag (mst_campos_valores, field 4) used on any sense, by any user, sorted by description.
**Specification:**
  Given user A uses the tag "Animales" and user B uses no tags
  When  B opens their personal dictionary
  Then  B's filter list still offers "Animales"
**Parameters:** `ctes.campos_acepcion.tematica` = 4
**Edge cases handled:** none
**Suspected defect:** A comment says "only tags in the current user's dictionary", but the code queries all rows (`DiccionarioPersonalAcepcionTematica::all()`). It also loads the whole table into memory.
**Confidence:** High.

### RULE-297: "Modified by teacher" marker
**Category:** Policy
**Domain:** Publication-Moderation
**Priority:** P2
**Source:** `resources/views/layouts/partials/consulta/entradasAulaListado.blade.php:79-81`
**Plain English:** A "modified" icon is shown when `envios_entradas.updated_by_persona_id !== 0`.
**Specification:**
  Given a submission a teacher never edited (`updated_by_persona_id` NULL)
  When listed
  Then the icon still appears, because a strict comparison with 0 is true for NULL or the string "0"
**Parameters:** none
**Suspected defect:** probable false positives.
**Confidence:** Medium. SME: "What is the default value of `updated_by_persona_id` for unedited submissions?"

### RULE-298: The esAdministrador policy is broken
**Category:** Policy
**Domain:** Roles-Authorization
**Priority:** P2
**Source:** `app/Policies/DicAulaPolicy.php:54-58`
**Plain English:** It is meant to check the role name 'admin', but it calls `$user()` as a function, which throws an Error.
**Specification:**
  Given any user
  When esAdministrador is evaluated
  Then a fatal error occurs.
**Parameters:** role name 'admin'
**Edge cases handled:** none
**Suspected defect:** Yes (currently unused in the shard).
**Confidence:** High.

### RULE-299: List of a user's non-vigente dictionaries
**Category:** Policy
**Domain:** SchoolYear-Vigencia
**Priority:** P2
**Source:** `app/Helpers/dicAula_helper.php:2075-2088`
**Plain English:** The "non-vigente" list contains dictionaries where the user is an active participant and the dictionary estado = 0.
**Specification:**
  Given persona 50 active in dic 7 (estado 0)
  When listing
  Then dic 7 appears as non-vigente.
**Parameters:** none
**Edge cases handled:** none
**Confidence:** High.

### RULE-300: Sense up/down arrows
**Category:** Policy
**Domain:** Senses-Acepciones
**Priority:** P2
**Source:** `resources/views/layouts/partials/consulta/dpEntradaAcepcion.blade.php:10-25`
**Plain English:** "Move up" is hidden for sense `orden = 1`; "move down" is hidden when `orden` equals the number of senses.
**Specification:**
  Given an entry with 3 senses
  When viewed
  Then sense 1 shows only "down", sense 3 only "up", and sense 2 both
**Parameters:** none
**Edge cases handled:** if `orden` has gaps, the last sense may still show "down".
**Confidence:** High.

### RULE-301: Send-entry dialog choice depends on the number of joined dictionaries
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P2
**Source:** `app/Http/Controllers/EnviosController.php:338-414`
**Plain English:** When sending one entry, the dialog depends on how many dictionaries the student has joined (counting active, inactive and planned).
**Specification:**
  Given the student is joined to 0 dictionaries
  Then the "join a dictionary" dialog is shown
  Given 1 dictionary
  Then a confirmation dialog is shown, with this entry's previous sends to that dictionary
  Given 3 dictionaries
  Then a multi-select dialog is shown, with each dictionary's previous sends of this entry
**Parameters:** none
**Edge cases handled:** Only the entry's owner can open it (isOwner). For bulk send (lines 447-481), 0 dictionaries gives the join dialog; otherwise the user is redirected to the selection page.
**Confidence:** High.

### RULE-302: Send screen lists joined dictionaries by sending status; flag for non-teachers
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P2
**Source:** `app/Http/Controllers/EnviosController.php:49-72`
**Plain English:** The bulk-send screen lists joined dictionaries tagged as active (1), inactive (0) or planned (2). Active ones are shown enabled and the others greyed out. For non-teachers the flag ocultarDiccionarioNoVisible is true.
**Specification:**
  Given a student joined to D1 (envios_habilitados=1, start date in the past), D2 (envios_habilitados=0) and D3 (envios_habilitados=1, start date 2026-11-01)
  When the send screen opens on 2026-10-04
  Then D1=activo, D2=inactivo, D3=planificado
**Parameters:** ctes.estado_envio_habilitado: inactivo=0, activo=1, planificado=2
**Edge cases handled:** None found.
**Confidence:** High.

### RULE-303: Delete warning differs for an entry already sent
**Category:** Policy
**Domain:** Submissions-Envios
**Priority:** P2
**Source:** `app/Helpers/dpEntradas_helper.php:362-382`
**Plain English:** If the entry's latest relevant submission is "sent", the delete confirmation warns that it was sent. Otherwise the standard message is shown.
**Specification:**
  Given E is in a pending submission
  When  the user clicks delete
  Then  the confirmation text is "confirm_entrada_enviada" (naming E)
**Parameters:** estados_envios
**Edge cases handled:** published or deleted → standard text (the delete is refused later).
**Confidence:** High.

### RULE-304: Video thumbnail is the frame at second 1
**Category:** Policy
**Domain:** Uploads-Media
**Priority:** P2
**Source:** `app/Helpers/dpMedios_helper.php:83-85`
**Plain English:** After a video upload, a poster image is taken from the frame at 1 second and saved as `<name>_thumb.jpg` in the video folder.
**Specification:**
  Given video `5_12_77_<rand>.mp4` is uploaded
  When it is processed
  Then `5_12_77_<rand>_thumb.jpg` is generated from second 1 (`app/Helpers/video_thumbnail.php:8-39`)
**Parameters:** capture second 1; suffix "_thumb"
**Edge cases handled:** None. The generation result is ignored.
**Confidence:** Medium. It relies on the `public/storage` symlink and an external Thumbnail/FFmpeg library. SME question: "Is second 1 the intended poster frame, and is a missing poster acceptable?"

## Rules requiring SME confirmation

Every Medium/Low-confidence rule, plus P0 rules with a suspected defect. These are open items for human review (run non-interactively, no human was asked).

- **RULE-001** (P0, High) Persisted attributes and forced defaults: High. Suspected defect: - The max-senses setting is lost (1 if sent, 0 if not), and nothing else in the app enforces `max_acepciones_entrada` (grep finds it only in the form).
- **RULE-002** (P0, High) Join-code generation: High. Suspected defect: `create()` retry loop never increments (see H03). `daGenerateCode` uses `rand(0, strlen)`, which can index past the alphabet and yield shorter codes.
- **RULE-005** (P0, High) School-year start year from INICIO_CURSO: explicit code, corroborated by `documentos/Pruebas Fechas vigencia y cursoActual.md`. Suspected defect: - If the timestamp is 0, `$diames` is undefined.
- **RULE-006** (P0, High) Is a classroom dictionary still in force (vigente): High. Suspected defect: This disagrees with the list filter in B06 (`>=` there vs `>` here). `DicAula::activarDiccionarioCursoActual` (`app/Models/DicAula.php:184-190`) hardcodes `+9`, which corresponds to a fixed vigencia of 10.
- **RULE-007** (P0, High) Reactivate a dictionary for the current school year: High. Suspected defect: No check against `ctes.vigencia_max` (10), so the computed vigencia can exceed the maximum.
- **RULE-008** (P0, High) Last-submission lookup: published > sent > any, current school year only: High. SME question: "Should 'already published' protection on personal entries apply across school years?" Suspected defect: This feeds personal entry deletion (dpEntradas_helper.php:44-90). An entry published in an earlier school year can be deleted from the personal dictionary as if it had never been sent.
- **RULE-009** (P0, Medium) Submission window state (inactive / active / planned): SME: "For a planned window, should submissions open on envios_fecha_ini? Is there an end date?" Suspected defect: The comparison looks inverted (submissions open **before** the planned start and close after it). `envios_fecha_fin` is never persisted (not fillable).
- **RULE-010** (P1, Medium) Classroom PDF shows every optional field and renumbers senses: Medium. I didn't verify whether the export helper strips the fields beforehand. SME: "Must the classroom PDF respect the dictionary's field-visibility configuration?" Suspected defect: the field-visibility configuration is ignored in the export.
- **RULE-025** (P2, Medium) Published-entries statistic: Medium.
- **RULE-037** (P0, High) Persona matching order: High. Suspected defect: - If all three identifiers are empty, `$persona` is undefined and PHP 8 / Laravel raise an ErrorException, giving a 500 instead of a clean denial.
- **RULE-038** (P0, High) Session userData requires persona and centres, but this is not enforced: High. Suspected defect: The 403 is never sent, so the check does nothing.
- **RULE-041** (P0, High) Students can't select a classroom dictionary that is hidden from students: High for the view behaviour. SME: "Must a student be blocked server-side from activating, or browsing, a classroom dictionary that is not visible to students, or one they haven't joined?" Suspected defect: Client-only. `DicAulaController::setDicAulaActivo` → `daDiccionarioAulaSelDiccionarioActivo` (`app/Helpers/dicAula_helper.php:488-500`) puts any `DicAula::find($id)` into the session, with no membership or visibility check. Also, line 70 uses strict `!==` against the string `'1'`; if the DB returns an int, every dictionary gets the "not visible" tooltip for teachers.
- **RULE-042** (P0, High) Maximum senses per entry is offered but never stored or enforced (suspected defect): High. SME: "Should the limit be enforced when students submit entries (reject, or send only the first N senses)?" Suspected defect: yes, the `?:`/`??` misuse plus the limit is never enforced.
- **RULE-043** (P0, High) Maximum senses per entry defaults to 10 but is stored as a boolean: SME: "What maximum senses per entry should apply, and is it enforced anywhere today?" Suspected defect: The PHP precedence error makes the limit unusable.
- **RULE-045** (P0, High) General comments to a student (coordinator only): High. Suspected defect: GET is protected (`esCoordinador`, `ComentariosController.php:220`), but POST `comentariosPost` (lines 287-330) has no authorization, so any user can post general comments to any dictionary or student. The participant list comes from the session's active dictionary, not route `{id}`, and includes teachers.
- **RULE-046** (P0, High) Entry comments can be edited or deleted by any teacher, and are rendered as raw HTML: High. Suspected defect: Client-only. `DicAulaController::comentarioEdit/comentarioDelete` (lines 1173-1256) have no authorization and don't verify the comment belongs to `{identrada}`/`{id}`. Unescaped HTML is a stored-XSS risk.
- **RULE-048** (P0, High) Join-code validation and server-side regeneration: High. Suspected defect: - `$countWhile` is never incremented, so the 5-retry limit never triggers and the loop is unbounded.
- **RULE-049** (P0, High) Join-code validation (4–6 characters, unique): High. Suspected defect: In the controller retry loop, `$countWhile` is never incremented, so the loop has no real bound (controller shard).
- **RULE-051** (P0, High) Entry text is unique per dictionary, ignoring case: High. Suspected defect: Yes, three problems:
- **RULE-052** (P0, Medium) Bulk publish pre-check and execution: Medium. SME question: "When several students submit the same word, should none, the first, or a teacher-chosen one be published?" Suspected defect: The pre-check and execution disagree on duplicates. An empty `enviosId` leaves `$enviosIds` undefined.
- **RULE-053** (P0, High) Edit-entry and publish buttons only for coordinators of that dictionary: High. Suspected defect: Client-only for renaming. `DicAulaController::daEditEntradaGET/POST` (lines 1377-1495) have no `authorize`, so any authenticated user can rename any classroom entry. `publicar` does check `esDocente` + `esCoordinador` (lines 555-562).
- **RULE-054** (P0, High) Renaming a published entry: High. Suspected defect: - No authorization on GET or POST (lines 1377-1494).
- **RULE-056** (P0, High) A published submission can't be deleted (server check broken): High. Suspected defect: Client-only. The server guard in `eliminarEnvioEntrada` checks `$envioEntrada->publicada` (`dicAula_helper.php:936`), but no such attribute or column exists, so it's always null. A direct POST hard-deletes a published entry. Also, unpublishing (`despublicarEntrada`) flips `estado` to 2/1 rather than 1, so the UI's published state (`dic_aula_entradas.estado`, used in H33) and this check use different sources.
- **RULE-057** (P0, High) Duplicate-title block on publication: High. Suspected defect: The query also matches the same submission itself, so re-publishing an already-linked one is refused.
- **RULE-058** (P0, High) Guards on disabling a participant: High. Suspected defect: - The teacher count includes already-disabled teachers. With one active and one disabled teacher, the last active one can be disabled.
- **RULE-059** (P0, High) No authorization on editing submitted senses or deleting their media: High that the code has no check. SME question: "Who may edit a submitted sense: only teachers/owners of that classroom dictionary, or also co-teachers?" Suspected defect: Missing authorization (and an IDOR on acepcion_id). The GET edit form (lines 36-76) is also unprotected.
- **RULE-060** (P0, High) Saving edits is rejected for inactive dictionaries, but saving is not authorized: High. Suspected defect: - Missing authorization.
- **RULE-061** (P0, High) Only teachers see the "Create classroom dictionary" action: High. Suspected defect: Client-only for the write path. GET `/aula/create` (`DicAulaController::index`, line 64) authorizes `esDocente`, but POST `/aula/create` (`DicAulaController::create`, lines 119-174) has no `authorize`. A student can POST and create a dictionary. Also, `create()`'s code-retry loop never increments `$countWhile` (lines 148-157), so it can loop forever.
- **RULE-062** (P0, Medium) Who counts as a coordinator: SME: "Is 'teacher in this dictionary' defined by the global role or by the per-dictionary role?"
- **RULE-063** (P0, High) Teacher / admin role helpers: High. Suspected defect: These helpers use role names, while B14/B30–B32 use hardcoded ids from `ctes.rol`. These give different answers if the ids drift.
- **RULE-064** (P0, High) Validity (vigencia) selection and minimum on edit: High. SME: "Must the server reject a validity shorter than the years already elapsed, or longer than 10?" Suspected defect: Client-only. `DicAulaController::save` (lines 297-334) and `create` don't validate `vigencia` at all (no min/max/integer). `save()` also has **no authorization**, and `daCreateOrUpdateDicAulaByPersonaId` overwrites `persona_id` with the editor (`dicAula_helper.php:70`), so ownership silently moves to whoever saves.
- **RULE-065** (P0, Medium) Vigencia list filter (off by one compared with B03): SME: "Does vigencia = N mean the dictionary is usable in N school years in total (creation year included), or for N more years after creation?" Suspected defect: Off by one compared with B03: listings show a dictionary for one extra school year beyond the deactivation rule. `daGetDiccionariosConVigencias` (`dicAula_helper.php:1990-2012`) uses yet another test (`vigencia > 0`, elapsed-years comparison commented out).
- **RULE-066** (P0, Low) Atemporal dictionaries in the connected list: SME: "Is `dic_aula_atemporales.hasta` the date the timeless status ends? Should a disabled (estado = 0) atemporal record still make a dictionary visible?" Suspected defect: - The comparison looks inverted: atemporal records whose end date is in the future are excluded, past ones included.
- **RULE-067** (P0, High) The school year of a new classroom dictionary is supplied by the browser: High. Suspected defect: client-trusted school year.
- **RULE-068** (P0, Low) Reactivation window: start year + 9: Low. The method has no callers in `app/` or `resources/`. SME question: "Is there a 10-school-year cap on reactivating a classroom dictionary, and should it come from `VIGENCIA_MAX` instead of a hardcoded 9?"
- **RULE-069** (P0, High) A dictionary's school year comes from the submitted form: High. Suspected defect: The value can be tampered with; the server should compute it.
- **RULE-070** (P0, High) Hide/edit sense buttons in the classroom dictionary: High. Suspected defect: (1) There's a visibility mismatch: the button is shown to any teacher, but the server accepts only the creator. (2) Client-only: `EnvioAcepcionController::editAcepcionGet/editAcepcionPost` (lines 36-200) have no authorization, so any logged-in user can edit any submitted sense.
- **RULE-071** (P0, High) Single-entry submission requires the classroom dictionary to accept submissions: High. The logic is explicit. Suspected defect: The listing query (dicAula_helper.php:298-316) compares envios_fecha_ini with `new DateTime()` (now), but aceptaEnvios compares with today at midnight. If the column holds a time, a dictionary can show as "planned" yet accept submissions.
- **RULE-072** (P0, High) Target classroom dictionary membership is not verified server-side: High. SME question: "Should only active participants of a dictionary that is valid this school year be able to submit to it?" Suspected defect: Authorization gap. It applies to both paths (EnviosController.php:98-140 too).
- **RULE-074** (P0, High) Bulk / selected-entries submission bypasses the acceptance-window check: High that the check is missing. SME question: "Must the 'submissions enabled / start date' window apply to bulk sends too?" Suspected defect: This is inconsistent with RULE-D01. The single-entry path checks `aceptaEnvios`; this path (`dpEnvio_EnviarEntradasADicAula`, called from EnviosController.php:140) does not.
- **RULE-075** (P0, High) Submission-window state filter (inactive / planned / active): High. Suspected defect: `DicAula` model lines 91-94 map "future start" to activo, which contradicts this helper. That belongs to another shard.
- **RULE-076** (P0, High) Pre-check requires at least one visible sense and reports field errors per sense: High. SME question: "Must the server reject entries with zero visible senses at send time?" Suspected defect: The actual send (RULE-D09/D14) does not enforce "at least one sense". A direct POST can submit an entry with no senses.
- **RULE-079** (P0, High) Allowed extensions and size limit are checked in the browser only: High. SME question: "Must the server reject types outside this list and files over 200 MB, or is the browser check accepted as the policy?" Suspected defect: There is no server-side whitelist. Files go to the publicly served `storage/app/public` tree (G04) with an extension taken from `$file->extension()`, which is guessed from the MIME type.
- **RULE-080** (P0, High) Media upload type and size limits are client-only: High. Suspected defect: no server-side `mimes`/`max` rules.
- **RULE-081** (P0, High) TinyMCE upload has no validation: High. Suspected defect: there is no MIME, size or extension check; uploads can overwrite each other; hosting HTML/SVG files enables stored XSS.
- **RULE-094** (P1, Medium) Entry-title uniqueness lookup (case-insensitive, accent-sensitive): SME: "Should 'arbol' and 'árbol' be different entries?" Suspected defect: - The title is concatenated into raw SQL (SQL injection, and breaks on `"` or `%`).
- **RULE-098** (P1, Medium) The dictionary picker's grey/green rule disagrees with the server (suspected defect): Medium. SME: "Is a scheduled dictionary open from the start date onward (yes, per `aceptaEnvios`), and should the picker match that?" Suspected defect: (1) There are two inconsistent "accepts submissions" rules. (2) The hide-non-visible check compares to `null`, so it works only if `visible_estudiante` loads as int 0.
- **RULE-109** (P2, Medium) A search needs a word or at least one tag: SME: is the tag filter supposed to be one field (`tematicas_ids`, comma-separated)? Suspected defect: Validation and the existence check use `list_tematica_id`, but routing uses `tematicas_ids`, so the two can disagree.
- **RULE-113** (P0, High) CAS callback flow: High. Suspected defect: - If the persona was already linked to a user with a *different* `name` (e.g. the CAS ID changed), provisioning reuses that user. The lookup by the new CAS ID then fails with 404.
- **RULE-118** (P0, High) Deleting a classroom dictionary soft-deletes its related data: High Suspected defect: `dic_aula_entradas` is only counted, not soft-deleted. `centros_dic_aula` and `envios_entradas` are left untouched. Errors are swallowed and the method returns null. Raw SQL bypasses auditing and model events. The related models other than DicAula, EnvioEntrada and the DP entry/sense models do not use SoftDeletes, so their `deleted_at` is not applied by default scopes.
- **RULE-119** (P0, High) Pre-checks before deleting a classroom dictionary: High. Suspected defect: Client-only. GET `/aula/{id}/delete` (`DicAulaController::delete`, lines 1598-1607) has no authorization and doesn't re-check published entries or submissions, so any logged-in user can delete any dictionary by URL.
- **RULE-120** (P0, High) Creating or editing a general comment: High. SME question: "Should editing a comment keep its original send date, and should read or unread be tracked?" Suspected defect: editing moves `fecha_envio` to now. Under visibility mode 2, that can hide a previously visible comment. Nothing in the shard or the codebase ever sets `estado=2` (read), so the read state is never tracked. A student with no personal dictionary causes a null-pointer error.
- **RULE-121** (P0, High) A student sees only general comments aimed at their latest personal dictionary: High. Suspected defect: `->get()[0]` (line 211) throws an error instead of returning null when the student has no personal dictionary, so the `is_null` guard on line 213 never runs. Comments addressed to an older personal dictionary (for example from a previous school year) become invisible.
- **RULE-122** (P0, High) Joining a classroom dictionary by code: High. Suspected defect: A disabled teacher-role participant gets "already joined" rather than an error. Codes for past-year (non-vigente but still active) dictionaries still work.
- **RULE-124** (P0, High) One personal dictionary per person, created on first use: High. Suspected defect: There is no unique DB constraint on dic_personal.persona_id (migration `2020_04_19_113507:124-133`). "One per person" is enforced only by `->first()`, so two simultaneous first visits could create duplicates.
- **RULE-125** (P0, High) Saving the first sense creates the entry: High. Suspected defect: Errors while saving the sense, tags or media are caught and only logged (lines 587-595). The user still sees "sense added", and the entry may be left with no senses.
- **RULE-127** (P0, High) Soft-deleting a personal entry cascades to its senses: High Suspected defect: Restoring the entry does not restore its senses, and the media are already gone (G09).
- **RULE-128** (P0, Medium) Unpublishing and hiding an entry: Medium. SME question: "What status should an unpublished submission return to: 1 (enviado) or a distinct 'hidden'?" Suspected defect: - It writes `estados_entrada` values into a column that uses `estados_envios`, so 2 reads as "borrado_estudiante".
- **RULE-129** (P0, High) Publish transition: High. Suspected defect: - These helpers do no authorization check (it may be done in the controller).
- **RULE-130** (P0, High) Publishing a single entry: High. Suspected defect: Duplicate detection uses exact `entrada` equality, so case or accent behaviour depends on the DB collation. This differs from rename (A53).
- **RULE-132** (P0, Medium) Hide entry from a classroom dictionary: same SME question as B22. Suspected defect: - If no link exists, `->delete()` on null throws an Error that `catch(Exception)` does not catch, so the result is a 500.
- **RULE-133** (P0, High) Participant access and editing-permission toggles: High. Suspected defect: `DicAulaController::participantesAjax` (lines 702-737) only checks `esDocente`. `isOwner`/`esCoordinador` are commented out and the participant id isn't tied to `{id}`, so any teacher can enable or disable participants or coordinators of any dictionary.
- **RULE-135** (P0, Medium) Grant / revoke edit rights: SME: "Who may grant edit rights: the owner only, or any teacher participant?" Suspected defect: Same counting issues as B30. Grant has no authorization check in the helper.
- **RULE-136** (P0, High) Promote/demote a participant's dictionary role: High. Suspected defect: Promotion has no restriction on the target's global role, so a student can be made coordinator.
- **RULE-138** (P0, High) Admin bulk deactivation of expired dictionaries: High. Suspected defect: Logs `ctes.log_types.app_info`, a key not defined in `ctes.log_types` (null type).
- **RULE-139** (P0, High) Atemporal (permanent) dictionary flag: High for the model behaviour. The listing logic belongs to another shard. Suspected defect: The listing query is `whereDate('hasta','<', tomorrow) OR hasta IS NULL`, so it includes atemporal dictionaries whose end date has passed. The orWhere also escapes the estado filter.
- **RULE-140** (P0, High) Per-dictionary validity check needs no login: High. Suspected defect: State-changing GET with no authentication.
- **RULE-144** (P0, Medium) Hidden classroom senses are still displayed to everyone (suspected defect): Medium. SME: "When a coordinator hides a sense in the classroom dictionary, should students (and the PDF) stop seeing it?" Suspected defect: Hiding a sense has no effect on what students see.
- **RULE-145** (P0, High) Deleting a sense (the last one deletes the entry): SME: may students delete senses of an entry that is already sent or published? Suspected defect: Deleting a non-last sense of a sent or published entry is allowed without any check. The success message is passed through `withErrors`.
- **RULE-146** (P0, High) Deleting a sense permanently deletes its media: High Suspected defect: The soft delete is not reversible for media, and it bypasses the G07 "submitted" protection.
- **RULE-147** (P0, Medium) Deleting an entry depends on its submission state this school year: Medium. SME questions: Suspected defect: - **Wrong state:** `$entrada->estado = borrado_logico` is set just before `$entrada->delete()`. With SoftDeletes, delete() only updates deleted_at/updated_at, so estado '0' is probably never saved.
- **RULE-148** (P0, High) Delete an unpublished submission (guard broken): High. Suspected defect: - `$envioEntrada->publicada` does not exist: there is no column, accessor or relation; the relation is `publicadas`. It is always null, so the guard never fires and published entries can be force-deleted.
- **RULE-164** (P1, Medium) Removing a submitted sense hard-deletes it: Medium. I did not trace the callers of `EnvioAcepcion::borrar`. SME question: "Should removing a submitted sense be recoverable or audited?" Suspected defect: Query-builder deletes produce no audit rows. `Envio::borrar()` (`app/Models/Envio.php:50-69`) calls `EnvioEntrada::borrar()`, which is commented out, so it would throw. It has no callers.
- **RULE-167** (P0, Medium) Admin routes require `admin.user`: middleware semantics come from vendor code. Suspected defect: - Exceptions without `admin.user`: `/admin/vigencia/comprobar/{id}` (F24), POST `/admin/sendFileJs` and `/admin/sendFotosJs` (no auth; they only echo data and store nothing, vRecordingController.php:27-58).
- **RULE-168** (P0, High) Unrouted backdoor that creates or resets an admin account: High. These must not be migrated. Suspected defect: a credential is committed in a public repo and must be rotated. `logs()` references the non-existent route `down`.
- **RULE-171** (P0, High) Soft-delete scope: which tables are recoverable: High Suspected defect: Several tables have a `deleted_at` column that G21 writes to, but their models have no SoftDeletes, so Eloquent reads still return the "deleted" rows. `DiccionarioPersonal` has no SoftDeletes, yet statistics filter `dic_personal.deleted_at`.
- **RULE-173** (P0, High) Provisioned user account fields (shared password hash): High. Suspected defect: - All CAS-provisioned accounts share one password. Together with F21 (password login still active), anyone who knows the plaintext and a login ID can sign in as that user through POST /login or /admin/login, bypassing CAS and CAUCE.
- **RULE-174** (P0, High) CAUCE web service call: High. Suspected defect: - `env()` is called outside config. If `config:cache` is used it returns null and the endpoint/token become empty.
- **RULE-175** (P0, High) Password login stays available in every environment: High. Suspected defect: Combined with F12's shared hash this is a CAS bypass.
- **RULE-177** (P0, High) Any logged-in user can run the CAUCE validator test page: High. Suspected defect: debug endpoint exposed in production. The fixtures are a useful test oracle for the CAUCE rules (roles 2, 3 and 5; a centre with an empty code).
- **RULE-182** (P0, High) `/test` route provisions test accounts: High. Suspected defect: Test fixtures reachable in production put junk data in the database.
- **RULE-183** (P0, High) Defaults when creating or updating a classroom dictionary: High. Suspected defect: - Line 78: `isset($params['maxAcepciones']) ?? config(...)` stores `1` or `0` (a boolean) instead of the chosen value or the default 10.
- **RULE-184** (P0, High) Comments are only aggregated from "connected" classroom dictionaries: High for the aggregation. SME question: "Should a student still see comments from previous-year classrooms?" Suspected defect: the atemporal clause `whereDate('hasta','<',tomorrow)->orWhereNull('hasta')` keeps only atemporal links whose end date is today or earlier. That looks inverted (expired links would match). Shard owning `dicAula_helper` should confirm.
- **RULE-185** (P0, High) Classroom comment-visibility modes: High. The logic is explicit. SME question: "Is mode 2 meant to hide recent comments (sent on or after the date) or old ones?" Suspected defect: the code comment at `DicAula.php:240` says "posteriores" (after the date), but the code shows comments sent *before* the date. The teacher-screen label also calls it "anteriores_a". Mode 0 returns a Collection instead of a relation, which is inconsistent.
- **RULE-186** (P0, High) Entry-comment modal is owner-only and lists visible comments from every classroom: High. Suspected defect: the visibility subquery at `ComentariosHelper.php:62-66` compares unqualified `fecha_envio` (a comment column) inside a `dic_aulas` subquery. It works only if SQL resolves the column to the outer table, and that is fragile. Comment bodies are rendered unescaped (`{!! !!}` in `modal-comentarosentrada.blade.php:103`), which is a stored-XSS risk from teacher HTML.
- **RULE-188** (P0, High) Editing/deleting comments on classroom entries has no authorization: High. Suspected defect: Missing authorization, and the comment is not checked to belong to the dictionary or entry in the URL. The `ComentarioEntrada` model has no `SoftDeletes` trait, so `destroy` is permanent.
- **RULE-189** (P0, High) CSV statistics export open to any logged-in user: High. SME question: "Should these statistics exports be limited to Voyager admins?" Suspected defect: Missing admin authorization (`routes/web.php:244-245`). No view links to these routes. Files land in the web root (`public/exports`) and can be fetched directly by URL.
- **RULE-190** (P0, High) Classroom PDF content: published and visible entries only: High Suspected defect: `PDFController.php:96` calls this outside the try block, so an empty dictionary gives an unhandled exception (500) instead of the friendly "no entries" redirect, which is only reachable on the tagged path.
- **RULE-191** (P0, High) Personal-dictionary PDF has no ownership check: High Suspected defect: IDOR: a user can read another user's personal dictionary by changing the ID. A non-existent ID causes a null error, not a 404.
- **RULE-192** (P0, High) CSV export calls any controller method named in the request: High Suspected defect: **Authorization bypass with state change.** Admin-only, parameterless actions can be triggered by any authenticated user. A whitelist and the `admin.user` middleware are required.
- **RULE-193** (P0, High) Classroom PDF only for participants: High Suspected defect: A non-existent ID passes `null` to the policy, which gives a type error rather than a 404. The owning teacher can export only if they are also a participant row.
- **RULE-194** (P0, Medium) Field catalogue ids (`mst_campos_entrada`): SME/DBA: "Confirm the production ids of mst_campos_entrada match ctes.mst_campos_entrada (5 = Más datos … 10 = Ejemplo de uso)." Suspected defect: On a fresh install the field ids for video, audio, image, language and example are off by one compared with the code constants, so per-dictionary visible/required flags apply to the wrong fields.
- **RULE-195** (P0, High) Per-dictionary visible/required entry fields: High. Suspected defect: Nothing enforces "required implies visible", so obligatorio=1 with visible=0 is possible.
- **RULE-197** (P0, High) Publish/unpublish/delete submission need teacher + coordinator; the entry–dictionary link is not checked: High. Suspected defect: Cross-dictionary publish or delete of another class's submissions.
- **RULE-199** (P0, High) Only a docente participant can open the general-comment screen: High. SME question: "Should comment recipients be limited to active alumno participants of this classroom?" Suspected defect: the list of students to comment on, and the breadcrumb, come from the session's active classroom dictionary (`getDicAulaActivo()`, lines 242, 247-250), not from the `{id}` in the URL. The teacher can see students of classroom A while posting to classroom B. The list also includes teachers. A docente whose participation is disabled is still authorized.
- **RULE-200** (P0, High) Only the dictionary owner may act on an entry: explicit. Suspected defect: dpDeleteEntradaGET and dpCreateAcepcionGET call the check without first confirming the entry exists, so a missing or deleted id causes a crash (500) instead of a 404 (`DiccionarioPersonalController.php:110-115`, `792-795`).
- **RULE-201** (P0, High) Seeded accounts with shared hardcoded passwords: High. Suspected defect: This is a security finding. Credentials and personal identifiers are committed to a public repository and seeded into any environment that runs `DatabaseSeeder`.
- **RULE-202** (P0, Medium) Open self-registration probably grants the admin-panel role: the role assignment happens in vendor Voyager code, which is not in the repo.
- **RULE-203** (P0, Medium) Application roles docente/alumno; role ids depend on the environment: the mismatch is stated in a code comment and is consistent with the seeder order. SME/DBA: "What are the production ids of roles admin, user, docente and alumno?"
- **RULE-204** (P0, High) Admin gets every permission; "user" (Oficina técnica) is read-only: High. Suspected defect: Oficina técnica menu items point to BREAD routes (`centrosBread`) for which the role has no browse permission.
- **RULE-205** (P0, High) Choosing the user's active classroom dictionary: High. Suspected defect: - Passes `auth()->user()->userPersona->id` as `persona_id`. `userPersona` may be the pivot rather than Persona; verify.
- **RULE-206** (P0, High) Numeric role IDs depend on environment: High. Suspected defect: Any DB where role IDs differ from these constants silently breaks teacher permissions. Two role-identification methods (by name and by ID) coexist.
- **RULE-207** (P0, High) Editing a sense does not check it belongs to the entry: High. Suspected defect: Yes. A user can take over or overwrite another user's sense. Compare dpEditAcepcionGET, which correctly uses `$entrada->dpAcepciones()->find(...)`.
- **RULE-208** (P0, High) Global "teacher" role check: High. Suspected defect: There are three different "is teacher" implementations:
- **RULE-210** (P0, High) Only the comment's author can delete a general comment: High. Suspected defect: a state-changing GET is CSRF-exposed. The `{id}` classroom value is ignored. Combined with RULE-E10, anyone can first take over authorship and then delete. The model has no soft delete; only the audit log keeps a trace.
- **RULE-211** (P0, High) Saving a general comment has no authorization check: High. Suspected defect: IDOR / privilege escalation. Any authenticated user can create comments in any classroom for any persona, and can hijack any comment.
- **RULE-212** (P0, High) Participant management needs only the global teacher role: High. Suspected defect: - No coordinator check.
- **RULE-213** (P0, High) Only teachers may open the "create classroom dictionary" form: High. The authorize call is present in `index()` and absent in `create()`. Suspected defect: `create()` (DicAulaController.php:119-175) has no `authorize('esDocente')`, so a student can create classroom dictionaries.
- **RULE-214** (P0, Medium) One admin vigencia route is reachable without auth: Medium. VoyagerCompassController has no constructor middleware, but I did not trace any global middleware. SME question: "Is /admin/vigencia/comprobar/{id} protected by any global or Voyager middleware in production?" Suspected defect: Missing middleware.
- **RULE-215** (P0, High) Reordering senses has no ownership check: High. Suspected defect: Yes. This is an IDOR (insecure direct object reference): `// $this->authorize('isOwner', $entrada);` is commented out at lines 733-734, and the down action has no check at all.
- **RULE-216** (P0, High) Access to the edit page needs teacher + coordinator + active dictionary: High. Suspected defect: `canEditDic` (DicAulaPolicy.php:85-101) ignores the dictionary and only checks the global role.
- **RULE-217** (P0, High) Reading published entries by explicit dicId skips the membership check: High. Suspected defect: IDOR. Any dictionary's content can be read, including ones not visible to students.
- **RULE-218** (P0, High) Deleting a classroom dictionary has no authorization and no preconditions: High. Suspected defect: - No authorization.
- **RULE-219** (P0, High) Classroom coordinator = participant whose dictionary role is "docente": High. Suspected defect: A coordinator who was disabled (estado 0) keeps coordinator rights.
- **RULE-220** (P0, High) Any logged-in user can trigger a test email: High. Suspected defect: debug endpoint left in production. It can be abused to spam, and it leaks personal addresses in a public repo. `sendPlainTextMail` exists but is unrouted.
- **RULE-221** (P0, High) Landing page after login depends on role: High. Suspected defect: `HomeController::index` (`HomeController.php:39-42`) is not routed; the `home` view is dead code.
- **RULE-222** (P0, High) The submission list for moderation is visible to any teacher: High. Suspected defect: - Missing coordinator check.
- **RULE-223** (P0, High) Hiding entries/senses is restricted to the dictionary owner: High. Suspected defect: This is inconsistent with publish/unpublish, which any coordinator can do. Also, the entry/sense is not checked to belong to dic 7.
- **RULE-224** (P0, High) Participant check: High. Suspected defect: Disabled participants can still export.
- **RULE-225** (P0, High) A user's visible ("connected") classroom dictionaries: High (the code is explicit). SME question: "Is vigencia N meant to cover N school years or N+1?" Suspected defect: - Off-by-one: `>=` here vs `>` in A26, so a dictionary stays visible one extra year until the admin deactivation (A29) runs.
- **RULE-229** (P0, Medium) No duplicate check; each send makes a new version: Medium. SME question: "When a student resends an entry, should it replace the pending or published version in the classroom dictionary, or queue a new version for teacher review?"
- **RULE-230** (P0, High) Submission window: High. Suspected defect: - `envios_fecha_fin` is never stored or enforced (see A39).
- **RULE-235** (P0, High) A media file is kept if its entry was ever submitted (protection is defeated): High Suspected defect: **Data loss.** Submitted copies share the same `url_interna` (`app/Helpers/dpEnvios_helper.php:418-428`). The unconditional delete in the model hook breaks images, audio and video in published classroom dictionaries whenever the student replaces or deletes the medium. The hook also skips the thumbnail, so in the non-submitted case only the helper removes it.
- **RULE-236** (P0, Medium) Media files are served from public storage without authorization: Medium. Inferred from the URL builders; I did not inspect the web-server config. SME question: "Are students' images, audio and video allowed to be publicly downloadable by URL, or must access be limited to the owner and the participants of the classroom dictionaries where they are published?"
- **RULE-266** (P1, Medium) Vigencia maximum / minimum: SME: "Is the vigencia range enforced server-side, or only by the form?" The controller validator should be confirmed by the controller shard.
- **RULE-271** (P1, Medium) Only classrooms with sending active are offered: the filter logic lives in `daGetDiccionariosAulaByUserConectado` (`dicAula_helper.php:195`, another shard). SME: does "active" also depend on vigencia/start date?
- **RULE-294** (P2, Medium) Theme and language catalogues: the purpose of the leading space is inferred from the code comment "destacar". SME: "Should the highlighted languages be modelled as an explicit sort order instead of a leading space?"
- **RULE-295** (P2, Medium) Study-level ordering (Multiestudio, Infantil first): SME: "Should Infantil always be offered?" Suspected defect: If 'Multiestudio' does not exist, 'Infantil' disappears from the list (it is excluded from the base query and only re-added when both exist).
- **RULE-297** (P2, Medium) "Modified by teacher" marker: Medium. SME: "What is the default value of `updated_by_persona_id` for unedited submissions?" Suspected defect: probable false positives.
- **RULE-304** (P2, Medium) Video thumbnail is the frame at second 1: Medium. It relies on the `public/storage` symlink and an external Thumbnail/FFmpeg library. SME question: "Is second 1 the intended poster frame, and is a missing poster acceptable?"

## Shard card ID cross-reference

Cards cite each other by their shard IDs (A01, F21, ...). This maps them to final IDs.

| Shard ID | Rule |
|---|---|
| A39 | RULE-001 |
| H21 | RULE-002 |
| F09 | RULE-003 |
| F10 | RULE-004 |
| B01 | RULE-005 |
| A24 | RULE-005 |
| B03 | RULE-006 |
| A26 | RULE-006 |
| F27 | RULE-007 |
| D18 | RULE-008 |
| I06 | RULE-009 |
| G23 | RULE-009 |
| H11 | RULE-010 |
| A35 | RULE-011 |
| B12 | RULE-011 |
| H34 | RULE-012 |
| G19 | RULE-013 |
| F30 | RULE-014 |
| B02 | RULE-015 |
| F28 | RULE-016 |
| I08 | RULE-017 |
| C12 | RULE-018 |
| C20 | RULE-019 |
| C18 | RULE-020 |
| B35 | RULE-021 |
| G05 | RULE-022 |
| I11 | RULE-023 |
| F29 | RULE-024 |
| F31 | RULE-025 |
| B47 | RULE-026 |
| B28 | RULE-027 |
| E07 | RULE-028 |
| C33 | RULE-029 |
| B45 | RULE-030 |
| I32 | RULE-031 |
| G29 | RULE-032 |
| C28 | RULE-033 |
| I10 | RULE-034 |
| G27 | RULE-035 |
| I16 | RULE-035 |
| B46 | RULE-036 |
| F07 | RULE-037 |
| F18 | RULE-038 |
| B41 | RULE-038 |
| F06 | RULE-039 |
| F16 | RULE-040 |
| H01 | RULE-041 |
| H16 | RULE-042 |
| I34 | RULE-043 |
| B37 | RULE-044 |
| H39 | RULE-045 |
| H35 | RULE-046 |
| D10 | RULE-047 |
| A34 | RULE-048 |
| B11 | RULE-049 |
| B13 | RULE-050 |
| C08 | RULE-051 |
| A49 | RULE-052 |
| B20 | RULE-052 |
| H05 | RULE-053 |
| A53 | RULE-054 |
| D19 | RULE-055 |
| H32 | RULE-056 |
| B19 | RULE-057 |
| A12 | RULE-058 |
| B30 | RULE-058 |
| D21 | RULE-059 |
| A06 | RULE-060 |
| H03 | RULE-061 |
| B32 | RULE-062 |
| B33 | RULE-063 |
| H14 | RULE-064 |
| B06 | RULE-065 |
| B07 | RULE-066 |
| A28 | RULE-066 |
| H15 | RULE-067 |
| G25 | RULE-068 |
| I09 | RULE-068 |
| A32 | RULE-068 |
| A25 | RULE-069 |
| A38 | RULE-069 |
| H06 | RULE-070 |
| D01 | RULE-071 |
| D03 | RULE-072 |
| D04 | RULE-073 |
| D02 | RULE-074 |
| B09 | RULE-075 |
| D13 | RULE-076 |
| H28 | RULE-077 |
| D11 | RULE-078 |
| G02 | RULE-079 |
| H44 | RULE-080 |
| E18 | RULE-081 |
| A09 | RULE-082 |
| B38 | RULE-083 |
| B15 | RULE-084 |
| H19 | RULE-085 |
| E22 | RULE-086 |
| A43 | RULE-087 |
| I21 | RULE-088 |
| C27 | RULE-089 |
| C09 | RULE-090 |
| C06 | RULE-091 |
| B40 | RULE-091 |
| C07 | RULE-092 |
| I30 | RULE-093 |
| B39 | RULE-094 |
| A30 | RULE-095 |
| C10 | RULE-096 |
| H43 | RULE-097 |
| H29 | RULE-098 |
| B36 | RULE-099 |
| A47 | RULE-099 |
| H27 | RULE-100 |
| I24 | RULE-101 |
| B42 | RULE-102 |
| H45 | RULE-103 |
| F42 | RULE-104 |
| B27 | RULE-105 |
| A54 | RULE-106 |
| D27 | RULE-107 |
| G12 | RULE-108 |
| C29 | RULE-109 |
| H46 | RULE-110 |
| H47 | RULE-111 |
| H26 | RULE-112 |
| F02 | RULE-113 |
| F15 | RULE-114 |
| F17 | RULE-115 |
| F08 | RULE-116 |
| F13 | RULE-117 |
| G21 | RULE-118 |
| I28 | RULE-118 |
| A10 | RULE-118 |
| H23 | RULE-119 |
| E09 | RULE-120 |
| E02 | RULE-121 |
| A36 | RULE-122 |
| H31 | RULE-123 |
| C04 | RULE-124 |
| C11 | RULE-125 |
| I02 | RULE-126 |
| G08 | RULE-127 |
| I29 | RULE-127 |
| A50 | RULE-128 |
| B22 | RULE-128 |
| B21 | RULE-129 |
| A48 | RULE-130 |
| H33 | RULE-131 |
| B23 | RULE-132 |
| H24 | RULE-133 |
| B14 | RULE-134 |
| A37 | RULE-134 |
| B31 | RULE-135 |
| A13 | RULE-136 |
| F11 | RULE-137 |
| F25 | RULE-138 |
| I33 | RULE-139 |
| F24 | RULE-140 |
| B04 | RULE-141 |
| A29 | RULE-141 |
| G24 | RULE-142 |
| F26 | RULE-143 |
| H07 | RULE-144 |
| C17 | RULE-145 |
| G09 | RULE-146 |
| C19 | RULE-146 |
| C16 | RULE-147 |
| B24 | RULE-148 |
| A46 | RULE-148 |
| D07 | RULE-149 |
| I03 | RULE-150 |
| F20 | RULE-151 |
| A40 | RULE-152 |
| B18 | RULE-152 |
| E13 | RULE-153 |
| A20 | RULE-153 |
| B34 | RULE-154 |
| A15 | RULE-154 |
| I04 | RULE-155 |
| D05 | RULE-156 |
| H42 | RULE-157 |
| C21 | RULE-158 |
| A31 | RULE-159 |
| C13 | RULE-160 |
| C22 | RULE-161 |
| B25 | RULE-162 |
| A51 | RULE-162 |
| H41 | RULE-163 |
| G28 | RULE-164 |
| C24 | RULE-165 |
| G01 | RULE-166 |
| F23 | RULE-167 |
| E20 | RULE-168 |
| F33 | RULE-169 |
| F35 | RULE-170 |
| G30 | RULE-171 |
| G20 | RULE-172 |
| F12 | RULE-173 |
| F05 | RULE-174 |
| F21 | RULE-175 |
| F01 | RULE-176 |
| E19 | RULE-177 |
| F14 | RULE-178 |
| F40 | RULE-179 |
| F03 | RULE-180 |
| F04 | RULE-181 |
| F39 | RULE-182 |
| B16 | RULE-183 |
| E04 | RULE-184 |
| E01 | RULE-185 |
| G26 | RULE-185 |
| I05 | RULE-185 |
| A16 | RULE-185 |
| E05 | RULE-186 |
| E03 | RULE-187 |
| A14 | RULE-188 |
| G16 | RULE-189 |
| G14 | RULE-190 |
| G11 | RULE-191 |
| G17 | RULE-192 |
| G13 | RULE-193 |
| I12 | RULE-194 |
| A42 | RULE-195 |
| B17 | RULE-195 |
| I22 | RULE-196 |
| A19 | RULE-197 |
| A52 | RULE-198 |
| B26 | RULE-198 |
| E08 | RULE-199 |
| C01 | RULE-200 |
| I27 | RULE-201 |
| F22 | RULE-202 |
| I25 | RULE-203 |
| I26 | RULE-204 |
| B29 | RULE-205 |
| A41 | RULE-205 |
| F36 | RULE-206 |
| C03 | RULE-207 |
| A02 | RULE-208 |
| H02 | RULE-209 |
| E11 | RULE-210 |
| E10 | RULE-211 |
| A11 | RULE-212 |
| A01 | RULE-213 |
| A23 | RULE-214 |
| C02 | RULE-215 |
| A05 | RULE-216 |
| A17 | RULE-217 |
| A08 | RULE-218 |
| A03 | RULE-219 |
| E15 | RULE-220 |
| E16 | RULE-221 |
| F19 | RULE-221 |
| A22 | RULE-221 |
| A18 | RULE-222 |
| A07 | RULE-223 |
| A04 | RULE-224 |
| A27 | RULE-225 |
| B05 | RULE-225 |
| I07 | RULE-226 |
| G22 | RULE-226 |
| D14 | RULE-227 |
| H30 | RULE-228 |
| D17 | RULE-229 |
| A45 | RULE-230 |
| B10 | RULE-230 |
| H18 | RULE-231 |
| D09 | RULE-232 |
| D12 | RULE-233 |
| C23 | RULE-234 |
| G07 | RULE-235 |
| G04 | RULE-236 |
| F38 | RULE-237 |
| F32 | RULE-238 |
| D20 | RULE-239 |
| F41 | RULE-240 |
| F37 | RULE-241 |
| H22 | RULE-242 |
| I23 | RULE-243 |
| A44 | RULE-244 |
| H37 | RULE-245 |
| H20 | RULE-246 |
| E12 | RULE-247 |
| G10 | RULE-248 |
| G15 | RULE-249 |
| G18 | RULE-250 |
| H08 | RULE-251 |
| H09 | RULE-252 |
| H10 | RULE-253 |
| H17 | RULE-254 |
| I13b | RULE-255 |
| I13a | RULE-256 |
| I14 | RULE-257 |
| I20 | RULE-258 |
| I19 | RULE-259 |
| I01 | RULE-260 |
| I18 | RULE-261 |
| I17 | RULE-262 |
| D22 | RULE-263 |
| H25 | RULE-264 |
| C05 | RULE-265 |
| B08 | RULE-266 |
| F44 | RULE-267 |
| C14 | RULE-268 |
| C15 | RULE-269 |
| D15 | RULE-270 |
| C31 | RULE-271 |
| D08 | RULE-272 |
| D06 | RULE-273 |
| D16 | RULE-274 |
| G03 | RULE-275 |
| D23 | RULE-276 |
| C25 | RULE-277 |
| D24 | RULE-278 |
| F43 | RULE-279 |
| C26 | RULE-280 |
| F34 | RULE-281 |
| I31 | RULE-282 |
| E21 | RULE-283 |
| E17 | RULE-284 |
| H49 | RULE-285 |
| H04 | RULE-286 |
| B44 | RULE-287 |
| E14 | RULE-288 |
| H38 | RULE-289 |
| E06 | RULE-290 |
| H36 | RULE-291 |
| H13 | RULE-292 |
| H12 | RULE-293 |
| I15 | RULE-294 |
| B43 | RULE-295 |
| C30 | RULE-296 |
| H48 | RULE-297 |
| A21 | RULE-298 |
| A33 | RULE-299 |
| H40 | RULE-300 |
| D25 | RULE-301 |
| D26 | RULE-302 |
| C32 | RULE-303 |
| G06 | RULE-304 |
