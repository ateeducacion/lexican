# Mapeo de datos legacy → LexiCán

Correspondencia entre el esquema MariaDB/MySQL del LexiCán legacy (Laravel 8) y el modelo PostgreSQL nuevo
(`packages/db/src/schema.ts`, [DATA-MODEL.md](DATA-MODEL.md)). La aplica el migrador
`tools/legacy-migrator` (procedimiento en [MIGRATION.md](MIGRATION.md)). Fuentes: `database/migrations/*.php`,
`config/ctes.php`, `analysis/lexican/DATA_OBJECTS.md` y `FUNCTIONAL_INVENTORY.md` §8 y §10.

## Reglas generales

- **Trazabilidad.** Cada fila nueva que procede de una fila legacy guarda `legacy_source` (nombre de la tabla
  legacy) y `legacy_id`. El par es único por tabla destino. «¿Qué registro nuevo corresponde al legacy X?» se
  responde con `where legacy_source = 'tabla' and legacy_id = X`. Los ids nuevos son UUID. El `legacy_id` no se
  expone en la API.
- **Idempotencia.** Todas las escrituras son *upserts* por (`legacy_source`, `legacy_id`), o por la clave natural
  cuando la tabla no tiene columnas legacy (`user_schools`, `dictionary_memberships`, `classroom_settings`,
  `entry_revisions` por (`entry_id`, `number`), `schools.code`, `app_settings.key`). Las acepciones de una entrada se
  reescriben completas en cada ejecución. Una segunda ejecución no crea filas (lo comprueba el test).
- **Maestros por etiqueta, nunca por id.** Los ids de `mst_*` y `roles` cambian entre entornos; el orden de
  producción de `mst_campos_entrada` no coincide con el de un seed limpio. Se casa por etiqueta normalizada
  (recorte, espacios simples, sin mayúsculas ni tildes).
- **Estados.** `estado char(1)` se convierte en `boolean`, `enum` o `deleted_at` según la tabla (detalle abajo).
  `deleted_at` del legacy se conserva como `deleted_at` donde el destino lo tiene; donde no, la fila no se migra
  y cuenta como `skipped`.
- **Nada se arregla en silencio.** Cada corrección automática y cada fila descartada aparece en el informe
  (JSON + CSV) con una clase: `repairable automatically`, `needs rule`, `needs human review` o `cannot migrate`.
- **Fechas.** Laravel guardaba en UTC (`config/app.php`); se leen como UTC.
- **Privacidad (§21).** Las columnas `personas.NIF_NIE`, `cial`, `pasaporte`, `users.password`, `remember_token`,
  `settings` y los emails de avisos **no se leen** (el `select` las omite).
- **Huérfano**: fila cuyo padre no existe o no se migró. Se cuenta en `orphaned` y en `orphans`; con
  `--fail-on-orphans` la transacción se revierte.

Columnas de las métricas por tabla en el informe: `legacy` (filas leídas), `new` (filas destino con ese
`legacy_source`), `mapped`, `skipped` (descartadas por regla, p. ej. borradas), `invalid` (datos que no se pueden
convertir), `orphaned`.

## Orden de carga

El orden de §68, comprobado contra las FK del destino:

1. vocabularios (seed + `mst_*`), porque las acepciones y las aulas los referencian;
2. `users`/`personas` (identidad y `auth_identities`);
3. `centros`, `users_centros`;
4. `dic_personal`;
5. `dp_entradas` con sus acepciones, temáticas y medios (los medios se copian aquí);
6. `dic_aula`, configuración, pautas;
7. miembros (`dic_aula_participantes` + propietario);
8. envíos: `envios_entradas` → `entry_revisions` (instantánea) → `submissions`;
9. publicaciones: `dic_aula_entradas` → entradas de aula, revisión `publish` y `submissions.published_entry_id`;
10. comentarios;
11. pautas maestras (`app_settings`);
12. recuento de tablas no migradas, ficheros sin referencia y verificaciones.

`submissions.revision_id` necesita la revisión, y la revisión necesita la entrada personal origen; por eso los
envíos van después de las entradas y antes de las publicaciones (`entries.source_submission_id`).

---

## Identidad

