# ADR 0006 — Modelo de datos: diccionarios, envíos como revisiones inmutables, vocabularios controlados

- Estado: aceptada
- Fecha: 2026-10-04

## Decisión

- `dictionaries(kind: personal | classroom)` + `classroom_settings` (1:1) en lugar de `dic_personal` y `dic_aula`.
- `entries` + `entry_senses` + `sense_topics` + `sense_media` sirven para los dos tipos de diccionario.
- Un envío (`submissions`) apunta a una revisión inmutable (`entry_revisions.snapshot`, JSON con la forma
  `EntrySnapshot`). Publicar crea (o actualiza, si es un reenvío de la misma entrada) una entrada del aula con
  `source_submission_id`. Desaparecen `dp_envios`, `envios_entradas`, `envios_acepciones*` y `dic_aula_entradas`.
- Estados explícitos: `submission_status = pending | published | rejected | withdrawn`; booleanos `hidden`, `active`,
  `timeless`; `deleted_at` solo donde se necesita borrado recuperable.
- Vocabularios controlados en una tabla `vocabulary_values(vocabulary, code, label, abbreviation, position, featured,
  active)` con códigos estables. Las acepciones referencian valores por relación, no por id histórico.
- Campos de acepción configurables por aula como arrays tipados `visible_fields` / `required_fields`
  (`SENSE_FIELDS`), no como tabla EAV.
- Bloqueo optimista: `entries.version`; una escritura con versión antigua devuelve 409.
- UUID como clave; trazabilidad con `legacy_source` + `legacy_id` (únicos juntos) en cada tabla migrada.
- `users` sustituye a `users` + `personas`; la identidad institucional vive en `auth_identities`.

Detalle completo: `docs/DATA-MODEL.md`.
