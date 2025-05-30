<?php

use App\Models\DiccionarioPersonalAcepcion;
use App\Models\DiccionarioPersonalAcepcionTematica;

if (!function_exists('dpAcepcion_GetAcepcionById')) {
    /**
     * Devuelve la acepción buscada en la base de datos filtrando por el id recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $acepcion_id
     * 
     * @return object DiccionarioPersonalAcepcion
     */
    function dpAcepcion_GetAcepcionById($acepcion_id)
    {
        $acepcion = DiccionarioPersonalAcepcion::find($acepcion_id);

        return $acepcion;
    }
}

if (!function_exists('dpAcepcion_Add')) {
    /**
     * Crea una acepción en la base de datos a partir de un objeto tipo acepción que se recibe por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $datos DiccionarioPersonalAcepcion
     * 
     * @return object DiccionarioPersonalAcepcion
     */
    function dpAcepcion_Add($datos)
    {
        // Se crea la Acepcion
        $acepcion = new DiccionarioPersonalAcepcion;
        $acepcion = dpAcepcionSave($acepcion, $datos);

        return $acepcion;
    }
}

if (!function_exists('dpAcepcion_Edit')) {
    /**
     * Modifica en la base de datos la acepción que se recibe por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $datos DiccionarioPersonalAcepcion
     * 
     * @return object DiccionarioPersonalAcepcion
     */
    function dpAcepcion_Edit($datos)
    {
        // Se crea la Acepcion
        $acepcion = DiccionarioPersonalAcepcion::find($datos->id);
        $acepcion = dpAcepcionSave($acepcion, $datos);
        return $acepcion;
    }
}

if (!function_exists('dpAcepcionSave')) {

/**
 * guarda datos de una acepción en la base de datos
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.1.0
 *
 * @param DiccionarioPersonalAcepcion $acepcion
 * @param object $datos
 * @return Bool|DiccionarioPersonalAcepcion
 */
    function dpAcepcionSave($acepcion,$datos)
    {
        $acepcion->dic_entrada_id = $datos->dic_entrada_id;
        $acepcion->orden = $datos->orden;
        $acepcion->cat_gramatical_id = $datos->cat_gramatical_id;
        $acepcion->genero_id = $datos->genero_id;
        $acepcion->numero_id = $datos->numero_id;
        $acepcion->idioma_id = $datos->idioma_id;
        $acepcion->idioma_palabra = $datos->idioma_palabra;
        $acepcion->definicion = $datos->definicion;
        $acepcion->frase_ejemplo = $datos->frase_ejemplo;
        $acepcion->ejemplo2 = $datos->ejemplo2;
        $acepcion->estado = $datos->estado;

        $grabado = $acepcion->save();
        if ($grabado) {
            // dd(
            //     '$datos-ejemplo2', $datos->ejemplo2,
            //     '$acepcion-ejemplo2',$acepcion->ejemplo2,
            //     '$acepcion-estado',$acepcion->estado,
            //     'estado oculto:' , config('ctes.estados_entrada.oculta'),
            //     'estado publicada:', config('ctes.estados_entrada.publicada'),
            // );
            return $acepcion;
        } else {
            return false;
        }
    }
}

if (!function_exists('dpAcepcionTematicas_Actualizar')) {
    /**
     * Guarda en base de datos las temáticas relacionadas con la acepción
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $acepcion DiccionarioPersonalAcepcion
     * @param string $listaTematicas Lista de DiccionarioPersonalAcepcionTematica separadas por comas
     * 
     * @return void
     */
    function dpAcepcionTematicas_Actualizar($acepcion, $listaTematicas)
    {
        // Borramos todas las temáticas que hay en la base de datos
        dpAcepcion_BorrarTematicas($acepcion);

        $arrayIdsTematicas = explode(',', $listaTematicas);
        array_shift($arrayIdsTematicas); // eliminamos el primer elemento del array porque la lista comienza por ","

        // Insertamos las nuevas temáticas en la base de datos
        foreach ($arrayIdsTematicas as $idTematica) {
            $acepcionTematica = new DiccionarioPersonalAcepcionTematica;
            $acepcionTematica->dp_acepcion_id = $acepcion->id;
            $acepcionTematica->tematica_id = $idTematica;
            $grabado = $acepcionTematica->save();
        }
    }
}


if (!function_exists('dpAcepcion_DeleteAcepcionById')) {
    /**
     * Borra la acepción con el id recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $acepcion_id
     * 
     * @return void
     */
    function dpAcepcion_DeleteAcepcionById($acepcion_id)
    {
        DiccionarioPersonalAcepcion::destroy($acepcion_id);
    }
}

if (!function_exists('dpAcepcion_DeleteAcepcionByAcepcion')) {
    /**
     * Borra la acepción con el id recibido por parámetro
     * Reenumero las acepciones con orden posterior
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $acepcion_id
     * 
     * @return void
     */
    function dpAcepcion_DeleteAcepcionByAcepcion($acepcion)
    {
        $entrada_id = $acepcion->dic_entrada_id;
        $acepcion_id = $acepcion->id;
        $acepcion_orden = $acepcion->orden;

        dpAcepcion_ReordenaAcepcionByAcepcionIdByOrden($entrada_id, $acepcion_orden);

        dpAcepcion_DeleteAcepcionById($acepcion_id);
    }
}

