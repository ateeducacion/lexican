<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DataTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('data_types')->delete();
        
        \DB::table('data_types')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'users',
                'slug' => 'users',
                'display_name_singular' => 'Usuario',
                'display_name_plural' => 'Usuarios',
                'icon' => 'voyager-person',
                'model_name' => 'TCG\\Voyager\\Models\\User',
                'policy_name' => 'TCG\\Voyager\\Policies\\UserPolicy',
                'controller' => 'TCG\\Voyager\\Http\\Controllers\\VoyagerUserController',
                'description' => '',
                'generate_permissions' => 1,
                'server_side' => 0,
                'details' => 'null',
                'created_at' => '2022-11-04 09:41:08',
                'updated_at' => '2022-11-04 09:41:08',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'menus',
                'slug' => 'menus',
                'display_name_singular' => 'Menú',
                'display_name_plural' => 'Menús',
                'icon' => 'voyager-list',
                'model_name' => 'TCG\\Voyager\\Models\\Menu',
                'policy_name' => NULL,
                'controller' => '',
                'description' => '',
                'generate_permissions' => 1,
                'server_side' => 0,
                'details' => 'null',
                'created_at' => '2022-11-04 09:41:08',
                'updated_at' => '2022-11-04 09:41:08',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'roles',
                'slug' => 'roles',
                'display_name_singular' => 'Rol',
                'display_name_plural' => 'Roles',
                'icon' => 'voyager-lock',
                'model_name' => 'TCG\\Voyager\\Models\\Role',
                'policy_name' => NULL,
                'controller' => '',
                'description' => '',
                'generate_permissions' => 1,
                'server_side' => 0,
                'details' => 'null',
                'created_at' => '2022-11-04 09:41:08',
                'updated_at' => '2022-11-04 09:41:08',
            ),
            3 => 
            array (
                'id' => 13,
                'name' => 'audits',
                'slug' => 'audits',
                'display_name_singular' => 'Audit',
                'display_name_plural' => 'Audits',
                'icon' => 'voyager-search',
                'model_name' => 'OwenIt\\Auditing\\Models\\Audit',
                'policy_name' => NULL,
                'controller' => NULL,
                'description' => NULL,
                'generate_permissions' => 1,
                'server_side' => 1,
                'details' => '{ 
                    "order_column": "id", 
                    "order_display_column":null, 
                    "order_direction": "desc", 
                    "default_search_key":null} ',
                'created_at' => '2022-11-04 09:41:08',
                'updated_at' => '2022-11-04 09:41:08',
            ),
            4 => 
            array (
                'id' => 14,
                'name' => 'categories',
                'slug' => 'categories',
                'display_name_singular' => 'Categoría',
                'display_name_plural' => 'Categorías',
                'icon' => 'voyager-categories',
                'model_name' => 'TCG\\Voyager\\Models\\Category',
                'policy_name' => NULL,
                'controller' => NULL,
                'description' => NULL,
                'generate_permissions' => 1,
                'server_side' => 0,
                'details' => '{"order_column":null,"order_display_column":null,"order_direction":"desc","default_search_key":null,"scope":null}',
                'created_at' => '2022-11-04 10:13:08',
                'updated_at' => '2022-11-14 13:16:28',
            ),
            5 => 
            array (
                'id' => 15,
                'name' => 'posts',
                'slug' => 'posts',
                'display_name_singular' => 'Post',
                'display_name_plural' => 'Posts',
                'icon' => 'voyager-news',
                'model_name' => 'TCG\\Voyager\\Models\\Post',
                'policy_name' => 'TCG\\Voyager\\Policies\\PostPolicy',
                'controller' => '',
                'description' => '',
                'generate_permissions' => 1,
                'server_side' => 0,
                'details' => NULL,
                'created_at' => '2022-11-04 10:13:08',
                'updated_at' => '2022-11-04 10:13:08',
            ),
            6 => 
            array (
                'id' => 16,
                'name' => 'pages',
                'slug' => 'pages',
                'display_name_singular' => 'Página',
                'display_name_plural' => 'Páginas',
                'icon' => 'voyager-file-text',
                'model_name' => 'TCG\\Voyager\\Models\\Page',
                'policy_name' => NULL,
                'controller' => '',
                'description' => '',
                'generate_permissions' => 1,
                'server_side' => 0,
                'details' => NULL,
                'created_at' => '2022-11-04 10:13:08',
                'updated_at' => '2022-11-04 10:13:08',
            ),
            7 => 
            array (
                'id' => 24,
                'name' => 'dic_aula',
                'slug' => 'dic-aulaCursoEscolar',
                'display_name_singular' => 'Dic Aula Curso Escolar',
                'display_name_plural' => 'Dic Aulas Cursos Escolares',
                'icon' => 'voyager-bread',
                'model_name' => 'App\\Models\\DicAula',
                'policy_name' => NULL,
                'controller' => 'App\\Http\\Controllers\\Voyager\\vDicAulaController',
                'description' => NULL,
                'generate_permissions' => 1,
                'server_side' => 1,
                'details' => '{"order_column":"ano_ini_curso_escolar","order_display_column":"ano_ini_curso_escolar","order_direction":"asc","default_search_key":"id","scope":null}',
                'created_at' => '2022-11-15 08:58:39',
                'updated_at' => '2022-11-15 15:54:17',
            ),
            8 => 
            array (
                'id' => 26,
                'name' => 'dic_personal',
                'slug' => 'dic-personal-bread',
                'display_name_singular' => 'Diccionario personal',
                'display_name_plural' => 'Diccionarios personales',
                'icon' => NULL,
                'model_name' => 'App\\Models\\DiccionarioPersonal',
                'policy_name' => NULL,
                'controller' => 'App\\Http\\Controllers\\Voyager\\vDicPersonalController',
                'description' => NULL,
                'generate_permissions' => 1,
                'server_side' => 1,
                'details' => '{"order_column":null,"order_display_column":null,"order_direction":"asc","default_search_key":null,"scope":null}',
                'created_at' => '2022-11-16 13:02:44',
                'updated_at' => '2022-11-16 13:16:23',
            ),
            9 => 
            array (
                'id' => 30,
                'name' => 'centros',
                'slug' => 'centrosBread',
                'display_name_singular' => 'Centros curso escolar',
                'display_name_plural' => 'Centros curso escolar',
                'icon' => NULL,
                'model_name' => 'App\\Models\\Centro',
                'policy_name' => NULL,
                'controller' => 'App\\Http\\Controllers\\Voyager\\vCentrosAñoEscolarController',
                'description' => NULL,
                'generate_permissions' => 1,
                'server_side' => 1,
                'details' => '{"order_column":null,"order_display_column":null,"order_direction":"asc","default_search_key":null}',
                'created_at' => '2022-11-28 12:31:41',
                'updated_at' => '2022-11-28 12:31:41',
            ),
        ));
    }
}