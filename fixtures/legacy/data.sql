-- Small, entirely fictitious legacy data set for the migrator tests (no real people, ids or schools).
-- Each block says which migration rule it exercises. Role and master ids deliberately differ from a fresh seed.
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- Roles with «local» ids (production has docente=1, alumno=2): the migrator must map by name.
INSERT INTO roles (id, name, display_name) VALUES
  (1, 'admin', 'Administrador'), (2, 'user', 'Oficina técnica'), (3, 'docente', 'Docente'), (4, 'alumno', 'Alumno');

-- users.name is the CAS login id; CAS users store it in email too (not an address → not migrated as email).
INSERT INTO users (id, role_id, name, email, password, created_at, updated_at) VALUES
  (1, 1, 'admin.ficticio', 'admin@example.org', '$2y$10$fakefakefakefakefakefakefakefakefakefakefakefakefake', '2021-09-01 08:00:00', '2021-09-01 08:00:00'),
  (2, 3, 'prof.garcia', 'prof.garcia', '$2y$10$fakefakefakefakefakefakefakefakefakefakefakefakefake', '2021-09-01 09:00:00', '2024-09-02 09:00:00'),
  (3, 4, 'alu.perez', 'alu.perez@example.org', '$2y$10$fakefakefakefakefakefakefakefakefakefakefakefakefake', '2021-09-02 10:00:00', '2024-09-02 10:00:00'),
  (4, 4, 'alu.lopez', 'alu.lopez', '$2y$10$fakefakefakefakefakefakefakefakefakefakefakefakefake', '2021-09-02 11:00:00', '2024-09-02 11:00:00');

-- Fake national ids to prove they are never migrated.
INSERT INTO personas (id, cial, NIF_NIE, pasaporte, nombre, apellidos, avatar_URL, estado, created_at, updated_at) VALUES
  (10, NULL, '00000000T', NULL, 'Marta', 'García Ficticia', 'default.svg', '1', '2021-09-01 09:00:00', '2024-09-02 09:00:00'),
  (11, 'CIAL0000001', '11111111H', 'XX0000001', 'Pablo', 'Pérez Inventado', 'default.svg', '1', '2021-09-02 10:00:00', '2024-09-02 10:00:00'),
  (12, 'CIAL0000002', NULL, NULL, 'Lucía', 'López Ejemplo', 'default.svg', '1', '2021-09-02 11:00:00', '2024-09-02 11:00:00'),
  (13, 'CIAL0000003', NULL, NULL, 'Sin', 'Cuenta', 'default.svg', '1', '2021-09-03 11:00:00', '2021-09-03 11:00:00');
INSERT INTO users_personas (user_id, persona_id, created_at) VALUES (2, 10, NOW()), (3, 11, NOW()), (4, 12, NOW());

INSERT INTO password_resets (email, token, created_at) VALUES ('admin@example.org', 'faketoken', NOW());
INSERT INTO audits (user_type, user_id, event, auditable_type, auditable_id, created_at) VALUES
  ('App\\User', 2, 'created', 'App\\Models\\DicAula', 500, NOW()), ('App\\User', 3, 'updated', 'App\\Models\\DiccionarioPersonalEntrada', 1000, NOW());

-- Schools: duplicated code and the «no centre» placeholder.
INSERT INTO centros (id, cod_centro, denominacion, estado, created_at) VALUES
  (1, '38999901', 'IES Ficticio Uno', '1', NOW()), (2, '00000000', 'Sin centro', '1', NOW()), (3, '38999901', 'IES Ficticio Uno', '1', NOW());
INSERT INTO users_centros (centro_id, user_id, created_at) VALUES (1, 2, NOW()), (3, 3, NOW()), (2, 4, NOW());

-- Master data with production-like (not fresh-seed) ids and labels.
INSERT INTO mst_ensenanzas (id, descripcion, estado) VALUES (1, 'Multienseñanza', '1'), (3, 'ESO', '1');
INSERT INTO mst_nivel_estudios (id, descripcion, estado) VALUES (1, 'Multiestudio', '1'), (9, '1º ESO', '1');
INSERT INTO mst_areas_materias (id, descripcion, estado) VALUES (1, 'Interdisciplinar', '1'), (77, 'Robótica Ficticia', '1');
INSERT INTO mst_tipos_diccionario_aula (id, tipo_diccionario, estado) VALUES (1, 'Diccionario general', '1'), (2, 'Canarismos', '1');
INSERT INTO mst_campos_entrada (id, nombre_campo, estado) VALUES
  (1, 'Categoría gramatical', '1'), (2, 'Género', '1'), (3, 'Número', '1'), (4, 'Temáticas generales', '1'), (5, 'Más datos', '1'),
  (6, 'Vídeo', '1'), (7, 'Audio', '1'), (8, 'Imagen', '1'), (9, 'Lengua', '1'), (10, 'Ejemplo de uso', '1');
