<?php

use App\Models\ComentarioEntrada;
use App\Models\ComentarioGeneral;
use App\Models\DicAula;
use App\Models\DiccionarioPersonal;
use Illuminate\Database\Eloquent\Collection;

// ********************************************************************************************  DICCIONARIO

if (!function_exists('daGetDiccionariosAulaComentariosByUserConectado')) {
    /**
     * Devuelve un lista de diccionarios de aula conectados mejorados con la lista de comentarios
     *  Estudio los diccionarios activos, y los pongo como inactivos si 
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $diccionario DiccionarioPersonal
     * 
     * @return array $listaDiccionariosAula DicAula
     */
    function daGetDiccionariosAulaComentariosByUserConectado($diccionario)
    {
        $listaDiccionariosAula = daGetDiccionariosAulaByUserConectado();

        foreach ($listaDiccionariosAula as $diccionarioAula) {

            // Marco el diccionario como activo si tiene algún comentario general o de entrada, si no lo tiene lo marco como inactivo
            if ($diccionarioAula->comentariosEntradasByComentariosVisible('desc')->count() > 0) {
                // Si el diccionario tiene comentarios de entradas se pone como Activo
                $diccionarioAula->estadoActual = config('ctes.estados.activo');
            } else {
                // Si el diccionario NO tiene comentarios de entradas se pone como Inactivo
                $diccionarioAula->estadoActual = config('ctes.estados.inactivo');
            }
            if ($diccionarioAula->comentariosGeneralesByComentariosVisible('desc')->count() > 0) {
                // Si el diccionario tiene comentarios generales se pone como Activo tenga o no comentarios de entrada
                $diccionarioAula->estadoActual = config('ctes.estados.activo');
            }
        }

        return $listaDiccionariosAula;
    }
}

if (!function_exists('daGetComentariosEntradasByEntradaByComentariosVisible')) {
    /**
     * Devuelve un lista de comentarios de aula ordeados por entrada y fecha DESC para una entrada teniendo en cuenta el campo comentarios_visibles
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $entrada DiccionarioPersonalEntrada
     * 
     * @return array $listaComentarioEntrada ComentarioEntrada
     */
    function daGetComentariosEntradasByEntradaByComentariosVisible($entrada)
    {
        $listaComentarioEntrada = ComentarioEntrada::whereHas('envioEntrada', function ($query) use ($entrada) {
            $query
                ->where('dp_entrada_id', '=', $entrada->id);
        })->whereHas('dicAula', function ($query) {
            $query
                ->where('comentarios_visibles', '=', config('ctes.comentarios_visibles.visible'))
                ->orWhere('comentarios_visibles', '=', config('ctes.comentarios_visibles.anteriores_a'))
                ->whereColumn('fecha_envio', '<', 'comentarios_visibles_anteriores_a');
        })
            ->orderBy('fecha_envio', 'desc')
            ->get();

        return $listaComentarioEntrada;
    }
}

if (!function_exists('daGetComentariosEntradasByComentariosVisible')) {
    /**
     * Devuelve una lista de comentarios de aula ordeados por entrada y fecha DESC teniendo en cuenta el campo comentarios_visibles
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @return array $listaComentarioEntrada ComentarioEntrada
     */
    function daGetComentariosEntradasByComentariosVisible()
    {
        $listaComentarioEntrada = ComentarioEntrada::orWhereHas('dicAula', function ($query) {
            $query
                ->where('comentarios_visibles', '=', config('ctes.comentarios_visibles.visible'));
        })->orWhereHas('dicAula', function ($query) {
            $query
                ->where('comentarios_visibles', '=', config('ctes.comentarios_visibles.anteriores_a'))
                ->whereColumn('fecha_envio', '<', 'comentarios_visibles_anteriores_a');
        })
            ->orderBy('fecha_envio', 'desc')
            ->get();


        return $listaComentarioEntrada;
    }
}

