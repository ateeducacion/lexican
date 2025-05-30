<?php

namespace App\Cas;

if ( ! function_exists('cas')) {
    function cas()
    {
        return app('cas');
    }
}
