<?php

namespace Database\Seeders;

use App\Models\Pautas;
use Illuminate\Database\Seeder;

class MstPautasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // borramos roles anteriores
        // DB::table('pautas')->truncate();

        Pautas::firstOrCreate([
            'texto' => 'Esto son las pautas generales desde la tabla maestra de pautas',
        ]);
    }
}
