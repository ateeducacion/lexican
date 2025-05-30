<?php

use App\Models\CampoEntrada;
use App\Models\DicAula;
use App\Models\Envio;
use App\Models\DiccionarioPersonalEntrada;
use App\Models\EnvioEntrada;
use App\Models\EnvioAcepcion;
use App\Models\EnvioAcepcionMedio;
use App\Models\EnvioAcepcionTematica;
use Illuminate\Support\Facades\DB;

// ********************************************************************************************  DICCIONARIO

if (!function_exists('dpEnvio_EnviarDiccionarioAListaDiccionariosAula')) {
    /**
     * Envía un diccionario entero a una lista de diccionarios de aula recibidos por parámetro
     *  Devuelve un array con el resultado del envío a cada diccionario de aula
     *  Si hay un error se guarda el error en el resultado del envío y se continúa// , al final se hará rollbak
     *  // Si hay algún error hago rollback
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $diccionario_id id del diccionario personal
     * @param array $arrayDiccionariosAulaIds Lista de ids de DicAula
     * 
     * @return array listaIntentos Cada elemento es un objeto con los campos diccinoarioaula, envio, error
     */
    function dpEnvio_EnviarDiccionarioAListaDiccionariosAula($diccionario_id, $arrayDiccionariosAulaIds)
    {
        $listaIntentos = [];

        // Variable que servirá para saber si debemos hacer rollback
        // Si tengo un error continúo para conocer todos los errores y mostrárselos al usuario // aunque luego haga rollbak
        // $hacerRollback = false;

        foreach ($arrayDiccionariosAulaIds as $diccinoarioAulaId) {
            // Prueba fer , 
            $listaEntradas = dpEntrada_GetListaEntradasByDiccionarioId($diccionario_id);
            $intentoEnvio = dpEnvio_EnviarEntradasADicAula( $diccionario_id, $listaEntradas, $diccinoarioAulaId );
            $listaIntentos[] = $intentoEnvio;
            // array_push($listaIntentos, $intentoEnvio);
        }

        return $listaIntentos;
    }
}

if (!function_exists('dpEnvio_EnviarEntradasADicAula')) {
    /**
     * Enviar entradas del diccionairo personal al diccionario de aula,
     * lo he separado de dpEnvio_EnviarDiccionarioAListaDiccionariosAula por que ahora
     *  se va a escoger que entradas se mandan y cuales no 
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param [integer] $dicPersonalId id del diccionario personal origen de las entradas
     * @param [array.entradas] $listaEntradas listado de entradas que se van a enviar
     * @param [integer] $dicAulaId id diccionario de aula destino
     *
     * @return [object] intentoEnvio con información de el envió , contiene los errores 
     *                  que se hayan podido producir
     */
    function dpEnvio_EnviarEntradasADicAula( $dicPersonalId, $listaEntradas, $dicAulaId ) {
        // Start transaction
        DB::beginTransaction();

        $intentoEnvio = new \stdClass();
        $intentoEnvio->diccinoarioaula = $dicAulaId;

        $diccionario = daGetDiccionarioAulaById($dicAulaId);
        $intentoEnvio->diccionarioNombre = $diccionario->titulo;

        try {
            // Se crea el envío del diccionario personal al envío
            $envio = new Envio;
            $envio->ano_ini_curso_escolar = getAnoIniCursoEscolar(new Datetime());
            $envio->dic_personal_id = $dicPersonalId;
            $envio->dic_aula_id = $dicAulaId;
            $envio->estado = config('ctes.estados_envios.enviado');
            $envio->save();

            $intentoEnvio->envio = $envio;

            foreach ($listaEntradas as $entrada) {
                // Sólo envío las entradas que no están ocultas
                if ($entrada->estado == config('ctes.estados_entrada.visible')) {
                    dpEnvio_EnviarEntradaADiccionarioAula($envio->id, $entrada);
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            // Executed only in PHP 7, will not match in PHP 5.x
            $intentoEnvio->error = $th->getMessage();
            // $hacerRollback = true;
            DB::rollBack();
        } catch (\Exception $e) {
            // Executed only in PHP 5.x, will not be reached in PHP 7
            $intentoEnvio->error = $e->getMessage();

            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.create_or_update'),
                ['file' => $th->getFile(), 'line' => $th->getLine() ],
                PHP_EOL . json_encode($request->all(), JSON_PRETTY_PRINT),
                PHP_EOL . 'Fallo al crear Envio ' . $th->getMessage() .
                PHP_EOL . 'Parametros:' . PHP_EOL .
                " dicPersonalId $dicPersonalId " . PHP_EOL .
                ' listaEntradas ' . json_encode($listaEntradas, JSON_PRETTY_PRINT) .  PHP_EOL .
                " dicAulaId $dicAulaId "
            );
            // $hacerRollback = true;
            DB::rollBack();
        }

        return $intentoEnvio;
    }
}

if (!function_exists('dpEnvio_EnviarDiccionarioUserActualAListaDiccionariosAula')) {
    /**
     * Envía el diccionario entero del usuario actual a una lista de diccionarios de aula recibidos por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param array $arrayDiccionariosAulaIds Lista de ids de DicAula
     * 
     * @return object Envio
     */
    function dpEnvio_EnviarDiccionarioUserActualAListaDiccionariosAula($arrayDiccionariosAulaIds)
    {
        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];

        // Busco el diccionario del usuario actual
        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);

        $listaIntentos = dpEnvio_EnviarDiccionarioAListaDiccionariosAula($diccionario->id, $arrayDiccionariosAulaIds);

        return $listaIntentos;
    }
}

