<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\User;
/**
 * Controlador del CAS.
 * Obtiene la información del usuario a través del CAS, guarda 
 * la información en sesión y redirige.
 *
 * @category CAS
 * @package  App\Http\Auth
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class CasController extends Controller
{
    /**
     * Obtain the user information from CAS.
     *
     * @return Illuminate\Http\RedirectResponse
     * @access public
     */
    public function callback()
    {
        $user = userValidatorCAUCE(cas()->user()->id);
        if(!$user){
            return abort('403');
        }
        $user = User::where('name', cas()->user()->id)->firstOrFail();
        Auth::loginUsingId($user->id);
        if (Auth::check()) {
            setUserDataSession();
            return redirect()->route('diccionariopersonal.get');
        }else{
            return abort('403');
        }

        // $username = Cas::getUser();
        // Here you can store the returned information in a local User model on your database (or storage).

        // This is particularly usefull in case of profile construction with roles and other details
        // e.g. Auth::login($local_user);
    }
}
