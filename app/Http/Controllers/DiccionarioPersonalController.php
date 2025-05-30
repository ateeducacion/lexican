<?php

namespace App\Http\Controllers;

// use App\Http\Requests;

use App\Models\DiccionarioPersonalAcepcion;
use Illuminate\Http\Request;

use DB;
use App\Models\DiccionarioPersonalEntrada;
use App\Models\Persona;
use Illuminate\Support\Facades\URL;
use stdClass;
use Validator;


/**
 * Controlador de todo el sistema de diccionario personal.
 * Incluye el manejo de diccionario personal, entidades, acepciones y medios
 * 
 * @package App\Http\Controllers
 * 
 * @access  public
 * @author  Julio Buenadicha <julio.buenadicha@altia.es>
 * @version Release: <package_version>
 * 
 */
class DiccionarioPersonalController extends Controller
{

    /**
     * Recibe un texto de entrada y se busca en la base de datos.
     *  Si YA existe mostramos la ventana con la entrada y con el listado de acepciones
     *  Si NO existe mostramos la ventana con la entrada y con el formulario para añadir la primera acepción
     *
     * @author  Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function dpGetEntradaGET(Request $request)
    {
        // Obtenemos la entrada del POST
        $entrada_id = $request->entrada_id;

        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        $entrada = dpEntrada_GetEntradaById($entrada_id);

        // Si YA existe mostramos la ventana con la entrada y con el listado de acepciones
        if ($entrada) {

            // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
            $this->authorize('isOwner', $entrada);

            $datos->modo = 'MOSTRAR_ENTRADA';
            $datos->entrada = $entrada;
            $datos->confirm = dpEntrada_GetConfirm($entrada);

            $persona_id = getSessionPersona()['id'];
            $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);
            $datos->diccionario = $diccionario;
            $letraInicial = ' '. mb_strtoupper(substr( $entrada->entrada , 0, 1 ));
            $inicialUrl = mb_strtolower(substr( $entrada->entrada , 0, 1 ));

            // Migas de pan
            $datos->breadcrumb = [
                ['name' => __('diccionario.Inicio'), 'url' => url('/') ],
                ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],
                ['name' => __('diccionario.Busqueda'), 'url' => route('personal.consulta.all')],
                ['name' => __('diccionario.Por_Letra').$letraInicial , 'url' => route('personal.consulta.byInitial', ['letra'=>$inicialUrl ])],

                ['name' => $entrada->entrada, 'url' => $request->url],
            ];

            return view('diccionario/dpEntrada/masterEntrada')->with('datos', $datos);
        } else {
            // Si NO existe mostramos la ventana con la entrada y con el formulario para añadir la primera acepción
            $datos->modo = 'BUSCADOR_ENTRADA';
            $mensaje =  __('diccionario.URLnoExiste');
            return redirect()->route('entrada.buscador')->withErrors([$mensaje . $entrada_id]);
        }
    }

    /**
     * Borra la entrada en la base de datos
     *  Se comprueba si la entrada se ha enviado
     *      Si no se ha enviado se borra del todo
     *      Si se ha enviado y el envío está pendiente del profe se hace softdelete y se muestra un mensaje success
     *      Si se ha enviado y el envío se ha publicado no se borra y se muestra mensaje de error indicando que se ha publicado
     *      Si se ha enviado y es otro el caso se da como error y se muestra mensaje de error genérico
     *  Se llama desde dpEntradaEntrada.blade.php
     * 
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void Redirecciona al diccionario perosnal o a la entrada , si hay un erro muestra un 403

     */
    public function dpDeleteEntradaGET(Request $request)
    {
        // Obtenemos la entrada del GET
        $entrada_id = $request->entrada_id;
        $entrada = dpEntrada_GetEntradaById($entrada_id);

        $diccionario_id = $entrada->dic_personal_id;

        // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
        $this->authorize('isOwner', $entrada);

        $resultado = dpEntrada_DeleteEntradaById($entrada_id);
        switch ($resultado) {
            case config('ctes.resultados.entrada_borrada'):
                $mensaje =  __('diccionario.entrada_borrada') . ': "' . $entrada->entrada . '"';
                // return redirect()->route('entrada.buscador')->with('success', $mensaje);
                return redirect()->route('personal.consulta.all')->with('success', $mensaje);

            case config('ctes.resultados.entrada_publicada'):
                $mensaje =  __('diccionario.entrada_publicada').'. '.__('diccionario.entrada_publicada_diccionario');
                return redirect()->route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $entrada_id])->withErrors([$mensaje]);

