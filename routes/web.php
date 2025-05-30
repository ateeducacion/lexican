<?php

Use App\Cas;
use TCG\Voyager\Events\Routing;
use TCG\Voyager\Events\RoutingAdmin;
use TCG\Voyager\Events\RoutingAdminAfter;
use TCG\Voyager\Events\RoutingAfter;
use TCG\Voyager\Facades\Voyager;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/health-check', function () {
    return Response::json(['status' => 'up'], 200);
});
Route::get('/hc', function () {
    return Response::json(['status' => 'up'], 200);
});

Route::get('/acceptCookies', 'HomeController@acceptCookies')->name('aceptarCookies');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/', function () {
        // return session('userData');
        if(Auth::user()->role->name == 'admin' || Auth::user()->role->name == 'user' ){
            return redirect()->route('voyager.dashboard');
        } else {
            return redirect()->route('diccionariopersonal.get');
        }
    });

    // Diccionario Personal **************************************************************************************
    Route::group(['prefix' => 'personal'], function () {

        // listado de entradas
        Route::GET('/', 'DiccionarioPersonalController@dpHome')->name('diccionariopersonal.get');
        // Route::GET('/entradas/letra/{letra}', 'DiccionarioPersonalController@dpGetEntradaAllGET')->name('consulta.letra');

        Route::GET('/entradas_json', 'DiccionarioPersonalController@dpGetEntradaAllJson')->name('personal.consulta.all.json');

        // Paginas buscadores por búsqueda, letra inicial, o todas las palabras
        Route::GET('/entradas', 'DiccionarioPersonalController@dpGetEntradaAllGET')->name('personal.consulta.all');
        Route::GET('/entradas/letra/{letra}', 'DiccionarioPersonalController@dpGetEntradaByInitialGET')->name('personal.consulta.byInitial');
        Route::GET('/entradas/{consulta}', 'DiccionarioPersonalController@dpGetEntradaByConsultaGET')->name('personal.consulta.palabra');
        Route::POST('/entradas/consulta', 'DiccionarioPersonalController@dpBuscarEntradaPOST')->name('personal.consulta.busqueda');
        // buscar con etiquetas
        Route::GET ('/entradas/{consulta}/etiquetas/{tematicasIds}' , 'DiccionarioPersonalController@dpGetEntradaByConsultaGET' )->name('personal.consulta.palabra.tematica' );
        Route::GET ('/entradas/etiquetas/{tematicasIds}'            , 'DiccionarioPersonalController@dpGetEntradaByConsultaGET' )->name('personal.consulta.tematica');

        Route::GET('/entradas/n/{offset}', 'DiccionarioPersonalController@dpGetEntradasAjax')->name('personal.consulta.ajax');
        Route::GET('/entradas/letra/{letra}/n/{offset}', 'DiccionarioPersonalController@dpGetEntradasbyInitialAjax')->name('personal.consulta.ajaxLetra');
        
        // pongo /x/ en los que son con ajax para evitar que se confunda con otras rutas
        // Tambien es importante el orden , mejor poner las rutas con texto que se puede confundir con parametros primero para evitar que vaya a la ruta que no es
        Route::GET('/x/entradas/tematicas/{tematicasIds}/n/{offset}', 'DiccionarioPersonalController@dpGetEntradasAjax')->name('personal.tematica.ajaxConsulta');
        Route::GET('/x/entradas/{consulta}/n/{offset}', 'DiccionarioPersonalController@dpGetEntradasAjax')->name('personal.consulta.ajaxConsulta');
        Route::GET('/x/entradas/{consulta}/tematicas/{tematicasIds}/n/{offset}', 'DiccionarioPersonalController@dpGetEntradasAjax')->name('personal.tematica.consulta.ajaxConsulta');
        

        Route::GET('/ajax/getDiccionariosAulaEntradaAjax', 'EnviosController@getDiccionariosAulaEntradaAjax')->name('getDiccionariosAulaEntradaAjax');
        Route::GET('/ajax/getUnirseDiccionarioAjax', 'EnviosController@getUnirseDiccionarioAjax')->name('getUnirseDiccionarioAjax');
        Route::GET('/ajax/getDiccionariosAulaDiccionarioAjax', 'EnviosController@getDiccionariosAulaDiccionarioAjax')->name('getDiccionariosAulaDiccionarioAjax');

        Route::GET('/ajax/getComentariosEntradaAjax', 'ComentariosController@getComentariosEntradaAjax')->name('getComentariosEntradaAjax');

        // obtiene modal con formulario de comentar entrada
        Route::GET('/ajax/getComentarEntradaAjax/{entrada_id}', 'EnviosController@getComentarEntradaAjax')->name('getComentarEntradaAjax');

        //Debe estar por encima de la ruta /{diccionario_id} porque si no entran en conflicto 
        Route::POST('/grabacionAcepcion', 'MediaTypeController@index')->name('grabacionAcepcion');

        Route::GET('/{diccionario_id}', 'DiccionarioPersonalController@dpHome');

        // mantenimiento de entradas
        Route::GET('/entrada/create', 'DiccionarioPersonalController@dpCreateEntradaGET')->name('entrada.buscador'); // devuelve la vista con el formulario de alta de entrada. BUSCADOR
        Route::GET('/entrada/insert', 'DiccionarioPersonalController@dpInsertEntradaGET')->name('entrada.insert'); // envía una entrada para guardarla. Se llama desde el FORM de createEntrada.blade

        Route::GET('/{diccionario_id}/entrada/{entrada_id}/delete', 'DiccionarioPersonalController@dpDeleteEntradaGET')->name('entrada.delete'); // devuelve la entrada completa
        Route::GET('/{diccionario_id}/entrada/{entrada_id}/edit', 'DiccionarioPersonalController@dpEditEntradaGET')->name('entrada.edit'); // devuelve la entrada completa
        Route::POST('/{diccionario_id}/entrada/{entrada_id}/edit', 'DiccionarioPersonalController@dpEditEntradaPOST')->name('entrada.update'); // guarda la modificación de la entrada en base de datos
        Route::GET('/{diccionario_id}/entrada/{entrada_id}/ocultar', 'DiccionarioPersonalController@dpOcultarEntradaGET')->name('entrada.ocultar'); // modifica la entrada poniéndola como oculta
        Route::GET('/{diccionario_id}/entrada/{entrada_id}', 'DiccionarioPersonalController@dpGetEntradaGET')->name('entrada.get'); // devuelve la entrada completa

        Route::GET('/{diccionario_id}/entrada/{entrada_id}/comentarios', 'DiccionarioPersonalController@comentariosEntradaGET')->name('entrada.comentarios'); // devuelve todos los comentarios de una entrada


        // Comentarios diccionario
        Route::GET('/{diccionario_id}/comentarios', 'ComentariosController@comentariosDiccionarioGET')->name('diccionario.comentarios'); // devuelve todos los comentarios de todas las entradas de un diccionario personal
        Route::GET('/ajax/getComentariosDiccionarioAjax', 'ComentariosController@getComentariosDiccionarioAjax')->name('getComentariosDiccionarioAjax'); // devuelve un aviso de que no hay comentarios o la url para redirigir a la pantalla de ver comentarios del diccionario


        // mantenimiento de acepciones
        Route::POST('/entrada/acepcion/create', 'DiccionarioPersonalController@dpInsertAcepcionPOST'); // envía una acepcion para guardarla. Si tiene orden 1 se guarda tb la entrada
        Route::GET('/{diccionario_id}/entrada/{entrada_id}/acepcion/create', 'DiccionarioPersonalController@dpCreateAcepcionGET')->name('acepcion.create'); //
        Route::GET('/{diccionario_id}/entrada/{entrada_id}/acepcion/{acepcion_id}/delete', 'DiccionarioPersonalController@dpDeleteAcepcionGET')->name('acepcion.delete'); //
        Route::GET('/{diccionario_id}/entrada/{entrada_id}/acepcion/{acepcion_id}/edit', 'DiccionarioPersonalController@dpEditAcepcionGET')->name('acepcion.edit'); //
        Route::POST('/{diccionario_id}/entrada/{entrada_id}/acepcion/{acepcion_id}/update', 'DiccionarioPersonalController@dpEditAcepcionPOST')->name('acepcion.update'); //
        Route::GET('/{diccionario_id}/entrada/{entrada_id}/acepcion/{acepcion_id}/up', 'DiccionarioPersonalController@dpUpAcepcionGET')->name('acepcion.up'); //
        Route::GET('/{diccionario_id}/entrada/{entrada_id}/acepcion/{acepcion_id}/down', 'DiccionarioPersonalController@dpDownAcepcionGET')->name('acepcion.down'); //
        Route::GET('/{diccionario_id}/entrada/{entrada_id}/acepcion/{acepcion_id}/ocultar', 'DiccionarioPersonalController@dpOcultarAcepcionGET')->name('acepcion.ocultar'); // Oculta o muestra una acepción


        // mantenimiento de medios
        Route::GET('/{diccionario_id}/entrada/{entrada_id}/acepcion/{acepcion_id}/dpmedio/{medio_id}/delete', 'DiccionarioPersonalController@dpDeleteMedioGET')->name('medio.delete'); //


        // Modificar diccionario personal
        Route::GET('/general/modificar', 'DiccionarioPersonalController@dpModificarDiccionarioGET')->name('diccionario.modificar.ver');       // Muestra pantalla para cambiar nombre y avatar.
        Route::POST('/{diccionario_id}/general/update', 'DiccionarioPersonalController@dpSaveModificarDiccionarioPOST')->name('diccionario.modificar.update'); // Actualiza el nombre del


        // envíos
        // Route::GET('/{diccionario_id}/entrada/{entrada_id}/enviar', 'EnviosController@enviarEntradaGET')->name('entrada.enviar'); // Muestra la pantalla para seleccionar los diccinarios de aula para enviar la entrada
        Route::POST('/{diccionario_id}/entrada/{entrada_id}/enviar', 'EnviosController@enviarEntradaPOST')->name('entrada.send');  // Envía la entrada a los diccionarios de aula seleccionados
        Route::GET('/{diccionario_id}/enviar', 'EnviosController@enviarDiccionarioGET')->name('diccionario.enviar'); // Muestra la pantalla para seleccionar los diccinarios de aula para enviar el diccionario personal
        Route::POST('/{diccionario_id}/enviar', 'EnviosController@enviarDiccionarioPOST')->name('diccionario.send'); // Envía el diccionario del usuario actual a los diccionarios de aula seleccionados


        // modales
        Route::POST('/ajax/getAceptarCancelarAjax', 'ModalAjaxController@getAceptarCancelarAjax')->name('getAceptarCancelarAjax');

        // formulario opciones de exportar pdf
        Route::GET('/{diccionario_id}/pdf', 'PDFController@optionsPdfPersonal')->name('pdfPersonal.options');

    });
    


    // Diccionario de Aula *************************************************************************************
    Route::group(['prefix' => 'aula'], function () {
        Route::GET('/', 'DicAulaController@daHome')->name('diccionarioaula.get'); // pagina inicial dic aula

        // Crear y modificar dic de aula
        Route::GET('/create', 'DicAulaController@index')->name('diccionarioaula.index');
        Route::POST('/create', 'DicAulaController@create')->name('diccionarioaula.create');
        Route::GET('/{id}/edit', 'DicAulaController@edit')->name('diccionarioaula.edit');
        Route::POST('/{id}/edit', 'DicAulaController@save')->name('diccionarioaula.save');

        // borra diccionairo
        Route::GET('/{id}/delete', 'DicAulaController@delete')->name('diccionarioaula.delete');
        // Comprueba que se puede borra le diccionario o si tiene entradas pendietes de publicar
        Route::GET('/{id}/testdelete', 'DicAulaController@testDeleteAjax')->name('diccionarioaula.testdelete');

        // Publicar entradas:
        Route::GET('/{id}/entradas', 'DicAulaController@entradas')->name('diccionarioaula.entradas');
        Route::POST('/{id}/entradas', 'DicAulaController@entradasAjax')->name('diccionarioaula.entradas');


        Route::POST('/{id}/entrada/{identrada}/publicar', 'DicAulaController@publicar')->name('diccionarioaula.publicar');
        Route::POST('/{id}/entradas/status', 'DicAulaController@getStatusEntradasParaPublicarListado')->name('diccionarioaula.publicarListado.statusEntradas');
        Route::post('/{id}/entrada/{identrada}/eliminar', 'DicAulaController@eliminar')->name('diccionarioaula.eliminar');

        Route::POST('/{id}/publicar', 'DicAulaController@publicarListado')->name('diccionarioaula.publicarListado');
        Route::POST('/{id}/entrada/{identrada}/comentario/{idcomentario}/edit', 'DicAulaController@comentarioEdit')->name('diccionarioaula.comentario.edit');
        Route::POST('/{id}/entrada/{identrada}/comentario/{idcomentario}/delete', 'DicAulaController@comentarioDelete')->name('diccionarioaula.comentario.delete');
        Route::POST('/{id}/edit/deshabilitarParticipante/{idp}', 'DicAulaController@participantesAjax')->name(config('ctes.rutas.aula.deshabilitarParticipante'));
        Route::POST('/{id}/edit/habilitarParticipante/{idp}', 'DicAulaController@participantesAjax')->name(config('ctes.rutas.aula.habilitarParticipante'));
        Route::POST('/{id}/edit/deshabilitarParticipanteAdmin/{idp}', 'DicAulaController@participantesAjax')->name(config('ctes.rutas.aula.deshabilitarParticipanteAdmin'));
        Route::POST('/{id}/edit/habilitarParticipanteAdmin/{idp}', 'DicAulaController@participantesAjax')->name(config('ctes.rutas.aula.habilitarParticipanteAdmin'));
        Route::POST('/unirse', 'DicAulaController@unirse')->name('aula.unirse'); // Recibe el código para unir al usuario a un diccionario. Se llama desde enviar entrada
        Route::GET('/DEV_unirseGET', 'DicAulaController@DEV_unirseGET')->name('aula.DEV_unirseGET'); // pantalla no modal para unirse a diccionarios

        Route::POST('/{diccionario_id}/inviteMail', 'DicAulaController@invitarMailPOST')->name('aula.invitar');   // Invita a un profesor por e-mail.

        // Guardar el dic de aula activo en la session por ajax
        Route::POST('dicAulaActivo', 'DicAulaController@setDicAulaActivo')->name('aula.selectDicAula');
        // Guarda el diccionario de aula activo enviandolo en el get
        Route::GET('dicAulaActivo/{id}', 'DicAulaController@setDicAulaActivoGET')->name('aula.selectDicAulaGet');

        // comentarios
        Route::GET('/{id}/comentarios', 'ComentariosController@comentariosGet')->name('comentarios.enviar');
        Route::POST('/{id}/comentarios', 'ComentariosController@comentariosPost')->name('comentarios.save');
        Route::GET('/{id}/comentario/{comentario_id}/delete', 'ComentariosController@deleteComentarioGet')->name('comentario.delete');
        Route::POST('/{id}/entrada/{identrada}/comentar', 'DicAulaController@comentar')->name('diccionarioaula.comentar');

        // Paginas buscadores por busqueda, letra inicial, o todas las palabras CON DIC ID EN LA RUL
        // Route::GET(  '{dicId}/entradas',               'DicAulaController@getAllEntradasConsulta') ->name('aula.consulta.all');
        // Route::GET(  '{dicId}/entradas/letra/{letra}', 'DicAulaController@getEntradaByInitial')    ->name('aula.consulta.byInitial');
        // Route::GET(  '{dicId}/entradas/{consulta}',    'DicAulaController@getEntradaByConsulta')   ->name('aula.consulta.palabra');
        // Route::POST( '{dicId}/entradas/consulta',      'DicAulaController@buscarEntrada')          ->name('aula.consulta.busqueda');
        // Route::GET(  '{dicId}/entradas_json',          'DicAulaController@getAllEntradasJson')     ->name('aula.consulta.all.json');

        // Paginas buscadores por busqueda, letra inicial, o todas las palabras SIN DIC ID EN AL URL
        Route::GET ('/entradas'              , 'DicAulaController@getAllEntradasConsulta')->name('aula.consulta.all'      );
        Route::GET ('/entradas/letra/{letra}', 'DicAulaController@getEntradaByInitial'   )->name('aula.consulta.byInitial');
        Route::GET ('/entradas/{consulta}'   , 'DicAulaController@getEntradaByConsulta'  )->name('aula.consulta.palabra'  );
        // Buscar con etiquetas
        Route::GET ('/entradas/{consulta}/etiquetas/{tematicasIds}'   , 'DicAulaController@getEntradaByConsulta'  )->name('aula.consulta.palabra.tematica'  );
        Route::GET ('/entradas/etiquetas/{tematicasIds}'   , 'DicAulaController@getEntradaByConsulta'  )->name('aula.consulta.tematica'  );

        

        Route::POST('/entradas/consulta'     , 'DicAulaController@buscarEntrada'         )->name('aula.consulta.busqueda' );
        Route::GET ('/entradas_json'         , 'DicAulaController@getAllEntradasJson'    )->name('aula.consulta.all.json' );

        // obtener mas entradas de aula por ajax
        Route::GET('{dicId}/entradas/n/{offset}'               , 'DicAulaController@getEntradasConsultaAjax'  )->name('aula.consulta.ajax');
        Route::GET('entradas/n/{offset}'                       , 'DicAulaController@getEntradasConsultaAjax'  )->name('aula.consulta.ajax');
        Route::GET('{dicId}/entradas/letra/{letra}/n/{offset}' , 'DicAulaController@getEntradasbyInitialAjax' )->name('aula.consulta.ajaxLetra');
        Route::GET('entradas/letra/{letra}/n/{offset}'         , 'DicAulaController@getEntradasbyInitialAjax' )->name('aula.consulta.ajaxLetra');
        Route::GET('{dicId}/entradas/{consulta}/n/{offset}'    , 'DicAulaController@getEntradasConsultaAjax'  )->name('aula.consulta.ajaxConsulta');
        Route::GET('entradas/{consulta}/n/{offset}'            , 'DicAulaController@getEntradasConsultaAjax'  )->name('aula.consulta.ajaxConsulta');
        
        Route::GET('/x/entradas/tematicas/{tematicasIds}/n/{offset}', 'DicAulaController@getEntradasConsultaAjax')->name('aula.tematica.ajaxConsulta');
        Route::GET('/x/entradas/{consulta}/tematicas/{tematicasIds}/n/{offset}', 'DicAulaController@getEntradasConsultaAjax')->name('aula.tematica.consulta.ajaxConsulta');


        // Acepciones aula
        Route::GET('/{id}/entrada/{entrada_id}/acepcion/{acepcion_id}/ocultar', 'DicAulaController@ocultarAcepcion')->name('aula.acepcion.ocultar'); // Oculta o muestra una acepción
        Route::GET('/{id}/entrada/{entrada_id}/ocultar', 'DicAulaController@ocultarEntrada')->name('aula.entrada.ocultar'); // modifica la entrada poniéndola como oculta

        // obtiene datos de las entradas del diccionario en formato json , se usa en enviar entradas de diccionario personal para saber el estado de las entradas enviadas:
        Route::GET('/{id}/entradas.json', 'DicAulaController@getEntradasJson')->name('diccionarioaula.entradas.json');

        // Comprube si se puede enviar la entarda
        Route::POST('/{id}/comprobarEntradas', 'DicAulaController@comprobarEntradasAEnviar')->name('diccionarioaula.comprobarentradas');

        // Editar acepcion en envios_acepciones
        Route::GET ('/{diccionario_id}/entrada/{entrada_id}/acepcion/{acepcion_id}/edit', 'EnvioAcepcionController@editAcepcionGet' )->name('aula.acepcion.edit'  ); // Muestra formurlario editar EnvioAcepcion como docente
        Route::POST('/acepcion/{acepcion_id}/edit'                                      , 'EnvioAcepcionController@editAcepcionPost')->name('aula.acepcion.update'); // Enviar datos del formulario envioAcepcion

        // mantenimiento de medios - borra medio de EnvioAcepcion
        Route::GET('/acepcion/{acepcion_id}/dpmedio/{medio_id}/delete', 'EnvioAcepcionController@editAcepcionDeleteMedio')->name('aula.medio.delete'); //

        // Ver y editar entrada desde consulta diccionario de aula
        Route::GET ('/{diccionario_id}/entrada/{entrada_id}'     , 'DicAulaController@daEntradaGET'     )->name('aula.entrada.get'   ); // devuelve la entrada completa
        Route::GET ('/{diccionario_id}/entrada/{entrada_id}/edit', 'DicAulaController@daEditEntradaGET' )->name('aula.entrada.edit'  ); // Editar solo nombre de entrada
        Route::POST('/{diccionario_id}/entrada/{entrada_id}/edit', 'DicAulaController@daEditEntradaPOST')->name('aula.entrada.update'); // guarda la modificación de la entrada en base de datos

        // mostar opciones pdf
        Route::GET('/{diccionario_id}/pdf', 'PDFController@optionsPdfAula')->name('pdfAula.options');
    });

    //Exportar CSV************************************************************************************************

    Route::GET('/getCSV', 'CSVController@generarCSV')->name('csv');
    Route::GET('/getAllDatesCSV', 'CSVController@generarCsvAll')->name('csvAll');  

    // Exportar PDF ***********************************************************************************************
    Route::group(['prefix' => 'pdf'], function () {
        Route::view('/paginapdf', 'layouts/partials/pdf/showPDFButton');            // Devuelve una vista donde hay un botón PDF. Provisional. Se debe borrar.

        
        Route::GET('/{diccionario_id}/getdp', 'PDFController@generarPdfDPGET')->name('pdfdp.download');    // Genera un PDF con los datos de un diccionario personal.
        Route::POST('/{diccionario_id}/getdp', 'PDFController@generarPdfDPGET')->name('pdfdp.download');

        Route::GET('/{diccionario_id}/getda', 'PDFController@generarPdfDAGET')->name('pdfda.download');    // Genera un PDF con los datos de un diccionario de aula.
        Route::POST('/{diccionario_id}/getda', 'PDFController@generarPdfDAGET')->name('pdfda.download');    // Genera un PDF con los datos de un diccionario de aula.
    });

    // Envío de mail ********************************************************************************************
    Route::group(['prefix' => 'mail'], function () {
        Route::view('/paginamail', 'layouts/partials/mail/showMailButton');     // Devuelve una vista donde hay un botón para mandar un mail
        Route::GET('/get', 'MailController@sendHtmlMail')->name('mail.send');    // Envía un e-mail.
    });
    
    // probar cas sin webservice ni cas
    Route::GET('/test',  'HomeController@test' )->name('test');

    // Route::GET('/down/12g48132413471234hukoe',  'HomeController@downloadLogs' )->name('down');

    // Temporal para probar errores 403/404/etc  ****************************************************************
    // Route::group(['prefix' => 'error'], function () {
    //     Route::view('/403', 'errors/403');     // devuelve vista 403
    //     Route::view('/404', 'errors/404');     // devuelve vista 404
    // });

    // Subir imagenes tinyMce
    Route::post('/upload', 'HomeController@upload');
});

