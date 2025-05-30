<?php

namespace Database\Seeders;

use App\Models\CampoEntrada;
use Illuminate\Database\Seeder;

class NuevoCampoEjemploSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        CampoEntrada::firstOrCreate(
            [
                'nombre_campo' => 'Ejemplo de uso'
            ],
            [
                'estado'        => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

    }
}
