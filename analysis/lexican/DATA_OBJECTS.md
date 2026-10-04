# Data Objects — lexican

Complete legacy data dictionary (every table and column) plus per-area usage notes from the rule-extraction shards. Feeds `docs/LEGACY-DATA-MAPPING.md`. Personal data masked.

I could not read the Voyager vendor migrations: `vendor/` is not in the repo and there is no `composer.lock`, only `composer.json` with `tcg/voyager ^1.5` and `owen-it/laravel-auditing ^12.0`. The Voyager table definitions below are the standard Voyager 1.5 schema, marked **[vendor]**, with Medium confidence. I found no AI-directed instruction text anywhere in this shard (searched migrations, seeders, models, `config/ctes.php` and `documentos/*.sql|md`).

Citation aliases used in the dictionary (the rule cards use full paths):
- **IS** = `database/migrations/2020_04_19_113507_create_initial_structure.php`
- **CT** = `config/ctes.php`
- **MS** = `database/seeders/MasterTablesDataSeeder.php`

---

## PART 1 — DATA DICTIONARY

## Conventions that apply everywhere

- **`estado` is `char(1)`** unless noted. The generic meaning is `'0'` inactive / `'1'` active (CT:51-54). Some tables use richer state sets, listed per table.
- **`deleted_at` exists on almost every domain table, but only four models use the `SoftDeletes` trait:** `DicAula`, `DiccionarioPersonalEntrada`, `DiccionarioPersonalAcepcion`, `EnvioEntrada`.
  - For every other model, Eloquent `delete()` is a **hard DELETE**.
  - Rows soft-deleted by raw SQL (for example `DicAula::delete`, `app/Models/DicAula.php:376-448`) are **not filtered out** by those models' queries.
- **Auditing:** every `App\Models\*` class implements `OwenIt\Auditing\Contracts\Auditable`, except `Pautas`, `App\User` and the Voyager models.
  - Audited events are created, updated, deleted and restored. Console runs are not audited (`config/audit.php:59-64,136`).
- **Indexes:** besides PKs, the only indexes are the unique constraints listed per table, the FK indexes MySQL creates automatically, `password_resets.email`, and the `audits` indexes.
- **Missing unique constraints:**
  - `dic_aula.codigo`
  - `centros.cod_centro`
  - `(dic_aula_id, persona_id)` on participants
  - `(dic_aula_id, mst_campo_entrada_id)` on field config
  - `(dic_aula_id, envio_entrada_id)` on published entries
  - `users_personas`

## A. Identity / users

### `users`
- **Purpose:** login accounts (CAS/CAUCE and Voyager).
- **Model:** `App\User` extends `TCG\Voyager\Models\User`. Fillable: role_id, name, email, password. Hidden: password, remember_token.
- **Soft deletes / audited:** no / no.
- **PK:** id.

| Column | Type | Meaning | FK | Source |
|---|---|---|---|---|
| id | bigint unsigned AI | PK | | `2014_10_12_000000_create_users_table.php:17` |
| name | varchar(255) NOT NULL | Username. The seeders put NIF-like identifiers here, so treat it as personal data | | :18 |
| email | varchar(255) UNIQUE | Email | | :19 |
| email_verified_at | timestamp NULL | Laravel verification | | :20 |
| password | varchar(255) | bcrypt hash | | :21 |
| remember_token | varchar(100) NULL | Session token | | :22 |
| created_at / updated_at | timestamp NULL | | | :23 |
| avatar | varchar NULL, default `users/default.png` | Voyager avatar | | [vendor] add_voyager_user_fields |
| role_id | bigint unsigned NULL | **Global role.** Values: `admin`, `user` (shown as "Oficina técnica"), `docente`, `alumno`. Ids depend on the environment (RULE-I25) | roles.id | [vendor] add_user_role_relationship |
| settings | text NULL | Voyager per-user settings (locale) | | [vendor] add_user_settings |

- **Relations:** `userPersona` (hasOneThrough `users_personas`, `app/User.php:52-61`) and `centros` (belongsToMany via `users_centros`, :68-76).

### `password_resets`
Laravel standard. No model.

| Column | Type | Source |
|---|---|---|
| email | varchar(255), INDEX | `2014_10_12_100000_create_password_resets_table.php:17` |
| token | varchar(255) | :18 |
| created_at | timestamp NULL | :19 |

No PK.

### `personas`
- **Purpose:** the natural person (teacher or student) behind a user account. Every domain table points here, never to `users`.
- **Model:** `App\Models\Persona`, audited, no SoftDeletes trait, fillable `['id']`.

| Column | Type | Meaning | Source |
|---|---|---|---|
| id | bigint AI | PK | IS:107 |
| cial | varchar(12) UNIQUE NULL | CIAL, the Canary student identifier from CAUCE. Used as the lookup key at login (`global_helper.php:784`) | IS:108 |
| NIF_NIE | varchar(9) UNIQUE NULL | Spanish NIF/NIE | IS:109 |
| pasaporte | varchar(255) UNIQUE NULL | Passport, an alternative identity key (`global_helper.php:782`) | IS:110 |
| nombre | varchar(150) NOT NULL | First name | IS:111 |
| apellidos | varchar(150) NOT NULL | Surnames | IS:112 |
| avatar_URL | varchar(255) NOT NULL | Avatar file. Seeders use `default.svg`; files live in `avatares/oficiales` (CT:100) | IS:113 |
| estado | char(1) | 1 = active. Set to 1 on CAUCE login (`global_helper.php:791`) | IS:114 |
| created_at, updated_at, deleted_at | | | IS:115-116 |

- **Relations:** `personaUser`, and `dicAulas` (hasMany `dic_aula` by `persona_id`, meaning dictionaries owned by this person).
- `nombreCompleto()` returns "nombre apellidos", or "apellidos, nombre" when surname-first is requested.

### `users_personas` (pivot, intended 1:1)
- **Model:** `UserPersona`, audited, fillable user_id and persona_id.
- **No PK and no unique constraint,** although the model assumes an `id` column.

| Column | Type | FK | Source |
|---|---|---|---|
| user_id | bigint unsigned | users.id | IS:382-383 |
| persona_id | bigint unsigned | personas.id | IS:384-385 |
| created_at, updated_at, deleted_at | | | IS:386-387 |

### `centros`
- **Purpose:** school centres, created on the fly from the CAUCE login data (`global_helper.php:949-1045`). A placeholder "no centre" record exists (`codigoNoCentro`).
- **Model:** `Centro`, audited, fillable cod_centro, denominacion, estado.

| Column | Type | Meaning | Source |
|---|---|---|---|
| id | bigint AI | PK | IS:392 |
| cod_centro | varchar(8) NOT NULL (**not unique**) | Official centre code | IS:393 |
| denominacion | varchar(255) | Centre name | IS:394 |
| estado | char(1) | 0/1 | IS:395 |
| timestamps, deleted_at | | | IS:396-397 |

### `users_centros` (pivot)
- **Model:** `UserCentro`, audited. No PK.

| Column | FK | Source |
|---|---|---|
| centro_id | centros.id | IS:402-403 |
| user_id | users.id | IS:404-405 |
| timestamps, deleted_at | | IS:406-407 |

### `centros_dic_aula`
- Created at IS:415-424 and **dropped** by `2021_02_11_094937_remove_centros_dic_aula_table.php:16`; its `down()` does nothing.
- The orphan model `CentroDicAula` still exists.

## B. Master data (`mst_*`)

All of the tables below are seeded by MS. **The seeder is run automatically from inside the initial migration** (IS:541-543).

