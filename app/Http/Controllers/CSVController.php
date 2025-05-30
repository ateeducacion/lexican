<?php

namespace App\Http\Controllers;

use Response;
use File;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use App\Http\Controllers\Voyager\VoyagerCompassController;
use Exception;
use Illuminate\Support\Facades\Storage;
use Debugbar;

class CSVController extends Controller
{

    public function generarCsvAll(Request $request)
    {
        $path = env('EXPORT_PATH', './public/exports');
        $nameFile = 'dataAll_export_' . date("Y-m-d") . ".csv";

        $listFunctionNames = array();

        switch($request->get("option")){
            case "dicsCursos":
                $listFunctionNames = ["queryNumDicsCursoEscolar", "queryDicsVigentesCursoActual", "queryPersonasParticipantesDiccionarios_AulasVigentes"];
                break;
            case "dicsPersonales":
                $listFunctionNames = ["queryDicsCreadosCursoEscolar", "queryEntradasPublicadasDiccionarioAula", "queryEntradasCursoEscolar"];
                break;
            case "centros":
                $listFunctionNames = ["queryCentrosAnoEscolar"];
                break;
        }
            
        //guardamos la respuesta del la query en un array
        $fieldsValues = array();
        $vcc = new VoyagerCompassController();

        foreach ($listFunctionNames as $functionName) {
            //ejecutamos la query sql
            $aux = array();
            $response = call_user_func(array($vcc, $functionName));

            foreach ($response as $k => $v)
                array_push($aux, $v);

            array_push($fieldsValues, $aux);
        }

        if (count($fieldsValues) == 0) {
            return null;
        }

        $arrayValues = array();
        $count = 0;

        foreach ($fieldsValues as $key => $value) {
            //obtenemos los nombres de los campos
            $aux = array();
            foreach ($value as $obj) {
                array_push($aux, array_keys(get_object_vars($obj)));
            }
            //almacenamos los nombres de los campos con los valores con un array que contiene los datos de esos campos
            if(count($fieldsValues[$count])>0)
            {
                array_push($arrayValues, [$aux[0], $fieldsValues[$count]]);
                $count += 1;
            }
        }

        if (!File::exists($path)) {
            File::makeDirectory($path);
        }

        ob_start();
        $df = fopen($path . '/' . $nameFile, 'w');

        //$value[0] contien en nombre de los campos y $value[1] contiene todos los valores de esos campos
        foreach ($arrayValues as $key => $value) {
            fputcsv($df, $value[0]);

            foreach ($value[1] as $key => $value) {
                $valuesObjList = get_object_vars($value);
                fputcsv($df, $valuesObjList);
            }
        }

        fclose($df);

        $this->deleteOldCsvFiles($path);

        return response()->download($path . '/' . $nameFile);
    }


    public function generarCSV(Request $request)
    {
        $path = env('EXPORT_PATH', './public/exports');
        $nameFile = 'data_export_' . date("Y-m-d") . ".csv";

        //Ejecucion de la query
        $vcc = new VoyagerCompassController();
        $fieldsValues = call_user_func(array($vcc, $request->get("methodName")));

        if (count($fieldsValues) == 0) {
            return null;
        }

        //obtenemos el nombre de los campos
        $nombresCamposCSV = array();

        foreach ($fieldsValues as $key => $value) {
            $nombresCamposCSV = array_keys(get_object_vars($value));
            break;
        }

        if (!File::exists($path)) {
            File::makeDirectory($path);
        }

        ob_start();
        $df = fopen($path . '/' . $nameFile, 'w');
        fputcsv($df, $nombresCamposCSV);

        foreach ($fieldsValues as $key => $value) {
            $valuesObjList = get_object_vars($value);
            fputcsv($df, $valuesObjList);
        }
        fclose($df);

        $this->deleteOldCsvFiles($path);

        return response()->download($path . '/' . $nameFile);
    }


    //Borramos los .csv de la carpeta public que no contenga en el nombre la fecha del dia de actual.
    public function deleteOldCsvFiles($path)
    {
        $listFiles = scandir($path);

        if (count($listFiles) > 1) // el index 0 y 1 son "." , "..", apartir del indice 1 empiezan los nombres de los archivos
        {
            foreach ($listFiles as $file) {
                if ( file_exists($path . "/" . $file)  && str_contains($file, "data")
                    && str_contains($file, '.csv') && !str_contains($file, date('Y-m-d'))) {
                        unlink($path . '/' . $file);
                }
            }
        }
    }
}//class