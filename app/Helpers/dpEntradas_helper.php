<?php

use App\Models\CampoValor;
use App\Models\DiccionarioPersonal;
use App\Models\DiccionarioPersonalAcepcionTematica;
use App\Models\DiccionarioPersonalEntrada;
use App\Models\NivelEstudio;

if (!function_exists('dpEntrada_GetEntradaById')) {
    /**
     * Devuelve la entrada buscada en la base de datos filtrando por el id recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param integer $entrada_id
     *
     * @return object DiccionarioPersonalEntrada
     */
    function dpEntrada_GetEntradaById($entrada_id)
    {
        $entrada = DiccionarioPersonalEntrada::find($entrada_id);

        return $entrada;
    }
}

if (!function_exists('dpEntrada_DeleteEntradaById')) {
    /**
     * Borra la entrada con el id recibido por parámetro
     *  Se comprueba si la entrada se ha enviado
     *      Si no se ha enviado se borra del todo
     *      Si se ha enviado y el envío está pendiente del profe se hace softdelete
     *      Si se ha enviado y el envío se ha publicado no se borra
     *      Si se ha enviado y es otro el caso se da como error
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param integer $entrada_id
     *
     * @return integer constante perteneciente a ctes.resultados
     */
    function dpEntrada_DeleteEntradaById($entrada_id)
    {
        $entrada = DiccionarioPersonalEntrada::find($entrada_id);

        // Primero busco un envío con la entrada publicada
        // Si no hay entrada publicada, busco envío con entrada enviada
        $envio = dpEnvio_getUltimoEnvioPublicadoEnviadoByEntrada($entrada);
        if (!$envio) {
            // Se borra del todo si NO se ha enviado
            // TODO: corregir forceDelete
            // $entrada->forceDelete(); // da un error ahora mismo force delete
            $entrada->delete();
            $resultado = config('ctes.resultados.entrada_borrada');
        } else {
            // busca si alguno de los envíos se publicó
            if( dpEnvio_getEnvioByEntradaByEstado($entrada, config('ctes.estados_envios.publicado')) &&
                dpEnvio_getEnvioByEntradaByEstado($entrada, config('ctes.estados_envios.publicado'))->count() > 0 )
            {
                return config('ctes.resultados.entrada_publicada');
            }
            switch ($envio->estado) {
                case config('ctes.estados_envios.borrado_logico'):
                    // No se puede dar este caso, porque implica que ya se había borrado antes
                    // ERROR
                    $resultado = config('ctes.resultados.error_entrada_previamente_borrada');
                    break;

                case config('ctes.estados_envios.enviado'):
                    // Si la entrada se ha enviado se hace soft delete de la entrada del diccionario personal
                    $entrada->estado = config('ctes.estados_envios.borrado_logico');
                    $entrada->delete();
                    // Si la entrada se ha enviado se cambia el estado de la entrada del envio y NO modifico el envío
                    $envioEntrada = $envio->envioEntradas->where('dp_entrada_id', '=', $entrada->id)->first();
                    $envioEntrada->estado = config('ctes.estados_envios.borrado_logico');
                    dpEnvio_EnvioEntradaEdit($envioEntrada);
                    // hacemos borrado lógico del envío de la entrada
                    $envioEntrada->delete();

                    $resultado = config('ctes.resultados.entrada_borrada');
                    break;

                case config('ctes.estados_envios.publicado'):
                    // No se borra si la entrada se ha publicado
                    $resultado = config('ctes.resultados.entrada_publicada');
                    break;

                default:
                    // ERROR
                    $resultado = config('ctes.resultados.error_generico');
                    break;
            }
        }

        return $resultado;
    }
}

if (!function_exists('dpEntrada_GetEntradaByEntradaByDiccionario')) {
    /**
     * Devuelve la entrada con el campo entrada y el id de diccionario recibidos por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param string $entrada_entrada
     * @param integer $diccionario_id
     *
     * @return object DiccionarioPersonalEntrada
     */
    function dpEntrada_GetEntradaByEntradaByDiccionario($entrada_entrada, $diccionario_id, $listaCategorias=null)
    {
        \DB::enableQueryLog(); // Enable query log                
        // buscar sin tener en cuenta mayusculas/minusculas // collate utf8_bin o utf8mb4_bin  
        $entrada = DiccionarioPersonalEntrada::
            // whereRaw('LOWER(`entrada`) LIKE "'.mb_strtolower($entrada_entrada).'"  COLLATE utf8_bin')
            whereRaw('LOWER(`entrada`) LIKE "'.mb_strtolower($entrada_entrada).'"' )
            ->where('dic_personal_id', '=', $diccionario_id);

            // filtar por lista de categorias
            if ($listaCategorias) {

                $entrada->whereHas('dpAcepciones', function($q) use ($listaCategorias){
                    $q->whereHas('dpAcepcionTematicas', function($q) use ($listaCategorias){
                        $q->whereIn('tematica_id', $listaCategorias);
                    });
                });               
            }
            
            $entrada = $entrada->first();
            // dd(
            //     'log sqls', \DB::getQueryLog(), // Show results of log
            //     'entrada', $entrada
            // ); 

        return $entrada;
    }
}

