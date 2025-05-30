<?php

use Carbon\Carbon;
use App\Models\Centro;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client;
use App\Models\Persona;
use App\Models\UserPersona;
use TCG\Voyager\Models\Role;
//use TCG\Voyager\Models\User as VoyagerUser;
use App\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\NivelEstudio;

if (!function_exists('valida_nif_nie')) {
    /**
     * Esta función valida que un NIF o un NIE pasados como parámetros ($documento) sean válidos.
     *
     * @param string $document El número de nif o nie a validar
     * @return boolean true si es un número de documento válido, false si no lo es
     */
    function valida_nif_nie($documento)
    {
        if (strlen($documento) != 9 || preg_match('/^([XYZ]?)([0-9]{7,8})([A-Z])$/i', $documento, $matches) !== 1)
            return false;

        $map = 'TRWAGMYFPDXBNJZSQVHLCKE';
        list(, $nieLetter, $number, $letter) = $matches;

        if ($nieLetter == 'Y')
            $number = '1' . $number;
        else if ($nieLetter == 'Z')
            $number = '2' . $number;

        return strtoupper($letter) === $map[((int) $number) % 23];
    }
}

if (!function_exists('getHttpMessage')) {
    /**
     * Devuelve mensaje de error de una respuesta http segun su codigo
     *
     * @param Integer $code
     * @return String texto del mensaje
     */
    function getHttpMessage($code)
    {
        switch ($code) {
            case 100:
                $text = 'Continue';
                return $text;
            case 101:
                $text = 'Switching Protocols';
                return $text;
            case 200:
                $text = 'OK';
                return $text;
            case 201:
                $text = 'Created';
                return $text;
            case 202:
                $text = 'Accepted';
                return $text;
            case 203:
                $text = 'Non-Authoritative Information';
                return $text;
            case 204:
                $text = 'No Content';
                return $text;
            case 205:
                $text = 'Reset Content';
                return $text;
            case 206:
                $text = 'Partial Content';
                return $text;
            case 300:
                $text = 'Multiple Choices';
                return $text;
            case 301:
                $text = 'Moved Permanently';
                return $text;
            case 302:
                $text = 'Moved Temporarily';
                return $text;
            case 303:
                $text = 'See Other';
                return $text;
            case 304:
                $text = 'Not Modified';
                return $text;
            case 305:
                $text = 'Use Proxy';
                return $text;
            case 400:
                $text = 'Bad Request';
                return $text;
            case 401:
                $text = 'Unauthorized';
                return $text;
            case 402:
                $text = 'Payment Required';
                return $text;
            case 403:
                $text = 'Forbidden';
                return $text;
            case 404:
                $text = 'Not Found';
                return $text;
            case 405:
                $text = 'Method Not Allowed';
                return $text;
            case 406:
                $text = 'Not Acceptable';
                return $text;
            case 407:
                $text = 'Proxy Authentication Required';
                return $text;
            case 408:
                $text = 'Request Time-out';
                return $text;
            case 409:
                $text = 'Conflict';
                return $text;
            case 410:
                $text = 'Gone';
                return $text;
            case 411:
                $text = 'Length Required';
                return $text;
            case 412:
                $text = 'Precondition Failed';
                return $text;
            case 413:
                $text = 'Request Entity Too Large';
                return $text;
            case 414:
                $text = 'Request-URI Too Large';
                return $text;
            case 415:
                $text = 'Unsupported Media Type';
                return $text;
            case 422:
                $text = 'Unprocessable entity';
                return $text;
            case 500:
                $text = 'Internal Server Error';
                return $text;
            case 501:
                $text = 'Not Implemented';
                return $text;
            case 502:
                $text = 'Bad Gateway';
                return $text;
            case 503:
                $text = 'Service Unavailable';
                return $text;

            case 504:
                $text = 'Gateway Time-out';
                return $text;
            case 505:
                $text = 'HTTP Version not supported';
                return $text;
            default:
                $text = 'Unknown http status code: ' . htmlentities($code);
                return $text;
        }
    }
}

if (!function_exists('getMessageErrorCode')) {
    function getMessageErrorCode($origin, $errorCode, $cause)
    {
        switch ($errorCode) {
            case 400:
                $messageCode = ($cause) ? "error.$cause" : "error.invalidParameters";
                return $messageCode;

            case 401:
                $messageCode = "error.noAuthenticate";
                return $messageCode;

            case 403:
                $messageCode = 'error.forbidden';
                return $messageCode;

            case 404:
                // En este caso, la causa debe de ser el nombre del recurso no encontrado
                $messageCode = ($cause) ? "error.$cause.notFound" : "error.resource.notFound";
                return $messageCode;

            case 405:
                $messageCode = "error.methodNotAllowed";
                return $messageCode;

            case 409:
                $messageCode = ($cause) ? "error.$cause" : "error.resource.alreadyExist";
                return $messageCode;

            case 500:
                $messageCode = ($cause) ? $messageCode = "error.$cause" : "error.internalError";
                return $messageCode;

            default:
                # code...
                break;
        }
    }
}