if (!function_exists('daGetComentariosGeneralesByComentariosVisible')) {
    /**
     * Devuelve una lista de comentarios de aula ordeados fecha DESC teniendo en cuenta el campo comentarios_visibles
     * ESTO DEVUELVE TODOS LOS COMENTARIOS PARA TODOS LOS DICCIONARIOS y TODOS LOS PARTICIPANTES
     * 
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @return array $listaComentarioGeneral ComentarioGeneral     
     */
    function daGetComentariosGeneralesByComentariosVisible()
    {
        $listaComentarioGeneral = ComentarioGeneral::orWhereHas('dicAula', function ($query) {
            $query
                ->where('comentarios_visibles', '=', config('ctes.comentarios_visibles.visible'));
        })->orWhereHas('dicAula', function ($query) {
            $query
                ->where('comentarios_visibles', '=', config('ctes.comentarios_visibles.anteriores_a'))
                ->whereColumn('fecha_envio', '<', 'comentarios_visibles_anteriores_a');
        })
            ->orderBy('fecha_envio', 'desc')
            ->get();


        return $listaComentarioGeneral;
    }
}

if (!function_exists('daGetComentariosGeneralesAlumno')) {
    /**
     * Devuelve una lista de comentarios de aula ordeados fecha DESC teniendo en cuenta el campo comentarios_visibles
     * Y dirigidos a un alunmno concreto en lugar de todos
     * 
     * TODO: NO MUESTRA LOS COMENTRARIOS ACTUALES HE SUSTITUIDO ESTA POR  daGetComentariosGeneralesAlumnoAula 
     * por ahora 
     *
     * @author fernando.ramirez@altia.es
     * @version 1.0.0
     * 
     * @return array $listaComentarioGeneral ComentarioGeneral
     */
    function daGetComentariosGeneralesAlumno( $alumno )
    {        
        /// diccionarios de aula donde particicpe el alumno 
        $dicionarios =daGetDiccionariosAulaConectadosByPersonaId($alumno['id']);
        $comentariosDicAula = ComentarioGeneral::whereIn( 'dic_aula_id', $dicionarios->pluck('id')->toArray() );
        $listaComentarios = new Collection();
        foreach ( $dicionarios as $dicAula ) {
            // dicionario personal activo del usuario 
            $dicPersonal = DiccionarioPersonal::where('persona_id', $alumno['id'])->orderBy('id','desc')->get()[0];
            $comentsCur = null;
            // si no exite el dic personla devuelve coleccion vacia
            if(is_null($dicPersonal)) return new Collection();

            // DB::enableQueryLog(); // Enable query log
            if ( $dicAula->comentarios_visibles == config('ctes.comentarios_visibles.visible') ){
                $comentariosCurDic = ComentarioGeneral::where(
                    [['dic_aula_id', $dicAula->id],
                    ['dic_personal_id', $dicPersonal->id]]
                );
            }

            // comentarios visibles apartir de fecha 
            if (
                $dicAula->comentarios_visibles == config('ctes.comentarios_visibles.anteriores_a')            
            ) {
                $fecha_visibles_anteriores = $dicAula->comentarios_visibles_anteriores_a;

                $comentariosCurDic = ComentarioGeneral::where(
                    [['dic_aula_id', $dicAula->id],
                    ['dic_personal_id', $dicPersonal->id],
                    ['fecha_envio', '<', $fecha_visibles_anteriores]]
                );
                $comentsCur = $comentariosCurDic->orderBy('fecha_envio','desc')->get();
            }
            if ( $comentsCur )            
                $listaComentarios = $listaComentarios->merge( $comentsCur );
            // if ( $dicAula->id == 1 ) dd($listaComentarios);
        }

        if( isset($listaComentarios) ) {
            return $listaComentarios;
            // $listaComentarioGeneral->orderBy('fecha_envio', 'desc')->get();
        } else {
            // devuelve una collection vacia
            return new Collection();
        }
    }
}