if (!function_exists('dpEntrada_GetEntradaByEntradaByUserActual')) {
    /**
     * Devuelve la entrada con el campo entrada recibido por parámetro y que es del usuario actual
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param string $entrada_entrada
     *
     * @return object DiccionarioPersonalEntrada
     */
    function dpEntrada_GetEntradaByEntradaByUserActual($entrada_entrada, $listaCategorias=null)
    {
        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];

        // Busco el diccionario del usuario actual
        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);

        if ($diccionario) {
            $entrada = dpEntrada_GetEntradaByEntradaByDiccionario($entrada_entrada, $diccionario->id, $listaCategorias);
            return $entrada;
        } else {
            // throw new Exception('No se ha encontrado el diccionario del usuario actual');
            return;
        }
    }
}

if (!function_exists('dpEntrada_Add')) {
    /**
     * Crea una entrada en la base de datos usando el campo entrada que se recibe por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param string $entrada_entrada
     *
     * @return object DiccionarioPersonalEntrada
     */
    function dpEntrada_Add($entrada_entrada)
    {
        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];

        // Busco el diccionario del usuario actual
        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);

        // Si no existe un diccionario personal para este usuario, se crea
        if (!$diccionario) {
            $diccionario = dpDiccionario_AddDiccionario($persona_id);
        }

        // Se crea la entrada
        $entrada = new DiccionarioPersonalEntrada;
        $entrada->dic_personal_id = $diccionario->id;
        $entrada->entrada = $entrada_entrada;
        $entrada->estado = config('ctes.estados.activo');
        $grabado = $entrada->save();

        return $entrada;
    }
}

if (!function_exists('dpEntrada_AddToDiccionario')) {
    /**
     * Crea una entrada en la base de datos usando el campo entrada que se recibe por parámetro
     *
     * @access public
     * @param string $entrada_entrada
     * @param string $diccionario_id
     *
     * @return object DiccionarioPersonalEntrada
     */
    function dpEntrada_AddToDiccionario($entrada_entrada, $diccionario_id)
    {
        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];

        // Busco el diccionario del usuario actual
        $diccionario = DiccionarioPersonal::find($diccionario_id);

        // Si no existe un diccionario personal devuelve null
        if (!$diccionario) {
            return;
        }

        // Se crea la entrada
        $entrada = new DiccionarioPersonalEntrada;
        $entrada->dic_personal_id = $diccionario->id;
        $entrada->entrada = $entrada_entrada;
        $entrada->estado = config('ctes.estados.activo');
        $grabado = $entrada->save();

        return $entrada;
    }
}

if (!function_exists('dpEntrada_Edit')) {
    /**
     * Modifica en la base de datos la entrada que se recibe por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param object $entrada DiccionarioPersonalEntrada
     *
     * @return object DiccionarioPersonalEntrada
     */
    function dpEntrada_Edit($entrada)
    {
        //$entrada = DiccionarioPersonalEntrada::find($tmp_entrada->id);
        $grabado = $entrada->save();

        return $entrada;
    }
}

if (!function_exists('dpEntrada_GetListaEntradasByEntrada')) {
    /**
     * Devuelve todas las entradas que tengan en el campo entrada el valor recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param string $entrada_entrada
     *
     * @return array lista de DiccionarioPersonalEntrada
     */
    function dpEntrada_GetListaEntradasByEntrada($entrada_entrada)
    {
        $entradas = DiccionarioPersonalEntrada::where('entrada', 'like', "%$entrada_entrada%")->get();

        return $entradas;
    }
}

if (!function_exists('dpEntrada_GetListaEntradasByDiccionarioId')) {
    /**
     * Devuelve todas las entradas del diccionario recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param string $diccionario_id
     *
     * @return array lista de DiccionarioPersonalEntrada
     */
    function dpEntrada_GetListaEntradasByDiccionarioId($diccionario_id)
    {
        $entradas = DiccionarioPersonalEntrada::where('dic_personal_id', '=', $diccionario_id)
            ->get();

        return $entradas;
    }
}

