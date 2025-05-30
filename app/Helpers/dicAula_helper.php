<?php

use Carbon\Carbon;
use App\Models\DicAula;
use App\Models\DicAulaCampo;
use App\Models\DicAulaPauta;
use App\Models\CampoEntrada;
use App\Models\CampoValor;
use App\Models\DicAulaDestinarioAviso;
use App\Models\DicAulaParticipante;
use App\Models\DicAulaEntrada;
use App\Models\EnvioEntrada;
use App\Models\TipoDiccionarioAula;
use App\Models\ComentarioEntrada;
use App\Models\EnvioAcepcion;
use App\Models\EnvioAcepcionTematica;
use App\Models\Persona;
use App\Models\UserPersona;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;
use Mockery\Undefined;
use PhpParser\Node\Expr\Throw_;

if (!function_exists('createOrUpdateDicAula')) {
    /**
     *   Crea un diccionario de aula
     *   @access public
     *   @param Request $request
     *   @return String
     *   @version 0.0.1
     */
    function createOrUpdateDicAula($request, $dicAulaId = null)
    {
        // $ESTADO_ACTIVO = config('ctes.estados.activo');
        // $ESTADO_INACTIVO = config('ctes.estados.inactivo');
        // $MULTI_ENSENANZA = config('ctes.multi.multienseñanza');
        // $MULTI_AREA_MATERIA = config('ctes.multi.multiareamateria');
        // $MULTI_ESTUDIO = config('ctes.multi.multiestudio');
        // $MULTI_GRUPO = config('ctes.multi.multigrupo');
        // $ESTUDIO_FINAL = config('ctes.multi.estudiofinal');

        $user = session('userData');
        $createDic = daCreateOrUpdateDicAulaByPersonaId( $user['persona']['id'] , $request->all(), $dicAulaId  );

        if ( $createDic instanceof DicAula ){
            return true;
        } else {
            return $createDic;
        }
    }
}

function daCreateOrUpdateDicAulaByPersonaId( $persona_id, $params, $dicAulaId = null )
{
    // if( array_key_exists('grupoLetra', $params) ){
    //     if( $params['grupoLetra'] == '0') {
    //         $grupoFinal = '';
    //     } else {
    //         $grupoFinal = $params['grupoLetra'];
    //     }
    // }
    $codigo = $params['codigoAleatorio'];

    try {

        $diccionario = DicAula::updateOrCreate([
            'id' => $dicAulaId
        ], [
            'persona_id'            => $persona_id,
            'titulo'                => $params['nombreDiccionario'],
            'descripcion'           => $params['descripcionDiccionario'],
            'mst_tipo_dic_id'       => $params['tipoDiccionario']?? 1,
            'letra_grupo'           => $params['grupoLetra']?? 0,
            'mst_nivel_estudios_id' => $params['estudio']?: null,
            'mst_area_materia_id'   => $params['areaMateria']?? 1,
            'ano_ini_curso_escolar' => $params['ano_ini_curso_escolar'],
            'max_acepciones_entrada' => isset($params['maxAcepciones']) ?? config('ctes.dic_aula.max_acepciones'),
            'visible_estudiante'    => $params['visibilidad'],
            'envios_habilitados'    => $params['habilitarEnvio'],
            'envios_fecha_ini'      => isset($params['envioFecha']) ? $params['envioFecha'] : null,
            'envios_fecha_fin'      => isset($params['envioFecha']) ? Carbon::createFromTimestamp(time())->toDateTimeString() : null,
            'codigo'                => $codigo,
            'comentarios_visibles'  => $params['visibilidadComentarios'],
            'comentarios_visibles_anteriores_a' => isset($params['comentariosVisibleFecha']) ? $params['comentariosVisibleFecha'] : null,
            'estado'                => config('ctes.estados.activo'),
            'vigencia'              => $params['vigencia'],
        ]);
        foreach (CampoEntrada::all() as $mst_campos_entrada) {
            $dic_aula_campos = DicAulaCampo::updateOrCreate([
                'dic_aula_id' => $diccionario->id,
                'mst_campo_entrada_id' => $mst_campos_entrada->id
            ], [
                'visible' => isset($params[$mst_campos_entrada->id . '_visible']) ? 1 : 0,
                'obligatorio' => isset($params[$mst_campos_entrada->id . '_obligatorio']) ? 1 : 0,
                'estado' => config('ctes.estados.activo')
            ]);
        }

        try {
            //crear pautas
            $paramsAulaPauta = [
                'texto' => $params['pautasEspecificas'],
                'estado' => config('ctes.estados.activo')
            ];
            $dic_aula_pauta = DicAulaPauta::updateOrCreate([
                'dic_aula_id' => $diccionario->id
            ], $paramsAulaPauta );
            customLoggin(
                config('ctes.log_levels.info'),
                config('ctes.log_types.info'),
                ['file' => __FILE__, 'line' => __FILE__ ],
                PHP_EOL . 'dic_aula_id: ' .$diccionario->id .
                PHP_EOL . 'parametros: ' .json_encode($dic_aula_pauta, JSON_PRETTY_PRINT)
            );
        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_base_error'),
                ['file' => $th->getFile(), 'line' => $th->getLine()],
                PHP_EOL . 'dic_aula_id: ' .$diccionario->id .
                PHP_EOL . 'parametros: ' .json_encode($paramsAulaPauta, JSON_PRETTY_PRINT),
                $th->getMessage()
            );
        }

        daDiccionarioAulaUnirUsuario( $diccionario, $persona_id);

        if (isset($params['importar'])) {
            if ($params['importar'] != '0'  && isset($params['importarRadio']) && $params['importarRadio'] != 'undefined') {
                $idImportar = $params['importarRadio'];
                $idImportar = str_replace('chk_', '', $idImportar);
                $datosImportar = DicAulaEntrada::where('dic_aula_id', $idImportar)->get();
                foreach ($datosImportar as $aImportar) {
                    DicAulaEntrada::create([
                        'dic_aula_id' => $diccionario->id,
                        'envio_entrada_id' => $aImportar->envio_entrada_id,
                        'origen' => $aImportar->origen,
                        'estado' => $aImportar->estado
                    ]);
                }
            }
        }

        return $diccionario;
    } catch (\Throwable $th) {

        customLoggin(
            config('ctes.log_levels.error'),
            config('ctes.log_types.data_base_error'),
            ['file' => $th->getFile(), 'line' => $th->getLine()],
            PHP_EOL . json_encode($params, JSON_PRETTY_PRINT),
            $th->getMessage()
        );

        return response(['error' => $th->getMessage()], 404);
    }

}


if (!function_exists('daGetDiccionariosAulaByPersonaId')) {
    /**
     * Devuelve la lista de diccionarios de aula a los que está unida la persona recibida
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param integer $persona_id
     *
     * @return array lista de DicAula
     */
    function daGetDiccionariosAulaByPersonaId($persona_id)
    {
        $diccionarios_aula = DicAula::where('persona_id', $persona_id)
            ->where('estado', config('ctes.estados.activo'))
            ->orderBy('descripcion', 'asc')
            ->get();

        return $diccionarios_aula;
    }
}

if (!function_exists('daGetDiccionariosAulaByUserConectado')) {
    /**
     * Devuelve la lista de diccionarios de aula a los que está unido el usuario actual
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param integer $estado_envio_habilitado constante perteneciente a ctes.estado_envio_habilitado
     *
     * @return array lista de DicAula
     */
    function daGetDiccionariosAulaByUserConectado($estado_envio_habilitado = null)
    {
        // Obtenemos el id de persona del usuario conectado
        try {
            $persona_id = getSessionPersona()['id'];
        } catch (\Throwable $th) {
            throw $th;
            // return redirect('cas.logout')->with('error', 'No se pudo obtener el usuario conectado');
        }

        $diccionarios_aula = daGetDiccionariosAulaConectadosByPersonaId($persona_id, $estado_envio_habilitado);

        return $diccionarios_aula;
    }
}

if (!function_exists('daConectadosByPersonaId')) {
/**
 * Devuelve el _query_ de diccionarios conectados a la persona
 * Solo los diccionarios actuales (del curso actual) o atemporales
 * hace falta ->get() para obtener los resultados y se pueden aplicar
 * mas filtros
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param [type] $persona_id
 *
 * @return void
 */
function daConectadosByPersonaId($persona_id)
{
    // Solo diccionarios de aula del curso actual
    $anoEscolarActual = getAnoIniCursoEscolar( new Datetime() );
    // $diccionariosCursoActual = DicAula::where('ano_ini_curso_escolar', $anoEscolarActual );

    // Diccionarios a los que la persona esta conectada :
    $diccionarios = DicAula::whereHas('participantes',
        function ($query) use ($persona_id) {
            $query
                ->where('persona_id', '=', $persona_id)
                ->where('estado', '=', config('ctes.estados.activo'))
            ;
        }
    )->where('estado', config('ctes.estados.activo'));

    // Muestra diccionarios cursoActual o atemporales
    $diccionariosCursoActual = $diccionarios
        ->where(function ($q) use ($anoEscolarActual) {
            return $q
                ->where('ano_ini_curso_escolar', $anoEscolarActual )
                ->orWhereHas( 'atemporal', function ($query){
                    $dayAfter = (new DateTime())->modify('+1 day')->format('Y-m-d');
                    $dayAfter =
                    $query
                        ->where('estado', '=', config('ctes.estados.activo'))
                        ->whereDate('hasta', '<', $dayAfter )->orWhereNull('hasta');
                })
                // o vigentes
                ->orWhere( queryVigencia($anoEscolarActual) )
            ;
        });
    
    return $diccionariosCursoActual;
}
}

