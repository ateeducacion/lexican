<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

/**
 * Controlador de las ventanas modales genéricas
 * 
 * @package App\Http\Controllers
 * 
 * @access public
 * @author Julio Buenadicha <julio.buenadicha@altia.es>
 * @version  Release: <package_version>
 * 
 */
class ModalAjaxController extends Controller
{

    /**
     * Reponde a una petición Ajax 
     * 
     * Devuelve una ventana de confirmación simple de Aceptar-Cancelar
     *
     * @author Julio Buenadicha <julio.buenadicha@altia.es>
     * @version  Release: <package_version>
     *
     * @param Request $request
     *
     * @return void
     */
    public function getAceptarCancelarAjax(Request $request)
    {
        // Obtenemos ellos datos necesarios del GET
        $titulo = $request->titulo;
        $mensaje = $request->mensaje;
        $action = $request->action;
        $modal_width = $request->modal_width;

        $id = 'uid_' . uniqid();

        $html = View::make('layouts.partials.components.modal-AceptarCancelar')
            ->with('id', $id)
            ->with('titulo', $titulo)
            ->with('mensaje', $mensaje)
            ->with('action', $action)
            ->with('modal_width', $modal_width)
            ->render();

        return Response::json(['id' => $id, 'html' => $html]);
    }
}