### `users` → `users` + `auth_identities`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `id` | `users.legacy_id` (`legacy_source='users'`) | — | no | Clave de idempotencia | test: 4 usuarios con `legacy_source='users'` |
| `name` (id de login CAS) | `auth_identities.subject` (`provider='cas'`) | recorte | no | Sin `name` no hay identidad (`cannot migrate`). Repetido (sin mayúsculas): la identidad queda en el primero (`needs human review`) | test: sujetos CAS esperados |
| `email` | `users.email` | recorte | sí | Solo si tiene forma de dirección; los usuarios CAS guardan aquí el login, que no es un email. Repetido: no se migra (`needs human review`) | índice único `lower(email)` |
| `role_id` → `roles.name` | `users.global_role` | `admin`→`admin`, `user` («Oficina técnica»)→`support`, `docente`→`teacher`, `alumno`→`student` | no | Por **nombre** de rol. Desconocido: `student` (`needs rule`) | test: roles esperados |
| `password`, `remember_token`, `email_verified_at`, `settings` | — | no se leen | — | CAS no usa contraseña local; hashes bcrypt no reutilizables | test: sin PII |
| `avatar` (Voyager) | — | — | — | Avatar del panel; se usa `users.avatar='default'` | — |
| `created_at`, `updated_at` | idem | — | no (ahora si nulo) | — | — |

### `personas` → `users`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `nombre` | `users.first_name` | `normalizeText` | no | — | test |
| `apellidos` | `users.last_name` | `normalizeText` | no | — | test |
| (`nombre apellidos`) | `users.display_name` | concatenación | no | Sin persona: «Usuario legacy N» (`needs human review`) | test |
| `NIF_NIE`, `cial`, `pasaporte` | — | **no se leen** | — | §21: CAS identifica por login; no hay uso funcional | verificación «sin columnas de identificadores personales» + test busca los valores ficticios |
| `avatar_URL` | — | — | — | Avatares oficiales SVG; el nuevo modelo usa nombres de avatar propios (`default`) | — |
| `estado` | — | — | — | Siempre 1 al crear; sin uso | — |
| `deleted_at` | `users.status='disabled'` | — | — | — | — |
| persona **sin usuario** | `users` con `legacy_source='personas'`, `status='disabled'`, sin identidad | — | — | Puede poseer datos; se conserva (`needs human review`) | test: 5 usuarios |

### `users_personas`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `user_id`, `persona_id` | (solo en memoria: persona → usuario) | — | — | 1:1 previsto sin restricción. Varias personas por usuario: se usa la última (`needs human review`). Una persona en varios usuarios: se queda con el primero (`needs human review`). Filas con `deleted_at`: `skipped` | métricas |

### `roles`, `user_roles`, `permissions`, `permission_role`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `roles.name` | `users.global_role`, `dictionary_memberships.role` | por nombre | — | Las filas no se migran; solo se usan para traducir ids | `roles.mapped` = filas |
| `user_roles`, `permissions`, `permission_role` | — | no migradas | — | Voyager; los permisos pasan a ser reglas de código (§26) | contadas en `notMigrated` |

### `password_resets`

No se migra: tokens temporales sin valor tras el corte. Contada en `notMigrated`.

## Centros

### `centros` → `schools`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `id` | `legacy_id` | — | — | — | — |
| `cod_centro` | `code` (único) | recorte | no | Repetido (el legacy no lo impide): se fusiona en el primero (`repairable automatically`). `00000000` («sin centro»): no se migra (`repairable automatically`) | test |
| `denominacion` | `name` | `normalizeText` | no | — | — |
| `estado` | — | — | — | Siempre 1 | — |
| `deleted_at` | — | — | — | Borrados: `skipped` | métricas |

### `users_centros` → `user_schools`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `user_id`, `centro_id` | `user_id`, `school_id` | por mapas | no | Centro descartado o fila borrada: `skipped`. Sin usuario o centro: huérfano | métricas |
| — | `role_code` | `null` | sí | El legacy no guarda el rol CAUCE por centro | — |

### `centros_dic_aula`

Tabla eliminada en 2021 (`remove_centros_dic_aula_table`). Contada si existe.

## Datos maestros

