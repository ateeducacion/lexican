<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Models\ComentarioGeneral;

class ComentarioGeneralPolicy
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

    public function isOwner(User $user, ComentarioGeneral $comentarioGeneral)
    {
        $persona_id = $user->userPersona->id;
        return $persona_id == $comentarioGeneral->persona_id;
    }
}
