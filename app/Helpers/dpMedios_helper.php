<?php

use App\Models\DiccionarioPersonalAcepcion;
use App\Models\DiccionarioPersonalAcepcionMedio;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (!function_exists('dpMedio_GetMedioByAcepcionTipoMedio')) {
    /**
     * Devuelve el objeto Medio de una acepción según reciba imagen, audio o video en el campo tipo_medio
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $tipo_medio constante perteneciente a ctes.tipos_medios
     * @param integer $acepcion_id
     * 
     * @return object DiccionarioPersonalAcepcionMedio
     */
    function dpMedio_GetMedioByAcepcionTipoMedio($tipo_medio, $acepcion_id)
    {
        $medio = DiccionarioPersonalAcepcionMedio::where('tipo_medio', '=', $tipo_medio)
            ->where('dic_acepcion_id', '=', $acepcion_id)
            ->first();

        return $medio;
    }
}

if (!function_exists('dpMedio_TratarMedio')) {
    /**
     * Recibe el fichero de un medio y su tipo y la acepción donde se desea guardar
     *  Sustituye el medio del mismo tipo que tuviera la acepción por el nuevo medio recibido
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $file
     * @param integer $tipo_medio constante perteneciente a ctes.tipos_medios
     * @param integer $entrada_id
     * @param integer $acepcion_id
     * 
     * @return object DiccionarioPersonalAcepcionMedio
     */
    function dpMedio_TratarMedio($file, $tipo_medio, $entrada_id, $acepcion_id)
    {
        // ****************** Eliminar el medio anterior
        $medio_anterior = dpMedio_GetMedioByAcepcionTipoMedio($tipo_medio, $acepcion_id);
        if ($medio_anterior) {
            dpMedio_DeleteMedio($medio_anterior);
        }

        // ****************** Creamos el nuevo medio
        // Grabamos el fichero en el STORAGE
        $filename_original = $file->getClientOriginalName();
        $filename_storage = dpMedio_SaveMedio($file, $tipo_medio, $entrada_id, $acepcion_id);

        // Grabamos en base de datos los datos de la imagen
        $acepcionMedio = new DiccionarioPersonalAcepcionMedio;
        $acepcionMedio->dic_acepcion_id = $acepcion_id;
        $acepcionMedio->tipo_medio = $tipo_medio;
        $acepcionMedio->nombre = $filename_original;
        $acepcionMedio->url_externa = '';
        $acepcionMedio->url_interna = $filename_storage;
        $acepcionMedio->estado = config('ctes.estados.activo');
        $grabado = $acepcionMedio->save();

        // ****************** Creamos thumbnail de Imagen
        if ($tipo_medio == config('ctes.tipos_medios.imagen')) {
            // Obtenemos el path de la imagen origen
            $imagen_origen = dpMedio_getStorageFolderCompleto($tipo_medio) . DIRECTORY_SEPARATOR . $filename_storage;

            // Obtenemos el path de la imagen destino
            $nombre_fichero_destino = getNombreFichero($acepcionMedio->url_interna) . config('ctes.video_thumbnail_sufijo') . ".jpg";
            $imagen_destino = dpMedio_getStorageFolderCompleto($tipo_medio) . DIRECTORY_SEPARATOR . $nombre_fichero_destino;

            // Creamos el thumbnail y lo guardamos en disco
            $imageThumbnail = getImageThumbnail($imagen_origen, $imagen_destino, config('ctes.imagen_thumbnail_tamano'));
        }

        // ****************** Creamos thumbnail de Video
        if ($tipo_medio == config('ctes.tipos_medios.video')) {
            getVideoThumbnail($tipo_medio, $filename_storage);
        }

        return $acepcionMedio;
    }
}

