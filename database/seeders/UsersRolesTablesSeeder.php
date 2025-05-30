<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Role;
use TCG\Voyager\Models\User;
use App\Models\Persona;
use App\Models\UserCentro;
use App\Models\Centro;
use App\Models\UserPersona;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsersRolesTablesSeeder extends Seeder
{

    // protected $toTruncate = ['roles','users','personas','users_personas','centros'];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Truncate no permitido por FK contrains
        // DB::table('roles')->truncate();
        // DB::table('users')->truncate();        
        // DB::table('personas')->truncate(); // Persona
        // DB::table('users_personas')->truncate(); // UserPersona
        // DB::table('centros')->truncate(); // Centro
        
        // borramos datos anteriores
        /*TRUNCATE TABLES*/ 
        // DB::table('roles')->delete();
        // DB::table('users')->delete();        
        // DB::table('personas')->delete(); // Persona
        // DB::table('users_personas')->delete(); // UserPersona
        // DB::table('centros')->delete(); // CentroDB::table('users')->delete();
        //  me dice lo mismo no se puede por la fk con dato
        // DB::statement('DELETE FROM roles');
        // DB::statement('DELETE FROM users');
        // DB::statement('DELETE FROM personas');
        // DB::statement('DELETE FROM users_personas');
        // DB::statement('DELETE FROM centros');

        // // /*RESET AUTOINCREMENTS*/ 
        // DB::statement('ALTER TABLE roles  AUTO_INCREMENT = 1');
        // DB::statement('ALTER TABLE users  AUTO_INCREMENT = 1');
        // DB::statement('ALTER TABLE personas  AUTO_INCREMENT = 1');
        // DB::statement('ALTER TABLE users_personas  AUTO_INCREMENT = 1');
        // DB::statement('ALTER TABLE centros  AUTO_INCREMENT = 1');

        // Creación de roles
        $rolDocente = Role::firstOrNew(
            ['name' => 'docente'],
            ['display_name' => 'Docente']
        );
        $rolDocente->save();
        $rolAlumno = Role::firstOrNew(
            ['name' => 'alumno'],
            ['display_name' => 'Alumno']
        );
        $rolAlumno->save();

        // Creación de users
        $userA = User::firstOrCreate(
            ['email' => 'docente@docente.com'],
            [
                'name' => 'Docente',
                'password' => bcrypt('BFLC^p^%UTmd3^n6'),
                'remember_token' => Str::random(60),
                'role_id' => $rolDocente->id,
            ]
        );
        $userA->save();
        $userB = User::firstOrCreate(
            ['email' => 'alumno@alumno.com'],
            [
                'name' => 'Alumno',
                'password' => bcrypt('oB33b#~7hL2YDnRQ'),
                'remember_token' => Str::random(60),
                'role_id' => $rolAlumno->id,
            ]
        );
        $userB->save();
        $userC = User::firstOrCreate(
            ['email' => 'daniel.matalobos@altia.es'],
            [
                'name' => '44837453C',
                'password' => bcrypt('oB33b#~7hL2YDnRQ'),
                'remember_token' => Str::random(60),
                'role_id' => $rolDocente->id,
            ]
        );
        $userC->save();
        $admin_role = Role::where('name', 'admin')->firstOrFail();
        $userD = User::firstOrCreate(
            ['email' => 'antonio.fernandez.cabezas@altia.es'],
            [
                'name' => '36113886E',
                'password' => bcrypt('Mc7FpXXs95CcGPr3WXww'),
                'remember_token' => Str::random(60),
                'role_id' => $admin_role->id,
            ]
        );
        $userD->save();

        // Creación de personas
        $personaA = Persona::firstOrCreate(
            ['NIF_NIE' => '04245458A'],
            [
                'nombre' => 'Docente',
                'apellidos' => 'Docente A',
                'estado' => 1,
                'avatar_URL' => 'default.svg'
            ]
        );
        $personaA->save();
        $personaB = Persona::firstOrCreate(
            ['NIF_NIE' => '24415425M'],
            [
                'nombre' => 'Alumno',
                'apellidos' => 'Alumno A',
                'estado' => 1,
                'avatar_URL' => 'default.svg'
            ]
        );
        $personaB->save();
        $personaC = Persona::firstOrCreate(
            ['NIF_NIE' => '44837453B'],
            [
                'nombre' => 'Docente',
                'apellidos' => 'Docente B',
                'estado' => 1,
                'avatar_URL' => 'default.svg'
            ]
        );
        $personaC->save();
        $personaD = Persona::firstOrCreate(
            ['NIF_NIE' => '36113886E'],
            [
                'nombre' => 'Alumno',
                'apellidos' => 'Alumno B',
                'estado' => 1,
                'avatar_URL' => 'default.svg'
            ]
        );
        $personaD->save();

        // Creación de users_personas
        $userPersonaA = UserPersona::firstOrCreate(
            [
                'user_id' => $userA->id,
                'persona_id' => $personaA->id
            ]
        );
        $userPersonaA->save();
        $userPersonaB = UserPersona::firstOrCreate(
            [
                'user_id' => $userB->id,
                'persona_id' => $personaB->id
            ]
        );
        $userPersonaB->save();
        $userPersonaC = UserPersona::firstOrCreate(
            [
                'user_id' => $userC->id,
                'persona_id' => $personaC->id
            ]
        );
        $userPersonaC->save();
        $userPersonaD = UserPersona::firstOrCreate(
            [
                'user_id' => $userD->id,
                'persona_id' => $personaD->id
            ]
        );
        $userPersonaD->save();

        // Creación de centros
        $centroA = Centro::firstOrCreate(
            ['id' => 1],
            [
                'cod_centro' => 'centro-1',
                'denominacion' => 'Centro Inicial para la definición de la relación entre modelos',
                'estado' => 1,
            ]
        );
        $centroB = Centro::firstOrCreate(
            ['id' => 2],
            [
                'cod_centro' => 'centro-2',
                'denominacion' => 'Centro Inicial para la definición de la relación entre modelos',
                'estado' => 1,
            ]
        );

        // Creación de users_centros
        $userCentroA1 = UserCentro::firstOrCreate([
            'user_id' => $userA->id,
            'centro_id' => 1,
        ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $userCentroA1->save();
        $userCentroA2 = UserCentro::firstOrCreate([
            'user_id' => $userA->id,
            'centro_id' => 2,
        ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $userCentroA2->save();
        $userCentroB = UserCentro::firstOrCreate([
            'user_id' => $userB->id,
            'centro_id' => 1,
        ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $userCentroB->save();
        $userCentroC = UserCentro::firstOrCreate([
            'user_id' => $userC->id,
            'centro_id' => 1,
        ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $userCentroD = UserCentro::firstOrCreate([
            'user_id' => $userD->id,
            'centro_id' => 1,
        ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $userCentroD->save();

        $role = Role::where('name', 'admin')->firstOrFail();
        User::updateOrCreate(['name'  => 'SuperAdmin'],
            [
                'email'          => 'superadmin@superadmin.com',
                'password'       => bcrypt('12345678'),
                'remember_token' => Str::random(60),
                'role_id'        => $role->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

    }
}