if (!function_exists('daGetDiccionariosAulaConectadosByPersonaId')) {
    /**
     * Devuelve la lista de Diccionarios de Aula a los que está conectada una persona y que esté como participante activo
     *  Si recibe estado_envio_habilitado a activo, devuelve los que están con el período de envíar el diccionario activo
     *  Si lo recibe a inactivo devuelve los que tienen el período de enviar a inactivo
     *  Si lo recibe a planificado devuelve los que tienen el período de enviar activo, pero no ha llegado la fecha de activación
     *  Si lo recibe a NULL devuelve los diccionarios conectados sin tener en cuenta el estado del envío
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param integer $dic_aula_id
     * @param integer $estado_envio_habilitado constante perteneciente a ctes.estado_envio_habilitado
     *
     * @return array lista de DicAula
     */
    function daGetDiccionariosAulaConectadosByPersonaId($persona_id, $estado_envio_habilitado = null)
    {
        $diccionariosCursoActual = daConectadosByPersonaId($persona_id);

        // No compruebo nada del estado del envío
        if (is_null($estado_envio_habilitado)) {
            $diccionarios_aula = $diccionariosCursoActual
                ->orderBy('titulo')
                ->get();
        } else {
            switch ($estado_envio_habilitado) {

                case config('ctes.estado_envio_habilitado.inactivo'): // El envío debe estar inactivo
                    $diccionarios_aula = $diccionariosCursoActual
                        ->where('envios_habilitados', '=', config('ctes.estados.inactivo'))
                        // ->where('estado', '=', config('ctes.estados.activo')) // ya esta puesto arriba lo de activo
                        ->orderBy('titulo')
                        ->get();
                    break;

                case config('ctes.estado_envio_habilitado.planificado'):
                    $diccionarios_aula = $diccionariosCursoActual
                        ->where('envios_habilitados', '=', config('ctes.estados.activo'))
                        ->where('envios_fecha_ini', '>', new DateTime())
                        ->orderBy('titulo')
                        ->get();
                    break;

                case config('ctes.estado_envio_habilitado.activo'): // El envío debe estar activo
                default:

                    $diccionarios_aula =
                    $diccionariosCursoActual
                        ->where('envios_habilitados', '=', config('ctes.estados.activo'))
                        ->where(function ($q) {
                            return $q
                                ->whereNull('envios_fecha_ini')
                                ->orWhere('envios_fecha_ini', '<=', new DateTime());
                        })
                        ->orderBy('titulo')
                        ->get();
                    break;
            }
        }

        return $diccionarios_aula;
    }
}

if (!function_exists('daGetDiccionarioUltimoUnido')) {
/**
 * Obtener el ultimo diccionario al que se ha unido el usuario
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param [type] $persona_id
 *
 * @return void
 */
function daGetDiccionarioUltimoUnido($persona_id)
{
    $diccionariosCursoActual = daConectadosByPersonaId($persona_id);

    return $diccionariosCursoActual->first();
}
}

if (!function_exists('daGetDiccionariosAulaConectadosNoActivosByPersonaId')) {
    /**
     * Undocumented function
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param [number] $persona_id
     *
     * @return [Collection<DicAula>]
     */
    function daGetDiccionariosAulaConectadosNoActivosByPersonaId($persona_id)
    {
        $diccionarios_aula = DicAula::whereHas('participantes',
                    function ($query) use ($persona_id) {
                        $query
                            ->where('persona_id', '=', $persona_id)
                            ->where('estado', '=', config('ctes.estados.inactivo'))
                        ;
                    })
                ->where('estado', '=', config('ctes.estados.activo'))
                ->orderBy('titulo')
                ->get();

        return $diccionarios_aula;
    }
}

if (!function_exists('daGetDiccionarioAulaById')) {
    /**
     * Devuelve el diccionario de aula por ID
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param integer $diccionario_id
     *
     * @return object DicAula
     */
    function daGetDiccionarioAulaById($diccionario_id)
    {
        $diccionario_aula = DicAula::find($diccionario_id);

        return $diccionario_aula;
    }
}

if (!function_exists('daGetDiccionarioAulaByCodigo')) {
    /**
     * Devuelve el diccionario de aula por Codigo completo
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param string $codigo Código compuesto por 2 caracteres del curso, 1 caracter del grupo y 4 caracteres del código del profe
     *
     * @return object DicAula
     */
    function daGetDiccionarioAulaByCodigo($codigo)
    {
        $diccionario_aula = DicAula::where('codigo', '=', $codigo)
            ->where('estado', config('ctes.estados.activo'))
            ->first();

        return $diccionario_aula;
    }
}

if (!function_exists('daDiccionarioAulaUnirUsuario')) {
    /**
     * Crea la asociación del usuario al diccionario que se reciben por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param object $diccionarioAula
     * @param integer $persona_id
     *
     * @return object DicAulaParticipante
     */
    function daDiccionarioAulaUnirUsuario($diccionarioAula, $persona_id, ?bool $fromUnirse = false)
    {
        // Obtenemos el rol del usuario
        $up = UserPersona::where('persona_id',$persona_id)->get()->first();
        $role_id = User::find($up->user_id)->role_id;
        if($fromUnirse) {
            $role_id = (int) config('ctes.rol.alumno');
        }

        $participanteYaExiste = DicAulaParticipante::
            where( 'persona_id', $persona_id )
            ->where( 'dic_aula_id', $diccionarioAula->id )
            ->get();

        if ( $participanteYaExiste->count() == 0 ) {
            $dicAulaParticipante = new DicAulaParticipante;
            $dicAulaParticipante->dic_aula_id = $diccionarioAula->id;
            $dicAulaParticipante->persona_id = $persona_id;
            $dicAulaParticipante->rol_diccionario_id = $role_id;
            $dicAulaParticipante->estado = config('ctes.estados.activo');
            $grabado = $dicAulaParticipante->save();
        } else {
            $dicAulaParticipante = $participanteYaExiste->first();
        }

        return $dicAulaParticipante;
    }
}

if (!function_exists('daDiccionarioAulaUnirUsuarioActual')) {
    /**
     * Crea la asociación del usuario actual al diccionario que se recibe por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param object $diccionarioAula
     *
     * @return object DicAulaParticipante
     */
    function daDiccionarioAulaUnirUsuarioActual($diccionarioAula)
    {
        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];

        $dicAulaParticipante = daDiccionarioAulaUnirUsuario($diccionarioAula, $persona_id, true);

        return $dicAulaParticipante;
    }
}

if (!function_exists('daDiccionarioAulaSelDiccionarioActivo')) {
    /**
     * Selecionar diccionario activo y guardarlo en la session
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param integer $id
     *
     * @return void
     */
    function daDiccionarioAulaSelDiccionarioActivo($id)
    {
        $dicAulaActivo = DicAula::find($id);
        // return dd($dicAulaActivo);
        if ($dicAulaActivo) {
            \Session(['userData.dicAulaActivo' => $dicAulaActivo]);
            return [
                'diccionario' => $dicAulaActivo->id
            ];
        } else {
            return response()->json(['error' => 'Error msg'], 404);
        }
    }
}

if (!function_exists('daDiccionarioAulaComprobarUsuario')) {
    /**
     * Compruebo si el usuario recibido está unido a un diccionario de aula
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param object $diccionarioAula
     * @param integer $persona_id
     *
     * @return boolean estaUnido Indica si el usuario recibido está unido al diccionario de aula recibido
     */
    function daDiccionarioAulaComprobarUsuario($diccionarioAula, $persona_id)
    {
        $estaUnido = DicAulaParticipante::where('persona_id', $persona_id)
            ->where('dic_aula_id', $diccionarioAula->id)
            ->exists();

        return $estaUnido;
    }
}

if (!function_exists('daDiccionarioAulaComprobarUsuarioActual')) {
    /**
     * Compruebo si el usuario actual está unido a un diccionario de aula
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param object $diccionarioAula
     *
     * @return boolean estaUnido Indica si el usuario actual está unido al diccionario de aula recibido
     */
    function daDiccionarioAulaComprobarUsuarioActual($diccionarioAula)
    {
        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];

        $estaUnido = daDiccionarioAulaComprobarUsuario($diccionarioAula, $persona_id);

        return $estaUnido;
    }
}

