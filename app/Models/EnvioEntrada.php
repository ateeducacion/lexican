<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo EnvioEntrada
 * 
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class EnvioEntrada extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'envios_entradas';

    /**
     * Get all of the video's comments.
     */
    public function dpEnvio()
    {
        return $this->belongsTo(Envio::class, 'dp_envio_id');
    }

    /**
     * Obtiene las acepciones de la entrada del envío
     */
    public function envioAcepciones()
    {
        return $this->hasMany(EnvioAcepcion::class, 'envio_entrada_id');
    }

    /**
     * Get all of the video's comments.
     */
    public function dpEntrada()
    {
        return $this->belongsTo(DiccionarioPersonalEntrada::class, 'dp_entrada_id');
    }

    /**
     * Get el usuario que envió la entrada
     */
    public function dpPersonal()
    {
        return $this->hasOneThrough(DiccionarioPersonal::class, DiccionarioPersonalEntrada::class, 'id', 'id', 'dp_entrada_id', 'dic_personal_id');
    }

    /**
     * Get el comentarios de la entrada
     */
    public function comentariosEntrada()
    {
        return $this->hasMany(ComentarioEntrada::class, 'envio_entrada_id');
    }

    /**
     * Obtiene llos diccionarios de aula que tienen comentarios
     */
    public function dicAulas()
    {
        return $this->belongsToMany(DicAula::class, ComentarioEntrada::class, 'envio_entrada_id', 'dic_aula_id');
    }

    /**
     * Obtiene el diccionario de aula
     */
    public function dicAula()
    {
        return $this->hasOneThrough(DicAula::class, Envio::class, 'id', 'id', 'dp_envio_id', 'dic_aula_id');
    }

    /**
     * Obtiene la publicación de la entrada
     */
    public function publicadas()
    {
        return $this->hasOne(DicAulaEntrada::class, 'envio_entrada_id');
    }

    public function updatedBy(Type $var = null)
    {
        return $this->belongsTo(Persona::class, 'updated_by_persona_id');
    }

    // borrar
    // public function borrar()
    // {
    //     // \DB::beginTransaction();
    //     try {
    //         $acepciones = $this->envioAcepciones()->get();
    //         foreach ($acepciones as $a) {
    //             $a->borrar();
    //         }
            
    //         $this->comentariosEntrada()->delete();

    //         $result = parent::forceDelete();

    //         // \DB::commit();
    //         return $result;
    //     } catch (\Throwable $th) {
    //         // dd($th);
    //         // \DB::rollBack();
    //         throw $th;
    //     }
    // }

}
