<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo DiccionarioPersonalEntrada
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class DiccionarioPersonalEntrada extends  Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'dp_entradas';

    /**
     * Get el diccionario de la entrada
     */
    public function dpDiccionario()
    {
        return $this->belongsTo(DiccionarioPersonal::class, 'dic_personal_id');
    }

    /**
     * Get all aceptions of the entry.
     */
    public function dpAcepciones($orden = '')
    {
        switch (strtoupper($orden)) {
            case 'DESC':
                return $this->hasMany(DiccionarioPersonalAcepcion::class, 'dic_entrada_id')->orderBy('orden', 'desc');
                break;

            case 'ASC':
                return $this->hasMany(DiccionarioPersonalAcepcion::class, 'dic_entrada_id')->orderBy('orden', 'asc');
                break;

            default:
                return $this->hasMany(DiccionarioPersonalAcepcion::class, 'dic_entrada_id');
                break;
        }
    }

    /**
     * Get el comentarios de la entrada
     */
    public function enviosEntrada()
    {
        return $this->hasMany(EnvioEntrada::class, 'dp_entrada_id');
    }

    // this is a recommended way to declare event handlers
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($entrada) { // before delete() method call this
            if ($entrada->isForceDeleting()) {
                $entrada->dpAcepciones()->each(function ($acepcion) {
                    $acepcion->forceDelete();
                });
            } else {
                $entrada->dpAcepciones()->each(function ($acepcion) {
                    $acepcion->delete();
                });
            }
        });
    }

    /**
     * Obtiene las Temáticas de todas las acepciones de la entrada
     * 
     * @return HasManyThrough //|tematica[]
     * 
     */
    public function tematicas() 
    {
        $acepcion_visible = config('ctes.estados_entrada.visible');

        $todasTematicas = $this->hasManyThrough(
            DiccionarioPersonalAcepcionTematica::class, 
            DiccionarioPersonalAcepcion::class,  
            'dic_entrada_id',  
            'dp_acepcion_id', // dp_acepciones_tematicas.dp_acepcion_id
            'id',  // id en dp_entradas
            'id' // id en dp_acepciones
        );

        // filtrar por acepcoines que estan activas 
        $tematicasVisibles = $todasTematicas->where('dp_acepciones.estado', '=', $acepcion_visible);

        return $todasTematicas;
        
    }
}