if (!function_exists('dpEntrada_GetSiguienteOrdenAcepcionByEntrada')) {
    /**
     * Devuelvel el número de orden siguente al de la entrada recibida por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param object $entrada DiccionarioPersonalEntrada
     *
     * @return integer
     */
    function dpEntrada_GetSiguienteOrdenAcepcionByEntrada($entrada)
    {
        $orden = config('ctes.primer_registro');
        dpEntrada_fixOrder($entrada);
        if ($entrada->dpAcepciones->last()) {
            $orden = $entrada->dpAcepciones()
                ->orderBy('orden')->get()->last()->orden + 1;
        }
        return $orden;
    }
}

if (!function_exists('dpEntrada_fixOrder')) {
    /**
     * Reescribe orden para que no existan saltos ni repeticiones en los numeros de orden
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param [Entrada] $entrada
     *
     * @return void
     */
    function dpEntrada_fixOrder($entrada) {
        $inicio = config('ctes.primer_registro');
        $acepciones = $entrada->dpAcepciones()->orderBy('orden')->get();

        // $primera = $acepciones->first()->orden;
        // if ( $primera->orden !== $inicio){
        //     $primera = $inicio;
        //     $primera->save();
        // }
        $i = $inicio;
        foreach ( $acepciones as $acepcion) {
            $acepcion->orden = $i;
            $acepcion->save();
            $i++;
        }
    }
}

if (!function_exists('dpEntrada_GetConfirm')) {
    /**
     * dpEntrada_GetConfirm
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param object $entrada DiccionarioPersonalEntrada
     *
     * @return string cadena con el mensaje de confirmación que se mostraría al borrar la entrada
     */
    function dpEntrada_GetConfirm($entrada)
    {
        $envioByEntrada = dpEnvio_getUltimoEnvioPublicadoEnviadoByEntrada($entrada);
        $confirm = __('diccionario.confirm_entrada_borrar');
        if ($envioByEntrada) {
            switch ($envioByEntrada->estado) {
                case config('ctes.estados_envios.enviado'):
                    $confirm = __('diccionario.confirm_entrada_enviada', ['entrada' => $entrada->entrada]);
                    break;

                case config('ctes.estados_envios.borrado_logico'): // No se puede dar este caso, porque implica que ya se había borrado antes
                case config('ctes.estados_envios.publicado'): // Cuando se intente borrar se dará un aviso de error
                default:
                    // ERROR
                    $confirm = __('diccionario.confirm_entrada_borrar');
                    break;
            }
        }

        return $confirm;
    }
}

if (!function_exists('dpEntrada_GetNumEntradasByUserActual')) {
    /**
     * Obtener numero de entradas el el diccionario del usuario actual
     *
     *
     * @return integer Numero entradas
     */
    function dpEntrada_GetNumEntradasByUserActual()
    {
        // Obtenemos el id de persona del usuario conectado
        try {
            $persona_id = getSessionPersona()['id'];
        } catch (\Throwable $th) {
            throw $th;
            // return redirect('cas.logout')->with('error', 'No se pudo obtener el usuario conectado');
        }

        // Busco el diccionario del usuario actual
        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);

        if ($diccionario) {
            return dpEntrada_GetNumEntradasDiccionario($diccionario->id);
        } else {
            return;
        }
    }
}

if (!function_exists('dpEntrada_GetNumEntradasDiccionario')) {
    /**
     * Obtener numero de entradas el el diccionario del usuario actual
     *
     * @access public
     *
     * @return integer Numero entradas
     */
    function dpEntrada_GetNumEntradasDiccionario($diccionario_id)
    {
        $n = DiccionarioPersonalEntrada::where('dic_personal_id', '=', $diccionario_id)
            ->count();
        return $n;
    }
}

if (!function_exists('dpEntrada_GetAllEntradasByUserActual')) {
    /**
     * Devuelve todas las entradas del diccionario del usuario
     *
     *
     * @return array lista de DiccionarioPersonalEntrada
     */
    function dpEntrada_GetAllEntradasByUserActual()
    {
        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];
        // Busco el diccionario del usuario actual
        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);
        $entradas = [];
        if ($diccionario) {
            $entradas = $diccionario->dpEntradas()
                ->orderBy('entrada', 'asc')->get();
        }

        return $entradas;
    }
}

