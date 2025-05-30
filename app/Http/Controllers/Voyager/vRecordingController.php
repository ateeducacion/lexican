<?php

namespace App\Http\Controllers\Voyager;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

use Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

use TCG\Voyager\Http\Controllers\VoyagerUserController as BaseVoyagerUserController;


class vRecordingController extends BaseVoyagerUserController
{

    public function index(Request $request)
    {
        return view('voyager::compass.record');
    }
    

    public function showResult(Request $request)
    {
        if($request->has("media_type"))
        {
            return response()
            ->json(['media' =>  $request->url ,'size'=> $request->media_type->getSize() ,'hasMedia' => $request->has("media_type")]);
        }
       
    }

    public function showFotosResult(Request $request){

        if($request->has("url"))
        {
            $imagenCodificadaLimpia = str_replace("data:image/png;base64,", "", urldecode($request->url));
            $imagenDecodificada = base64_decode($imagenCodificadaLimpia);

            $nombreImagenGuardada = "foto_" . uniqid() . ".png";


            //$path = env('EXPORT_PATH', './public/exports');
            // $allPath= $path."/".$nombreImagenGuardada;
            // file_put_contents($allPath, $imagenDecodificada);

            return response()
            ->json(['ok' => $nombreImagenGuardada ]);

        }else{
            return response()
            ->json(['error' => "La url de la imagen estaba vacia" ]);
        }
    }

}



    // $this->deleteOldPNGFiles(env('EXPORT_PATH', './public/exports'));
    // public function deleteOldPNGFiles($path)
    // {
    //     $listFiles = scandir($path);

    //     if (count($listFiles) > 1) // el index 0 y 1 son "." , "..", apartir del indice 1 empiezan los nombres de los archivos
    //     {
    //         foreach ($listFiles as $file) {
    //             if ( file_exists($path . "/" . $file)  && str_contains($file, "foto")) {
    //                     unlink($path . '/' . $file);
    //             }
    //         }
    //     }
    // }


