<?php

namespace App\Http\Controllers\Voyager;

use App\Models\DicAula;
use App\Models\DicAulaAtemporal;
use Artisan;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use TCG\Voyager\Http\Controllers\VoyagerCompassController as BaseVoyagerCompassController;

/**
 * Voyager Compass Controller
 *
 * @category Voyager
 * @package  App\Http\Voyager
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class VoyagerCompassController extends BaseVoyagerCompassController
{
    public function logs_viewer(Request $request)
    {
        // Check permission
        $this->authorize('browse_compass');
        //Check if app is not local
        if (!\App::environment('local') && !config('voyager.compass_in_production', false)) {
            throw new AccessDeniedHttpException();
        }

        $message = '';
        $active_tab = '';

        if ($this->request->input('log')) {
            $active_tab = 'logs';
            LogViewer::setFile(base64_decode($this->request->input('log')));
        }

        if ($this->request->input('download')) {
            return $this->download(LogViewer::pathToLogFile(base64_decode($this->request->input('download'))));
        } elseif ($this->request->has('del')) {
            app('files')->delete(LogViewer::pathToLogFile(base64_decode($this->request->input('del'))));

            return $this->redirect($this->request->url().'?logs=true')->with([
                'message'    => __('voyager::compass.logs.delete_success').' '.base64_decode($this->request->input('del')),
                'alert-type' => 'success',
            ]);
        } elseif ($this->request->has('delall')) {
            foreach (LogViewer::getFiles(true) as $file) {
                app('files')->delete(LogViewer::pathToLogFile($file));
            }

            return $this->redirect($this->request->url().'?logs=true')->with([
                'message'    => __('voyager::compass.logs.delete_all_success'),
                'alert-type' => 'success',
            ]);
        }

        $logs = LogViewer::all();
        $files = LogViewer::getFiles(true);
        $current_file = LogViewer::getFileName();

        return view('voyager::compass.logs', compact('logs', 'files', 'current_file'))->with($message);
    }

    private function redirect($to)
    {
        if (function_exists('redirect')) {
            return redirect($to);
        }

        return app('redirect')->to($to);
    }

    private function download($data)
    {
        if (function_exists('response')) {
            return response()->download($data);
        }

        // For laravel 4.2
        return app('\Illuminate\Support\Facades\Response')->download($data);
    }


    //-----------------------------------------------------
    //  FUNCIONES DICCIONARIOS CURSOS ACTUAL OPCION MENU VOYAGER
    //-----------------------------------------------------

    public function  queryNumDicsCursoEscolar() {
        
        $dicsCurso = \DB::table("dic_aula")
        ->select("ano_ini_curso_escolar", \DB::raw('count(*) as numDiccionarios'))
        ->whereNull('deleted_at')
        ->groupby("ano_ini_curso_escolar")
        ->orderby("ano_ini_curso_escolar", "asc")
        ->get();

        return $dicsCurso;
    }

    public function queryDicsVigentesCursoActual() {

        $vigCursoActual = \DB::table("dic_aula")
        ->select("ano_ini_curso_escolar", \DB::raw('count(*) as numVigentes'))
        ->where("estado", "=", 1)
        ->where("vigencia", ">", 0)
        ->whereNull('deleted_at')
        ->groupby("ano_ini_curso_escolar")
        ->orderby("ano_ini_curso_escolar", "asc")
        ->get();

        return $vigCursoActual;
    }


    public function queryPersonasParticipantesDiccionarios_AulasVigentes(){

        //************************************* 
        //CONSULTA CON EL AÑO
        //************************************* 
        // $data = \DB::table("dic_aula")
        // ->join("dic_aula_participantes", "dic_aula.id", "=", "dic_aula_participantes.dic_aula_id")
        // ->select("ano_ini_curso_escolar",  "dic_aula_participantes.rol_diccionario_id", \DB::raw('count(*) as numPersonas'))
        // ->where("dic_aula.estado", "=", 1)
        // ->where("dic_aula.vigencia", ">", 0)
        // ->groupby("dic_aula.ano_ini_curso_escolar")
        // ->groupby("dic_aula_participantes.rol_diccionario_id")
        // ->orderby("dic_aula.ano_ini_curso_escolar", "asc")
        // ->get();

        $data = \DB::table("dic_aula_participantes")
        ->join("dic_aula", "dic_aula.id", "=", "dic_aula_participantes.dic_aula_id")
        ->select("dic_aula_participantes.rol_diccionario_id", \DB::raw('count(*) as numPersonas'))
        ->where("dic_aula.estado", "=", 1)
        ->where("dic_aula.vigencia", ">", 0)
        ->groupby("dic_aula_participantes.rol_diccionario_id")
        ->orderby("dic_aula_participantes.rol_diccionario_id", "asc")
        ->get();

        return $data;
    }

    public function dicsCursoEscolar(){

        $dicsCurso = $this->queryNumDicsCursoEscolar();
        $dicsVigsCursoActual = $this->queryDicsVigentesCursoActual();
        $persDic_AulasVigentes = $this->queryPersonasParticipantesDiccionarios_AulasVigentes();

        return view('voyager::compass.dicsCursoEsc')
        ->with(compact( 'dicsCurso', 'dicsVigsCursoActual', 'persDic_AulasVigentes'));
    }

    //-----------------------------------------------------
    //  FUNCIONES DICCIONARIOS PERSONAL CREADOS EN EL CURSOS ACTUAL OPCION MENU VOYAGER
    //-----------------------------------------------------

    public function queryDicsCreadosCursoEscolar(){

        $data = \DB::table("dic_personal")
        ->selectRaw("(CASE WHEN month(created_at) > 8 THEN year(created_at) ELSE  year(created_at)-1 END) AS ano_ini_curso, count(*) as numDicsCreados")
        ->whereNull('deleted_at')
        ->groupby("ano_ini_curso")
        ->get();

        return $data;
    }

    function queryEntradasPublicadasDiccionarioAula(){
        $data = \DB::table("envios_entradas")
        ->selectRaw("count(*) as entradasPublicadaDicAulaVigentes")
        ->join("dic_aula_entradas", "envios_entradas.id", "=", "dic_aula_entradas.envio_entrada_id")
        ->join("dic_aula", "dic_aula_entradas.dic_aula_id", "=", "dic_aula.id")
        ->where('envios_entradas.estado' ,'=', 3)
        ->where('envios_entradas.estado', '=', 3)
        ->whereNull('envios_entradas.deleted_at')
        ->where('dic_aula.estado', '=', 1)
        ->get();

        return $data;
    }

    function queryEntradasCursoEscolar(){

        $data = \DB::table("dp_entradas")
        ->selectRaw('YEAR(CASE WHEN MONTH(created_at) > 8 THEN created_at ELSE DATE_SUB(created_at, INTERVAL 1 YEAR) END) AS ano_ini_curso, COUNT(*) as numEntradas')
        ->whereNull('deleted_at')
        ->groupby('ano_ini_curso')
        ->get();

        return $data;
    }

    public function dicsPersonales()
    {
        $dicsPersonalCursoEscolar = $this->queryDicsCreadosCursoEscolar();
        $entradasDiccionarioAula = $this->queryEntradasPublicadasDiccionarioAula();
        $entradasCursoEscolar = $this->queryEntradasCursoEscolar();
        
        return view('voyager::compass.dicsPersonales')
        ->with(compact( 'dicsPersonalCursoEscolar', 'entradasDiccionarioAula' ,'entradasCursoEscolar'));
    }

    //-----------------------------------------------------
    //  FUNCIONES CURSOS CURSO ESCOLAR OPCION MENU VOYAGER
    //-----------------------------------------------------

    function queryCentrosAnoEscolar()
    {

        $centrosAnoEscolar = \DB::table("centros")
        ->select (
            \DB::raw('concat("centros") as centros'), 
            \DB::raw('count(*) as totalCentros'),  
            // \DB::raw('concat( date_format(created_at, "%Y"), "/", date_format(created_at, "%Y")+1 ) as añoPrueba'),  
            \DB::raw( 
                '(CASE 
                    WHEN date_format(created_at, "%m") < 9 
                    THEN (date_format(created_at, "%Y") - 1)
                    ELSE concat( date_format(created_at, "%Y"), "/", date_format(created_at, "%Y") + 1 )
                END) AS curso'
                ) )
        ->whereNull('deleted_at')
        ->where('estado','=',1)
        ->groupby(\DB::raw( 
                    '(CASE 
                        WHEN date_format(created_at, "%m") < 9 
                        THEN (date_format(created_at, "%Y") - 1)
                        ELSE concat( date_format(created_at, "%Y"), "/", date_format(created_at, "%Y") + 1 )
                    END)'
                ))
        ->get();

        return $centrosAnoEscolar;
    }


    public function centrosAñoEscolar(){

        $centrosAnoEscolar = $this->queryCentrosAnoEscolar();
        //dd($centrosAnoEscolar);

        return view('voyager::compass.centrosAñoEscolar')
        ->with(compact('centrosAnoEscolar'));
    }



    //-----------------------------------------------------
    //-----------------------------------------------------

    public function vigencia() {
        // \DB::enableQueryLog();
        // $dicConVigencias = daGetDiccionariosConVigencias()->get();
        // dd(\DB::getQueryLog()); // Show results of log


        $dicAtemporales = DicAula::whereHas('atemporal',
        function ($query) {
            return $query->where('estado', config('ctes.estados.activo'));
        })->get();

        //$dicTodos = DicAula::all();
        //Obtenemos un array con de DicAula que esten vigentes
        $dicTodos = $this->getListaDiccionarios(1);//1 = vigentes

        return view('voyager::compass.vigencia', [
            // 'dicConVigencias' => $dicConVigencias,
            'dicTodos' => $dicTodos,
            'dicAtemporales' => $dicAtemporales,
            'tipoDiccionario' => 1,
            // 'warnings' => $avisos,
            // 'current_warning' => $avisos->first()
        ]);
    }

    

    public function comprobarVigenciaDicionariosAula() {

        // diccionarios que caducan este año con un solo año de vigencia:

        $dicConVigencias = daGetDiccionariosConVigencias()->get();
        $ACTIVADO=1;
        $count = 0;
        $dicModificados = [];
        $cambiado = false;
        foreach ($dicConVigencias as $dicAula) {
            if ($dicAula->estado==$ACTIVADO) {

                $cambiado = daDesactivarSiNoVigente($dicAula);
                if ($cambiado){
                    $now = Carbon::now();
                    $dicModificados[] = [
                        'id' => $dicAula->id,
                        'titulo'=> $dicAula->titulo,
                        'inicio cuso' => $dicAula->ano_ini_curso_escolar,
                        'años vigencia' =>  $dicAula->vigencia,
                        'fecha cambio' => $now->toDateTimeString()
                    ];
                    $count++;
                }
            }
        }
        $out = $this->redirect(route('vigencia'));
        if ($count>0){
            $out->with([
                'message'    => __('admin.vigencia_desactivados_diccionarios', [ 'num'=>$count ]),
                'alert-type' => 'success',
            ]);
            $now = Carbon::now();
                customLoggin(
                    config('ctes.log_levels.info'),
                    config('ctes.log_types.app_info'),
                    ['file' => __FILE__, 'line' => __LINE__],
                    PHP_EOL . json_encode($dicModificados, JSON_PRETTY_PRINT),
                    'Diccionario desacitivado'
                );
        } else {
            $out->with([
                'message'    => __('admin.vigencia_no_cambios'),
                'alert-type' => 'success',
            ]);
        }

        return $out;
    }

    public function setAtemporalDicAula($diccionario_id) {
        $dicAula = DicAula::find($diccionario_id);

        //$dicAula->atemporal = config('ctes.estados.activo');
        try {
            $dicAula->setAtemporal();
        } catch (\Throwable $th) {
            dd($th);
        }

        $out = $this->redirect(route('vigencia'));
        $out->with([
            'message'    => __('admin.vigencia_atemporal', [ 'nombre'=>$dicAula->titulo ]),
            'alert-type' => 'success',
        ]);

        return $out;
    }

    public function unsetAtemporalDicAula($diccionario_id) {
        $dicAula = DicAula::find($diccionario_id);

        // $dicAula->atemporal = config('ctes.estados.activo');
        try {
            $dicAula->setAtemporal(false);
        } catch (\Throwable $th) {
            dd($th);
        }

        $out = $this->redirect(route('vigencia'));
        $out->with([
            'message'    => __('admin.vigencia_atemporal_unset', [ 'nombre'=>$dicAula->titulo ]),
            'alert-type' => 'success',
        ]);

        return $out;
    }

    public function comporbarVigenciaDiccionario($diccionario_id) {
        $dicAula = DicAula::find($diccionario_id);
        $out = $this->redirect(route('vigencia'));
        $cambiado = false;
        $ACTIVADO = 1;
        try {
            if ($dicAula->estado==$ACTIVADO) {
                $cambiado = daDesactivarSiNoVigente($dicAula);
            }

            if($cambiado){
                $out->with([
                    'message'    => __('admin.vigencia_desactivado', [ 'nombre'=>$dicAula->titulo ]),
                    'alert-type' => 'success',
                ]);

                // TODO: LOG
                //  *Se debe auditar en el log la operación: fecha+ desactivado de XXX diccionarios no vigentes
                $now = Carbon::now();
                customLoggin(
                    config('ctes.log_levels.info'),
                    config('ctes.log_types.app_info'),
                    ['file' => __FILE__, 'line' => __LINE__],
                    PHP_EOL . json_encode([
                        'id' => $diccionario_id,
                        'titulo'=> $dicAula->titulo,
                        'inicio cuso' => $dicAula->ano_ini_curso_escolar,
                        'años vigencia' =>  $dicAula->vigencia,
                        'fecha cambio' => $now->toDateTimeString()
                    ], JSON_PRETTY_PRINT),
                    'Diccionario desacitivado'
                );
            } else {
                $out->with([
                    'message'    => __('admin.vigencia_no_cambios'),
                    'alert-type' => 'warning',
                ]);
            }

        } catch (\Throwable $th) {
            throw $th;
        }

        return $out;
    }

    public function diccionariosVigentesAtemporalJson() {
        $dics = DicAula::whereHas('atemporal',
        function ($query) {
            return $query->where('estado',1);
        })->get();

        return $dics->toJson();
    }

    public function diccionariosVigentesJson() {
        $dicConVigencias = daGetDiccionariosConVigencias()->get();

        // return ['diccionarios' => $dicConVigencias];
        return $dicConVigencias->toJson();
    }

    public function diccionariosVigentesTodosJson() {
        $dics = DicAula::all();

        return $dics->toJson();
    }

    //si $esVigente == true devuelve la lista de diccionarios vigentes, en caso contrario devuelve los diccionarios NO vigentes
    public function diccionariosVigentes_NoVigentesArray($esVigente, $estado)
    {
        $listDics = array();

        $sEstado = ($estado) ? 'ctes.estados.activo' : 'ctes.estados.inactivo';

        $dics = DicAula::where('estado', config( $sEstado ))
        -> orderby("titulo","asc")
        ->get();

        foreach ($dics as $value)
        {
            if(daEsVigente($value) == $esVigente && !$value->esAtemporal())
                array_push($listDics, $value);
        }

        return $listDics;
    }

    //obtenemos los diccionarios con estado 1 y donde el año de fin es menor que el año de curso actual.
    public function diccionariosErroresDeEstado()
    {
        $listDics = array();

        $dics = DicAula::where("estado", config('ctes.estados.activo'))
        ->get();

        foreach ($dics as $value) {
           if(daEsVigente($value)==false && !$value->esAtemporal())
                array_push($listDics, $value);
        }

        //dd($listDics);
        return $listDics;
    }

    //devuelve un array de diccionarios
    public function getListaDiccionarios($tipoDiccionario)
    {
        $dicsList = array();
        $estadosDiccionarios = ["noVigente", "vigente", "atemporal","errores"];

        switch($estadosDiccionarios[$tipoDiccionario])
        {
            case "noVigente": //No vigente
                $dicsList=$this->diccionariosVigentes_NoVigentesArray(false, false);
                break;

            case "atemporal"://atemporal
                $dicsList = DicAula::whereHas('atemporal',
                function ($query) {
                    return $query->where('estado', config('ctes.estados.activo'));
                })->get();
                break;

            case "errores": // errores de estado
                $dicsList = $this->diccionariosErroresDeEstado();
                break;

            default: // vigente
                $dicsList=$this->diccionariosVigentes_NoVigentesArray(true,true);
                break;
            }

        return $dicsList;
    }

    public function getDatosTipoDiccionarioSelecionado($tipoDiccionario)
    {
        //Atemporales
        $dicAtemporales = DicAula::whereHas('atemporal',
        function ($query) {
            return $query->where('estado', config('ctes.estados.activo'));
        })->get();

        //Obtenemos un array de DicAula dependiendo del valor que tengamos marcado en select
        // Vigente = 1 , noVigente = 0,  atemporal =2, error estado = 3
        $dicList = $this->getListaDiccionarios($tipoDiccionario);

        return view('voyager::compass.vigencia', [
            // 'dicConVigencias' => $dicConVigencias,
            'dicTodos' => $dicList,
            'dicAtemporales' => $dicAtemporales,
            'tipoDiccionario' => $tipoDiccionario,
            // 'warnings' => $avisos,
            // 'current_warning' => $avisos->first()
        ]);

    }

    public function activarDiccionarioCursoVigente($id)
    {
        //Modificamos los datos del diccionario
        $dicAula = DicAula::find($id);

        $anoActual = getAnoIniCursoEscolar( new \Datetime() );

        $dicAula->estado = 1;

        $dicAula->vigencia = ($anoActual) - $dicAula->ano_ini_curso_escolar +1 ;
        $dicAula->save();

        // return back();
        $out = $this->redirect(route('vigencia'));

        $out->with([
            'message'    => __('admin.activar_no_vigente', [ 'nombre'=>$dicAula->titulo ]),
            'alert-type' => 'success',
        ]);

        return $out;
    }
}

/***
**** Credit for the LogViewer class
**** https://github.com/rap2hpoutre/laravel-log-viewer
***/
class LogViewer
{
    /**
     * @var string file
     */
    private static $file;

