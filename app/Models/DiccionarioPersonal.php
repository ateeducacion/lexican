<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo DiccionarioPersonal
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class DiccionarioPersonal extends  Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'dic_personal';

    /**
     * Get la persona del diccionario personal
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    /**
     * Get all of the video's comments.
     */
    public function dpEntradas($orden = '')
    {
        switch (strtoupper($orden)) {
            case 'DESC':
                return $this->hasMany(DiccionarioPersonalEntrada::class, 'dic_personal_id')->orderBy('entrada', 'desc');
                break;

            case 'ASC':
                return $this->hasMany(DiccionarioPersonalEntrada::class, 'dic_personal_id')->orderBy('entrada', 'asc');
                break;

            default:
                return $this->hasMany(DiccionarioPersonalEntrada::class, 'dic_personal_id');
                break;
        }
    }
}
