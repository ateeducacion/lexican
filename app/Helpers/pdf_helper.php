<?php

use App\Models\DiccionarioPersonal;
use App\Models\DiccionarioPersonalAcepcionMedio;
use App\Models\DicAula;
use App\Models\DiccionarioPersonalAcepcionTematica;
use App\Models\EnvioAcepcion;
use App\Models\EnvioAcepcionMedio;
use App\Models\EnvioAcepcionTematica;
use App\Models\EnvioEntrada;

if (!function_exists('getDataByDiccionarioPersonalId')) {
    /**
     * Esta función accede a las tablas del diccionario personal y obtiene diversos datos para mostrar en un array que se devolverá en la llamada.
     *
     * @author josecarlos.trillo@altia.es
     * @version 1.0.0
     * 
     * @param integer $diccionario_id El identificador del diccionario debe coincidir con el campo id de la tabla de BBDD dic_personal
     * 
     * @return array Devuelve un array data[] que contendrá un campo texto título, otro con el nomble completo del dueño del diccionario y otro campo 'contenido' que será un array de arrays.
     *               En el array de arrays irán las entradas del diccionario y sus acepciones.
     */
    function getDataByDiccionarioPersonalId($diccionario_id, $showHidden=false, $tematicas_ids=null )
    {   
        try {
            $diccionario = DiccionarioPersonal::find($diccionario_id);
        } catch (\Throwable $th) {
            //throw $th;
            throw new Exception("no existe diccionario", 404);
        }
        // Tenemos que simplificar estos accesos usando métodos en el modelo. Para ver que funciona haré accesos a BBDD simples.
        $titulo = $diccionario->titulo;
        // Necesito el nombre y Apellidos del dueño del diccionario.
        $nombreCompleto = $diccionario->persona->nombreCompleto();

        $contenido      = [];
        $dic_entradas   = [];
        $dic_acepciones = [];

        $entradas = $diccionario->dpEntradas()
            ->where('dic_personal_id', $diccionario->id);
            
        //Mostrar solo las entrnadas visibles
        if (!$showHidden) {
                $entradas->where('estado','=', config('ctes.estados_entrada.visible'));
        }

        if ( $tematicas_ids ){
            $tematicas_ids = explode(',', $tematicas_ids);
            array_shift($tematicas_ids);
        }

        if ( $entradas = $entradas->orderBy('entrada')->get()) {
            // Empezamos recorriendo todas las entradas del diccionario.
            foreach ($entradas as $entrada) {
                $dic_entradas = [];
                $acepciones = $entrada->dpAcepciones('ASC');
                if ($tematicas_ids){
                    // $acepciones_tematicas = clone $acepciones;
                    $acepciones = $acepciones->whereHas('dpAcepcionTematicas', function($query) use ($tematicas_ids) {
                        $query->whereIn('tematica_id', $tematicas_ids);
                    });
                }
                // dd( 
                //     'tematicas: ', $tematicas_ids,
                //     'acepciones todas ids ', $acepciones->pluck('id')->all() ,
                //     'acepciones tematicas todas:' , DiccionarioPersonalAcepcionTematica::whereIn('dp_acepcion_id', $acepciones->pluck('id')->all())->get(),
                //     'acepciones tematicas', $acepciones_tematicas->pluck('id')->all(),
                // );

                if ($acepciones = $acepciones->get()) {                    
                    $dic_acepciones = [];                    

                    // Recorremos todas las acepciones que tenga la entrada que estamos consultando.
                    foreach ($acepciones as $acepcion) {
                        // si esta la acepcion oculta se salta
                        if (!$showHidden && $acepcion->estado == config('ctes.estados_entrada.oculta')) {
                            continue;
                        }

                        $dic_acepciones = $acepcion;
                        $dic_acepciones['atributos']     = $acepcion->getAtributos();
                        // no entiendo por que en este caso tengo que 
                        // agergarlo asi pero en otros vale con el $dic_acepciones = $acepcion para que obtenga todo...
                        // $dic_acepciones['ejemplo2'] = $acepcion->ejemplo2;

                        // hace falta limpiar el registro anterior para que no salga repedida la imagen en la segunda acepcion cuando no tiene imagen
                        $dic_acepciones['imagen'] = '';
                        // Medios (Imagen).
                        if (dpMedio_getImagen($acepcion) ) {
                            $ruta = public_path() . '/storage/' . config('ctes.path_medios.imagen') . '/' .
                                // dpMedio_getImagen($acepcion)->url_interna ;
                                getNombreFichero(dpMedio_getImagen($acepcion)->url_interna) . config('ctes.video_thumbnail_sufijo') . ".jpg" ;
                            $dic_acepciones['imagen'] = $ruta;
                        }

                        // Añadimos la accepción a la entrada.
                        array_push($dic_entradas, $dic_acepciones);
                    }
                }
                // dd( $dic_entradas );
                if(count($dic_entradas) == 0){
                    // dd(
                    //     'entrada', $entrada->entrada,
                    //     'acepciones', $dic_entradas,
                    // );
                    // si la entrada esta vacia sin acepcioens nos saltamos el paso de inculirla en los datos para el pdf
                    continue;
                }
                // Guardamos las acepciones de una entrada en un registro con el nombre de la propia entrada.
                $contenido[$entrada->entrada] = $dic_entradas;
            }
        }
        // ---------------------------------------- FIN DE LA LÓGICA DE OBTENCION DE DATOS ----------------------------------------

        $data = [
            'titulo'         => $titulo,
            'nombreCompleto' => $nombreCompleto,
            'contenido'      => $contenido,
            'showHidden'     => $showHidden,
        ];

        return $data;
    }
}