INSERT INTO mst_campos_valores (id, mst_campo_entrada_id, descripcion, estado) VALUES
  (1, 1, 'Adjetivo', '1'), (2, 1, 'Sustantivo', '1'), (10, 2, 'Femenino', '1'), (11, 2, 'Masculino', '1'), (15, 3, 'Singular', '1'),
  (20, 4, 'Fauna canaria', '1'), (21, 4, 'GASTRONOMÍA', '1'), (30, 9, ' inglés', '1'), (31, 9, ' élfico ficticio', '1');
INSERT INTO mst_pautas (id, tipo, texto, estado) VALUES (1, 1, '<p>Pautas <strong>generales</strong> de prueba</p>', 1);

-- Personal dictionaries: Pablo has an older one (99) and the active one (100); Lucía has 101.
INSERT INTO dic_personal (id, persona_id, titulo, estado, created_at, updated_at) VALUES
  (99, 11, 'Antiguo', '1', '2021-09-02 10:00:00', '2021-09-02 10:00:00'),
  (100, 11, 'Mi diccionario personal', '1', '2022-09-02 10:00:00', '2024-10-01 10:00:00'),
  (101, 12, 'Mi diccionario personal', '1', '2022-09-02 11:00:00', '2024-10-01 11:00:00');
INSERT INTO dp_entradas (id, dic_personal_id, entrada, estado, created_at, updated_at, deleted_at) VALUES
  (1000, 100, 'árbol', '1', '2024-10-01 10:00:00', '2024-10-02 10:00:00', NULL),
  (1001, 100, 'Perro', '2', '2024-10-01 10:05:00', '2024-10-01 10:05:00', NULL),
  (1002, 100, 'gato', '0', '2024-10-01 10:06:00', '2024-10-05 10:06:00', '2024-10-05 10:06:00'),
  (1003, 100, 'Casa', '1', '2024-10-01 10:07:00', '2024-10-01 10:07:00', NULL),
  (1004, 100, 'Mesa', '1', '2024-10-01 10:08:00', '2024-10-01 10:08:00', NULL),
  (1005, 100, '  Árbol ', '1', '2024-10-03 10:00:00', '2024-10-03 10:00:00', NULL),
  (1010, 101, 'Sol', '1', '2024-10-01 11:00:00', '2024-10-01 11:00:00', NULL);
-- «árbol»: senses out of order with a gap (orden 5,1,2; deleted 3), one deleted sense, every field filled.
INSERT INTO dp_acepciones (id, dic_entrada_id, orden, cat_gramatical_id, genero_id, numero_id, idioma_id, idioma_palabra, definicion, frase_ejemplo, ejemplo2, estado, created_at, updated_at, deleted_at) VALUES
  (2001, 1000, 5, 2, 11, 15, NULL, NULL, 'Tercera acepción.', NULL, NULL, '1', NOW(), NOW(), NULL),
  (2002, 1000, 1, 2, 11, 15, 30, 'tree', 'Planta de tronco leñoso.', 'Más datos de prueba', 'El árbol da sombra.', '1', NOW(), NOW(), NULL),
  (2003, 1000, 2, 1, NULL, NULL, 31, 'aldar', 'Segunda acepción.', NULL, NULL, '2', NOW(), NOW(), NULL),
  (2004, 1000, 3, NULL, NULL, NULL, NULL, NULL, 'Acepción borrada.', NULL, NULL, '0', NOW(), NOW(), NOW()),
  (2005, 1001, 1, 2, NULL, NULL, NULL, NULL, 'Animal doméstico.', NULL, NULL, '1', NOW(), NOW(), NULL),
  (2010, 1003, 1, 2, 10, 15, NULL, NULL, 'Edificio para vivir.', NULL, NULL, '1', NOW(), NOW(), NULL),
  (2011, 1003, 2, 2, 10, 15, NULL, NULL, 'Familia.', NULL, NULL, '1', NOW(), NOW(), NULL),
  (2020, 1004, 1, 2, 10, 15, NULL, NULL, 'Mueble.', NULL, NULL, '1', NOW(), NOW(), NULL);