if (!function_exists('customLoggin')) {
    /**
     * Inserta una entrada el los logs del diccionario -> storage/logs/diccionario-aaaa-mm-dd.log
     *
     * @param string $logLevel ordenados de mayor a menor [critical, error, warning, info, debug]
     * @param string $tipoError descripción breve del problema
     * @param array $fileAndLine array que contiene el fichero y la línea desde la que se llama esta función
     * @param string $datos descripción y/o información importante relativa al error, warning... p.e. mensaje de la excepción, errores del validador...
     * @param string $textPersonalizado texto libre que pone el programador para explicar detalles del error . No es obligatorio.
     */
    function customLoggin($logLevel, $tipoError, $fileAndLine, $datos, $textPersonalizado = null)
    {
        try {
            $operacion = Route::getCurrentRoute()->getActionMethod();
        } catch (\Throwable $th) {
            //throw $th;
            $operacion = $th->getMessage();
        }

        $message = Str::replaceFirst(
                    base_path(), '',
                    $fileAndLine['file'])
                . " Line: " . $fileAndLine['line'] . "
                    OPERACIÓN: " . $operacion . "
                    TIPO: $tipoError";

        if ($textPersonalizado) {
            $message = $message . "
                    DESCRIPCIÓN: $textPersonalizado";
        }

        $message = $message . "
                    DATOS: $datos";

        Log::channel('customlog')->$logLevel($message);
    }
}

if (!function_exists('obtenerQueryString')) {
    function obtenerQueryString()
    {
        //Obtenemos los datos de la request, necesario a causa del mismo key para el valor de tipoContenido
        $query  = explode('&', $_SERVER['QUERY_STRING']);
        $params = array();
        foreach ($query as $param) {
            if (strpos($param, '=') === false) {
                $param .= '=';
            }
            list($name, $value) = explode('=', $param, 2);
            $params[urldecode($name)][] = urldecode($value);
        }
        return $params;
    }
}

if (!function_exists('file_build_path')) {
    function file_build_path(...$segments)
    {
        return join(DIRECTORY_SEPARATOR, $segments);
    }
}

if (!function_exists('modify_config_files')) {
    function modify_config_files($request)
    {
        $fichero = $request['fichero'];

        $filesize = filesize(base_path() . "/config/$fichero.php");

        // Parte de la lectura de fichero de configuración y comparación con valores del formulario
        $fp = fopen(base_path() . "/config/$fichero.php", 'r');
        if ($fp) {
            $array = explode("\n", fread($fp, $filesize));
        }
        $overrideEnv = array();
        foreach ($request as $input => $value) {
            //echo "$input => $value</br>";
            foreach ($array as $line => $text) {
                //echo "$line => $text</br>";
                //echo "Testeo: ".last(explode('_', $input))."</br>";
                if (Str::contains($text, $input)) {
                    //if( Str::contains($text, $input) || Str::contains($text, last(explode('_', $input)))   ){
                    if (Str::contains($text, 'env(')) {
                        $overrideEnv += Arr::add($overrideEnv, str::after(str::before($text, "',"), "env('"), $value);
                    } else {
                        $array[$line] = str_replace(last(explode('=>', $text)), " '$value'", $text);
                    }
                }
            }
        }
        fclose($fp);
        //dd($overrideEnv);
        //exit;
        // Fin de lectura, comparación y preparado de datos


        // Parte de escritura de nuevos valores en el fichero de configuración
        $fp = fopen(base_path() . "/config/$fichero.php", 'w');
        $fileContent = "";
        foreach ($array as $values) {
            $fileContent = $fileContent . "$values\n";
        }
        fwrite($fp, $fileContent);
        fclose($fp);
        // Fin de la parte de escritura en el fichero de configuración

        if ($overrideEnv) {
            $envFile = app()->environmentFilePath();
            $str = file_get_contents($envFile);

            foreach ($overrideEnv as $envKey => $envValue) {

                $str .= "\n"; // In case the searched variable is in the last line without \n
                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

                // If key does not exist, add it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}={$envValue}\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                }
            }
            $str = substr($str, 0, -1);
            file_put_contents($envFile, $str);
        }

        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        return redirect()->route('configuracion.view');
        //return view('vendor.voyager.configuracion');
    }
}


if (!function_exists('getFormattedCursoEscolar')) {
    /**
    *   Devuelve una fecha en formato 'AÑO/AÑO+1' según el curso escolar de $time
    *   @access public
    *   @param Integer $time fecha en formato timestamp 
    *   @return String
    *   @version 0.0.1
    */
    function getFormattedCursoEscolar(int $time) {

        $inicioCurso = date_create_from_format('d/m/Y', config('ctes.inicio_curso'));
        $t = $inicioCurso->getTimestamp();
        if ($t) $diamesInicioCurso = date("md", $t );

        // $diamesInicioCurso = date('d/m', $inicioCurso->getTimestamp() );
        $datetime = new DateTime();
        $datetime->setTimestamp($time);

        $diames = date("md", $time);

        $formattedTime = Carbon::createFromTimestamp($time);
        if ($diames >= $diamesInicioCurso) {
            return $formattedTime->year . '/' . ($formattedTime->year + 1);
        } else {
            return ($formattedTime->year - 1) . '/' . $formattedTime->year;
        }
    }
}

