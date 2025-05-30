SELECT
	count(0), 
	me.descripcion  as ensenanza,
	concat('') as nivel_estudios,
	concat('') as area_materia
from
	dic_aula da
left join mst_nivel_estudios mne 
on
	mne.id = da.mst_nivel_estudios_id
LEFT join mst_areas_materias mam 
on
	mam.id = da.mst_area_materia_id
left join mst_ensenanzas me 
on
	me.id = da.mst_ensenanza_id
where dic_aula.ano_ini_curso_escolar = 2021   
	group by mst_ensenanza_id; 
    
 select
	count(0),
	me.descripcion  as ensenanza,
	mne.descripcion as nivel_estudios ,
	concat('') as area_materia
from
	dic_aula da
left join mst_nivel_estudios mne 
on
	mne.id = da.mst_nivel_estudios_id
LEFT join mst_areas_materias mam 
on
	mam.id = da.mst_area_materia_id
left join mst_ensenanzas me 
on
	me.id = da.mst_ensenanza_id
where da.ano_ini_curso_escolar = 2021   
group by mst_ensenanza_id, mst_nivel_estudios_id

; 
select
	count(0),
	me.descripcion  as ensenanza,
	mne.descripcion as nivel_estudios ,
	mam.descripcion as area_materia
from
	dic_aula da
left join mst_nivel_estudios mne 
on
	mne.id = da.mst_nivel_estudios_id
LEFT join mst_areas_materias mam 
on
	mam.id = da.mst_area_materia_id
left join mst_ensenanzas me 
on
	me.id = da.mst_ensenanza_id
where da.ano_ini_curso_escolar = 2021
group by mst_ensenanza_id, mst_nivel_estudios_id, mst_area_materia_id
; 
select
	count(0),
	concat('') as ensenanza,
	concat('') as nivel_estudios,
	mam.descripcion as area_materia
from
	dic_aula da
left join mst_nivel_estudios mne 
on
	mne.id = da.mst_nivel_estudios_id
LEFT join mst_areas_materias mam 
on
	mam.id = da.mst_area_materia_id
left join mst_ensenanzas me 
on
	me.id = da.mst_ensenanza_id
where da.ano_ini_curso_escolar = 2021
group by area_materia
ORDER BY area_materia