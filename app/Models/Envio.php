<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo Envio
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class Envio extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'dp_envios';

    /**
     * Get all of the video's comments.
     */
    public function dpDiccionario()
    {
        return $this->belongsTo(DiccionarioPersonal::class, 'dic_personal_id');
    }

    /**
     * Obtiene las entradas del envío
     */
    public function envioEntradas()
    {
        return $this->hasMany(EnvioEntrada::class, 'dp_envio_id');
    }

    /**
     * Obtiene el aula a la que petercene el envio
     */
    public function dicAula()
    {
        return $this->belongsTo(DicAula::class, 'dic_aula_id');
    }

    public function borrar()
    {
        \DB::beginTransaction();
        try {

            $enviosEntrada = $this->envioEntradas()->withTrashed()->get();
            foreach ( $enviosEntrada as $ee ) {
                $ee->borrar();
                // $ee->forceDelete();
            }
            
            $result = parent::delete();

            \DB::commit();
            return $result;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }
}
