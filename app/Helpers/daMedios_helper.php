<?php

use App\Models\EnvioAcepcion;

if (!function_exists('daMedio_getImagen')) {
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
    function daMedio_getImagen($acepcion)
    {
        if ( method_exists($acepcion,'envioAcepcionesMedios' )) {
            return $acepcion->envioAcepcionesMedios()->where('tipo_medio', '=', config('ctes.tipos_medios.imagen'))->first();
        } else {
            return null;
        }
    }
}



if (!function_exists('daMedio_getAudio')) {
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
    function daMedio_getAudio($acepcion)
    {
        if ( method_exists($acepcion,'envioAcepcionesMedios' ) || method_exists($acepcion,'dpAcepcionMedios' )) {
            // En acepiciones Diccionario Personal
            if ($acepcion instanceof \app\Models\EnvioAcepcion) {
                return $acepcion->envioAcepcionesMedios()
                    ->where('tipo_medio', '=', config('ctes.tipos_medios.audio'))
                    ->first();
            } else {
                return $acepcion->dpAcepcionMedios
                    ->where('tipo_medio', '=', config('ctes.tipos_medios.audio'))
                    ->first();
            }

        } else {
            // En Acepciones de aula
            return null;
        }
    }
}

if (!function_exists('daMedio_getVideo')) {
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
    function daMedio_getVideo($acepcion)
    {
        if ( method_exists($acepcion,'envioAcepcionesMedios') || method_exists($acepcion,'dpAcepcionMedios') ) {
            if ($acepcion instanceof \app\Models\EnvioAcepcion) {
                return $acepcion
                    ->envioAcepcionesMedios()
                    ->where('tipo_medio', '=', config('ctes.tipos_medios.video'))
                    ->first();
            } else {
                return $acepcion
                    ->dpAcepcionMedios
                    ->where('tipo_medio', '=', config('ctes.tipos_medios.video'))
                    ->first();
            }

        } else {
            return null;
        }
    }
}


if (!function_exists('daMedio_getImagenPublicURL')) {
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
    function daMedio_getImagenPublicURL($acepcion)
    {
        return URL::to('/') . '/storage/' . config('ctes.path_medios.imagen') . '/' .  daMedio_getVideo($acepcion)->url_interna;
    }
}

if (!function_exists('daMedio_getAudioPublicURL')) {
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
    function daMedio_getAudioPublicURL($acepcion)
    {
        return URL::to('/') . '/storage/' . config('ctes.path_medios.audio') . '/' .  daMedio_getAudio($acepcion)->url_interna;
    }
}

if (!function_exists('daMedio_getVideoPublicURL')) {
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
    function daMedio_getVideoPublicURL($acepcion)
    {
        return URL::to('/') . '/storage/' . config('ctes.path_medios.video') . '/' .  daMedio_getVideo($acepcion)->url_interna;
    }
}
