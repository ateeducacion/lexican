<?php

namespace App\Http\Controllers;

use App\Models\AreaMateria;
use App\Models\CampoEntrada;
use App\Models\ComentarioEntrada;
use App\Models\DicAula;
use App\Models\DicAulaDestinarioAviso;
use App\Models\DicAulaEntrada;
use App\Models\DiccionarioPersonalEntrada;
use App\Models\Ensenanza;
use App\Models\EnvioAcepcion;
use App\Models\EnvioEntrada;
use App\Models\Pautas;
use App\Models\Persona;
use App\Models\TipoDiccionarioAula;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Mail;
use Validator;

// use \Okipa\LaravelTable\Table;

/**
 * Controlador de todo lo relacionado con los diccionarios de aula
 *
 * @category Laravel
 * @package  App\Http\Controllers
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 *
 */
class DicAulaController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Vista para la creación del diccionario de aula
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return View
     */
    public function index()
    {
        // sin el DicAula::class no funiciona!
        $this->authorize( 'esDocente', DicAula::class );

        $diccionarios   = DicAula::all();
        // $nivelEstudios  = NivelEstudio::all();
        $nivelEstudios  = getAllNivelEstudios();
        $areaMaterias   = AreaMateria::all();
        $ensenanzas     = Ensenanza::all();
        $camposEntradas = CampoEntrada::all();
        $tipoDiccionarioAulas = TipoDiccionarioAula::all();

        $datos = new \stdClass();

        $datos->breadcrumb = [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => __('diccionario.DiccionarioAula'), 'url' => route('diccionarioaula.get')],
            ['name' => __('diccionario.Crear'), 'url' => route('diccionarioaula.index')],
        ];

        // Mostrar tab aula
        $datos->tabs = getDatosTabs(true);

        // $persona = Persona::find(getSessionPersona()['id'])->personaUser;
        $datos->listaDiccionariosAula = daGetDiccionariosAulaByUserConectado();
        $datos->listaDiccionariosAulaNoVigentes = daGetDiccionariosAulaNoVigenteByUserConectado();

        foreach ( $datos->listaDiccionariosAula as $key => $dic ) {
            Log::debug( $key );
            Log::debug( json_encode($dic, JSON_PRETTY_PRINT ));
        }

        $datos->maestroPautas = Pautas::all()->where('estado', config('ctes.estados.activo'))->first();
        $datos->vigenciaMax = config('ctes.vigencia_max');

        return view('diccionario/crearDicAula')->with([
            'diccionarios' => $diccionarios,
            'nivelEstudios' => $nivelEstudios,
            'areaMaterias' => $areaMaterias,
            'ensenanzas' => $ensenanzas,
            'camposEntradas' => $camposEntradas,
            'tipoDiccionarioAulas' => $tipoDiccionarioAulas,
            'datos' => $datos
        ]);
    }

    /**
     * Guarda la creación del diccionario de aula
     *
     * @param Request $request Solicitud Http con los parámetros
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return View
     */
    public function create(Request $request)
    {
        // dd( 'request',$request, 'ano_ini_curso_escolar', $request->ano_ini_curso_escolar, $request->all() );
        $campos = $request->all();
        // fueza el tipo de diccionario => Diccionario General
        $request->merge(['tipoDiccionario' => '1']);

        // fuerzo el mismo codigo (pruebas)
        // $request->merge(['ano_ini_curso_escolar' => '2021']);
        // $request->merge(['codigoAleatorio' => 'test123']);
        // dd( 'request',$request, 'ano_ini_curso_escolar', $request->ano_ini_curso_escolar, $request->all(), $campos );

        $validator = Validator::make($request->all(), [
            'tipoDiccionario'        =>  'required',
            'nombreDiccionario'      => ['required', 'max:255'],
            'pautasEspecificas'      =>  'required',
            'visibilidadComentarios' =>  'required',
            'comentariosVisibleFecha' => ['required_if:visibilidadComentarios,2', 'before_or_equal:today'],
            'ano_ini_curso_escolar' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('aula/create')->withInput()->withErrors($validator);
        }
        // probamos si el codigo recibido no da problemas
        $nuevoCodigo = $request['codigoAleatorio'];
        $validatorCode = daValidateCode($nuevoCodigo);
        // si da problemas intentamos crear uno nuevo
        $whileMax = 5;
        $countWhile = 0;
        while( $validatorCode->fails() ) {
            // genera codigos hasta que encuentra uno valido
            $nuevoCodigo = daGenerateCode(6);
            $validatorCode = daValidateCode($nuevoCodigo);
            $request->merge(['codigoAleatorio' => $nuevoCodigo]);
            if ( $countWhile > $whileMax ){
                return redirect('aula/create')->withInput()
                    ->withErrors( __('diccionario.fallo_generando_codigo') );
            }
        }


        $create = createOrUpdateDicAula($request);
        if ($create === true) {
            // devolver que se ha creador correctamente
            $success = (object) [
                'msg' => __('diccionario.diccionario_creado_success')
            ];
            // return redirect('aula')->with('success', collect($success));
            // Lo he modificado por que salia el mensaje con el json como "{msj: ...}"
            return redirect('aula')->with('success',
                    $success->msg .', '.
                    __('diccionario.msg_nuevo_codigo').' '. $nuevoCodigo );
        } else {
            return $create;
        }
    }


    /**
     * Pagina Inicial diccionario de aula
     *
     * @param Request $request Solicitud Http con los parámetros
     *
     * @author  Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return view diccionario/aula
     */
    public function daHome(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        $datos->breadcrumb = [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => __('diccionario.DiccionarioAula'), 'url' => $request->url()],
        ];
        $datos->tabs = getDatosTabs(true);
        $datos->username = Auth::user()->name;

        $datos->dicAulaActivo = getDicAulaActivo();
        \Session(['userData.dicAulaActivo' => $datos->dicAulaActivo]);
        datosComunesConsultasDicAula( $datos, $datos->dicAulaActivo );

        if($datos->dicAulaActivo == null){
            return view('diccionario/aula')->with([
                'datos'=> $datos,
                'noExisteDicAula'=>true
            ]);
        }

        return view('diccionario/aula')->with('datos', $datos);
    }

    /**
     * Página de modificación del diccionario de aula
     *
     * @param Request $dicAulaId id del diccionario de aula a modificar
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return void
     */
    public function edit($dicAulaId)
    {
        $diccionarioAula = DicAula::find($dicAulaId);
        $datos = new \stdClass();
        $datos->tabs = getDatosTabs(true);
        $this->authorize('canEditDic', $diccionarioAula);
        $this->authorize('esCoordinador', $diccionarioAula);
        
        // si el diccionario no esta vigente sali y mostarra mensaje
        if ( $diccionarioAula->estado == config('ctes.estados.inactivo') ) {
            $mensaje =  __('diccionario.diccionario_inactivo');
            return redirect( route('diccionarioaula.get') )
                ->withErrors( [$mensaje]);
        }

        $datos->breadcrumb = [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => __('diccionario.DiccionarioAula'), 'url' => route('diccionarioaula.get')],
            ['name' => __('diccionario.Editar'), 'url' => route('diccionarioaula.edit', ['id' => $diccionarioAula->id])],
        ];

        $datos->listaDiccionariosAula = DicAula::all();
        $datos->listaDiccionariosAulaNoVigentes = daGetDiccionariosAulaNoVigenteByUserConectado();
        $maestroPautas = Pautas::all()->where('estado', config('ctes.estados.activo'));
        if (!($maestroPautas->first() instanceof Pautas)) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_base_error'),
                ['file' => __FILE__, 'line' => __LINE__],
                PHP_EOL . json_encode($dicAulaId, JSON_PRETTY_PRINT),
                $maestroPautas
            );
            // dd($e);
            return response(['error' => $maestroPautas], 404);
        }
        $datos->maestroPautas = $maestroPautas->first();

        // no se puede cambiar la vigencia a menos de los años que ya han pasado 
        $datos->vigenciaMin = (getAnoIniCurrentCursoEscolar() - $diccionarioAula->ano_ini_curso_escolar );
        // $datos->vigenciaMax = $diccionarioAula->vigencia+1;
        $datos->vigenciaMax = config('ctes.vigencia_max');

        // comprobar que el diccionario que se va a editar esta vigente y por lo tanto activo o desactivar si no lo esta
        // daDesactivarSiNoVigente( $diccionarioAula );

        $persona = Persona::find(getSessionPersona()['id'])->personaUser;

        $out = $this->index()->with([
            'diccionarioAula' => $diccionarioAula,
            'persona' => $persona,
            'datos' => $datos
        ]);
        if (isset($mensaje) && $mensaje != '') {
            $out->withErrors([$mensaje]);
        }

        return $out;
    }

    /**
     * Guarda la modificación de diccionario de aula
     *
     * @param Request $dicAulaId id del diccionario de aula a modificar
     * @param Request $request   Solicitud Http con los parámetros
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return void
     */
    public function save($dicAulaId, Request $request)
    {
        $diccionarioAula = DicAula::find($dicAulaId);

        if ($diccionarioAula->estado == config('ctes.estados.inactivo') ){
            $mensaje =  __('diccionario.diccionario_inactivo') + __('diccionario.no se puede modificar');
            return redirect( route('diccionarioaula.get') )
                ->withErrors( [$mensaje]);
        }

        $validator = Validator::make($request->all(), [
            'tipoDiccionario'   => 'required',
            'nombreDiccionario' => ['required', 'max:255'],
            'pautasEspecificas' => 'required',
            'visibilidadComentarios'  => 'required',
            'comentariosVisibleFecha' => ['required_if:visibilidadComentarios,2', 'before_or_equal:today'],
        ]);

        // 'codigo' => Rule::unique('dic_aula', 'codigo')->ignore($diccionarioAula->id)
        // 'codigoAleatorio' => 'required|unique:dic_aula,codigo,NULL,id,ano_ini_curso_escolar,' . $request->ano_ini_curso_escolar

        if ($validator->fails()) {
            return redirect("aula/$dicAulaId/edit")->withInput()->withErrors($validator);
        }

        // Pruebas intentado solucionar probelma con fecha >PHP4.3
        // return dd( $request->all()['comentariosVisibleFecha'] );
        // return Carbon::createFromFormat('d-m-Y', $request->all()['comentariosVisibleFecha'] );
        // return dd( formDateToDbDate( $request->all()['comentariosVisibleFecha'] )  );
        // return isset( $request->all()['comentariosVisibleFecha'] )?
        //             formDateToDbDate($request->all()['comentariosVisibleFecha']) :
        //             null;
        $create = createOrUpdateDicAula($request, $dicAulaId);
        if ($create === true) {
            $success = __('diccionario.diccionario_modificado_success');
            return redirect( route('diccionarioaula.get') )->with('success', $success );
        } else {
            return $create;
        }
    }

    /**
     * Envía un mail al profesor invitándole a unirse al diccionario.
     *
     * @param Request $request Solicitud Http con los parámetros
     *
     * @author  Jose Carlos Trillo <josecarlos.trillo@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return void
     */
    public function invitarMailPOST(Request $request)
    {
        // Validamos los parámetros de entrada
        $validator = Validator::make($request->all(), [
            'emailDocente'      => ['required', 'max:150'],
            'nombreDocente'     => ['required', 'max:150'],
            'apellidoDocente'   => ['required', 'max:150'],
            'codigo_dic'        => ['required', 'max:100'],
        ]);

        // comprobamos que el actual usuario es coordinador del diccionario
        $diccionarioAula = DicAula::where('codigo',$request->codigo_dic)
            // ->where('ano_ini_curso_escolar', getAnoIniCurrentCursoEscolar() ) Se comenta al no deberse tener en cuenta el año
            ->first();
        $this->authorize('esCoordinador', $diccionarioAula);

        if ($validator->fails()) {
            // return $this->index()->with(['errors' => $validator->errors()]);
            return redirect()->back()->withErrors( $validator->errors() );
        }

        $email = [$request->emailDocente];

        // Aquí definimos el contenido de las variables que irán en la vista.
        $data = array(
            'nombre'    => $request->nombreDocente,
            'apellido'  => $request->apellidoDocente,
            'codigo'    => $request->codigo_dic,
            'url'       => config('app.url')
        );
        $viewMail   = "layouts.partials.mail.inviteDocenteMailView";
        $asunto     = "LexiCán: Invitación a unirse a un diccionario.";
        // $from = "noreply@gobiernodecanarias.org";
        // Solo  me deja enviar emails con este "from" :
        $from = "eva.educacion@gobiernodecanarias.org";

        try {
            $resultado = sendHtmlMailwithView($data, $viewMail, $asunto, $email, null, null, $from );
        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.external_error'),
                ['file' => $th->getFile(), 'line' => $th->getLine()],
                $th->getMessage(),
                PHP_EOL . 'url: ' . $request->url() .
                PHP_EOL . 'request: ' .json_encode($request->all(), JSON_PRETTY_PRINT)
            );
            return redirect()->back()->withErrors( __("diccionario.invitarmail_error"));
        }
        try {
            // guardar en destinatario avisos:
            $destAvisos = new DicAulaDestinarioAviso();
            $destAvisos->email = $request->emailDocente;
            $destAvisos->dic_aula_id = $diccionarioAula->id;
            $destAvisos->estado=3; // profesor invitado
            // TODO: agregar a constantes
            $destAvisos->save();
        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.create_or_update'),
                ['file' => $th->getFile(), 'line' => $th->getLine()],
                $th->getMessage(),
                PHP_EOL . 'url: ' . $request->url() .
                PHP_EOL . 'request: ' .json_encode($destAvisos, JSON_PRETTY_PRINT) .
                PHP_EOL .'fallo al guardar en destinatario avisos'
            );
            return redirect()->back()->withErrors( __("diccionario.invitarmail_error"));
        }

        return redirect()->back()->with('success', __("diccionario.invitarmail_enviado"));
    }

    /**
     * Une al usuario conectado al diccionario de aula que se recibe por parámetro
     *
     * @param Request $request Solicitud Http con los parámetros
     *
     * @author  Julio Buenadicha <julio.buenadicha@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return void
     */
    public function unirse(Request $request)
    {
        $cursoEscolar = $request->cursoEscolar;
        $estudio = $request->estudio;
        $grupoLetra = $request->grupoLetra;
        $nivelEstudioFinal = $request->nivelEstudioFinal;
        $grupoFinal = $request->grupoFinal;
        $codigoProfe = $request->codigoProfe;

        // $codigoCompleto = $nivelEstudioFinal . $grupoFinal . $codigoProfe;
        $codigoCompleto = $request->codigo;

        $diccionarioAula = daGetDiccionarioAulaByCodigo($codigoCompleto);
        if ($diccionarioAula) {
            // Compruebo si ya estoy unido al diccionario
            $estaUnido = daDiccionarioAulaComprobarUsuarioActual($diccionarioAula);
            if ($estaUnido) {
                // comprobar que no este inhabilitado
                $participantes = $diccionarioAula->participantes()->where('rol_diccionario_id',config('ctes.rol.alumno'));
                $usuarioParticipante = $participantes->where('persona_id', getSessionPersona()['id'])->get()->first();
                if ($usuarioParticipante && $usuarioParticipante->estado == config('ctes.estados.inactivo')){
                    return redirect()->back()
                        ->withErrors([__('diccionario.modal_unirse_diccionario_error_noautorizado')]);
                }

                // Si existe el diccionario y YA estoy ya unido doy error
                $mensaje =  __('diccionario.modal_unirse_diccionario_error_yaunido', ['codigo' => $codigoCompleto]);
                return redirect()->back()
                    ->with('success', $mensaje); // En verde ya que solo se esta informando
            } else {
                // Si existe el diccionario y no estoy ya unido se enlaza
                $dicAulaParticipante = daDiccionarioAulaUnirUsuarioActual($diccionarioAula);
                $mensaje =  __('diccionario.modal_unirse_diccionario_success', ['codigo' => $codigoCompleto]);
                return redirect()->back()->with('success', $mensaje);
            }
        } else {
            // Si no existe el diccionario damos error
            $mensaje =  __('diccionario.modal_unirse_diccionario_error_noexiste');
            return redirect()->back()->withErrors([$mensaje]);
        }
    }

    /**
     * Ver las entradas del diccionario de aula (en ver y editar entradas)
     *
     * @param $dicAulaId Id del diccionario de aula para obtener las entradas
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return View
     */
    public function entradas($dicAulaId)
    {
        $diccionarioAula = DicAula::find($dicAulaId);
        $this->authorize('esCoordinador', $diccionarioAula);
        $entradas = EnvioEntrada::whereHas('dpEnvio', function ($envio) use ($dicAulaId) {
            $envio->where('dic_aula_id', $dicAulaId);
        })->with('dpEnvio')->get();

        $datos = (object)[
            'alfabeto' => array_merge(range('A', 'N'), ['Ñ'], range('O', 'Z'))
        ];

        // Mostrar tab aula
        $datos = new \stdClass();
        $datos->tabs = getDatosTabs(true);
        // Migas de pan
        $datos->breadcrumb = [
            // ['name' => 'Inicio', 'url' => url('/')],
            ['name' => __('diccionario.DiccionarioAula'), 'url' => route('diccionarioaula.get')],
            ['name' => $diccionarioAula->titulo , 'url' => route('diccionarioaula.get')],
            ['name' => __('diccionario.ver_publicar_entradas'), 'url' => route('diccionarioaula.entradas', ['id' => $dicAulaId])],
        ];
        return view('diccionario/entradasAula')->with([
            'entradas' => $entradas,
            'dicAulaId' => $dicAulaId,
            'datos' => $datos,
        ]);
    }

    /**
     * Ver las entradas del diccinario de aula cargado por Ajax
     *
     * @param Request $request   Solicitud Http con los parámetros
     * @param $dicAulaId Id del diccionario de aula para obtener las entradas
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return View
     */
    public function entradasAjax(Request $request, $dicAulaId)
    {
        $this->authorize( 'esDocente', DicAula::class );
        $params = (object) $request->all();
        // dd($params, $params->selectedSliderEstudiante);
        // Log::debug(json_encode($request, JSON_PRETTY_PRINT));
        $dicAula = DicAula::find($dicAulaId);

        $entradas = getDicAulaEntradasBusqueda($dicAulaId, $params);
        $estudiante =getDicAulaEntradasBusquedaEstudiante($dicAulaId, $params);

        return view('layouts/partials/consulta/entradasAulaListado')->with([
            'dicAula' => $dicAula,
            'dicAulaId' => $dicAulaId,
            'entradas' => $entradas->get(),
            'estudiante' => $estudiante,
        ]);
    }

    /**
     * Publica una entrada a un diccionario de aula
     *
     * @author Javier Pérez Batista <javier.perez@altia.es>
     * @version 1.0.0
     * @param  [string] $dicAulaId Indica el id del diccionario de aula
     * @param  [string] $entradaId
     * @return view
     */
    public function publicar(Request $request, $dicAulaId, $entradaId)
    {
        $this->authorize( 'esDocente', DicAula::class );
        $diccionarioAula = DicAula::find($dicAulaId);
        $this->authorize('esCoordinador', $diccionarioAula);

        return publicarEntrada($request, $dicAulaId, $entradaId);
    }

    public function publicarListado(Request $request, $dicAulaId)
    {
        $this->authorize('esDocente', DicAula::class);
        $diccionarioAula = DicAula::find($dicAulaId);
        $this->authorize('esCoordinador', $diccionarioAula);

        if ($request->enviosId) {
            $enviosIds = explode(',', $request->enviosId);
        }
        $errores = [];
        foreach ($enviosIds as $id) {
            $p = publicarEntradaSimple($request, $dicAulaId, $id);
            if ($p->codeType == 'error') {
                $p->id = $id;
                $errores[] = $p;
            } else if ($p->codeType == 'success') {
                $p->id = $id;
            }
        }
        if (count($errores) > 0) {
            return response()->json([$errores], 400);
        } else {
            return response()->json([], 200);
        }

    }

    /**
     * Elimina un envío de entrada
     *
     * @author Natalia Moreira <natalia.moreira@altia.es>
     * @version 1.0.0
     * @param Request $request
     * @param Integer $dicAulaId
     * @param Integer $entradaId
     * @return void
     */
    public function eliminar(Request $request, $dicAulaId, $entradaId)
    {
        $this->authorize( 'esDocente', DicAula::class );
        $diccionarioAula = DicAula::find($dicAulaId);
        $this->authorize('esCoordinador', $diccionarioAula);

        return eliminarEnvioEntrada($dicAulaId, $entradaId);
    }

    /**
     * Devuelve la vista que se usa en la ventana de confirmación de enviar todos
     * con datos actualizados
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param Request $request
     * @param Integer $dicAulaId
     *
     * @return View
     */
    public function getStatusEntradasParaPublicarListado(Request $request, $dicAulaId)
    {
        $this->authorize('esDocente', DicAula::class);
        $dicAula = DicAula::find($dicAulaId);
        $this->authorize('esCoordinador', $dicAula);
        // Aplicar los filtors que se estan aplicando
        $params = (object) $request->all();
        // $params = new \stdClass;

        $entradas = getDicAulaEntradasBusqueda($dicAulaId, $params)->get();
        $check = comprobarPublicarListadoEntradas($entradas, $dicAula->id);

        $n_entradasOk = count($check->ok);
        // entradas que no se publictaran:
        $yaPublicadas = $check->error->yaPublicadas;
        $duplicadasEnListado = $check->error->duplicadasEnListado;
        $n_entradasError = count($yaPublicadas) + count($duplicadasEnListado);
        $n_entradas = $n_entradasOk + $n_entradasError;

        // dd([
        //     // 'dicAula'             => $dicAula,
        //     // 'entradas'            => $entradas,
        //     'n_entradasOk'        => $n_entradasOk,
        //     'yaPublicadas'        => $yaPublicadas,
        //     'duplicadasEnListado' => $duplicadasEnListado,
        //     'n_entradasError'     => $n_entradasError,
        //     'n_entradas'          => $n_entradas,
        // ] );

        return response()->json([
            'view' => strval(view('layouts.partials.components.modal-publicar-listado')->with(
                [
                    'dicAula'             => $dicAula,
                    'entradas'            => $entradas,
                    'n_entradasOk'        => $n_entradasOk,
                    'yaPublicadas'        => $yaPublicadas,
                    'duplicadasEnListado' => $duplicadasEnListado,
                    'n_entradasError'     => $n_entradasError,
                    'n_entradas'          => $n_entradas,
                ]
            )),
            'codeType' => 'success']
        );
    }

    /**
     * Publica una entrada a un diccionario de aula
     *
     * @param Request $request   Solicitud Http con los parámetros
     * @param $dicAulaId Indica el id del diccionario de aula
     * @param $entradaId id de la entrada a comentar
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return View
     */
    public function comentar(Request $request, $dicAulaId, $entradaId)
    {
        $this->authorize( 'esDocente', DicAula::class );
        $diccionarioAula = DicAula::find($dicAulaId);
        $this->authorize('esCoordinador', $diccionarioAula);

        return comentarEntrada($request, $dicAulaId, $entradaId);
    }


    /**
     * Ajax que devuelve los participantes del diccionario
     *
     * @param $dicAulaId      Indica el id del diccionario de aula
     * @param $participanteId Indica el id del participante de aula
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return void
     */
    public function participantesAjax($dicAulaId, $participanteId)
    {
        $this->authorize( 'esDocente', DicAula::class );
        $diccionarioAula = DicAula::find($dicAulaId);
        // $this->authorize('isOwner', $diccionarioAula);
        // $this->authorize('esCoordinador', $diccionarioAula);

        if (Route::currentRouteName() == config('ctes.rutas.aula.habilitarParticipante')) {
            $result = hablitarParticipanteDicAula($participanteId);
        } elseif(Route::currentRouteName() == config('ctes.rutas.aula.deshabilitarParticipante')) {
            $result = deshablitarParticipanteDicAula($participanteId);
        } elseif(Route::currentRouteName() == config('ctes.rutas.aula.habilitarParticipanteAdmin')) {
            $result = hablitarParticipanteDicAulaAdmin($participanteId);
        } elseif(Route::currentRouteName() == config('ctes.rutas.aula.deshabilitarParticipanteAdmin')) {
            $result = deshablitarParticipanteDicAulaAdmin($participanteId);
        }
        // \Debugbar::info( $result );
        $vars = [ 'diccionarioAula' => $diccionarioAula, ];
        if ($result->status== false){
            $vars['errormsg'] = $result->msg;
        }
        \Debugbar::info( 'es participante?' . participanteEsCoordinador($participanteId) .'.' );
        if ( participanteEsCoordinador($participanteId) ){
            $vars['participantes'] = $diccionarioAula->participantes()
                ->whereHas('persona.personaUser', function ($query) {
                    $query->where('role_id', config('ctes.rol.docente'));
                })->get();
            $vars['profesorado'] = true;
        } else {
            $vars['participantes'] = $diccionarioAula->participantes()
                ->whereHas('persona.personaUser', function ($query) {
                    $query->where('role_id', config('ctes.rol.alumno'));
                })->get();
            $vars['profesorado'] = false;
        }
        return view('diccionario/participantesAjax')->with($vars);
    }

    /**
     * Pantalla para unirse a un diccoinario para el equipo de desarrollo
     *
     * @author  Julio Buenadicha <julio.buenadicha@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return void
     */
    public function DEV_unirseGET()
    {
        $datos = new \stdClass();
        dpEntrada_datosComunesCosultas($datos);

        return view('development.unirsediccionario')->with('datos', $datos)
            ->with('listaDiccionariosAula', $datos->listaDiccionariosAula)
        // ->with('centro_denominacion', $datos->centro_denominacion)
            ->with('curso_escolar', $datos->curso_escolar)
            ->with('nivelEstudios', $datos->nivelEstudios)
            ->with('id', uniqid())
            ->with('modal_width', '950px')
            ->render();
    }

    /**
     * Guardar en la session el dic de aula activo, se llama con ajax
     *
     * @param Request $request Solicitud Http con los parámetros
     *
     * @author  Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return json Devuelve ok con el id de del diccionario si todo va bien y 404 si falla
     */
    public function setDicAulaActivo(Request $request)
    {
        $dicAulaActivoId = $request->dicActivoId;
        $datos = daDiccionarioAulaSelDiccionarioActivo($dicAulaActivoId);

        if (is_array($datos)) {
            $datos['redirectUrl'] = $request->redirectUrl;
            return response()->json($datos);
        } else {
            return $datos;
        }
    }

    /**
     * Guarda en la session el Dic. de aula activo, peticion GET
     * la he utilizado durante el desarrollo para pruebas pero es posible que
     * no esa necesaria en el futuro y se pueda borrar
     *
     * @param Request $request Solicitud Http con los parámetros
     *
     * @author  Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return json Devuelve ok con el id de del diccionario si todo va bien y 404 si falla
     */
    public function setDicAulaActivoGet(Request $request)
    {
        $dicAulaActivoId = $request->id;
        return daDiccionarioAulaSelDiccionarioActivo($dicAulaActivoId);
    }

    /**
     * Muestra la vista de consultar todas las entradas
     *
     * @param Request $request Solicitud Http con los parámetros
     *
     * @author  Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return view
     */
    public function getAllEntradasConsulta(Request $request)
    {
        $datos = new \stdClass();
        $datos->dicAulaActivo = getDicAulaActivo();
        \Session(['userData.dicAulaActivo' => $datos->dicAulaActivo]);
        datosComunesConsultasDicAula( $datos, $datos->dicAulaActivo );

        $datos->listado = [];

        if ( $datos->dicAulaActivo ) {
            $datos->entradas = getEntradasConsultaDicAula($datos->dicAulaActivo->id);

            $datos->listado = [];
            foreach ($datos->entradas as $entrada) {
                // comprobar que la entrada no sea null
                if($entrada->dpEntrada()->first() !== null){
                    // comprobar que existe dpDiccionario
                    if($entrada->dpEntrada()->first()->dpDiccionario){
                        $item = new \stdClass();
                        $entrada->acepciones = EnvioAcepcion::where('envio_entrada_id',$entrada->id)->get();
                        $item->entrada = $entrada;
                        $item->confirm = dpEntrada_GetConfirm($entrada);
                        $datos->listado[] = $item;
                    }else{
                        // log convertir entrada en json
                        customLoggin(
                            config('ctes.log_levels.error'),
                            config('ctes.log_types.data_base_error'),
                            ['file' => __FILE__, 'line' => __LINE__],
                            PHP_EOL . json_encode($entrada, JSON_PRETTY_PRINT)
                        );
                    }
                }else{
                    // auditar en el log
                    customLoggin(
                        config('ctes.log_levels.error'),
                        config('ctes.log_types.data_base_error'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . 'ERROR_DICAULA_Entrada_BORRADA_EN_DICPERSONAL. IdEntrada: ' . $entrada->id
                    );
                }
            }

            $datos->breadcrumb = [
                ['name' => __('diccionario.Inicio'), 'url' => url('/')],
                ['name' => __('diccionario.DiccionarioAula'), 'url' => route('diccionarioaula.get')],
                ['name' => getDicAulaActivo()->titulo, 'url' => route('aula.consulta.all')],
                ['name' => __('diccionario.Busqueda'), 'url' => $request->url],
            ];
        } else {
            $datos->breadcrumb = [
                ['name' => __('diccionario.Inicio'), 'url' => url('/')],
                ['name' => __('diccionario.diccionario_aula'), 'url' => route('diccionarioaula.get')],
            ];
        }
        return view('diccionario.aula.consulta')->with('datos', $datos);
    }

    /**
     * Muestra la vista de consulta de entradas que empiezan por la inicial
     * que se ha selecionado en la url aula/entradas/letra/{letra}
     *
     * @param Request $request Solicitud Http con los parámetros
     *
     * @author  Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return view
     */
    public function getEntradaByInitial(Request $request)
    {
        $datos = new \stdClass();
        $datos->dicAulaActivo = getDicAulaActivo();

        if (is_null($datos->dicAulaActivo)) {
            return $this->getAllEntradasConsulta($request);
        }

        $datos->letra = $request->letra;
        $datos->consultaMsg = __('diccionario.Por_Letra') . ' ' . $datos->letra;
        \Session(['userData.dicAulaActivo' => $datos->dicAulaActivo]);
        datosComunesConsultasDicAula( $datos, $datos->dicAulaActivo );

        // getEntradasConsultaDicAula($datos->dicAulaActivo->id);

        if ( $datos->dicAulaActivo ) {
            $datos->entradas = getEntradasAulaByInitial( $datos->dicAulaActivo, $datos->letra );
            $datos->listado = [];
            foreach ($datos->entradas as $entrada) {
                if($entrada->dpEntrada()->first() !== null){
                    if($entrada->dpEntrada()->first()->dpDiccionario){
                        $item = new \stdClass();
                        $entrada->acepciones = EnvioAcepcion::where('envio_entrada_id',$entrada->id)->get();
                        $item->entrada = $entrada;
                        // $item->confirm = dpEntrada_GetConfirm($entrada);
                        $datos->listado[] = $item;
                    }else{
                       // log convertir entrada en json
                       customLoggin(
                            config('ctes.log_levels.error'),
                            config('ctes.log_types.data_base_error'),
                            ['file' => __FILE__, 'line' => __LINE__],
                            PHP_EOL . json_encode($entrada, JSON_PRETTY_PRINT)
                        );
                    }
                }else{
                    // auditar en el log
                    customLoggin(
                        config('ctes.log_levels.error'),
                        config('ctes.log_types.data_base_error'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . 'ERROR_DICAULA_Entrada_BORRADA_EN_DICPERSONAL. IdEntrada: ' . $entrada->id
                    );
                }
                
            }
            $datos->breadcrumb = [
                ['name' => __('diccionario.Inicio'), 'url' => url('/') ],
                ['name' => __('diccionario.DiccionarioAula'), 'url' => route('diccionarioaula.get')],
                ['name' => getDicAulaActivo()->titulo, 'url' => route('aula.consulta.all')],
                ['name' => __('diccionario.Busqueda'), 'url' => route('aula.consulta.all')],
                ['name' => $datos->consulta?? $datos->consultaMsg, 'url' => $request->url],
            ];

            return view('diccionario.aula.consulta')->with('datos', $datos);
        } else {
            return redirect()->route('diccionarioaula.get')
                ->with('noExisteDicAula', true);
        }
    }

    /**
     * Muestra entradas por consulta, busca las entradas que coinciden
     * url aula/entradas/{consulta}
     *
     * @param Request $request Solicitud Http con los parámetros
     *
     * @author  Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View diccionario/aula/consulta
     */
    public function getEntradaByConsulta(Request $request)
    {
        $allParams = array_merge($request->route()->parameters, $request->toArray());
        // dd($allParams['consulta']);

        $validator = Validator::make($allParams, [
            'consulta' => 'max:' . config('ctes.constantes_entradas.max_entrada'),
            // esto es opcional para filtrar tambien por tematica
            'list_tematica_id' => 'array|max:'. config('ctes.tematicas_max') ,
            'list_tematica_id.*' => 'integer',
            'tematicas_ids' => 'string', //TODO: crear validador propio para que al hace explode(',') verificqe que no hay mas elementos que en tematicas_max
        ]);
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $datos = new \stdClass();
        $datos->dicAulaActivo = getDicAulaActivo();
        $datos->consulta = $request->consulta;

        // si se envian en la url como /tematicas/{teamaticas_ids} hay que porcesarlos:
        if ( array_key_exists('tematicasIds', $allParams) && 
            !emptyOrNull($allParams['tematicasIds']) ) {
            $datos->list_tematica_id = explode(',',$allParams['tematicasIds']);
            $datos->tematicasIds = $allParams['tematicasIds'];
        } else {
            $datos->list_tematica_id = $request->list_tematica_id;
        }

        // dd($datos->consulta);
        \Session(['userData.dicAulaActivo' => $datos->dicAulaActivo]);
        datosComunesConsultasDicAula( $datos, $datos->dicAulaActivo );
        

        if (is_null($datos->dicAulaActivo)) {
            return $this->getAllEntradasConsulta($request);
        }
        if( !emptyOrNull($datos->consulta)){
            $datos->entradas = getEntradasAulaByConsulta( $datos->dicAulaActivo, $datos->consulta,0, $datos->list_tematica_id );
        } else {
            $datos->entradas = getEntradasAulaByTematica( $datos->dicAulaActivo, $datos->list_tematica_id,0 );
        }

        $datos->listado = [];
        foreach ($datos->entradas as $entrada) {
            if($entrada->dpEntrada()->first()->dpDiccionario){
                if($entrada->dpEntrada()->first()->dpDiccionario){
                    $item = new \stdClass();
                    $entrada->acepciones = EnvioAcepcion::where('envio_entrada_id',$entrada->id)->get();
                    $item->entrada = $entrada;
                    // $item->confirm = dpEntrada_GetConfirm($entrada);
                    $datos->listado[] = $item;
                }else{
                    customLoggin(
                        config('ctes.log_levels.error'),
                        config('ctes.log_types.data_base_error'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . json_encode($entrada, JSON_PRETTY_PRINT)
                    );
                }
            }else{
                // auditar en el log
                customLoggin(
                    config('ctes.log_levels.error'),
                    config('ctes.log_types.data_base_error'),
                    ['file' => __FILE__, 'line' => __LINE__],
                    PHP_EOL . 'ERROR_DICAULA_Entrada_BORRADA_EN_DICPERSONAL. IdEntrada: ' . $entrada->id
                );
            }
        }

        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.DiccionarioAula'), 'url' => route('diccionarioaula.get')],
            ['name' => getDicAulaActivo()->titulo, 'url' => route('aula.consulta.all')],
            ['name' => __('diccionario.Busqueda'), 'url' => route('aula.consulta.all')],
            ['name' => $datos->consulta, 'url' => $request->url],
        ];

        return view('diccionario.aula.consulta')->with('datos', $datos);
    }

    /**
     * Muestra entradas por consulta, se lanza cuando se busca dede el formulario
     * del buscador y se envia mediante POST, redireciona a la pagina quue luego
     * lleva a getEntradaByConsulta()
     *
     * @param Request $request Solicitud Http con los parámetros
     *
     * @author  Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function buscarEntrada(Request $request)
    {
        // Validamos los parámetros de entrada
        $validator = Validator::make($request->all(), [
            'entrada_entrada' => 'max:' . config('ctes.constantes_entradas.max_entrada'),
            // opcional filtar por etiquetas:
            'list_tematica_id' => 'array|max:' . config('ctes.tematicas_max'),
            'list_tematica_id.*' => 'integer',
            // 'tematicas_ids' => 'string',
        ]);
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }
        $entrada_entrada = $request->entrada_entrada;

        $datos = new \stdClass();
        $datos->dicAulaActivo = getDicAulaActivo();
        datosComunesConsultasDicAula($datos, $datos->dicAulaActivo);

        // Redirecionamos a la consulta con la busqueda de la entrada
        // lo manda a getEntradaByConsulta()
        // return $this->getEntradaByConsulta($request);
        if (emptyOrNull($entrada_entrada) && emptyOrNull($request->tematicas_ids)) {
            return redirect()->back()->withErrors(__('diccionario.buscador_sindatos'));
        }
        if ( emptyOrNull($entrada_entrada) ) {
            return redirect()->route('aula.consulta.tematica', [
                'tematicasIds' => $request->tematicas_ids
            ]);
        } elseif ( emptyOrNull($request->tematicas_ids) ) {
            return redirect()->route('aula.consulta.palabra', [
                'consulta' => $entrada_entrada,
            ]);
        } else {
            return redirect()->route('aula.consulta.palabra.tematica', [
                'consulta' => $entrada_entrada,
                'tematicasIds' => $request->tematicas_ids
            ]);
        }
    }

    /**
     * Obtiene todas las entradas del diccionario activo con formanto JSON para el
     * buscador en diccionario de aula
     *
     * @param Request $request Solicitud Http con los parámetros
     *
     * @author  Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return JSON
     */
    public function getAllEntradasJson(Request $request)
    {
        $campos = ['entrada'];
        $entradas = getEntradasConsultaDicAula(getDicAulaActivo()->id, $campos, 0,0);
        $r_entradas = [];
        // dd( count($entradas) , $entradas );
        $jsonObj = new \stdClass();
        try {
            foreach ($entradas as $entrada) {
                // $r_entradas[] = [ 'name'=> $entrada->entrada, 'id'=>$entrada->id ];
                $r_entradas[] = $entrada->entrada;
            }
            $jsonObj->entradas = $r_entradas;
            return json_encode($jsonObj);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    /**
     * Obtiene entradas del diccionario elegido con formanto JSON
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function getEntradasJson(Request $request)
    {
        $dicAulaId = $request->id;
        // $entradas = getEntradasConsultaDicAula($dicAulaId); // esto devuelve las entradas pubilcadas
        $estudianteId = getSessionPersona()['id'];
        $jsonObj = new \stdClass();
        $entradas = getDAulaEnviosDiccionario($dicAulaId, $estudianteId);
        $jsonObj->entradas = $entradas;

        try {
            return response()->json($jsonObj, 200);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    /**
     * Borra un comentario de una entrada
     *
     * @param Request $request      Solicitud Http con los parámetros
     * @param $dicAulaId    Id del diccionario de aula
     * @param $entradaId    Id de la entrad a borrar comentario
     * @param $idcomentario Id del comentario
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return JSON
     */
    public function comentarioDelete(Request $request, $dicAulaId, $entradaId, $idcomentario)
    {
        try {
            ComentarioEntrada::destroy($idcomentario);
            $entrada = EnvioEntrada::find($entradaId);
            $success = (object) [
                'msg' => 'Comentario borrado con éxito'
            ];
            return response()->json([
                'view' => strval(view('layouts.partials.components.botones.entrada.comentarios')->with(
                    [
                        'entrada'=> $entrada,
                    ]
                )),
                'codeType' => 'succes',
                'message'  => 'Success: comentario borrado',
            ]
            );
        } catch (\Throwable$th) {
            $errors = (object) [
                'msg' => 'Error al borrar el comentario',
            ];
            return view('layouts.partials.alerts.errors')
                ->with('errors', collect($errors));
        }

    }

    /**
     * Edita un comentario de una entrada
     *
     * @param Request $request      Solicitud Http con los parámetros
     * @param $dicAulaId    Id del diccionario de aula
     * @param $entradaId    Id de la entrad a borrar comentario
     * @param $idcomentario Id del comentario
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return JSON
     */
    public function comentarioEdit(Request $request, $dicAulaId, $entradaId, $idcomentario)
    {
        try {
            $comentario = ComentarioEntrada::find($idcomentario);
            $comentario->comentario = $request->all()['text'];
            $comentario->save();
            $entrada = EnvioEntrada::find($entradaId);
            $success = (object) [
                'msg' => 'Comentario editado con éxito',
            ];
            return response()->json([
                'view' => strval(view('layouts.partials.components.botones.entrada.comentarios')->with(
                    [
                        'entrada' => $entrada,
                    ]
                )),
                'codeType'  => 'succes',
                'message'   => 'Success: entrada borrada'
            ]
            );
        } catch (\Throwable $th) {
            $errors = (object) [
                'msg' => 'Error al borrar el comentario'
            ];
            return view('layouts.partials.alerts.errors')
                ->with('errors', collect($errors));
        }

    }
    /**
     * Oculta la acepción de la entrada en el diccionario de aula
     *
     * @param Request $request    Solicitud Http con los parámetros
     * @param $dicAulaId  Id del diccionario de aula
     * @param $entradaId  Id de la entrad a borrar comentario
     * @param $acepcionId Id de la acepción
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return JSON
     */
    public function ocultarAcepcion(Request $request, $dicAulaId, $entradaId, $acepcionId)
    {
        $diccionarioAula = DicAula::find($dicAulaId);
        $this->authorize('isOwner', $diccionarioAula);
        return ocultaAcepcion($acepcionId, $entradaId);
    }

    /**
     * Oculta la entrada en el diccionario de aula
     *
     * @param Request $request   Solicitud Http con los parámetros
     * @param $dicAulaId Id del diccionario de aula
     * @param $entradaId Id de la entrad a borrar comentario
     *
     * @author  Javier Pérez Batista <javier.perez@altia.es>
     * @access  public
     * @version Release: <package_version>
     *
     * @return JSON
     */
    public function ocultarEntrada(Request $request, $dicAulaId, $entradaId)
    {
        $diccionarioAula = DicAula::find($dicAulaId);
        $this->authorize('isOwner', $diccionarioAula);
        return ocultaEntrada($dicAulaId, $entradaId);
    }

    /**
     * Comprueba si las entradas que estan selecionadas para enviar
     * al diccionario de aula no tienen errores
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param Request $request incluye los ids de las entradas y el dic de aula
     *
     * @return json con el estado de cada entrada c
     */
    public function comprobarEntradasAEnviar( Request $request )
    {
        $entradasIds = $request->entradas;
        $dicAulaId = $request->id;
        $diccionarioAula = DicAula::find($dicAulaId);

        foreach ($entradasIds as $entradaId ) {
            $entrada = DiccionarioPersonalEntrada::find( $entradaId );
            $status[$entradaId] = dpEnvio_EntradaCumpleRequisitosDiccionario($entrada, $diccionarioAula);
        }

        return response()->json($status);
    }

    /**
     * Vista indiviudal de entrada
     * se usa para que los docentees accedan a editar nombre de entrada
     * url->name aula.entrada.edit
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param Request $request
     *
     * @return view 'diccionario/aula/entrada'
     */
    public function daEntradaGET( Request $request )
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        // Obtenemos la entrada
        $entrada_id = $request->entrada_id;
        // Buscamos la entrada para ver si existe
        $envioEntrada = EnvioEntrada::find($entrada_id);
        $entrada = DicAulaEntrada::where('envio_entrada_id',$envioEntrada->id)
                ->where('estado',config('ctes.estados.activo'));

        // Mostrar tab aula
        $datos->tabs = getDatosTabs(true);
        // dd($entrada);

        if ($entrada) {
            // Seguridad - Comprobamos que el usuario conectado es docente
            $this->authorize( 'esDocente', DicAula::class );
            $diccionarioAula = DicAula::find($request->diccionario_id);
            $this->authorize('esCoordinador', $diccionarioAula);

            $datos->entrada = $entrada->get()->first();
            $envioEntrada->acepciones = EnvioAcepcion::where('envio_entrada_id', $entrada_id)
                ->where('estado', config('ctes.estados.activo'))
                ->get();
            $datos->envioEntrada = $envioEntrada;

            // Migas de pan
            $datos->breadcrumb = [
                ['name' => __('diccionario.Inicio'), 'url' => url('/')],
                ['name' => __('diccionario.diccionario_aula'), 'url' => route('diccionarioaula.get')],
                ['name' => getDicAulaActivo()->titulo, 'url' => route('aula.consulta.all')],
                ['name' => $envioEntrada->entrada, 'url' => $request->url],
            ];

            // return view('')->with('datos', $datos);
            return view('diccionario/aula/entrada')->with('datos', $datos);
        }

        $mensaje =  __('diccionario.URLmala');
        return redirect()->route('entrada.buscador')->withErrors([$mensaje]);
    }

    /**
     * Te lleva al formulario editar nombre de entrada
     * url->name aula.entrada.update
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param Request $request
     *
     * @return view diccionario/aula/daEntradaEdit
     */
    public function daEditEntradaGET( Request $request )
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        // Obtenemos la entrada del POST
        $entrada_id = $request->entrada_id;
        // Buscamos la entrada para ver si existe
        $envioEntrada = EnvioEntrada::find($entrada_id);
        $entrada = DicAulaEntrada::where('envio_entrada_id',$envioEntrada->id)
                ->where('estado',config('ctes.estados.activo'));

        // Mostrar tab aula
        $datos->tabs = getDatosTabs(true);

        if ($entrada) {
            $datos->entrada = $envioEntrada;

            // $envioEntrada->acepciones = EnvioAcepcion::where('envio_entrada_id', $entrada_id)
            //     ->where('estado', config('ctes.estados.activo'))
            //     ->get();

            // Migas de pan
            $datos->breadcrumb = [
                ['name' => __('diccionario.Inicio'), 'url' => url('/')],
                ['name' => __('diccionario.diccionario_aula'), 'url' => route('diccionarioaula.get')],
                ['name' => getDicAulaActivo()->titulo, 'url' => route('aula.consulta.all')],
                ['name' => $envioEntrada->entrada, 'url' => $request->url]
            ];
            // return view('diccionario.aula.daEntradaEdit')->with('datos', $datos);
            return view('diccionario/aula/daEntradaEdit')->with('datos', $datos);
        }

        $mensaje =  __('diccionario.URLmala');
        return redirect()->back()->withErrors([$mensaje]);
    }

    /**
     * Editar nombre de entrada del diccionario de aula
     * url->name: aula.entrada.update
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param Request $request
     *
     * @return redirect aula.entrada.get te devuelve a la vista de edicion de la entrada y acepciones
     */
    public function daEditEntradaPOST( Request $request )
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
        $envioEntrada_id = $request->entrada_id;
        // Buscamos la entrada para ver si existe
        $envioEntrada = EnvioEntrada::find($envioEntrada_id);
        $entrada = DicAulaEntrada::where('envio_entrada_id',$envioEntrada->id)
                ->where('estado',config('ctes.estados.activo'));
        $nuevoNombreEntrada = $request->entrada_entrada;

        if ($entrada) {
            $nuevoNombreEntrada = normalizarEntrada( $nuevoNombreEntrada );

            // Modificar EnvioEntrada
            $envioEntrada->entrada = $nuevoNombreEntrada;

            // buscar si existe entrada con el mismo nombre
            $entradaConNuevoNombre = daGetEntradaByEntradaByDiccionario(
                $nuevoNombreEntrada, $envioEntrada->dicAula->id);
            if ($entradaConNuevoNombre && $entradaConNuevoNombre->id != $envioEntrada->id) {
                // Si existe una entrada con el nuevo nombre y no es la que estoy editando,
                // doy error porque ya esta el nombre usado
                $mensaje =  __('diccionario.entrada_existe');
                return redirect()
                    ->back()
                    ->withErrors([$mensaje])
                    ->withInput();
            } else {
                try {
                    $envioEntrada->updated_by_persona_id = getSessionPersona()['id'];
                    $envioEntrada->save();

                } catch (\Throwable $th) {
                    // loguear error
                    customLoggin(
                        config('ctes.log_levels.error'),
                        config('ctes.log_types.data_base_error'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . json_encode($request->all(), JSON_PRETTY_PRINT),
                        $th->getMessage()
                    );

                    return redirect()
                        ->back()
                        ->withErrors([$th->getMessage()])
                        ->withInput();
                }
            }

            // Mostramos la entrada con la lista de acepciones
            // dd( $envioEntrada->dicAula, $envioEntrada->dicAula['id'], $envioEntrada->dicAula->id );
            return redirect()->route('aula.entrada.get', [
                'diccionario_id' => $envioEntrada->dicAula->id ,
                'entrada_id' => $envioEntrada->id
            ]);
        }
    }

    public function getEntradasConsultaAjax(Request $request)
    {
        $allParams = array_merge($request->route()->parameters, $request->toArray());
        $offset = $request->offset;
        $consulta = $request->consulta;
        $tematicas_ids = $request->tematicasIds;
        $datos = new \stdClass();

        if ($request->dicId){
            $dicAula = daGetDiccionarioAulaById($request->dicId);
        } else {
            $datos->dicAulaActivo = getDicAulaActivo();
            \Session(['userData.dicAulaActivo' => $datos->dicAulaActivo]);
            $dicAula = $datos->dicAulaActivo;
        }
        datosComunesConsultasDicAula( $datos, $dicAula );

        if ( array_key_exists('tematicasIds', $allParams) && 
            !emptyOrNull($allParams['tematicasIds']) ) {
            $datos->list_tematica_id = explode(',',$allParams['tematicasIds']);
            $datos->tematicasIds = $allParams['tematicasIds'];
        } else {
            $datos->list_tematica_id = $request->list_tematica_id;
        }

        if ($consulta) {
            $datos->consulta = $consulta;
            $datos->entradas = getEntradasAulaByConsulta($dicAula, $consulta, $offset);
        } elseif($tematicas_ids) {
            // dd($tematicas_ids);
            // $datos->entradas = getEntradasConsultaDicAula($dicAula->id, $consulta, $offset);
            $datos->entradas = getEntradasAulaByTematica($dicAula, $datos->list_tematica_id, $offset);
        } else {
            $datos->entradas = getEntradasConsultaDicAula($dicAula->id, null, $offset);
        }

        $datos->scrollLastRow = $offset + config('ctes.scrollEntradas');
        $datos->listado = [];
        foreach ($datos->entradas as $entrada) {
            $item = new \stdClass();
            $entrada->acepciones = EnvioAcepcion::where('envio_entrada_id',$entrada->id)->get();
            $item->entrada = $entrada;
            $item->confirm = dpEntrada_GetConfirm($entrada);
            $datos->listado[] = $item;
        }

        if (sizeof($datos->entradas) <= 0) {
            return response()->json(['status' => false]);
        }

        return view('diccionario/consultaAulaAjax')->with('datos', $datos);
    }

    public function getEntradasbyInitialAjax(Request $request)
    {
        $offset = $request->offset;
        $consulta = $request->consulta;
        $letra = $request->letra;
        $datos = new \stdClass();

        if ($request->dicId) {
            $dicAula = daGetDiccionarioAulaById($request->dicId);
        } else {
            $datos->dicAulaActivo = getDicAulaActivo();
            \Session(['userData.dicAulaActivo' => $datos->dicAulaActivo]);
            $dicAula = $datos->dicAulaActivo;
        }
        datosComunesConsultasDicAula( $datos, $dicAula );

        $datos->entradas = getEntradasAulaByInitial( $dicAula, $letra, $offset);

        // dd(
        //     'offset', $offset,
        //     'dicaulaid', $request->dicId, $dicAula->id,
        //     'entradas',
        //     $datos->entradas->toArray(),
        //     'entradas->entrada',
        //     array_map(
        //         function($x){
        //             return $x['entrada'];
        //         },
        //         $datos->entradas->toArray()
        //     )
        // );

        $datos->scrollLastRow = $offset + config('ctes.scrollEntradas');
        $datos->listado = [];
        foreach ($datos->entradas as $entrada) {
            $item = new \stdClass();
            $entrada->acepciones = EnvioAcepcion::where('envio_entrada_id',$entrada->id)->get();
            $item->entrada = $entrada;
            $item->confirm = dpEntrada_GetConfirm($entrada);
            $datos->listado[] = $item;
        }

        if (sizeof($datos->entradas) <= 0) {
            return response()->json(['status' => false]);
        }

        return view('diccionario/consultaAulaAjax')->with('datos', $datos);
    }

    public function delete($dicAulaId, Request $request)
    {
        $diccionarioAula = DicAula::find($dicAulaId);
        $del = $diccionarioAula->delete();
        if ($del === true) {
            $success = __('diccionario.diccionario_borrado_success');
            return redirect( route('diccionarioaula.get') )->with('success', $success );
        } else {
            return $del;
        }
    }

    public function testDeleteAjax($dicAulaId){
        $diccionarioAula = DicAula::find($dicAulaId);
        $this->authorize('canEditDic', $diccionarioAula);

        if ( peticionEntradasAula($diccionarioAula->id)->get()->count() > 0 )
            return response()->json(['borrable'=>false,'error'=>'publicadas']);

        if ( daTieneEnviosPendientes($diccionarioAula) )
            return response()->json(['borrable'=>false,'error'=>'envios']);

        // el numero minimo de participantes es 1 ya que el creador aparece como participante tambien
        $n_participantes = $diccionarioAula->participantes->count();
        if ( $n_participantes>1 )
            return response()->json(['borrable'=>true,'participantes'=>$n_participantes]);

        return response()->json(['borrable'=>true]);
    }

    // devuelve listado activos con vigencia de mas de un año
    public function comprobarVigenciaDiccionarios()
    {
        //

    }

    //
    /**
     * tabla larabelTable
     *
     * @return View
     */
    // public function tabla()
    // {
    //     $table = (new Table)->model(DicAula::class)->routes([
    //         'index'   => ['name' => 'dicAula.index'],
    //         // 'create'  => ['name' => 'dicaulaTable.create'],
    //         // 'edit'    => ['name' => 'dicaulaTable.edit'],
    //         // 'destroy' => ['name' => 'dicaulaTable.destroy'],
    //     ])->destroyConfirmationHtmlAttributes(function (DicAula $dic) {
    //         return [
    //             'data-confirm' => 'Are you sure you want to delete ' . $dic->title . ' ?',
    //         ];
    //     });
    //     // $table->column('title')->sortable(true)->searchable();
    //     $table->column('vigencia')->sortable()->searchable();
    //     $table->column('estado')->sortable()->searchable();
    //     $table->column('ano_ini_curso_escolar')->sortable()->searchable();

    //     return view('diccionario.aula.table', compact('table'));
    // }
}
