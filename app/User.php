<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Modelo User
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class User extends \TCG\Voyager\Models\User
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'role_id','name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Persona asociada al usuario.
     */
    public function userPersona()
    {
        return $this->hasOneThrough(
            'App\Models\Persona',
            'App\Models\UserPersona',
            'user_id', // Referencia a la tabla users en la tabla users_personas
            'id', // Clave foránea de la tabla personas a la tabla users_personas
            'id', // Clave foránea de la tabla users a la tabla users_personas
            'persona_id' // Referencia a la tabla personas en la tabla users_personas
        );
    }
    

    /**
     * The users that in the centre. | Los usuarios que pertenecen al centro
     */
    public function centros()
    {
        return $this->belongsToMany(
            'App\Models\Centro',
            'users_centros',
            'user_id',
            'centro_id'
        )->withTimestamps();
    }
}
