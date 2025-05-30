<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Modelo DicAula
 *
 * @category Laravel
 * @package  App\Models
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class DicAula extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    /**
     * @var string  $table  The table associated with the model. | La tabla asociada al modelo.
     */
    protected $table = 'dic_aula';

    /**
     * @var boolean  $timestamps  Inserts and updates will store created_at and updated_at data
     */
    public $timestamps = true;

    /**
     * @var string $fillable permite guardados y asignaciones masivas por estos campos   
     */
    protected $fillable =   [
        'id',
        'persona_id',
        'mst_tipo_dic_id',
        'titulo',
        'descripcion',
        'letra_grupo',
        'mst_nivel_estudios_id',
        'mst_area_materia_id',
        'mst_ensenanza_id',
        'ano_ini_curso_escolar',
        'max_acepciones_entrada',
        'codigo',
        'pautas_maestras',
        'visible_estudiante',
        'envios_habilitados',
        'envios_fecha_ini',
        'estado',
        'comentarios_visibles',
        'comentarios_visibles_anteriores_a',
        'vigencia'
    ];

    // valores por defecto defecto
    protected $attributes = [
        'vigencia' => 1,
    ];

    // Esto es necesario para que se tome este campo como una fecha y se pueda hacer ->format('d/m/Y')
    protected $dates = [
        'comentarios_visibles_anteriores_a',
    ];
    // Esto lo puse como solucion al error que daba pero con el valor de arriba que puso javi da confligto y no permite guardar
    // las fechas se imprimen con este formato en las plantillas
    // protected $dateFormat = 'Y-m-d H:i:s.u';


    /**
     * Obtiene si los envios estan habilitados en la fecha actual 
     * 
     */
    public function enviosHabilitados()
    {
        switch ($this->envios_habilitados) {
            case config('ctes.estado_envio_habilitado.inactivo'): 
                return config('ctes.estado_envio_habilitado.inactivo');
                break;

            case config('ctes.estado_envio_habilitado.activo'):
                return config('ctes.estado_envio_habilitado.activo');
                break;

            case config('ctes.estado_envio_habilitado.planificado'):
                if ($this->envios_fecha_ini > new DateTime()){
                    return config('ctes.estado_envio_habilitado.activo');
                } else {
                    return config('ctes.estado_envio_habilitado.inactivo');
                }
                break;
        }
    }

    public function visibleEstudiante(){
        switch ($this->visible_estudiante){
            case config('ctes.estados.activo'): 
            case config('ctes.estados.inactivo'): 
                return $this->visible_estudiante;
                break;
            default:
                return $this->visible_estudiante;
                break;
        }
    }

    /**
     * Obtiene las pautas del diccionario de aula
     */
    public function pauta()
    {
        return $this->hasOne('App\Models\DicAulaPauta');
    }

    /**
     * Obtiene los campos de la configuración de la entrada
     */
    public function dicAulaCampos()
    {
        // return $this->hasMany('App\Models\DicAulaCampo');
        return $this->hasMany(DicAulaCampo::class, 'dic_aula_id');
    }

    /**
     * Obtiene las pautas del diccionario de aula
     */
    public function destinatarioAvisos()
    {
        return $this->hasOne('App\Models\DicAulaDestinarioAviso');
    }

    /**
     * Obtiene los participantes
     */
    public function participantes()
    {
        return $this->hasMany('App\Models\DicAulaParticipante');
    }

    /**
     * Obtiene el profesor que es dueño del diccionario de aula
     */
    public function profesor()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    /**
     * Obtiene los envios
     */
    public function envios()
    {
        return $this->hasMany(Envio::class, 'dic_aula_id');
    }

    /**
     * Obtiene los envios
     */
    public function atemporal()
    {
        return $this->hasOne('App\Models\DicAulaAtemporal');
    }
    /**
     * Devuelve si es atemporal o no 
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @return Boolean true si es atemporal
     */
    public function esAtemporal()
    {
        if ($this->atemporal){
            return ($this->atemporal->estado == config('ctes.estados.activo'));
        }
        return false;
    }

    public function activarDiccionarioCursoActual()
    {
        if (  getAnoIniCursoEscolar( new Datetime() ) <= $this->ano_ini_curso_escolar + 9){
            return true;
        }
        return false;
    }

    public function setAtemporal( $activar=true ){
        // dd($activar);
        if ($activar){
            $set = config('ctes.estados.activo');
            // activar si esta inactivo 
            $this->estado = $set;
        } else {
            $set = config('ctes.estados.inactivo');
        }

        if( $this->atemporal ) {            
            $this->atemporal->estado = $set;
            $this->atemporal->save();
        } else {
            $atemporal = new DicAulaAtemporal(['estado' => $set ]);
            $this->atemporal()->save($atemporal);
        }
        // $this->save();
        return $this->save();
    }

    /**
     * Obtiene los Comentarios Generales teniendo en cuenta el campo comentarios_visibles
     */
    public function comentariosGeneralesByComentariosVisible($orden = '')
    {
        $comentariosGenerales = new Collection();
        if ($this->comentarios_visibles == config('ctes.comentarios_visibles.no_visible')) {
            // no se muestran los comentarios
            return $comentariosGenerales;
        } else {
            if ($this->comentarios_visibles == config('ctes.comentarios_visibles.visible')) {
                // se muestran todos los comentarios
                switch (strtoupper($orden)) {
                    case 'DESC':
                        return $this->hasMany(ComentarioGeneral::class, 'dic_aula_id')->orderBy('fecha_envio', 'desc');
                        break;

                    case 'ASC':
                        return $this->hasMany(ComentarioGeneral::class, 'dic_aula_id')->orderBy('fecha_envio', 'asc');
                        break;

                    default:
                        return $this->hasMany(ComentarioGeneral::class, 'dic_aula_id');
                        break;
                }
            } else {
                // anteriores_a
                // se muestran los comentarios posteriores a la fecha del campo comentarios_visibles_anteriores_a
                switch (strtoupper($orden)) {
                    case 'DESC':
                        return $this->hasMany(ComentarioGeneral::class, 'dic_aula_id')
                            ->where('fecha_envio', '<', $this->comentarios_visibles_anteriores_a)
                            ->orderBy('fecha_envio', 'desc');
                        break;
                    case 'ASC':
                        return $this->hasMany(ComentarioGeneral::class, 'dic_aula_id')
                            ->where('fecha_envio', '<', $this->comentarios_visibles_anteriores_a)
                            ->orderBy('fecha_envio', 'asc');
                        break;

                    default:
                        return $this->hasMany(ComentarioGeneral::class, 'dic_aula_id')
                            ->where('fecha_envio', '<', $this->comentarios_visibles_anteriores_a);
                        break;
                }
            }
        }
    }

    /**
     * Obtiene los Comentarios Entradas teniendo en cuenta el campo comentarios_visibles
     */
    public function comentariosEntradasByComentariosVisible($orden = '')
    {
        $comentariosEntrada = new Collection();
        if ($this->comentarios_visibles == config('ctes.comentarios_visibles.no_visible')) {
            // no se muestran los comentarios
            return $comentariosEntrada;
        } else {
            if ($this->comentarios_visibles == config('ctes.comentarios_visibles.visible')) {
                // se muestran todos los comentarios
                switch (strtoupper($orden)) {
                    case 'DESC':
                        return $this->hasMany(ComentarioEntrada::class, 'dic_aula_id')->orderBy('fecha_envio', 'desc');
                        break;

                    case 'ASC':
                        return $this->hasMany(ComentarioEntrada::class, 'dic_aula_id')->orderBy('fecha_envio', 'asc');
                        break;

                    default:
                        return $this->hasMany(ComentarioEntrada::class, 'dic_aula_id');
                        break;
                }
            } else {
                // anteriores_a
                // se muestran los comentarios posteriores a la fecha del campo comentarios_visibles_anteriores_a
                switch (strtoupper($orden)) {
                    case 'DESC':
                        return $this->hasMany(ComentarioEntrada::class, 'dic_aula_id')
                            ->where('fecha_envio', '<', $this->comentarios_visibles_anteriores_a)
                            ->orderBy('fecha_envio', 'desc');
                        break;
                    case 'ASC':
                        return $this->hasMany(ComentarioEntrada::class, 'dic_aula_id')
                            ->where('fecha_envio', '<', $this->comentarios_visibles_anteriores_a)
                            ->orderBy('fecha_envio', 'asc');
                        break;

                    default:
                        return $this->hasMany(ComentarioEntrada::class, 'dic_aula_id')
                            ->where('fecha_envio', '<', $this->comentarios_visibles_anteriores_a);
                        break;
                }
            }
        }
    }

    /**
     * Obtiene las envio_entradas
     */
    // public function envioEntradas()
    // {
    //     return $this->belongsToMany(EnvioEntrada::class, ComentarioEntrada::class, 'dic_aula_id', 'envio_entrada_id');
    // }

    /**
     * Datos de dic_aula_entradas
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @return void
     */
    public function dicAulaEntradas()
    {
        return $this->hasMany(DicAulaEntrada::class, 'dic_aula_id');
    }

    /**
     * Deberia ser envioEntradas pero esta cojido arriba para los comentarios
     * ???
     */
    // public function entradasAula()
    // {
    //     DicAulaEntrada::where('dic_aula_id', )
    //     return $this->belongsToMany(
    //         'App\Models\EnvioEntrada', // EnvioEntrada::class, 
    //         'dic_aula_entradas', 
    //         'id', // 'dic_aula_id', 
    //         'envio_entrada_id',
    //     );
    // }
    
    /**
    * Devuelve las personas matriculadas
    *
    * @author  Javier Pérez Batista <javier.perez@altia.es>
    * @version 1.0.0
    *
    * @return void
    */
    public function personas()
    {        
        return $this->HasManyThrough(            
            Persona::class,
            DicAulaParticipante::class,
            'dic_aula_id',
            'id',
            'id',
            'id'
            );
    }

    /**
     * Sobreescribo delete para borrar relacionados
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     *
     * @return result
     */
    public function delete()
    {
        \DB::beginTransaction();
        // Softdelete con sql
        try {
            $ids = "($this->id)";
            $sql__avisos ="UPDATE avisos SET deleted_at=NOW()  WHERE dic_aula_id IN $ids";
            $sql__comentarios_entradas ="UPDATE comentarios_entradas SET deleted_at=NOW()  WHERE dic_aula_id IN $ids";
            $sql__comentarios_generales ="UPDATE comentarios_generales SET deleted_at=NOW()  WHERE dic_aula_id IN $ids";
            $sql__atemporales ="UPDATE dic_aula_atemporales SET deleted_at=NOW()  WHERE dic_aula_id IN $ids";
            $sql__dic_aula_campos ="UPDATE dic_aula_campos SET deleted_at=NOW()  WHERE dic_aula_id IN $ids";
            $sql__dic_aula_destinatario_avisos = "UPDATE dic_aula_destinatario_avisos SET deleted_at=NOW()  WHERE dic_aula_id IN $ids";
            $count_dic_aula_entradas = "SELECT count(*) FROM dic_aula_entradas WHERE dic_aula_id IN $ids";
            $sql__dic_aula_participantes = "UPDATE dic_aula_participantes SET deleted_at=NOW()  WHERE dic_aula_id IN $ids";
            $sql__dp_envios = "UPDATE dp_envios SET deleted_at=NOW()  WHERE dic_aula_id IN $ids";
            $sql__dic_aula_pautas = "UPDATE dic_aula_pautas SET deleted_at=NOW()  WHERE dic_aula_id IN $ids";
            // dd( 
            //     $sql__avisos,
            //     $sql__comentarios_entradas,
            //     $sql__comentarios_generales,
            //     $sql__atemporales,
            //     $sql__dic_aula_campos,
            //     $sql__dic_aula_destinatario_avisos,
            //     $count_dic_aula_entradas,
            //     $sql__dic_aula_participantes,
            //     $sql__dp_envios,
            //     $sql__dic_aula_pautas
            // );
            $st_avisos = \DB::statement($sql__avisos);            
            \DB::statement($sql__comentarios_entradas);
            \DB::statement($sql__comentarios_generales);
            \DB::statement($sql__atemporales);
            \DB::statement($sql__dic_aula_campos);
            \DB::statement($sql__dic_aula_destinatario_avisos);
            \DB::statement($count_dic_aula_entradas);
            \DB::statement($sql__dic_aula_participantes);
            \DB::statement($sql__dp_envios);
            \DB::statement($sql__dic_aula_pautas);

            // dd( 
            //     $sql__avisos,
            //     $st_avisos,
            //     $sql__comentarios_entradas,
            //     $sql__comentarios_generales,
            //     $sql__atemporales,
            //     $sql__dic_aula_campos,
            //     $sql__dic_aula_destinatario_avisos,
            //     $count_dic_aula_entradas,
            //     $sql__dic_aula_participantes,
            //     $sql__dp_envios,
            //     $sql__dic_aula_pautas
            // );

            $sql = "UPDATE dic_aula set deleted_at=now() where id=" . $this->id;
            $st_sql = \DB::statement($sql);
            // dd($sql, $st_sql);
            \DB::commit();
            return $st_sql;
        } catch (\Throwable $th) {

            // dd($th->getMessage(), $th, 'delete');

            \DB::rollBack();
            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.data_base_error'),
                ['file' => $th->getFile(), 'line' => $th->getLine()],
                PHP_EOL . 'BORRAR dic_aula_id: ' .$this->id ,
                // PHP_EOL . 'sql: ' .$sql,
                $th->getMessage()
            );
            
        }
        
        // $this->estado = config('ctes.estados.marcado_borrar');
        // \DB::beginTransaction();
        // try {
        //     // dd($result);
        //     $this->participantes()->delete();
        //     $this->destinatarioAvisos()->delete();            
        //     // $this->comentariosGeneralesByComentariosVisible()->delete();
        //     // $this->comentariosEntradasByComentariosVisible()->delete();
        //     // $this->dicAulaEntradas()->delete();

        //     //  belongs to many
        //     // envioEntradas()

        //     $envios = $this->envios()->get();
        //     foreach ($envios as $e) {
        //         $e->borrar();
        //     }
            
        //     $this->dicAulaCampos()->delete();
        //     // $result = DicAulaCampo::where('dic_aula_id',$this->id)->delete();
        //     // $result = DicAulaDestinarioAviso::where('dic_aula_id',$this->id)->delete();
        //     $this->destinatarioAvisos()->delete();

        //     // $campos = $this->dicAulaCampos()->get();
        //     // foreach ($campos as $c) {
        //     //     $c->delete();
        //     // }
        //     $this->pauta()->delete();

        //     $result = parent::delete();

        //     \DB::commit();
        //     return $result;
        // } catch (\Throwable $th) {
        //     \DB::rollBack();
        //     throw $th;
        // }
    }


}