if (!function_exists('getDataByDiccionarioAulaId')) {
    /**
     * Esta función accede a las tablas del diccionario de aula y obtiene diversos datos para mostrar en un array que se devolverá en la llamada.
     *
     * @author josecarlos.trillo@altia.es
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.1.1
     *
     * @param integer $diccionario_id El identificador del diccionario debe coincidir con el campo id de la tabla de BBDD dic_aula
     *
     * @return array Devuelve un array data[] que contendrá un campo texto título, otro con el nombre completo del dueño del diccionario (el profesor) y otro campo 'contenido' que será un array de arrays.
     *               En el array de arrays irán las entradas del diccionario y sus acepciones.
     */
    function getDataByDiccionarioAulaId($diccionario_id)
    {
        $diccionario = DicAula::find($diccionario_id);
        $titulo = $diccionario->titulo;
        $nombreCompleto = $diccionario->profesor->nombreCompleto();

        $contenido      = [];

        // envio entradsa
        $envioEntradas = peticionEntradasAula($diccionario->id)->get();

        if( $envioEntradas->count() == 0){
            throw new Exception("no hay entradas", 204);
        }
        foreach ($envioEntradas as $entrada) {
            $dic_entradas   = [];
            $acepcionesEntrada = $entrada
                ->envioAcepciones()
                ->orderBy('orden', 'asc')->get();

            if ($acepcionesEntrada->count()>0) {
                foreach ($acepcionesEntrada as $index => $acepcion) {
                    $dic_acepciones = [];
                    $dic_acepciones = $acepcion;
                    $dic_acepciones['atributos']     = $acepcion->getAtributos();
                    // hace falta limpiar el registro anterior para que no salga repedida la imagen en la segunda acepcion cuando no tiene imagen
                    $dic_acepciones['imagen'] = '';
                    if (daMedio_getImagen($acepcion)) {
                        $ruta = public_path() . '/storage/' . config('ctes.path_medios.imagen') . '/' .
                        // daMedio_getImagen($acepcion)->url_interna ;
                        getNombreFichero(daMedio_getImagen($acepcion)->url_interna) . config('ctes.video_thumbnail_sufijo') . ".jpg";
                        $dic_acepciones['imagen'] = $ruta;
                    }
                    // Añadimos la accepción a la entrada.
                    array_push($dic_entradas, $dic_acepciones);
                }
                // Guardamos las acepciones de una entrada en un registro con el nombre de la propia entrada.
                $contenido[$entrada->entrada] = $dic_entradas;
            }
        }

        // ----------------------------- FIN DE LA LÓGICA DE OBTENCION DE DATOS ----------------------------------------

        $data = [
            'titulo'         => $titulo,
            'nombreCompleto' => $nombreCompleto,
            'contenido'      => $contenido,
            'participantes'  => $diccionario->participantes
        ];

        if(empty($data['contenido'])){
            throw new Exception("no hay entradas", 204);
        }

        return $data;
    }
}