if (!function_exists('daGetComentariosGeneralesAlumnoAula')) {
    /**
     * Devuelve una lista de comentarios de aula ordeados fecha DESC teniendo en cuenta el campo comentarios_visibles
     * Y dirigidos a un alunmno concreto en lugar de todos
     *
     * @author fernando.ramirez@altia.es
     * @version 1.0.0
     * 
     * @return array $listaComentarioGeneral ComentarioGeneral
     */
    function daGetComentariosGeneralesAlumnoAula( $alumno , $dicAula)
    {
        
        // Si es el diccionario de la session viene con los datos sin actualizar!!!
        // Aqui cargo el estado actual de la bbdd
        if (is_null($dicAula)) return new Collection();
        $dicAula = DicAula::find($dicAula->id);

        // dicionario personal activo del usuario 
        $dicPersonal = DiccionarioPersonal::where('persona_id', $alumno['id'])->orderBy('id','desc')->get()[0];
        // si no exite el dic personla devuelve coleccion vacia
        if(is_null($dicPersonal)) return new Collection();

        // DB::enableQueryLog(); // Enable query log
        if ( $dicAula->comentarios_visibles == config('ctes.comentarios_visibles.visible') ){
            $listaComentarioGeneral = ComentarioGeneral::where(
                [['dic_aula_id', $dicAula->id],
                ['dic_personal_id', $dicPersonal->id]]
            );
        }

        // comentarios visibles apartir de fecha 
        if (
            $dicAula->comentarios_visibles == config('ctes.comentarios_visibles.anteriores_a')            
        ) {
            $fecha_visibles_anteriores = $dicAula->comentarios_visibles_anteriores_a;

            $listaComentarioGeneral = ComentarioGeneral::where(
                [['dic_aula_id', $dicAula->id],
                ['dic_personal_id', $dicPersonal->id],
                ['fecha_envio', '<', $fecha_visibles_anteriores]]
            );
        }

        if( isset($listaComentarioGeneral) ) {
            return $listaComentarioGeneral->orderBy('fecha_envio', 'desc')->get();
        } else {
            // devuelve una collection vacia
            return new Collection();
        }
    }
}

if (!function_exists('daGetComentariosEntradasAlumno')) {
    /**
     * Comentarios de entradas del alumno
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param [Persona] $alumno alumno
     * @param [DicAula] $dicAula Diccionario de Aula 
     *
     * @return Collection de comentarios
     */
    function daGetComentariosEntradasAlumno( $alumno )
    {
        /// diccionarios de aula donde particicpe el alumno 
        $dicionarios =daGetDiccionariosAulaConectadosByPersonaId($alumno['id']);
        // dd($dicionarios);
        // $comentariosDicAula = ComentarioEntrada::where('dic_aula_id', 'in',$diccionario->pluck('id')->toArray());
        $comentariosDicAula = ComentarioEntrada::whereIn( 'dic_aula_id', $dicionarios->pluck('id')->toArray() );
        // comprueba que el diccionario tenga visibles los comentarios:
        
        $listaComentarios = new Collection();
        // DB::enableQueryLog(); // Enable query log        
        foreach ( $dicionarios as $dicAula ) {
            $comentsCur = null;
            $comentariosCurDic = null;
            
            if ( $dicAula->comentarios_visibles == config('ctes.comentarios_visibles.visible') ){
                // Comentarios que sean dirigidos al usuario actual
                $comentariosCurDic = $comentariosDicAula->whereHas('envioEntrada.dpPersonal.persona', 
                    function($q) use ($alumno){
                        $q->where('id',$alumno['id']); 
                    });                
            }

            // comentarios visibles apartir de fecha 
            if ( $dicAula->comentarios_visibles == config('ctes.comentarios_visibles.anteriores_a') ) {
                $fecha_visibles_anteriores = $dicAula->comentarios_visibles_anteriores_a;

                $comentariosCurDic = $comentariosDicAula->whereHas('envioEntrada.dpPersonal.persona', 
                    function($q) use ($alumno){
                        $q->where('id',$alumno['id']); 
                    })->where('fecha_envio','<', $fecha_visibles_anteriores);
            }
            
            // return $comentariosCurDic->sortByDesc('fecha_envio');
            // $preMerge =  $listaComentarios;
            if ( $comentariosCurDic ) {
                $comentsCur = $comentariosCurDic->orderBy('fecha_envio','desc')->get();
                $listaComentarios = $listaComentarios->merge( $comentsCur );
            }

        }
        // dd(DB::getQueryLog());


        if( isset($listaComentarios) ) {
            // return $listaComentarios->get();
            return $listaComentarios
                // ->sortByDesc('fecha_envio')
                // ->get()
                ;
            // ->orderBy('fecha_envio', 'desc')->get();
        } else {
            // devuelve una collection vacia
            return new Collection();
        }
    }
}

