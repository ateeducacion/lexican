<?php

namespace App\Http\Controllers;

use App\Models\DicAula;
use App\Models\DiccionarioPersonalEntrada;
use App\Models\EnvioEntrada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use stdClass;

/**
 * Controlador de todo el sistema de diccionario personal.
 * Incluye el manejo de diccionario personal, entidades, acepciones y medios
 * 
 * @package App\Http\Controllers
 * 
 * @access public
 * @author Julio Buenadicha <julio.buenadicha@altia.es>
 * @version Release: <package_version>
 * 
 */
class EnviosController extends Controller
{

    /**
     * Muestra la pantalla para seleccionar los diccionarios de aula para enviar el diccionario personal
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function enviarDiccionarioGET(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        // Obtenemos el diccionario personal del usuario conectado
        $diccionario = dpDiccionario_GetDiccionarioByUserActual();
        $datos->diccionario = $diccionario;


        // Obtengo los diccionarios de aula a los que está unido el usuario actual
        $listaDiccionariosAulaActivos = daGetDiccionariosAulaByUserConectado(config('ctes.estado_envio_habilitado.activo'));
        foreach ($listaDiccionariosAulaActivos as $listaDiccionariosAulaActivo) {
            $listaDiccionariosAulaActivo->estadoActual = config('ctes.estado_envio_habilitado.activo');
        }
        $listaDiccionariosAulaInactivos = daGetDiccionariosAulaByUserConectado(config('ctes.estado_envio_habilitado.inactivo'));
        foreach ($listaDiccionariosAulaInactivos as $listaDiccionariosAulaActivo) {
            $listaDiccionariosAulaActivo->estadoActual = config('ctes.estado_envio_habilitado.inactivo');
        }
        $listaDiccionariosAulaPlanificados = daGetDiccionariosAulaByUserConectado(config('ctes.estado_envio_habilitado.planificado'));
        foreach ($listaDiccionariosAulaPlanificados as $listaDiccionariosAulaActivo) {
            $listaDiccionariosAulaActivo->estadoActual = config('ctes.estado_envio_habilitado.planificado');
        }

        // Uno los diccionarios activos y los planificados e inactivos porque voy a mostrar unos en verde y otros en gris
        $listaDiccionariosAula = $listaDiccionariosAulaActivos->merge($listaDiccionariosAulaInactivos);
        $listaDiccionariosAula = $listaDiccionariosAula->merge($listaDiccionariosAulaPlanificados);

        $datos->listaDiccionariosAula = $listaDiccionariosAula;
        // 
        if ( usuarioEsDocente(Auth::user()) ){
            $datos->ocultarDiccionarioNoVisible = false;
        } else {
            $datos->ocultarDiccionarioNoVisible = true;
        }

        // Migas de pan
        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],
            ['name' => __('diccionario.modal_enviar_diccionario_tooltip'), 'url' => $request->url],
        ];

        // Mostramos la ventana
        return view('diccionario/dpDiccionario/dpDiccionarioEnviarFormulario')->with('datos', $datos);
    }

    /**
     * Recibe una lista de diccionarios de aula y envía el diccionario personal del usuario conectado a estos diccionarios de aula
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function enviarDiccionarioPOST(Request $request)
    {
        // Obtengo la lista de diccionarios de aula destino
        $listaDiccionariosAula = $request->listaDiccionariosAula;
        $arrayDiccionariosAulaIds = explode(',', $listaDiccionariosAula);

        $listaErrores = [];
        $listaExitos = [];

        // Ahora solo se debe enviar las entradas marcadas como entradas seleciconadoas
        if ($request->entradasSelecionadas){
            $entradasSeleccionadasIds = explode(', ', $request->entradasSelecionadas);
        } else {
            $entradasSeleccionadasIds = [];
        }
        if ($request->entradasOcultas){
            $entradasOcultasIds = explode(', ', $request->entradasOcultas);
        } else {
            $entradasOcultasIds = [];
        }
        // dd('sel',$entradasSeleccionadasIds, 'ocultas', $entradasOcultasIds);
        try {

            foreach ($entradasOcultasIds as $idEntrada) {
                dpEntrada_ocultar($idEntrada, $this);
            }
            foreach ($entradasSeleccionadasIds as $idEntrada) {
                dpEntrada_mostrar($idEntrada, $this);
            }

            // Si no viene ningún código devuelvo error
            if (!isset($listaDiccionariosAula) || $listaDiccionariosAula = "") {
                array_push($listaErrores, __('diccionario.envio_error_no_diccionarios'));
                return redirect()->refresh()->with('errores', __('diccionario.envio_error_no_diccionarios'));
            } else {

                // Enviar el diccionario personal del usuario actual a los diccionarios de aula recibido por parámetro
                // $listaIntentos = dpEnvio_EnviarDiccionarioUserActualAListaDiccionariosAula($arrayDiccionariosAulaIds);
                $dicPersonal = dpDiccionario_GetDiccionarioByPersonaId(getSessionPersona()['id']);

                $listaEntradas = DiccionarioPersonalEntrada::where('dic_personal_id', '=', $dicPersonal->id)
                    ->whereIn("id", $entradasSeleccionadasIds )
                    ->get();
                // dd( $dicPersonal->id, $listaEntradas, $arrayDiccionariosAulaIds[0], $entradasSeleccionadasIds );
                $listaIntentos = []; // estaba preparado para que llegase como un array
                $listaIntentos[] = dpEnvio_EnviarEntradasADicAula( $dicPersonal->id, $listaEntradas, $arrayDiccionariosAulaIds[0] );

                foreach ($listaIntentos as $intento) {
                    if (isset($intento->error)) {
                        if( !isset($intento->error->tipo) ) {
                            $strErrores = $intento->error;
                            $intento->error = new \stdClass();
                            $intento->error->tipo = substr($strErrores,0,13);
                            $intento->error->html = substr($strErrores,14 );
                        }
                        // dd($intento);
                        if ( $intento->error->tipo == 'FALTAN_CAMPOS' ) {
                            // genera el mensaje
                            // No ha sido posible enviar el diccionario personal al diccionario de aula ":diccionario"
                            // a alguna de las entradas enviadas le fataban los siguientes datos:
                            array_push($listaErrores, __('diccionario.envio_error', ['diccionario' => 
                                    $intento->diccionarioNombre]) . ', '.
                                    __('diccionario.envio_error_entradas_faltan_datos') .
                                    "<br>" . $intento->error->html );

                        } else {
                            array_push($listaErrores, __('diccionario.envio_error', ['diccionario' => 
                                    $intento->diccionarioNombre]) );                                    
                        }                    
                        // return dd($listaIntentos[0]->error );
                        // log $intento->error
                        customLoggin(
                            config('ctes.log_levels.error'),
                            config('ctes.log_types.data_base_error'),
                            ['file' => __FILE__, 'line' => __LINE__ ],
                            PHP_EOL . json_encode($request->all(), JSON_PRETTY_PRINT),
                            PHP_EOL . 'Intento de envio: ' . json_encode($intento, JSON_PRETTY_PRINT)
                        );
                    } else {
                        array_push($listaExitos, __('diccionario.envio_exito', ['diccionario' => $intento->diccionarioNombre]));
                        // log $intento->error
                    }
                }
            }

        } catch (\Throwable $th) {
            // dd( $th->getMessage() ,['file' => $th->getFile(), 'line' => $th->getLine() ] );
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_base_error'),
                ['file' => $th->getFile(), 'line' => $th->getLine() ],
                PHP_EOL . json_encode($request->all(), JSON_PRETTY_PRINT),
                __FILE__ .':'. __LINE__ . $th->getMessage()
            );
        }

        return redirect()->refresh()->withErrors($listaErrores)->with('successArray', $listaExitos);
    }

    /**
     * Recibe una lista de diccionarios de aula y envía la entrada a estos diccionarios de aula
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function enviarEntradaPOST(Request $request)
    {
        try {
            // Obtengo la lista de diccionarios de aula destino
            $listaDiccionariosAula = $request->listaDiccionariosAula;
            if (isset($listaDiccionariosAula)) {
                $arrayDiccionariosAulaIds = explode(',', $listaDiccionariosAula);

                // Obtenemos el diccionario y la entrada del GET
                $entrada_id = $request->entrada_id;

                $entrada = dpEntrada_GetEntradaById($entrada_id);

                if ($entrada) {

                    // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
                    $this->authorize('isOwner', $entrada);

                    // Enviar la entrada a los diccionarios de aula recibido por parámetro
                    $listaIntentos = dpEnvio_EnviarEntradaAListaDiccionariosAula($entrada, $arrayDiccionariosAulaIds);
                    $listaErrores = [];
                    $listaExitos = [];
                    // dd($listaIntentos);
                    foreach ($listaIntentos as $intento) {
                        if (isset($intento->error)) {
                            // dd( substr($intento->error ,0, 14), $intento->error );
                            if( !isset($intento->error->tipo) ) {
                                $strErrores = $intento->error;
                                $intento->error = new \stdClass();
                                $intento->error->tipo = substr($strErrores,0,13);
                                $intento->error->html = substr($strErrores,14 );
                            }
                            if ( $intento->error->tipo == 'FALTAN_CAMPOS' ) {
                                
                                array_push($listaErrores, __('diccionario.envio_entrada_error', [
                                        'diccionario' => $intento->diccionarioNombre,
                                        'entrada' => $entrada->entrada,
                                    ]) . ' ' . __('diccionario.envio_entrada_error_faltan_campos') .": <br>" . $intento->error->html
                                );

                            } else {
                                array_push($listaErrores, __('diccionario.envio_entrada_error', [
                                        'diccionario' => $intento->diccionarioNombre,
                                        'entrada' => $entrada->entrada,
                                    ]) 
                                );
                            }
                            // log $intento->error
                        } else {
                            array_push($listaExitos, __('diccionario.envio_entrada_exito', [
                                'diccionario' => $intento->diccionarioNombre,
                                'entrada' => $entrada->entrada,
                            ]));
                            // log $intento->error
                        }
                    }

                    return redirect()->back()->withErrors($listaErrores)->with('successArray', $listaExitos);
                } else {
                    // Si NO existe la entrada mostramos ventana con error
                    $mensaje =  __('diccionario.URLmala');
                    return redirect()->back()->withErrors([$mensaje . $entrada_id]);
                }
            } else {
                // Si NO se han recibido diccionarios
                $mensaje =  __('diccionario.envio_error_no_diccionarios');
                return redirect()->back()->withErrors([$mensaje]);
            }
        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_base_error'),
                ['file' => __FILE__, 'line' => __LINE__],                
                PHP_EOL . json_encode($request->all(), JSON_PRETTY_PRINT),
                $th->getMessage()
            );
            dd('eror', $th);
        }        
    }

    /**
     * Reponde a una petición Ajax 
     * Devuelve la pantalla correcta cuando se pulsa en enviar entrada a un diccionario.
     * Si el usuario no está unido a ningún diccionario, abrimos la ventana MODAL de unirse a un diccionario
     * Si sólo tiene un diccionario conectado, abrimos la ventana de confirmación MODAL de enviar a ese diccionario
     * Si está conectado a varios diccionarios, abrimos la ventana MODAL para elegir diccionarios
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     *
     * @param Request $request
     *
     * @return void
     */
    public function getDiccionariosAulaEntradaAjax(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        // Obtenemos el diccionario y la entrada del GET
        $entrada_id = $request->entrada_id;

        $entrada = dpEntrada_GetEntradaById($entrada_id);
        if ($entrada) {
            // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
            $this->authorize('isOwner', $entrada);

            dpEntrada_datosComunesCosultas($datos);

            // Obtengo los diccionarios de aula a los que está unido el usuario actual
            // $listaDiccionariosAula = $datos->listaDiccionariosAula;
            // Obtengo los diccionarios de aula a los que está unido el usuario actual
            $listaDiccionariosAulaActivos = daGetDiccionariosAulaByUserConectado(config('ctes.estado_envio_habilitado.activo'));
            foreach ($listaDiccionariosAulaActivos as $listaDiccionariosAulaActivo) {
                $listaDiccionariosAulaActivo->estadoActual = config('ctes.estado_envio_habilitado.activo');
            }
            $listaDiccionariosAulaInactivos = daGetDiccionariosAulaByUserConectado(config('ctes.estado_envio_habilitado.inactivo'));
            foreach ($listaDiccionariosAulaInactivos as $listaDiccionariosAulaActivo) {
                $listaDiccionariosAulaActivo->estadoActual = config('ctes.estado_envio_habilitado.inactivo');
            }
            $listaDiccionariosAulaPlanificados = daGetDiccionariosAulaByUserConectado(config('ctes.estado_envio_habilitado.planificado'));
            foreach ($listaDiccionariosAulaPlanificados as $listaDiccionariosAulaActivo) {
                $listaDiccionariosAulaActivo->estadoActual = config('ctes.estado_envio_habilitado.planificado');
            }

            // Uno los diccionarios activos y los planificados e inactivos porque voy a mostrar unos en verde y otros en gris
            $listaDiccionariosAula = $listaDiccionariosAulaActivos->merge($listaDiccionariosAulaInactivos);
            $listaDiccionariosAula = $listaDiccionariosAula->merge($listaDiccionariosAulaPlanificados);

            // $centro_denominacion = $datos->centro_denominacion;
            $curso_escolar = $datos->curso_escolar;
            $nivelEstudios = $datos->nivelEstudios;
            $id = 'uid_' . uniqid();

            if (count($listaDiccionariosAula) == 0) {
                // No tiene ningún diccionario conectado, abrimos la ventana MODAL de unirse a un diccionario

                $html = View::make('layouts.partials.components.modal-unirsediccionario')
                    ->with('modal_unirse_body', __('diccionario.modal_unirse_diccionario_body_enviar_entradas') )
                    ->with('listaDiccionariosAula', $listaDiccionariosAula)
                    // ->with('centro_denominacion', $centro_denominacion)
                    ->with('curso_escolar', $curso_escolar)
                    ->with('nivelEstudios', $nivelEstudios)
                    ->with('id', $id)
                    ->with('modal_width', '600px')
                    ->render();

                return Response::json(['id' => $id, 'html' => $html]);
            } else {
                if (count($listaDiccionariosAula) == 1) {
                    // Sólo tiene un diccionario conectado, abrimos la ventana de confirmación MODAL de enviar a ese diccionario

                    // Envios al diccionario de la entrada
                    $dicAulaid = $listaDiccionariosAula[0]->id;

                    $enviosADic = $entrada->enviosEntrada()
                    ->with(['dpEnvio' => function($query) use ($dicAulaid) {
                        $query->where('dic_aula_id', $dicAulaid);
                    }]);
                    $enviosADic = $entrada->enviosEntrada()
                            ->whereHas('dpEnvio', function($q) use ($dicAulaid) {
                                $q->where('dic_aula_id', $dicAulaid);
                            })->get();
                    // $enviosADic = $enviosADic->orderby('id', 'desc')->limit(1)->get();

                    // $enviosADic = array_map(
                    //     function($envioEntrada) { return [$envioEntrada->id,$envioEntrada->updated_at]; },
                    //     $enviosADic->all()
                    // );

                    $html = View::make('layouts.partials.components.modal-enviarEntradaUnDiccionario')
                        ->with('listaDiccionariosAula', $listaDiccionariosAula[0])
                        ->with('entrada', $entrada)
                        ->with('enviosADic', $enviosADic )
                        ->with('id', $id)
                        ->with('modal_width', '900px')
                        ->render();

                    return Response::json(['id' => $id, 'html' => $html]);
                } else {
                    // count($listaDiccionariosAula) > 1

                    // ultimo envio de esta palabra al dic de aula
                    foreach ($listaDiccionariosAula as $dicAula ) {
                        $dicAulaid = $dicAula->id;

                        $enviosADic = $entrada->enviosEntrada()
                            ->whereHas('dpEnvio', function($q) use ($dicAulaid) {
                                $q->where('dic_aula_id', $dicAulaid);
                            })->get();

                        // $enviosADic = $enviosADic->orderby('id', 'desc')->limit(1)->get();
                        $dicAula->ultimoEnvioPalabra = $enviosADic;

                        // $enviosADic = array_map(
                        //     function($envioEntrada) { return [$envioEntrada->id,$envioEntrada->updated_at]; },
                        //     $enviosADic->all()
                        // );
                    }
                    

                    // Está conectado a varios diccionarios, abrimos la ventana MODAL para elegir diccionarios
                    $html = View::make('layouts.partials.components.modal-enviarEntradaVariosDiccionarios')
                        ->with('listaDiccionariosAula', $listaDiccionariosAula)
                        ->with('entrada', $entrada)
                        ->with('id', $id)
                        ->with('modal_width', '700px')
                        ->render();

                    return Response::json(['id' => $id, 'html' => $html]);
                }
            }
        }
    }