Auth::routes();

Route::group([
        'prefix' => '/admin', 
        // 'middleware' => ['admin.user']
    ], function () 
{
    // Route::get('/maintenance', 'Voyager\VoyagerController@maintenance')->name('maintenance');

    Route::get('/logs', 'Voyager\VoyagerCompassController@logs_viewer')
        ->name('logs.view');
    // hacte falta editar el menu para que aparezca en //lexican/admin/menus
    Route::get('/vigencia', 'Voyager\VoyagerCompassController@vigencia')
        ->name('vigencia')
        ->middleware('admin.user');
    Route::get('/vigencia/comprobar', 'Voyager\VoyagerCompassController@comprobarVigenciaDicionariosAula')
        ->name('vigencia.comprobar')
        ->middleware('admin.user');
    Route::get('/vigencia/comprobar/{diccionario_id}', 'Voyager\VoyagerCompassController@comporbarVigenciaDiccionario')
        ->name('vigencia.comprobar.diccionario_id');
    Route::get('/vigencia/atemporal/{diccionario_id}', 'Voyager\VoyagerCompassController@setAtemporalDicAula')
        ->name('vigencia.setAtemporal')
        ->middleware('admin.user');
    Route::get('/vigencia/atemporal/{diccionario_id}/desactivar', 'Voyager\VoyagerCompassController@unsetAtemporalDicAula')
        ->name('vigencia.unsetAtemporal')
        ->middleware('admin.user');
    Route::get('/vigencia/{tipoDiccionario}', 'Voyager\VoyagerCompassController@getDatosTipoDiccionarioSelecionado')
        ->name('vigencia.getDatosTipoDiccionario')
        ->middleware('admin.user');
    Route::get('/vigencia/activacion/{id}', 'Voyager\VoyagerCompassController@activarDiccionarioCursoVigente')
        ->name('vigencia.activacionDiccionario')
        ->middleware('admin.user');


    Route::get('/vigencia/all.json', 'Voyager\VoyagerCompassController@diccionariosVigentesTodosJson')
        ->name('vigencia.all.json')
        ->middleware('admin.user');
    Route::get('/vigencia/atemporal.json', 'Voyager\VoyagerCompassController@diccionariosVigentesAtemporalJson')
        ->name('vigencia.atemporal.json')
        ->middleware('admin.user');
    Route::get('/vigencia.json', 'Voyager\VoyagerCompassController@diccionariosVigentesJson')
        ->name('vigencia.json')
        ->middleware('admin.user');

    Route::get('/diccionarios_curso', 'Voyager\VoyagerCompassController@dicsCursoEscolar')
    ->name('dicsCursoEscolar')
    ->middleware('admin.user');

    Route::get('/diccionarios_personales', 'Voyager\VoyagerCompassController@dicsPersonales')
    ->name('dicsPersonales')
    ->middleware('admin.user');

    Route::get('/centrosAñoEscolar', 'Voyager\VoyagerCompassController@centrosAñoEscolar')
    ->name('centrosAñoEscolar')
    ->middleware('admin.user');

    Route::get('/grabacion', 'Voyager\vRecordingController@index')
    ->name('grabacion')
    ->middleware('admin.user');

    Route::post('/sendFileJs', 'Voyager\vRecordingController@showResult')
    ->name("sendFileJs");

    Route::post('/sendFotosJs', 'Voyager\vRecordingController@showFotosResult')
    ->name("sendFotosJs");
    
        
    // Route::get('/configuracion', 'Voyager\VoyagerController@configuracion_view')
    //     ->name('configuracion.view');
    // Route::post('/configuracion', 'Voyager\VoyagerController@configuracion_write')
    //     ->name('configuracion.write');
    Voyager::routes();
});

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
    Route::get('/maintenance', 'Voyager\VoyagerController@maintenance')->name('maintenance')->middleware('admin.user');
    Route::get('/logs', 'Voyager\VoyagerCompassController@logs_viewer')->name('logs.view')->middleware('admin.user');
    Route::get('/configuracion', 'Voyager\VoyagerController@configuracion_view')->name('configuracion.view')->middleware('admin.user');
    Route::post('/configuracion', 'Voyager\VoyagerController@configuracion_write')->name('configuracion.write')->middleware('admin.user');
    Route::get('/warnings/create', 'Voyager\VoyagerCompassController@createWarning')->name('warnings.create')->middleware('admin.user');
    Route::get('/warnings/{id?}', 'Voyager\VoyagerCompassController@warnings')->name('warnings')->middleware('admin.user');
    Route::get('/warnings/{id}/edit', 'Voyager\VoyagerCompassController@createWarning')->name('warnings.edit')->middleware('admin.user');
    Route::delete('/warnings/{id}', 'Voyager\VoyagerCompassController@deleteWarnings')->name('warnings.delete')->middleware('admin.user');
    Route::post('/warnings/create', 'Voyager\VoyagerCompassController@saveWarning')->name('warnings.create')->middleware('admin.user');
    Route::put('/warnings/{id?}/create', 'Voyager\VoyagerCompassController@saveWarning')->name('warnings.save')->middleware('admin.user');
    Route::put('/warnings/status/{id}', 'Voyager\VoyagerCompassController@statusWarning')->name('warnings.status')->middleware('admin.user');
});