if (!function_exists('getDataByDiccionarioAulaIdTematica')) {
    /**
     * Mostrar solo entradas cuyas acepciones tengan una tematica selectionada
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     */
    function getDataByDiccionarioAulaIdTematica($diccionario_id, $tematicas_ids)
    {       
        try {
            $diccionario = DicAula::find($diccionario_id);
        } catch (\Throwable $th) {
            //throw $th;
            throw new Exception("no existe diccionario", 404);
        }
        
        // $entradas = $diccionario->dicAulaEntradas();

        // Obtenemos todas las acepciones de las entradas del diccionario que coiniddan con la tematica
        $envioAcepcionesTematica = getAcepcionesDiccionarioAulaConTematicas($diccionario, $tematicas_ids);

        $titulo = $diccionario->titulo;
        $nombreCompleto = $diccionario->profesor->nombreCompleto();

        $contenido      = [];

        // todoss los ids de EnvioAcepcion con la tematica 
        $envio_acepcion_ids = $envioAcepcionesTematica->pluck('envio_acepcion_id')->all();
        
        // envio entradsa
        $envioEntradas = peticionEntradasAula($diccionario->id)->get();

        if( $envioEntradas->count() == 0){
            throw new Exception("no hay entradas", 204);
        }
        foreach ($envioEntradas as $entrada) {            
            $dic_entradas   = [];
            $acepcionesEntrada = $entrada
                ->envioAcepciones()
                ->whereIn('id',$envio_acepcion_ids )
                ->orderBy('orden', 'asc')->get();

            $acepcionesEntradaTodas = $entrada
                ->envioAcepciones()
                ->orderBy('orden', 'asc')->get();

            if ($acepcionesEntrada->count()>0) {
                foreach ($acepcionesEntrada as $index => $acepcion) {
                    $dic_acepciones = [];
                    $dic_acepciones = $acepcion;
                    $dic_acepciones['atributos']     = $acepcion->getAtributos();
                    // hace falta limpiar el registro anterior para que no salga repedida la imagen en la segunda acepcion cuando no tiene imagen
                    $dic_acepciones['imagen'] = '';
                    if (daMedio_getImagen($acepcion)) {
                        $ruta = public_path() . '/storage/' . config('ctes.path_medios.imagen') . '/' .
                        daMedio_getImagen($acepcion)->url_interna ;
                        $dic_acepciones['imagen'] = $ruta;
                    }
                    // Añadimos la accepción a la entrada.
                    array_push($dic_entradas, $dic_acepciones);
                }
                // Guardamos las acepciones de una entrada en un registro con el nombre de la propia entrada.
                $contenido[$entrada->entrada] = $dic_entradas;
            }
        }

        $data = [
            'titulo'         => $titulo,
            'nombreCompleto' => $nombreCompleto,
            'contenido'      => $contenido,
            'participantes'  => $diccionario->participantes
        ];

        if(empty($data['contenido'])){
            throw new Exception("no hay entradas", 204);
        }

        return $data;
    }   
}

if (!function_exists('getAcepcionesDiccionarioAulaConTematicas')) {
    /**
     * Undocumented function
     *
     * @param [type] $diccionarioAula
     * @param [type] $tematicas_ids
     * @return \Illuminate\Database\Eloquent\Collection<EnvioAcepcionTematica>
     */
    function getAcepcionesDiccionarioAulaConTematicas($diccionarioAula, $tematicas_ids)
    {

        $envioEntradas_ids = $diccionarioAula->dicAulaEntradas()->pluck('envio_entrada_id')->all();

        $acepciones_ids = EnvioAcepcion::whereIn('envio_entrada_id', $envioEntradas_ids)->get()->pluck('id');

        $tematicas_ids = explode(',', $tematicas_ids);
        array_shift($tematicas_ids);
        $acepciones_ids = $acepciones_ids->toArray();

        $acepcionesConTematica =  EnvioAcepcionTematica::whereIn('envio_acepcion_id',$acepciones_ids) 
            ->whereIn('tematica_id', $tematicas_ids);

        
        return $acepcionesConTematica;
    }        
}

if (!function_exists('ordernarEntradasDiccionarioPorNombre')){
    function ordernarEntradasDiccionarioPorNombre($entradas)
    {
        $entradas = $entradas->sort(function ($a, $b) {
            if ($a == $b) {
                return 0;
            }
            return strcmp( $a['envioEntrada']['entrada'] , $b['envioEntrada']['entrada'] );
        });
        return $entradas;
    }
}