/**
 * Devuelve el curso con el formato definido dado el año inical
 *
 * @param [type] $year_ini
 * @return String Curso escolar con el formato 2000/2001
 */
if (!function_exists('getStrCursoByAnoIni')) {
function getStrCursoByAnoIni($year_ini){
    return sprintf( '%s/%s', $year_ini, $year_ini+1 );
}
}

if (!function_exists('getAnoIniCursoEscolar')) {
    /**
     * Devuelve el año en formato YYYY del inicio del curso correspondiente a la fecha recibida por parámetro.
     *  Se calcula teniendo en cuenta que el curso comienza en la fecha definida en config('ctes.inicio_curso')
     *
     * @author julio.buenadicha@altia.es
     * @author fernando.ramirez@altia.es
     * @version 1.0.0
     *
     * @param DateTime $dateTime
     *
     * @return string año de inicio del curso
     */
    function getAnoIniCursoEscolar(Datetime $dateTime)
    {
        $t = $dateTime->getTimestamp();
        if ($t) $diames = date("md", $t );

        $inicioCurso = date_create_from_format('d/m/Y', config('ctes.inicio_curso'));
        $diamesInicioCurso = date('md', $inicioCurso->getTimestamp() );

        if ( $diames >= $diamesInicioCurso) {
            // Si el mes es Agosto o posterior el inicio del curso es este año
            $ano = $dateTime->format("Y");
        } else {
            // Si el mes es anterior a Agosto el inicio del curso es el año pasado
            $ano = $dateTime->format("Y") - 1;
        }
        // dd( 
        //     config('ctes.inicio_curso'),
        //     $dateTime->format('d/m'),
        //     $inicioCurso,
        //     $inicioCurso->getTimestamp(),
        //     $inicioCurso->format('d/m/Y') ,
        //     $diames , 
        //     $diamesInicioCurso ,
        //     $diames >= $diamesInicioCurso,
        //     $diamesInicioCurso >= $diames,
        //     $ano
        // );
        return $ano;
    }
}

if (!function_exists('getAnoIniCurrentCursoEscolar')) {
    /**
     * Devuelve el año en formato YYYY del inicio del curso correspondiente a la fecha actual
     *  Se calcula teniendo en cuenta que el curso comienza el 1 de Agosto
     *
     * @author fernando.ramirez@altia.es
     * @version 1.0.0
     *
     * @return string año de inicio del curso
     */
    function getAnoIniCurrentCursoEscolar()
    {
        $dateTime = new DateTime();//fecha hora actual
        return getAnoIniCursoEscolar($dateTime);
    }
}



/**
 * Crea una variable de sesión con los datos del usuario logueado mediante el CAS.
 *
 * @return void
 */
if (!function_exists('setUserDataSession')) {
    function setUserDataSession()
    {
        if (Auth::user()->id ) {

            $persona = Auth::user()->userPersona? Auth::user()->userPersona->toArray() : null;
            $centros = Auth::user()->centros? Auth::user()->centros->toArray() : null;
            if (!$persona || !$centros) {
                customLoggin(
                    config('ctes.log_levels.error'),
                    config('ctes.log_types.info'),
                    [ 'file'=> __FILE__ , 'line'=> __LINE__],
                    'Persona y centro no pueden ser null'
                );
                return response()->view('errors.403');
            }

            try {
                session(['userData' => [
                    'user'          => Auth::user()->toArray(),
                    'persona'       => $persona,
                    'centros'       => $centros,
                    'roles'         => Auth::user()->roles_all()->toArray()
                ]]);
            } catch (\Throwable $th) {
                // return dd($th);
                return response()->view('errors.403');
            }
            
        } else {
            return response()->view('errors.403');
        }
    }
}



if (!function_exists('getTiposMediosMimeType')) {
    /**
     * Devuelve una cadena con tipos mimes o extensiones para poner en el campo accept de un filepicker y así filtrar los tipos de ficheros aceptados por el filepicker
     * Se extrae de las constantes extensiones_medios
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param integer $tipo_medio constante perteneciente a ctes.tipos_medios
     *
     * @return string tipo mime para poner en filepicker, pe "imagen/jpg, imagen/png"
     */
    function getTiposMediosMimeType($tipo_medio)
    {
        $cadena = "";
        // jpg jpeg jpe gif png bmp tif tiff ico
        switch ($tipo_medio) {
            case config('ctes.tipos_medios.imagen'):
                $lista_extensiones = config('ctes.extensiones_medios.imagen');
                $tipo = 'image';
                break;
            case config('ctes.tipos_medios.audio'):
                $lista_extensiones = config('ctes.extensiones_medios.audio');
                $tipo = 'audio';
                break;
            case config('ctes.tipos_medios.video'):
                $lista_extensiones = config('ctes.extensiones_medios.video');
                $tipo = 'video';
                break;
        }

        $extensiones = explode(' ', $lista_extensiones);
        foreach ($extensiones as $extension) {
            $cadena = $cadena . ',' . $tipo . '/' . $extension;
        }

        // quito la primera coma
        $cadena = substr($cadena, 1);

        return $cadena;
    }
}