// if (!function_exists('comprobarPublicarEntrada')) {
//     /**
//      * Comprueba si la entrada se puede publicar la entrada en el diccionario de aula
//      * Nunca publica la entrada
//      *
//      * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
//      * @version 1.0.0
//      *
//      * @param EnvioEntrada $entrada
//      * @param DicAula $dicAula
//      *
//      * @return void
//      */
//     public function comprobarPublicarEntrada(EnvioEntrada $entrada, DicAula $dicAula )
//     {
//         // esta duplicada?
//         $tituloYaPublicada = comprobarEntradaMismoTituloYaPubilicada($entrada, $dicAula);

//     }
// }

if (!function_exists('comprobarPublicarListadoEntradas')) {
/**
 * Comprubea que es posbile publicar las entrada antes de intentarlo
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param Collection<EnvioEntradas> $envioEntradas EnviosEntradas Asegurate que venga ya con el ->get()
 * @param Integer $dicAulaId
 *
 * @return Object.ok
 * @return Object.okIds Ids de entradas que se van a duplicar
 * @return Object.okTitulos Titulos de entradas que se van a publicar
 * @return Object->error->yaPublicadas Array de EnvioEntrada ya publicadas
 * @return Object->error->yaPublicadasTitulo Array de string con los titulo ya publicados
 * @return Object->error->duplicadasEnListado Array de EnvioEntrada que se intentan enviar varias veces
 * @return Object->error->duplicadasEnListadoTitulo Array des string con titulos
 */
    function comprobarPublicarListadoEntradas( $envioEntradas, $dicAulaId )
    {
        $dicAula = DicAula::find($dicAulaId);
        $noPublicables = [];
        $entradasTituloYaPublicada = [];
        $tituloYaPublictadas= [] ;
        $entradasPasan = [];
        $titulos = [];
        $titulosEntradasPasan = [];
        $duplicadasEnListado = [];
        $entradasOkFinal = [];

        foreach ($envioEntradas as $envioEntrada ) {

            $titulos[] = $envioEntrada->entrada;
            if ( comprobarEntradaMismoTituloYaPubilicada($envioEntrada, $dicAula) ){
                $noPublicables[] = $envioEntrada;
                $entradasTituloYaPublicada[] = $envioEntrada;
                $tituloYaPublictadas[] = $envioEntrada->entrada;
            } else {
                $entradasPasan[] = $envioEntrada;
                $titulosEntradasPasan[] = $envioEntrada->entrada;
            }
        }

        // si dentro del listado de entradas hay duplicados, distitntas versiones de la misma palabra mandadas por el mismo usuario o por varios se
        // $titulosEntradasPasan = array_map(function($x) { return $x->entrada; }, $entradasPasan );
        // comparo el array con duplicados con el array sin duplicados y la diferencia son los duplicados :
        $titulos_duplicados = array_diff_assoc($titulosEntradasPasan, array_unique($titulosEntradasPasan));
        // Elimino los duplicados que estan mas de una vez
        // $titulos_duplicados = array_unique($titulos_duplicados);
        // NEOedyú
        // dd(
        //     'entadas count', $envioEntradas->count(),
        //     'entradas', $envioEntradas,
        //     'entradas titulos', $titulos,
        //     'ya publicadas count:', count($entradasTituloYaPublicada),
        //     // 'ya publicadas:', $entradasTituloYaPublicada,
        //     'ya publicadas:', $tituloYaPublictadas,
        //     'titulos entradas pasan', $titulosEntradasPasan,
        //     array_unique($titulosEntradasPasan),
        //     'titulos duplicados de las que pasan:', $titulos_duplicados
        // );
        $entradasOkFinalTitulo = [];
        $duplicadasEnListadoTitulo = [];
        $entradasOkIds=[];
        foreach ($entradasPasan as $ee) {
            // eliminamos los titulos duplicados de $entradaspasan  y

            if ( in_array($ee->entrada, $titulos_duplicados) ) {
                $duplicadasEnListado[] = $ee;
                // $duplicadasEnListadoTitulo[] = $ee->entrada;
            } else {
                $entradasOkFinal[] = $ee;
                $entradasOkFinalTitulo[] = $ee->entrada;
                $entradasOkIds[] = $ee->id;
            }

        }

        // devuelve
        // {
        //     'publicables'=> $entradas,
        //     'error'=> {
        //         'yaPublicadas': $entradasTituloYaPublicada,
        //         'duplicadasEnListado': $duplicadasEnListado
        //     }
        // }
        $return = new \stdClass();
        $return->ok = $entradasOkFinal;
        $return->okTitulos = $entradasOkFinalTitulo;
        $return->okIds = $entradasOkIds;
        $return->error = new \stdClass();
        $return->error->yaPublicadas = $entradasTituloYaPublicada;
        $return->error->yaPublicadasTitulo = $tituloYaPublictadas;
        $return->error->duplicadasEnListado = $duplicadasEnListado;
        $return->error->duplicadasEnListadoTitulo = $titulos_duplicados;

        // dd( $return );

        // devuelve
        return $return;
    }
}

if (!function_exists('comprobarEntradaMismoTituloYaPubilicada')) {
function comprobarEntradaMismoTituloYaPubilicada(EnvioEntrada $envioEntrada, DicAula $dicAula )
{
    $dicAulaId = $dicAula->id;
    $duplicados = $envioEntrada::where('entrada', $envioEntrada->entrada)
        ->whereHas('publicadas', function ($query) use ($dicAulaId) {
            $query->where('estado', config('ctes.estados.activo'))->where('dic_aula_id', $dicAulaId);
        })->get();
    return (sizeof($duplicados) > 0);
}
}

if (!function_exists('publicarEntradaSimple')) {
/**
 * Version de publicar entrada que no devuelve una vista
 * solo devuelve verdadero o msg error
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param [type] $request
 * @param [type] $dicAulaId
 * @param [type] $entradaId
 *
 * @return void
 */
function publicarEntradaSimple($request, $dicAulaId, $entradaId)
    {
        $envioEntrada = EnvioEntrada::find($entradaId);
        $diccionario = DicAula::find($dicAulaId);

        if (is_null($envioEntrada)) {
            return (object)[
                'codeType' => 'error',
                'message' => 'Entrada no encontrada'
            ];
        }
        if (is_null($diccionario)) {
            return (object)[
                'codeType' => 'error',
                'message' => 'Diccionario no encontrado'
            ];
        } else {
            $tituloYaPublicada = comprobarEntradaMismoTituloYaPubilicada($envioEntrada, $diccionario);
            if ($tituloYaPublicada) {
                return (object)[
                    'codeType' => 'error',
                    'message' => 'Error: entrada duplicada'
                ];
            }
            $envioEntrada->estado = config('ctes.estados_envios.publicado');
            $saved = $envioEntrada->save();
            if ($saved) {
                $dicAulaEntrada = DicAulaEntrada::updateOrCreate(
                    [
                    'dic_aula_id' => $dicAulaId,
                    'envio_entrada_id' => $entradaId
                    ], [
                    'origen' => config('ctes.origen.envio'),
                    'estado' => config('ctes.estados.activo')
                    ]
                );
                if (isset($dicAulaEntrada)) {
                    return (object)[ 'codeType' => 'success'];
                } else {
                    return (object)[
                        'codeType' => 'error',
                        'message' => 'La entrada "'.$envioEntrada->dpEntrada->entrada.'" no se ha podido publicar'
                    ];
                }
            } else {
                return (object)[
                    'codeType' => 'error',
                    'message' => 'La entrada "'.$envioEntrada->dpEntrada->entrada.'" no se ha podido publicar'
                ];
            }
        }
    }
}

