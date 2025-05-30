
-- diccionariosDatosCursoView
--
-- Muestra los datos de los diccionaros con el curso de inicio y de fin :

CREATE OR REPLACE
VIEW `diccionariosDatosCursoView` AS
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
    union all
    select
        concat('Diccionarios personales') AS `concat('Diccionarios personales')`,
        count(0) AS `count(*)`,
        if(date_format(`dp`.`created_at`, '%m') < 9,
        concat(date_format(`dp`.`created_at`, '%Y') - 1, '/', date_format(`dp`.`created_at`, '%Y')),
        concat(date_format(`dp`.`created_at`, '%Y'), '/', date_format(`dp`.`created_at`, '%Y') + 1)) AS `curso`,
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
    union all
        select
            concat('Centros') AS `concat('Centros')`,
            count(0) AS `count(0)`,
            if(date_format(`c`.`created_at`, '%m') < 9,
            concat(date_format(`c`.`created_at`, '%Y') - 1, '/', date_format(`c`.`created_at`, '%Y')),
            concat(date_format(`c`.`created_at`, '%Y'), '/', date_format(`c`.`created_at`, '%Y') + 1)) AS `curso`,
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
    union all
        select
            concat('Usuarios con rol "', `t`.`rol`, '" en diccionarios de aula') AS `CONCAT('Usuarios con rol "', t.rol, '" en diccionarios de aula')`,
            count(0) AS `count(*)`,
            concat(`t`.`ano_ini_curso_escolar`, '/', `t`.`ano_ini_curso_escolar` + 1) AS `curso_ini`,
            concat(`t`.`vigencia`-1 + `t`.`ano_ini_curso_escolar`, '/', `t`.`vigencia`-1 + `t`.`ano_ini_curso_escolar` + 1) AS `curso_fin`
        from (
                select
                    `dap`.`dic_aula_id` AS `dic_aula_id`,
                    `da`.`titulo` AS `titulo`,
                    `dap`.`persona_id` AS `persona_id`,
                    `p`.`nombre` AS `nombre`,
                    `p`.`NIF_NIE` AS `NIF_NIE`,
                    `u`.`role_id` AS `role_id`,
                    `r`.`name` AS `rol`,
                    `da`.`ano_ini_curso_escolar` AS `ano_ini_curso_escolar`,
                    `da`.`vigencia` AS `vigencia`
                from
                    (((((`dic_aula_participantes` `dap`
                join `personas` `p` on
                    (`p`.`id` = `dap`.`persona_id`))
                join `dic_aula` `da` on
                    (`da`.`id` = `dap`.`dic_aula_id`))
                join `users_personas` `up` on
                    (`up`.`persona_id` = `p`.`id`))
                join `users` `u` on
                    (`up`.`user_id` = `u`.`id`))
                join `roles` `r` on
                    (`u`.`role_id` = `r`.`id`))
                where
                    `dap`.`estado` = 1
                    and `dap`.`deleted_at` is null
                    and `p`.`estado` = 1
                    and `p`.`deleted_at` is null
                    and `da`.`estado` = 1
                    and `da`.`deleted_at` is null
                group by
                    `dap`.`persona_id`,
                    `da`.`ano_ini_curso_escolar`
        ) AS `t`
        group by 
            `t`.`rol`,
            concat(`t`.`ano_ini_curso_escolar`, '/', `t`.`ano_ini_curso_escolar` + 1);



CREATE OR REPLACE
    VIEW `diccionariosDatosView` AS
    select
        concat('Diccionarios de aula') AS `nombre`,
        count(0) AS `veces`
    from
        `dic_aula`
    where
        `dic_aula`.`deleted_at` is null
        and `dic_aula`.`estado` = 1
    union ALL
    select
        concat('Diccionarios personales') AS `concat('Diccionarios personales')`,
        count(0) AS `count(*)`
    from
        `dic_personal` `dp`
    where
        `dp`.`deleted_at` is null
        and `dp`.`estado` = 1
    union ALL
    select
        concat('Centros'),
        count(0)
    from
        centros c
    where
        c.deleted_at is null
        and c.estado = 1
    union ALL
    select
        CONCAT('Usuarios con rol "', t.rol, '" en diccionarios de aula'),
        count(*)
    FROM
        (
        select
            dap.dic_aula_id,
            da.titulo,
            dap.persona_id ,
            p.nombre,
            p.NIF_NIE,
            u.role_id,
            r.name as rol
        from
            dic_aula_participantes dap
        inner join personas p on
            p.id = dap.persona_id
        inner join dic_aula da on
            da.id = dap.dic_aula_id
        inner join users_personas up on
            up.persona_id = p.id
        inner join users u on
            up.user_id = u.id
        inner JOIN roles r on
            u.role_id = r.id
        where
            dap.estado = 1
            and dap.deleted_at is null
            AND p.estado = 1
            and p.deleted_at is null
            AND da.estado = 1
            and da.deleted_at is null
        group by
            persona_id ) as t
    group by
        rol;


