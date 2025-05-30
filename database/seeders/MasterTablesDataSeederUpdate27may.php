<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CampoEntrada;
use App\Models\CampoValor;
use App\Models\NivelEstudio;

class MasterTablesDataSeederUpdate27may extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        printf("seeder init\n");
        // Cambiar valores estudios
        $cambios_mst_nivel_estudios = [
            'FP 1' => 'Formación profesional',
            'Formación profesiona/' => 'Formación profesional'
        ];
        $agregar_nivel_estudios = [
            'Infantil',
        ];
        foreach ($cambios_mst_nivel_estudios as $viejo => $nuevo) {
                try {
                    $campoValor = NivelEstudio::where('descripcion', $viejo );
                    if ($campoValor->count() > 0 ) {
                        $campoValor->update([
                            'descripcion'=> $nuevo,
                            'updated_at'=> now(),
                        ]);
                        $msg = "Cambiado nivel de estudios, '$viejo' a '$nuevo' ";
                        printf($msg . "\n");

                        customLoggin(
                            config('ctes.log_levels.info'),
                            config('ctes.log_types.create_or_update'),
                            ['file' => __FILE__, 'line' => __LINE__],
                            PHP_EOL . $msg,
                            PHP_EOL .'Actualizado en mst_nivel_estudios'
                        );
                    }

                } catch (\Throwable $th) {
                    customLoggin(
                        config('ctes.log_levels.error'),
                        config('ctes.log_types.create_or_update'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . "Error Cambiado clave 'mst_nivel_estudios' descripción: '$viejo' a '$nuevo' " .
                        PHP_EOL . $th->getMessage(),
                        PHP_EOL .'Actualizado en mst_nivel_estudios'
                    );
                }
        }
        foreach ($agregar_nivel_estudios as $valor) {
            try {
                $ne = NivelEstudio::firstOrNew(
                    [
                        'descripcion'   => $valor
                    ],
                    [
                        'estado'        => 1,
                        'updated_at'    => now(),
                        'created_at'    => now(),
                    ]);
                if (!$ne->exists) {
                    $ne->save();
                    $msg = "creando: '$valor' ";
                    printf($msg . "\n");
                    customLoggin(
                        config('ctes.log_levels.info'),
                        config('ctes.log_types.create_or_update'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . $msg,
                        PHP_EOL ."error creando nuevo registro en mst_nivel_estudios"
                    );
                } else {
                    customLoggin(
                        config('ctes.log_levels.error'),
                        config('ctes.log_types.create_or_update'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . "Ya existe nivel de estudios: '$valor' ",
                        PHP_EOL ."error guardando en mst_nivel_estudios"
                    );
                }
            } catch (\Throwable $th) {
                customLoggin(
                    config('ctes.log_levels.error'),
                    config('ctes.log_types.create_or_update'),
                    ['file' => __FILE__, 'line' => __LINE__],
                    PHP_EOL . "Error creando: '$nuevo' " .
                    PHP_EOL . json_encode($ne, JSON_PRETTY_PRINT) .
                    PHP_EOL . $th->getMessage(),
                    PHP_EOL ."error guardando en mst_nivel_estudios"
                );
            }
        }
        printf("seeder end\n");

    } // fin run
}
