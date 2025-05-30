
select
    concat('Diccionarios de aula') AS `concat('Diccionarios de aula')`,
    count(0) AS `count(*)`
from
    `dic_aula`
where
    `dic_aula`.`deleted_at` is null
    and `dic_aula`.`estado` = 1
and ano_ini_curso_escolar = 2021;

select
    concat('Diccionarios personales') AS `concat('Diccionarios personales')`,
    count(0) AS `count(*)`
from
    `dic_personal` `dp`
where
    `dp`.`deleted_at` is null
    and `dp`.`estado` = 1
    and created_at > '2021-08-30 00:00:00' 
;


select
    concat('Centros'), 
    count(0)
from
    centros c 
where
    c.deleted_at  is null
    and c.estado = 1
;

select CONCAT('Usuarios con rol "',t.rol, '" en diccionarios de aula'), count(*) FROM (
	select
		-- count(*),
		-- concat(r.name, ' en diccionarios '),
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
		and
dap.deleted_at is null
		AND
p.estado = 1
		and
p.deleted_at is null
		AND
da.estado = 1
		and
da.deleted_at is null
and da.created_at > '2021-08-30 00:00:00' 
group by persona_id
) as t
group by rol