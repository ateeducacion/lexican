<?php

namespace App\Http\Controllers;

use App\Models\EnvioAcepcion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use stdClass;
use Validator;

/**
 * Controlador para EnvioAcepcion
  * 
 * @package App\Http\Controllers
 * 
 * @access public
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 * 
 */
class EnvioAcepcionController extends Controller
{
    /**
     * Lleva a formulario Editar acepcion 
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param Request $request
     *
     * @return view diccionario/aula/acepcionEdit
     */
    public function editAcepcionGet(Request $request)
    {
        $envioAcepcion = EnvioAcepcion::find($request->acepcion_id);
        $diccionarioAula = $envioAcepcion->envioEntrada->dpEnvio->dicAula;

        // dd( 'acepcion', $envioAcepcion );
        $datos = new stdClass;

        $datos->acepcion = $envioAcepcion;
        $datos->entrada = $envioAcepcion->envioEntrada;

        $datos->listaCategorias = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.categoria'));
        $datos->listaGeneros = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.genero'));
        $datos->listaNumeros = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.numero'));
        $datos->listaIdiomas = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.idioma'));
        $datos->mensaje = __('diccionario.acepcion_edicion');
        $datos->listaTematicas = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.tematica'));
        
        
        // Adaptamos las tematicas para que se vean como en DP
        $listaTematicas = new Collection();
        foreach ($envioAcepcion->envioAcepcionTematicas as $tematica) {
            $listaTematicas->add($tematica->dpTematica);
        }
        $datos->listaTematicasSeleccionadas = $listaTematicas;

        $datos->listaTematicasDisponibles = $datos->listaTematicas->diff($datos->listaTematicasSeleccionadas)->all();

        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.DiccionarioAula'), 'url' => route('diccionarioaula.get')],
            ['name' => $diccionarioAula->titulo, 'url' => route('aula.consulta.all')],
            ['name' => $datos->entrada->entrada, 'url' => '' ],
            ['name' => $envioAcepcion->orden, 'url' => $request->url],
            
        ];
        // Mostrar tab aula 
        $datos->tabs = getDatosTabs(true);
        

        return view('diccionario/aula/acepcionEdit')->with('datos', $datos);
        
    }

    /**
     * Cambia los datos en la Acepción enviada
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param Request $request
     *
     * @return void|\Illuminate\Http\RedirectResponse
     */
    public function editAcepcionPost(Request $request)
    {
        // Validamos los parámetros de entrada

        $camposValidar = [
            'definicion' => 'required|max:' . config('ctes.constantes_acepciones.max_definicion'),
            'frase_ejemplo' => 'max:' . config('ctes.constantes_acepciones.max_frase'),
            'orden' => 'required|integer',
        ];
        // Agregamos los campos obligatorios al validador ?
        // $camposValidar.array_push(['' => 'required' ])
        $validator = Validator::make($request->all(), $camposValidar);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $envioAcepcion = EnvioAcepcion::find($request->acepcion_id);
            $diccionarioAula = $envioAcepcion->envioEntrada->dpEnvio->dicAula;
            $envioEntrada = $envioAcepcion->envioEntrada;
            $listaTematicasSeleccionadas = $request->listaTematicas;
            
            if ($envioAcepcion) {
                // comento los datos que no deberian por que cambiar
                // $envioAcepcion->envio_entrada_id = $envioEntrada->id;
                // $envioAcepcion->orden = $request->orden;
                $envioAcepcion->cat_gramatical_id = $request->cat_gramatical_id;
                $envioAcepcion->genero_id = $request->genero_id;
                $envioAcepcion->numero_id = $request->numero_id;
                $envioAcepcion->idioma_id = $request->idioma_id;
                $envioAcepcion->idioma_palabra = $request->idioma_palabra;
                $envioAcepcion->definicion = $request->definicion;
                $envioAcepcion->frase_ejemplo = $request->frase_ejemplo;
                $envioAcepcion->ejemplo2 = $request->ejemplo2;

                // Guardamos la acepción
                $guardado = $envioAcepcion->save();
                if (!getSessionPersona()) {
                    return redirect('cas.login')->with('error', 'No se pudo obtener el usuario conectado');
                }
                if ( $guardado ) {
                    $envioEntrada->updated_by_persona_id = getSessionPersona()['id'];
                    $envioEntrada->save();
                }

                // Guardar las tematicas
                envioAcepcionTematicas_Actualizar($envioAcepcion, $listaTematicasSeleccionadas);

                // Guardamos los medios
                if ($request->hasFile('medio_imagen')) {
                    if ($request->medio_imagen->isValid()) {
                        $file = $request->medio_imagen;
                        $acepcionMedioImagen = envioAcepcionMedio_TratarMedio($file, config('ctes.tipos_medios.imagen'), $envioAcepcion);
                    }
                }

                if ($request->hasFile('medio_audio')) {
                    if ($request->medio_audio->isValid()) {
                        $file = $request->medio_audio;
                        $acepcionMedioAudio = envioAcepcionMedio_TratarMedio($file, config('ctes.tipos_medios.audio'),  $envioAcepcion);
                    }
                }

                if ($request->hasFile('medio_video')) {
                    if ($request->medio_video->isValid()) {
                        // Grabamos el fichero en el STORAGE
                        $file = $request->medio_video;
                        $acepcionMedioVideo = envioAcepcionMedio_TratarMedio($file, config('ctes.tipos_medios.video'), $envioAcepcion);
                    }
                }
                

                // Mostramos la entrada en el dic de aula (buscandola por su nombre)
                return redirect()->route('aula.consulta.palabra', ['consulta' => $envioEntrada->entrada]);
            }


        } catch (\Throwable $th) {
            
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_base_error'),
                ['file' => $th->getFile(), 'line' => $th->getLine()],
                PHP_EOL . json_encode($request->all(), JSON_PRETTY_PRINT),
                $th->getMessage()
            );
            dd( 
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_base_error'),
                ['file' => $th->getFile(), 'line' => $th->getLine()],
                PHP_EOL . json_encode($request->all(), JSON_PRETTY_PRINT),
                $th->getMessage(),
                $th->getTraceAsString()
            );            
            // throw $th;
        }
    }

    /**
     * Borra imagenes,audios y videos vinculados a la acepcion
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editAcepcionDeleteMedio(Request $request)
    {
        
        $envioAcepcion = EnvioAcepcion::find($request->acepcion_id);
        $medio_id = $request->medio_id;
        $medio = $envioAcepcion->envioAcepcionesMedios()->find($medio_id);

        // Borramos medio
        try {
            envioAcepcionMedio_DeleteMedio($medio);
            return redirect()->back();
        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_base_error'),
                ['file' => __FILE__, 'line' => __LINE__],                
                PHP_EOL . json_encode($request->all(), JSON_PRETTY_PRINT),
                $th->getMessage()
            );
            return redirect()->back();
        }        

    }

}