    /**
     * Reponde a una petición Ajax 
     * Devuelve la pantalla correcta cuando se pulsa en enviar diccionario.
     * Si el usuario no está unido a ningún diccionario, abrimos la ventana MODAL de unirse a un diccionario
     * Si sólo tiene un diccionario conectado, abrimos la ventana de confirmación MODAL de enviar a ese diccionario
     * Si está conectado a varios diccionarios, abrimos la ventana NO MODAL para elegir diccionarios
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     *
     * @param Request $request
     *
     * @return void
     */
    public function getDiccionariosAulaDiccionarioAjax(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();
        dpEntrada_datosComunesCosultas($datos);

        // Obtengo los diccionarios de aula a los que está unido el usuario actual
        $listaDiccionariosAula = $datos->listaDiccionariosAula;
        // $centro_denominacion = $datos->centro_denominacion;
        $curso_escolar = $datos->curso_escolar;
        $nivelEstudios = $datos->nivelEstudios;
        $id = 'uid_' . uniqid();

        if (count($listaDiccionariosAula) == 0) {
            // No tiene ningún diccionario conectado, abrimos la ventana MODAL de unirse a un diccionario
            $html = View::make('layouts.partials.components.modal-unirsediccionario')
                ->with('modal_unirse_body', __('diccionario.modal_unirse_diccionario_body_enviar_entradas') )
                ->with('listaDiccionariosAula', $listaDiccionariosAula)
                // ->with('centro_denominacion', $centro_denominacion)
                ->with('curso_escolar', $curso_escolar)
                ->with('nivelEstudios', $nivelEstudios)
                ->with('id', $id)
                ->with('modal_width', '600px')
                ->render();

            return Response::json([
                'id' => $id, 
                'html' => $html, 
                'info' => 'no existen diccionarios de aula' ,
                'listadic' => $listaDiccionariosAula,
                'listadic2' => $datos
            ]);
        } else {
            // if (count($listaDiccionariosAula) == 1) {
            //     // Sólo tiene un diccionario conectado, abrimos la ventana de confirmación MODAL de enviar a ese diccionario
            //     $html = View::make('layouts.partials.components.modal-enviarDiccionarioCompleto')
            //         ->with('listaDiccionariosAula', $listaDiccionariosAula[0])
            //         ->with('diccionario', $datos->diccionario)
            //         ->with('id', $id)
            //         ->with('modal_width', '900px')
            //         ->render();

            //     return Response::json(['id' => $id, 'html' => $html]);
            // } else {
            // count($listaDiccionariosAula) > 1
            // Está conectado a varios diccionarios, abrimos la ventana NO MODAL para elegir diccionarios
            // Devolvemos la URL para redirigir
            return Response::json(['redirect' => route('diccionario.enviar', [$datos->diccionario->id])]);
            // }
        }
    }


