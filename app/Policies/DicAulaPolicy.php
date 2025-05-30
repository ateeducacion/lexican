<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Models\DicAula;
use TCG\Voyager\Models\Role;

class DicAulaPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function isOwner(User $user, DicAula $dicAula)
    {
        $persona_id = $user->userPersona->id;
        return $persona_id == $dicAula->persona_id;
    }

    /**
     * Comprueba si el usuario acutula es docente , llamar en el controlador de
     * esta manera:
     *   $this->authorize( 'esDocente', DicAula::class );
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param User $user
     *
     * @return boolean devuelve true si el usuario es docente
     */
    public function esDocente(User $user)
    {
        // dd( 
        //     '$user()->role_id', $user->role_id,
        //     'config ctes.rol.docente', config('ctes.rol.docente')
        // );
        $esDocente = ( $user->role_id == config('ctes.rol.docente'));
        // dd( $esDocente );
        return $esDocente;
    }

    public function esAdministrador(User $user)
    {
        $esAdministrador = $user()->role()->first()->name == 'admin';
        return $esAdministrador;
    }

    /**
     * Comprueba si el usuario actual puede crear diccionarios de aula
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @return void
     */
    public function createDicAula()
    {        
        $esDocente = (auth()->user()->role_id == Role::where('name', 'docente')->first()->id);
        
        return $esDocente;
    }

    /**
     * Comprueba si el usuario puede editar el diccionario de aula
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param DicAula $dic Diccionario de aula que se quiere editar
     *
     * @return boolean
     */
    public function canEditDic( User $user, DicAula $dic )
    {
        $rolname = Role::where('id',$user->role_id)->get()[0]->name;
        if  ( $rolname )
            return ( $rolname == 'docente');
        else 
            return false;
        
        // $roles = array_map( 
        //     function($v) { return $v->name; },
        //     getSessionRoles() 
        // );
        // if ( in_array('docente', $roles) ) {
        //     return true;
        // } else 
        //     return false;
    }

    /**
     * Compureba que el usuario actual es coordinador y permite o bloquea el acceso
     * a la pagina 
     *
     * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param User $user
     *
     * @return boolean true si es coordinador
     */
    public function esCoordinador( User $user, DicAula $dicAula )
    {
        // dd($user,$dicAula);
        // dd($user->userPersona->id);
        $coordinadores = $dicAula->participantes()->where('rol_diccionario_id',config('ctes.rol.docente'));

        $items = $coordinadores->where('persona_id', $user->userPersona->id)->get();
        // dd( 
        //     'rol_diccionario_id', $items[0]->rol_diccionario_id, 
        //     'config(ctes.rol.docente)', config('ctes.rol.docente')
        // );
        return ( $items && $items->count()>0 );
    }

    /**
     * Comprueba si el usuario actual es participante del diccionario de aula
     *
     * @param User $user
     * @param DicAula $dicAula
     * @return boolean
     */
    public function esParticipante( User $user, DicAula $dicAula )
    {
        $participantes = $dicAula->participantes();
        $items = $participantes->where('persona_id', $user->userPersona->id)->get();

        return ( $items && $items->count()>0 );
    }
}