if (!function_exists('dpAcepcion_ReordenaAcepcionByAcepcionIdByOrden')) {
    /**
     * Recibe una entrada id y un número de orden. En teoría son de una acepción que se va a borrar
     * Hay que buscar el listado de acepciones con orden posterior a esta, y restarles a todas uno al orden
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $acepcion_id
     * 
     * @return void
     */
    function dpAcepcion_ReordenaAcepcionByAcepcionIdByOrden($entrada_id, $acepcion_orden)
    {
        // Acepciones con orden posterior al recibido
        $acepciones = DiccionarioPersonalAcepcion::where('dic_entrada_id', '=', $entrada_id)
            ->where('orden', '>', $acepcion_orden)
            ->orderBy('orden', 'asc')
            ->get();

        // Para cada acepción a modificar se le resta uno al orden
        $acepciones->each(function ($acepcion) {
            $acepcion->orden = $acepcion->orden - 1;
            dpAcepcion_Edit($acepcion);
        });
    }
}

// Devuelve todas las Acepciones que tengan el id de la entrada recibida
if (!function_exists('dpAcepcion_GetListaAcepcionesByEntradaId')) {
    /**
     * Devuelve un array con las acepciones de la entrada con id recibido por parámetro
     * Solo acepciones visibles
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $entrada_id
     * 
     * @return array DiccionarioPersonalAcepcion
     */
    function dpAcepcion_GetListaAcepcionesByEntradaId($entrada_id)
    {
        $acepciones = DiccionarioPersonalAcepcion::where('dic_entrada_id', '=', $entrada_id)
            ->where('estado',config('ctes.estados_entrada.visible'))
            ->orderBy('orden', 'asc')
            ->get();

        return $acepciones;
    }
}

if (!function_exists('dpAcepcion_GetAcepcionByEntradaIdOrden')) {
    /**
     * Devuelve la acepción de la entrada con id y con el orden recibidos por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $entrada_id
     * @param integer $orden
     * 
     * @return object DiccionarioPersonalAcepcion
     */
    function dpAcepcion_GetAcepcionByEntradaIdOrden($entrada_id, $orden)
    {
        $acepcion = DiccionarioPersonalAcepcion::where('dic_entrada_id', '=', $entrada_id)
            ->where('orden', '=', $orden)
            ->first();

        return $acepcion;
    }
}

if (!function_exists('dpAcepcion_GetAcepcionAnteriorById')) {
    /**
     * Devuelve la acepción anterior según el campo orden de la entrada con id recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $acepcion_id
     * 
     * @return object DiccionarioPersonalAcepcion
     */
    function dpAcepcion_GetAcepcionAnteriorById($acepcion_id)
    {
        $acepcionReferencia = dpAcepcion_GetAcepcionById($acepcion_id);
        $acepcionReferenciaOrden = $acepcionReferencia->orden;
        $acepcionReferenciaEntradaId = $acepcionReferencia->dic_entrada_id;

        if ($acepcionReferenciaOrden > config('ctes.primer_registro')) {
            $acepcionBuscadaOrden =  $acepcionReferenciaOrden - 1;
            $acepcionBuscada = dpAcepcion_GetAcepcionByEntradaIdOrden($acepcionReferenciaEntradaId, $acepcionBuscadaOrden);
        } else {
            // Si el orden que hemos recibido es 1, se devuelve la misma acepción que hemos deribido
            $acepcionBuscada = $acepcionReferencia;
        }

        return $acepcionBuscada;
    }
}

if (!function_exists('dpAcepcion_GetAcepcionSiguienteById')) {
    /**
     * Devuelve la acepción siguiente según el campo orden de la entrada con id recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $acepcion_id
     * 
     * @return object DiccionarioPersonalAcepcion
     */
    function dpAcepcion_GetAcepcionSiguienteById($acepcion_id)
    {
        $acepcionReferencia = dpAcepcion_GetAcepcionById($acepcion_id);
        $acepcionReferenciaOrden = $acepcionReferencia->orden;
        $acepcionReferenciaEntradaId = $acepcionReferencia->dic_entrada_id;


        $acepcionBuscadaOrden =  $acepcionReferenciaOrden + 1;
        $acepcionBuscada = dpAcepcion_GetAcepcionByEntradaIdOrden($acepcionReferenciaEntradaId, $acepcionBuscadaOrden);

        // Si no hay acepción siguiente, se devuelve la misma acepción que hemos deribido
        if (!$acepcionBuscada) {
            $acepcionBuscada = $acepcionReferencia;
        }

        return $acepcionBuscada;
    }
}

if (!function_exists('dpAcepcion_SetAcepcionOrdenByAcepcion')) {
    /**
     * dpAcepcion_SetAcepcionOrdenByAcepcion
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $acepcion DiccionarioPersonalAcepcion
     * @param integer $orden
     * 
     * @return object DiccionarioPersonalAcepcion
     */
    function dpAcepcion_SetAcepcionOrdenByAcepcion($acepcion, $orden)
    {
        $acepcion->orden = $orden;
        $acepcion->save();
        return $acepcion;
    }
}

if (!function_exists('dpAcepcion_BorrarTematicas')) {
    /**
     * Borra todas las temáticas de la acepción recibida por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $acepcion DiccionarioPersonalAcepcion
     * 
     * @return void
     */
    function dpAcepcion_BorrarTematicas($acepcion)
    {
        $deletedRows = DiccionarioPersonalAcepcionTematica::where('dp_acepcion_id', $acepcion->id)->delete();
    }
}