if (!function_exists('publicarEntrada')) {
    /**
     * Publica una entrada a un diccionario de aula
     *
     * @author Javier Pérez Batista <javier.perez@altia.es>
     * @version 1.0.0
     * @param  [string] $dicAulaId Indica el id del diccionario de aula
     * @param  [string] $entradaId
     * @return view|Illuminate\Http\JsonResponse 
     */
    function publicarEntrada($request, $dicAulaId, $entradaId)
    {
        $envioEntrada = EnvioEntrada::find($entradaId);
        $diccionario = DicAula::find($dicAulaId);
        $action = $request->all()['action'];
        if (is_null($envioEntrada)) {
            $errors = (object) [
                'msg' => 'Entrada no encontrada'
            ];
            return view('layouts.partials.alerts.errors')
                ->with('errors', collect($errors));
        } else if (is_null($diccionario)) {
            $errors = (object) [
                'msg' => 'Diccionario no encontrado'
            ];
            return view('layouts.partials.alerts.errors')
                ->with('errors', collect($errors));
        } else {
            if ($action == 'publicar') {
                // $duplicados = $envioEntrada::where('entrada', $envioEntrada->entrada)
                // ->whereHas('publicadas', function ($query) use ($dicAulaId) {
                //     $query->where('estado', config('ctes.estados.activo'))->where('dic_aula_id', $dicAulaId);
                // })->get();
                $tituloYaPublicada = comprobarEntradaMismoTituloYaPubilicada($envioEntrada, $diccionario);
                if ($tituloYaPublicada) {
                    return response()->json([
                        'view' => strval(view('layouts.partials.components.botones.entrada.publicar')->with(
                            [
                            'entrada' =>$envioEntrada,
                            'diccionario' => $diccionario,
                            'estudiante' => $envioEntrada->dpEnvio->dpDiccionario->persona
                            ]
                        )),
                        'codeType'  => 'error',
                        'message'   => __('diccionario.pubilcarEntrada_ya_publicada')
                        ]
                    );
                }
                $envioEntrada->estado = config('ctes.estados_envios.publicado');
                $saved = $envioEntrada->save();
                if ($saved) {
                    $dicAulaEntrada = DicAulaEntrada::updateOrCreate(
                        [
                        'dic_aula_id' => $dicAulaId,
                        'envio_entrada_id' => $entradaId
                        ], [
                        'origen' => config('ctes.origen.envio'),
                        'estado' => config('ctes.estados.activo')
                        ]
                    );
                    if (isset($dicAulaEntrada)) {
                        return response()->json([
                            'view' => strval(view('layouts.partials.components.botones.entrada.publicar')->with(
                                [
                                    'entrada' =>$envioEntrada,
                                    'diccionario' => $diccionario,
                                    'estudiante' => $envioEntrada->dpEnvio->dpDiccionario->persona
                                ]
                            )),
                            'codeType' => 'success']
                        );
                    } else {
                        $errors = (object) [
                            'msg' => 'La entrada "'.$envioEntrada->dpEntrada->entrada.'" no se ha podido publicar'
                        ];
                        return view('layouts.partials.alerts.errors')->with('errors', collect($errors));
                    }
                } else {
                    $errors = (object) [
                        'msg' => 'La entrada "'.$envioEntrada->dpEntrada->entrada.'" no se ha podido publicar'
                    ];
                    return view('layouts.partials.alerts.errors')->with('errors', collect($errors));
                }
            } else {
                // anular entrada
                return despublicarEntrada( $dicAulaId, $entradaId);
            }
        }
    }
}

/**
 * Anula una entrada que ya esta publicada
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.1
 *
 * @param [integer] $dicAulaId
 * @param [integer] $entradaId
 *
 * @return void
 */
function despublicarEntrada( $dicAulaId, $entradaId) {
    $envioEntrada = EnvioEntrada::find($entradaId);
    $diccionario = DicAula::find($dicAulaId);
    $dicAulaEntrada = DicAulaEntrada::where('dic_aula_id', $dicAulaId)->where('envio_entrada_id', $entradaId)->first();
    if (isset($dicAulaEntrada)) {
        try {
            $dicAulaEntrada->delete();
            $envioEntrada = EnvioEntrada::find($entradaId);
            $envioEntrada->estado
                =   $envioEntrada->estado == config('ctes.estados_entrada.oculta')?
                    config('ctes.estados_entrada.visible'):
                    config('ctes.estados_entrada.oculta');
            $envioEntrada->save();
            // Cambiamos el estado de oculta <==> visible
            if ($envioEntrada->estado == config('ctes.estados_entrada.visible')) {
                $mensaje =  __('diccionario.entrada_visible');
            } else {
                $mensaje =  __('diccionario.entrada_oculta');
            }

            return response()->json([
                'view' => strval(view('layouts.partials.components.botones.entrada.publicar')->with(
                    [
                        'entrada' =>$envioEntrada,
                        'diccionario' => $diccionario,
                        'estudiante' => $envioEntrada->dpEnvio->dpDiccionario->persona
                    ]
                )),
                'codeType' => 'success']
            );
        } catch(Exception $e) {
            $mensaje =  __('diccionario.URLmala');
            customLoggin(
                config('ctes.log_levels.info'),
                config('ctes.log_types.info'),
                ['file' => __FILE__, 'line' => __LINE__],
                PHP_EOL. "parametros: " .
                PHP_EOL. "\$dicAulaId = $dicAulaId ," .
                PHP_EOL. "\$entradaId = $entradaId ,"
                ,
                'Error al despublicar entrada' .
                PHP_EOL . $e->getMessage()
            );
            return redirect()->back()->withErrors([$mensaje]);
        }
    } else {
        $errors = (object) [
            'msg' => 'Entrada no encontrada'
        ];
        return view('layouts.partials.alerts.errors')
            ->with('errors', collect($errors));
    }
}

/**
 * Elimina el envío de una entrada que no está publicada
 *
 * @author Natalia Moreira <natalia.moreira@altia.es>
 * @version 1.0.1
 * @param [integer] $dicAulaId
 * @param [integer] $entradaId
 *
 * @return void
 */
if (!function_exists('eliminarEnvioEntrada')) {

    function eliminarEnvioEntrada($dicAulaId, $entradaId){
        $envioEntrada = EnvioEntrada::find($entradaId);
        $diccionario = DicAula::find($dicAulaId);

        if (is_null($envioEntrada)) {
            $errors = (object) [
                'msg' => 'Entrada no encontrada'
            ];
            return view('layouts.partials.alerts.errors')
                ->with('errors', collect($errors));
        } else if (is_null($diccionario)) {
            $errors = (object) [
                'msg' => 'Diccionario no encontrado'
            ];
            return view('layouts.partials.alerts.errors')
                ->with('errors', collect($errors));
        } else {
            if ($envioEntrada->publicada) {
                $errors = (object) [
                    'msg' => 'No se puede eliminar un envío de entrada publicado.'
                ];
                return view('layouts.partials.alerts.errors')
                    ->with('errors', collect($errors));
            }
            // Eliminar los registros físicamente relacionados en cascada
            if(isset($envioEntrada)){
                foreach ($envioEntrada->envioAcepciones as $envioAcepcion){
                    $envioAcepcion->envioAcepcionTematicas()->forceDelete();
                    foreach ($envioAcepcion->envioAcepcionesMedios as $envioAcepcionMedio){
                        $envioAcepcionMedio->forceDelete();
                    }
                    $envioAcepcion->forceDelete();
                }
                $envioEntrada->forceDelete();
            }
    
            return response()->json([
                'message' => 'El envío de entrada ha sido eliminado.',
                'codeType' => 'success'
            ]);
    
        }
         
    }
}

// if (!function_exists('getComentariosEntradaByAulaDicByEntrada')) {
//     /**
//      *   Devuelve una coleccción con los comentarios_entrada del diccionario y la entrada recibidos por parámetros
//      *
//      *   @access public
//      *   @param Request $request
//      *   @return String
//      *   @version 0.0.1
//      */
//     function getComentariosEntradaByAulaDicByEntrada($diccionarioAula, $dp_entrada_id)
//     {
//         $comentariosEntrada = new Collection();

//         $enviosEntrada_inicial = $diccionarioAula->enviosEntradaByEntrada($dp_entrada_id)->get();
//         foreach ($enviosEntrada_inicial as $envioEntrada) {
//             $comentariosEntradaDiccionario = $envioEntrada->comentariosEntrada()->get();
//             $comentariosEntrada = $comentariosEntrada->merge($comentariosEntradaDiccionario);
//         }

//         return $comentariosEntrada;
//     }
// }


if (!function_exists('getEntradasConsultaDicAula')) {
    /**
     * Obtener las entradas del diccionaro de aula para el listado busquedas/consultas
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param integer $dicAulaId Diccionario que se esta consultando
     * @param integer $offset Cargar a apartir de este numero de entradas
     * @param integer $limit Maximo numero de entradas
     *
     * @return void
     */
    function getEntradasConsultaDicAula( $dicAulaId, $campos=null, $offset = 0, $limit = null )
    {
        if ( is_null( $limit)  ) {
            $limit = config('ctes.scrollEntradas');
        }

        $entradas = peticionEntradasAula($dicAulaId, $campos);
        if ($offset>0 ) {
            $entradas->offset($offset);
        }
        if ( $limit > 0 ){
            $entradas->limit($limit);
        }

        return $entradas->get();;
    }
}
if (!function_exists('peticionEntradasAula')) {
    /**
     * Peticion de entrada a la base de datos que se utiliza en
     * getEntradasConsultaDicAula, getEntradasAulaByInitial y
     * getEntradasAulaByConsulta
     *
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param integer $dicAulaId
     *
     * @return colletion de EnvioEntradas donde el estado es publicada y es visible
     */
    function peticionEntradasAula($dicAulaId, $campos=null)
    {
        $entradas = EnvioEntrada::where('dic_aula_id', $dicAulaId)
            ->join('dic_aula_entradas','envio_entrada_id','=','envios_entradas.id');
        
        if (isset($campos)){
            // dd($campos);
            $campos_ee = [];
            foreach( $campos as $i => $c ) {
                $campos_ee[]= 'envios_entradas.'.$c;
            }
            $entradas = $entradas
                ->select( $campos_ee, 'dic_aula_entradas.estado as estado_entrada' );
        } else {
            $entradas = $entradas
                ->select( 'envios_entradas.*', 'dic_aula_entradas.estado as estado_entrada' );
        }
        $entradas = $entradas
            ->where('envios_entradas.estado', 3) // publicada
            ->where('dic_aula_entradas.estado', 1) // visible
            ->orderBy('entrada','asc');

        return $entradas;
    }
}