if (!function_exists('daGetComentariosEntradasAlumnoAula')) {
    /**
     * Comentarios de entradas del alumno
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param [Persona] $alumno alumno
     * @param [DicAula] $dicAula Diccionario de Aula 
     *
     * @return Collection de comentarios
     */
    function daGetComentariosEntradasAlumnoAula( $alumno, $dicAula )
    {
        if (is_null($dicAula)) return new Collection();
        $dicAula = DicAula::find($dicAula->id);
        $comentariosDicAula = ComentarioEntrada::where('dic_aula_id', $dicAula->id);    

        // comprueba que el diccionario tenga visibles los comentarios:
        // dd($dicAula);
        if ( $dicAula->comentarios_visibles == config('ctes.comentarios_visibles.visible') ){
            // Comentarios que sean dirigidos al usuario actual
            $listaComentarios = $comentariosDicAula->whereHas('envioEntrada.dpPersonal.persona', 
            function($q) use ($alumno){
                 $q->where('id',$alumno['id']); 
            });
            
        }

        // comentarios visibles apartir de fecha 
        if (
            $dicAula->comentarios_visibles == config('ctes.comentarios_visibles.anteriores_a')
        ) {
            $fecha_visibles_anteriores = $dicAula->comentarios_visibles_anteriores_a;

            $listaComentarios = $comentariosDicAula->whereHas('envioEntrada.dpPersonal.persona', 
            function($q) use ($alumno){
                $q->where('id',$alumno['id']); 
            })->where('fecha_envio','<', $fecha_visibles_anteriores);
        }

        if( isset($listaComentarios) ) {
            return $listaComentarios->orderBy('fecha_envio', 'desc')->get();
        } else {
            // devuelve una collection vacia
            return new Collection();
        }
    }
}

if (!function_exists('daGetComentariosGeneralesProfesorByDicAula')) {
    /**
     * Devuelve una lista de comentarios de aula ordeados fecha DESC teniendo en cuenta el campo comentarios_visibles
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @return array $listaComentarioGeneral ComentarioGeneral
     */
    function daGetComentariosGeneralesProfesorByDicAula($dicAulaId)
    {
        $listaComentarioGeneral = ComentarioGeneral::where('dic_aula_id', '=', $dicAulaId)
            ->orderBy('fecha_envio', 'desc')
            ->get();

        return $listaComentarioGeneral;
    }
}



if (!function_exists('daComentarios_DeleteComentarioGeneralById')) {
    /**
     * Borra el comentario general con el id recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $comentario_id
     * 
     * @return void
     */
    function daComentarios_DeleteComentarioGeneralById($comentario_id)
    {
        ComentarioGeneral::destroy($comentario_id);
    }
}

/**
 * Helpers para Comentarios Controller
 * Prueba de falsa clase para documentar 
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 
class ComentariosHelper
{
    */
    /**
     * Devuelve un lista de diccionarios de aula conectados mejorados con la lista de comentarios
     *  Estudio los diccionarios activos, y los pongo como inactivos si 
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $diccionario DiccionarioPersonal
     * 
     * @return array $listaDiccionariosAula DicAula
     */
    // function daGetDiccionariosAulaComentariosByUserConectado($diccionario){}
    /**
     * Devuelve un lista de comentarios de aula ordeados por entrada y fecha DESC para una entrada teniendo en cuenta el campo comentarios_visibles
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param object $entrada DiccionarioPersonalEntrada
     * 
     * @return array $listaComentarioEntrada ComentarioEntrada
     */
    // function daGetComentariosEntradasByEntradaByComentariosVisible($entrada) {}
    /**
     * Devuelve una lista de comentarios de aula ordeados fecha DESC teniendo en cuenta el campo comentarios_visibles
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @return array $listaComentarioGeneral ComentarioGeneral
     */
    // function daGetComentariosGeneralesByComentariosVisible() {}
    /**
     * Devuelve una lista de comentarios de aula ordeados fecha DESC teniendo en cuenta el campo comentarios_visibles
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @return array $listaComentarioGeneral ComentarioGeneral
     */
    // function daGetComentariosGeneralesProfesorByDicAula($dicAulaId) {}
    /**
     * Borra el comentario general con el id recibido por parámetro
     *
     * @author julio.buenadicha@altia.es
     * @version 1.0.0
     * 
     * @param integer $comentario_id
     * 
     * @return void
     */
    // public function daComentarios_DeleteComentarioGeneralById($comentario_id) { }
// }