Valores destino: `vocabulary_values` sembrados desde `packages/db/src/seed/vocabulary.json` (el migrador ejecuta
`seedVocabulary` antes). Cada valor legacy se casa por etiqueta normalizada con su vocabulario; el valor sembrado
recibe `legacy_source`/`legacy_id` de la primera fila legacy que lo usa. Si no hay equivalente se crea con
`code` = slug de la etiqueta (sufijo `_2`, `_3`… si existe), `position` al final, `featured` si la etiqueta
empezaba por espacio, `active=false` si estaba borrado o con `estado=0`, y se informa como `needs human review`.

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `mst_ensenanzas.descripcion` | `vocabulary_values` (`education_stage`) | por etiqueta | — | — | test: `eso` |
| `mst_nivel_estudios.descripcion` | `study_level` | por etiqueta | — | — | test: `1_eso` |
| `mst_areas_materias.descripcion` | `subject` | por etiqueta | — | Sin equivalente: se crea | test: `robotica_ficticia` creado |
| `mst_tipos_diccionario_aula.tipo_diccionario` | `dictionary_type` | por etiqueta | — | «Canarismos» se mantiene como valor administrable | — |
| `mst_campos_valores.descripcion` | vocabulario según el **campo** (`mst_campos_entrada.nombre_campo`): categoría gramatical→`part_of_speech`, género→`gender`, número→`number`, temáticas→`topic`, lengua/otros lenguajes→`language` | por etiqueta | — | Si el campo no es de vocabulario (bug del seed limpio: idiomas colgados de «Imagen»), el vocabulario lo decide la **columna que lo usa** (`idioma_id`→`language`, `tematica_id`→`topic`…), informado como `repairable automatically`. Etiquetas con espacio inicial (`' inglés'`) casan con el valor sembrado; las nuevas se crean `featured` | test: `GASTRONOMÍA`→`gastronomia`; `' élfico ficticio'` creado |
| `mst_*.estado`, `deleted_at` | `active` (solo valores creados) | — | — | Los sembrados no se tocan | — |
| `mst_*.fecha_baja` | — | — | — | Nunca usado | — |
| `mst_campos_entrada.nombre_campo` | códigos de campo (`SENSE_FIELDS` de `@lexican/core`) | por etiqueta: `Categoría gramatical`→`part_of_speech`, `Género`→`gender`, `Número`→`number`, `Temáticas (generales)`→`topics`, `Más datos`/`Frase ejemplo`→`extra_info`, `Vídeo`→`video`, `Audio`→`audio`, `Imagen`→`image`, `Lengua`/`Lengua-idioma`/`Otros lenguajes`→`language`, `Ejemplo de uso`→`example` | — | Etiqueta desconocida: su configuración por aula se descarta (`needs human review`) | test unitario `fieldCodeOf` |
| `mst_ensenanzas_estudios`, `mst_estudios_areas_materias` | — | no migradas | — | Producto cartesiano sin uso | `notMigrated` |
| `mst_pautas` (primera con `estado=1` sin borrar) | `app_settings` clave `master_guidelines` (texto) | HTML→texto | — | El resto: `skipped`. `tipo` sin uso | métricas |

## Diccionarios personales

### `dic_personal` → `dictionaries` (`kind='personal'`)

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `persona_id` | `owner_id` | persona → usuario | no | Sin persona: huérfano (`cannot migrate`) | — |
| `titulo` | `title` | `normalizeText`; vacío → «Mi diccionario personal» | no | — | — |
| — | `avatar` | `'default'` | sí | Como la app al crear | — |
| `estado` | — | — | — | Sin uso | — |
| `deleted_at` | `deleted_at` | — | sí | El destino admite **un** personal activo por usuario. Si una persona tiene varios, el legacy usa el de id mayor: ese queda activo y los demás borrados (`needs human review`) | test: diccionario 99 borrado |

### `dp_entradas` → `entries`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `dic_personal_id` | `dictionary_id` | mapa | no | Sin diccionario: huérfano | — |
| `entrada` | `headword`, `headword_key`, `initial`, `sort_key` | `normalizeText`, `headwordKey`, `initialOf`, `sortKeyOf` de `@lexican/core` (idénticos a la app) | no | Vacía: `cannot migrate`. Repetida sin distinguir mayúsculas en el mismo diccionario (el legacy no lo impide): la de id mayor se migra **borrada** (`needs human review`) | test: 1005 borrada |
| `estado` | `hidden` / `deleted_at` | `0`→`deleted_at` (= `deleted_at` o `updated_at`), `1`→visible, `2`→`hidden=true` | — | — | test |
| `deleted_at` | `deleted_at` | — | sí | SoftDeletes | test |
| — | `created_by`, `updated_by` | propietario | — | — | — |
| `created_at`, `updated_at` | idem | — | — | — | — |

