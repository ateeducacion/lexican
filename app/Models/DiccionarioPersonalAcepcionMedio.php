<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Support\Facades\Storage;

/**
 * Modelo DiccionarioPersonalAcepcionMedio
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class DiccionarioPersonalAcepcionMedio extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'dp_acepciones_medios';

    /**
     * Get all of the video's comments.
     */
    public function dpAcepcion()
    {
        return $this->belongsTo(DiccionarioPersonalAcepcion::class, 'dic_acepcion_id');
    }

    // this is a recommended way to declare event handlers
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($acepcionMedio) { // before delete() method call this
            // Obtener los datos del medio
            $tipo_medio = $acepcionMedio->tipo_medio;
            $url_interna = $acepcionMedio->url_interna;

            // Obtenemos el path del medio a borrar
            $storage_folder = dpMedio_getStorageFolder($tipo_medio);

            $filename_storage =  $acepcionMedio->url_interna;

            Storage::delete($storage_folder . '/' . $filename_storage);

            // do the rest of the cleanup...
        });
    }
}
