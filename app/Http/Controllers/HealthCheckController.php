<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use TCG\Voyager\Models\Role;

/**
 * Controlador de la página de inicio
 *
 * @category Laravel
 * @package  App\Http\Controllers
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class HealthCheckController extends Controller
{
    /**
     * Healt check para openshift 
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param Request $request
     *
     * @return void
     */
    public function healthCheck(Request $request){
        // dd('hola');
        // aqui se puede realizar distintas rpuebsa y segun eso mostrar respose 200 o otro si falla

        $checks = [
            "ok" => "ok",
        ];
        
        // realizar comprobaciones y si falla algo:
        // return response()->json(['error' => 'invalid'], 401);
        // abort(401, 'Fallo tal cosa'); // tambien devuelve 401 sin mas 
        
        // todo ok
        return response()->json($checks); // 200 
        // response()->json(['success' => 'success'], 200);
    }

}