### `dp_acepciones` → `entry_senses`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `dic_entrada_id` | `entry_id` | mapa | no | — | — |
| `orden` | `position` | orden por (`orden`, `id`) y renumeración 1..n | no | Si no era ya 1..n: `repairable automatically` | verificación «orden contiguo»; test |
| `definicion` | `definition` | — | no | — | — |
| `frase_ejemplo` («Más datos») | `extra_info` | `null`→`''` | no | — | test |
| `ejemplo2` («Ejemplo de uso») | `example` | `null`→`''` | no | — | test |
| `idioma_palabra` | `foreign_form` | `null`→`''` | no | — | test |
| `cat_gramatical_id`, `genero_id`, `numero_id`, `idioma_id` | `part_of_speech_id`, `gender_id`, `number_id`, `language_id` | valor maestro por etiqueta | sí | Referencia rota: vacío (`needs human review`) | test |
| `estado` | `hidden` | `2`→`true`; `0` → no se migra | — | — | test |
| `deleted_at` | — | — | — | Borradas: `skipped` (con sus temáticas y medios) | test: `skipped=1` |

### `dp_acepciones_tematicas` → `sense_topics`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `dp_acepcion_id`, `tematica_id` | `sense_id`, `topic_id` | valor `topic` por etiqueta; duplicados fuera | no | `deleted_at`: `skipped` | test |

### `dp_acepciones_medios` → `media_assets` + `sense_media`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `tipo_medio` | `kind` | `1` imagen, `2` audio, `3` vídeo (carpeta `imagenes`/`audios`/`videos`) | no | Desconocido: `cannot migrate` | — |
| `url_interna` | fichero `<media-source>/dp/medios/<carpeta>/<url_interna>` copiado a `<media-target>/<2 primeros>/<uuid>.<ext>`; `storage_key` | sha256, tipo por contenido | no | Nombre vacío o con separadores/`..`: referencia rota. Fichero ausente: `missing`. Vacío o formato no admitido (bmp, tiff…): `corrupt`. Todos `cannot migrate`. Tipo declarado ≠ contenido: se clasifica por contenido (`needs human review`) | verificación de checksum de cada fichero copiado |
| (contenido) | `sha256`, `byte_size`, `mime` | — | no | **Mismo sha256 → un solo `media_asset`** (`sameContent`) | test: imagen compartida |
| `url_externa` | — | — | — | Nunca se guardó otra cosa que `''`; si hay URL: `cannot migrate` | test 3006 |
| `nombre` | `original_name` | saneado como la app | no | — | — |
| `estado=0`, `deleted_at` | — | — | — | `skipped` | — |
| varios del mismo tipo en una acepción | `sense_media` (único por acepción y tipo) | se conserva el más reciente (`created_at`, `id`) | — | Los demás: `duplicates` (`repairable automatically`) | test 3001 |
| — | `created_by` | propietario del diccionario | — | Permite al alumno leer su medio | — |

Miniaturas `*_thumb.jpg` no se migran ni se regeneran; los ficheros de las carpetas legacy que ninguna fila
referencia se listan como «sin referencia» (`needs human review`). Los originales nunca se modifican ni mueven.

## Diccionarios de aula

