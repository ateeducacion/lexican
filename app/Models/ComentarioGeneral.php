<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo ComentarioGeneral
 *
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class ComentarioGeneral extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'comentarios_generales';

    /**
     * @var string $fillable permite guardados y asignaciones masivas por estos campos   
     */
    protected $fillable =   [
        'dic_personal_id',
        'persona_id',
        'dic_aula_id',
        'comentario',
        'fecha_envio',
        'estado',
    ];

    // Esto es necesario para que se tome este campo como una fecha y se pueda hacer ->format('d/m/Y')
    protected $dates = [
        'fecha_envio',
    ];

    /**
     * Get diccionario personal
     */
    public function dpDiccionario()
    {
        return $this->belongsTo(DiccionarioPersonal::class, 'dic_personal_id');
    }

    /**
     * Get persona. Es el profesor que hace los comentarios
     */
    public function persona()
    {
        return $this->hasOne('App\Models\Persona', 'id', 'persona_id');
    }

    /**
     * Obtiene el aula a la que petercene el envio
     */
    public function dicAula()
    {
        return $this->belongsTo(DicAula::class, 'dic_aula_id');
    }
}