if (!function_exists('getNombreFichero')) {
    /**
     * Recibe un nombre de fichero con extensión y devuelve el nombre del fichero sin la extensión
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param mixed $nombreConExtension
     *
     * @return string basename del fichero
     */
    function getNombreFichero($nombreConExtension)
    {
        $posicionPunto = strrpos($nombreConExtension, '.');
        $nombre = substr($nombreConExtension, 0, $posicionPunto);
        return $nombre;
    }
}


if (!function_exists('getExtensionFichero')) {
    /**
     * Recibe un nombre de fichero con extensión y devuelve el sólo la extensión
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @param mixed $nombreConExtension
     *
     * @return string extesnión del fichero
     */
    function getExtensionFichero($nombreConExtension)
    {
        $posicionPunto = strrpos($nombreConExtension, '.');
        $extension = substr($nombreConExtension, $posicionPunto + 1);
        return $extension;
    }
}

if (!function_exists('getSessionPersona')) {
    /**
     * Devuelve el array persona que está en los datos de la sesión y corresponden al usuario logueado
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @return void|bool
     */
    function getSessionPersona()
    {
        if ( $sesion_userdata = \Session::get('userData'))
            return $sesion_userdata['persona'];
        else
            return false;
    }
}

if (!function_exists('getSessionRoles')) {

    /**
     * Devuelve todos los datos roles del usuario actual
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @return array
     */
    function getSessionRoles()
    {
        // return \Session::get('userData')['roles'];

        if ( $sesion_userdata = \Session::get('userData'))
            return $sesion_userdata['roles'];
        else
            return false;
    }
}

if (!function_exists('getSessionUser')) {
    /**
     * Devuelve el array usuario que está en los datos de la sesión y corresponden al usuario logueado
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     *
     * @return void
     */
    function getSessionUser()
    {
        return \Session::get('userData')['user'];
    }
}



if (!function_exists('getDatosTabs')) {
    /**
     * Devuelve la estructura de datos de las pestañas
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param boolean $activeTabAula
     *
     * @return array
     */
    function getDatosTabs($activeTabAula = false)
    {

        $tabs = [
            [
                'tabName' => trans('Diccionario Personal'),
                'id' => 'dc-tab-personal',
                'tabUrl' => route('diccionariopersonal.get')
            ],
            [
                'tabName' => trans('Diccionario De Aula'),
                'id' => 'dc-tab-aula',
                'tabUrl' => route('diccionarioaula.get'),
            ]
        ];
        if ($activeTabAula) {
            $tabs[1]['active'] = true;
        } else {
            $tabs[0]['active'] = true;
        }

        return $tabs;
    }
}

if (!function_exists('getAlfabeto')) {
    /**
     * Devuelve estructura de datos del alfabeto
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @return array
     */
    function getAlfabeto()
    {
        return array_merge(range('A', 'N'), ['Ñ'], range('O', 'Z'));
    }
}

if (!function_exists('formatClassStr')) {
    /**
     * Formatea un string para que se pueda usar como nombre de clase
     *
     */
    function formatClassStr($str)
    {
        $out = str_replace('.', '-', $str);
        return $out;
    }
}

