<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo ComentarioEntrada
 *
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class ComentarioEntrada extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'comentarios_entradas';

    // Esto es necesario para que se tome este campo como una fecha y se pueda hacer ->format('d/m/Y')
    protected $dates = [
        'fecha_envio',
    ];

    /**
     * Get el envio_entrada
     */
    public function envioEntrada()
    {
        return $this->belongsTo(EnvioEntrada::class, 'envio_entrada_id');
    }

    /**
     * Get persona. Es el profesor que hace los comentarios
     */
    public function persona()
    {
        return $this->hasOne(Persona::class, 'id', 'persona_id');
    }

    /**
     * Obtiene el aula a la que petercene el envio
     */
    public function dicAula()
    {
        return $this->belongsTo(DicAula::class, 'dic_aula_id');
    }
}