            case config('ctes.resultados.error_entrada_previamente_borrada'):
                $mensaje =  __('diccionario.URLmala');
                return redirect()->route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $entrada_id])->withErrors([$mensaje]);

            case config('ctes.resultados.resultados.error_generico'):
            default:
                $mensaje =  __('diccionario.ERROR');
                abort(403, 'Acción no permitida.');
                return;
        }
    }

    /**
     * Muestra la ventana con el buscador de entrada para dar una entrada de alta
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @return void
     */
    public function dpCreateEntradaGET(Request $request)
    {
        $datos = new \stdClass();
        $datos->modo = 'BUSCADOR_ENTRADA';

        dpEntrada_datosComunesCosultas($datos);

        // Migas de pan
        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],
            ['name' => __('diccionario.entrada_anadir'), 'url' => $request->url],
        ];

        return view('diccionario/dpEntrada/masterEntrada')->with('datos', $datos);
    }

    /**
     * Viene del buscador de entradas. El usuario ha escrito una entrada y ha pulsado en crear
     *  Si YA existe la entrada mostramos la ventana con la entrada y con el listado de acepciones
     *  Si NO existe la entrada mostramos la ventana con la entrada y con el formulario para añadir la primera acepción
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function dpInsertEntradaGET(Request $request)
    {
        // Validamos los parámetros de entrada
        
        $validator = Validator::make($request->all(), [
            'entrada_entrada' => 'required|max:' . config('ctes.constantes_entradas.max_entrada'),
        ]);
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        // elimina espacios prnicipio y final, espacios dobles...
        $entrada_entrada = normalizarEntrada($request->entrada_entrada);

        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();
        // dpEntrada_datosComunesCosultas($datos);

        // Buscamos la entrada para ver si existe
        $entrada = dpEntrada_GetEntradaByEntradaByUserActual($entrada_entrada);

        // Si YA existe mostramos la ventana con la entrada y con el listado de acepciones
        if ($entrada) {

            // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
            $this->authorize('isOwner', $entrada);

            $diccionario_id = $entrada->dic_personal_id;

            $mensaje =  __('diccionario.entrada_existe_1') . '"' . $entrada->entrada . '"' . __('diccionario.entrada_existe_2');
            return redirect()->route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $entrada->id])
                ->with('acepcion_info', $mensaje);
        } else {
            // Si NO existe mostramos la ventana con la entrada y con el formulario para añadir la primera acepción

            $entrada = new DiccionarioPersonalEntrada;
            $entrada->entrada = $entrada_entrada;

            $acepcion = new DiccionarioPersonalAcepcion;
            $acepcion->orden = 1; // Se va a crear la primera acpción de la entrada

            $listaCategorias = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.categoria'));
            $listaGeneros = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.genero'));
            $listaNumeros = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.numero'));
            $listaIdiomas = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.idioma'));
            $listaTematicas = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.tematica'));

            $datos->modo = 'CREAR_ENTRADA';
            $datos->entrada = $entrada;
            $datos->confirm = dpEntrada_GetConfirm($entrada);
            $datos->acepcion = $acepcion;
            $datos->listaCategorias = $listaCategorias;
            $datos->listaGeneros = $listaGeneros;
            $datos->listaTematicasDisponibles = $listaTematicas;
            $datos->listaNumeros = $listaNumeros;
            $datos->listaIdiomas = $listaIdiomas;
            $datos->mensaje = __('diccionario.acepcion_creacion');

            // Migas de pan
            $datos->breadcrumb = [
                ['name' => __('diccionario.Inicio'), 'url' => url('/')],
                ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],
                ['name' => __('diccionario.acepcion_creacion').' para "'.$entrada->entrada.'"', 'url' => $request->url],
            ];
            

            return view('diccionario/dpEntrada/masterEntrada')->with('datos', $datos);
        }
    }

    /**
     * Buscador de entradas. 
     *
     * @access public
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector

     */
    public function dpBuscarEntradaPOST(Request $request)
    {
        // Validamos los parámetros de entrada
        $validator = Validator::make($request->all(), [
            'entrada_entrada' => 'max:' . config('ctes.constantes_entradas.max_entrada'),
            // esto es opcional para filtrar tambien por tematica/etiquetas
            'list_tematica_id' => 'array',
            'list_tematica_id.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        // Obtenemos la entrada del POST
        $entrada_entrada = normalizarEntrada( $request->entrada_entrada );

        $datos = new \stdClass();
        dpEntrada_datosComunesCosultas($datos);

        // Buscamos la entrada para ver si existe, si No existe mostramos el boton "crear entrada $entrada_entrada" 
        $entrada = dpEntrada_GetEntradaByEntradaByUserActual($entrada_entrada, $request->list_tematica_id);
        $noExisteEntrada = false;
        // $mensaje = __('diccionario.buscar_coincide');
        if (!$entrada) {
            $noExisteEntrada = true;
            // $mensaje = __('diccionario.buscar_noencontrado');
        }

        if (emptyOrNull($entrada_entrada) && emptyOrNull($request->tematicas_ids)) {
            return redirect()->back()->withErrors(__('diccionario.buscador_sindatos'));
        }
        if ( emptyOrNull($entrada_entrada) ) {
            return redirect()->route('personal.consulta.tematica', [
                'tematicasIds' => $request->tematicas_ids
            ]);
        } elseif ( emptyOrNull($request->tematicas_ids) ) {
            return redirect()->route('personal.consulta.palabra', [
                'consulta' => $entrada_entrada,
            ])->with('noExisteEntrada', $noExisteEntrada);
        } else {
            return redirect()->route('personal.consulta.palabra.tematica', [
                'consulta' => $entrada_entrada,
                'tematicasIds' => $request->tematicas_ids
            ]);
        }
        // Redirecionamos a la consulta con la busqueda de la entrada
        // return redirect()->route('personal.consulta.palabra', [
        //         'consulta' => $entrada_entrada,
        //         'list_tematica_id' => $request->list_tematica_id??null,
        //         // 'datos' => $datos,
        //     ])
        //     ->with('noExisteEntrada', $noExisteEntrada)
        //     // ->with('success', $mensaje)
        ;
    }

    /**
     * Muestra la ventana de edición del texto de una entrada.
     *  Se llama desde dpEntradaEntrada.blade.php
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return view masterEntrada con la opcion EDITAR_ENTRADA
     */
    public function dpEditEntradaGET(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        // Obtenemos la entrada del POST
        $entrada_id = $request->entrada_id;

        // Buscamos la entrada para ver si existe
        $entrada = dpEntrada_GetEntradaById($entrada_id);

        if ($entrada) {
            // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
            $this->authorize('isOwner', $entrada);

            $datos->modo = 'EDITAR_ENTRADA';
            $datos->entrada = $entrada;
            $datos->confirm = dpEntrada_GetConfirm($entrada);

            // Migas de pan
            $datos->breadcrumb = [
                ['name' => __('diccionario.Inicio'), 'url' => url('/')],
                ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],
                ['name' => $entrada->entrada, 'url' => $request->url],
            ];

            return view('diccionario/dpEntrada/masterEntrada')->with('datos', $datos);
        }

        $mensaje =  __('diccionario.URLmala');
        return redirect()->route('entrada.buscador')->withErrors([$mensaje]);
    }

    /**
     * Recibe una entrada de la pantalla y manda guardar la modificación en la base de datos
     *  Si el nuevo texto de la entrada ya existe devuelvo error
     *  Si el nuevo texto de la entrada no existe, guardo la modificación de la entrada
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void|\Illuminate\Http\RedirectResponse
     */
    public function dpEditEntradaPOST(Request $request)
    {
        // Validamos los parámetros de entrada
        $validator = Validator::make($request->all(), [
            'entrada_id' => 'required|integer',
            'entrada_entrada' => 'required|max:' . config('ctes.constantes_entradas.max_entrada'),
        ]);

        if ($validator->fails()) {
            //return redirect('post/create')
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        // Obtenemos la entrada del POST
        $entrada_id = $request->entrada_id;
        $entrada_entrada = $request->entrada_entrada;

        // Buscamos la entrada para ver si existe
        $entrada = dpEntrada_GetEntradaById($entrada_id);

        if ($entrada) {

            // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
            $this->authorize('isOwner', $entrada);

            $entrada_entrada = normalizarEntrada( $entrada_entrada );

            $entradaConNuevoNombre = dpEntrada_GetEntradaByEntradaByUserActual($entrada_entrada);
            if ($entradaConNuevoNombre && $entradaConNuevoNombre->id != $entrada->id) {
                // Si existe una entrada con el nuevo nombre y no es la que estoy editando, doy error porque ya existe la entrada
                $mensaje =  __('diccionario.entrada_existe');
                return redirect()
                    ->back()
                    ->withErrors([$mensaje])
                    ->withInput();
            }

            $entrada->entrada = $entrada_entrada;

            $entrada = dpEntrada_Edit($entrada);

            $diccionario_id = $entrada->dic_personal_id;

            // Mostramos la entrada con la lista de acepciones
            return redirect()->route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $entrada->id]);
        }

        $mensaje =  __('diccionario.URLmala');
        return redirect()->route('entrada.buscador')->withErrors([$mensaje]);
    }

    /**
     * Oculta o muestra una entrada del diccionario personal
     *  Se llama desde partials\consulta\entrada.blade.php
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function dpOcultarEntradaGET(Request $request)
    {
        // Obtenemos la entrada del POST
        $entrada_id = $request->entrada_id;

        // Buscamos la entrada para ver si existe
        $entrada = dpEntrada_GetEntradaById($entrada_id);

        if ($entrada) {
            // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
            $this->authorize('isOwner', $entrada);

            // Cambiamos el estado de oculta <==> visible
            if ($entrada->estado == config('ctes.estados_entrada.oculta')) {
                // Está oculta y lo ponemos visible
                $entrada->estado = config('ctes.estados_entrada.visible');
                $mensaje =  __('diccionario.entrada_visible') . ': ' . $entrada->entrada;
            } else {
                // Está ponemos visible y lo oculta
                $entrada->estado = config('ctes.estados_entrada.oculta');
                $mensaje =  __('diccionario.entrada_ocultada') . ': ' . $entrada->entrada;
            }

            // Oculta la entrada
            $entrada = dpEntrada_Edit($entrada);

            return redirect()->back()->with('success', $mensaje);
        }

        $mensaje =  __('diccionario.URLmala');
        return redirect()->route('entrada.buscador')->withErrors([$mensaje]);
    }


    // ********************************************************************************************  ACEPCION

    /**
     * Recibe una entrada de la pantalla y manda guardar la nueva acepción en la base de datos
     *  Guarda los medios
     * Si la entrada no existe se da de alta
     * Cuando termina, redirige a la vista mostrando la entrada con la lista de acepciones
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function dpInsertAcepcionPOST(Request $request)
    {

        // Validamos los parámetros de entrada
        $validator = Validator::make($request->all(), [
            'entrada_entrada' => 'required|max:' . config('ctes.constantes_entradas.max_entrada'),
            'definicion' => 'required|max:' . config('ctes.constantes_acepciones.max_definicion'),
            'frase_ejemplo' => 'max:' . config('ctes.constantes_acepciones.max_frase'),
            'orden' => 'required|integer',
        ], [
            'definicion.required'=> __('diccionario.campo_definicion'),

        ]);

        if ($validator->fails()) {
            if($request->grabacion){
                $mensaje = $validator->errors();
                return response()->json([
                    'ruta' => URL::previous(),
                    'error' => $mensaje
                ],200);
            }
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }
        
         // Obtenemos la entrada del POST
         $entrada_entrada = normalizarEntrada( $request->entrada_entrada );

         $listaTematicasSeleccionadas = $request->listaTematicas;
 
         // Buscamos la entrada para ver si existe
         $entrada = dpEntrada_GetEntradaByEntradaByUserActual($entrada_entrada);
 
         // Si NO existe damos de alta la entrada
         if (!$entrada) {
             $entrada = dpEntrada_Add($entrada_entrada);
         }
        $diccionario_id = $entrada->dic_personal_id;
       
        // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
        $this->authorize('isOwner', $entrada);

        // Obtenemos el orden real. El que se presentó en la pantalla era provisional, porque el usaario puede haber guardado otra entrada en otra ventana.
        $orden = dpEntrada_GetSiguienteOrdenAcepcionByEntrada($entrada);

        // Creamos el objeto donde vamos a guardar todo lo necesario para pasárselo al helper y allí dar de alta la acepción
        $acepcion = new DiccionarioPersonalAcepcion;
        $acepcion->dic_entrada_id = $entrada->id;
        $acepcion->orden = $orden;
        $acepcion->cat_gramatical_id = $request->cat_gramatical_id;
        $acepcion->genero_id = $request->genero_id;
        $acepcion->numero_id = $request->numero_id;
        $acepcion->tipologia_id = $request->tipologia_id;
        $acepcion->idioma_id = $request->idioma_id;
        $acepcion->idioma_palabra = $request->idioma_palabra;
        $acepcion->definicion = $request->definicion;
        $acepcion->frase_ejemplo = $request->frase_ejemplo;
        $acepcion->ejemplo2 = $request->ejemplo2;
        $acepcion->estado = config('ctes.estados.activo');

        // dd(
        //     '$acepcion', $acepcion,
        //     '$request', $request,
        // );

        try {
            // Guardamos la acepción
            $acepcion = dpAcepcion_Add($acepcion);
        
            // Guardar las tematicas
            dpAcepcionTematicas_Actualizar($acepcion, $listaTematicasSeleccionadas);

            // Asociamos la acepción a la entrada
            $acepcion->dpEntrada()->associate($entrada);

            // Guardamos los medios
            if ($request->hasFile('medio_imagen')) {
                if ($request->medio_imagen->isValid()) {
                    $file = $request->medio_imagen;
                    $acepcionMedioImagen = dpMedio_TratarMedio($file, config('ctes.tipos_medios.imagen'), $entrada->id, $acepcion->id);
                }
            }

            if ($request->hasFile('medio_audio')) {
                if ($request->medio_audio->isValid()) {
                    $file = $request->medio_audio;
                    $acepcionMedioAudio = dpMedio_TratarMedio($file, config('ctes.tipos_medios.audio'), $entrada->id, $acepcion->id);
                }
            }

            if ($request->hasFile('medio_video')) {
                if ($request->medio_video->isValid()) {
                    // Grabamos el fichero en el STORAGE
                    $file = $request->medio_video;
                    $acepcionMedioVideo = dpMedio_TratarMedio($file, config('ctes.tipos_medios.video'), $entrada->id, $acepcion->id);
                }
            }
        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_base_error'),
                ['file' => __FILE__, 'line' => __LINE__],                
                PHP_EOL . json_encode($request->all(), JSON_PRETTY_PRINT),
                $th->getMessage()
            );
        }

        
        
        // Mostramos la entrada con la lista de acepciones
        $mensaje =  __('diccionario.entrada_acepcion_anadida') . $entrada->entrada;
        if($request->grabacion){
            return response()->json([
                'ruta' => route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $entrada->id]),
                'mesg' => $mensaje
            ],200);
        }
        return redirect()
            ->route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $entrada->id])
            ->with('acepcion_success', $mensaje);
    }

    /**
     * Guarda en base de datos los datos de una acepción que recibe de la pantalla
     * Cuando termina, redirige a la vista mostrando la entrada con la lista de acepciones
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function dpEditAcepcionPOST(Request $request)
    {

        // Validamos los parámetros de entrada
        $validator = Validator::make($request->all(), [
            'definicion' => 'required|max:' . config('ctes.constantes_acepciones.max_definicion'),
            'frase_ejemplo' => 'max:' . config('ctes.constantes_acepciones.max_frase'),
            'orden' => 'required|integer',
        ], [
            'definicion.required'=> __('diccionario.campo_definicion'),

        ]);
        
        if ($validator->fails()) {
            if($request->grabacion){
                $mensaje = $validator->errors();
                return response()->json([
                    'ruta' => URL::previous(),
                    'error' => $mensaje
                ],200);
            }
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        // Si estamos en modo EDITAR
        $entrada = dpEntrada_GetEntradaById($request->entrada_id);
        $acepcion = dpAcepcion_GetAcepcionById($request->acepcion_id);
        $listaTematicasSeleccionadas = $request->listaTematicas;

        $diccionario_id = $entrada->dic_personal_id;

        // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
        $this->authorize('isOwner', $entrada);

        // Creamos el objeto donde vamos a guardar todo lo necesario para pasárselo al helper y allí Editar la acepción
        $acepcion = new DiccionarioPersonalAcepcion;
        $acepcion->id = $request->acepcion_id;
        $acepcion->dic_entrada_id = $request->entrada_id;
        $acepcion->orden = $request->orden;
        $acepcion->cat_gramatical_id = $request->cat_gramatical_id;
        $acepcion->genero_id = $request->genero_id;
        $acepcion->numero_id = $request->numero_id;
        $acepcion->idioma_id = $request->idioma_id;
        $acepcion->idioma_palabra = $request->idioma_palabra;
        $acepcion->definicion = $request->definicion;
        $acepcion->frase_ejemplo = $request->frase_ejemplo;
        $acepcion->ejemplo2 = $request->ejemplo2;
        $acepcion->estado = config('ctes.estados.activo');

        // Guardamos la acepción
        $acepcion = dpAcepcion_Edit($acepcion);

        // Guardar las tematicas
        dpAcepcionTematicas_Actualizar($acepcion, $listaTematicasSeleccionadas);

        

        // Guardamos los medios
        if ($request->hasFile('medio_imagen')) {
            if ($request->medio_imagen->isValid()) {
                $file = $request->medio_imagen;
                $acepcionMedioImagen = dpMedio_TratarMedio($file, config('ctes.tipos_medios.imagen'), $entrada->id, $acepcion->id);
            }
        }

        if ($request->hasFile('medio_audio')) {
            if ($request->medio_audio->isValid()) {
                $file = $request->medio_audio;
                $acepcionMedioAudio = dpMedio_TratarMedio($file, config('ctes.tipos_medios.audio'), $entrada->id, $acepcion->id);
            }
        }

        if ($request->hasFile('medio_video')) {
            if ($request->medio_video->isValid()) {
                // Grabamos el fichero en el STORAGE
                $file = $request->medio_video;
                $acepcionMedioVideo = dpMedio_TratarMedio($file, config('ctes.tipos_medios.video'), $entrada->id, $acepcion->id);
            }
        }
        if($request->grabacion){
            return response()->json([
                'ruta' => route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $entrada->id]),
            ],200);
        }
        // Mostramos la entrada con la lista de acepciones
        return redirect()->route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $request->entrada_id]);
    }

    /**
     * Sube la acepción en la lista, es decir, que disminuye en uno el campo orden
     * Cuando termina, redirige a la vista mostrando la entrada con la lista de acepciones
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function dpUpAcepcionGET(Request $request)
    {
        // Obtengo la acepción anterior y la actual
        $acepcionActual = dpAcepcion_GetAcepcionById($request->acepcion_id);
        $acepcionActualOrden = $acepcionActual->orden;
        $acepcionAnterior = dpAcepcion_GetAcepcionAnteriorById($request->acepcion_id);

        $diccionario_id = $acepcionActual->dpentrada->dic_personal_id;

        // // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
        // $this->authorize('isOwner', $entrada);

        // Modificar la acepción Actual
        dpAcepcion_SetAcepcionOrdenByAcepcion($acepcionActual, $acepcionAnterior->orden);

        // Modificar la acepción Anterior
        dpAcepcion_SetAcepcionOrdenByAcepcion($acepcionAnterior, $acepcionActualOrden);

        // Mostramos la entrada con la lista de acepciones
        return redirect()->route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $request->entrada_id]);
    }

    /**
     * Baja la acepción en la lista, es decir, que aumenta en uno el campo orden
     * Cuando termina, redirige a la vista mostrando la entrada con la lista de acepciones
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function dpDownAcepcionGET(Request $request)
    {
        // Obtengo la acepción siguiente y la actual
        $acepcionActual = dpAcepcion_GetAcepcionById($request->acepcion_id);
        $acepcionActualOrden = $acepcionActual->orden;
        $acepcionSiguiente = dpAcepcion_GetAcepcionSiguienteById($request->acepcion_id);

        $diccionario_id = $acepcionActual->dpentrada->dic_personal_id;

        // Modificar la acepción Actual
        dpAcepcion_SetAcepcionOrdenByAcepcion($acepcionActual, $acepcionSiguiente->orden);

        // Modificar la acepción Siguiente
        dpAcepcion_SetAcepcionOrdenByAcepcion($acepcionSiguiente, $acepcionActualOrden);

        // Mostramos la entrada con la lista de acepciones
        return redirect()->route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $request->entrada_id]);
    }

    /**
     * Muestra la pantalla para crear una nueva acepción
     *  Se llama desde el link Añadir acepción de dpEntradaEntrada.blade
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function dpCreateAcepcionGET(Request $request)
    {
        // Obtenemos la entrada del GET
        $entrada_id = $request->entrada_id;

        $entrada = dpEntrada_GetEntradaById($entrada_id);

        // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
        $this->authorize('isOwner', $entrada);

        $acepcion = new DiccionarioPersonalAcepcion;
        $acepcion->orden = dpEntrada_GetSiguienteOrdenAcepcionByEntrada($entrada);

        $listaCategorias = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.categoria'));
        $listaGeneros = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.genero'));
        $listaNumeros = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.numero'));
        $listaIdiomas = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.idioma'));
        $listaTematicas = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.tematica'));

        // Mostramos la entrada con la lista de acepciones
        $datos = new \stdClass();
        $datos->modo = 'ANADIR_ACEPCION';
        $datos->entrada = $entrada;
        $datos->confirm = dpEntrada_GetConfirm($entrada);
        $datos->acepcion = $acepcion;
        $datos->listaCategorias = $listaCategorias;
        $datos->listaGeneros = $listaGeneros;
        $datos->listaTematicasDisponibles = $listaTematicas;
        $datos->listaNumeros = $listaNumeros;
        $datos->listaIdiomas = $listaIdiomas;
        $datos->mensaje = __('diccionario.acepcion_creacion');

        // Migas de pan
        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],
            ['name' => $entrada->entrada, 'url' => route('entrada.get', ['diccionario_id' => $entrada->dic_personal_id, 'entrada_id' => $entrada->id])],
            ['name' => __('diccionario.acepcion_orden') .' '. $acepcion->orden, 'url' => $request->url],
        ];

        return view('diccionario/dpEntrada/masterEntrada')->with('datos', $datos);
    }

    /**
     * Muestra la ventana de edición de una entrada.
     * Se llama desde la lista de acepciones de una entrada
     * Cuando termina, redirige a la vista mostrando la entrada con la lista de acepciones
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function dpEditAcepcionGET(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        // Obtenemos la entrada del POST
        $entrada_entrada_id = $request->entrada_id;
        $entrada_acepcion_id = $request->acepcion_id;

        // Buscamos la entrada para ver si existe
        $entrada = dpEntrada_GetEntradaById($entrada_entrada_id);
        if ($entrada) {

            // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
            $this->authorize('isOwner', $entrada);

            $acepcion = $entrada->dpAcepciones()->find($entrada_acepcion_id);
        }

        // Si YA existe mostramos la ventana con la entrada y con el listado de acepciones
        if ($entrada && $acepcion) {

            $acepcion->atributos = $acepcion->getAtributos();

            $listaCategorias = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.categoria'));
            $listaGeneros = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.genero'));
            $listaNumeros = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.numero'));
            $listaIdiomas = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.idioma'));
            $listaTematicas = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.tematica'));

            // Mandamos como Disponibles las temáticasque no están ya seleccionadas
            $listaTematicasSeleccionadas = $acepcion->dpAcepcionTematicas;
            $listaTematicasDisponibles = $listaTematicas->diff($listaTematicasSeleccionadas)->all();

            $datos->modo = 'ANADIR_ACEPCION';
            $datos->entrada = $entrada;
            $datos->confirm = dpEntrada_GetConfirm($entrada);
            $datos->acepcion = $acepcion;
            $datos->listaCategorias = $listaCategorias;
            $datos->listaGeneros = $listaGeneros;
            $datos->listaTematicasDisponibles = $listaTematicasDisponibles;
            $datos->listaTematicasSeleccionadas = $listaTematicasSeleccionadas;
            $datos->listaNumeros = $listaNumeros;
            $datos->listaIdiomas = $listaIdiomas;
            $datos->mensaje = __('diccionario.acepcion_edicion');

            // Migas de pan
            $datos->breadcrumb = [
                ['name' => __('diccionario.Inicio'), 'url' => url('/')],
                ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],
                ['name' => $entrada->entrada, 'url' => route('entrada.get', ['diccionario_id' => $entrada->dic_personal_id, 'entrada_id' => $entrada->id])],
                ['name' => $acepcion->orden, 'url' => $request->url],
            ];

            return view('diccionario/dpEntrada/masterEntrada')->with('datos', $datos);
        }

        $mensaje =  __('diccionario.URLmala');
        return redirect()->route('entrada.buscador')->withErrors([$mensaje]);
    }

    /**
     * Se borra en base de datos una acepción
     * Se llama desde la lista de acepciones de una entrada
     * Si es la última acepción se borra la entrada. A no ser que la entrada esté publicada, en este caso no se puede borrar la entrada, ni la acpción, porque no se puede dejar una entrada sin acepciones
     * Cuando termina, redirige a la vista mostrando la entrada con la lista de acepciones
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function dpDeleteAcepcionGET(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        // Obtenemos la entrada del POST
        $entrada_id = $request->entrada_id;
        $acepcion_id = $request->acepcion_id;

        // Buscamos la entrada para ver si existe
        $entrada = dpEntrada_GetEntradaById($entrada_id);
        if ($entrada) {

            // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
            $this->authorize('isOwner', $entrada);

            $acepcion = $entrada->dpAcepciones()->find($acepcion_id);
        }

        if ($entrada && $acepcion) {

            $diccionario_id = $entrada->dic_personal_id;

            // Si es la última acepción se borra toda la entrada.
            if ($entrada->dpAcepciones()->count() == 1) {
                // dpEntrada_DeleteEntradaById($entrada_id);

                // Si la entrada está publicada, no se puede borrar la entrada, y por tanto, tampoco la última acepción
                //  Se comprueba si la entrada se ha enviado
                //      Si no se ha enviado se borra del todo
                //      Si se ha enviado y el envío está pendiente del profe se hace softdelete
                //      Si se ha enviado y el envío se ha publicado no se borra
                //      Si se ha enviado y es otro el caso se da como error

                $resultado = dpEntrada_DeleteEntradaById($entrada_id);
                switch ($resultado) {                    
                    case config('ctes.resultados.entrada_borrada'):
                        
                        $mensaje =  __('diccionario.entrada_borrada') . ': ' . $entrada->entrada;
                        return redirect()->route('entrada.buscador')->with('success', $mensaje);

                    case config('ctes.resultados.entrada_publicada'):
                        $mensaje =  __('diccionario.entrada_publicada');
                        return redirect()->route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $entrada_id])->withErrors([$mensaje]);

                    case config('ctes.resultados.error_entrada_previamente_borrada'):
                        $mensaje =  __('diccionario.URLmala');
                        return redirect()->route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $entrada_id])->withErrors([$mensaje]);

                    case config('ctes.resultados.resultados.error_generico'):
                    default:
                        $mensaje =  __('diccionario.ERROR');
                        abort(403, 'Acción no permitida.');
                        break;
                }

                $datos->modo = 'BUSCADOR_ENTRADA';
                return view('diccionario/dpEntrada/masterEntrada')->with('datos', $datos);
            } else {
                dpAcepcion_DeleteAcepcionByAcepcion($acepcion);

                $datos->modo = 'MOSTRAR_ENTRADA';
                $datos->entrada = $entrada;
                $mensaje = __('diccionario.acepcion_borrada');
                return redirect()
                    ->route('entrada.get', ['diccionario_id' => $diccionario_id, 'entrada_id' => $entrada_id])
                    ->withErrors($mensaje)
                    ;
            }
        }

        $mensaje =  __('diccionario.URLmala');
        return redirect()->route('entrada.buscador')->withErrors([$mensaje]);
    }

    /**
     * Se borra en base de datos un medio de una acepción
     * Se llama desde la edición de una acepción
     * Cuando termina, regresa a la pantalla de edición de acepciçon
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function dpDeleteMedioGET(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        // Obtenemos la entrada del POST
        $entrada_id = $request->entrada_id;
        $acepcion_id = $request->acepcion_id;
        $medio_id = $request->medio_id;

        // Buscamos la entrada para ver si existe
        $entrada = dpEntrada_GetEntradaById($entrada_id);
        if ($entrada) {

            // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
            $this->authorize('isOwner', $entrada);

            $acepcion = $entrada->dpAcepciones()->find($acepcion_id);
        }
        if ($entrada && $acepcion) {
            $medio = $acepcion->dpAcepcionMedios()->find($medio_id);
        }

        if ($entrada && $acepcion && $medio) {

            // Borramos el fichero
            dpMedio_DeleteMedio($medio);

            $diccionario_id = $entrada->dic_personal_id;

            $datos->modo = 'MOSTRAR_ENTRADA';
            $datos->entrada = $entrada;
            
            return redirect()->route('acepcion.edit', ['diccionario_id' => $diccionario_id, 'entrada_id' => $entrada_id, 'acepcion_id' => $acepcion_id]);
        }

        $mensaje =  __('diccionario.URLmala');
        return redirect()->route('entrada.buscador')->withErrors([$mensaje]);
    }

    /**
     * Este método devolverá una vista que mostrará la opción de cambiar el nombre del diccionario y el Avatar.
     *
     * @author José Carlos Trillo <josecarlos.trillo@altia.es>
     * @version Release: <package_version>
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function dpModificarDiccionarioGET(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        // Obtenemos el id de persona del usuario conectado y el avatar que tenga definido.
        $persona_id = getSessionPersona()['id'];
        $avatar     = getSessionPersona()['avatar_URL'];

        // Busco el diccionario del usuario actual
        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);

        // Cargamos los avatares del directorio de avatares
        $avatarListFiles = [];
        $directorio_avatares_oficiales = storage_path('app') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . config('ctes.path_medios.avatares');

        // Sólo vamos a ofrecer como avatares válidos aquellos que tengan extensión .svg
        $archivos = glob($directorio_avatares_oficiales . DIRECTORY_SEPARATOR . '*.svg');
        // $ruta = '/var/www/'. $directorio_avatares_oficiales. '/*.svg';
        // ../storage/avatares
        $ruta = $directorio_avatares_oficiales. '/' . '*.svg';
        $archivos = glob($ruta );

        foreach ($archivos as $archivo) {
            // Limpiamos los nombres para que queden con el formato nombre.ext
            array_push($avatarListFiles, basename($archivo));
        }

        // dd( $request, 
        //     session()->get('_previous')['url'],
        //     $request->server('HTTP_REFERER')
        // );
        session(['formBackUrl' => $request->server('HTTP_REFERER') ]);
        $datos->formBackUrl = $request->server('HTTP_REFERER');

        $datos->breadcrumb = [
            ['name' => __('diccionario.diccionario_personal'),  'url' => url('/')],
            ['name' => __('diccionario.title_diccionario-modificar-ver'), 'url' => $request->url()],
        ];

        $datos->persona_id      = $persona_id;
        $datos->diccionario     = $diccionario;
        $datos->lista_avatares  = $avatarListFiles;
        $datos->avatar          = $avatar;

        return view('diccionario/dpDiccionario/dpModificacion')->with('datos', $datos);
    }

    /**
     * Este método recibirá los datos de una persona (nombre de diccionario y avatar) y procederá con el cambio en la BBDD y la Session.
     *
     * @author José Carlos Trillo <josecarlos.trillo@altia.es>
     * @version Release: <package_version>
     *
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function dpSaveModificarDiccionarioPOST(Request $request)
    {

        // TODO: Pendiente ver si se le pasa un validador a los datos de entrada.
        // Validamos los parámetros de entrada
        /*
            $validator = Validator::make($request->all(), [
                'nombre_diccionario' => 'required',
                'avatar_nuevo' => 'required',
            ]);
        */

        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];

        if (!(empty($request->nombre_diccionario))) {
            $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);
            $diccionario->titulo = $request->nombre_diccionario;
            $diccionario->save();
        }

        if (!(empty($request->avatar_nuevo))) {
            $persona = Persona::where('id', $persona_id)->first();
            $persona->avatar_URL = $request->avatar_nuevo;
            $persona->save();

            // Tras guardar el avatar en la BBDD, lo cambiamos también en la Session para que se vean los cambios ya.
            \Session(['userData.persona.avatar_URL' => $request->avatar_nuevo]);
        }

        $mensaje =  __('diccionario.guardado_ok');

        // si el form incluye la direcion de vuelta ir a esa y si no a inicio diccionario personal
        if ($request->formBackUrl) 
            return redirect($request->formBackUrl)->with('success', $mensaje);
        else 
            return redirect()->route('diccionariopersonal.get')->with('success', $mensaje);
        
    }

    /**
     * Pagina Inicial Diccionario Personal
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version Release: <package_version>
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector diccionario/personal
     */
    public function dpHome(Request $request)
    {
        // si es admin o super admir redirigir a administracion 
        // dd(
        //     auth()->user(),
        //     usuarioEsAdministrador(auth()->user())
        // );
        if( usuarioEsAdministrador(auth()->user()) ){
            // dd('admin');
            return redirect('/admin');
        }

        // si falta informacion de session redirigir a logout para volver a login

        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();
        $datos->tabs = getDatosTabs();
        $datos->nentradas = dpEntrada_GetNumEntradasByUserActual();
        try {
            $datos->diccionario = dpDiccionario_GetDiccionarioByUserActual();    
        } catch (\Throwable $th) {
            // dd($datos);
            // return redirect()->route('cas.logout', ['#'=>'#']);
            // return redirect('cas.logout');
            // return redirect()->route('cas.logout', ['test'=>'tal']) ;
            return redirect()->route('cas.login');
            
        }

        dpEntrada_datosComunesCosultas($datos);

        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.diccionario_personal'), 'url' => $request->url()],
        ];

        return view('diccionario/personal')->with('datos', $datos);
    }

    /**
     * Muestra listado con todas las entradas del diccionario
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version Release: <package_version>
     *
     * @param Request $request
     *
     * @return view diccionario/consulta
     */
    public function dpGetEntradaAllGET(Request $request)
    {
        $datos = new \stdClass();

        $datos->entradas = dpEntrada_GetEntradasByUserActual();
        $datos->listado = [];
        foreach ($datos->entradas as $entrada) {
            $item = new \stdClass();
            $item->entrada = $entrada;
            $item->confirm = dpEntrada_GetConfirm($entrada);
            $datos->listado[] = $item;
        }
        dpEntrada_datosComunesCosultas($datos);
        // $datos->count = count($datos->entradas);
        // if ($datos->entradas->count() == 0) {
        //     $noResultados = true;
        // }
        // $datos->consulta = "Para este diccionario";

        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],
            ['name' => __('diccionario.Busqueda'), 'url' => $request->url],
        ];

        return view('diccionario/consulta')->with('datos', $datos);
    }


    /**
     * Muestra listado con todas las entradas del diccionario via ajax
     *
     * @version Release: <package_version>
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     *
     * @param Request $request
     *
     * @return view diccionario/conusltaAjax
     */
    public function dpGetEntradasAjax(Request $request)
    {
        $allParams = array_merge($request->route()->parameters, $request->toArray());
        // dd($request, $request->tematicasIds);
        $offset = $request->offset;
        $consulta = $request->consulta;
        $tematicas_ids = $request->tematicasIds;
        $datos = new \stdClass();

        if ( array_key_exists('tematicasIds', $allParams) && 
            !emptyOrNull($allParams['tematicasIds']) ) {
            $datos->list_tematica_id = explode(',',$allParams['tematicasIds']);
            $datos->tematicasIds = $allParams['tematicasIds'];
        } else {
            $datos->list_tematica_id = $request->list_tematica_id;
        }

        if ($consulta) {
            $datos->consulta = $consulta;
            $datos->entradas = dpEntrada_GetEntradasByUserActualConsulta($consulta, $offset);
        } elseif($tematicas_ids) {
            // dd($tematicas_ids);
            // $datos->entradas = dpEntrada_GetEntradasByUserActualConsulta($consulta, $offset);
            $datos->entradas = dpEntrada_GetEntradasByTematica( $datos->list_tematica_id, $offset);
        } else {
            $datos->entradas = dpEntrada_GetEntradasByUserActual($offset);
        }

        $datos->scrollLastRow = $offset + config('ctes.scrollEntradas');

        foreach ($datos->entradas as $entrada) {
            $item = new \stdClass();
            $item->entrada = $entrada;
            $item->confirm = dpEntrada_GetConfirm($entrada);
            $datos->listado[] = $item;
        }

        if (sizeof($datos->entradas) <= 0) {
            return response()->json(['status' => false]);
        }

        dpEntrada_datosComunesCosultas($datos);

        return view('diccionario/consultaAjax')->with('datos', $datos);
    }

    /**
     * Carga entradas por inicial version para ajax
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version Release: <package_version>
     *
     * @param Request $request
     *
     * @return view diccionario/consultaAjax
     */
    public function dpGetEntradasbyInitialAjax(Request $request)
    {
        $offset = $request->offset;
        $letra = $request->letra;
        $datos = new \stdClass();

        $datos->entradas = dpEntrada_GetEntradasByUserActualLetraInicial($letra, $offset);
        $datos->scrollLastRow = $offset + config('ctes.scrollEntradas');

        foreach ($datos->entradas as $entrada) {
            $item = new \stdClass();
            $item->entrada = $entrada;
            $item->confirm = dpEntrada_GetConfirm($entrada);
            $datos->listado[] = $item;
        }

        if (sizeof($datos->entradas) <= 0) {
            return response()->json(['status' => false]);
        }

        dpEntrada_datosComunesCosultas($datos);

        return view('diccionario/consultaAjax')->with('datos', $datos);
    }

    /**
     * Muestra listado de entradas del diccionario por letra inicial 
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version Release: <package_version>
     *
     * @param Request $request
     *
     * @return view diccionario/consulta
     */
    public function dpGetEntradaByInitialGET(Request $request)
    {
        $letra = $request->letra;
        $datos = new \stdClass();
        $datos->listado = [];
        $entradas = dpEntrada_GetEntradasByUserActualLetraInicial($letra);
        $datos->consultaMsg = __('diccionario.Por_Letra') . ' ' . $letra;
        $datos->letra = $letra;

        foreach ($entradas as $entrada) {
            $item = new \stdClass();
            $item->entrada = $entrada;
            $item->confirm = dpEntrada_GetConfirm($entrada);
            $datos->listado[] = $item;
        }
        dpEntrada_datosComunesCosultas($datos);

        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],
            ['name' => __('diccionario.Busqueda'), 'url' => route('personal.consulta.all')],
            ['name' => $datos->consulta?? $datos->consultaMsg, 'url' => $request->url],
        ];

        return view('diccionario/consulta')->with('datos', $datos);
    }


    /**
     * Oculta o muestra una acepción del diccionario personal
     *  Se llama desde \partials\components\botones\acepcion\ocultar.blade.php
     *
     * @author julio.buenadicha@altia.es
     * @version Release: <package_version>
     * 
     * @param Request $request
     * 
     * @return void
     */
    public function dpOcultarAcepcionGET(Request $request)
    {
        // Obtenemos la entrada del POST
        $entrada_id = $request->entrada_id;
        $acepcion_id = $request->acepcion_id;

        // Buscamos la entrada para ver si existe        

        $entrada = dpEntrada_GetEntradaById($entrada_id);
        if ($entrada) {

            // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
            $this->authorize('isOwner', $entrada);

            $acepcion = $entrada->dpAcepciones()->find($acepcion_id);
        }

        if ($entrada && $acepcion) {

            // Cambiamos el estado de oculta <==> visible
            if ($acepcion->estado == config('ctes.estados_entrada.oculta')) {
                // Está oculta y lo ponemos visible
                $acepcion->estado = config('ctes.estados_entrada.visible');
                $mensaje =  __('diccionario.acepcion_visible');                
            }else {
                // Está ponemos visible y lo oculta
                $acepcion->estado = config('ctes.estados_entrada.oculta');
                $mensaje =  __('diccionario.acepcion_ocultada') ;
            }
            
            $acepcion = dpAcepcion_Edit($acepcion);
            
            // Si no tiene acepciones visibles oculta la entrada
            if( $entrada->dpAcepciones()->where('estado', config('ctes.estados_entrada.visible'))->count() == 0 ){
                // ocutar enrada
                $entrada->estado = config('ctes.estados_entrada.oculta');
                $entrada = dpEntrada_Edit($entrada);
                $mensaje .= '. '.  __('diccionario.entrada_ocultada') . ': ' . $entrada->entrada;
            }
            if( $entrada->dpAcepciones()->where('estado', config('ctes.estados_entrada.visible'))->count() >= 1 
            && $entrada->estado == config('ctes.estados_entrada.oculta')){
                $mensaje .= '. '.  __('diccionario.entradas_visibles');
            }
            
            return redirect()->back()->with('success', $mensaje);
        }

        $mensaje =  __('diccionario.URLmala');
        return redirect()->route('entrada.buscador')->withErrors([$mensaje]);
    }

    /**
     * Obtener entradas resultado de la consulta
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version Release: <package_version>
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View diccionario/consulta
     */
    public function dpGetEntradaByConsultaGET(Request $request)
    {
        $allParams = array_merge($request->route()->parameters, $request->toArray());
        // dd(
        //     'dpGetEntradaByConsultaGET',$request->all(), 
        //     'consulta', $request->consulta, 
        //     'list_tematica_id', $request->list_tematica_id,
        //     'request->route', $request->route()->parameters(),
        //     'request->post', $request->post(),
        //     'allparameters', $allParams
        // );

        // // Validamos los parámetros de entrada
        $validator = Validator::make($allParams, [
            // 'consulta' => 'required_without:list_tematica_id|max:' . config('ctes.constantes_entradas.max_entrada'),
            'consulta' => 'max:' . config('ctes.constantes_entradas.max_entrada'),
            // esto es opcional para filtrar tambien por tematica
            'list_tematica_id' => 'array',
            'list_tematica_id.*' => 'integer',
            'tematicas_ids' => 'string',
        ]);
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $consulta = $request->consulta;
        $datos = new \stdClass();
        $datos->listado = [];
        $datos->consulta = $consulta;
        $datos->list_tematica_id = $request->list_tematica_id;

        // si se envian en la url como /tematicas/{teamaticas_ids} hay que porcesarlos:        
        if ( array_key_exists('tematicasIds', $allParams) && 
            !emptyOrNull($allParams['tematicasIds']) ) {
            $datos->list_tematica_id = explode(',',$allParams['tematicasIds']);
            $datos->tematicasIds = $allParams['tematicasIds'];
        } else {
            $datos->list_tematica_id = $request->list_tematica_id;
        }
        // dd($datos->list_tematica_id);

        // $entradas = dpEntrada_GetEntradasByUserActualConsulta($consulta, 0, $request->list_tematica_id);
        if( !emptyOrNull($datos->consulta)){
            $entradas = dpEntrada_GetEntradasByUserActualConsulta( $consulta,0, $datos->list_tematica_id );
        } else {
            $entradas = dpEntrada_GetEntradasByTematica($datos->list_tematica_id, 0);
        }
        $datos->entradas = $entradas;

        // si se envian en la url como /tematicas/{teamaticas_ids} hay que porcesarlos:        
        if ( array_key_exists('tematicasIds', $allParams) && 
            !emptyOrNull($allParams['tematicasIds']) ) {
            $datos->list_tematica_id = explode(',',$allParams['tematicasIds']);
        } else {
            $datos->list_tematica_id = $request->list_tematica_id;
        }

        dpEntrada_datosComunesCosultas($datos);

        foreach ($entradas as $entrada) {
            $item = new \stdClass();
            $item->entrada = $entrada;
            $item->confirm = dpEntrada_GetConfirm($entrada);
            $datos->listado[] = $item;
        }
        // $datos->offset = count($entradas);

        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],
            ['name' => __('diccionario.Busqueda'), 'url' => route('personal.consulta.all')],
            ['name' => $datos->consulta, 'url' => $request->url],
        ];

        return view('diccionario.consulta')->with('datos', $datos);
    }

    /**
     * Obtiene todos los nombres de las entradas para el autocompletar del 
     * buscador
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version Release: <package_version>
     *
     * @param Request $request
     *
     * @return bool|string listado de entradas como un string JSON o false si falla
     */
    public function dpGetEntradaAllJson(Request $request)
    {
        $entradas = dpEntrada_GetAllEntradasByUserActual();
        $r_entradas = [];

        // $tematicas = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.tematica'));
        $jsonObj = new stdClass();

        foreach ($entradas as $entrada) {
            // $r_entradas[] = [ 'name'=> $entrada->entrada, 'id'=>$entrada->id ];
            $r_entradas[] = $entrada->entrada;
        }

        $jsonObj->entradas = $r_entradas;
        // $jsonObj->categorias = $tematicas;
        // dd(count($r_entradas),$r_entradas);
        // header('Content-Type" => application/json');

        return json_encode($jsonObj);
    }

}
