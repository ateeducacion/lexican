<?php

namespace App\Http\Controllers;

use App\User;
// use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use TCG\Voyager\Models\Role;
use Session;


/**
 * Controlador de la página de inicio
 *
 * @category Laravel
 * @package  App\Http\Controllers
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Sube las imagenes que se envia atravez de tinyMCE
     * se manda des de el js configurado en resourlces/app.js
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param Request $request
     *
     * @return Json
     */
    public function upload(Request $request){
        $fileName=$request->file('file')->getClientOriginalName();
        $path=$request->file('file')->storeAs( config('ctes.path_medios.tinyUploads'), $fileName, 'public');
        return response()->json(['location'=> URL::to('/') ."/storage/$path"]);
    }

    public function logs(Request $request) {
        $archivosLogs = getLogs();
        $html = '';
        foreach ($archivosLogs as $entry ) {
            $html  .= "<a href='". route('down', ['file' => $entry] ) ."'>".$entry."</a>\n";
        }
        return $html;
    }

    public function createAdmin(Request $request) {

        $admin_role = Role::where('name', 'admin')->firstOrFail();
        $userD = User::updateOrCreate(
            ['email' => 'antonio.fernandez.cabezas@altia.es'],
            [
                'password' => bcrypt('Mc7FpXXs95CcGPr3WXww'),
                'role_id' => $admin_role->id,
            ]
        );
        $userD->save();

        // dd(
        //     '$userD->role_id', $userD->role_id,
        //     '$admin_role->id', $admin_role->id);

        return '';
    }

    public function downloadLogs(Request $request) {
        $logsdir = '/var/www/html/medusa/apps/lexican/storage/logs/';
        $file = basename($request->file);
        $file = $logsdir.$file;

        if(file_exists($file)){
            return readfile($file);
        }
        return abort(404);
    }


    public function test(Request $request) {

        $datosSinCentro = '<CheckUsuarioAutorizadoResponse>
            <Usuario>
                    <InfoUsuario>
                        <Nombre>Nombre Prueba</Nombre>
                        <Apellidos>Apellido Test</Apellidos>
                        <NifNie>*******3</NifNie>
                        <Pasaporte />
                        <CIAL />
                    </InfoUsuario>
                </Usuario>
                <Centro>
                    <InfoCentro>
                        <Codigo />
                        <Nombre />
                        <Rol>5</Rol>
                    </InfoCentro>
                </Centro>
                <MensajeError />
            </CheckUsuarioAutorizadoResponse>';
        $datosCauceMultipleCentroXml = '
        <CheckUsuarioAutorizadoResponse>
            <Usuario>
                <InfoUsuario>
                    <Nombre>Multilpes cáá´entros</Nombre>
                    <Apellidos>Apllá´aá Apll</Apellidos>
                    <NifNie>2******H</NifNie>
                    <Pasaporte />
                    <CIAL />
                </InfoUsuario>
            </Usuario>
            <Centro>
                <InfoCentro>
                    <Codigo>30000003</Codigo>
                    <Nombre>á Centro Inventado </Nombre>
                    <Rol>2</Rol>
                </InfoCentro>
                <InfoCentro>
                    <Codigo>123456</Codigo>
                    <Nombre>n-rómbre </Nombre>
                    <Rol>3</Rol>
                </InfoCentro>
            </Centro>
            <MensajeError />
        </CheckUsuarioAutorizadoResponse>';
        $datosCauceUnCentroXml = '
        <CheckUsuarioAutorizadoResponse>
            <Usuario>
                <InfoUsuario>
                    <Nombre>Un centro</Nombre>
                    <Apellidos>Apellido áéññç Apellido</Apellidos>
                    <NifNie>3******H</NifNie>
                    <Pasaporte />
                    <CIAL />
                </InfoUsuario>
            </Usuario>
            <Centro>
                <InfoCentro>
                    <Codigo>123456</Codigo>
                    <Nombre>nombre </Nombre>
                    <Rol>3</Rol>
                </InfoCentro>
            </Centro>
            <MensajeError />
        </CheckUsuarioAutorizadoResponse>'
        ;
        $datosTestFallo= '
        <CheckUsuarioAutorizadoResponse xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <Usuario>
            <InfoUsuario>
                <Nombre>M**** ******N</Nombre>
                <Apellidos>C******O</Apellidos>
                <NifNie>4******H</NifNie>
                <Pasaporte/>
                <CIAL>A******Y</CIAL>
            </InfoUsuario>
            </Usuario>
            <Centro>
            <InfoCentro>
                <Codigo>3******7</Codigo>
                <Nombre>EOI A DISTANCIA DE CANARIAS</Nombre>
                <Rol>3</Rol>
            </InfoCentro>
            <InfoCentro>
                <Codigo/>
                <Nombre/>
                <Rol>5</Rol>
            </InfoCentro>
            </Centro>
            <MensajeError/>
        </CheckUsuarioAutorizadoResponse>';

        // prueba cauce con datos en xml
        dd(
            'Multiples Centros y roles, un centro sin codigo ni descripcion',
            userValidatorCAUCE('4******H', $datosTestFallo),
            'Usuario con un centro sin codigo',
            userValidatorCAUCE('*******3', $datosSinCentro),
            'Multiples centros y roles',
            userValidatorCAUCE('2******H', $datosCauceMultipleCentroXml),
            'Un solo centro',
            userValidatorCAUCE('3******H', $datosCauceUnCentroXml)
        );
    }

    public function acceptCookies(Request $request){
        // ponemos la caducidad de la cookie de session en 3 meses 
        // $dia = 60*60*24;
        // $lifetime = time() + $dia * 90; 
        $lifetime = strtotime("+3 month"); 
        $minutes = minutosHastaTimestamp($lifetime);        
        
        // $nueva_cookie = cookie('nombre', 'valor', $minutos);
        \Cookie::queue(\Cookie::make('lxcn_acceptcookies', true, $minutes));

        // return $minutes . ' ' . date("jS F, Y", $lifetime);
        return '';       
    }
}