-- VISTA ENSEÑANZAS / NIVEL / AREA-MATERIA:
CREATE OR REPLACE
    VIEW `ensenanzasNivelAreaMateriaView` as
    SELECT
        count(0) AS `veces`,
        me.descripcion as ensenanza,
        concat('') as nivel_estudios,
        concat('') as area_materia
    from
        dic_aula da
    left join mst_nivel_estudios mne on
        mne.id = da.mst_nivel_estudios_id
    LEFT join mst_areas_materias mam on
        mam.id = da.mst_area_materia_id
    left join mst_ensenanzas me on
        me.id = da.mst_ensenanza_id
    group by
        mst_ensenanza_id
    union ALL
    select
        '-----',
        '-----',
        '-----',
        '-----'
    from
        DUAL
    union ALL
    select
        count(0),
        me.descripcion as ensenanza,
        mne.descripcion as nivel_estudios ,
        concat('') as area_materia
    from
        dic_aula da
    left join mst_nivel_estudios mne on
        mne.id = da.mst_nivel_estudios_id
    LEFT join mst_areas_materias mam on
        mam.id = da.mst_area_materia_id
    left join mst_ensenanzas me on
        me.id = da.mst_ensenanza_id
    group by
        mst_ensenanza_id,
        mst_nivel_estudios_id
    union ALL
    select
        '-----',
        '-----',
        '-----',
        '-----'
    from
        DUAL
    union ALL
    select
        count(0),
        me.descripcion as ensenanza,
        mne.descripcion as nivel_estudios ,
        mam.descripcion as area_materia
    from
        dic_aula da
    left join mst_nivel_estudios mne on
        mne.id = da.mst_nivel_estudios_id
    LEFT join mst_areas_materias mam on
        mam.id = da.mst_area_materia_id
    left join mst_ensenanzas me on
        me.id = da.mst_ensenanza_id
    group by
        mst_ensenanza_id,
        mst_nivel_estudios_id,
        mst_area_materia_id
    union ALL
    select
        '-----',
        '-----',
        '-----',
        '-----'
    from
        DUAL
    union ALL (
    select
        count(0),
        concat('') as ensenanza,
        concat('') as nivel_estudios,
        mam.descripcion as area_materia
    from
        dic_aula da
    left join mst_nivel_estudios mne on
        mne.id = da.mst_nivel_estudios_id
    LEFT join mst_areas_materias mam on
        mam.id = da.mst_area_materia_id
    left join mst_ensenanzas me on
        me.id = da.mst_ensenanza_id
    group by
        area_materia
    ORDER BY
        area_materia);
-- vistas para el curso 2021-2022

CREATE OR REPLACE
    VIEW `diccionarios21View` AS
    select
        concat('Diccionarios de aula') AS `nombre`,
        count(0) AS `veces`
    from
        dic_aula da
    where
        da.deleted_at is null
        and da.estado = 1
        and da.ano_ini_curso_escolar = 2021
    UNION ALL
    select
        concat('Diccionarios personales') AS `concat('Diccionarios personales')`,
        count(0) AS `count(*)`
    from
        `dic_personal` `dp`
    where
        `dp`.`deleted_at` is null
        and `dp`.`estado` = 1
        and created_at > '2021-08-30 00:00:00'
    union ALL
    select
        concat('Centros'),
        count(0)
    from
        centros c
    where
        c.deleted_at is null
        and c.estado = 1
    union ALL
    select
        CONCAT('Usuarios con rol "', t.rol, '" en diccionarios de aula'),
        count(*)
    FROM (
        select
            dap.dic_aula_id,
            da.titulo,
            dap.persona_id ,
            p.nombre,
            p.NIF_NIE,
            u.role_id,
            r.name as rol
        from
            dic_aula_participantes dap
        inner join personas p on
            p.id = dap.persona_id
        inner join dic_aula da on
            da.id = dap.dic_aula_id
        inner join users_personas up on
            up.persona_id = p.id
        inner join users u on
            up.user_id = u.id
        inner JOIN roles r on
            u.role_id = r.id
        where
            dap.estado = 1
            and dap.deleted_at is null
            AND p.estado = 1
            and p.deleted_at is null
            AND da.estado = 1
            and da.deleted_at is null
            and da.ano_ini_curso_escolar = '2021'
        group by
            persona_id ) as t
    group by
        rol;

CREATE OR REPLACE VIEW `ensenanzasNivelAreaMateria21View` as SELECT count(0) AS veces, me.descripcion as ensenanza, concat('') as nivel_estudios, concat('') as area_materia from dic_aula da left join mst_nivel_estudios mne on mne.id = da.mst_nivel_estudios_id LEFT join mst_areas_materias mam on mam.id = da.mst_area_materia_id left join mst_ensenanzas me on me.id = da.mst_ensenanza_id where da.ano_ini_curso_escolar = 2020 group by mst_ensenanza_id union ALL select '-----', '-----', '-----', '-----' from DUAL union ALL select count(0), me.descripcion as ensenanza, mne.descripcion as nivel_estudios , concat('') as area_materia from dic_aula da left join mst_nivel_estudios mne on mne.id = da.mst_nivel_estudios_id LEFT join mst_areas_materias mam on mam.id = da.mst_area_materia_id left join mst_ensenanzas me on me.id = da.mst_ensenanza_id where da.ano_ini_curso_escolar = 2020 group by mst_ensenanza_id, mst_nivel_estudios_id union ALL select '-----', '-----', '-----', '-----' from DUAL union ALL select count(0), me.descripcion as ensenanza, mne.descripcion as nivel_estudios , mam.descripcion as area_materia from dic_aula da left join mst_nivel_estudios mne on mne.id = da.mst_nivel_estudios_id LEFT join mst_areas_materias mam on mam.id = da.mst_area_materia_id left join mst_ensenanzas me on me.id = da.mst_ensenanza_id where da.ano_ini_curso_escolar = 2020 group by mst_ensenanza_id, mst_nivel_estudios_id, mst_area_materia_id union ALL select '-----', '-----', '-----', '-----' from DUAL union ALL (select count(0), concat('') as ensenanza, concat('') as nivel_estudios, mam.descripcion as area_materia from dic_aula da left join mst_nivel_estudios mne on mne.id = da.mst_nivel_estudios_id LEFT join mst_areas_materias mam on mam.id = da.mst_area_materia_id left join mst_ensenanzas me on me.id = da.mst_ensenanza_id where da.ano_ini_curso_escolar = 2020 group by area_materia ORDER BY area_materia);
