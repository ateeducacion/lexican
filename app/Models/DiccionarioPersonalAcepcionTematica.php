<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Support\Facades\Storage;

/**
 * Modelo DiccionarioPersonalAcepcionTematica
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class DiccionarioPersonalAcepcionTematica extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'dp_acepciones_tematicas';

    /**
     * Obtiene su acepción.
     */
    public function dpAcepcion()
    {
        return $this->belongsTo(DiccionarioPersonalAcepcion::class, 'dp_acepcion_id');
    }

    /**
     * Obtiene su Temática
     */
    public function dpTematica()
    {
        return $this->belongsTo(CampoValor::class, 'tematica_id');
    }
}