    private static $levels_classes = [
        'debug'     => 'info',
        'info'      => 'info',
        'notice'    => 'info',
        'warning'   => 'warning',
        'error'     => 'danger',
        'critical'  => 'danger',
        'alert'     => 'danger',
        'emergency' => 'danger',
        'processed' => 'info',
    ];

    private static $levels_imgs = [
        'debug'     => 'info',
        'info'      => 'info',
        'notice'    => 'info',
        'warning'   => 'warning',
        'error'     => 'warning',
        'critical'  => 'warning',
        'alert'     => 'warning',
        'emergency' => 'warning',
        'processed' => 'info',
    ];

    /**
     * Log levels that are used.
     *
     * @var array
     */
    private static $log_levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
        'processed',
    ];

    const MAX_FILE_SIZE = 52428800; // Why? Uh... Sorry

    /**
     * @param string $file
     */
    public static function setFile($file)
    {
        $file = self::pathToLogFile($file);

        if (app('files')->exists($file)) {
            self::$file = $file;
        }
    }

    /**
     * @param string $file
     *
     * @throws \Exception
     *
     * @return string
     */
    public static function pathToLogFile($file)
    {
        $logsPath = storage_path('logs');

        if (app('files')->exists($file)) { // try the absolute path
            return $file;
        }

        $file = $logsPath.'/'.$file;

        // check if requested file is really in the logs directory
        if (dirname($file) !== $logsPath) {
            throw new \Exception('No such log file');
        }

        return $file;
    }

    /**
     * @return string
     */
    public static function getFileName()
    {
        return basename(self::$file);
    }

    /**
     * @return array
     */
    public static function all()
    {
        $log = [];

        $pattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\].*/';

        if (!self::$file) {
            $log_file = self::getFiles();
            if (!count($log_file)) {
                return [];
            }
            self::$file = $log_file[0];
        }

        if (app('files')->size(self::$file) > self::MAX_FILE_SIZE) {
            return;
        }

        $file = app('files')->get(self::$file);

        preg_match_all($pattern, $file, $headings);

        if (!is_array($headings)) {
            return $log;
        }

        $log_data = preg_split($pattern, $file);

        if ($log_data[0] < 1) {
            array_shift($log_data);
        }

        foreach ($headings as $h) {
            for ($i = 0, $j = count($h); $i < $j; $i++) {
                foreach (self::$log_levels as $level) {
                    if (strpos(strtolower($h[$i]), '.'.$level) || strpos(strtolower($h[$i]), $level.':')) {
                        preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\](?:.*?(\w+)\.|.*?)'.$level.': (.*?)( in .*?:[0-9]+)?$/i', $h[$i], $current);
                        if (!isset($current[3])) {
                            continue;
                        }

                        $log[] = [
                            'context'     => $current[2],
                            'level'       => $level,
                            'level_class' => self::$levels_classes[$level],
                            'level_img'   => self::$levels_imgs[$level],
                            'date'        => $current[1],
                            'text'        => $current[3],
                            'in_file'     => $current[4] ?? null,
                            'stack'       => preg_replace("/^\n*/", '', $log_data[$i]),
                        ];
                    }
                }
            }
        }

        return array_reverse($log);
    }

    /**
     * @param bool $basename
     *
     * @return array
     */
    public static function getFiles($basename = false)
    {
        $files = glob(storage_path().'/logs/*.log');
        $files = array_reverse($files);
        $files = array_filter($files, 'is_file');
        if ($basename && is_array($files)) {
            foreach ($files as $k => $file) {
                $files[$k] = basename($file);
            }
        }

        return array_values($files);
    }
}
