<?php

namespace Database\Seeders;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Seeder;
use TCG\Voyager\Models\DataType;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(MasterTablesDataSeeder::class); 
        $this->call(UsersRolesTablesSeeder::class); // crea roles docente, alumno y usuarios de prueba para los mismos        
        // $this->call(DicPersonalTableSeeder::class); // genera datos de prueba para el dic personal
        $this->call(MstPautasSeeder::class);
        
        // autogenerado por voyager
        $this->call(DataRowsTableSeeder::class);
        $this->call(DataTypesTableSeeder::class);
        $this->call(MenusTableSeeder::class);  // crea menu "admin"
        $this->call(MenuItemsTableSeeder::class); // items del menu del dashboard
        $this->call(CategoriesTableSeeder::class); 
        $this->call(PagesTableSeeder::class);
        $this->call(RolesTableSeeder::class); // crea roles admin y user de voyager
        $this->call(UsersTableSeeder::class); // crea usuario admin 
        $this->call(PermissionRoleTableSeeder::class); // le da permisos al usuario con rol admin
        $this->call(PermissionsTableSeeder::class);
        $this->call(PostsTableSeeder::class);
        $this->call(SettingsTableSeeder::class);        
        $this->call(TranslationsTableSeeder::class);
        $this->call(VoyagerDatabaseSeeder::class);
        $this->call(VoyagerDummyDatabaseSeeder::class);
        $this->call(VoyagerCustomization::class);
        // fin autogenerado por voyager
        
    }
}
