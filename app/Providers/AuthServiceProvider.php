<?php

namespace App\Providers;

use App\Models\ComentarioGeneral;
use App\Models\DiccionarioPersonalEntrada;
use App\Models\DicAula;
use App\Policies\ComentarioGeneralPolicy;
use App\Policies\DiccionarioPersonalPolicy;
use App\Policies\DicAulaPolicy;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',

        // Policies definidas para el diccionario
        DicAula::class => DicAulaPolicy::class,
        DiccionarioPersonalEntrada::class => DiccionarioPersonalPolicy::class,
        ComentarioGeneral::class => ComentarioGeneralPolicy::class,

    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Gates definidos para el diccionario
        // $gate->define('acepcion-edit');
    }
}
