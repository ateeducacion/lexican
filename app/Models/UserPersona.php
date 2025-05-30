<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo UserPersona
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class UserPersona extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'users_personas';

    /**
     * @var string  $fillable  Array con los valores que se pueden asignar masivamente.
     */
    protected $fillable = ['user_id', 'persona_id'];
}