if (!function_exists('userValidatorCAUCE')) {
    /**
     * Comprueba contra el webservice del CAU_CE que el usuario que se loguea en el CAS tiene permisos para la aplicación
     * Si no existe en las tablas del Diccionario, se crea
     * Devuelve un objeto usuario
     *
     * @access public
     * @author daniel.matalobos@altia.es
     * @version 0.0.1
     *
     * @param string $userId
     * @return object|bool devuelve false si no puede crear usuario/persona u otro error
     */
    function userValidatorCAUCE($userId, $xmlTest= null)
    {
        if ($xmlTest) {

            customLoggin(
                config('ctes.log_levels.info'),
                config('ctes.log_types.info'),
                ['file' => __FILE__, 'line' => __LINE__],
                "parametros: \$userId = $userId".
                "\n \$xmlTest = $xmlTest",
                'se va a procesar un xml de prueba como si lo hubiese devueltro el ws cauce'
            );

            $xml = simplexml_load_string($xmlTest);
            $wsData = json_decode(json_encode($xml), true);
        }


        if (is_null($xmlTest)) {
            customLoggin(
                config('ctes.log_levels.info'),
                config('ctes.log_types.info'),
                ['file' => __FILE__, 'line' => __LINE__],
                "parametros: \$userId = $userId",
                'llamada a userValidatorCAUCE()'
            );
            // Petición de autorización del usuario devuelto por el CAS al webservice del cau_ce
            $data = [
                "endpoint" => env('CAUCE_WEBSERVICE') . "$userId",
                "headers" => [
                    'Authorization'     => 'Bearer ' . env('CAUCE_BEARER'),
                    'Accept-Encoding'   => 'gzip,deflate',
                ]
            ];
            // Petición al webservice del CAU_CE
            $client = new Client(['verify' => false]);
            $response = $client->get($data['endpoint'], [
                'headers' => $data['headers'],
                'http_errors' => false,
            ]);

            // TODO: Falla cuando llegan acentos?
            // Entity 'aacute' not defined {"exception":"[object] (ErrorException(code: 0): simplexml_load_string(): Entity: line 32: parser error : Entity 'aacute' not defined
            // $wsContentStr = $response->getBody()->getContents()
            // $wsContentStru8 = utf8_encode(html_entity_decode($wsContentStr));
            // $wsData = simplexml_load_string($wsContentStru8);
            $wsData = simplexml_load_string($response->getBody()->getContents());
            $wsData = json_decode(json_encode($wsData), true);

            customLoggin(
                config('ctes.log_levels.info'), config('ctes.log_types.info'),
                ['file' => __FILE__, 'line' => __LINE__],
                PHP_EOL . json_encode($wsData, JSON_PRETTY_PRINT),
                PHP_EOL .'Datos recibidos del WS CAUCE'
            );
        }

        if ($wsData['MensajeError']) {
            customLoggin(
                config('ctes.log_levels.info'), config('ctes.log_types.info'),
                ['file' => __FILE__, 'line' => __LINE__],
                PHP_EOL . $wsData['MensajeError'],
                PHP_EOL .' Mensaje Error devuelto por webservice'
            );
            return false;
        } else {
            if ($wsData['Usuario']['InfoUsuario']['NifNie']) {
                $persona = Persona::where('NIF_NIE', $wsData['Usuario']['InfoUsuario']['NifNie'])->first();
            } elseif ($wsData['Usuario']['InfoUsuario']['Pasaporte']) {
                $persona = Persona::where('pasaporte', $wsData['Usuario']['InfoUsuario']['Pasaporte'])->first();
            } elseif ($wsData['Usuario']['InfoUsuario']['CIAL']) {
                $persona = Persona::where('cial', $wsData['Usuario']['InfoUsuario']['CIAL'])->first();
            }

            if (!$persona) {
                $persona = new Persona();
                // si ya existe la persona no hace falta que le cambie el avatar
                $persona->avatar_URL= 'default.svg';
                $persona->estado    = 1;
            }

            $persona->cial      = ($wsData['Usuario']['InfoUsuario']['CIAL']) ? $wsData['Usuario']['InfoUsuario']['CIAL'] : null;
            $persona->NIF_NIE   = ($wsData['Usuario']['InfoUsuario']['NifNie']) ? $wsData['Usuario']['InfoUsuario']['NifNie'] : null;
            $persona->pasaporte = ($wsData['Usuario']['InfoUsuario']['Pasaporte']) ? $wsData['Usuario']['InfoUsuario']['Pasaporte'] : null;
            $persona->nombre    = ($wsData['Usuario']['InfoUsuario']['Nombre']) ? $wsData['Usuario']['InfoUsuario']['Nombre'] : null;
            $persona->apellidos = ($wsData['Usuario']['InfoUsuario']['Apellidos']) ? $wsData['Usuario']['InfoUsuario']['Apellidos'] : null;

            customLoggin(
                config('ctes.log_levels.info'), config('ctes.log_types.info'),
                ['file' => __FILE__, 'line' => __LINE__],
                PHP_EOL . json_encode($persona, JSON_PRETTY_PRINT),
                PHP_EOL .' se crea o actualiza Persona'
            );

            DB::beginTransaction();

            try {
                $persona->save();
            } catch (\Throwable $th) {
                DB::rollBack();
                customLoggin(
                    config('ctes.log_levels.error'),
                    config('ctes.log_types.create_or_update'),
                    ['file' => __FILE__, 'line' => __LINE__],
                    PHP_EOL . json_encode($persona, JSON_PRETTY_PRINT),
                    PHP_EOL .' error al crear persona' .
                    PHP_EOL . $th->getMessage()
                );
                return false;
            }

            if (!$user = $persona->personaUser()->first()) {
                $infoCentroArr = Arr::first($wsData['Centro']['InfoCentro']);
                // si tinene mulitples centros y roles:
                if( !(isset($wsData['Centro']['InfoCentro']['Rol'])) &&
                        is_array( $infoCentroArr ) &&
                        count($infoCentroArr)>0 ) {
                    // map roles_recibidos

                    customLoggin(
                        config('ctes.log_levels.info'),
                        config('ctes.log_types.create_or_update'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . json_encode($persona, JSON_PRETTY_PRINT),
                        PHP_EOL .'Tinene multiples roles y centros'
                    );
                    $roles_recibidos =  array_map(
                        function($n){ return $n['Rol']; },
                        $wsData['Centro']['InfoCentro']
                    );
                    if ( count($roles_recibidos)>0 ){
                        $roles_docente = [
                            config('ctes.roles_ws_cauce.id_rol_docente_pincel'),
                            config('ctes.roles_ws_cauce.id_rol_docente_centro_profesorado'),
                            config('ctes.roles_ws_cauce.id_rol_tecnicos_educativos')
                        ];
                        // si algun rol es docente se pone como docente si no como alumno
                        if(count(array_intersect($roles_recibidos, $roles_docente)) > 0){
                            $rol = obtenerEquivalenciaRol(config('ctes.roles_ws_cauce.id_rol_docente_pincel'));
                        } else {
                            // if(in_array(config('ctes.roles_ws_cauce.id_rol_alumnado_pincel'), $roles_recibidos) )
                            $rol = obtenerEquivalenciaRol(config('ctes.roles_ws_cauce.id_rol_alumnado_pincel'));
                        }
                    } else {
                        return false;
                    }

                } else { // Si solo tiene un centro y un rol
                    $rol = obtenerEquivalenciaRol($wsData['Centro']['InfoCentro']['Rol']);
                }
                $user = User::firstOrNew(
                    [
                        'name' => $userId
                        // 'name' => $persona->name
                    ],
                    [
                        'email'     => $userId,
                        // 'email'     => Str::random(60),
                        'role_id'   => $rol,
                        'avatar'    => 'users/default.png',
                        'password'  => '$2y$10$9pyd0UWhAX7egul1zbWrxu5.y.e6UZMEOrk24FHLWLp3NCiR9LQke'
                    ]
                );
                try {
                    $user->save();
                } catch (\Throwable $th) {
                    DB::rollBack();
                    customLoggin(
                        config('ctes.log_levels.error'),
                        config('ctes.log_types.create_or_update'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . json_encode($user, JSON_PRETTY_PRINT),
                        PHP_EOL .' error al crear User' .
                        PHP_EOL . $th->getMessage()
                    );
                    return false;
                }

                try {
                    $userPersona = UserPersona::firstOrCreate([
                        'user_id'       => $user->id,
                        'persona_id'    => $persona->id
                    ]);
                } catch (\Throwable $th) {
                    DB::rollBack();
                    customLoggin(
                        config('ctes.log_levels.error'),
                        config('ctes.log_types.create_or_update'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . json_encode($userPersona, JSON_PRETTY_PRINT),
                        PHP_EOL .' error al crear UserPersona' .
                        PHP_EOL . $th->getMessage()
                    );
                    return false;
                }

            }
            customLoggin(
                config('ctes.log_levels.info'),
                config('ctes.log_types.info'),
                ['file' => __FILE__, 'line' => __LINE__],
                PHP_EOL . 'entrar a asignarCentroaUsuario()'
            );
            asignarCentroaUsuario($user, $wsData);

            DB::commit();


            return true;
        }
        // no deberia llegar a aqui
        // return false;
    }
}


if (!function_exists('asignarCentroaUsuario')) {
    function asignarCentroaUsuario( User $user, $datosCauceWs)
    {
        // dd($user , $datosCauceWs );
        customLoggin(
            config('ctes.log_levels.info'),
            config('ctes.log_types.info'),
            ['file' => __FILE__, 'line' => __LINE__],
            PHP_EOL . 'Se ha llamado a asignarCentroaUsuario()' .
            PHP_EOL . 'Con los parametros: $user :' .
                json_encode($user, JSON_PRETTY_PRINT) .
            PHP_EOL . '$datosCauceWs :' .
                json_encode($datosCauceWs, JSON_PRETTY_PRINT)
        );
        $wsData = $datosCauceWs;

        $codigoNoCentro = '00000000';
        $demoninacionNoCentro = 'Sin centro educativo';

        try {
            $centerNoCenter = Centro::firstOrCreate(
                [ 'cod_centro' => $codigoNoCentro ],
                [
                    'estado'       => 1,
                    'denominacion'  => $demoninacionNoCentro
                ]
            );
        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.create_or_update'),
                ['file' => __FILE__, 'line' => __LINE__],
                PHP_EOL . json_encode($datosCauceWs, JSON_PRETTY_PRINT),
                PHP_EOL .' error al crear o selecionar Centro "00000000" ' .
                PHP_EOL . $th->getMessage()
            );
        }

        $infoCentroArr = Arr::first($wsData['Centro']['InfoCentro']);
        $centros_ids = [];
        // Si hay multiples centros:
        if (
            !isset( $wsData['Centro']['InfoCentro']['Codigo'] ) &&
            is_array( $infoCentroArr ) &&
            count($infoCentroArr)>0
        ) {
            customLoggin(
                config('ctes.log_levels.info'), config('ctes.log_types.info'),
                ['file' => __FILE__, 'line' => __LINE__],
                PHP_EOL . json_encode($wsData['Centro']['InfoCentro'], JSON_PRETTY_PRINT),
                PHP_EOL .'Multiples "InfoCentro" '
            );

            if ( count($wsData['Centro']['InfoCentro'])>0 ){
                foreach ($wsData['Centro']['InfoCentro'] as $centro) {

                    $codigo = $centro['Codigo'] ? $centro['Codigo'] : $codigoNoCentro;
                    $nombre = $centro['Nombre'] ? $centro['Nombre'] : $demoninacionNoCentro;

                    $datosCentro = [
                        ['cod_centro'   => $codigo ],
                        [
                            'estado'       => 1,
                            'denominacion'  => $nombre
                        ]];

                    try {
                        $center = Centro::firstOrCreate( $datosCentro[0],$datosCentro[1]  );
                        array_push($centros_ids, $center->id);
                    } catch (\Throwable $th) {
                        customLoggin(
                            config('ctes.log_levels.info'), config('ctes.log_types.create_or_update'),
                            ['file' => __FILE__, 'line' => __LINE__],
                            PHP_EOL . json_encode( $datosCentro , JSON_PRETTY_PRINT),
                            PHP_EOL .'Fallo al selecionar o crear centro ' .
                            PHP_EOL . $th->getMessage()
                        );
                    }
                }
            } else {
                array_push($centros_ids, $centerNoCenter->id );
            }
        } else { // si solo hay un centro (o un registro de "infocentro")
            customLoggin(
                config('ctes.log_levels.info'), config('ctes.log_types.info'),
                ['file' => __FILE__, 'line' => __LINE__],
                PHP_EOL . json_encode($wsData['Centro']['InfoCentro'], JSON_PRETTY_PRINT),
                PHP_EOL .'Solo un InfoCentro '
            );

            if ( isset($wsData['Centro']['InfoCentro']['Codigo']) &&
                    $wsData['Centro']['InfoCentro']['Codigo'] !== '' &&
                    !is_array($wsData['Centro']['InfoCentro']['Codigo'])
            ) {

                customLoggin(
                    config('ctes.log_levels.info'), config('ctes.log_types.info'),
                    ['file' => __FILE__, 'line' => __LINE__],
                    PHP_EOL . json_encode($wsData['Centro']['InfoCentro'], JSON_PRETTY_PRINT),
                    PHP_EOL .'Solo un centro con codigo '
                );
                $centro = $wsData['Centro']['InfoCentro'];

                // dd('solo un centro con codigo', $centro );

                $codigo = $centro['Codigo'] ? $centro['Codigo'] : $codigoNoCentro;
                $nombre = $centro['Nombre'] ? $centro['Nombre'] : $demoninacionNoCentro;
                $datosCentro = [
                    ['cod_centro'   => $codigo ],
                    [
                        'estado'       => 1,
                        'denominacion'  => $nombre
                    ]];

                try {
                    // ojo Spread Operator ( ...$datosCentro ) funciona apartir de php7.4 (usamos 7.2)
                    $center = Centro::firstOrCreate( $datosCentro[0], $datosCentro[1] );
                } catch (\Throwable $th) {
                    customLoggin(
                        config('ctes.log_levels.info'), config('ctes.log_types.create_or_update'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . json_encode( $datosCentro , JSON_PRETTY_PRINT),
                        PHP_EOL .'Fallo al selecionar o crear centro ' .
                        PHP_EOL . $th->getMessage()
                    );
                }

            } else {
                // nigun centro educativo
                // dd('nigun centro educativo');
                customLoggin(
                    config('ctes.log_levels.info'), config('ctes.log_types.info'),
                    ['file' => __FILE__, 'line' => __LINE__],
                    PHP_EOL . 'se va a guardar con centro "00000000" ' .
                    PHP_EOL . json_encode($centerNoCenter, JSON_PRETTY_PRINT),
                    PHP_EOL .'Ningun centro, no hay codigo '
                );
                $center = $centerNoCenter;

            }

            $centros_ids = [$center->id];
        }


        customLoggin(
            config('ctes.log_levels.info'),
            config('ctes.log_types.create_or_update'),
            ['file' => __FILE__, 'line' => __LINE__],
            PHP_EOL . 'centros_ids: ' . json_encode($centros_ids, JSON_PRETTY_PRINT),
            PHP_EOL .' Se le van a Asingnar estos centros al usuario: ' .
                json_encode($user, JSON_PRETTY_PRINT)
        );

        // dd($centros_ids);

        try {
            $user->centros()->sync($centros_ids);
            customLoggin(
                config('ctes.log_levels.info'),
                config('ctes.log_types.info'),
                ['file' => __FILE__, 'line' => __LINE__],
                '',
                'Centros asignados a usuario correctamente' .
                PHP_EOL . 'centros' . json_encode($user->centros, JSON_PRETTY_PRINT)
            );

            // dd(
            //     $wsData,
            //     $centros_ids,
            //     json_encode($user->centros, JSON_PRETTY_PRINT)
            // );

        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.create_or_update'),
                ['file' => __FILE__, 'line' => __LINE__],
                PHP_EOL . 'centros_ids: ' . json_encode($centros_ids, JSON_PRETTY_PRINT),
                PHP_EOL .' Asingnar centros a usuario: ' . json_encode($user, JSON_PRETTY_PRINT) .
                PHP_EOL . $th->getMessage()
            );
        }
    }
}

if (!function_exists('obtenerEquivalenciaRol')){
    function obtenerEquivalenciaRol( $wsDataRol )
    {
        switch ( $wsDataRol ) {
            // case config('ctes.'):
            case config('ctes.roles_ws_cauce.id_rol_docente_pincel'): // docente centros públicos (en pincel)
            case config('ctes.roles_ws_cauce.id_rol_docente_centro_profesorado'): // docente de centros de profesorado ( no esta en pincel )
            case config('ctes.roles_ws_cauce.id_rol_tecnicos_educativos'): // Tecnico educativo
                return Role::where('name', 'docente')->first()->id;
                break;
            case config('ctes.roles_ws_cauce.id_rol_alumnado_pincel'):
            default:
                return Role::where('name', 'alumno')->first()->id;
        }
    }

}

// Falla por que si se cambia el seeder no coiniciden los ids con el valor que deberia
// if (!function_exists('abreviaAtributosById')) {
//     /**
//      * Devuelve el texto de genero, nuemero, persona... abreviado
//      *
//      * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
//      * @version 1.0.0
//      *
//      * @param [type] $campo_id
//      * @param string $campo_print
//      *
//      * @return string palabra abrebviada
//      */
//     function abreviaAtributosById( $campo_id, $campo_print ) {

//         if ($campo_id) {
//             if (isset(config('ctes.abreviatura_personalizada')[$campo_id])) {
//                 $abrebiatura = config('ctes.abreviatura_personalizada')[$campo_id] . '.';
//             } else {
//                 $abrebiatura = strtolower(substr($campo_print, 0, config('ctes.abreviatura_tamano'))) . '.';
//             }
//             return $abrebiatura;
//         } else return;
//     }
// }



if (!function_exists('separaCodigo')) {


    /**
     * Muestra el docido de diccionario separado curso/clase y codigo
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param string $codigoDic
     *
     * @return void
     */
    function separaCodigo( $codigoDic ) {

        // Separar las 4 ultimas cifras del resto
        return substr($codigoDic, 0, -4 ).' '.substr($codigoDic, -4 );
    }
}


if (!function_exists('usuarioEsDocente')){
    function usuarioEsDocente(User $user)
    {
        $esDocente = ($user->role_id == Role::where('name', 'docente')->first()->id);
        // $esDocente = $user->role_id;
        return $esDocente;
    }
}

if (!function_exists('usuarioEsAdministrador')){
    function usuarioEsAdministrador(User $user)
    {
        $esAdministrador = $user->role()->first()->name == 'admin';
        return $esAdministrador;
    }
}

if (!function_exists('mb_ucfirst')){
    /**
     * mb_cufirst no existe asi que la creo con un helper para poder usarla en los archivo blade
     * Pone la primera letra del string en mayusculas usando mb_strtoupper para evitar problemas con
     * caracteres acentuados u otros cararcteres no propios del ingles
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param [type] $str
     *
     * @return void
     */
    function mb_ucfirst($str) {
        $fc = mb_strtoupper(mb_substr($str, 0, 1));
        return $fc.mb_substr($str, 1);
    }
}

if (!function_exists('normalizarEntrada')){
    /**
     * Normalizar string para comparar y crear entradas
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param [type] $str
     *
     * @return void
     */
    function normalizarEntrada($str) {

        // Quitar espacios principio y final
        $str = trim($str, " ");
        // cambia varios espacios segudidos por uno solo
        $str = preg_replace('/\s+/', ' ', $str);
        // Primera en mayusculas y resto minusculas
        // $str = mb_strtolower($str);
        // $str = mb_ucfirst($str);

        return $str;
    }
}

if (!function_exists('formDateToDbDate')){
    /**
     * Transforma la fecha que viene de los input[type=date] al formato de la bbdd
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param [type] $value
     *
     * @return void
     */
    function formDateToDbDate($value)
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            $value = Carbon::createFromFormat('Y-m-d', $value)
                ->startOfDay()
                // ->format('Y-m-d h:m:s')
                ->toDateTimeString()
                ;
        }

        return $value;
    }
}

