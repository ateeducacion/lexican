<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

    use Illuminate\Support\Facades\Config;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Schema::defaultStringLength(191); 
        if ($this->app->environment('local')) {
            Config::set('ctes.rol.docente', '3');
            Config::set('ctes.rol.alumno', '4');
        }        
        if(
            $this->app->environment('production') || 
            $this->app->environment('preproduction') || 
            $this->app->environment('develop')) {
        URL::forceScheme('https');
        }
    }
}