if (!function_exists('dpMedio_SaveMedio')) {
    /**
     * Devuelve el nombre del fichero que se guarda en el directorio Storage
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $file
     * @param integer $tipo_medio constante perteneciente a ctes.tipos_medios
     * @param integer $entrada_id
     * @param integer $acepcion_id
     * 
     * @return string 
     */
    function dpMedio_SaveMedio($file, $tipo_medio, $entrada_id, $acepcion_id)
    {
        // Obtenemos el id de persona del usuario conectado
        $persona_id = getSessionPersona()['id'];

        // Obtenemos el path del medio a grabar
        $storage_folder = dpMedio_getStorageFolder($tipo_medio);

        // Generamos el nombre del medio a grabar
        $nombre_fichero =  $persona_id . '_' . $entrada_id  . '_' . $acepcion_id . '_' . Str::random(20) . '.' .  $file->extension();

        // Grabamos el fichero
        Storage::putFileAs($storage_folder, $file, $nombre_fichero);

        return $nombre_fichero;
    }
}

if (!function_exists('dpMedio_DeleteMedio')) {
    /**
     * Recibe un objeto Medio y lo borra de la base de datos y borra el fichero
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $medio DiccionarioPersonalAcepcionMedio
     * 
     * @return void
     */
    function dpMedio_DeleteMedio($medio)
    {

        try {
            DiccionarioPersonalAcepcionMedio::destroy($medio->id);
        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_conflict'),
                ['file' => __FILE__, 'line' => __LINE__],                
                PHP_EOL . json_encode($medio, JSON_PRETTY_PRINT),
                PHP_EOL . $th->getMessage() .
                PHP_EOL ."Error al intentar borrar medio de la bbdd"
            );
        }

        // Obtener los datos del medio
        $tipo_medio = $medio->tipo_medio;
        $filename_storage =  $medio->url_interna;

        // Obtenemos el path del medio a borrar
        $storage_folder = dpMedio_getStorageFolder($tipo_medio);

        try {
            $acepcion = DiccionarioPersonalAcepcion::find($medio->dic_acepcion_id);
            if (  $acepcion->dpEntrada->enviosEntrada->count()>0 ) {
                // no se borra ya que es posible que se este usando en las entradas del
                // diccionario perosonal
                customLoggin(
                    config('ctes.log_levels.info'),
                    config('ctes.log_types.data_conflict'),
                    ['file' => __FILE__, 'line' => __LINE__],                
                    PHP_EOL . json_encode($medio, JSON_PRETTY_PRINT),
                    "medio '$filename_storage' no se borrara, se esta usanodo en diccionario aula"
                );
            } else {
                // *********************   Borramos el fichero
                // Si no se ha relizado el envio de esta entrada se puede borrar el medio
                // si no es posible que se use en el dic personal
                Storage::delete($storage_folder . '/' . $filename_storage);

                // Borramos el thumbnail
                switch ($tipo_medio) {
                    case config('ctes.tipos_medios.imagen'):
                        $filename_storage = getNombreFichero($filename_storage) . config('ctes.video_thumbnail_sufijo') . '.jpg';
                        Storage::delete($storage_folder . '/' . $filename_storage);
                        break;
                    case config('ctes.tipos_medios.audio'):
                        // no hay thumbnail de audios
                        break;
                    case config('ctes.tipos_medios.video'):
                        $filename_storage = getNombreFichero($filename_storage) . config('ctes.video_thumbnail_sufijo') . ".jpg";
                        Storage::delete($storage_folder . '/' . $filename_storage);
                        break;
                }
                customLoggin(
                    config('ctes.log_levels.info'),
                    config('ctes.log_types.data_conflict'),
                    ['file' => __FILE__, 'line' => __LINE__],                
                    PHP_EOL . json_encode($medio, JSON_PRETTY_PRINT),
                    "medio '$filename_storage' Borrado"
                );
            }
        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_conflict'),
                ['file' => __FILE__, 'line' => __LINE__],                
                PHP_EOL . json_encode($medio, JSON_PRETTY_PRINT),
                PHP_EOL . $th->getMessage() .
                PHP_EOL ."Se ha prodcido error al intentar borrar medio '$filename_storage'"
            );            
        }
    }
}


