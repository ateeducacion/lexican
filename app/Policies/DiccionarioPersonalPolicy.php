<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Models\DiccionarioPersonalEntrada;

class DiccionarioPersonalPolicy
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

    public function isOwner(User $user, DiccionarioPersonalEntrada $entrada)
    {
        $persona_id = $user->userPersona->id;
        return $persona_id == $entrada->dpDiccionario()->first()->persona_id;
    }
}
