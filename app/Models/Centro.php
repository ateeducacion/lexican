<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo Centro
 *
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class Centro extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'centros';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cod_centro', 'denominacion', 'estado',
    ];

    /**
     * The users that in the centre. | Los usuarios que pertenecen al centro
     */
    public function users()
    {
        return $this->belongsToMany(
            'App\User',
            'users_centros',
            'centro_id',
            'user_id'
        )->withTimestamps();
    }
}