if (!function_exists('dpMedio_getStorageFolderCompleto')) {
    /**
     * Recibe un tipo de medio y devuelve el path INTERNO del Storage Completo teniendo en cuenta de si es Windows o Linux
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $tipo_medio
     * 
     * @return string
     */
    function dpMedio_getStorageFolderCompleto($tipo_medio)
    {
        switch ($tipo_medio) {
            case config('ctes.tipos_medios.imagen'):
                $storage_folder = storage_path('app') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . config('ctes.path_medios.imagen');
                break;
            case config('ctes.tipos_medios.audio'):
                $storage_folder = storage_path('app') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . config('ctes.path_medios.audio');
                break;
            case config('ctes.tipos_medios.video'):
                $storage_folder = storage_path('app') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . config('ctes.path_medios.video');
                break;
        }

        // cambio los / por \ cuando se está en Windows
        if (DIRECTORY_SEPARATOR == '\\') {
            $storage_folder = str_replace('/', '\\', $storage_folder);
        }


        return $storage_folder;
    }
}

if (!function_exists('dpMedio_getStorageFolder')) {
    /**
     * Recibe un tipo de medio y devuelve el path INTERNO del Storage de los medios
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $tipo_medio
     * 
     * @return string
     */
    function dpMedio_getStorageFolder($tipo_medio)
    {
        // Obtenemos el path del medio a borrar
        switch ($tipo_medio) {
            case config('ctes.tipos_medios.imagen'):
                $storage_folder = '/public/' . config('ctes.path_medios.imagen');
                break;
            case config('ctes.tipos_medios.audio'):
                $storage_folder = '/public/' . config('ctes.path_medios.audio');
                break;
            case config('ctes.tipos_medios.video'):
                $storage_folder = '/public/' . config('ctes.path_medios.video');
                break;
        }

        return $storage_folder;
    }
}

if (!function_exists('dpMedio_getPublicURL')) {
    /**
     * Recibe un tipo de medio y devuelve el path PUBLICO de los medios
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $tipo_medio
     * 
     * @return void
     */
    function dpMedio_getPublicURL($tipo_medio)
    {
        // Obtenemos el path del medio a borrar
        switch ($tipo_medio) {
            case config('ctes.tipos_medios.imagen'):
                $path_medio = '/storage/' . config('ctes.path_medios.imagen');
                break;
            case config('ctes.tipos_medios.audio'):
                $path_medio = '/storage/' . config('ctes.path_medios.audio');
                break;
            case config('ctes.tipos_medios.video'):
                $path_medio = '/storage/' . config('ctes.path_medios.video');
                break;
        }
        return $path_medio;
    }
}

if (!function_exists('dpMedio_getImagen')) {
    /**
     * Recibe una acepción y devuelve el objeto Medio de tipo Imagen
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $acepcion
     * 
     * @return object DiccionarioPersonalAcepcionMedio
     */
    function dpMedio_getImagen($acepcion)
    {
        if ( method_exists($acepcion,'dpAcepcionMedios' )) {
            return $acepcion->dpAcepcionMedios()->where('tipo_medio', '=', config('ctes.tipos_medios.imagen'))->first();
        } else {
            return null;
        }
    }
}

if (!function_exists('dpMedio_getAudio')) {
    /**
     * Recibe una acepción y devuelve el objeto Medio de tipo Audio
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $acepcion
     * 
     * @return object DiccionarioPersonalAcepcionMedio
     */
    function dpMedio_getAudio($acepcion)
    {
        if ( method_exists($acepcion,'dpAcepcionMedios' )) {
            // En acepiciones Diccionario Personal
            return $acepcion->dpAcepcionMedios()
                ->where('tipo_medio', '=', config('ctes.tipos_medios.audio'))
                ->first();
        } else {
            // En Acepciones de aula
            return null;
        }


    }
}

if (!function_exists('dpMedio_getVideo')) {
    /**
     * Recibe una acepción y devuelve el objeto Medio de tipo Video
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $acepcion
     * 
     * @return object DiccionarioPersonalAcepcionMedio
     */
    function dpMedio_getVideo($acepcion)
    {
        if ( method_exists($acepcion,'dpAcepcionMedios' )) {
            return $acepcion
                ->dpAcepcionMedios()
                ->where('tipo_medio', '=', config('ctes.tipos_medios.video'))
                ->first();
        } else {
            return null;
        }
    }
}