### `dic_aula` → `dictionaries` (`kind='classroom'`) + `classroom_settings`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `persona_id` | `dictionaries.owner_id` + membresía `teacher` activa | persona → usuario | no | Sin persona: el aula y todo lo que cuelga son huérfanos | verificación «aulas con profesor» |
| `titulo` | `title` | `normalizeText` | no | — | — |
| `descripcion` | `description` | `null`→`''` | no | — | — |
| `codigo` | `classroom_settings.join_code` | recorte + mayúsculas | no | Se conserva si cumple `[A-Z0-9]{4,6}` y es único en el legacy; si no, se genera uno (alfabeto de la app) (`repairable automatically`). En re-ejecuciones se mantiene el ya asignado | test |
| `ano_ini_curso_escolar` | `school_year` | — | no | — | — |
| `vigencia` | `validity_years` | — | no | Fuera de 1..10: se acota (`repairable automatically`) | test |
| `dic_aula_atemporales` activo | `timeless` | ver abajo | no | — | test |
| `mst_tipo_dic_id`, `mst_ensenanza_id`, `mst_nivel_estudios_id`, `mst_area_materia_id` | `dictionary_type_id`, `education_stage_id`, `study_level_id`, `subject_id` | valor maestro por etiqueta | sí | Referencia rota: vacío (`needs human review`) | test |
| `letra_grupo` | `group_label` | `'0'`→`''` | no | — | test |
| `max_acepciones_entrada` | `max_senses` | `> 1` → valor; si no, `null` | sí | El legacy guardaba un booleano (RULE-I34): `1` significa «sin límite» (`repairable automatically`) | test |
| `visible_estudiante` | `visible_to_students` | `'1'`→`true` | no | — | — |
| `envios_habilitados` | `submissions_enabled` | `'1'`/`'2'`→`true` | no | `2` («planificado») = habilitado con fecha de inicio | test |
| `envios_fecha_ini` | `submissions_start_at` | — | sí | — | — |
| `envios_fecha_fin` | `submissions_end_at` = `null` | — | sí | El legacy nunca la lee y la escribe como «ahora» (DATA_OBJECTS K.1): migrarla cerraría plazos sin querer | test |
| `comentarios_visibles` | `comments_visibility` | `0` hidden, `1` visible, `2` before_date | no | `2` sin fecha: `hidden` (`needs rule`) | test |
| `comentarios_visibles_anteriores_a` | `comments_visible_before` | — | sí | — | — |
| `estado` | — | — | — | Vigencia se calcula al leer (`isClassroomCurrent`) | — |
| `pautas_maestras` | — | — | — | Nunca leído | — |
| `deleted_at` | `dictionaries.deleted_at` | — | sí | Sus entradas publicadas también quedan borradas | — |

### `dic_aula_atemporales`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `estado='1'` sin `deleted_at` | `classroom_settings.timeless=true` | — | — | Otros: `skipped` | test |
| `hasta` | — | — | — | Semántica no definida: si existe, se migra como permanente sin fecha (`needs human review`) | — |

### `dic_aula_campos` → `classroom_settings.visible_fields` / `required_fields`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `mst_campo_entrada_id` | código de campo | por **etiqueta** del campo (orden de producción ≠ seed limpio) | — | Campo desconocido: `invalid` | test |
| `visible` | `visible_fields` | `'1'` | — | Obligatorio implica visible (como la app) | test |
| `obligatorio` | `required_fields` | `'1'` | — | — | test |
| `estado='0'`, `deleted_at` | — | — | — | `skipped` | — |
| (aula sin filas) | todos visibles, ninguno obligatorio | — | — | `repairable automatically` | test 501 |

### `dic_aula_pautas` → `classroom_settings.guidelines`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `texto` (HTML TinyMCE) | `guidelines` | HTML→texto: `<br>` y fin de bloque → salto, `<li>` → «- », entidades decodificadas, sin `<script>/<style>` | no | Se toma la última fila no borrada. Con `<img>`, `<table>`, `<iframe>`, `<video>`, `<audio>`: `needs human review` | test |
| `url_fichero` | — | — | — | Sin escritor conocido; si tiene valor: `needs human review` | — |

### `dic_aula_participantes` → `dictionary_memberships`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `dic_aula_id`, `persona_id` | `dictionary_id`, `user_id` (PK) | mapas | no | Sin aula o persona: huérfano | test |
| `rol_diccionario_id` | `role` | por **nombre**: `docente`→`teacher`, `alumno`→`student` | no | Otro: no se migra (`needs human review`) | test |
| `estado` | `active` | `'1'`→`true` | no | — | test: alumna deshabilitada |
| filas repetidas (sin restricción en legacy) | una membresía | `teacher` gana; activa si alguna lo es | — | `repairable automatically` | test 604 |
| `deleted_at` | — | — | — | `skipped` | — |
| propietario del aula | membresía `teacher` activa (`legacy_source='dic_aula'`) | — | — | Si figuraba como alumno o deshabilitado: se corrige (`repairable automatically`) | verificación «aulas con profesor» |

### `dic_aula_destinatario_avisos`