if (!function_exists('dpEntrada_GetEntradasByUserActual')) {
    /**
     * Devuelve las entradas del diccionario del usuario
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param integer $offset a partir de que entrada empieza
     * @param integer $limit numero de entradas que muestra
     *
     * @return array lista de DiccionarioPersonalEntrada
     */
    function dpEntrada_GetEntradasByUserActual($offset = 0, $limit = 10)
    {
        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];
        // Busco el diccionario del usuario actual
        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);
        $entradas = [];
        if ($diccionario) {
            $entradas = $diccionario->dpEntradas()
                ->orderBy('entrada', 'asc')
                ->offset($offset)->limit($limit)->get();
        }

        return $entradas;
    }
}

if (!function_exists('dpEntrada_GetEntradasByUserActualLetraInicial')) {
    /**
     * Devuelve todas las entradas del diccionario del usuario
     *
     * @version 1.0.0
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     *
     * @param String $letra letra inicical a buscar
     * @param integer $offset a partir de que entrada empieza
     *
     * @return array lista de DiccionarioPersonalEntrada
     */
    function dpEntrada_GetEntradasByUserActualLetraInicial($letra, $offset = 0)
    {
        $limit = config('ctes.scrollEntradas');

        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];
        // Busco el diccionario del usuario actual
        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);
        if ($diccionario) {
            // $entradas = DiccionarioPersonalEntrada::WhereRaw('entrada like "ñ%" COLLATE utf8mb4_spanish_ci' )
            //  ->orderByRaw('entrada COLLATE utf8mb4_spanish_ci asc')->get()
            $entradas = DiccionarioPersonalEntrada::Where('entrada', 'like', $letra . '%')
                ->where('dic_personal_id', $diccionario->id)
                ->orderBy('entrada', 'asc')
                ->offset($offset)->limit($limit)->get();
        } else {
            return [];
        }

        return $entradas;
    }
}

if (!function_exists('dpEntrada_GetEntradasByUserActualConsulta')) {
    /**
     * Busca por titulo de entrada 
     * Busca Entradas del usuario actual segun la consulta "recuerda que este entrada es titulo de entrada"
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param String $consulta palabra a buscar
     * @param integer $offset a partir de que entrada empieza
     * @param integer $limit numero de entradas que muestra
     *
     * @return array lista de DiccionarioPersonalEntrada
     */
    function dpEntrada_GetEntradasByUserActualConsulta($consulta, $offset = 0, $listaCategorias=null)
    {
        // dd(
        //     'dpEntrada_GetEntradasByUserActualConsulta', 
        //     'consulta', $consulta, 
        //     'offset', $offset, 
        //     'listaCategorias', $listaCategorias
        // );
        
        $limit = config('ctes.scrollEntradas');
        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];
        // Busco el diccionario del usuario actual
        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);
        $entradas = [];

        // elimina espacios prnicipio y final
        $consulta = normalizarEntrada($consulta);
        
        // \DB::enableQueryLog(); // Enable query log
        if ($diccionario) {
            $entradas = DiccionarioPersonalEntrada::Where('entrada', 'like', '%' . $consulta . '%')
                ->where('dic_personal_id', $diccionario->id);

            if ($listaCategorias) {
                $entradas =$entradas->whereHas('dpAcepciones', function ($q) use ($listaCategorias) {
                    $q->whereHas('dpAcepcionTematicas', function ($q) use ($listaCategorias) {
                        $q->whereIn('tematica_id', $listaCategorias);
                    });
                });
            }
            
            $entradas =$entradas->orderBy('entrada', 'asc')
                ->offset($offset)->limit($limit)
                ->get();
        }
        // dd('log sqls', \DB::getQueryLog()); // Show results of log

        return $entradas;
    }
}

if (!function_exists('dpEntrada_GetEntradasByTematica')) {
/**
 * Obtener entradas del deccionario perosnal filtradas por categoria
 *
 * @param Array   $listaCategorias
 * @param integer $offset
 * @return collection Entradas
 */
function dpEntrada_GetEntradasByTematica( Array $listaCategorias, int $offset = 0, )
    {
        $limit = config('ctes.scrollEntradas');
        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];
        // Busco el diccionario del usuario actual
        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);
        $entradas = [];
        
        // \DB::enableQueryLog(); // Enable query log
        if ($diccionario) {
            $entradas = DiccionarioPersonalEntrada::where('dic_personal_id', $diccionario->id);
            if ($listaCategorias) {
                $entradas =$entradas->whereHas('dpAcepciones', function ($q) use ($listaCategorias) {
                    $q->whereHas('dpAcepcionTematicas', function ($q) use ($listaCategorias) {
                        $q->whereIn('tematica_id', $listaCategorias);
                    });
                });
            }            
            $entradas =$entradas->orderBy('entrada', 'asc')
                ->offset($offset)->limit($limit)
                ->get();
        }
        // dd('log sqls', \DB::getQueryLog()); // Show results of log

        return $entradas;
    }
}