| Table | Columns (type) | Meaning / seeded values | Model | Source |
|---|---|---|---|---|
| `mst_ensenanzas` | id; descripcion varchar(255); fecha_baja datetime NULL (**never used**); estado char(1); timestamps; deleted_at | Education stage. 1 Multienseñanza (the `dic_aula` default), 2 Primaria, 3 ESO, 4 Formación profesional | `Ensenanza` (audited) | IS:19-28; MS:176-192 |
| `mst_nivel_estudios` | same shape | Course level. 1 Multiestudio, 2 Infantil, 3-8 "1º–6º Primaria", 9-12 "1º–4º ESO", 13 Concreción Curricular Adaptada, 14-15 Bachillerato, 16 FP Básica, 17-18 PMAR, 19 Otros (fresh-install ids) | `NivelEstudio` | IS:39-48; MS:197-229 |
| `mst_areas_materias` | same shape | Subject/area. 1 Interdisciplinar (the default area), then about 92 subjects. Duplicates in the list are removed by `firstOrCreate` | `AreaMateria` | IS:29-38; MS:234-342 |
| `mst_tipos_diccionario_aula` | id; tipo_diccionario varchar(150) **UNIQUE**; estado; timestamps; deleted_at | Classroom dictionary type. 1 Diccionario general, 2 Canarismos | `TipoDiccionarioAula` | IS:49-57; MS:157-171 |
| `mst_campos_entrada` | id; nombre_campo varchar(150) **UNIQUE**; estado; fecha_baja; timestamps; deleted_at | Catalogue of configurable sense fields. Ids are in RULE-I13 | `CampoEntrada` (hasMany values) | IS:58-67 |
| `mst_campos_valores` | id; mst_campo_entrada_id FK→mst_campos_entrada; descripcion varchar(255) (not unique); estado; fecha_baja; timestamps; deleted_at | Selectable values per field. 1-9 grammatical category, 10-14 gender, 15-16 number, then themes (field 4) and languages (field 9) | `CampoValor` | IS:68-79; MS:76-152 |
| `mst_ensenanzas_estudios` | mst_ensenanzas_id FK; mst_nivel_estudios_id FK; timestamps; deleted_at. **No PK** | Allowed stage × level combinations (seeded as a full cross product) | `EnsenanzaEstudio` | IS:80-89 |
| `mst_estudios_areas_materias` | mst_nivel_estudios_id FK; mst_area_materia_id FK; timestamps; deleted_at. **No PK** | Allowed level × area combinations (full cross product) | `EstudioAreaMateria` | IS:90-99 |
| `mst_pautas` | id; tipo **int** default 1; texto text; estado **int** default 1; timestamps; deleted_at | Global "master" guidelines text shown when creating a classroom dictionary. The first row with estado 1 is used (`DicAulaController.php:94`). The meaning of `tipo` is undocumented | `Pautas` (**not audited**) | `2021_02_03_124026_create_mst_pautas_table.php:16-23` |

## C. Classroom dictionaries

### `dic_aula`
- **Purpose:** a class dictionary owned by a teacher for a school year.
- **Model:** `DicAula`, audited, **SoftDeletes**.
- Model default `vigencia = 1` (`DicAula.php:62-64`). `comentarios_visibles_anteriores_a` is cast as a date.