if (!function_exists('getNumEntradasDicAula')) {
    /**
     * Obtener numero de entradas del diccionario de aula
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param [type] $dicAula
     *
     * @return void
     */
    function getNumEntradasDicAula( $dicAulaId )
    {
        $entradas = peticionEntradasAula($dicAulaId)->count();
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
    function datosComunesConsultasDicAula($datos, $dicAula)
    {
        // remplaza el enlace de nentradas
        $datos->route_nentradas = 'aula.consulta.all';

        if ( $dicAula )
            $datos->nentradas = getNumEntradasDicAula( $dicAula->id );
        else
            $datos->nentradas = 0 ;

        $datos->offset = config('ctes.scrollEntradas'); // apartir de que entrada empieza
        $datos->tabs = getDatosTabs(true);

        $datos->camposEntradas = CampoEntrada::all();
        $datos->tipoDiccionarioAulas = TipoDiccionarioAula::all();

        $datos->estados = config('ctes.estados');


        // todas las tematicas/etiquetas
        $datos->listaTematicasDisponibles = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.tematica'));

        // $entradas = EnvioEntrada::where('dic_aula_id', $dicAula->id)
        //           ->join('dic_aula_entradas','envio_entrada_id','=','envios_entradas.id');        
        // $entradas_ids = $entradas->pluck('envios_entradas.id')->toArray();
        // dd($entradas_ids);
        // Todos los EnvioAcepcionTematica del diccionario de aula
        if($dicAula){

            $envioEntrada = EnvioEntrada::where('envios_entradas.estado',config('ctes.estado_envios.publicado'))
                ->join('dic_aula_entradas','envio_entrada_id','=','envios_entradas.id')
                ->where('dic_aula_id', $dicAula->id)
                // ->whereIn('dp_entrada_id', $entradas_ids);
            ;
        }
        // dd($envioEntrada->get());
        // ->pluck('envios_entradas.id')->toArray()
        // EnvioAcepcionTematica::

        // $tematicasActivas_ids = $dicAula->tematicas()->pluck('tematica_id')->toArray();
        // ::whereHas('diccionarioPersonalAcepcion', function($query) use ($dicAula) {
        //     $query->where('diccionario_personal_acepcion.dic_aula_id', $dicAula->id);
        // })->pluck('tematica_id');
        // dd($tematicasActivas_ids);
        
        // $datos->listaTematicasDisponibles = $tematicas_ids;
        if ($dicAula){
            $entradas_ids = peticionEntradasAula($dicAula->id, ['id'])->get()->pluck('id');
            $acepcion_ids = EnvioAcepcion::whereIn('envio_entrada_id',$entradas_ids)->get()->pluck('id');
            $tematicas_ids = EnvioAcepcionTematica::whereIn('envio_acepcion_id', $acepcion_ids)->get()->pluck('tematica_id');

            // dd(
            //     '$entradas_ids', $entradas_ids,
            //     '$acepcion_ids', $acepcion_ids,
            //     'tematicas_ids', $tematicas_ids
            // );
            $datos->listaTematicasDisponibles = CampoValor::where('mst_campo_entrada_id', '=', config('ctes.campos_acepcion.tematica'))
            ->whereIn('id', $tematicas_ids)
            ->orderBy('descripcion', 'asc')
            ->distinct()
            ->get();
        }
        // Obtengo los diccionarios de aula a los que está unido el usuario actual
        $listaDiccionariosAula = daGetDiccionariosAulaByUserConectado(config('ctes.estado_envio_habilitado.activo'));
        $datos->listaDiccionariosAula = $listaDiccionariosAula;

        // Obtenemos el curso escolar actual
        $curso_escolar = getFormattedCursoEscolar(time());
        $datos->curso_escolar = $curso_escolar;

        // Obtenemos la descripción del centro del usuario conectado
        // $centro_denominacion = \Session::get('userData')['centros'][0]['denominacion'];
        // $datos->centro_denominacion = $centro_denominacion;

    }

    if (!function_exists('getDicAulaActivo')) {
    /**
     * Obtener Diccionario de aula activo , 
     * si no existe ninguno sale 
     * el último al que se ha unido el usuario
     * si no tiene diccionarios devuelve null
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @return DicAula Diccionario de aula activo o null si no tiene diccionarios
     */
    function getDicAulaActivo()
    {
        // Diccionarios de aula
        $diccionarios = daGetDiccionariosAulaConectadosByPersonaId(auth()->user()->userPersona->id); 
        // $diccionariosVisibles = get dic visibles persona()
        $diccionariosOwn = auth()->user()->userPersona->dicAulas;
        // dd("no hay dics", $diccionariosOwn, count($diccionarios)>0,( $diccionarios && count($diccionarios)>0 ));
        if ( $diccionarios && count($diccionarios)>0 ) {
            if ( usuarioEsDocente(Auth::user()) ) {
                $dicActivo = daGetDiccionarioUltimoUnido(auth()->user()->userPersona->id);
            } else {
                // el primero visible
                foreach ($diccionarios as $dic) {
                    if ($dic->visible_estudiante == config('ctes.estados.activo')){
                        $dicActivo = $dic;
                        break;// sale del foreach
                    }
                }
            }
        }

        // Ahora te unes automaticamete a los diccionarios que creas por lo que no hace falta poner estos:
        // if ( $diccionariosOwn && count( $diccionariosOwn )>0 ) {
        //     // return ( dd($diccionariosOwn[0]) );
        //     $dicActivo = $diccionariosOwn[0];
        // }

        // Si se a activado otro diccionario de aula y esta en session se pone este como activo
        if ( session()->has('userData.dicAulaActivo') ) {
            $dicSession = \Session::get('userData')['dicAulaActivo'];

            //Actualiza dic aula con los datos de la bbdd :
            // (para que no falle si se ha cambiado opciones dentro de la misma
            //  session)
            // TODO: revisar confictos session
            if ( $diccionarios->find($dicSession->id ))
                $dicSession = $diccionarios->find($dicSession->id);

            // El dicAula de session es uno de tus diccionarios de aula?
            $dicSesionOk = false;
            $ids_diccionarios = [];
            foreach ($diccionarios as $key => $d) {
                if ($dicSession->id == $d->id) {
                    // El dicAula de session es visible para estudiantes?
                    if ( !usuarioEsDocente(Auth::user()) ) {
                        if ($d->visible_estudiante == config('ctes.estados.activo')){
                            $dicActivo = $dicSession;
                        }
                    } else {
                        $dicActivo = $dicSession;
                    }
                }
            }
        }
        if (isset($dicActivo)) return $dicActivo;
        else return null;
    }}

    if (!function_exists('getEntradasAulaByInitial')) {
        /**
         * Muestra las entradas que comienzan por $letra del diccionario de aula
         * limitado al numero de entradas en la configuración
         *
         * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
         * @version 1.0.0
         *
         * @param DicAula $dicAula Diccionario de aula a consultar
         * @param string $letra letra inicial de las entradas
         * @param integer $offset a partir de esta entrada en el listado de entradas
         *
         * @return collection de EnvioEntradas
         */
        function getEntradasAulaByInitial( DicAula $dicAula, string $letra, $offset = 0 )
        {
            $limit = config('ctes.scrollEntradas');

            $entradas = peticionEntradasAula($dicAula->id)
                ->where('entrada', 'like', $letra . '%')
                ->offset($offset)->limit($limit)->get();

            return $entradas;
        }
    }

    /**
     * Muestra las entradas que incluyen o coinciden con la consulta de búsqueda
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param DicAula $dicAula Diccionario de aula a consultar
     * @param string $letra letra inicial de las entradas
     * @param integer $offset a partir de esta entrada en el listado de entradas
     *
     * @return collection de EnvioEntradas
     */
    function getEntradasAulaByConsulta( DicAula $dicAula, string $consulta, $offset = 0 , $listaCategorias=null)
    {
        $limit = config('ctes.scrollEntradas');

        $entradas = peticionEntradasAula($dicAula->id)
                ->where('entrada', 'like', '%' . $consulta . '%');

        if ($listaCategorias) {
            $entradas = $entradas->whereHas('envioAcepciones', function ($query) use ($listaCategorias) {
                $query->whereHas('envioAcepcionTematicas', function ($query) use ($listaCategorias) {
                    $query->whereIn('tematica_id', $listaCategorias);
                });
            });
        }        
        $entradas = $entradas
            ->orderBy('entrada', 'asc')
            ->offset($offset)->limit($limit)->get();

        return $entradas;
    }

if (!function_exists('getEntradasAulaByTematica')) {
    /**
     * Muestra las entradas para las tematicas/etiquetas selecionados
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * 
     * @param DicAula $dicAula
     * @param Array   $listaCategorias
     * @param integer $offset
     * @return void
     */
    function getEntradasAulaByTematica(DicAula $dicAula, Array $listaCategorias, int $offset = 0 )
    {
        $limit = config('ctes.scrollEntradas');

        $entradas = peticionEntradasAula($dicAula->id);

        if ($listaCategorias) {
            $entradas = $entradas->whereHas('envioAcepciones', function ($query) use ($listaCategorias) {
                $query->whereHas('envioAcepcionTematicas', function ($query) use ($listaCategorias) {
                    $query->whereIn('tematica_id', $listaCategorias);
                });
            });
        }        
        $entradas = $entradas
            ->orderBy('entrada', 'asc')
            ->offset($offset)->limit($limit)->get();

        return $entradas;
    }
}
    

    if (!function_exists('comentarEntrada')) {
        /**
         * Añade comentario a una entrada
         *
         * @author Javier Pérez Batista <javier.perez@altia.es>
         * @version 1.0.0
         *
         * @param [type] $campo_id
         * @param [type] $campo_print
         * @param [type] $dicAulacampos
         *
         * @return void
         */
        function comentarEntrada($request, $dicAulaId, $entradaId) {
            $envioEntrada = EnvioEntrada::find($entradaId);
            $diccionario = DicAula::find($dicAulaId);
            $personaId = Auth::user()->id;
            $tinyComentario = $request->all()['tinyComentario'];
            $ESTADO_ACTIVO = config('ctes.estados.activo');

            if (is_null($envioEntrada)) {
                $errors = (object) [
                    'msg' => 'Entrada no encontrada'
                ];
                return view('layouts.partials.alerts.errors')
                    ->with('errors', collect($errors));
            } else if (is_null($diccionario)) {
                $errors = (object) [
                    'msg' => 'Diccionario no encontrado'
                ];
                return view('layouts.partials.alerts.errors')
                    ->with('errors', collect($errors));
            } else {
                $comentario = new ComentarioEntrada;
                $comentario->dic_aula_id = $dicAulaId;
                $comentario->envio_entrada_id = $envioEntrada->id;
                $comentario->persona_id = $personaId;
                $comentario->comentario = $tinyComentario;
                $comentario->fecha_envio = Carbon::now();
                $comentario->estado = $ESTADO_ACTIVO;
                $comentario->dic_aula_id = $dicAulaId;
                $comentario->save();

                return view('layouts.partials.components.botones.entrada.comentarios')->with(
                    [
                        'entrada' =>$envioEntrada
                    ]
                );
            }
        }
    }

if (!function_exists('ocultaAcepcion')) {
    /**
     * Oculta o muestra la acepción de una entrada de aula
     *
     * @param [String] $acepcionId
     * @return void
     * @author Javier Pérez Batista <javier.perez@altia.es>
     * @version 1.0.0
     */
    function ocultaAcepcion($acepcionId, $entradaId)
    {
        $entrada = EnvioEntrada::find($entradaId);

        if (count($entrada->envioAcepciones) == 1) {
            $mensaje =  __('diccionario.ultima_acepcion');
            return redirect()->back()->withErrors([$mensaje]);
        }

        try {
            $envioAcepcion = EnvioAcepcion::find($acepcionId);
            $envioAcepcion->estado
                =   $envioAcepcion->estado == config('ctes.estados_entrada.oculta')?
                    config('ctes.estados_entrada.visible'):
                    config('ctes.estados_entrada.oculta');
            $envioAcepcion->save();
            // Cambiamos el estado de oculta <==> visible
            if ($envioAcepcion->estado == config('ctes.estados_entrada.visible')) {
                $mensaje =  __('diccionario.acepcion_visible');
            } else {
                $mensaje =  __('diccionario.acepcion_ocultada');
            }
            return redirect()->back()->with('success', $mensaje);
        } catch(Exception $e) {
            $mensaje =  __('diccionario.URLmala');
            return redirect()->back()->withErrors([$mensaje]);
        }
    }
}

if (!function_exists('ocultaEntrada')) {
    /**
     * Oculta o muestra la entrada de un diccionario de aula
     *
     * @param [String] $entradaId
     * @return void
     * @author Javier Pérez Batista <javier.perez@altia.es>
     * @version 1.0.0
     */
    function ocultaEntrada($dicAulaId, $entradaId)
    {

        try {
            $dicAulaEntrada = DicAulaEntrada::where('dic_aula_id', $dicAulaId)->where('envio_entrada_id', $entradaId)->first();
            $dicAulaEntrada->delete();
            $envioEntrada = EnvioEntrada::find($entradaId);
            $envioEntrada->estado
                =   $envioEntrada->estado == config('ctes.estados_entrada.oculta')?
                    config('ctes.estados_entrada.visible'):
                    config('ctes.estados_entrada.oculta');
            $envioEntrada->save();
            // Cambiamos el estado de oculta <==> visible
            if ($envioEntrada->estado == config('ctes.estados_entrada.visible')) {
                $mensaje =  __('diccionario.entrada_visible');
            } else {
                $mensaje =  __('diccionario.entrada_no_pubicar');
            }
            return redirect()->back()->with('success', $mensaje);
        } catch(Exception $e) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_base_error'),
                ['file' => __FILE__, 'line' => __LINE__],
                PHP_EOL . json_encode([$dicAulaId, $entradaId], JSON_PRETTY_PRINT),
                $e->getMessage()
            );
            $mensaje =  __('diccionario.URLmala');
            return redirect()->back()->withErrors([$mensaje]);
        }
    }
}

