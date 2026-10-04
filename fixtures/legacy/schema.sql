-- Legacy LexiCán schema (MariaDB), reproduced from database/migrations/*.php for the migrator tests.
-- Laravel initial structure + later migrations (updated_by_persona_id, mst_pautas, dic_aula_atemporales,
-- vigencia, ejemplo2). Voyager tables are reduced to the ones the migrator reads or counts.
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE TABLE roles (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) NOT NULL UNIQUE,
  display_name varchar(255) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE users (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
  role_id bigint unsigned NULL,
  name varchar(255) NOT NULL,
  email varchar(255) NOT NULL UNIQUE,
  avatar varchar(255) NULL DEFAULT 'users/default.png',
  email_verified_at timestamp NULL,
  password varchar(255) NOT NULL,
  remember_token varchar(100) NULL,
  settings text NULL,
  created_at timestamp NULL, updated_at timestamp NULL,
  FOREIGN KEY (role_id) REFERENCES roles (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE user_roles (
  user_id bigint unsigned NOT NULL, role_id bigint unsigned NOT NULL, PRIMARY KEY (user_id, role_id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  email varchar(255) NOT NULL, token varchar(255) NOT NULL, created_at timestamp NULL, INDEX (email)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE audits (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
  user_type varchar(255) NULL, user_id bigint unsigned NULL, event varchar(255) NOT NULL,
  auditable_type varchar(255) NOT NULL, auditable_id bigint unsigned NOT NULL,
  old_values text NULL, new_values text NULL, url text NULL, ip_address varchar(45) NULL,
  user_agent varchar(1023) NULL, tags varchar(255) NULL,
  created_at timestamp NULL, updated_at timestamp NULL,
  INDEX (auditable_type, auditable_id), INDEX (user_id, user_type)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Master tables
CREATE TABLE mst_ensenanzas (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, descripcion varchar(255) NOT NULL, fecha_baja datetime NULL,
  estado char(1) NOT NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE mst_areas_materias LIKE mst_ensenanzas;
CREATE TABLE mst_nivel_estudios LIKE mst_ensenanzas;
CREATE TABLE mst_tipos_diccionario_aula (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, tipo_diccionario varchar(150) NOT NULL UNIQUE,
  estado char(1) NOT NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE mst_campos_entrada (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, nombre_campo varchar(150) NOT NULL UNIQUE, estado char(1) NOT NULL,
  fecha_baja datetime NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE mst_campos_valores (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
  mst_campo_entrada_id bigint unsigned NOT NULL, descripcion varchar(255) NOT NULL, estado char(1) NOT NULL,
  fecha_baja datetime NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (mst_campo_entrada_id) REFERENCES mst_campos_entrada (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE mst_ensenanzas_estudios (
  mst_ensenanzas_id bigint unsigned NOT NULL, mst_nivel_estudios_id bigint unsigned NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE mst_estudios_areas_materias (
  mst_nivel_estudios_id bigint unsigned NOT NULL, mst_area_materia_id bigint unsigned NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE mst_pautas (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, tipo int NOT NULL DEFAULT 1, texto text NOT NULL,
  estado int NOT NULL DEFAULT 1, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- People
CREATE TABLE personas (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
  cial varchar(12) NULL UNIQUE, NIF_NIE varchar(9) NULL UNIQUE, pasaporte varchar(255) NULL UNIQUE,
  nombre varchar(150) NOT NULL, apellidos varchar(150) NOT NULL, avatar_URL varchar(255) NOT NULL, estado char(1) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE users_personas (
  user_id bigint unsigned NOT NULL, persona_id bigint unsigned NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (user_id) REFERENCES users (id), FOREIGN KEY (persona_id) REFERENCES personas (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE centros (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, cod_centro varchar(8) NOT NULL, denominacion varchar(255) NOT NULL,
  estado char(1) NOT NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE users_centros (
  centro_id bigint unsigned NOT NULL, user_id bigint unsigned NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (centro_id) REFERENCES centros (id), FOREIGN KEY (user_id) REFERENCES users (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Personal dictionaries
CREATE TABLE dic_personal (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, persona_id bigint unsigned NOT NULL, titulo varchar(150) NOT NULL,
  estado char(1) NOT NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (persona_id) REFERENCES personas (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE dp_entradas (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dic_personal_id bigint unsigned NOT NULL, entrada varchar(150) NOT NULL,
  estado char(1) NOT NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (dic_personal_id) REFERENCES dic_personal (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE dp_acepciones (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dic_entrada_id bigint unsigned NOT NULL, orden smallint NOT NULL,
  cat_gramatical_id bigint unsigned NULL, genero_id bigint unsigned NULL, numero_id bigint unsigned NULL,
  idioma_id bigint unsigned NULL, idioma_palabra varchar(150) NULL, definicion varchar(1000) NOT NULL,
  frase_ejemplo varchar(255) NULL, estado char(1) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL, ejemplo2 varchar(255) NULL,
  FOREIGN KEY (dic_entrada_id) REFERENCES dp_entradas (id),
  FOREIGN KEY (cat_gramatical_id) REFERENCES mst_campos_valores (id), FOREIGN KEY (genero_id) REFERENCES mst_campos_valores (id),
  FOREIGN KEY (numero_id) REFERENCES mst_campos_valores (id), FOREIGN KEY (idioma_id) REFERENCES mst_campos_valores (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE dp_acepciones_tematicas (
  dp_acepcion_id bigint unsigned NOT NULL, tematica_id bigint unsigned NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (dp_acepcion_id) REFERENCES dp_acepciones (id), FOREIGN KEY (tematica_id) REFERENCES mst_campos_valores (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE dp_acepciones_medios (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dic_acepcion_id bigint unsigned NOT NULL, tipo_medio char(1) NOT NULL,
  nombre varchar(255) NOT NULL, url_externa varchar(255) NULL, url_interna varchar(255) NULL, estado char(1) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (dic_acepcion_id) REFERENCES dp_acepciones (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Classroom dictionaries
CREATE TABLE dic_aula (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, persona_id bigint unsigned NOT NULL, mst_tipo_dic_id bigint unsigned NOT NULL,
  titulo varchar(150) NOT NULL, descripcion varchar(255) NULL, letra_grupo char(1) NOT NULL,
  mst_nivel_estudios_id bigint unsigned NULL, mst_area_materia_id bigint unsigned NOT NULL,
  mst_ensenanza_id bigint unsigned NOT NULL DEFAULT 1, ano_ini_curso_escolar smallint NOT NULL,
  max_acepciones_entrada smallint NOT NULL, codigo varchar(7) NOT NULL, visible_estudiante char(1) NOT NULL,
  envios_habilitados char(1) NOT NULL, envios_fecha_ini datetime NULL, envios_fecha_fin datetime NULL, estado char(1) NOT NULL,
  comentarios_visibles char(1) NOT NULL DEFAULT '0', comentarios_visibles_anteriores_a datetime(1) NULL,
  pautas_maestras char(1) NOT NULL DEFAULT '1', created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  vigencia int NOT NULL,
  FOREIGN KEY (persona_id) REFERENCES personas (id), FOREIGN KEY (mst_tipo_dic_id) REFERENCES mst_tipos_diccionario_aula (id),
  FOREIGN KEY (mst_nivel_estudios_id) REFERENCES mst_nivel_estudios (id), FOREIGN KEY (mst_area_materia_id) REFERENCES mst_areas_materias (id),
  FOREIGN KEY (mst_ensenanza_id) REFERENCES mst_ensenanzas (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE dic_aula_atemporales (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dic_aula_id bigint unsigned NOT NULL, estado char(1) NOT NULL,
  hasta timestamp NULL, created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at timestamp NULL,
  FOREIGN KEY (dic_aula_id) REFERENCES dic_aula (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE dic_aula_destinatario_avisos (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dic_aula_id bigint unsigned NOT NULL, email varchar(255) NULL,
  estado char(1) NOT NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (dic_aula_id) REFERENCES dic_aula (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE dic_aula_pautas (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dic_aula_id bigint unsigned NOT NULL, url_fichero varchar(255) NULL,
  texto text NULL, estado char(1) NOT NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (dic_aula_id) REFERENCES dic_aula (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE dic_aula_participantes (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dic_aula_id bigint unsigned NOT NULL, persona_id bigint unsigned NOT NULL,
  rol_diccionario_id bigint unsigned NOT NULL, estado char(1) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (dic_aula_id) REFERENCES dic_aula (id), FOREIGN KEY (persona_id) REFERENCES personas (id),
  FOREIGN KEY (rol_diccionario_id) REFERENCES roles (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE dic_aula_campos (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dic_aula_id bigint unsigned NOT NULL, mst_campo_entrada_id bigint unsigned NOT NULL,
  visible char(1) NOT NULL, obligatorio char(1) NOT NULL, estado char(1) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (dic_aula_id) REFERENCES dic_aula (id), FOREIGN KEY (mst_campo_entrada_id) REFERENCES mst_campos_entrada (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Submissions
CREATE TABLE dp_envios (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, ano_ini_curso_escolar smallint NOT NULL, dic_personal_id bigint unsigned NOT NULL,
  dic_aula_id bigint unsigned NOT NULL, estado char(1) NOT NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (dic_personal_id) REFERENCES dic_personal (id), FOREIGN KEY (dic_aula_id) REFERENCES dic_aula (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE envios_entradas (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dp_envio_id bigint unsigned NOT NULL, dp_entrada_id bigint unsigned NOT NULL,
  entrada varchar(150) NOT NULL, estado char(1) NOT NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  updated_by_persona_id int NOT NULL,
  FOREIGN KEY (dp_envio_id) REFERENCES dp_envios (id), FOREIGN KEY (dp_entrada_id) REFERENCES dp_entradas (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE envios_acepciones (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, envio_entrada_id bigint unsigned NOT NULL, orden smallint NOT NULL,
  cat_gramatical_id bigint unsigned NULL, genero_id bigint unsigned NULL, numero_id bigint unsigned NULL,
  idioma_id bigint unsigned NULL, idioma_palabra varchar(150) NULL, definicion varchar(1000) NOT NULL,
  frase_ejemplo varchar(255) NULL, estado char(1) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL, ejemplo2 varchar(255) NULL,
  FOREIGN KEY (envio_entrada_id) REFERENCES envios_entradas (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE envios_acepciones_tematicas (
  envio_acepcion_id bigint unsigned NOT NULL, tematica_id bigint unsigned NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (envio_acepcion_id) REFERENCES envios_acepciones (id), FOREIGN KEY (tematica_id) REFERENCES mst_campos_valores (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE envios_acepciones_medios (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, envio_acepcion_id bigint unsigned NOT NULL, tipo_medio char(1) NOT NULL,
  nombre varchar(255) NOT NULL, url_externa varchar(255) NULL, url_interna varchar(255) NULL, estado char(1) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (envio_acepcion_id) REFERENCES envios_acepciones (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE dic_aula_entradas (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dic_aula_id bigint unsigned NOT NULL, envio_entrada_id bigint unsigned NOT NULL,
  origen char(1) NOT NULL, dic_origen bigint NULL, estado char(1) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (dic_aula_id) REFERENCES dic_aula (id), FOREIGN KEY (envio_entrada_id) REFERENCES envios_entradas (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Comments
CREATE TABLE comentarios_generales (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dic_personal_id bigint unsigned NOT NULL, persona_id bigint unsigned NOT NULL,
  dic_aula_id bigint unsigned NOT NULL, comentario text NOT NULL, fecha_envio datetime NULL, estado char(1) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (dic_personal_id) REFERENCES dic_personal (id), FOREIGN KEY (persona_id) REFERENCES personas (id),
  FOREIGN KEY (dic_aula_id) REFERENCES dic_aula (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE comentarios_entradas (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, envio_entrada_id bigint unsigned NOT NULL, persona_id bigint unsigned NOT NULL,
  dic_aula_id bigint unsigned NOT NULL, comentario text NOT NULL, fecha_envio datetime NULL, estado char(1) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL,
  FOREIGN KEY (envio_entrada_id) REFERENCES envios_entradas (id), FOREIGN KEY (persona_id) REFERENCES personas (id),
  FOREIGN KEY (dic_aula_id) REFERENCES dic_aula (id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Dead schema (counted, not migrated)
CREATE TABLE entradas_compartidas (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, dic_personal_id bigint unsigned NOT NULL, dic_aula_entrada_id bigint unsigned NOT NULL,
  fecha_comparticion datetime NULL, estado char(1) NOT NULL, created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE avisos (
  id bigint unsigned AUTO_INCREMENT PRIMARY KEY, tipo_aviso char(1) NOT NULL, destinatario char(1) NOT NULL,
  dic_personal_id bigint unsigned NOT NULL, dic_aula_id bigint unsigned NOT NULL, email char(1) NOT NULL, estado char(1) NOT NULL,
  created_at timestamp NULL, updated_at timestamp NULL, deleted_at timestamp NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
