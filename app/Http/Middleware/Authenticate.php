<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;
use App;

/**
 * Authenticate
 *
 * @category Laravel
 * @package  App\Http\Middleware
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if (!Auth::check()) {
            if(App::environment('local')){
                return route('voyager.login');
            }else{
                return route('cas.login');
            }
        }
    }
}