No se migra: los avisos por correo y la invitación de co-docentes no tienen implementación (estado `3`) y la tabla
solo contiene emails (minimización de datos). Contada en `notMigrated`.

## Envíos y publicación

El legacy copia filas (`envios_*`) en cada envío; el nuevo modelo usa una revisión inmutable y una `submission`.

### `dp_envios` (cabecera)

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `dic_aula_id` | `submissions.classroom_id` | mapa | no | — | — |
| `dic_personal_id` → `persona_id` | `submissions.submitted_by` | persona → usuario | no | Sin aula o alumno: los `envios_entradas` son huérfanos | verificación «origen del envío» |
| `ano_ini_curso_escolar`, `estado` | — | — | — | Redundantes | — |

### `envios_entradas` → `entry_revisions` (`reason='submit'`) + `submissions`

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `id` | `submissions.legacy_id` | — | — | — | test |
| `dp_entrada_id` | `submissions.source_entry_id`; la revisión cuelga de esa entrada | mapa | no | **Origen inexistente** (fila huérfana): se crea una entrada personal **borrada** en el diccionario del alumno (`legacy_source='envios_entradas'`) para no perder el envío ni su publicación; cuenta como huérfano (`repairable automatically`) | test 803 |
| `entrada` + `envios_acepciones*` | `entry_revisions.snapshot` (`EntrySnapshot`) | mismo formato que `snapshotOf` de la app: solo acepciones visibles, posiciones 1..n, `media` sin URL | no | El legacy no conserva lo enviado originalmente; la copia incluye ya las ediciones del profesor | test |
| — | `entry_revisions.number` | 1..n por entrada origen en orden de id | — | Clave de idempotencia (`entry_id`, `number`) | — |
| `estado` + `dic_aula_entradas` | `status` | publicado si existe `dic_aula_entradas` en **su** aula (fuente de verdad; el legacy también escribe `2` «oculta» en filas publicadas); si no: `1`→`pending`, resto (`0`, `2`, `3` sin publicación, `deleted_at`)→`withdrawn` | no | `3` sin publicación: `needs rule`. Varios pendientes de la misma entrada al mismo aula (el destino solo admite uno): queda el más reciente, el resto `withdrawn` (`repairable automatically`) | test |
| `created_at` | `submitted_at` | — | no | — | — |
| publicación (`dic_aula_entradas.created_at`) | `reviewed_at` | — | sí | — | — |
| `updated_by_persona_id` | `reviewed_by`, `entries.updated_by` de la entrada de aula | persona → usuario; `0` = sin editar → propietario del aula | sí | — | — |
| `deleted_at` | `status='withdrawn'`; entrada de aula borrada | — | — | — | test 802 |

### `envios_acepciones`, `envios_acepciones_tematicas`, `envios_acepciones_medios`

Mismas reglas que `dp_acepciones`, `dp_acepciones_tematicas` y `dp_acepciones_medios`. Alimentan la instantánea
del envío y las acepciones de la entrada publicada (con `hidden` para `estado=2`). Los medios suelen ser el mismo
fichero que el del alumno (un solo `media_asset`); los sustituidos por el profesor son ficheros nuevos.
`legacy_source='envios_acepciones'` se guarda en las acepciones de la **primera** entrada de aula publicada de
ese envío; las copias importadas a otras aulas no llevan clave legacy.

### `dic_aula_entradas` → `entries` (aula) + `entry_revisions` (`reason='publish'`)

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `id` | `entries.legacy_id` (`legacy_source='dic_aula_entradas'`) | — | — | — | test |
| `dic_aula_id` | `entries.dictionary_id` | mapa | no | — | — |
| `envio_entrada_id` | `entries.source_submission_id`; `submissions.published_entry_id` si es su aula | — | no | Envío no migrado: huérfano | verificaciones «cada envío publicado tiene entrada» y «procedencia» |
| (importación a otra aula) | otra entrada de aula con el **mismo** `source_submission_id` | — | — | La función «importar entradas» publicaba un mismo envío en varias aulas; cada aula recibe su copia | test 902 |
| `estado` | `hidden` | `≠'1'` o `envios_entradas.estado='2'` → oculta | no | — | — |
| `deleted_at` / aula borrada / envío borrado | `deleted_at` | — | sí | — | — |
| headword repetida en el aula | `deleted_at` | — | — | `needs human review` | — |
| `origen`, `dic_origen` | — | — | — | `dic_origen` nunca se escribe; la procedencia está en `source_submission_id` | — |
| — | `created_by` = alumno, `updated_by` = editor o propietario, `version=1` | como `publishSubmissions` de la app | — | — | test |
| — | revisión `publish` número 1 con la instantánea de la entrada | — | — | — | — |

