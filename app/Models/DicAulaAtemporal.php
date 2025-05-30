<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo DicAulaAtemporal
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Fernando Ramirez Perez <fernando.ramirez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class DicAulaAtemporal extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $visible = ['id','estado'];
    protected $fillable =   ['estado'];

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'dic_aula_atemporales';

    /**
     * @var boolean  $timestamps  Inserts and updates will store created_at and updated_at data
     */
    public $timestamps = true;

    public function dicAula()
    {
        return $this->belongsTo(DicAula::class, 'dic_aula_id');
    }

    // public function estado()
    // {
    //     return $this->estado;
    //     // == config('ctes.estados.activo');
    // }
    
}
