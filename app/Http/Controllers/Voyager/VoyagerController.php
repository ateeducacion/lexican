<?php

namespace App\Http\Controllers\Voyager;

use TCG\Voyager\Http\Controllers\VoyagerController as BaseVoyagerController;

/**
 * Voyager Controller
 *
 * @category Voyager
 * @package  App\Http\Voyager
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class VoyagerController extends BaseVoyagerController
{
    public function index()
    {
        return view('voyager::index');
    }
}