## Comentarios

### `comentarios_generales` → `comments` (`submission_id = null`)

| Legacy | Nuevo | Transformación | Nullable | Regla | Verificación |
|---|---|---|---|---|---|
| `dic_aula_id` | `classroom_id` | mapa | no | — | — |
| `dic_personal_id` → `persona_id` | `student_id` | — | no | — | test |
| `persona_id` (autor) | `author_id` | persona → usuario | no | Sin aula, alumno o autor: huérfano | test |
| `comentario` (HTML) | `body` | HTML→texto | no | Vacío tras convertir: `cannot migrate`. Más de 2000 caracteres (límite de la app): se conserva (`needs human review`) | test |
| `fecha_envio` | `created_at` | `fecha_envio` o `created_at` | no | Es la fecha que filtra `before_date` | — |
| `estado` | `deleted_at` si `0` | — | — | `0` (no visible) nunca se escribe; si aparece: borrado (`needs rule`). `1`/`2` (leído/no leído) no se modelan | — |
| `deleted_at` | `deleted_at` | — | sí | — | — |

### `comentarios_entradas` → `comments` (`submission_id` = envío)

Como `comentarios_generales`, con `submission_id` = envío de `envio_entrada_id` y `student_id` = quien envió.
**Bug legacy** (FUNCTIONAL_INVENTORY §0 #10): `persona_id` guarda un **id de usuario**. Se interpreta como
`users.id`; si no existe y sí existe como persona, se usa la persona (`needs human review`); si como usuario no
es profesor del aula y como persona sí, se informa como ambiguo (`needs human review`). Verificación: «comentarios
de envío dirigidos a quien envió».

## Tablas no migradas

| Tabla | Motivo |
|---|---|
| `audits` | Log técnico de owen-it (valores antiguos/nuevos completos, IP, user agent). Solo se cuenta. El historial funcional empieza en `entry_revisions`; el log nuevo es `audit_events` (§53). Conservar el volcado legacy según la política de retención (docs/PRIVACY.md) |
| `password_resets` | Tokens temporales |
| `avisos`, `avisos_dp_envios`, `avisos_coment_generales`, `avisos_coment_entradas`, `avisos_entradas_compartidas` | Esquema muerto: nada los escribe |
| `entradas_compartidas` | Esquema muerto, sin modelo |
| `dic_aula_destinatario_avisos` | Funcionalidad sin implementación; solo emails |
| `mst_ensenanzas_estudios`, `mst_estudios_areas_materias` | Producto cartesiano sin uso |
| `centros_dic_aula` | Eliminada en 2021 |
| `user_roles`, `permissions`, `permission_role`, `menus`, `menu_items`, `data_types`, `data_rows`, `settings`, `translations` | Voyager, sustituido por la administración propia (§37) |
| `pages`, `posts`, `categories` | CMS de ejemplo de Voyager |
| `migrations` | Control de Laravel |
| Vistas de estadísticas (`diccionariosDatos*View`, `v_*`) | No son tablas; la app calcula estadísticas al vuelo |

Todas aparecen en `notMigrated` del informe con su recuento (`null` si la tabla no existe).

## Verificaciones posteriores a la carga

Dentro de la misma transacción, antes de confirmar (también en `--dry-run`):

- orden de acepciones contiguo 1..n;
- cada envío `published` tiene entrada de aula con `source_submission_id`;
- toda entrada de aula migrada tiene procedencia;
- el origen de cada envío es una entrada del diccionario personal de quien envió;
- quien envió es miembro del aula (si falla: alumnos dados de baja en el legacy; se revisa, no se corrige);
- los comentarios de envío van dirigidos a quien envió;
- cada aula tiene al menos un profesor activo;
- como mucho un medio por tipo y acepción;
- no existen columnas de identificadores personales en el esquema;
- (ejecución real) cada fichero copiado existe en el destino con el sha256 registrado.

FK y unicidad las garantiza PostgreSQL: una violación aborta la transacción y aparece en `failure`.
