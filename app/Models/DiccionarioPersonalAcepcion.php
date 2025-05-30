<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo DiccionarioPersonalAcepcion
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class DiccionarioPersonalAcepcion extends  Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'dp_acepciones';

    /**
     * Get all of the video's comments.
     */
    public function dpEntrada()
    {
        return $this->belongsTo(DiccionarioPersonalEntrada::class, 'dic_entrada_id');
    }

    /**
     * Get all of the video's comments.
     */
    public function dpAcepcionMedios()
    {
        return $this->hasMany(DiccionarioPersonalAcepcionMedio::class, 'dic_acepcion_id');
    }

    /**
     * Obtiene las Temáticas
     */
    public function dpAcepcionTematicas()
    {
        return $this->belongsToMany(CampoValor::class, 'dp_acepciones_tematicas', 'dp_acepcion_id', 'tematica_id');
    }

    // this is a recommended way to declare event handlers
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($acepcion) { // before delete() method call this
            $acepcion->dpAcepcionMedios()->each(function ($medio) {
                $medio->delete();
            });

            // do the rest of the cleanup...
        });
    }

    public function dpCategoria()
    {
        return $this->belongsTo(CampoValor::class, 'cat_gramatical_id');
    }

    public function dpGenero()
    {
        return $this->belongsTo(CampoValor::class, 'genero_id');
    }

    public function dpNumero()
    {
        return $this->belongsTo(CampoValor::class, 'numero_id');
    }

    public function dpIdioma()
    {
        return $this->belongsTo(CampoValor::class, 'idioma_id');
    }

    public function getAtributos()
    {
        $atributos = "";
        if ( $this->dpCategoria()->first() ) {
            $abrCategoria = config('ctes.abreviatura.' . $this->dpCategoria()->first()->descripcion );
            if ($abrCategoria){
                $atributos .= $abrCategoria . ' ';
            } else {
                $atributos .= $this->dpCategoria()->first()->descripcion . ' ';
            }

        }
        if ( $this->dpGenero()->first() && $this->dpGenero()->first()->descripcion!="No tiene" ) {
            $abrGenero = config('ctes.abreviatura.' . $this->dpGenero()->first()->descripcion );
            if ($abrGenero){
                $atributos .= $abrGenero . ' ';
            } else {
                $atributos .= $this->dpGenero()->first()->descripcion . ' ';
            }
        }
        if ($this->dpNumero()->first()) {
            $abrNum = config('ctes.abreviatura.' . $this->dpNumero()->first()->descripcion);

            // dd( config($abrNum) );
            if( $abrNum ){
                $atributos .= $abrNum. ' ';

            } else {
                $atributos .= $this->dpNumero()->first()->descripcion . ' ';
            }
        } 
        
        if ($atributos != "") {
            $atributos .= ' ';
        }
        $atributos = mb_strtolower($atributos);

        return $atributos;
    }

}