/**
 * Agrega datos comunes a distintos controladores de cosultas
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param stdClass $datos Objeto de datos que se va a acutualizar
 *
 * @return void
 */
function dpEntrada_datosComunesCosultas(stdClass $datos)
{
    $datos->nentradas = dpEntrada_GetNumEntradasByUserActual();
    $datos->offset = config('ctes.scrollEntradas'); // apartir de que entrada empieza

    // Obtenemos el diccionario personal del usuario conectado
    $diccionario = dpDiccionario_GetDiccionarioByUserActual();
    $datos->diccionario = $diccionario;

    // Obtengo los diccionarios de aula a los que está unido el usuario actual
    $datos->listaDiccionariosAula = daGetDiccionariosAulaByUserConectado(config('ctes.estado_envio_habilitado.activo'));

    // Todas las tematicas/etiquetas/categorias:
    // $datos->listaTematicasDisponibles = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.tematica'));
    
    // Solo las etiquetas que están en el diccionario del usuario actual
    // $datos->listaTematicasDisponibles = DiccionarioPersonalAcepcionTematica::where('dpAcepcion.estado',1)->get();
    $tematicasActivas_ids = DiccionarioPersonalAcepcionTematica::all()
        // ->whereHas('dpAcepcion', function ($q) {$q->where('estado', config('ctes.estados_entrada.visible'));}) 
        // en el diccionario personal igual si no son visibles con que no esten borradas ya qu el usuario las ve todas        
        ->pluck('tematica_id')->toArray();
    $datos->listaTematicasDisponibles = CampoValor::where('mst_campo_entrada_id', '=', config('ctes.campos_acepcion.tematica'))
        ->whereIn('id', $tematicasActivas_ids)
        ->orderBy('descripcion', 'asc')
        ->distinct()
        ->get();
    // dd($datos->listaTematicasDisponibles);

    // Obtenemos el curso escolar actual
    $curso_escolar = getFormattedCursoEscolar(time());
    $datos->curso_escolar = $curso_escolar;

    // Obtenemos la descripción del centro del usuario conectado
    // if (isset(\Session::get('userData')['centros'][0])){
    //     $centro_denominacion = \Session::get('userData')['centros'][0]['denominacion'];
    //     $datos->centro_denominacion = $centro_denominacion;
    // }

    // $nivelEstudios = NivelEstudio::all();
    // $datos->nivelEstudios = $nivelEstudios;
    $datos->nivelEstudios = getAllNivelEstudios();
}

if (!function_exists('dpEntrada_ocultar')) {
    function dpEntrada_ocultar($entradaId, $src)
    {
        $entrada = DiccionarioPersonalEntrada::find($entradaId);
        $src->authorize('isOwner', $entrada);

        if ($entrada->estado == config('ctes.estados_entrada.visible')) {
            $entrada->estado = config('ctes.estados_entrada.oculta');
            // Oculta la entrada
            $entrada = dpEntrada_Edit($entrada);
        }
    }
}

if (!function_exists('dpEntrada_mostrar')) {
    function dpEntrada_mostrar($entradaId, $src)
    {
        $entrada = DiccionarioPersonalEntrada::find($entradaId);
        $src->authorize('isOwner', $entrada);

        if ($entrada->estado == config('ctes.estados_entrada.oculta')) {
            $entrada->estado = config('ctes.estados_entrada.visible');
            // Oculta la entrada
            $entrada = dpEntrada_Edit($entrada);
        }
    }
}

if (!function_exists('dpDiccionario_GetNumEntradasOcultas')) {
    /**
     * Numero de entradas ocultas en un diccionario personal
     *
     * @param integer $diccionarioId
     * @return void
     */
    function dpDiccionario_GetNumEntradasOcultas(int $diccionarioId)
    {
        $numEntradas = DiccionarioPersonalEntrada::where('dic_personal_id', $diccionarioId)
            ->where('estado', config('ctes.estados_entrada.oculta'))
            ->count();

        return $numEntradas;
    }
}