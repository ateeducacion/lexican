<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo CampoEntrada
 *
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class CampoEntrada extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model.
     */
    protected $table = 'mst_campos_entrada';

    /**
     * Collección modelos tabla mst_campos_valores relacionados con el modelo CampoEntrada
     *
     * @return Object Illuminate\Database\Eloquent\Collection
     */
    public function campoValores()
    {
        return $this->hasMany('App\Models\CampoValor', 'mst_campo_entrada_id', 'id');
    }
}
