<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CampoEntrada;
use App\Models\CampoValor;
use App\Models\NivelEstudio;

class MasterTablesDataSeederUpdate extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $start_time = microtime(true); 
        printf("seeder init\n");

        // Cambios mst_campos_entrada
        $cambios_mst_campos_entradas = [
            'Frase ejemplo'   => 'Más datos',
            'Lengua-idioma'   => 'Lengua', // primero se cambio a Otros lenguajes
            'Otros lenguajes' => 'Lengua', // asi que se revisan los dos casos
        ];

        foreach ($cambios_mst_campos_entradas as $viejo => $nuevo) {
            $msg = "'$viejo' a '$nuevo' ";
            printf($msg . "\n");
            try {
                $campo = CampoEntrada::where('nombre_campo',$viejo );
                if ($campo->count() > 0 ){
                    $msg = "Cambiado CampoEntrada, nombre campo '$viejo' a '$nuevo' ";
                    printf($msg . "\n");
                    $c = $campo->first();
                    $c->update([
                        'nombre_campo'=> $nuevo,
                        'updated_at' => now()
                    ]);
                    $c->save();
                    printf("Cambiado nombre_campo '$viejo' a '$nuevo' \n");
                    customLoggin(
                        config('ctes.log_levels.info'),
                        config('ctes.log_types.create_or_update'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . $msg,
                        PHP_EOL . "Actualizado en mst_campos_entrada"
                    );
                }


            } catch (\Throwable $th) {
                printf("Fallo al intentar cambiar nombre_campo '$viejo' a '$nuevo' \n");
                customLoggin(
                    config('ctes.log_levels.error'),
                    config('ctes.log_types.create_or_update'),
                    ['file' => __FILE__, 'line' => __LINE__],
                    PHP_EOL . "Fallo al intentar cambiar nombre_campo '$viejo' a '$nuevo'" .
                    PHP_EOL . $th->getMessage() ,
                    PHP_EOL ."Actualizado en mst_campos_entrada"
                );
            }
        }

        // Cambiar castellano y destacar  INGLÉS, FRANCÉS, ALEMÁN, NOTACIÓN CIENTÍFICA y ESPAÑOL
        // '9' =>
        $cambios_mst_campos_valores = [ '9' => [
            'Notación científica' => ' notación científica',
            'Alemán' => ' alemán',
            'Inglés' => ' inglés',
            'español (o castellano)' => ' español',
            'Francés' => ' francés',
            'italiano' => ' italiano'
        ] ];


        foreach ($cambios_mst_campos_valores as $clave => $valor) {
            $msg = "cambios en $clave ";
            printf($msg . "\n");
            foreach($valor as $viejo => $nuevo ){
                $msg = "'$viejo' a '$nuevo' ";
                printf($msg . "\n");
                try {
                    $campoValor = CampoValor::where('descripcion', $viejo )
                            ->where('mst_campo_entrada_id', $clave);
                    if ($campoValor->count() > 0 ) {
                        $cv = $campoValor->first();
                        $cv->descripcion = $nuevo;
                        $cv->save();

                        // $campoValor->first()->update([
                        //     'descripcion'=> $nuevo
                        // ]);

                        $msg = "Cambiado clave: '$clave', descricion: '$viejo' a '$nuevo' ";
                        printf( $msg . "\n" );

                        // customLoggin(
                        //     config('ctes.log_levels.info'),
                        //     config('ctes.log_types.create_or_update'),
                        //     ['file' => __FILE__, 'line' => __LINE__],
                        //     PHP_EOL . $msg,
                        //     PHP_EOL ."Actualizado en mst_campos_valores"
                        // );
                    } else {
                        printf("no se encuentra $viejo , no es necesario el camibo");
                    }

                } catch (\Throwable $th) {
                    customLoggin(
                        config('ctes.log_levels.error'),
                        config('ctes.log_types.create_or_update'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . "Error Cambiado clave: '$clave', descricion: '$viejo' a '$nuevo' " .
                        PHP_EOL . $th->getMessage(),
                        PHP_EOL ."Actualizado en mst_campos_valores"
                    );
                }

            }
        }
        printf("fin cambios_mst_campos_valores \n");

        $borrar_mst_campos_valores =  [ '9' => [
            'dialecto canario',
        ] ];

        foreach ( $borrar_mst_campos_valores as $clave => $valor) {
            $msg = "borrar en '$clave'  ";
            printf($msg . "\n");
            foreach($valor as $nuevo ){
                $msg = "borrar '$clave', descripción: '$nuevo' ";
                printf($msg . "\n");
                $check = CampoValor::where('descripcion', $nuevo )->where('mst_campo_entrada_id', $clave);
                try {
                    if ($check->count() > 0) {
                        $check->first()->delete();
                        $msg = "borrando: '$clave', descripción: '$nuevo' ";
                        printf($msg . "\n");

                        customLoggin(
                            config('ctes.log_levels.info'),
                            config('ctes.log_types.create_or_update'),
                            ['file' => __FILE__, 'line' => __LINE__],
                            PHP_EOL . $msg,
                            PHP_EOL . 'Actualizado en mst_campos_valores'
                        );
                    }
                } catch (\Throwable $th) {
                    customLoggin(
                        config('ctes.log_levels.error'),
                        config('ctes.log_types.create_or_update'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . "Error borrando: '$clave', descripción: '$nuevo' " .
                        PHP_EOL . $th->getMessage(),
                        PHP_EOL . 'Actualizado en mst_campos_valores'
                    );
                }
            }
        }

        // Agregar valores si no existen :
        $agregar = [
            '4' => ['Flora canaria','Literatura canaria','Personaje ilustre canario'],
            '9' => [
            ' alemán',
            ' notación científica',
            ' inglés',
            ' español',
            ' francés',
            ' italiano',
            // 'afar', 'abjasio (o abjasiano)', 'avéstico', 'afrikáans', 'akano', 'amhárico', 'aragonés', 'árabe', 'asamés', 'avar (o ávaro)', 'aimara', 'azerí', 'baskir', 'bielorruso', 'búlgaro', 'bhoyapurí', 'bislama', 'bambara', 'bengalí', 'tibetano', 'bretón', 'bosnio', 'catalán', 'checheno', 'chamorro', 'corso', 'cree', 'checo', 'eslavo eclesiástico antiguo', 'chuvasio', 'galés', 'danés',  'maldivo (o dhivehi)', 'dzongkha', 'ewé', 'griego (moderno)', 'esperanto',
            // 'estonio', 'euskera', 'persa', 'fula', 'finés (o finlandés)', 'fiyiano (o fiyi)', 'feroés',  'frisón (o frisio)', 'irlandés (o gaélico)', 'gaélico escocés', 'gallego', 'guaraní', 'guyaratí (o gujaratí)', 'manés (gaélico manés o de Isla de Man)', 'hausa', 'hebreo', 'hindi (o hindú)', 'hiri motu', 'croata', 'haitiano', 'húngaro', 'armenio', 'herero', 'interlingua', 'indonesio', 'occidental', 'igbo', 'yi de Sichuán', 'iñupiaq', 'ido', 'islandés', 'inuktitut (o inuit)', 'japonés', 'javanés', 'georgiano', 'kongo (o kikongo)', 'kikuyu', 'kuanyama', 'kazajo (o kazajio)', 'groenlandés (o kalaallisut)', 'camboyano (o jemer)', 'canarés', 'coreano', 'kanuri', 'cachemiro (o cachemir)', 'kurdo', 'komi', 'córnico', 'kirguís', 'latín', 'luxemburgués', 'luganda', 'limburgués', 'lingala', 'lao', 'lituano', 'luba-katanga (o chiluba)', 'letón', 'malgache (o malagasy)', 'marshalés', 'maorí', 'macedonio', 'malayalam', 'mongol', 'maratí', 'malayo', 'maltés', 'birmano',
            //  'nauruano', 'noruego bokmål', 'ndebele del norte', 'nepalí', 'ndonga', 'neerlandés (u holandés)', 'nynorsk', 'noruego', 'ndebele del sur', 'navajo', 'chichewa', 'occitano', 'ojibwa', 'oromo', 'oriya', 'osético (u osetio, u oseta)', 'panyabí (o penyabi)', 'pali', 'polaco', 'pastú (o pastún, o pashto)', 'portugués', 'quechua', 'romanche', 'kirundi', 'rumano', 'ruso', 'ruandés (o kiñaruanda)', 'sánscrito', 'sardo', 'sindhi', 'sami septentrional', 'sango', 'cingalés', 'eslovaco', 'esloveno', 'samoano', 'shona', 'somalí', 'albanés', 'serbio', 'suazi (o swati, o siSwati)', 'sesotho', 'sundanés (o sondanés)', 'sueco', 'suajili', 'tamil', 'télugu', 'tayiko', 'tailandés', 'tigriña', 'turcomano', 'tagalo', 'setsuana', 'tongano', 'turco', 'tsonga', 'tártaro', 'twi', 'tahitiano', 'uigur', 'ucraniano', 'urdu', 'uzbeko', 'venda', 'vietnamita', 'volapük', 'valón', 'wolof', 'xhosa', 'yídish (o yidis, o yiddish)', 'yoruba', 'chuan (o chuang, o zhuang)', 'chino', 'zulú'
            ]
        ];

        foreach ($agregar as $clave => $valor) {
            echo "$clave ";
            foreach($valor as $nuevo ){
                    echo "$nuevo";
                    $check = CampoValor::where('descripcion', $nuevo )->where('mst_campo_entrada_id', $clave);
                    // CampoValor::where('descripcion', 'alemas' )->where('mst_campo_entrada_id', 9);
                    //
                    $msg = "CampoValor: Creado registro: '$clave', descricion: '$nuevo' ";
                    printf($msg . "\n");

                    try {
                        if ($check->count() == 0) {
                            $campoValor = new CampoValor;
                            $campoValor->descripcion = $nuevo;
                            $campoValor->mst_campo_entrada_id = $clave;
                            $campoValor->estado = 1;
                            try {
                                $campoValor->save();
                            } catch (\Throwable $th) {
                                customLoggin(
                                    config('ctes.log_levels.error'),
                                    config('ctes.log_types.create_or_update'),
                                    ['file' => __FILE__, 'line' => __LINE__],
                                    PHP_EOL . "Error Cambiado creando: '$clave', descricion: '$nuevo' " .
                                    PHP_EOL . json_encode($campoValor, JSON_PRETTY_PRINT) .
                                    PHP_EOL . $th->getMessage(),
                                    PHP_EOL ."error guardando Actualizado en mst_campos_valores"
                                );
                            }

                            customLoggin(
                                config('ctes.log_levels.info'),
                                config('ctes.log_types.create_or_update'),
                                ['file' => __FILE__, 'line' => __LINE__],
                                PHP_EOL . $msg .
                                PHP_EOL . json_encode($campoValor, JSON_PRETTY_PRINT)
                                ,
                                PHP_EOL ."Actualizado en mst_campos_valores"
                            );
                        } else {
                            customLoggin(
                                config('ctes.log_levels.info'),
                                config('ctes.log_types.create_or_update'),
                                ['file' => __FILE__, 'line' => __LINE__],
                                PHP_EOL . "Ya existe registro: '$clave', descricion: '$nuevo' " .
                                PHP_EOL . "num coincidencias: " . $check->count() ,
                                PHP_EOL ."Actualizado en mst_campos_valores"
                            );

                        }
                    } catch (\Throwable $th) {
                        customLoggin(
                            config('ctes.log_levels.error'),
                            config('ctes.log_types.create_or_update'),
                            ['file' => __FILE__, 'line' => __LINE__],
                            PHP_EOL . "Error Cambiado creando: '$clave', descricion: '$nuevo' " .
                            PHP_EOL . $th->getMessage(),
                            PHP_EOL ."Actualizado en mst_campos_valores"
                        );
                    }
            }
        }

        // Cambiar valores estudios
        $cambios_mst_nivel_estudios = [
            'FP 1' => 'Formación profesional',
            'Formación profesiona/' => 'Formación profesional'
        ];
        $agregar_nivel_estudios = [
            'Infantil',
        ];
        foreach ($cambios_mst_nivel_estudios as $viejo => $nuevo) {
            echo "actulizando $viejo a $nuevo";
                try {
                    $campoValor = NivelEstudio::where('descripcion', $viejo );

                    $msg = "Cambiado nivel de estudios, '$viejo' a '$nuevo' ";
                    printf($msg . "\n");
                    if ($campoValor->count() > 0 ) {
                        $c = $campoValor->first();

                        $c->update([
                            'descripcion'=> $nuevo,
                            'updated_at'=> now(),
                        ]);
                        $c->save();

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
                        PHP_EOL . "Error Cambiado clave: '$clave', descricion: '$viejo' a '$nuevo' " .
                        PHP_EOL . $th->getMessage(),
                        PHP_EOL .'Actualizado en mst_nivel_estudios'
                    );
                }
        }
        foreach ($agregar_nivel_estudios as $valor) {
            $msg = "Agregar nivel estudios, '$valor' ";
            printf($msg . "\n");
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
                    customLoggin(
                        config('ctes.log_levels.info'),
                        config('ctes.log_types.create_or_update'),
                        ['file' => __FILE__, 'line' => __LINE__],
                        PHP_EOL . "creando: '$valor' ",
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
        $end_time = microtime(true); 

        $t = date("H:i:s", $end_time - $start_time);
        printf("seeder end. time: $t \n");
    }
}