if (!function_exists('getLogs')){
    function getLogs()
    {
        $files = [];
        if ( $handle = opendir('/var/www/html/medusa/apps/lexican/storage/logs') ) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $files[] = $entry;
                }
            }
            closedir($handle);
        }

        return $files;
    }
}
if (!function_exists('downloadFile')){
function downloadFile($file)
{
    $file = '/var/www/html/medusa/apps/lexican/storage/logs/'.$file;

    if(!file_exists($file)){ // file does not exist
        die('file not found');
    } else {
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=$file");
        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: binary");

        // read the file from disk
        readfile($file);
    }
}
}

if (!function_exists('getAllNivelEstudios')){
/**
 * Devuelve los niveles de estudio ordenados
 * @return [type] [description]
 */
function getAllNivelEstudios()
{
    $niveles = NivelEstudio::where('descripcion','!=', 'Infantil')->get();
    $ordenados = [];
    $me =  NivelEstudio::where('descripcion', 'Multiestudio')->get()->first();
    $infantil = NivelEstudio::where('descripcion', 'Infantil')->get()->first();
    if ( isset($me) && isset($infantil) ){
        $ordenados[] = $me;
        $ordenados[] = $infantil;
    }
    foreach ($niveles as $nivel) {
        if( $nivel != $me && $nivel != $infantil){
        $ordenados[] = $nivel;
        }
    }
    return $ordenados;
}
}

/**
 * numero de minutos desde ahora hasta la fecha definida por timestamp
 *
 * @param int $timestamp
 * @return int
 */
function minutosHastaTimestamp($timestamp){
    $now = new \DateTime();
    $then = new \DateTime();
    $then->setTimestamp($timestamp);
    $diff = $now->diff($then);
    
    // diff->i => timepo en minutos pero el maximo es 59 asi que:
    $days = $diff->format('%a');
    $minutes = 0;
    if($days) $minutes += 24 * 60 * $days;
    $hours = $diff->format('%H');
    if($hours) $minutes += 60 * $hours;
    $minutes += $diff->format('%i');

    return $minutes;
}

if ( ! function_exists('cas')) {
    function cas()
    {
        return app('cas');
    }
}

if ( ! function_exists('emptyOrNull')) {
    function emptyOrNull(String|null $str)
    {
        return ( !isset($str) || is_null($str) || $str=='');
    }
}