CREATE OR REPLACE VIEW `v_diccionariosAula_curso` AS 
select
	concat('Diccionarios de aula') AS `nombre`,
	count(0) AS `veces`,
	concat(`dic_aula`.`ano_ini_curso_escolar`, '/', `dic_aula`.`ano_ini_curso_escolar` + 1) AS `curso_inicio`,
	concat(`dic_aula`.`vigencia`-1 + `dic_aula`.`ano_ini_curso_escolar`, '/', `dic_aula`.`vigencia`-1 + `dic_aula`.`ano_ini_curso_escolar` + 1) AS `curso_fin`
from
	`dic_aula`
where
	`dic_aula`.`deleted_at` is null
	and `dic_aula`.`estado` = 1
group by
	`dic_aula`.`ano_ini_curso_escolar`,
	`dic_aula`.`vigencia`
    COLLATE latin1_spanish_ci 
;

CREATE OR REPLACE VIEW `v_diccionariosPersonales_curso` AS 
 select
	concat('Diccionarios personales') AS `nombre`,
	count(0) AS `veces`,
	if(date_format(`dp`.`created_at`, '%m') < 9,
        concat(date_format(`dp`.`created_at`, '%Y') - 1, '/', date_format(`dp`.`created_at`, '%Y')),
        concat(date_format(`dp`.`created_at`, '%Y'), '/', date_format(`dp`.`created_at`, '%Y') + 1)) AS `curso_inicio`,
	'' AS `curso_fin`
from
	`dic_personal` `dp`
where
	`dp`.`deleted_at` is null
	and `dp`.`estado` = 1
group by
	if(date_format(`dp`.`created_at`, '%m') < 9,
	concat(date_format(`dp`.`created_at`, '%Y') - 1, '/', date_format(`dp`.`created_at`, '%Y')),
	concat(date_format(`dp`.`created_at`, '%Y'), '/', date_format(`dp`.`created_at`, '%Y') + 1))
;

CREATE OR REPLACE VIEW `v_centros_curso` AS 
select
	concat('Centros') AS `nombre`,
	count(0) AS `veces`,
	if(date_format(`c`.`created_at`, '%m') < 9 ,
	concat(date_format(`c`.`created_at`, '%Y') - 1, '/', date_format(`c`.`created_at`, '%Y')) ,
	concat(date_format(`c`.`created_at`, '%Y'), '/', date_format(`c`.`created_at`, '%Y') + 1)) AS `curso_inicio`, 
	'' AS `curso_fin` 
from
	`centros` `c`
where
	`c`.`deleted_at` is null
	and `c`.`estado` = 1
group by
	if(date_format(`c`.`created_at`, '%m') < 9,
	concat(date_format(`c`.`created_at`, '%Y') - 1, '/', date_format(`c`.`created_at`, '%Y')),
	concat(date_format(`c`.`created_at`, '%Y'), '/', date_format(`c`.`created_at`, '%Y') + 1))
;

CREATE OR REPLACE VIEW `v_usuariosRolAula_curso` AS 
select
	concat('Usuarios con rol "', `t`.`rol`, '" en diccionarios de aula') AS `nombre`,
	count(0) AS `veces`,
	concat(`t`.`ano_ini_curso_escolar`, '/', `t`.`ano_ini_curso_escolar` + 1) AS `curso_inicio`,
	concat(`t`.`vigencia`-1 + `t`.`ano_ini_curso_escolar`, '/', `t`.`vigencia`-1 + `t`.`ano_ini_curso_escolar` + 1) AS `curso_fin`
from
	v_temp1 as `t`
group by
	`t`.`rol`,
	concat(`t`.`ano_ini_curso_escolar`, '/', `t`.`ano_ini_curso_escolar` + 1)
;

-- CREATE OR REPLACE VIEW `v_diccionariosDatosCurso` AS 
-- select * from v_diccionariosAula_curso union all
-- select * from v_diccionariosPersonales_curso union all
-- select * from v_centros_curso union all
-- select * from v_usuariosRolAula_curso;

-- select * from v_diccionariosAula_curso;
-- select * from v_diccionariosPersonales_curso;
-- select * from v_centros_curso;
-- select * from v_usuariosRolAula_curso;
