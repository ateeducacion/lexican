<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo DicAulaEntrada
 *
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class DicAulaEntrada extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    
    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'dic_aula_entradas';

    /**
     * @var boolean  $timestamps  Inserts and updates will store created_at and updated_at data
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'origen',
        'estado',
        'dic_aula_id',
        'envio_entrada_id'
    ];

    public function dicAula()
    {
        return $this->belongsTo(DicAula::class, 'dic_aula_id');
    }

    public function envioEntrada(){
        return $this->belongsTo(EnvioEntrada::class, 'envio_entrada_id');
    }

    public function tematicas() 
    {
        $acepcion_visible = config('ctes.estados_entrada.visible');

        $todasTematicas = $this->hasManyThrough(
            EnvioAcepcionTematica::class, // DiccionarioPersonalAcepcionTematica::class, 
            EnvioAcepcion::class, // DiccionarioPersonalAcepcion::class,            
            'dic_entrada_id',  // envio_acepcion_tematicas
            'dp_acepcion_id', // dp_acepciones_tematicas.dp_acepcion_id
            'id',  // id en dp_entradas
            'id' // id en dp_acepciones
        );

        // filtrar por acepcoines que estan activas 
        $tematicasVisibles = $todasTematicas->where('dp_acepciones.estado', '=', $acepcion_visible);

        return $todasTematicas;
        
    }
}