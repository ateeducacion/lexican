<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo EnvioAcepcionMedio
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class EnvioAcepcionMedio extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'envios_acepciones_medios';

    /**
     * Get all of the video's comments.
     */
    public function envioAcepcion()
    {
        return $this->belongsTo(EnvioAcepcion::class, 'envio_acepcion_id');
    }
}