// ********************************************************************************************  ENTRADA

if (!function_exists('dpEnvio_getUltimoEnvioByEntradaByEstado')) {
    /**
     * Devuelve los envíos donde está incluida la entrada recibida por parámetro. Si no existe el envío se devolverá NULL
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $entrada DiccionarioPersonalEntrada
     * 
     * @return array Lista de Envios
     */
    function dpEnvio_getUltimoEnvioByEntradaByEstado($entrada, $estado = null)
    {
        $anoIniCursoEscolar = getAnoIniCursoEscolar(new Datetime());

        if ($estado) {
            // Si se recibe estado por parámetro, se tiene en cuenta
            $envio = Envio::whereHas('envioEntradas', function ($query) use ($entrada, $estado) {
                $query->where('dp_entrada_id', '=', $entrada->id)
                    ->where('estado', '=', $estado);
            })
                ->where('ano_ini_curso_escolar', '=', $anoIniCursoEscolar)
                ->orderBy('created_at', 'desc')
                ->first();
        } else {
            // Si NO se recibe estado por parámetro, NO se tiene en cuenta
            $envio = Envio::whereHas('envioEntradas', function ($query) use ($entrada, $estado) {
                $query->where('dp_entrada_id', '=', $entrada->id);
            })
                ->where('ano_ini_curso_escolar', '=', $anoIniCursoEscolar)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return $envio;
    }
}

if (!function_exists('dpEnvio_getEnvioByEntradaByEstado')) {
    /**
     * Devuelve todos los envíos donde está incluida la entrada recibida por parámetro. Si no existe el envío se devolverá NULL
     *
     * @author natalia.moreira@altia.es
     * @version 1.0.0
     * 
     * @param object $entrada DiccionarioPersonalEntrada
     * 
     * @return array Lista de Envios
     */
    function dpEnvio_getEnvioByEntradaByEstado($entrada, $estado = null)
    {
        $anoIniCursoEscolar = getAnoIniCursoEscolar(new Datetime());

        if ($estado) {
            // Si se recibe estado por parámetro, se tiene en cuenta
            $envio = Envio::whereHas('envioEntradas', function ($query) use ($entrada, $estado) {
                $query->where('dp_entrada_id', '=', $entrada->id)
                    ->where('estado', '=', $estado);
            })
                ->where('ano_ini_curso_escolar', '=', $anoIniCursoEscolar)
                ->orderBy('created_at', 'desc');
        } else {
            // Si NO se recibe estado por parámetro, NO se tiene en cuenta
            $envio = Envio::whereHas('envioEntradas', function ($query) use ($entrada, $estado) {
                $query->where('dp_entrada_id', '=', $entrada->id);
            })
                ->where('ano_ini_curso_escolar', '=', $anoIniCursoEscolar)
                ->orderBy('created_at', 'desc');
        }

        return $envio;
    }
}

if (!function_exists('dpEnvio_getUltimoEnvioPublicadoEnviadoByEntrada')) {
    /**
     * Devuelve el último envío donde está incluida la entrada recibida por parámetro teniendo en cuenta el estado
     *  Primero busca la entrada publicada, luego la enviada y luego el resto
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $entrada DiccionarioPersonalEntrada
     * 
     * @return array Lista de Envios
     */
    function dpEnvio_getUltimoEnvioPublicadoEnviadoByEntrada($entrada)
    {
        $envioByEntrada = dpEnvio_getUltimoEnvioByEntradaByEstado($entrada, config('ctes.estados_envios.publicado'));
        if (!$envioByEntrada) {
            $envioByEntrada = dpEnvio_getUltimoEnvioByEntradaByEstado($entrada, config('ctes.estados_envios.enviado'));
        }
        if (!$envioByEntrada) {
            $envioByEntrada = dpEnvio_getUltimoEnvioByEntradaByEstado($entrada);
        }

        return $envioByEntrada;
    }
}

if (!function_exists('dpEnvio_EnviarEntradaAListaDiccionariosAula')) {
    /**
     * Envía la entrada a una lista de diccionarios de aula recibidos por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param array $arrayDiccionariosAulaIds Lista de ids de DicAula
     * 
     * @return object Envio
     */
    function dpEnvio_EnviarEntradaAListaDiccionariosAula($entrada, $arrayDiccionariosAulaIds)
    {
        $listaIntentos = [];

        // Variable que servirá para saber si debemos hacer rollback
        // Si tengo un error continúo para conocer todos los errores y mostrárselos al usuario // aunque luego haga rollbak
        // $hacerRollback = false;

        foreach ($arrayDiccionariosAulaIds as $diccinoarioAulaId) {

            // Start transaction
            DB::beginTransaction();

            $intentoEnvio = new \stdClass();
            $intentoEnvio->diccinoarioaula = $diccinoarioAulaId;

            $diccionario = daGetDiccionarioAulaById($diccinoarioAulaId);
            $intentoEnvio->diccionarioNombre = $diccionario->titulo;

            try {
                // pero se permiten los envios al diccionario????
                $dic = DicAula::find($diccinoarioAulaId);
                if ( !aceptaEnvios($dic) ) {
                    throw new Exception("Se intento enviar entrada a diccionario con envios no habilitados", 1);
                }

                // Se crea el envío de la entrada
                $envio = new Envio;
                $envio->ano_ini_curso_escolar = getAnoIniCursoEscolar(new Datetime());
                $envio->dic_personal_id = $entrada->dic_personal_id;
                $envio->dic_aula_id = $diccinoarioAulaId;
                $envio->estado = config('ctes.estados_envios.enviado');
                $envio->save();

                $intentoEnvio->envio = $envio;

                // Sólo envío las entradas que no están ocultas
                if ($entrada->estado == config('ctes.estados_entrada.visible')) {
                    // Se copia la entrada al envío
                    dpEnvio_EnviarEntradaADiccionarioAula($envio->id, $entrada);
                }

                DB::commit();
            } catch (\Throwable $th) {
                // Executed only in PHP 7, will not match in PHP 5.x
                // return dd($th);
                $intentoEnvio->error = $th->getMessage();
                // $hacerRollback = true;
                customLoggin(
                    config('ctes.log_levels.error'),
                    config('ctes.log_types.data_base_error'),
                    ['file' => __FILE__, 'line' => __LINE__],
                    $th->getMessage(),
                    'dpEnvio_enviarEntradaAlistaDiccionariosAula : ' .
                    PHP_EOL . 'diccionario:'. $dic->id . 
                    PHP_EOL . 'intentoEnvio: ' .json_encode($intentoEnvio, JSON_PRETTY_PRINT)                     
                );

                DB::rollBack();
            } catch (\Exception $e) {
                // Executed only in PHP 5.x, will not be reached in PHP 7
                $intentoEnvio->error = $e->getMessage();
                // $hacerRollback = true;

                customLoggin(
                    config('ctes.log_levels.error'),
                    config('ctes.log_types.data_base_error'),
                    ['file' => __FILE__, 'line' => __LINE__],
                    $e->getMessage(),
                    'PHP5 catch '.
                    'dpEnvio_enviarEntradaAlistaDiccionariosAula : ' .
                    PHP_EOL . 'intentoEnvio: ' .json_encode($intentoEnvio, JSON_PRETTY_PRINT)                     
                );
                DB::rollBack();
            }
            array_push($listaIntentos, $intentoEnvio);
        }

        return $listaIntentos;
    }
}

if (!function_exists('dpEnvio_EnviarEntradaADiccionarioAula')) {
    /**
     * Envía una entrada a una lista de diccionarios de aula recibidos por parámetro
     *  Devuelve un array con el resultado del envío a cada diccionario de aula
     *  Si hay un error se guarda el error en el resultado del envío y se continúa// , al final se hará rollbak
     *  // Si hay algún error hago rollback
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $diccionario_id
     * @param array $arrayDiccionariosAulaIds Lista de ids de DicAula
     * 
     * @return array listaIntentos Cada elemento es un objeto con los campos diccinoarioaula, envio, error
     */
    function dpEnvio_EnviarEntradaADiccionarioAula($dp_envio_id, $entrada)
    {
        $envioEntrada = new EnvioEntrada;
        $envioEntrada->dp_envio_id = $dp_envio_id;
        $envioEntrada->dp_entrada_id = $entrada->id;
        $envioEntrada->entrada = $entrada->entrada;
        $envioEntrada->estado = $entrada->estado;
        $envioEntrada->updated_by_persona_id = 0;
        $grabado = $envioEntrada->save();


        // Se copian la acepciones de la entrada al envío
        $ListaAcepciones = dpAcepcion_GetListaAcepcionesByEntradaId($entrada->id);
        foreach ($ListaAcepciones as $acepcion) {
            // Sólo envío las acepciones que no están ocultas
            // Y que cumplen con los requisitos del diccienario

            $camposOk = dpEnvio_cumple_requisitos_diccionario( 
                $acepcion, $envioEntrada->dpEnvio->dicAula ); 
            if ( !$camposOk->ok ){
                throw new Exception( 
                    "FALTAN_CAMPOS ".
                    "<ul class='errores-campos'>".
                    join(', ',$camposOk->errors) .
                    "</ul>"
                    // <ul class='errores-campos'>".join(',<br>',$camposOk->errors) .'<ul>'
                     , 1 );
            }

            if ($acepcion->estado == config('ctes.estados_entrada.visible') && $camposOk ) {

                try {
                    $envioAcepcion = new EnvioAcepcion;
                    $envioAcepcion->envio_entrada_id = $envioEntrada->id;
                    $envioAcepcion->orden = $acepcion->orden;
                    $envioAcepcion->cat_gramatical_id = $acepcion->cat_gramatical_id;
                    $envioAcepcion->genero_id = $acepcion->genero_id;
                    $envioAcepcion->numero_id = $acepcion->numero_id;
                    $envioAcepcion->idioma_id = $acepcion->idioma_id;
                    $envioAcepcion->idioma_palabra = $acepcion->idioma_palabra;
                    $envioAcepcion->definicion = $acepcion->definicion;
                    $envioAcepcion->frase_ejemplo = $acepcion->frase_ejemplo;
                    $envioAcepcion->ejemplo2 = $acepcion->ejemplo2;
                    $envioAcepcion->estado = $acepcion->estado;
                    $grabado = $envioAcepcion->save();
                } catch (\Throwable $th) {
                    customLoggin(
                        config('ctes.log_levels.error'),
                        config('ctes.log_types.data_base_error'),
                        ['file' => __FILE__, 'line' => __LINE__ ],
                        PHP_EOL . 'Fallo al crear envioAcepcion: ' . json_encode($envioAcepcion, JSON_PRETTY_PRINT),
                        $th->getMessage()
                    );
                    throw $th;
                }

                // Guardar las tematicas
                dpEnvioTematicas_Actualizar($envioAcepcion, $acepcion->dpAcepcionTematicas);

                // Se copian los medios de la acepción al envío
                $ListaAcepcionMedios = dpEntrada_GetListaAcepcionMediosByAcepcionId($acepcion->id);
                foreach ($ListaAcepcionMedios as $acepcionMedio) {
                    try {
                        $envioAcepcionMedio = new EnvioAcepcionMedio;
                        $envioAcepcionMedio->envio_acepcion_id = $envioAcepcion->id;
                        $envioAcepcionMedio->tipo_medio = $acepcionMedio->tipo_medio;
                        $envioAcepcionMedio->nombre = $acepcionMedio->nombre;
                        $envioAcepcionMedio->url_externa = $acepcionMedio->url_externa;
                        // $envioAcepcionMedio->url_interna = $acepcionMedio->filename_storage;
                        $envioAcepcionMedio->url_interna = $acepcionMedio->url_interna;
                        $envioAcepcionMedio->estado = $acepcionMedio->estado;
                        $grabado = $envioAcepcionMedio->save();
                    } catch (\Throwable $th) {
                        customLoggin(
                            config('ctes.log_levels.error'),
                            config('ctes.log_types.data_base_error'),
                            ['file' => __FILE__, 'line' => __LINE__ ],
                            PHP_EOL . 'Fallo al crear envioAcepcionMedio: ' . json_encode($envioAcepcionMedio, JSON_PRETTY_PRINT),
                            $th->getMessage()
                        );
                        throw $th;
                    }
                    
                }
            }
        }
    }
}

if (!function_exists('dpEnvioTematicas_Actualizar')) {
    /**
     * Guarda en base de datos las temáticas relacionadas con la acepción
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $acepcion EnvioAcepcion
     * @param array $listaTematicas Lista de EnvioAcepcionTematica
     * 
     * @return void
     */
    function dpEnvioTematicas_Actualizar($envioAcepcion, $listaTematicas)
    {
        // Borramos todas las temáticas que hay en la base de datos
        dpEnvio_BorrarTematicas($envioAcepcion);

        
        // Insertamos las nuevas temáticas en la base de datos
        foreach ($listaTematicas as $tematica) {
            $acepcionTematica = new EnvioAcepcionTematica;

            $acepcionTematica->envio_acepcion_id = $envioAcepcion->id;
            $acepcionTematica->tematica_id = $tematica->id;
            try {
                $grabado = $acepcionTematica->save();
            } catch (\Throwable $th) {
                // dd(
                //     $th->getMessage(),
                //     $grabado, $tematica, $acepcionTematica
                // );
                customLoggin(
                    config('ctes.log_levels.error'),
                    config('ctes.log_types.data_base_error'),
                    ['file' => __FILE__, 'line' => __LINE__ ],
                    PHP_EOL . json_encode($request->all(), JSON_PRETTY_PRINT),
                    PHP_EOL . 'AcepcionTematica: ' . json_encode($acepcionTematica, JSON_PRETTY_PRINT)
                );
                throw $th;
            }
        }
    }
}


if (!function_exists('dpEnvio_BorrarTematicas')) {
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
    function dpEnvio_BorrarTematicas($acepcion)
    {
        $deletedRows = EnvioAcepcionTematica::where('envio_acepcion_id', $acepcion->id)->delete();
    }
}

if (!function_exists('dpEnvio_EnvioEntradaEdit')) {
    /**
     * Modifica en la base de datos la EntradaEnvio que se recibe por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $datos EnvioEntrada
     * 
     * @return object EnvioEntrada
     */
    function dpEnvio_EnvioEntradaEdit($datos)
    {
        $envioEntrada = EnvioEntrada::find($datos->id);
        $envioEntrada->dp_envio_id = $datos->dp_envio_id;
        $envioEntrada->dp_entrada_id = $datos->dp_entrada_id;
        $envioEntrada->entrada = $datos->entrada;
        $envioEntrada->estado = $datos->estado;
        $grabado = $envioEntrada->save();

        return $envioEntrada;
    }
}

//comprobar que la acepción que cumple requisitos del dic de aula
if (!function_exists('dpEnvio_cumple_requisitos_diccionario')) {
    function dpEnvio_cumple_requisitos_diccionario( $acepcion, $dicAula ){
        // return false;
        $camposObligatorios = $dicAula->dicAulaCampos()
            ->where('estado', config('ctes.estados.activo') )
            ->where('obligatorio', config('ctes.estados.activo') )
            ->get();
        $ids = [];
        $errores = [];
        foreach($camposObligatorios as $campo ) {
            $nombreCampo = CampoEntrada::find($campo->mst_campo_entrada_id)->nombre_campo;
            // $nombreCampo = '"'.$nombreCampo .'"'; 

            switch( $campo->mst_campo_entrada_id ) {
                case 1: // Categoria gramatical es obligatorio
                    if ( is_null($acepcion->cat_gramatical_id)  )
                        // $errores[] = __('diccionario.falta_campo') .' '. $nombreCampo ;
                        $errores[] = $nombreCampo ;
                    break;
                case 2: // Genero
                    if ( is_null($acepcion->genero_id)  )
                        // $errores[] = __('diccionario.falta_campo') .' '. $nombreCampo ;
                        $errores[] = $nombreCampo ;
                    break;
                case 3: // Numero
                    if ( is_null($acepcion->numero_id)  ) 
                        // $errores[] = __('diccionario.falta_campo') .' '. $nombreCampo ;
                        $errores[] = $nombreCampo ;
                    break;
                case 4: // tematicas generales
                    // dd($acepcion->dpAcepcionTematicas()->get() );
                    if ( $acepcion->dpAcepcionTematicas()->get()->count() == 0 ) 
                        // $errores[] = __('diccionario.falta_campo_plural') .' '. $nombreCampo ;
                        $errores[] = $nombreCampo ;
                    break;
                case 5: // frase ejemplo
                    if ( is_null($acepcion->frase_ejemplo)  )
                        // $errores[] = __('diccionario.falta_campo') .' '. $nombreCampo ;
                        $errores[] = $nombreCampo ;
                    break;
                case 6: // video
                    // dd( 'video', dpMedio_getVideo($acepcion) );
                    if ( is_null( dpMedio_getVideo($acepcion) ) ) 
                        // $errores[] = __('diccionario.falta_campo') .' '. $nombreCampo ;
                        $errores[] = $nombreCampo ;
                    break;
                case 7: // audio
                    // dd( 'audio', dpMedio_getAudio($acepcion) );
                    if ( is_null(dpMedio_getAudio($acepcion))  )
                        // $errores[] = __('diccionario.falta_campo') .' '. $nombreCampo ;
                        $errores[] = $nombreCampo ;
                    break;
                case 8: // imagen
                    // dd( 'imagen', dpMedio_getImagen($acepcion) );
                    if ( is_null(dpMedio_getImagen($acepcion))  )
                        // $errores[] = __('diccionario.falta_campo') .' '. $nombreCampo ;
                        $errores[] = $nombreCampo ;
                    break;
                case 9: // lengua-idioma
                    if ( is_null($acepcion->idioma_palabra) || $acepcion->idioma_palabra == '' )
                        // $errores[] = __('diccionario.falta_campo') .' '. $nombreCampo ;
                        $errores[] = $nombreCampo ;
                    break;
            }            
            
            $ids[] = $campo->mst_campo_entrada_id;
        }
        // dd($acepcion);
        $return = new \stdClass();
        
        if (count($errores)>0 ) {
            $return->ok = false;
            $return->errors = $errores;
        } else {
            $return->ok = true;
        }
        
        return $return;
    }
}

// comprobar que la entrada entera cumple los requisitos de envio a dic de aula 
function dpEnvio_EntradaCumpleRequisitosDiccionario($entrada, $diccionarioAula)
{
    $ListaAcepciones = dpAcepcion_GetListaAcepcionesByEntradaId($entrada->id);
    $errores = [];

    if( $ListaAcepciones->count() == 0 ) {
        $error = new \stdClass();
        $error->tipo = "FALTA_ACEPCION";
            $error->html = "<p>".__('diccionario.envio_error_faltan_acepciones')."</p>";
        $errores[0] = $error;
        // no es necesario seguir
        return $errores;
    }

    foreach ($ListaAcepciones as $acepcion) {
        $camposOk = dpEnvio_cumple_requisitos_diccionario( 
            $acepcion, $diccionarioAula ); 
        if ( !$camposOk->ok ) {
            $error = new \stdClass();
            $error->tipo = "FALTAN_CAMPOS";
            $error->html = "<ul class='errores-campos'>".
                    join(', ',$camposOk->errors) ."</ul>";
            $errores[$acepcion->orden] = $error;
        }
    }
    if(count($errores)>0)
        return $errores;
    else
        return 'ok';
}

/**
 * Para mandar una sola entrada y hacer todo el proceso
 * se diferencia de dpEnvio_EnviarEntradaADiccionarioAula en que no tienes creado el Envio
 * y usas la entrada del diccionario personal
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param DiccionarioPersonalEntrada $entrada
 * @param DicAula $dicAula
 *
 * @return void
 */
function dpEnvio_EnviarEntradaDicPersonalADicAula( DiccionarioPersonalEntrada $entrada, DicAula $dicAula )
{
    try {
        DB::beginTransaction();

        $intentoEnvio = new \stdClass();
        $intentoEnvio->diccinoarioaula = $dicAula->id;
        $intentoEnvio->diccionarioNombre = $dicAula->titulo;
        if ( !aceptaEnvios($dicAula) ) {
            throw new Exception("Se intento enviar entrada a diccionario con envios no habilitados", 1);
        }
        // Se crea el envío de la entrada
        $envio = new Envio;
        $envio->ano_ini_curso_escolar = getAnoIniCursoEscolar(new Datetime());
        $envio->dic_personal_id = $entrada->dic_personal_id;
        $envio->dic_aula_id = $dicAula->id;
        $envio->estado = config('ctes.estados_envios.enviado');
        $envio->save();

        $intentoEnvio->envio = $envio;
        // Sólo envío las entradas que no están ocultas
        if ($entrada->estado == config('ctes.estados_entrada.visible')) {
            // Se copia la entrada al envío
            dpEnvio_EnviarEntradaADiccionarioAula($envio->id, $entrada);
        }
        DB::commit();
    } catch (\Throwable $th) {
        customLoggin(
            config('ctes.log_levels.error'),
            config('ctes.log_types.data_base_error'),
            ['file' => $th->getFile(), 'line' => $th->getLine()],
            $th->getMessage(),
            PHP_EOL . 'diccionario:'. $dicAula->id . 
            PHP_EOL . 'intentoEnvio: ' .json_encode($intentoEnvio, JSON_PRETTY_PRINT)                     
        );
        DB::rollBack();
        //throw $th;
    }
    

}