// Route::group(['as' => 'voyager.'], function () {
//     event(new Routing());

//     $namespacePrefix = '\\'.config('voyager.controllers.namespace').'\\';

//     Route::get('login', ['uses' => $namespacePrefix.'VoyagerAuthController@login',     'as' => 'login']);
//     Route::post('login', ['uses' => $namespacePrefix.'VoyagerAuthController@postLogin', 'as' => 'postlogin']);

//     Route::group(['middleware' => 'admin.user'], function () use ($namespacePrefix) {
//         event(new RoutingAdmin());
//         // BREAD Routes
//         Route::group([
//             'as'     => 'bread.',
//             'prefix' => 'bread',
//         ], function () use ($namespacePrefix) {
//             Voyager::routes();
//             Route::get('{table}/export', ['uses' => $namespacePrefix.'VoyagerBreadController@export', 'as' => 'export'])->middleware('after');
//         });

//         event(new RoutingAdminAfter());
//     });

//     event(new RoutingAfter());
// });


// RUTAS CAS
Route::get('/cas/login', function () {
    return cas()->authenticate();
})->name('cas.login');

Route::get('/cas/callback', 'Auth\CasController@callback')->name('cas.callback');

Route::post('/cas/logout', function () {
    auth()->logout();
    session()->flush();
    session()->save();
    cas()->logout(url('/'));
})->name('cas.logout');

Route::get('/login', function () {
    return redirect()->route('cas.login');
})->name('login');