if (!function_exists('dpMedio_getVideoThumbnailPublicURL')) {
    /**
     * Recibe una acepción y devuelve la URL PUBLICA del thumbnail del Video
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $acepcion
     * 
     * @return string
     */
    function dpMedio_getVideoThumbnailPublicURL($acepcion)
    {
        return URL::to('/') . "/storage/" . config("ctes.path_medios.video") . '/' .  getNombreFichero(dpMedio_getVideo($acepcion)->url_interna) . config('ctes.video_thumbnail_sufijo') . ".jpg";
    }
}

if (!function_exists('dpMedio_getImagenPublicURL')) {
    /**
     * Recibe una acepción y devuelve la URL PUBLICA de la Imagen
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $acepcion
     * 
     * @return string
     */
    function dpMedio_getImagenPublicURL($acepcion)
    {
        return URL::to('/') . '/storage/' . config('ctes.path_medios.imagen') . '/' .  dpMedio_getVideo($acepcion)->url_interna;
    }
}

if (!function_exists('dpMedio_getAudioPublicURL')) {
    /**
     * Recibe una acepción y devuelve la URL PUBLICA del Audio
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $acepcion
     * 
     * @return string
     */
    function dpMedio_getAudioPublicURL($acepcion)
    {
        return URL::to('/') . '/storage/' . config('ctes.path_medios.audio') . '/' .  dpMedio_getAudio($acepcion)->url_interna;
    }
}

if (!function_exists('dpMedio_getVideoPublicURL')) {
    /**
     * Recibe una acepción y devuelve la URL PUBLICA del Video
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $acepcion
     * 
     * @return string
     */
    function dpMedio_getVideoPublicURL($acepcion)
    {
        return URL::to('/') . '/storage/' . config('ctes.path_medios.video') . '/' .  dpMedio_getVideo($acepcion)->url_interna;
    }
}

if (!function_exists('dpMedio_getImageStorageFolder')) {
    /**
     * Recibe una acepción y devuelve la URL PRIVADA de la imagen
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $acepcion
     * 
     * @return string
     */
    function dpMedio_getImageStorageFolder($acepcion)
    {
        return storage_path('app') . '/public/' . config('ctes.path_medios.imagen') . '/' .  dpMedio_getImagen($acepcion)->url_interna;
    }
}

if (!function_exists('dpMedio_getAudioStorageFolder')) {
    /**
     * Recibe una acepción y devuelve la URL PRIVADA del Audio
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $acepcion
     * 
     * @return string
     */
    function dpMedio_getAudioStorageFolder($acepcion)
    {
        return storage_path('app') . '/public/' . config('ctes.path_medios.audio') . '/' .  dpMedio_getAudio($acepcion)->url_interna;
    }
}

if (!function_exists('dpMedio_getVideoStorageFolder')) {
    /**
     * Recibe una acepción y devuelve la URL PRIVADA del Video
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param mixed $acepcion
     * 
     * @return string
     */
    function dpMedio_getVideoStorageFolder($acepcion)
    {
        return storage_path('app') . '/public/' . config('ctes.path_medios.video') . '/' .  dpMedio_getVideo($acepcion)->url_interna;
    }
}

if (!function_exists('dpEntrada_GetListaAcepcionMediosByAcepcionId')) {
    /**
     * Devuelve un array con los medios de la acepción con id recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $entrada_id
     * 
     * @return array DiccionarioPersonalAcepcionMedio
     */
    function dpEntrada_GetListaAcepcionMediosByAcepcionId($acepcion_id)
    {
        try {
            $medios = DiccionarioPersonalAcepcionMedio::where('dic_acepcion_id', '=', $acepcion_id)
            ->get();
        } catch (\Throwable $th) {
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_base_error'),
                ['file' => __FILE__, 'line' => __LINE__ ],
                PHP_EOL . 'Fallo al obtener mediso de acepcion_id ' . $acepcion_id .
                PHP_EOL . '$medio' . json_encode( $medios, JSON_PRETTY_PRINT),
                $th->getMessage()
            );
            throw $th;
        }
        

        return $medios;
    }
}
