<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenusTableSeeder extends Seeder
{
    /**
     * Auto generated seed file.
     */
    public function run()
    {
        $this->cleanTable('menus');

        DB::table('menus')->insert([
            0 => [
                'id' => 1,
                'name' => 'admin',
            ],
            1 => [
                'id' => 2,
                'name' => 'Oficina técnica',
            ],
        ]);
    }

    protected function cleanupTable($table_name)
    {
        $this->command->line("Borrando tabla {$table_name}", 'info');
        DB::table($table_name)->delete();
        $this->command->line("Reseteando autoincremental de la tabla {$table_name}", 'info');
        DB::statement('SET @count = 0;');
        DB::statement("UPDATE `{$table_name}` SET `{$table_name}`.`id` = @count:= @count + 1;");
        DB::statement("ALTER TABLE `{$table_name}` AUTO_INCREMENT = 1;");
    }

    private function cleanTable($name)
    {
        try {
            $this->cleanupTable($name);
        } catch (\Throwable $th) {
            $this->command->line($th->getMessage(), 'error');
        }
    }
}
