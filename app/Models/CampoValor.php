<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo CampoValor
 *
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class CampoValor extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'mst_campos_valores';
    
    /**
     * Modelo de la tabla mst_campos_entradas a la que pertenece el modelo CampoValor
     *
     * @return Object App\Models\CampoEntrada
     */
    public function campoEntrada()
    {
        return $this->belongsTo('App\Models\CampoEntrada', 'mst_campo_entrada_id', 'id');
    }

}
