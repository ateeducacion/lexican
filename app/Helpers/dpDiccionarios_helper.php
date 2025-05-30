<?php

use App\Models\DiccionarioPersonal;

if (!function_exists('dpDiccionario_GetDiccionarioById')) {
    /**
     * Devuelve el diccionario personal buscado en la base de datos filtrando por el id recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $diccionario_id
     * 
     * @return object DiccionarioPersonal
     */
    function dpDiccionario_GetDiccionarioById($diccionario_id)
    {
        $diccionario = DiccionarioPersonal::find($diccionario_id);

        return $diccionario;
    }
}

if (!function_exists('dpDiccionario_GetDiccionarioByPersonaId')) {
    /**
     * Devuelve el diccionario personal buscado en la base de datos filtrando por el id de persona recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $persona_id
     * 
     * @return object DiccionarioPersonal
     */
    function dpDiccionario_GetDiccionarioByPersonaId($persona_id)
    {
        $diccionario = DiccionarioPersonal::where('persona_id', $persona_id)->first();

        return $diccionario;
    }
}

if (!function_exists('dpDiccionario_GetDiccionarioByUserActual')) {
    /**
     * Devuelve el diccionario personal del usuario actual
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @return object DiccionarioPersonal
     */
    function dpDiccionario_GetDiccionarioByUserActual()
    {
        // Obtenemos el id de persona del usuario conectado
        try {
            $persona_id = getSessionPersona()['id'];
        } catch (\Throwable $th) {
            throw $th;
        }

        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);

        // Si no existe un diccionario personal para este usuario, se crea
        if (!$diccionario) {
            $diccionario = dpDiccionario_AddDiccionario($persona_id);
        }

        return $diccionario;
    }
}

if (!function_exists('dpDiccionario_AddDiccionario')) {
    /**
     * Crea un diccionario personal en la base de datos usando el campo id de persona que se recibe por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $persona_id
     * 
     * @return object DiccionarioPersonal
     */
    function dpDiccionario_AddDiccionario($persona_id)
    {
        $diccionario = new DiccionarioPersonal;
        $diccionario->persona_id = $persona_id;
        $diccionario->titulo = "Mi diccionario personal";
        $diccionario->estado = config('ctes.estados.activo');
        try {
            $grabado = $diccionario->save();
        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.info'),
                config('ctes.log_types.info'),
                ['file' => $th->getFile(), 'line' => $th->getLine() ],
                PHP_EOL . 'diccionario: ' . json_encode($diccionario, JSON_PRETTY_PRINT).
                PHP_EOL . 'persona_id: ' . $persona_id
            );
        }
        

        return $diccionario;
    }
}