INSERT INTO dp_acepciones_tematicas (dp_acepcion_id, tematica_id) VALUES (2002, 20), (2002, 21), (2005, 20);
-- Media: two images on one sense (newest wins), an audio, a zero-byte video, shared content, an external URL.
INSERT INTO dp_acepciones_medios (id, dic_acepcion_id, tipo_medio, nombre, url_externa, url_interna, estado, created_at) VALUES
  (3001, 2002, '1', 'arbol-viejo.png', '', '11_1000_2002_aaaa.png', '1', '2024-10-01 10:00:00'),
  (3002, 2002, '1', 'arbol nuevo.png', '', '11_1000_2002_bbbb.png', '1', '2024-10-02 10:00:00'),
  (3003, 2002, '2', 'arbol.mp3', '', '11_1000_2002_cccc.mp3', '1', '2024-10-02 10:00:00'),
  (3004, 2001, '3', 'roto.mp4', '', '11_1000_2001_corrupt.mp4', '1', '2024-10-02 10:00:00'),
  (3005, 2005, '1', 'perro.png', '', '11_1001_2005_dup.png', '1', '2024-10-02 10:00:00'),
  (3006, 2003, '1', 'externa.png', 'http://example.org/externa.png', NULL, '1', '2024-10-02 10:00:00'),
  (3010, 2010, '1', 'casa.png', '', '11_1003_2010_dddd.png', '1', '2024-10-02 10:00:00');

-- Classroom 500: valid lowercase code, boolean max senses (bug), comments before a date, HTML guidelines.
-- Classroom 501: invalid code, permanent, no field configuration, max 5 senses, unmatched subject.
INSERT INTO dic_aula (id, persona_id, mst_tipo_dic_id, titulo, descripcion, letra_grupo, mst_nivel_estudios_id, mst_area_materia_id, mst_ensenanza_id,
  ano_ini_curso_escolar, max_acepciones_entrada, codigo, visible_estudiante, envios_habilitados, envios_fecha_ini, envios_fecha_fin, estado,
  comentarios_visibles, comentarios_visibles_anteriores_a, created_at, updated_at, vigencia) VALUES
  (500, 10, 1, 'Lengua 1º ESO A', 'Diccionario de clase', 'A', 9, 1, 3, 2024, 1, 'abc123', '1', '1', '2024-09-15 00:00:00', '2024-09-15 00:00:00', '1',
   '2', '2025-06-30 00:00:00', '2024-09-10 09:00:00', '2024-09-10 09:00:00', 2),
  (501, 10, 2, 'Canarismos', NULL, '0', NULL, 77, 1, 2023, 5, 'X!', '0', '0', NULL, NULL, '1', '0', NULL, '2023-09-10 09:00:00', '2023-09-10 09:00:00', 1);
INSERT INTO dic_aula_atemporales (id, dic_aula_id, estado, hasta) VALUES (1, 501, '1', NULL);
INSERT INTO dic_aula_campos (id, dic_aula_id, mst_campo_entrada_id, visible, obligatorio, estado) VALUES
  (1, 500, 1, '1', '1', '1'), (2, 500, 2, '0', '0', '1'), (3, 500, 3, '0', '0', '1'), (4, 500, 4, '1', '0', '1'), (5, 500, 5, '1', '0', '1'),
  (6, 500, 6, '0', '0', '1'), (7, 500, 7, '1', '0', '1'), (8, 500, 8, '1', '0', '1'), (9, 500, 9, '1', '0', '1'), (10, 500, 10, '1', '1', '1');
INSERT INTO dic_aula_pautas (id, dic_aula_id, texto, estado) VALUES
  (1, 500, '<p>Normas del aula:</p><ul><li>Una entrada por palabra</li><li>Revisa la ortograf&iacute;a &amp; los acentos</li></ul><p><img src="/storage/tinyUploads/x.png"></p>', '1');
INSERT INTO dic_aula_destinatario_avisos (id, dic_aula_id, email, estado) VALUES (1, 500, 'invitada@example.org', '3');
-- Participants: teacher, student, disabled student, duplicated student row.
INSERT INTO dic_aula_participantes (id, dic_aula_id, persona_id, rol_diccionario_id, estado) VALUES
  (600, 500, 10, 3, '1'), (601, 500, 11, 4, '1'), (602, 500, 12, 4, '0'), (603, 501, 11, 4, '1'), (604, 500, 11, 4, '1');

-- Envíos: published with teacher edits (800), pending (801), deleted (802), orphan (803, its dp_entrada 9999 is missing),
-- pending to the second classroom (804). 800 is also imported into classroom 501 (dic_aula_entradas 902).
INSERT INTO dp_envios (id, ano_ini_curso_escolar, dic_personal_id, dic_aula_id, estado, created_at) VALUES
  (700, 2024, 100, 500, '1', '2024-10-03 12:00:00'), (701, 2024, 101, 500, '1', '2024-10-03 12:30:00'), (702, 2024, 100, 501, '1', '2024-10-04 12:00:00');
