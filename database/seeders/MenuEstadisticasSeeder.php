<?php

namespace Database\Seeders;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Seeder;
use TCG\Voyager\Models\DataType;

class MenuEstadisticasSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // llama a los seeder que recrean el menu, borra el actual
        // el orden es importante
        $this->call(DataTypesTableSeeder::class); 
        $this->call(DataRowsTableSeeder::class);
        $this->call(MenusTableSeeder::class);      
        $this->call(MenuItemsTableSeeder::class); 
    }
}
