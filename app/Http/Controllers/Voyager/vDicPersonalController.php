<?php

namespace App\Http\Controllers\Voyager;

use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Database\Schema\SchemaManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Http\Controllers\VoyagerBaseController as BaseVoyagerBreadController;
use Illuminate\Http\Request;
use App\Http\Controllers\Voyager\VoyagerCompassController;

class vDicPersonalController extends BaseVoyagerBreadController
{
    public function index(Request $request)
    {
        $vcc= new VoyagerCompassController;
        return $vcc->dicsPersonales();
    }
}

