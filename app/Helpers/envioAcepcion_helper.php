<?php

use App\Models\EnvioAcepcion;
use App\Models\EnvioAcepcionMedio;
use App\Models\EnvioAcepcionTematica;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;

if (!function_exists('envioAcepcionTematicas_Actualizar')) {
    /**
     * Guarda en base de datos las temáticas relacionadas con la acepción
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $acepcion DiccionarioPersonalAcepcion
     * @param array $listaTematicas Lista de DiccionarioPersonalAcepcionTematica
     * 
     * @return void
     */
    function envioAcepcionTematicas_Actualizar($envioAcepcion, $listaTematicas)
    {
        // Borramos todas las temáticas que hay en la base de datos
        envioAcepcionTematicas_Borrar($envioAcepcion);

        $arrayIdsTematicas = explode(',', $listaTematicas);
        array_shift($arrayIdsTematicas); // eliminamos el primer elemento del array porque la lista comienza por ","

        // Insertamos las nuevas temáticas en la base de datos
        foreach ($arrayIdsTematicas as $idTematica) {
            $acepcionTematica = new EnvioAcepcionTematica;
            $acepcionTematica->envio_acepcion_id = $envioAcepcion->id;
            $acepcionTematica->tematica_id = $idTematica;
            $grabado = $acepcionTematica->save();
        }
    }
}

if (!function_exists('envioAcepcionTematicas_Borrar')) {
/**
 * Borra todas las temáticas/etiquetas asignadas a la acepcion 
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param [type] $acepcion
 *
 * @return void
 */
function envioAcepcionTematicas_Borrar($acepcion) {
    $deletedRows = EnvioAcepcionTematica::where('envio_acepcion_id', $acepcion->id)->delete();
    return $deletedRows;
}
}

if (!function_exists('envioAcepcionMedio_TratarMedio')) {
/**
 * Recibe el fichero de un medio y su tipo y la acepción donde se desea guardar
 * Sustituye el medio del mismo tipo que tuviera la acepción por el nuevo medio recibido
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param string $file nombre del archivo a tratar
 * @param integer $tipo_medio tipos definidos en ctes.tipos_medios 
 * @param EnvioAcepcion $envioAcepcion
 *
 * @return EnvioAcepcionMedio
 */
function envioAcepcionMedio_TratarMedio($file, $tipo_medio, $envioAcepcion) {
    // ****************** Eliminar el medio anterior
    $medio_anterior = envioAcepcionMedio_GetMedioByAcepcionTipoMedio($tipo_medio, $envioAcepcion->id);
    if ($medio_anterior) {
        envioAcepcionMedio_DeleteMedio($medio_anterior);
    }

    // ****************** Creamos el nuevo medio
    // Grabamos el fichero en el STORAGE
    $filename_original = $file->getClientOriginalName();
    // se puede usar dpMedio_saveMedio en este caso sin problema
    //  entrada y acepción solo se usar para nombrar el archivo
    $filename_storage = dpMedio_SaveMedio($file, $tipo_medio, $envioAcepcion->envioEntrada->id, $envioAcepcion->id);

    // Grabamos en base de datos los datos de la imagen
    $acepcionMedio = new EnvioAcepcionMedio;
    $acepcionMedio->envio_acepcion_id = $envioAcepcion->id;
    $acepcionMedio->tipo_medio = $tipo_medio;
    $acepcionMedio->nombre = $filename_original;
    // $acepcionMedio->url_externa = $acepcionMedio->url_externa; // ?
    $acepcionMedio->url_interna = $filename_storage;
    $acepcionMedio->estado = config('ctes.estados.activo');
    $grabado = $acepcionMedio->save();
    

    // ****************** Creamos thumbnail de Imagen
    if ($tipo_medio == config('ctes.tipos_medios.imagen')) {
        // Obtenemos el path de la imagen origen
        $imagen_origen = dpMedio_getStorageFolderCompleto($tipo_medio) . DIRECTORY_SEPARATOR . $filename_storage;

        // Obtenemos el path de la imagen destino
        $nombre_fichero_destino = getNombreFichero($acepcionMedio->url_interna) . config('ctes.video_thumbnail_sufijo') . ".jpg";
        $imagen_destino = dpMedio_getStorageFolderCompleto($tipo_medio) . DIRECTORY_SEPARATOR . $nombre_fichero_destino;

        // Creamos el thumbnail y lo guardamos en disco
        $imageThumbnail = getImageThumbnail($imagen_origen, $imagen_destino, config('ctes.imagen_thumbnail_tamano'));
    }

    // ****************** Creamos thumbnail de Video
    if ($tipo_medio == config('ctes.tipos_medios.video')) {
        getVideoThumbnail($tipo_medio, $filename_storage);
    }

    return $acepcionMedio;
}
}

if (!function_exists('envioAcepcionMedio_GetMedioByAcepcionTipoMedio')) {
    /**
     * Devuelve el objeto Medio de una acepción según reciba imagen, audio o video en el campo tipo_medio
     * version para editar envioAcepcion en el diccionario de aula 
     *
     * @author julio.buenadicha@altia.es, fernando.ramirez@altia.es
     * @version 1.0.0
     * 
     * @param integer $tipo_medio constante perteneciente a ctes.tipos_medios
     * @param integer $acepcion_id id EnvioAcepcion
     * 
     * @return object EnvioAcepcionMedio::
     */
    function envioAcepcionMedio_GetMedioByAcepcionTipoMedio($tipo_medio, $acepcion_id)
    {
        
        $medio = EnvioAcepcionMedio::where('tipo_medio', '=', $tipo_medio)
            ->where('envio_acepcion_id', '=', $acepcion_id)
            ->first();

        return $medio;
    }
}

if (!function_exists('envioAcepcionMedio_DeleteMedio')) {
    /**
     * Recibe un objeto Medio y lo borra de la base de datos y borra el fichero
     *
     * @author julio.buenadicha@altia.es, fernando.ramirez@altia.es
     * @version 1.0.0
     * 
     * @param object $medio EnvioAcepcionMedio
     * 
     * @return void
     */
    function envioAcepcionMedio_DeleteMedio($medio)
    {
        // *********************   Borramos en base de datos
        EnvioAcepcionMedio::destroy($medio->id);
        
        // NO BORRAMOS EL FICHERO DEL LOS ARCHIVOS DEL SERVIDOR
        // YA QUE ES MUY PROBLABLE QUE ESTE COMPARTIENDO EL MISMO
        // FICHERO CON EL DICCIONARIO PERSONAL
    }
}
