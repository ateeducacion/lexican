<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo EnvioAcepcion
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class EnvioAcepcion extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'envios_acepciones';

    /**
     * Get all of the video's comments.
     */
    public function envioEntrada()
    {
        return $this->belongsTo(EnvioEntrada::class, 'envio_entrada_id');
    }

    /**
     * Obtiene los medios de la acepción de la entrada del envío
     */
    public function envioAcepcionesMedios()
    {
        return $this->hasMany(EnvioAcepcionMedio::class, 'envio_acepcion_id');
    }

    /**
     * Obtiene las Temáticas
     */
    public function envioAcepcionTematicas()
    {
        return $this->hasMany(EnvioAcepcionTematica::class, 'envio_acepcion_id');
    }

    /**
     * Obtiene la categoría
     * 
     * En relaiddad todos estos "dpNombre" deberian llamarse solo "nombre" pero se ha 
     * dejado asi para que funcionen los blades tanto para acepciones del dic personal
     * como para envios (dic aula)
     */
    public function dpCategoria()
    {
        return $this->belongsTo(CampoValor::class, 'cat_gramatical_id');
    }

    /**
     * Obtiene el género
     */
    public function dpGenero()
    {
        return $this->belongsTo(CampoValor::class, 'genero_id');
    }

    /**
     * Obtiene el número
     */
    public function dpNumero()
    {
        return $this->belongsTo(CampoValor::class, 'numero_id');
    }

    /**
     * Obtiene el idioma
     */
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


    // borrar
    public function borrar()
    {
        \DB::beginTransaction();
        try {
            $this->envioAcepcionesMedios()->delete();
            // $this->envioAcepcionTematicas()->delete();
            EnvioAcepcionTematica::where('envio_acepcion_id', $this->id)->delete();

            $result = parent::delete();

            \DB::commit();
            return $result;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }
}