if (!function_exists('aceptaEnvios')) {
    /**
     * Comprueba si el diccionario tiene los envios habilitados y la fecha de hoy esta
     * dentro de lo que permite enviar
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param [DicAula] $diccionario
     *
     * @return void
     */
    function aceptaEnvios($diccionario)
    {
        if ( $diccionario->envios_habilitados ){
            $fecha_envio_ini = $diccionario->envios_fecha_ini;
            $today = Carbon::today();
            if ( $fecha_envio_ini ){
                if ($fecha_envio_ini <= $today ){
                    return true;
                } else {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
}

if (!function_exists('getDAulaEnviosDiccionario')) {
    function getDAulaEnviosDiccionario($dicAulaId, $estudianteId=null)
    {
        $entradas = EnvioEntrada::selectRaw('max(id) as id, dp_entrada_id, count(*) as count' )
        ->whereHas(
            'dpEnvio', function ($envio) use ($estudianteId, $dicAulaId) {
                $envio->where('dic_aula_id', $dicAulaId)->whereHas(
                    'dpDiccionario', function ($diccionario) use ($estudianteId) {
                        if (isset($estudianteId)) {
                            $diccionario->where(
                                'persona_id', $estudianteId
                            );
                        }
                    }
                )
                ->with('dpDiccionario');
            }
        )
        ->groupBy('dp_entrada_id')
        ->orderBy('entrada')
        ;
        // con esto de arriba obtengo el numero de envios de cada entrada y el id
        // del ultimo envio
        $datosEnvios = $entradas->get();

        foreach ($datosEnvios as $key => $e) {
            $envioEstrada = EnvioEntrada::find($e->id)
                // ->with('dpEnvio')
                ;
            $e->info = $envioEstrada;
        }

        return $datosEnvios;

    }
}

/*
Estas clases no se usan actualmente
function daBorrarParticpastes( $diccionarioAula )
{
    try {
        DicAulaParticipante::where('dic_aula_id', $diccionarioAula->id);
    } catch (\Throwable $th) {
        throw $th;
    }
}

function daBorrarAulaCampos( $diccionarioAula )
{
    try {
        DicAulaCampo::where('dic_aula_id', $diccionarioAula->id);
    } catch (\Throwable $th) {
        throw $th;
    }
}
}*/

function daTieneEnviosPendientes( $dicAula )
{
    try {
        $dicAulaId = $dicAula->id;
        $enviosEntradasCount = EnvioEntrada::whereHas(
            'dpEnvio', function ($envio) use ( $dicAulaId ) {
                $envio
                    ->where('dic_aula_id', $dicAulaId)
                    ->with('dpDiccionario');
            }
        )->get()->count();

        return $enviosEntradasCount>0;
    } catch (\Throwable $th) {
        throw $th;
    }

}


/**
 * Validar que la combinacion codigo de curso y año de inicio es unica
 * pueden existir varios codigos iguales siempre que tengan año de inictio distinto
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param [string] $codigo
 * @param [integer] $ano_ini_curso
 *
 * @return Validator
 */
if (!function_exists('daValidateCode')) {
// function daValidateCode( $codigo, $ano_ini_curso )
// {
//     // comprobar que la combinacion codigo+curso es unica ( el codigo se puede repetir en cada curso )
//     // genera un select como este:
//     // select count(*) as aggregate from `dic_aula` where
//     // `codigo` = <codigo-enviado> and `id` <> NULL and `ano_ini_curso_escolar` = <ano_ini-enviado>
//     $validatorCode = Validator::make(['codigoAleatorio' => $codigo ], [
//         'codigoAleatorio' => 'required|unique:dic_aula,codigo,NULL,id,ano_ini_curso_escolar,' . $ano_ini_curso
//     ]);

//     return $validatorCode;
// }
// }

    function daValidateCode($codigo)
    {
        $validatorCode = Validator::make(['codigo' => $codigo], [
            'codigo' => 'required|string|between:4,6|unique:dic_aula,codigo'
        ]);

        return $validatorCode;
    }
}
if (!function_exists('daGenerateCode')) {
/**
 * Genera un codigo alfanumerico aleatorio
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param [integer] $cifras numero de cifras del codigo
 *
 * @return void
 */
function daGenerateCode( $cifras ) {
    //  en js se genera con:
    // Math.random().toString(36).substr(2, 6).toUpperCase();
    $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $txt = '';
    for($i=0;$i<$cifras;$i++){
        $txt.=substr($str, rand(0, strlen($str)), 1);
    }
    return $txt;
}
}

if (!function_exists('daGetEntradaByEntradaByDiccionario')) {
/**
 * Devuelve un EnvioEntrada del diccionario que tenga un determinado nombre de entrada
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param string $entrada_entrada Nombre de entrada
 * @param integer $dicAulaid Id diccionario de aula
 *
 * @return EnvioEntrada
 */
    function daGetEntradaByEntradaByDiccionario($entrada_entrada, $dicAulaid)
    {

        $envioEntrada = EnvioEntrada::whereHas('DicAula', function($q) use ($dicAulaid){
            $q->where('dic_aula.id',$dicAulaid);
            })
            // diferencia entre acentos pero no entre may y min
            ->whereRaw('LOWER(`entrada`) LIKE "'.mb_strtolower($entrada_entrada).'"  COLLATE utf8_bin')
            ->first();

        return $envioEntrada;
    }
}

// if (!function_exists('daBuscarEntradaDuplicada')) {

//     function daBuscarTituloDuplicado($titulo, $entradaId)
//     {

//         $envioEntrada = EnvioEntrada::whereHas('DicAula', function($q){
//             $q->where('dic_aula.id',9);
//             })
//             // ->where('entrada',$entrada_entrada)
//             ->whereRaw('LOWER(`entrada`) LIKE "'.mb_strtolower($entrada_entrada).'"')
//             ->first();

//         return $envioEntrada;
//     }
// }

/**
 * Obtener listado de entradas filtrado con datos de la busqueda
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param [type] $dicAulaId
 * @param [type] $params
 *
 * @return void
 */
if (!function_exists('getDicAulaEntradasBusqueda')) {
function getDicAulaEntradasBusqueda( $dicAulaId, $params)
{
    $entradas = EnvioEntrada::whereHas(
        'dpEnvio', function ($envio) use ($params, $dicAulaId) {
            $envio->where('dic_aula_id', $dicAulaId)->whereHas(
                'dpDiccionario', function ($diccionario) use ($params) {
                    if (isset($params->selectedSliderEstudiante)) {
                        $diccionario->where(
                            'persona_id', $params->selectedSliderEstudiante
                        );
                    }
                }
            )
            ->with('dpDiccionario');
        }
    );

    // $entradas0 = $entradas;

    //Filtrar nombre de entrada si viene en el request
    if ( isset($params->buscarNombre) && $params->buscarNombre!=='' ){
        $s = $params->buscarNombre;
        $entradas = $entradas->where('entrada', 'like', '%'.$s.'%' );
    }
    // $entradas1 = $entradas->get();
    if (isset($params->desde)) {
        $entradas = $entradas->whereHas(
            'dpEnvio', function ($envio) use ($params) {
                $envio->where(
                    'created_at',
                    '>=',
                    Carbon::createFromFormat('Y-m-d', $params->desde)
                    ->startOfDay()->toDateTimeString()
                );
            }
        );
    }

    if (isset($params->hasta)) {
        $entradas = $entradas->whereHas(
            'dpEnvio', function ($envio) use ($params) {
                $envio->where(
                    'created_at',
                    '<=',
                    Carbon::createFromFormat('Y-m-d', $params->hasta)
                    ->endOfDay()->toDateTimeString()
                );
            }
        );
    }
    // $entradas2 = $entradas->get();

    if (isset($params->selectedLetra)) {
        $entradas = $entradas->where(
            'entrada',
            'LIKE',
            $params->selectedLetra.'%'
        );
    }
    // $entradas3 = $entradas->get();

    //publicada o no publicada
    if ( isset($params->estado) && $params->estado!='0' ) {
        $entradas = $entradas->where('estado', $params->estado );
    }
    // $entradas4 = $entradas->get();

    $entradas = $entradas
        ->orderBy('entrada')
        ->orderByDesc('created_at')
        ->limit(200)
    ;

    // dd($entradas->get(),  $params,
    //         'entradas0 participante', $entradas0->count(), $entradas0,
    //         'entradas1 nombre entrada', $entradas1->count(), $entradas1,
    //         'entradas2 fechas', $entradas2,
    //         'entradas3 inical', $entradas3,
    //         'entradas4 estado', $entradas4
    //     );
    return $entradas;
}
}// fin if function exist


if (!function_exists('getDicAulaEntradasBusquedaEstudiante')) {
function getDicAulaEntradasBusquedaEstudiante( $params )
{
    if (isset($params->selectedSliderEstudiante)) {
        $estudiante = Persona::find($params->selectedSliderEstudiante);
    } else {
        $estudiante = null;
    }
    return $estudiante;
}
}// fin if function exist

if (!function_exists('hablitarParticipanteDicAula')) {
function hablitarParticipanteDicAula($participanteId)
{
    $obj = new \stdClass();
    try {
      $participante = DicAulaParticipante::where('id', $participanteId)
          ->get()->first();
      $participante->estado = config('ctes.estados.activo');

      $save = $participante->save();
      if ($save){
        $obj->status = true;
        return $obj;
      } else {
        $obj->status = false;
        $obj->msg = 'error_al_guadar';
        return $obj;
      }
    } catch (\Throwable $th) {
      Debugbar::error(  $th  );
      customLoggin(
          config('ctes.log_levels.error'),
          config('ctes.log_types.data_base_error'),
          ['file' => $th->getFile(), 'line' => $th->getLine()],
          PHP_EOL . 'Fallo al intentar hablitar particpante: ' .$participanteId,
          $th->getMessage()
      );
    }
}
}

if (!function_exists('deshablitarParticipanteDicAula')) {
function deshablitarParticipanteDicAula($participanteId)
{
    $obj = new \stdClass();
    try {
        $participante = DicAulaParticipante::where('id', $participanteId)
          ->get()->first();
        // comprueba que no me estes deshabiltando a mi mismo y que no este desahbilitando al unico docente
        // if ( $partcipante->persona == getSessionPersona() )
        // Debugbar::info( 'persona igual'. $participante->persona_id );
        // Debugbar::info( 'persona session'. getSessionPersona()['id'] );
        if( $participante->persona_id == getSessionPersona()['id'] ){
          $obj->status = false;
          $obj->msg = 'no_puedes_auto_excluirte';
          return $obj;
        }
        if (participanteEsCoordinador($participanteId)){
          // Debugbar::info( 'particpantes ' . $participante->DicAula->participantes->count() );
          // Debugbar::info( 'docentes: ' . $participante->DicAula->participantes->where('rol_diccionario_id',config('ctes.rol.docente'))->count() );
          $docentes = $participante->DicAula->participantes->where('rol_diccionario_id',config('ctes.rol.docente'));
          if ( !($docentes->count() > 1) ) {
            Debugbar::info('docente ' . $docentes->first()->id );
            $ultimoDocente_ParticipanteId = $docentes->first()->id;
            // solo hay uno
            // voya a eliminar ese ultixmo docente?
            // Debugbar::info( 'ultimo docente: ' . $ultimoDocente_ParticipanteId . ' particpante a borrar ' . $participanteId );
            if( $ultimoDocente_ParticipanteId == $participanteId ){
              $obj->status = false;
              $obj->msg = 'no_puedes_eliminar_ultimo_docente';
              return $obj;
            }
          }
        }

        $participante->estado = config('ctes.estados.inactivo');
        $save = $participante->save();

        if ($save){
          $obj->status = true;
          return $obj;
        } else {
          $obj->status = false;
          $obj->msg = 'error_al_guadar';
          return $obj;
          // return json_decode(json_encode('{"status": false, "msg": "error_al_guadar"}'));
        }

    } catch (\Throwable $th) {
      Debugbar::error(  $th  );
      customLoggin(
          config('ctes.log_levels.error'),
          config('ctes.log_types.data_base_error'),
          ['file' => $th->getFile(), 'line' => $th->getLine()],
          PHP_EOL . 'Fallo al intentar hablitar particpante: ' .$participanteId,
          $th->getMessage()
      );
    }
}
}

if (!function_exists('hablitarParticipanteDicAulaAdmin')) {
    function hablitarParticipanteDicAulaAdmin($participanteId)
    {
        $obj = new \stdClass();
        try {
          $participante = DicAulaParticipante::where('id', $participanteId)
              ->get()->first();
          $participante->rol_diccionario_id = config('ctes.rol.docente');
    
          $save = $participante->save();
          if ($save){
            $obj->status = true;
            return $obj;
          } else {
            $obj->status = false;
            $obj->msg = 'error_al_guadar';
            return $obj;
          }
        } catch (\Throwable $th) {
          Debugbar::error(  $th  );
          customLoggin(
              config('ctes.log_levels.error'),
              config('ctes.log_types.data_base_error'),
              ['file' => $th->getFile(), 'line' => $th->getLine()],
              PHP_EOL . 'Fallo al intentar habilitar como admin al particpante: ' .$participanteId,
              $th->getMessage()
          );
        }
    }
    }

if (!function_exists('deshablitarParticipanteDicAulaAdmin')) {
function deshablitarParticipanteDicAulaAdmin($participanteId)
{
    $obj = new \stdClass();
    try {
        $participante = DicAulaParticipante::where('id', $participanteId)
          ->get()->first();
        // comprueba que no me estes deshabiltando a mi mismo y que no este desahbilitando al unico docente
        // if ( $partcipante->persona == getSessionPersona() )
        // Debugbar::info( 'persona igual'. $participante->persona_id );
        // Debugbar::info( 'persona session'. getSessionPersona()['id'] );
        if( $participante->persona_id == getSessionPersona()['id'] ){
            $obj->status = false;
            $obj->msg = 'no_puedes_auto_autodeshabilitarte_edicion';
            return $obj;
          }
        // if( $participante->dicAula->persona_id == getSessionPersona()['id'] ){
        //     $obj->status = false;
        //     $obj->msg = 'solo_propietario_puede_deshabilitar_edicion';
        //     return $obj;
        // }
        if (participanteEsCoordinador($participanteId)){
          // Debugbar::info( 'particpantes ' . $participante->DicAula->participantes->count() );
          // Debugbar::info( 'docentes: ' . $participante->DicAula->participantes->where('rol_diccionario_id',config('ctes.rol.docente'))->count() );
          $docentes = $participante->DicAula->participantes->where('rol_diccionario_id',config('ctes.rol.docente'));
          if ( !($docentes->count() > 1) ) {
            Debugbar::info('docente ' . $docentes->first()->id );
            $ultimoDocente_ParticipanteId = $docentes->first()->id;
            // solo hay uno
            // voya a eliminar ese ultixmo docente?
            // Debugbar::info( 'ultimo docente: ' . $ultimoDocente_ParticipanteId . ' particpante a borrar ' . $participanteId );
            if( $ultimoDocente_ParticipanteId == $participanteId ){
              $obj->status = false;
              $obj->msg = 'no_puedes_eliminar_ultimo_docente';
              return $obj;
            }
          }
        }

        $participante->rol_diccionario_id = config('ctes.rol.alumno');
        $save = $participante->save();

        if ($save){
          $obj->status = true;
          return $obj;
        } else {
          $obj->status = false;
          $obj->msg = 'error_al_guadar';
          return $obj;
          // return json_decode(json_encode('{"status": false, "msg": "error_al_guadar"}'));
        }

    } catch (\Throwable $th) {
      Debugbar::error(  $th  );
      customLoggin(
          config('ctes.log_levels.error'),
          config('ctes.log_types.data_base_error'),
          ['file' => $th->getFile(), 'line' => $th->getLine()],
          PHP_EOL . 'Fallo al intentar deshabilitar como admin al particpante: ' .$participanteId,
          $th->getMessage()
      );
    }
}
}

if (!function_exists('participanteEsCoordinador')) {
function participanteEsCoordinador($participanteId)
{
  $participante = DicAulaParticipante::where('id', $participanteId)
    ->get()->first();
  return ($participante->persona->personaUser->role_id == config('ctes.rol.docente'));
}
}

if (!function_exists('queryVigencia')){
function queryVigencia($anoEscolarActual)
{
    $fun = function ($q) use ($anoEscolarActual) {
        $cursosTrascurridos = "($anoEscolarActual - ano_ini_curso_escolar)";
        return $q 
            ->where('estado', '=', config('ctes.estados.activo'))
            ->where('vigencia', '>', 1 )         
            // compara los cursos que han pasado contra el numero de cursos que esta vigente
            // si es vigente durante 3 años y se creo en 2017 
            ->where('vigencia', '>=', DB::raw($cursosTrascurridos) );
    };
    
    return $fun;
}
}

if (!function_exists('daGetDiccionariosConVigencias')){
function daGetDiccionariosConVigencias()
{
    $anoEscolarActual = getAnoIniCursoEscolar( new Datetime() );

    // Muestra diccionarios cursoActual o atemporales
    $diccionarios = DicAula::
        where( 
            // queryVigencia($anoEscolarActual)
            function ($q) use ($anoEscolarActual) {
            $cursosTrascurridos = "($anoEscolarActual - ano_ini_curso_escolar)";
            return $q 
                // ->where('estado', '=', config('ctes.estados.activo'))
                ->where('vigencia', '>', 0 ) 

                // compara los cursos que han pasado contra el numero de cursos que esta vigente
                // si es vigente durante 3 años y se creo en 2017 
                // ->where('vigencia', '>=', DB::raw($cursosTrascurridos) )
                ;
            }
        );
        
    return $diccionarios;
}
}

/**
 * Comprueba si el diccionario es vigente a dia  de hoy
 *
 * @param DicAula $dicAula
 * @return boolean devuelve verdadero si es vigente 
 */
if (!function_exists('daEsVigente')){
function daEsVigente(DicAula $dicAula)
{
    // si es atemporal siempre esta vigente
    if ($dicAula->esAtemporal()) return true;

    $anoEscolarActual = getAnoIniCursoEscolar( new Datetime() );
    $cursosTrascurridos = $anoEscolarActual - $dicAula->ano_ini_curso_escolar;

    // dd(
    //     '$anoEscolarActual',$anoEscolarActual,
    //     '$cursosTrascurridos',$cursosTrascurridos,
    //     '($dicAula->vigencia > $cursosTrascurridos)',($dicAula->vigencia > $cursosTrascurridos),
    // );

    return ($dicAula->vigencia > $cursosTrascurridos);
}
}

/**
 * Desactivar si no es vigente en el curso/fecha actual
 *
 * @param DicAula $dicAula
 * @return Boolean Desactivado o no 
 */
if (!function_exists('daDesactivarSiNoVigente')) {
function daDesactivarSiNoVigente(DicAula $dicAula)
{
    $desactivado = false;
    //  && !$dic->esAtemporal()
    if (!daEsVigente($dicAula) ){
        $dicAula->estado = 0;
        $dicAula->save();
        $desactivado = true;
    }
    return $desactivado;
}
}


function daGetDiccionariosAulaNoVigenteByUserConectado()
{
    // Obtenemos el id de persona del usuario conectado
    try {
        $persona_id = getSessionPersona()['id'];
    } catch (\Throwable $th) {
        throw $th;
        // return redirect('cas.logout')->with('error', 'No se pudo obtener el usuario conectado');
    }

    return daNoVigentesByPerosonaId($persona_id)->get();

}

function daNoVigentesByPerosonaId(int $persona_id)
{
    // Diccionarios a los que la persona esta conectada pero no esta activo
    $diccionarios = DicAula::whereHas('participantes',
        function ($query) use ($persona_id) {
            $query
                ->where('persona_id', '=', $persona_id)
                ->where('estado', '=', config('ctes.estados.activo'))
            ;
        }
    )->where('estado', config('ctes.estados.inactivo'));

    return $diccionarios;
}