| Column | Type | Meaning / enum | FK | Source |
|---|---|---|---|---|
| id | bigint AI | PK | | IS:199 |
| persona_id | bigint NOT NULL | Owning teacher (relation `profesor`) | personas.id | IS:200-201 |
| mst_tipo_dic_id | bigint NOT NULL | 1 general, 2 canarismos (app default 1) | mst_tipos_diccionario_aula | IS:202-203 |
| titulo | varchar(150) | Title | | IS:204 |
| descripcion | varchar(255) NULL | | | IS:205 |
| letra_grupo | char(1) NOT NULL | Class group letter. `'0'` means no or multiple groups (CT:433 `multigrupo='0'`; helper default 0) | | IS:206 |
| mst_nivel_estudios_id | bigint NULL | Course level | mst_nivel_estudios | IS:207-208 |
| mst_area_materia_id | bigint NOT NULL | Subject (app default 1, Interdisciplinar) | mst_areas_materias | IS:209-210 |
| mst_ensenanza_id | bigint NOT NULL **default 1** | Stage (1 = Multienseñanza) | mst_ensenanzas | IS:211-212 |
| ano_ini_curso_escolar | smallint | Start year of the school year (2021 means 2021/22) | | IS:213 |
| max_acepciones_entrada | smallint | Maximum senses per entry. The intended default is 10 (CT:182-184), but the helper stores a boolean (RULE-I34) | | IS:214 |
| codigo | varchar(7) (**no unique index**) | Join code. App validation: 4-6 chars, unique across `dic_aula` (`dicAula_helper.php:1593-1599`) | | IS:215 |
| visible_estudiante | char(1) | 0/1: whether participating students can see the published dictionary | | IS:216 |
| envios_habilitados | char(1) | 0 inactive, 1 active, 2 planned (CT:334-338) | | IS:217 |
| envios_fecha_ini | datetime NULL | Planned start for submissions | | IS:218 |
| envios_fecha_fin | datetime NULL | Submission end. **Not in `$fillable`**, so the helper's value is silently dropped (see discrepancies) | | IS:219 |
| estado | char(1) | 0/1. Set to 1 when made atemporal | | IS:220 |
| comentarios_visibles | char(1) default 0 | 0 not visible, 1 visible, 2 only comments before a date (CT:365-369) | | IS:221 |
| comentarios_visibles_anteriores_a | **DATETIME(1)** NULL (precision 1, because the 2nd argument of `dateTime()` is precision) | Cut-off date for value 2 | | IS:222 |
| pautas_maestras | char(1) default 1 | Whether master guidelines apply. **Never read or written by the app** (grep finds no use outside the model's fillable) | | IS:223 |
| vigencia | int NOT NULL, no DB default | Number of school years the dictionary stays current (1..VIGENCIA_MAX=10, CT:482) | | `2021_06_16_074933_add_vigencia_to_dic_aula.php:17` |
| created_at, updated_at, deleted_at | | | | IS:224-225 |

- **Relations:** pauta, dicAulaCampos, destinatarioAvisos, participantes, profesor, envios, atemporal, dicAulaEntradas, personas.

### `dic_aula_atemporales`
- **Purpose:** marks a dictionary as permanent ("atemporal"), not tied to one school year.
- **Model:** `DicAulaAtemporal`, audited. Fillable estado. Treated as 1:1 (hasOne) but has **no unique index**.

| Column | Type | Meaning | Source |
|---|---|---|---|
| id | bigint AI | | `2021_03_09_135756_add_table_dic_aula_dic_permanente.php:17` |
| dic_aula_id | FK dic_aula | | :18-19 |
| estado | char(1) | 1 = atemporal on; 0 = off (`DicAula.php:176-211`) | :20 |
| hasta | timestamp NULL | Atemporal-until date (NULL means open-ended) | :21 |
| created_at | timestamp, default CURRENT_TIMESTAMP | | :23 |
| updated_at | timestamp, ON UPDATE CURRENT_TIMESTAMP | | :24 |
| deleted_at | | | :26 |

### `dic_aula_destinatario_avisos`
- **Model:** `DicAulaDestinarioAviso`, audited.

| Column | Type | Meaning | Source |
|---|---|---|---|
| id | bigint | | IS:230 |
| dic_aula_id | FK dic_aula | | IS:231-232 |
| email | varchar(255) NULL | Notification recipient, or invited co-teacher email | IS:233 |
| estado | char(1) | 1 active; **3 = invited teacher** (`DicAulaController.php:403`) | IS:234 |
| timestamps, deleted_at | | | IS:235-236 |

### `dic_aula_pautas`
- **Purpose:** dictionary-specific guidelines.
- **Model:** `DicAulaPauta`, audited. 1:1 by `updateOrCreate` on dic_aula_id.

| Column | Type | Meaning | Source |
|---|---|---|---|
| dic_aula_id | FK | | IS:242-243 |
| url_fichero | varchar(255) NULL | Attached file (no writer found) | IS:244 |
| texto | text NULL | Guideline HTML | IS:245 |
| estado | char(1) | 0/1 | IS:246 |

### `dic_aula_participantes`
- **Purpose:** membership of a person in a classroom dictionary.
- **Model:** `DicAulaParticipante`, audited.

| Column | Type | Meaning | FK | Source |
|---|---|---|---|---|
| dic_aula_id | bigint | | dic_aula | IS:254-255 |
| persona_id | bigint | | personas | IS:256-257 |
| rol_diccionario_id | bigint | Role **in this dictionary**: docente (coordinator) or alumno, compared against `ctes.rol` 1/2 (CT:68-72) | roles.id | IS:258-259 |
| estado | char(1) | 1 enabled, 0 disabled (habilitar/deshabilitar participant routes, CT:386-392) | | IS:260 |

### `dic_aula_campos`
- **Purpose:** per-dictionary field configuration.
- **Model:** `DicAulaCampo`, audited.
- On every save, one row is upserted for **every** catalogue field (`dicAula_helper.php:89-99`).

| Column | Type | Meaning | Source |
|---|---|---|---|
| dic_aula_id | FK | | IS:268-269 |
| mst_campo_entrada_id | FK mst_campos_entrada | | IS:270-271 |
| visible | char(1) | 0/1: field shown to students (CT:446-449) | IS:272 |
| obligatorio | char(1) | 0/1: field required on submission | IS:273 |
| estado | char(1) | 0/1 | IS:274 |

### `dic_aula_entradas`
- **Purpose:** **published entries** of a classroom dictionary.
- **Model:** `DicAulaEntrada`, audited.

| Column | Type | Meaning | Source |
|---|---|---|---|
| dic_aula_id | FK dic_aula | | IS:364-365 |
| envio_entrada_id | FK envios_entradas | The published submitted entry. The same submitted entry can be published in several dictionaries via import (`dicAula_helper.php:129-142`) | IS:366-367 |
| origen | char(1) | `'1'` = came from a student submission (CT:418-420). Import copies the source value | IS:368 |
| dic_origen | bigint NULL | Intended source dictionary id. **Never written** | IS:369 |
| estado | char(1) | 1 published/visible | IS:370 |

## D. Personal dictionaries

| Table | Column | Type | Meaning / enum | FK | Source |
|---|---|---|---|---|---|
| **`dic_personal`** (`DiccionarioPersonal`, audited, no SoftDeletes trait). One per persona | persona_id | bigint | Owner | personas | IS:127-128 |
| | titulo | varchar(150) | | | IS:129 |
| | estado | char(1) | 0/1 | | IS:130 |
| **`dp_entradas`** (`DiccionarioPersonalEntrada`, **SoftDeletes**; cascades to senses, RULE-I33) | dic_personal_id | bigint | | dic_personal | IS:138-139 |
| | entrada | varchar(150) | Headword (max 150, CT:128-130) | | IS:140 |
| | estado | char(1) | 0 logical delete, 1 visible, 2 hidden (CT:267-271) | | IS:141 |
| **`dp_acepciones`** (`DiccionarioPersonalAcepcion`, **SoftDeletes**). A sense of an entry | dic_entrada_id | bigint | | dp_entradas | IS:149-150 |
| | orden | smallint | Sense order within the entry | | IS:151 |
| | cat_gramatical_id | NULL | Grammatical category (field 1) | mst_campos_valores | IS:152-153 |
| | genero_id | NULL | Gender (field 2) | mst_campos_valores | IS:154-155 |
| | numero_id | NULL | Number (field 3) | mst_campos_valores | IS:156-157 |
| | idioma_id | NULL | Other language (field 9) | mst_campos_valores | IS:158-159 |
| | idioma_palabra | varchar(150) NULL | The word in that language | | IS:160 |
| | definicion | varchar(1000) NOT NULL | Definition (max 1000, CT:142) | | IS:161 |
| | frase_ejemplo | varchar(255) NULL | Field 5, renamed in the UI from "Frase ejemplo" to "Más datos" | | IS:162 |
| | ejemplo2 | varchar(255) NULL | Field 10, "Ejemplo de uso" | | `2022_07_11_120555_add_ejemplo_to_dp_acepciones.php:17` |
| | estado | char(1) | 0/1/2, same as entries | | IS:163 |
| **`dp_acepciones_tematicas`** (`DiccionarioPersonalAcepcionTematica`). **No PK**; the model's default `id` does not exist | dp_acepcion_id | | | dp_acepciones | IS:170-171 |
| | tematica_id | | Theme (field 4 values). Max TEMATICAS_MAX=10 (CT:484) | mst_campos_valores | IS:172-173 |
| **`dp_acepciones_medios`** (`DiccionarioPersonalAcepcionMedio`; deleting the row deletes the file) | dic_acepcion_id | | | dp_acepciones | IS:181-182 |
| | tipo_medio | char(1) | 1 image, 2 audio, 3 video (CT:82-86) | | IS:183 |
| | nombre | varchar(255) | Display/original name | | IS:184 |
| | url_externa | varchar(255) NULL | External URL | | IS:185 |
| | url_interna | varchar(255) NULL | Stored filename under `dp/medios/{imagenes,audios,videos}` (CT:96-99) | | IS:186 |
| | estado | char(1) | | | IS:187 |

## E. Submissions (envíos)

These tables are snapshots of personal-dictionary content sent to a classroom dictionary.

| Table | Column | Type | Meaning / enum | FK | Source |
|---|---|---|---|---|---|
| **`dp_envios`** (`Envio`, audited, no SoftDeletes trait; `borrar()` hard-deletes) | ano_ini_curso_escolar | smallint | School year of the submission | | IS:287 |
| | dic_personal_id | | Student's personal dictionary | dic_personal | IS:288-289 |
| | dic_aula_id | | Target classroom dictionary | dic_aula | IS:290-291 |
| | estado | char(1) | 1 sent, 2 deleted by student (unused), 3 published (CT:282-287) | | IS:292 |
| **`envios_entradas`** (`EnvioEntrada`, **SoftDeletes**) | dp_envio_id | | | dp_envios | IS:300-301 |
| | dp_entrada_id | | Source personal entry | dp_entradas | IS:302-303 |
| | entrada | varchar(150) | Headword snapshot | | IS:304 |
| | estado | char(1) | 0 logical delete, 1 sent, 3 published | | IS:305 |
| | updated_by_persona_id | **int NOT NULL, no default, no FK** | Persona (usually a teacher) who last edited the submitted entry | (personas, logical) | `2021_01_13_125309_add_updated_by_persona_id_to_envios_entradas.php:17` |
| **`envios_acepciones`** (`EnvioAcepcion`) | envio_entrada_id | | | envios_entradas | IS:313-314 |
| | orden, cat_gramatical_id, genero_id, numero_id, idioma_id, idioma_palabra, definicion(1000), frase_ejemplo(255), estado | | Same meaning as `dp_acepciones` | mst_campos_valores | IS:315-327 |
| | ejemplo2 | varchar(255) NULL | "Ejemplo de uso" | | `2022_07_11_124149_add_ejemplo_field_to_envios_acepciones.php:17` |
| **`envios_acepciones_tematicas`** (`EnvioAcepcionTematica`). **No PK** | envio_acepcion_id, tematica_id | | | envios_acepciones / mst_campos_valores | IS:334-337 |
| **`envios_acepciones_medios`** (`EnvioAcepcionMedio`) | envio_acepcion_id, tipo_medio(1/2/3), nombre, url_externa, url_interna, estado | | | envios_acepciones | IS:345-351 |

## F. Comments

| Table | Column | Type | Meaning | FK | Source |
|---|---|---|---|---|---|
| **`comentarios_generales`** (`ComentarioGeneral`, audited). Teacher comment on a student's whole submission | dic_personal_id | | Student's personal dictionary | dic_personal | IS:433-434 |
| | persona_id | | Author | personas | IS:435-436 |
| | dic_aula_id | | | dic_aula | IS:437-438 |
| | comentario | text | HTML | | IS:439 |
| | fecha_envio | datetime NULL | Sent at; used by the `comentarios_visibles=2` filter | | IS:440 |
| | estado | char(1) | 0 not visible, 1 visible & unread, 2 visible & read (CT:349-353). New comments get 1 | | IS:441 |
| **`comentarios_entradas`** (`ComentarioEntrada`). Comment on one submitted entry | envio_entrada_id | | | envios_entradas | IS:449-450 |
| | persona_id, dic_aula_id, comentario, fecha_envio, estado | | Same as above | personas / dic_aula | IS:451-457 |

## G. Notifications and sharing (schema only, no models; effectively dead)

| Table | Columns | Source |
|---|---|---|
| `entradas_compartidas` | id; dic_personal_id FK; dic_aula_entrada_id FK dic_aula_entradas; fecha_comparticion datetime NULL; estado | IS:467-479 |
| `avisos` | id; tipo_aviso char(1); destinatario char(1); dic_personal_id bigint (**no FK**); dic_aula_id bigint (**no FK**); email char(1) (flag, not an address); estado | IS:485-497 |
| `avisos_dp_envios` | avisos_id FK; dp_envios_id FK | IS:498-507 |
| `avisos_coment_generales` | avisos_id FK; comentario_general_id FK | IS:508-517 |
| `avisos_coment_entradas` | avisos_id FK; comentario_entrada_id FK | IS:518-527 |
| `avisos_entradas_compartidas` | avisos_id FK; entrada_compartida_id FK | IS:528-537 |

All link tables have timestamps and deleted_at and no PK. The enum values of `tipo_aviso` and `destinatario` are not defined anywhere. The only code touching these tables is the raw-SQL soft delete of `avisos` in `DicAula::delete`.

## H. Audit

### `audits`
- **Model:** `OwenIt\Auditing\Models\Audit`. Exposed in Voyager as BREAD id 13, server-side, ordered by id desc.
- **Columns** (`2020_04_20_150059_create_audits_table.php:16-31`):
  - id
  - user_type varchar NULL (`user` morph prefix)
  - user_id bigint NULL
  - event varchar (created / updated / deleted / restored)
  - auditable_type + auditable_id (morphs, **indexed**)
  - old_values text NULL, new_values text NULL
  - url text NULL
  - ip_address varchar(45) NULL
  - user_agent varchar(1023) NULL
  - tags varchar NULL
  - timestamps
- **Index:** (user_id, user_type).
- Guards are web and api (`config/audit.php:27-33`).

## I. Voyager / CMS

| Table | Columns | Notes / seeded data | Source |
|---|---|---|---|
| `roles` | id bigint; name varchar UNIQUE; display_name; timestamps | admin, user (Voyager); docente, alumno (`UsersRolesTablesSeeder.php:56-65`) | [vendor] |
| `permissions` | id bigint; key varchar (indexed); table_name varchar NULL; timestamps | browse_admin(1), browse_bread(2), browse_database(3), browse_media(4), browse_compass(5), browse_hooks(26), plus browse/read/edit/add/delete for menus, roles, users, settings, audits_eventos, audits_eventos_tipos, audits | [vendor]; `VoyagerCustomization.php:1866-1897` |
| `permission_role` | permission_id FK; role_id FK; PK (both) | Model `App\Models\Voyager\PermissionRole` (no PK, no timestamps) | [vendor]; `app/Models/Voyager/PermissionRole.php:15-44` |
| `user_roles` | user_id FK; role_id FK; PK (both) | Extra (secondary) roles per user | [vendor] |
| `menus` | id; name UNIQUE; timestamps | 1 admin, 2 "Oficina técnica" | [vendor]; `MenusTableSeeder.php:17-26` |
| `menu_items` | id; menu_id FK; title; url; target default `_self`; icon_class; color; parent_id; order; route; parameters; timestamps | See RULE-I26 | [vendor]; `MenuItemsTableSeeder.php:418-789` |
| `data_types` | id; name UNIQUE; slug UNIQUE; display_name_singular/plural; icon; model_name; policy_name; controller; description; generate_permissions bool; server_side tinyint; details json text; timestamps | BREADs: users(1), menus(2), roles(3), audits(13), categories(14), posts(15), pages(16), dic_aula(24, slug `dic-aulaCursoEscolar`, `vDicAulaController`), dic_personal(26, `vDicPersonalController`), centros(30, slug `centrosBread`, `vCentrosAñoEscolarController`) | [vendor]; `VoyagerCustomization.php:59-232`; `DataTypesTableSeeder.php` |
| `data_rows` | id; data_type_id FK; field; type; display_name; required; browse; read; edit; add; delete; details; order | Admin field layout | [vendor]; `DataRowsTableSeeder.php`, `VoyagerCustomization.php:261-1837` |
| `settings` | id; key UNIQUE; display_name; value text NULL; details; type; order; group | site.title, site.description, site.logo, site.google_analytics_tracking_id, admin.bg_image, admin.title, admin.description, admin.loader, admin.icon_image, admin.google_analytics_client_id. **None drive domain behaviour** | [vendor]; `SettingsTableSeeder.php` |
| `translations` | id; table_name; column_name; foreign_key; locale; value; timestamps; UNIQUE(table_name, column_name, foreign_key, locale) | Voyager i18n of BREAD labels | [vendor] |
| `pages` | id int; author_id int; title; excerpt NULL; body NULL; image NULL; slug UNIQUE; meta_description NULL; meta_keywords NULL; status enum(ACTIVE, INACTIVE) default INACTIVE; timestamps | Voyager dummy CMS | `2016_01_01_000000_create_pages_table.php:17-28` |
| `posts` | id int; author_id int (FK commented out); category_id int NULL; title; seo_title NULL; excerpt; body; image NULL; slug UNIQUE; meta_description; meta_keywords; status enum(PUBLISHED, DRAFT, PENDING) default DRAFT; featured bool default 0; timestamps | excerpt, meta_description and meta_keywords were made nullable later | `2016_01_01_000000_create_posts_table.php:16-30`; `2017_04_11_000000_alter_post_nullable_fields_table.php:19-23` |
| `categories` | id int; parent_id int NULL FK→categories (on update cascade, on delete set null); order int default 1; name; slug UNIQUE; timestamps | | `2016_02_15_204651_create_categories_table.php:16-23` |
| `migrations` | Laravel | Listed in `documentos/tablasLexican.md:71` | framework |

## J. Statistics views (not in migrations; created by hand from `documentos/*.sql`; **not referenced by app code**)

| View | Purpose |
|---|---|
| `diccionariosDatosCursoView` | Counts per school year: active classroom dictionaries (with start year and end year from vigencia), personal dictionaries, centres, and users per role in classroom dictionaries (`documentos/vistas.sql:6-97`) |
| `diccionariosDatosView` | Same counts without the per-year split (:101-165) |
| `ensenanzasNivelAreaMateriaView` | Classroom dictionaries grouped by stage, then stage × level, then stage × level × area, then area alone (:169-262) |
| `diccionarios21View` | Counts for the 2021/22 school year only (:265-331) |
| `ensenanzasNivelAreaMateria21View` | Same grouping for one year (:333). **Filters 2020, not 2021** |
| `v_temp1`, `v_temp2` | Variants for MariaDB 10.1.40 that avoid subqueries in views (`documentos/vistas_10-1-40.sql:4,10`) |
| `v_diccionariosAula_curso`, `v_diccionariosPersonales_curso`, `v_centros_curso`, `v_usuariosRolAula_curso` | Per-school-year split views (`documentos/vistasPorCurso.sql:1-67`) |
| `datosDic2021 .sql`, `datosMaterias2021.sql` | Ad-hoc 2021 queries (not views) |

## K. Mismatches between code and schema

1. **`envios_fecha_fin` is not in `DicAula::$fillable`** (`app/Models/DicAula.php:38-59`), but `daCreateOrUpdateDicAulaByPersonaId` passes it to `updateOrCreate` (`dicAula_helper.php:82`), so it is silently discarded. When it was written elsewhere, the value is `now()`, not a user-chosen end date.
2. **`pautas_maestras` and `dic_origen` exist in the schema but nothing writes or reads them.** The `fecha_baja` columns on master tables are never used.
3. **Pivot tables have no `id` column, but their models assume one:** `users_personas`, `users_centros`, `mst_ensenanzas_estudios`, `mst_estudios_areas_materias`, `dp_acepciones_tematicas`, `envios_acepciones_tematicas`. Instance-level save, update or delete on these models will fail or behave unexpectedly; the code uses query-builder deletes instead.
4. **The `CentroDicAula` model points at a dropped table.**
5. **`DicAula::personas()` uses wrong keys** (`app/Models/DicAula.php:355-365`, all keys `'id'`). It joins `personas.id = dic_aula_participantes.id`, not `persona_id`.
6. **`DicAulaEntrada::tematicas()` uses columns that do not exist on the envios tables** (`dic_entrada_id`, `dp_acepcion_id`), and builds a filter it never returns (`app/Models/DicAulaEntrada.php:52-69`).
7. **Type inconsistencies:** `mst_pautas.estado` is `int` while every other `estado` is `char(1)`. `envios_entradas.updated_by_persona_id` is a signed `int` NOT NULL with no FK and no default, while personas ids are `bigint`.
8. **`users.role_id`, `avatar` and `settings` come only from vendor migrations.** The repo's users migration does not create them.
9. **Initial-structure FK to `roles`** (IS:259): `roles` comes from Voyager vendor migrations. A fresh `migrate` works only because Voyager registers its migrations and its filenames sort before 2020.

---

## Rules that use each table

| Table | Rules |
|---|---|
| `admin` | RULE-004, RULE-007, RULE-016, RULE-063, RULE-136, RULE-137, RULE-138, RULE-140, RULE-143, RULE-159, RULE-167, RULE-168, RULE-169, RULE-173, RULE-175, RULE-176, RULE-189, RULE-192, RULE-201, RULE-202, RULE-203, RULE-204, RULE-214, RULE-221, RULE-225, RULE-238, RULE-265, RULE-279, RULE-281, RULE-282, RULE-298 |
| `alumno` | RULE-003, RULE-004, RULE-061, RULE-122, RULE-134, RULE-135, RULE-136, RULE-137, RULE-167, RULE-173, RULE-199, RULE-201, RULE-203, RULE-206, RULE-208, RULE-213, RULE-221 |
| `audits` | RULE-170, RULE-172, RULE-204 |
| `avatar` | RULE-037, RULE-089, RULE-173 |
| `avisos` | RULE-118 |
| `centros` | RULE-013, RULE-038, RULE-189, RULE-281 |
| `centros_dic_aula` | RULE-118 |
| `comentarios_entradas` | RULE-118, RULE-187, RULE-188 |
| `comentarios_generales` | RULE-045, RULE-118, RULE-120, RULE-155 |
| `comentarios_visibles_anteriores_a` | RULE-183, RULE-185 |
| `deleted_at` | RULE-013, RULE-024, RULE-118, RULE-126, RULE-127, RULE-147, RULE-171 |
| `dic_aula` | RULE-001, RULE-002, RULE-006, RULE-011, RULE-024, RULE-025, RULE-042, RULE-043, RULE-048, RULE-049, RULE-064, RULE-070, RULE-071, RULE-085, RULE-118, RULE-119, RULE-140, RULE-141, RULE-142, RULE-143, RULE-183, RULE-189, RULE-223, RULE-231, RULE-243, RULE-261 |
| `dic_aula_atemporales` | RULE-006, RULE-066, RULE-118, RULE-139, RULE-142 |
| `dic_aula_campos` | RULE-010, RULE-118, RULE-195, RULE-251, RULE-254 |
| `dic_aula_destinatario_avisos` | RULE-118, RULE-153 |
| `dic_aula_entradas` | RULE-056, RULE-057, RULE-118, RULE-128, RULE-129, RULE-130, RULE-131, RULE-148, RULE-150, RULE-152, RULE-190, RULE-198, RULE-242 |
| `dic_aula_participantes` | RULE-053, RULE-118, RULE-133, RULE-134, RULE-193, RULE-206, RULE-219 |
| `dic_aula_pautas` | RULE-118, RULE-244, RULE-285 |
| `dic_entrada_id` | RULE-207 |
| `dic_personal` | RULE-089, RULE-124, RULE-171 |
| `docente` | RULE-003, RULE-004, RULE-023, RULE-053, RULE-058, RULE-061, RULE-062, RULE-063, RULE-133, RULE-134, RULE-135, RULE-136, RULE-173, RULE-199, RULE-201, RULE-203, RULE-206, RULE-208, RULE-209, RULE-213, RULE-219, RULE-221, RULE-264, RULE-292 |
| `dp_acepciones` | RULE-126, RULE-127, RULE-196, RULE-255 |
| `dp_acepciones_medios` | RULE-146, RULE-166 |
| `dp_acepciones_tematicas` | RULE-270 |
| `dp_entradas` | RULE-092, RULE-125, RULE-126 |
| `dp_envios` | RULE-008, RULE-074, RULE-118, RULE-149, RULE-152, RULE-229, RULE-232, RULE-272 |
| `envios_acepciones` | RULE-144, RULE-227, RULE-229, RULE-255 |
| `envios_acepciones_medios` | RULE-274, RULE-278 |
| `envios_acepciones_tematicas` | RULE-249, RULE-270 |
| `envios_entradas` | RULE-008, RULE-025, RULE-056, RULE-074, RULE-107, RULE-118, RULE-129, RULE-147, RULE-171, RULE-189, RULE-190, RULE-198, RULE-229, RULE-232, RULE-235, RULE-239, RULE-272, RULE-297 |
| `envios_fecha_fin` | RULE-001, RULE-009, RULE-183, RULE-230 |
| `estado` | RULE-001, RULE-006, RULE-007, RULE-008, RULE-016, RULE-017, RULE-023, RULE-024, RULE-025, RULE-029, RULE-037, RULE-044, RULE-047, RULE-050, RULE-056, RULE-057, RULE-058, RULE-065, RULE-066, RULE-072, RULE-083, RULE-084, RULE-099, RULE-100, RULE-114, RULE-120, RULE-122, RULE-123, RULE-124, RULE-125, RULE-126, RULE-128, RULE-129, RULE-130, RULE-133, RULE-134, RULE-138, RULE-139, RULE-140, RULE-141, RULE-142, RULE-144, RULE-147, RULE-149, RULE-150, RULE-152, RULE-153, RULE-154, RULE-155, RULE-157, RULE-158, RULE-159, RULE-160, RULE-161, RULE-162, RULE-165, RULE-166, RULE-178, RULE-183, RULE-184, RULE-189, RULE-190, RULE-195, RULE-198, RULE-199, RULE-214, RULE-216, RULE-219, RULE-224, RULE-225, RULE-227, RULE-232, RULE-234, RULE-243, RULE-244, RULE-248, RULE-260, RULE-268, RULE-272, RULE-274, RULE-276, RULE-285, RULE-292, RULE-299 |
| `fecha_baja` | RULE-260 |
| `int` | RULE-041, RULE-098, RULE-226 |
| `menus` | RULE-281 |
| `migrations` | RULE-093 |
| `mst_campos_entrada` | RULE-194, RULE-195, RULE-251, RULE-254, RULE-255 |
| `mst_campos_valores` | RULE-196, RULE-268, RULE-296 |
| `mst_pautas` | RULE-172, RULE-243 |
| `pages` | RULE-271 |
| `pautas_maestras` | RULE-243 |
| `permission_role` | RULE-170 |
| `permissions` | RULE-167, RULE-169, RULE-202, RULE-204, RULE-206 |
| `persona_id` | RULE-001, RULE-060, RULE-064, RULE-070, RULE-073, RULE-117, RULE-120, RULE-124, RULE-154, RULE-183, RULE-193, RULE-200, RULE-205, RULE-210, RULE-211, RULE-223, RULE-275 |
| `personas` | RULE-037, RULE-116, RULE-169, RULE-182, RULE-193 |
| `posts` | RULE-120, RULE-237 |
| `roles` | RULE-003, RULE-004, RULE-038, RULE-039, RULE-167, RULE-170, RULE-177, RULE-203, RULE-206 |
| `settings` | RULE-183, RULE-195, RULE-282 |
| `tipo` | RULE-076, RULE-087, RULE-183, RULE-243 |
| `user` | RULE-003, RULE-023, RULE-033, RULE-038, RULE-039, RULE-045, RULE-051, RULE-053, RULE-054, RULE-058, RULE-059, RULE-061, RULE-062, RULE-063, RULE-070, RULE-071, RULE-078, RULE-079, RULE-081, RULE-089, RULE-090, RULE-092, RULE-096, RULE-104, RULE-107, RULE-108, RULE-109, RULE-110, RULE-113, RULE-114, RULE-115, RULE-116, RULE-117, RULE-119, RULE-120, RULE-122, RULE-125, RULE-137, RULE-151, RULE-154, RULE-161, RULE-167, RULE-168, RULE-169, RULE-170, RULE-173, RULE-174, RULE-175, RULE-176, RULE-177, RULE-178, RULE-180, RULE-181, RULE-182, RULE-184, RULE-186, RULE-188, RULE-189, RULE-191, RULE-192, RULE-199, RULE-200, RULE-201, RULE-202, RULE-203, RULE-204, RULE-205, RULE-207, RULE-208, RULE-209, RULE-210, RULE-211, RULE-213, RULE-214, RULE-215, RULE-216, RULE-218, RULE-219, RULE-220, RULE-221, RULE-224, RULE-225, RULE-233, RULE-237, RULE-238, RULE-239, RULE-240, RULE-242, RULE-249, RULE-250, RULE-251, RULE-265, RULE-271, RULE-272, RULE-273, RULE-277, RULE-280, RULE-281, RULE-296, RULE-298, RULE-299, RULE-301, RULE-303 |
| `users` | RULE-004, RULE-023, RULE-062, RULE-113, RULE-134, RULE-137, RULE-170, RULE-173, RULE-182, RULE-208, RULE-209, RULE-213, RULE-221, RULE-237, RULE-245, RULE-250 |
| `users_centros` | RULE-115 |
| `users_personas` | RULE-117 |

## Column notes gathered by each extraction shard

### Shard A: DicAulaController + Policies

- **dic_aula**
  - persona_id: owner, overwritten on save.
  - titulo: varchar 150 (validator allows 255).
  - descripcion.
  - mst_tipo_dic_id: forced 1.
  - letra_grupo: char1, default 0.
  - mst_nivel_estudios_id.
  - mst_area_materia_id: default 1.
  - mst_ensenanza_id: default 1, not set.
  - ano_ini_curso_escolar: school-year start year.
  - max_acepciones_entrada: bug, stored 1/0; default intended 10.
  - codigo: varchar 7; 4-6 chars; globally unique.
  - visible_estudiante: 0 hidden / 1 visible to students.
  - envios_habilitados: 0 inactivo / 1 activo / 2 planificado.
  - envios_fecha_ini: submission start.
  - envios_fecha_fin: never written.
  - estado: 0 inactive or non-vigente / 1 active.
  - comentarios_visibles: 0 none / 1 all / 2 before date.
  - comentarios_visibles_anteriores_a.
  - pautas_maestras: default 1.
  - vigencia: number of school years, default 1, max 10.
  - deleted_at: soft delete.
- **dic_aula_atemporales** (migration "add_table_dic_aula_dic_permanente"): dic_aula_id, estado (1 atemporal / 0 off), hasta (nullable timestamp, meaning unclear), deleted_at.
- **dic_aula_participantes**: dic_aula_id, persona_id, rol_diccionario_id (→ roles; 1 docente/coordinator, 2 alumno), estado (1 active / 0 disabled), deleted_at.
- **dic_aula_campos**: dic_aula_id, mst_campo_entrada_id (1 cat. gramatical, 2 género, 3 número, 4 temáticas, 5 frase ejemplo, 6 vídeo, 7 audio, 8 imagen, 9 lengua/idioma, 10 ejemplo2), visible 0/1, obligatorio 0/1, estado 1.
- **dic_aula_pautas**: dic_aula_id (1:1), texto, url_fichero, estado 1.
- **mst Pautas** (master guidelines): estado 1 must exist for the edit page.
- **dic_aula_destinatario_avisos**: dic_aula_id, email, estado (3 = teacher invited).
- **dic_aula_entradas** (publication link, hard-deleted): dic_aula_id, envio_entrada_id, origen (1 = envío), estado (1 visible).
- **envios_entradas**: dp_envio_id, dp_entrada_id, entrada (title, max 150), estado (0 borrado_logico / 1 enviado / 2 borrado_estudiante, also written as "oculta" on unpublish / 3 publicado), updated_by_persona_id.
- **envios_acepciones**: estado (1 visible / 2 oculta). Also envios_acepciones_tematicas and envios_acepciones_medios (force-deleted with the submission).
- **dp_envios**: dic_aula_id, dic_personal_id, ano_ini_curso_escolar, estado (1 enviado); created_at is used for the date filter.
- **comentarios_entradas**: dic_aula_id, envio_entrada_id, persona_id (receives a user id, bug), comentario, fecha_envio, estado 1.
- **comentarios_generales, avisos**: soft-deleted on dictionary delete.
- **users.role_id / roles.name**: 'docente' (config id 1), 'alumno' (2), 'admin', 'user'.
- **Config**: ctes.estados 0/1; ctes.rol docente '1' / alumno '2'; ctes.vigencia_max 10; ctes.inicio_curso '30/8'; ctes.scrollEntradas 10; ctes.tematicas_max 10; ctes.constantes_entradas.max_entrada 150; ctes.dic_aula.max_acepciones 10.

#### Comment/code discrepancies (not rules)

- `app/Helpers/global_helper.php:444`: the docblock says the school year starts on 1 August; the code uses config 30/8.
- `app/Helpers/dicAula_helper.php:400`: the docblock describes a 7-character composite code (2 course + 1 group + 4 teacher); the code uses random 4-6 character codes.
- `app/Helpers/dicAula_helper.php:327-343`: "último diccionario unido" (last joined), but there is no ordering.
- `app/Models/DicAula.php:239,288`: the comment says comments "posteriores" (after) the date; the code returns earlier ones.
- `app/Helpers/dicAula_helper.php:936`: the guard comment implies published submissions are protected; the property it reads does not exist.
- No `dic_aula_dic_permanente` table exists. The migration with that name creates `dic_aula_atemporales`.
- No instruction-shaped or AI-directed text was found.

#### Files
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Http/Controllers/DicAulaController.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Policies/DicAulaPolicy.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Helpers/dicAula_helper.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Helpers/global_helper.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Models/DicAula.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/config/ctes.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/routes/web.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/resources/views/diccionario/crearDicAula.blade.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/database/migrations/2021_03_09_135756_add_table_dic_aula_dic_permanente.php

### Shard B: dicAula/global helpers

**dic_aula**

| Column | Meaning / values |
|---|---|
| id | — |
| persona_id | owner; overwritten on update |
| titulo, descripcion | title, description |
| mst_tipo_dic_id | default 1 |
| letra_grupo | char(1), default '0' |
| mst_nivel_estudios_id | nullable |
| mst_area_materia_id | default 1 |
| ano_ini_curso_escolar | start year of the school year (B01) |
| max_acepciones_entrada | intended default 10; bug stores 0/1 |
| visible_estudiante | '0' / '1' |
| envios_habilitados | '0' / '1' |
| envios_fecha_ini | start of submissions; null = immediately |
| envios_fecha_fin | set to "now", never read |
| codigo | varchar(7), validated 4–6, unique overall |
| comentarios_visibles | 0 no_visible, 1 visible, 2 anteriores_a |
| comentarios_visibles_anteriores_a | date used with value 2 |
| estado | '0' inactive / expired, '1' active |
| vigencia | int, school years in force; max VIGENCIA_MAX = 10 |

**Other tables**

- **dic_aula_atemporales:** dic_aula_id; estado ('1' = timeless); hasta (nullable timestamp, semantics unclear, B07).
- **dic_aula_participantes:** dic_aula_id; persona_id; rol_diccionario_id (FK roles: 1 docente/editor, 2 alumno in production, ids differ locally); estado ('1' enabled, '0' disabled).
- **dic_aula_campos:** dic_aula_id; mst_campo_entrada_id (1–10, see B17); visible 0/1; obligatorio 0/1; estado.
- **dic_aula_pautas:** dic_aula_id; texto (guidelines specific to the dictionary); estado.
- **dic_aula_entradas** (publication link): dic_aula_id; envio_entrada_id; origen ('1' = from a submission); estado ('1' visible); dic_origen. It has a softDeletes column, but the model has no SoftDeletes trait, so delete is a hard delete.
- **envios_entradas:** id; dp_envio_id; dp_entrada_id; entrada (title, max 150); estado.
  - Per `estados_envios`: 0 logically deleted, 1 enviado, 2 borrado_estudiante (unused), 3 publicado.
  - Unpublish/hide also writes `estados_entrada` values (1 visible / 2 oculta) here. This is the enum collision in B22/B23.
- **envios_acepciones:** envio_entrada_id; estado (1 visible / 2 oculta).
- **envio_acepcion_tematicas:** envio_acepcion_id; tematica_id → mst_campos_valores (campo 4).
- **envios_acepciones_medios:** force-deleted in B24.
- **dp_envios:** dic_aula_id; created_at (send date); dp_diccionario_id → dp_diccionarios.persona_id (student).
- **comentarios_entrada:** dic_aula_id; envio_entrada_id; persona_id (bug: user id written); comentario; fecha_envio; estado (0 hidden, 1 visible unread, 2 visible read).
- **users:** role_id (roles.name 'docente' / 'alumno' / 'admin').
- **users_personas:** user_id, persona_id.
- **mst_campos_valores:** id, mst_campo_entrada_id, descripcion.
- **mst_nivel_estudios:** descripcion ('Multiestudio', 'Infantil', …).
- **Config keys:**
  - `ctes.inicio_curso` (INICIO_CURSO = '30/8/2000', day/month only)
  - `ctes.vigencia_max` (VIGENCIA_MAX = 10)
  - `ctes.tematicas_max` (TEMATICAS_MAX = 10)
  - `ctes.dic_aula.max_acepciones` = 10
  - `ctes.scrollEntradas` = 10
  - `ctes.constantes_entradas.max_entrada` = 150
  - `ctes.constantes_acepciones.max_definicion` = 1000
  - `ctes.constantes_acepciones.max_frase` = 255
  - `ctes.rol` {docente 1, alumno 2}
  - `UPLOAD_MAXSIZE` = 200M

Files read:
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Helpers/dicAula_helper.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Helpers/global_helper.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/config/ctes.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/documentos/Pruebas Fechas vigencia y cursoActual.md
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Models/DicAula.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Http/Controllers/DicAulaController.php

### Shard C: Personal dictionary

- **dic_personal**: id, persona_id (FK personas; no unique constraint), titulo (varchar 150, default "Mi diccionario personal"), estado (char 1: '0' inactive / '1' active), timestamps, deleted_at.
- **dp_entradas**: id, dic_personal_id, entrada (varchar 150; no unique index), estado (char 1: '0' logically deleted, '1' visible/active, '2' hidden), timestamps, deleted_at (SoftDeletes; all deletes here are soft).
- **dp_acepciones**: id, dic_entrada_id, orden (smallint, 1-based, kept with no gaps), cat_gramatical_id / genero_id / numero_id / idioma_id (FK mst_campos_valores; campo 1 / 2 / 3 / 9), idioma_palabra (150), definicion (1000, required), frase_ejemplo (255), ejemplo2 (255, added 2022-07), estado ('1' visible, '2' hidden), deleted_at. tipologia_id is set but not saved.
- **dp_acepciones_tematicas**: dp_acepcion_id, tematica_id (mst_campos_valores, campo 4). Deleted and re-inserted on every save (hard delete).
- **dp_acepciones_medios**: id, dic_acepcion_id, tipo_medio ('1' image, '2' audio, '3' video), nombre, url_externa. Deleted when their sense is deleted.
- **mst_campos_valores**: mst_campo_entrada_id (1 category, 2 gender, 3 number, 4 tag/temática, 9 language), descripcion.
- **personas**: avatar_URL (written on avatar change; also stored in session `userData.persona.avatar_URL`).
- **envios / envios_entradas** (read, plus write on delete): envios.ano_ini_curso_escolar (submissions are scoped to the current school year); envios_entradas.dp_entrada_id, estado ('0' logically deleted, '1' sent/pending, '2' student-deleted (unused), '3' published), deleted_at.

#### Files
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Http/Controllers/DiccionarioPersonalController.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Helpers/dpDiccionarios_helper.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Helpers/dpEntradas_helper.php
- /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Helpers/dpAcepciones_helper.php
- Supporting: /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Policies/DiccionarioPersonalPolicy.php, /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Models/DiccionarioPersonalAcepcion.php, /Users/ernesto/Dropbox/Trabajo/ate/lexican/app/Helpers/global_helper.php, /Users/ernesto/Dropbox/Trabajo/ate/lexican/config/ctes.php, /Users/ernesto/Dropbox/Trabajo/ate/lexican/database/migrations/2020_04_19_113507_create_initial_structure.php

### Shard D: Submissions

**dp_envios** (model Envio), written on every send:
- dic_personal_id
- dic_aula_id
- ano_ini_curso_escolar: school-year start year
- estado: estados_envios. 0 = borrado_logico (envios_entradas only), 1 = enviado, 2 = borrado_estudiante (unused), 3 = publicado. Always created as 1.
- created_at: used for "latest" ordering

**envios_entradas** (EnvioEntrada, SoftDeletes):
- dp_envio_id
- dp_entrada_id: source personal entry
- entrada: headword text
- estado: copied from the personal entry (1); later 3 = publicado (dicAula_helper) or 0 = borrado_logico (personal delete via dpEnvio_EnvioEntradaEdit)
- updated_by_persona_id: 0 = unmodified, otherwise the persona who edited
- deleted_at

**envios_acepciones** (EnvioAcepcion): envio_entrada_id, orden, cat_gramatical_id, genero_id, numero_id, idioma_id, idioma_palabra, definicion (≤1000 on edit), frase_ejemplo (≤255 on edit), ejemplo2, estado (copied).

**envios_acepciones_tematicas**: envio_acepcion_id, tematica_id (refers to mst CampoValor). Always replace-all.

**envios_acepciones_medios**: envio_acepcion_id, tipo_medio (1 imagen, 2 audio, 3 video), nombre, url_externa, url_interna (shared file with the personal media), estado (copied; 1 on teacher upload).

**dic_aula** (read):
- envios_habilitados: 0/1
- envios_fecha_ini: submission start date; null = immediately
- titulo
- estado: 1 active
- ano_ini_curso_escolar

**dic_aula_campos** (read): mst_campo_entrada_id 1-9, with meanings as in RULE-D10; estado (1 active); obligatorio (1 mandatory).

**mst_campos_entrada** (read): nombre_campo, used in error messages.

**dic_entradas_personal / DiccionarioPersonalEntrada** (read; estado written by RULE-D05):
- dic_personal_id
- entrada
- estado: 0 borrado_logico, 1 visible, 2 oculta

**DiccionarioPersonalAcepcion** (read): dic_entrada_id, orden, estado and the copied fields; dp_acepciones_tematicas pivot (dp_acepcion_id, tematica_id).

**DiccionarioPersonalAcepcionMedio** (read): dic_acepcion_id, tipo_medio, nombre, url_externa, url_interna, estado.

**dic_aula participants** (read via daConectadosByPersonaId): persona_id, estado (1 active). Used for the UI list only; not enforced on send.

**dic_aula_entradas**: not touched by this shard. Publication is in `app/Helpers/dicAula_helper.php:715-810`.

### Shard E: Comments/mail/home

- **`comentarios_generales`**:
  - `id`; `dic_personal_id` (the target student's latest personal dictionary); `persona_id` (the author); `dic_aula_id`; `comentario` (rich HTML); `fecha_envio` (datetime, set on every save).
  - `estado`: `ctes.estado_comentario_entrada` 0=not visible, 1=visible and unread (always written), 2=visible and read (never written).
  - Hard delete.
- **`comentarios_entradas`**: `id`, `dic_aula_id`, `envio_entrada_id` (via `envioEntrada`), `comentario`, `fecha_envio`, `estado`. Read-only in this shard; it is written by `DicAulaController::comentar/comentarioEdit`.
- **`dic_aulas`**:
  - `comentarios_visibles`: 0=never, 1=always, 2=only comments sent before the cut-off.
  - `comentarios_visibles_anteriores_a`: date.
  - `estado`: 0/1. `ano_ini_curso_escolar`. `titulo`. `codigo` (join code). `persona_id` (owner). `envios_habilitados`: 0/1. `envios_fecha_ini`.
- **`dic_aula_participantes`** (relation `participantes`):
  - `persona_id`; `estado` (1=active); `rol_diccionario_id` (1=docente/coordinator, 2=alumno).
- **`dic_aula_destinatario_avisos`**: `email`, `dic_aula_id`, `estado` (3=invited teacher; other values unknown), `deleted_at` (bulk soft-deleted with the classroom, `DicAula.php:387`).
- **`envios_entradas`** (`EnvioEntrada`): `dp_entrada_id`, `dp_envio_id` → `dp_envios.dic_aula_id`.
- **`diccionarios_personales`**: `id`, `persona_id`. The latest is chosen by max id.
- **`users`**: `email`, `password`, `role_id` (Voyager roles 'admin', 'user'; others go to the personal-dictionary home).
- **Cookie** `lxcn_acceptcookies`; storage path `public/tinyUploads/`.

### Shard F: Auth CAS/CAUCE/config

- **personas:** `NIF_NIE`, `pasaporte`, `cial` (identity, match order NIF → passport → CIAL), `nombre`, `apellidos` (overwritten from CAUCE each login), `avatar_URL` (default 'default.svg' on create), `estado` (1 = active on create; not checked at login).
- **users:** `name` (= CAS login ID, unique lookup key), `email` (= CAS login ID for CAS users; real email for self-registered), `password` (shared hash for CAS users), `role_id` (FK roles; set once), `avatar` ('users/default.png').
- **roles:** `name` ∈ {admin, user, docente, alumno}. In pre/pro docente=1, alumno=2; in local 3/4. 'admin' has all permissions; 'user' has browse_admin, browse_compass and audit read.
- **permissions / permission_role:** browse_admin, browse_compass, browse_/read_audits*.
- **users_personas:** `user_id`, `persona_id` (1:1 link, created on first login).
- **users_centros:** `user_id`, `centro_id`, timestamps (fully replaced each login).
- **centros:** `cod_centro` (CAUCE code; '00000000' = no-centre placeholder), `denominacion` (from CAUCE on create only), `estado` (1 on create), `created_at` (stats bucketing), `deleted_at`.
- **dic_aula:** `estado` (0 inactive / 1 active), `vigencia` (years valid, >0), `ano_ini_curso_escolar` (school-year start year), `titulo`, `deleted_at`.
- **dic_aula_atemporal** (via `DicAula->atemporal`): `estado` 1 = timeless active.
- **dic_aula_participantes:** `dic_aula_id`, `rol_diccionario_id` (reuses ctes.rol IDs: docente/alumno).
- **dic_aula_entradas:** `envio_entrada_id`, `dic_aula_id`.
- **envios_entradas:** `estado` (0 logical delete, 1 sent, 2 deleted by student (unused), 3 published), `deleted_at`.
- **dic_personal, dp_entradas:** `created_at`, `deleted_at` (statistics only).
- **audits:** standard owen-it columns (event, auditable_type/id, old/new_values, user_type/id, url, ip_address, user_agent).
- **Session keys:** `userData` {user, persona, centros, roles}, `cas_user` (CAS login ID).
- **CAUCE XML fields read:** `MensajeError`; `Usuario.InfoUsuario.{NifNie, Pasaporte, CIAL, Nombre, Apellidos}`; `Centro.InfoCentro` (one object or a list) with `{Codigo, Nombre, Rol}`. Rol codes: 1 Pincel teacher, 3 Pincel student, 4 teacher-centre staff, 5 educational technician.

### Shard G: Media/exports/models

- `dp_acepciones_medios`: dic_acepcion_id, tipo_medio (1=imagen, 2=audio, 3=video), nombre (original filename), url_externa (always ""), url_interna (stored filename), estado (1=activo, 0=inactivo). Hard-deleted.
- `envios_acepciones_medios`: envio_acepcion_id, tipo_medio, nombre, url_externa, url_interna (same file as the personal copy), estado.
- `dp_entradas`: id, dic_personal_id, entrada, estado (0=borrado_logico, 1=visible, 2=oculta), deleted_at, created_at.
- `dp_acepciones`: id, dic_entrada_id, orden, estado (same enum), cat_gramatical_id, genero_id, numero_id, idioma_id, deleted_at.
- `dp_acepciones_tematicas`: dp_acepcion_id, tematica_id. `envios_acepciones_tematicas`: envio_acepcion_id, tematica_id.
- `dic_personal`: id, persona_id, titulo, created_at, deleted_at (column exists, model has no SoftDeletes).
- `envios_entradas`: id, dp_envio_id, dp_entrada_id, dic_aula_id, entrada, estado (0=borrado_logico, 1=enviado, 2=borrado_estudiante (unused), 3=publicado), updated_by_persona_id, deleted_at.
- `envios_acepciones`: id, envio_entrada_id, orden, cat_gramatical_id, genero_id, numero_id, idioma_id.
- `dp_envios`: id, dic_personal_id, dic_aula_id, deleted_at.
- `dic_aula`: id, persona_id (teacher/owner), titulo, ano_ini_curso_escolar, vigencia (default 1, max 10), estado (1=activo), envios_habilitados (0/1/2=planificado), envios_fecha_ini, comentarios_visibles (0 none / 1 all / 2 before date), comentarios_visibles_anteriores_a, visible_estudiante, max_acepciones_entrada, codigo, deleted_at.
- `dic_aula_entradas`: dic_aula_id, envio_entrada_id, origen, estado (1=visible in the classroom dictionary).
- `dic_aula_participantes`: dic_aula_id, persona_id, rol_diccionario_id, deleted_at.
- `dic_aula_atemporales`: dic_aula_id, estado (1 = timeless), deleted_at.
- `avisos`, `comentarios_entradas` (fecha_envio, persona_id, envio_entrada_id, dic_aula_id), `comentarios_generales` (dic_personal_id, persona_id, dic_aula_id, comentario, fecha_envio, estado), `dic_aula_campos` (mst_campo_entrada_id, visible, obligatorio, estado), `dic_aula_destinatario_avisos` (email, estado), `dic_aula_pautas` (texto, estado): all soft-deleted via raw SQL in G21.
- `centros` (cod_centro, denominacion, estado, created_at, deleted_at), `users_centros`, `users_personas` (user_id, persona_id), `personas` (nombre, apellidos).
- `mst_campos_entrada` / `mst_campos_valores` (descripcion used for abbreviations; IDs: category 1-9, gender 10-14 with 14="No tiene", number 15-16).
- `audits` (owen-it, database driver).

### Shard H: Blade views

- **dic_aula**:
  - `persona_id`: the owner. The `isOwner` check uses it; it is overwritten on every save (see H14).
  - Descriptive fields: `titulo` (max 255), `descripcion`, `mst_tipo_dic_id` (always 1), `letra_grupo` (A–Z or 0), `mst_nivel_estudios_id`, `mst_area_materia_id`.
  - `ano_ini_curso_escolar`: first year of the school year; client-supplied (H15).
  - `vigencia`: number of school years the dictionary stays current, 1..10.
  - `max_acepciones_entrada`: buggy, stores 1 (H16).
  - `visible_estudiante`: 0 not visible / 1 visible.
  - `envios_habilitados`: 0 off / 1 on / 2 scheduled.
  - `envios_fecha_ini`: submissions start date. `envios_fecha_fin` is set to now when a start date is given.
  - `codigo`: join code, 4–6 chars, globally unique.
  - `comentarios_visibles`: 0 not visible / 1 visible / 2 visible before date. `comentarios_visibles_anteriores_a` holds that date (must be ≤ today).
  - `estado`: 0 inactive (not current, can't be edited) / 1 active.
- **dic_aula_campos**: `dic_aula_id`, `mst_campo_entrada_id` (1 categoría, 2 género, 3 número, 4 temáticas, 5 frase_ejemplo/"más datos", 6 vídeo, 7 audio, 8 imagen, 9 lengua/idioma, 10 ejemplo2), `visible` 0/1, `obligatorio` 0/1, `estado`.
- **dic_aula_participantes**: `persona_id`, `dic_aula_id`, `rol_diccionario_id` (1 coordinator/teacher, 2 student), `estado` (1 access enabled, 0 disabled).
- **dic_aula_entradas** (publication): `dic_aula_id`, `envio_entrada_id`, `origen` (1 = from submission), `estado` (1 = published). Unpublishing deletes the row.
- **dic_aula_pautas**: `dic_aula_id`, `texto`, `estado`.
- **envios**: `dic_personal_id`, `dic_aula_id`, `ano_ini_curso_escolar`, `estado`.
- **envios_entradas**: `dp_entrada_id`, `dp_envio_id`, `entrada`, `estado` (0 deleted, 1 sent, 2 student-deleted (unused) / also reused as hidden, 3 published), `updated_by_persona_id`.
- **envios_acepciones**: `orden`, `definicion`, `frase_ejemplo` ("más datos"), `ejemplo2`, `idioma_id`, `idioma_palabra`, `cat_gramatical_id`, `genero_id`, `numero_id`, `estado` (1 visible / 2 hidden).
- **envios_acepciones_tematicas**, **envios_acepciones_medios**: tipo_medio 1 image / 2 audio / 3 video.
- **dp_entradas**: `dic_personal_id`, `entrada` (max 150), `estado` (0 logically deleted / 1 visible / 2 hidden).
- **dp_acepciones**: `orden`, `definicion` (max 1000), `frase_ejemplo` (255), `ejemplo2` (255), `idioma_palabra` (150), `estado` 1/2.
- **dp_acepciones_medios**: `tipo_medio` 1/2/3, `url_interna`, `nombre`.
- **comentarios_entradas**: `envio_entrada_id`, `dic_aula_id`, `comentario` (HTML), `fecha_envio`.
- **comentarios_generales**: `dic_aula_id`, student `persona_id`, author, `comentario`, `fecha_envio`.
- **users.role_id**: 1 docente / 2 alumno in production; the config comment says local installs use 3/4.
- **pautas** (master): `texto`, `estado`.
- **mst_campos_entrada**: `id`, `nombre_campo`.

### Shard I: Migrations/seeders

- **dic_aula:**
  - `estado` 0/1
  - `visible_estudiante` 0/1
  - `envios_habilitados` 0 inactive / 1 active / 2 planned
  - `comentarios_visibles` 0 / 1 / 2 (before a date)
  - `letra_grupo` '0' = no group
  - `mst_ensenanza_id` default 1 (Multienseñanza); `mst_area_materia_id` default 1 (Interdisciplinar); `mst_tipo_dic_id` 1 general / 2 canarismos
  - `vigencia` default 1, max 10
  - `codigo` 4-6 chars (column 7)
  - `max_acepciones_entrada` default 10 (bug)
  - `pautas_maestras` unused; `envios_fecha_fin` not fillable
- **dic_aula_atemporales:** `estado` 1 = permanent; `hasta`
- **dic_aula_participantes:** `rol_diccionario_id` (docente / alumno role ids); `estado` 1 enabled / 0 disabled
- **dic_aula_campos:** `visible` and `obligatorio` 0/1
- **dic_aula_entradas:** `origen` '1' = from submission; `dic_origen` unused; `estado` 1 published
- **dic_aula_destinatario_avisos:** `estado` 1 active / 3 invited teacher
- **dp_entradas, dp_acepciones:** `estado` 0 deleted / 1 visible / 2 hidden
- **dp_acepciones_medios, envios_acepciones_medios:** `tipo_medio` 1 image / 2 audio / 3 video
- **dp_envios, envios_entradas:** `estado` 0 (entries only) / 1 sent / 2 unused / 3 published; `updated_by_persona_id`
- **comentarios_generales, comentarios_entradas:** `estado` 0 hidden / 1 unread / 2 read
- **mst_campos_entrada:** ids 1-10 per `ctes.mst_campos_entrada`
- **mst_campos_valores:** 1-9 category, 10-14 gender, 15-16 number, themes, languages
- **mst_pautas:** `tipo`, `estado` (int)
- **users.role_id; roles.name:** admin, user, docente, alumno
- **permissions.key, permission_role**
- **menus:** admin and Oficina técnica; **menu_items; data_types; settings**
- **personas:** `cial`, `NIF_NIE`, `pasaporte` (identity keys), `estado`
- **centros.cod_centro** (not unique)
- **avisos\*** and **entradas_compartidas:** dead schema