INSERT INTO envios_entradas (id, dp_envio_id, dp_entrada_id, entrada, estado, created_at, updated_at, deleted_at, updated_by_persona_id) VALUES
  (800, 700, 1003, 'Casa', '3', '2024-10-03 12:00:00', '2024-10-06 12:00:00', NULL, 10),
  (801, 700, 1004, 'Mesa', '1', '2024-10-03 12:00:00', '2024-10-03 12:00:00', NULL, 0),
  (802, 700, 1002, 'gato', '0', '2024-10-03 12:00:00', '2024-10-05 10:06:00', '2024-10-05 10:06:00', 0),
  (803, 701, 9999, 'Luna', '3', '2024-10-03 12:30:00', '2024-10-03 12:30:00', NULL, 0),
  (804, 702, 1003, 'Casa', '1', '2024-10-04 12:00:00', '2024-10-04 12:00:00', NULL, 0);
INSERT INTO envios_acepciones (id, envio_entrada_id, orden, cat_gramatical_id, genero_id, numero_id, idioma_id, idioma_palabra, definicion, frase_ejemplo, ejemplo2, estado, created_at, updated_at) VALUES
  (810, 800, 1, 2, 10, 15, NULL, NULL, 'Edificio para vivir (revisado por la profesora).', NULL, 'Mi casa es azul.', '1', NOW(), NOW()),
  (811, 800, 2, 2, 10, 15, NULL, NULL, 'Familia.', NULL, NULL, '2', NOW(), NOW()),
  (812, 801, 1, 2, 10, 15, NULL, NULL, 'Mueble.', NULL, NULL, '1', NOW(), NOW()),
  (813, 802, 1, 2, 11, 15, NULL, NULL, 'Felino doméstico.', NULL, NULL, '1', NOW(), NOW()),
  (814, 803, 1, 2, 10, 15, NULL, NULL, 'Satélite de la Tierra.', NULL, NULL, '1', NOW(), NOW()),
  (815, 804, 1, 2, 10, 15, NULL, NULL, 'Edificio para vivir.', NULL, NULL, '1', NOW(), NOW());
INSERT INTO envios_acepciones_tematicas (envio_acepcion_id, tematica_id) VALUES (810, 21), (814, 20);
-- 820 shares the student's file (same bytes, one asset); 821 is a teacher-uploaded replacement; 822 points to a missing file.
INSERT INTO envios_acepciones_medios (id, envio_acepcion_id, tipo_medio, nombre, url_externa, url_interna, estado, created_at) VALUES
  (820, 815, '1', 'casa.png', '', '11_1003_2010_dddd.png', '1', '2024-10-04 12:00:00'),
  (821, 810, '1', 'casa-profe.png', '', '10_1003_810_eeee.png', '1', '2024-10-06 12:00:00'),
  (822, 810, '2', 'casa.mp3', '', '10_1003_810_missing.mp3', '1', '2024-10-06 12:00:00');
INSERT INTO dic_aula_entradas (id, dic_aula_id, envio_entrada_id, origen, estado, created_at, updated_at) VALUES
  (900, 500, 800, '1', '1', '2024-10-06 12:00:00', '2024-10-06 12:00:00'),
  (901, 500, 803, '1', '1', '2024-10-06 12:05:00', '2024-10-06 12:05:00'),
  (902, 501, 800, '1', '1', '2024-10-07 12:00:00', '2024-10-07 12:00:00');

-- Comments: rich HTML; comentarios_entradas.persona_id stores the teacher's USER id (2), legacy bug.
INSERT INTO comentarios_generales (id, dic_personal_id, persona_id, dic_aula_id, comentario, fecha_envio, estado, created_at, updated_at) VALUES
  (950, 100, 10, 500, '<p>Buen trabajo, <strong>Pablo</strong>.<br>Sigue as&iacute;.</p>', '2024-10-07 09:00:00', '1', '2024-10-07 09:00:00', '2024-10-07 09:00:00');
INSERT INTO comentarios_entradas (id, envio_entrada_id, persona_id, dic_aula_id, comentario, fecha_envio, estado, created_at, updated_at) VALUES
  (960, 800, 2, 500, '<p>Revisa la acepci&oacute;n&nbsp;2</p>', '2024-10-07 09:10:00', '1', '2024-10-07 09:10:00', '2024-10-07 09:10:00');

SET FOREIGN_KEY_CHECKS = 1;
