<?php

namespace App\Http\Controllers;

// use App\Http\Requests;

use App\Models\DicAula;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use PDF;


/**
 * Class PDFController
 * 
 * @category Laravel
 * @package  App\Http\Controllers
 * @author   Trillo <josecarlos.trillo@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class PDFController extends Controller
{
    /**
     * Ordena generar del DICIONARIO PERSONAL un PDF a partir de una vista. Utiliza el DOMPDF para ello
     *
     * @author Trillo <josecarlos.trillo@altia.es>
    *  @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version  Release: <package_version>
     * 
     * @param int $idDiccionarioPersonal
     * @param Request $request
     * 
     * @return Barryvdh\DomPDF
     */
    public function generarPdfDPGET(int $idDiccionarioPersonal, Request $request)
    {
        // Obtenemos de la session el ID del usuario que está logueado.
        //$persona_id = getSessionPersona()['id'];

        $showHidden = false;
        if( $request->showHidden && $request->showHidden == 1 ) {
            $showHidden = true;
        }

        $listaTematicasSeleccionadas = $request->listaTematicas;        
        // si se ha marcado exportar etiquetas y no se a selecionado nigunga dueve mensaje de error:
        if ($request->exportOnlyTagged && !$listaTematicasSeleccionadas){
            return redirect(            
                route('pdfPersonal.options',[$idDiccionarioPersonal])
            )->withErrors(__('diccionario.exportarpdf__no_tematicas_seleccionadas'));
        }

        // Diccionario que queremos pasar al PDF.
        $data = getDataByDiccionarioPersonalId(
            $idDiccionarioPersonal, 
            $showHidden, 
            $request->listaTematicas
        );

        // Mostrar PDF y permitir descargar
        $contenidoView = "/layouts/partials/pdf/dpMainPDFView";

        set_time_limit(300); // Extends to 5 minutes.
        $pdf = PDF::loadView($contenidoView, ['datos'=>$data]);
        
        // return view( $contenidoView)->with(['datos'=> $data]);
        return $pdf->stream('dp_' . $data['nombreCompleto'] . '.pdf');

        // Descargar PDF directamente sin mostrar previamente
        /*
        $pdf = PDF::loadView($contenidoView, ['datos'=>$data]);
        return $pdf->download('fichero.pdf');
        */
    }

    /**
     * Ordena generar un PDF a partir de una vista. Utiliza el DOMPDF para ello
     *
     * @author Trillo <josecarlos.trillo@altia.es>
     * @version  Release: <package_version>
     * 
     * @return Barryvdh\DomPDF
     */
    public function generarPdfDAGET($idDiccionarioAula, Request $request)
    {      
        $this->authorize('esParticipante', DicAula::find($idDiccionarioAula));
        
        $listaTematicasSeleccionadas = $request->listaTematicas;        
        
        if ($request->exportOnlyTagged && !$listaTematicasSeleccionadas) return redirect(
                    route('pdfAula.options',[$idDiccionarioAula])
                )->withErrors(__('diccionario.exportarpdf__no_tematicas_seleccionadas'));

        // Diccionario que queremos pasar al PDF.
        $data = getDataByDiccionarioAulaId($idDiccionarioAula);
        
        try {
            if ( $request->exportOnlyTagged ){
                $data = getDataByDiccionarioAulaIdTematica($idDiccionarioAula, $listaTematicasSeleccionadas);
            }
        } catch (\Throwable $th) {
            if ($th->getCode()==204) 
            {
                return redirect(
                    route('pdfAula.options', [$idDiccionarioAula])
                )->withErrors(__('diccionario.exportarpdf__no_entradas'));
            }
            // si no es el error de arriba lanza el throw 
            throw $th;
        }

        // Mostrar PDF y permitir descargar
        $contenidoView = "/layouts/partials/pdf/daMainPDFView";

        // return view( $contenidoView)->with(['datos'=> $data]);
        $pdf = PDF::loadView($contenidoView, ['datos'=>$data]);
        return $pdf->stream('da_'. $data['titulo']  .'.pdf');

        // Descargar PDF directamente sin mostrar previamente
        /*
        $pdf = PDF::loadView($contenidoView, ['datos'=>$data]);
        return $pdf->download('fichero.pdf');
        */
    }

    /**
     * Muestra la vista de opciones para exportar a PDF el diccionario personal
     * 
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     *
     * @param Request $request
     * @return View
     */
    public function optionsPdfPersonal(Request $request)
    {   
        $datos = new \stdClass();

        $persona_id = getSessionPersona()['id'];
        $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id);
        $datos->diccionario_id = $diccionario->id;

        $listaCategorias = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.categoria'));
        $listaGeneros = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.genero'));        
        $listaTematicas = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.tematica'));

        // Mandamos como Disponibles las temáticasque no están ya seleccionadas
        $listaTematicasSeleccionadas = [];
        $listaTematicasDisponibles = $listaTematicas;
        
        $datos->listaCategorias = $listaCategorias;
        $datos->listaGeneros = $listaGeneros;
        $datos->listaTematicasDisponibles = $listaTematicasDisponibles;
        $datos->listaTematicasSeleccionadas = $listaTematicasSeleccionadas;

        $datos->n_entradasOcultas = dpDiccionario_GetNumEntradasOcultas($diccionario->id);

        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.diccionario_personal'), 'url' => route('diccionariopersonal.get')],            
            ['name' => __('diccionario.exportarpdf__titulo'), 'url' => $request->url],
        ];

        $datos->action = route('pdfdp.download', $datos->diccionario_id );

        return view('diccionario.dpOptionsPDFView', ['datos'=>$datos]);
    }

    /**
     * Muestra la vista de opciones para exportar a PDF el diccionario aula
     * 
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     *
     * @param Request $request
     * @return View
     */
    function optionsPdfAula(Request $request)
    {
        $datos = new \stdClass();        
        $datos->tabs = getDatosTabs(true);        
        $datos->diccionario_id = $request->diccionario_id;
        $diccionario = DicAula::find($datos->diccionario_id);
        // comprobar que el usuario tiene permiso para ver el diccionario
        $this->authorize('esParticipante', $diccionario);

        $listaCategorias = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.categoria'));
        $listaGeneros = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.genero'));        
        $listaTematicas = mstEntradaValor_GetValoresByEntrada(config('ctes.campos_acepcion.tematica'));

        // Mandamos como Disponibles las temáticasque no están ya seleccionadas
        $listaTematicasSeleccionadas = [];
        $listaTematicasDisponibles = $listaTematicas;
        
        $datos->listaCategorias = $listaCategorias;
        $datos->listaGeneros = $listaGeneros;
        $datos->listaTematicasDisponibles = $listaTematicasDisponibles;
        $datos->listaTematicasSeleccionadas = $listaTematicasSeleccionadas;

        $datos->breadcrumb = [
            ['name' => __('diccionario.Inicio'), 'url' => url('/')],
            ['name' => __('diccionario.DiccionarioAula'), 'url' => route('diccionarioaula.get')],
            ['name' => $diccionario->titulo, 'url' => route('aula.consulta.all')],
            ['name' => __('diccionario.exportarpdf__titulo'), 'url' => $request->url],
        ];

        $datos->action = route('pdfda.download', $datos->diccionario_id );

        return view('diccionario.daOptionsPDF', ['datos'=>$datos]);
    }

}
