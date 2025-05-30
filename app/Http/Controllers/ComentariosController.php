<?php

namespace App\Http\Controllers;

use App\Models\ComentarioGeneral;
use App\Models\DicAula;
use App\Models\EnvioEntrada;
use Illuminate\Http\Request;

use Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Controlador de todo lo relacionado con los comentarios de entradas 
 * y de diccionario de aula
 * 
 * @package App\Http\Controllers
 * 
 * @access  public
 * @author  Julio Buenadicha <julio.buenadicha@altia.es>
 * @version Release: <package_version>
 * 
 */
class ComentariosController extends Controller
{

    /**
     * Muestra la pantalla para seleccionar los diccionarios de aula 
     * y ver los comentarios de todas las entradas del alumno 
     * del diccionario personal
     *
     * @param Request $request Variable con la solicitud Http
     * 
     * @author  julio.buenadicha@altia.es
     * @access  public
     * @version Release: <package_version>
     * 
     * @return view comentarios/comentariosDiccionarioVer
     */
    public function comentariosDiccionarioGET(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        // Obtenemos el diccionario personal del usuario conectado
        $diccionario = dpDiccionario_GetDiccionarioByUserActual();
        // diccionarios de aulas en los que participa el usuario:
        $diccionariosAula = daGetDiccionariosAulaConectadosByPersonaId(getSessionPersona()['id']);
        $datos->diccionario = $diccionario;

        // Listados de comentarios, debería valer con esto pero esta fallando
        // $datos->comentariosEntradas= daGetComentariosEntradasAlumno( getSessionPersona() );
        // $datos->comentariosGenerales= daGetComentariosGeneralesAlumno( getSessionPersona() );

        // Obtengo los diccionarios de aula a los que está unido el usuario actual 
        $listaDiccionariosAula = daGetDiccionariosAulaComentariosByUserConectado($diccionario);
        $datos->listaDiccionariosAula = $listaDiccionariosAula;
        // Obtengo todos los cometarios
        $datos->comentariosEntradas = new Collection();
        $datos->comentariosGenerales = new Collection();
        foreach ( $diccionariosAula as $key => $value ) {
            $generales = daGetComentariosGeneralesAlumnoAula( getSessionPersona(), $value );
            $entradas = daGetComentariosEntradasAlumnoAula( getSessionPersona(), $value );

            $datos->comentariosGenerales = $datos->comentariosGenerales->merge( $generales );
            $datos->comentariosEntradas = $datos->comentariosEntradas->merge( $entradas );            
        }

        // Migas de pan
        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],
            ['name' => __('diccionario.comentarios_diccionario_miga'), 'url' => $request->url],
        ];

        // Mostramos la ventana
        return view('comentarios/comentariosDiccionarioVer')->with('datos', $datos);
    }

    /**
     * Reponde a una petición Ajax 
     * Devuelve la pantalla de ver comentarios a una entrada
     *
     * @param Request $request Variable con la solicitud Http
     * 
     * @author  julio.buenadicha@altia.es
     * @access  public
     * @version Release: <package_version>
     *
     * @return Response::json con id y contenido html del modal comentairo
     */
    public function getComentariosEntradaAjax(Request $request)
    {
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();

        // Obtenemos el diccionario y la entrada del GET
        $entrada_id = $request->entrada_id;

        $entrada = dpEntrada_GetEntradaById($entrada_id);
        if ($entrada) {
            // Seguridad - Comprobamos que el usuario conectado es el dueño de la entrada
            $this->authorize('isOwner', $entrada);

            // dpEntrada_datosComunesCosultas($datos);

            // Obtenemos el diccionario personal del usuario conectado
            $diccionario = dpDiccionario_GetDiccionarioByUserActual();
            $datos->diccionario = $diccionario;

            // Obtengo los diccionarios de aula a los que está unido el usuario actual mejorados con los comentarios
            $listaDiccionariosAula = daGetDiccionariosAulaComentariosByUserConectado($diccionario);
            $datos->listaDiccionariosAula = $listaDiccionariosAula;

            $datos->entrada = $entrada;

            $id = 'uid_' . uniqid();

            $html = View::make('layouts.partials.components.modal-comentarosentrada')
                ->with('datos', $datos)
                ->with('titulo_comentarios', __('diccionario.comentarios_entrada_entradas'))
                ->with('id', $id)
                ->with('modal_width', '950px')
                ->render();

            return Response::json(['id' => $id, 'html' => $html]);
        }
    }

    /**
     * Reponde a una petición Ajax 
     * Devuelve la pantalla de ver comentarios del diccionario o el aviso 
     * de que no hay comentarios
     *
     * @param Request $request Variable con la solicitud Http
     * 
     * @author  julio.buenadicha@altia.es
     * @access  public
     * @version Release: <package_version>
     *
     * @return void
     */
    public function getComentariosDiccionarioAjax(Request $request)
    {
        // Obtenemos el diccionario personal del usuario conectado
        $diccionario = dpDiccionario_GetDiccionarioByUserActual();

        $id = 'uid_' . uniqid();
        // $comentariosGenerales = daGetComentariosGeneralesByComentariosVisible();
        
        // $comentariosGenerales = daGetComentariosGeneralesAlumnoAula( getSessionPersona(), getDicAulaActivo() );
        // $comentariosEntradas = daGetComentariosEntradasAlumnoAula( getSessionPersona(), getDicAulaActivo() );
        
        // obtener los datos de todos los diccionarios en los que participa el usuario 
        $diccionariosAula = daGetDiccionariosAulaConectadosByPersonaId(getSessionPersona()['id']);
        $comentariosGenerales = new Collection();
        $comentariosEntradas = new Collection();
        foreach ( $diccionariosAula as $key => $value ) {
            $generales = daGetComentariosGeneralesAlumnoAula( getSessionPersona(), $value );
            $entradas = daGetComentariosEntradasAlumnoAula( getSessionPersona(), $value );

            $comentariosGenerales = $comentariosGenerales->merge( $generales );
            $comentariosEntradas = $comentariosEntradas->merge( $entradas );            
        }

        // DEBUG Comprobar los datos que se devuelven
        // return Response::json([
        //         'id' => $id, 
        //         'generales' => $comentariosGenerales->count(), 
        //         'entradas' => $comentariosEntradas->count(),
        //         'diccionarios' => $diccionariosAula->count() 
        //         //  json_encode( $diccionariosAula )
        // ]);

        // No hay comentarios
        if ( $comentariosGenerales->count() + $comentariosEntradas->count() == 0) {

            $mensaje = __('diccionario.comentarios_diccionario_sincomentarios');
            // si el diccionario tiene la opcion de comentarios visibles
            // if ( )
            // $mensaje .= "No estan activados los comentarios para este diccionario..."; 
            // o algo similar...             
            
            $html = View::make('layouts.partials.components.modal-Aceptar')
                ->with('tooltip', __('diccionario.comentarios_entrada_tooltip'))
                ->with('titulo', __('diccionario.comentarios_entrada_title'))
                ->with('mensaje', $mensaje )
                ->with('boton', __('diccionario.boton_aceptar'))
                ->with('id', $id)
                ->render();
        } else { // SÍ hay comentarios
            // Devolvemos la URL para redirigir
            return Response::json(['redirect' => route('diccionario.comentarios', [$diccionario->id])]);
        }

        return Response::json(['id' => $id, 'html' => $html]);
    }

    /**
     * Muestra la pantalla para crear un comentario o editar el últimno
     *
     * @param Request $request Variable con la solicitud Http con $dicAulaId 
     *                         integer id del diccionario de aula donde el 
     *                         profesor va a comentar
     * 
     * @author  julio.buenadicha@altia.es
     * @access  public
     * @version Release: <package_version>
     *
     * @return void
     */
    public function comentariosGet(Request $request)
    {
        $dicAulaId = $request->id;
        // Obtengo el diccionario de aula con el id recibido por parámetro
        $diccionarioAula = DicAula::find($dicAulaId);
        $this->authorize('esCoordinador', $diccionarioAula);
        // Creo el objeto $datos para pasar datos entre funciones
        $datos = new \stdClass();
        
        

        // Mostrar tab aula 
        $datos->tabs = getDatosTabs(true);

        // Seguridad
        // $this->authorize('isOwner', $diccionarioAula);
        $datos->dicAula = $diccionarioAula;

        $entradas = EnvioEntrada::whereHas('dpEnvio', function ($envio) use ($dicAulaId) {
            $envio->where('dic_aula_id', $dicAulaId);
        })->with('dpEnvio')->get();  
          
        $datos->entradas = $entradas;

        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.DiccionarioAula'), 'url' => route('diccionarioaula.get')],
            ['name' => getDicAulaActivo()->titulo, 'url' => route('aula.consulta.all')],
            ['name' => __('diccionario.comentarios_add_diccionario_generales'), 'url' => $request->url]
        ];

        // Participantes
        $datos->participantes = getDicAulaActivo()
            ->participantes()
            ->get()
            ->sortBy('persona.nombre');

        // Obtengo la visibilidad de comentarios
        $visibilidad = $diccionarioAula->comentarios_visibles;
        switch ($visibilidad) {
            case config('ctes.comentarios_visibles.no_visible'): // 0
                $mensaje_visibilidad = __('diccionario.comentarios_comentarios_visibles_no_visible');
                break;
            case config('ctes.comentarios_visibles.anteriores_a'): // 2
                $mensaje_visibilidad = __('diccionario.comentarios_comentarios_visibles_anteriores_a', ['fecha' => $diccionarioAula->comentarios_visibles_anteriores_a->format('d/m/Y')]);
                break;
            case config('ctes.comentarios_visibles.visible'): // 1
            default:
                $mensaje_visibilidad = __('diccionario.comentarios_comentarios_visibles_visible');
                break;
        }
        $datos->mensaje_visibilidad = $mensaje_visibilidad;


        // Mostramos la ventana
        return view('comentarios/comentariosDiccionarioEnviar')->with('datos', $datos);
    }

    /**
     * Graba un nuevo comentario o el últimno modificado
     *
     * @param Request $request Variable con la solicitud Http con $dicAulaId 
     *                         integer id del diccionario de aula donde el 
     *                         profesor va a comentar
     * 
     * @author  julio.buenadicha@altia.es
     * @access  public
     * @version Release: <package_version>
     *
     * @return redirect a 'comentarios.enviar' o a la misma pagina mostrando errores 
     *                  si falla el validador
     */
    public function comentariosPost(Request $request)
    {
        // Validamos los parámetros de entrada
        $validator = Validator::make($request->all(), [
            'dic_aula_id' => 'required|integer',
            'estudiante' => 'required|integer',
            'tinyComentario' => 'required',
            'radio' => 'required',
        ],[
            'tinyComentario.required'=> __('diccionario.comentariosPost_tinyComentarioError'), 

        ]);

        if ($validator->fails()) {
            return redirect()
                ->refresh()
                ->withErrors($validator)
                ->withInput();
        }

        // Obtenemos los parámetros del request
        $dic_aula_id = $request->dic_aula_id;
        $estudiante = $request->estudiante;
        $tinyComentario = $request->tinyComentario;
        $radio = $request->radio;
        $hiddenComentarioOriginalId = $request->hiddenComentarioOriginalId;

        // Obtengo el diccionario de aula con el id recibido por parámetro
        $diccionarioAula = DicAula::find($dic_aula_id);

        // Seguridad
        // $this->authorize('isOwner', $diccionarioAula);

        // Obtengo el diccionario personal a partir del estudiante
        $diccionarioPersonal = dpDiccionario_GetDiccionarioByPersonaId($estudiante);

        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];

        // Generamos la fecha de envío
        $fecha_envio = Carbon::createFromTimestamp(time())->toDateTimeString();

        ComentarioGeneral::updateOrCreate(
            [
                'id' => $hiddenComentarioOriginalId,
            ],
            [
                'dic_personal_id' => $diccionarioPersonal->id,
                'persona_id' => $persona_id,
                'dic_aula_id' => $dic_aula_id,
                'comentario' => $tinyComentario,
                'fecha_envio' => $fecha_envio,
                'estado' => config('ctes.estado_comentario_entrada.visible_no_leido')
            ]
        );

        // Establecemos el mensaje del success
        $nombrePersona = $diccionarioPersonal->persona->nombre . ' ' . $diccionarioPersonal->persona->apellidos;
        $mensaje = __('diccionario.comentarios_add_success', ['nombre_persona' => $nombrePersona]);

        // Mostramos la ventana
        return redirect()->route('comentarios.enviar', ['id' => $dic_aula_id])
            ->with('lastEstudiante', $estudiante)
            ->with('success', $mensaje);
    }

    /**
     * Borra un nuevo comentario recibido por parametro
     *
     * @param Request $request Variable con la solicitud Http con $dicAulaId 
     *                         integer id del diccionario de aula donde el 
     *                         profesor va a comentar
     * 
     * @author  julio.buenadicha@altia.es
     * @access  public
     * @version Release: <package_version>
     *
     * @return void
     */
    public function deleteComentarioGet(Request $request)
    {
        // Obtenemos los parámetros del request
        $dic_aula_id = $request->id;
        $comentario_id = $request->comentario_id;

        // Obtengo el diccionario de aula con el id recibido por parámetro
        $diccionarioAula = DicAula::find($dic_aula_id);

        // Buscamos el comentario para ver si existe
        $comentario = ComentarioGeneral::find($comentario_id);

        if ($comentario) {
            // Seguridad
            $this->authorize('isOwner', $comentario);

            daComentarios_DeleteComentarioGeneralById($comentario_id);

            $mensaje = __('diccionario.comentarios_add_borrado');

            // TODO: esta comentado revisar si esto deveria devolver el redirecto o no 
            // return redirect()->route('comentarios.enviar', ['id' => $dic_aula_id])
            //     ->with('success', $mensaje);
        }
    }
}
