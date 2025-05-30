<?php 

// -- SQLs PARA BORRA DICCIONARIO 
// borrar relaciones con dic_aula_id

$ids = [1];

$del_avisos ="UPDATE avisos SET delete_at=NOW()  WHERE dic_aula_id IN $ids";

// comentarios entradas
    // Antes borrar envios entrada
    // esta vinculado a 'envios_entradas' con 'envio_entrada_id'
    // y tambien a la tabla 'personas' con 'persona_id'    
$del_comentarios_entradas ="UPDATE comentarios_entradas SET delete_at=NOW()  WHERE dic_aula_id IN $ids";
$del_comentarios_generales ="UPDATE comentarios_generales SET delete_at=NOW()  WHERE dic_aula_id IN $ids";
$del_atemporales ="UPDATE dic_aula_atemporales SET delete_at=NOW()  WHERE dic_aula_id IN $ids";
$del_dic_aula_campos ="UPDATE dic_aula_campos SET delete_at=NOW()  WHERE dic_aula_id IN $ids";
$del_dic_aula_destinatario_avisos = "UPDATE dic_aula_destinatario_avisos SET delete_at=NOW()  WHERE dic_aula_id IN $ids";
$count_dic_aula_entradas = "SELECT count(*) FROM dic_aula_entradas WHERE dic_aula_id IN $ids";
$del_dic_aula_participantes = "UPDATE dic_aula_participantes SET delete_at=NOW()  WHERE dic_aula_id IN $ids";
$del_dp_envios = "UPDATE dp_envios SET delete_at=NOW()  WHERE dic_aula_id IN $ids";

$del_dic_aula_pautas = "UPDATE dic_aula_pautas SET delete_at=NOW()  WHERE dic_aula_id IN $ids";