    /**
     * Reponde a una petición Ajax 
     * Devuelve la ventana MODAL de unirse a un diccionario
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     *
     * @param Request $request
     *
     * @return void
     */
    public function getUnirseDiccionarioAjax(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();
        dpEntrada_datosComunesCosultas($datos);

        // Obtengo los diccionarios de aula a los que está unido el usuario actual
        $listaDiccionariosAula = $datos->listaDiccionariosAula;
        // $centro_denominacion = $datos->centro_denominacion;
        $curso_escolar = $datos->curso_escolar;
        $nivelEstudios = $datos->nivelEstudios;
        $id = 'uid_' . uniqid();

        // No tiene ningún diccionario conectado, abrimos la ventana MODAL de unirse a un diccionario
        $html = View::make('layouts.partials.components.modal-unirsediccionario')
            ->with('modal_unirse_body', __('diccionario.modal_unirse_diccionario_body') )
            ->with('listaDiccionariosAula', $listaDiccionariosAula)
            // centro_denominacion->with('centro_denominacion', $centro_denominacion)
            ->with('curso_escolar', $curso_escolar)
            ->with('nivelEstudios', $nivelEstudios)
            ->with('id', $id)
            ->with('modal_width', '600px')
            ->render();

        return Response::json(['id' => $id, 'html' => $html]);
    }

    public function getComentarEntradaAjax(Request $request, $entrada_id)
    {
        $entrada = EnvioEntrada::find($entrada_id);
        if ( $entrada ) {
            // No tiene ningún diccionario conectado, abrimos la ventana MODAL de unirse a un diccionario
            $html = View::make('layouts.partials.components.modal-formulario-comentarEntrada')                               
                ->with('id', $entrada->id)
                ->with('entrada', $entrada)
                ->with('diccionario', $entrada->dicAula)
                ->with('modal_width', '600px')
                ->render();

            return Response::json([
                'id' => $entrada->id, 
                'html' => $html
            ]);
        } else {
            return false;
        }
    }
}
