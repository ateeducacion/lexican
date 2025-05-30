<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo Persona
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class Persona extends  Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'personas';

    /**
     * @var string  $fillable  Array con los valores que se pueden asignar masivamente.
     */
    protected $fillable = ['id'];

    /**
     * Usuario de la persona.
     */
    public function personaUser()
    {
        return $this->hasOneThrough(
            'App\User',
            'App\Models\UserPersona',
            'persona_id', // Referencia a la tabla personas en la tabla users_personas
            'id', // Clave foránea de la tabla users a la tabla users_personas
            'id', // Clave foránea de la tabla personas a la tabla users_personas
            'user_id' // Referencia a la tabla usuarios en la tabla users_personas
        );
    }

    /**
     * Diccionarios asociada al usuario.
     */
    public function dicAulas()
    {
        return $this->hasMany(
            'App\Models\DicAula'
        );
    }
    /**
     * Obtiene el nombre completo de la persona
     */
    public function nombreCompleto($primeroApellidos = false)
    {
        $nombre = $this->nombre;
        $apellidos = $this->apellidos;
        if ($primeroApellidos) {
            return $apellidos . ', ' . $nombre;
        } else {
            return $nombre . ' ' . $apellidos;
        }
    }
}
