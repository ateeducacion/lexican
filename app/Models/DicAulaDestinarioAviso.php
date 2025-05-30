<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo DicAulaDestinarioAviso
 *
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class DicAulaDestinarioAviso extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'dic_aula_destinatario_avisos';

    /**
     * @var boolean  $timestamps  Inserts and updates will store created_at and updated_at data
     */
    public $timestamps = true;

    /**
     * @var string $fillable permite guardados y asignaciones masivas por estos campos   
     */
    protected $fillable =   [
        'email',
        'dic_aula_id',
        'estado'
    ];
}
