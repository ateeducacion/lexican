<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CampoEntrada;
use App\Models\CampoValor;
use App\Models\TipoDiccionarioAula;
use App\Models\Ensenanza;
use App\Models\NivelEstudio;
use App\Models\AreaMateria;
use App\Models\EnsenanzaEstudio;
use App\Models\EstudioAreaMateria;
use Illuminate\Support\Facades\DB;

class MasterTablesDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        /*TRUNCATE TABLES*/
        DB::statement('DELETE FROM mst_estudios_areas_materias');
        DB::statement('DELETE FROM mst_ensenanzas_estudios');
        DB::statement('DELETE FROM mst_campos_valores');
        DB::statement('DELETE FROM mst_campos_entrada');
        DB::statement('DELETE FROM mst_areas_materias');
        DB::statement('DELETE FROM mst_ensenanzas');
        DB::statement('DELETE FROM mst_nivel_estudios');
        DB::statement('DELETE FROM mst_tipos_diccionario_aula');

        /*RESET AUTOINCREMENTS*/
        DB::statement('ALTER TABLE mst_estudios_areas_materias AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE mst_ensenanzas_estudios AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE mst_campos_valores AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE mst_campos_entrada AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE mst_areas_materias AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE mst_ensenanzas AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE mst_nivel_estudios AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE mst_tipos_diccionario_aula AUTO_INCREMENT = 1');

        /**
         * @var string  $mst_campos_entrada  The values of table mst_campos_entrada.
         */
        $mst_campos_entrada = [
            'Categoría gramatical',
            'Género',
            'Número',
            'Temáticas generales',
            'Más datos',
            'Ejemplo de uso',
            'Vídeo',
            'Audio',
            'Imagen',
            'Otros lenguajes'
        ];
        foreach ($mst_campos_entrada as $clave => $valor) {
            CampoEntrada::firstOrCreate(
                [
                    'nombre_campo' => $valor
                ],
                [
                    'estado'        => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
        }

        /**
         * @var string  $mst_campos_valores  The values of table mst_campos_valores.
         */
        $mst_campos_valores = [
            '1' => [
                'Adjetivo', 'Sustantivo', 'Verbo', 'Preposición', 'Conjunción', 'Adverbio', 'Determinante', 'Interjección', 'Pronombre'

            ],
            '2' => [
                'Femenino', 'Masculino', 'Masculino y femenino', 'Neutro', 'No tiene'
            ],
            '3' => [
                'Singular', 'Plural'
            ],
            '4' => [
                'Lengua', 'Arte', 'Historia', 'Educación física', 'Cultura', 'General', 'Gastronomía', 'Toponimia',
                'Fauna canaria',
                'Astronomía',
                'Inglés',
                'Francés',
                'Alemán',
                'Otros idiomas',
                'Matemáticas',
                'Tecnología y Electrotecnia',
                'Física y Química',
                'Biología y Geología',
                'Ciencias Naturales',
                'Ciencias Sociales',
                'Economía',
                'Geografía e Historia',
                'Valores',
                'Psicología',
                'Filosofía',
                'Religión',
                'Música y danza',
                'Dialecto canario',
                'Etnografía y artesanía canarias',
                'Paisaje y arquitectura canaria',
                'Vestuario y complementos canarios',
                'Deportes autóctonos',
                'Música tradicional canaria',
                'Espacios naturales canarios',
                'Sociedad y cultura canaria',
                'Patrimonio canario',
                'Salud',
                'Medio Ambiente y Sostenibilidad',
                'TIC',
                "Flora canaria",
                "Literatura canaria",
                "Personaje ilustre canario"
            ],
            '5' => [],
            '6' => [],
            '7' => [],
            '8' => [],
            '9' => [
                ' alemán',
                ' notación científica',
                ' inglés',
                ' español',
                ' francés',
                'dialecto canario',  'afar', 'abjasio (o abjasiano)', 'avéstico', 'afrikáans', 'akano', 'amhárico', 'aragonés', 'árabe', 'asamés', 'avar (o ávaro)', 'aimara', 'azerí', 'baskir', 'bielorruso', 'búlgaro', 'bhoyapurí', 'bislama', 'bambara', 'bengalí', 'tibetano', 'bretón', 'bosnio', 'catalán', 'checheno', 'chamorro', 'corso', 'cree', 'checo', 'eslavo eclesiástico antiguo', 'chuvasio', 'galés', 'danés',  'maldivo (o dhivehi)', 'dzongkha', 'ewé', 'griego (moderno)', 'esperanto',
                'estonio', 'euskera', 'persa', 'fula', 'finés (o finlandés)', 'fiyiano (o fiyi)', 'feroés',  'frisón (o frisio)', 'irlandés (o gaélico)', 'gaélico escocés', 'gallego', 'guaraní', 'guyaratí (o gujaratí)', 'manés (gaélico manés o de Isla de Man)', 'hausa', 'hebreo', 'hindi (o hindú)', 'hiri motu', 'croata', 'haitiano', 'húngaro', 'armenio', 'herero', 'interlingua', 'indonesio', 'occidental', 'igbo', 'yi de Sichuán', 'iñupiaq', 'ido', 'islandés', 'italiano', 'inuktitut (o inuit)', 'japonés', 'javanés', 'georgiano', 'kongo (o kikongo)', 'kikuyu', 'kuanyama', 'kazajo (o kazajio)', 'groenlandés (o kalaallisut)', 'camboyano (o jemer)', 'canarés', 'coreano', 'kanuri', 'cachemiro (o cachemir)', 'kurdo', 'komi', 'córnico', 'kirguís', 'latín', 'luxemburgués', 'luganda', 'limburgués', 'lingala', 'lao', 'lituano', 'luba-katanga (o chiluba)', 'letón', 'malgache (o malagasy)', 'marshalés', 'maorí', 'macedonio', 'malayalam', 'mongol', 'maratí', 'malayo', 'maltés', 'birmano', 'nauruano', 'noruego bokmål', 'ndebele del norte', 'nepalí', 'ndonga', 'neerlandés (u holandés)', 'nynorsk', 'noruego', 'ndebele del sur', 'navajo', 'chichewa', 'occitano', 'ojibwa', 'oromo', 'oriya', 'osético (u osetio, u oseta)', 'panyabí (o penyabi)', 'pali', 'polaco', 'pastú (o pastún, o pashto)', 'portugués', 'quechua', 'romanche', 'kirundi', 'rumano', 'ruso', 'ruandés (o kiñaruanda)', 'sánscrito', 'sardo', 'sindhi', 'sami septentrional', 'sango', 'cingalés', 'eslovaco', 'esloveno', 'samoano', 'shona', 'somalí', 'albanés', 'serbio', 'suazi (o swati, o siSwati)', 'sesotho', 'sundanés (o sondanés)', 'sueco', 'suajili', 'tamil', 'télugu', 'tayiko', 'tailandés', 'tigriña', 'turcomano', 'tagalo', 'setsuana', 'tongano', 'turco', 'tsonga', 'tártaro', 'twi', 'tahitiano', 'uigur', 'ucraniano', 'urdu', 'uzbeko', 'venda', 'vietnamita', 'volapük', 'valón', 'wolof', 'xhosa', 'yídish (o yidis, o yiddish)', 'yoruba', 'chuan (o chuang, o zhuang)', 'chino', 'zulú'
            ]

        ];
        foreach ($mst_campos_valores as $clave => $valor) {
            foreach($valor as $descripcion){
                CampoValor::firstOrCreate(
                    [
                        'descripcion'   => $descripcion
                    ],
                    [
                        'mst_campo_entrada_id'  => $clave,
                        'estado'                => 1,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ]);
            }
        }

        /**
         * @var string  $mst_tipos_diccionario_aula  The values of table mst_tipos_diccionario_aula.
         */
        $mst_tipos_diccionario_aula = [
            'Diccionario general',
            'Canarismos'
        ];
        foreach ($mst_tipos_diccionario_aula as $valor) {
            TipoDiccionarioAula::firstOrCreate(
                [
                    'tipo_diccionario' => $valor
                ],
                [
                    'estado'        => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
        }

        /**
         * @var string  $mst_ensenanzas  The values of table mst_ensenanzas.
         */
        $mst_ensenanzas = [
            'Multienseñanza',
            'Primaria',
            'ESO',
            'Formación profesional'
        ];
        foreach ($mst_ensenanzas as $clave => $valor) {
            Ensenanza::firstOrCreate(
                [
                    'descripcion'   => $valor
                ],
                [
                    'estado'        => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
        }

        /**
         * @var string  $mst_nivel_estudios  The values of table mst_ensmst_nivel_estudiosenanzas.
         */
        $mst_nivel_estudios = [
            'Multiestudio',
            'Infantil',
            '1º Primaria',
            '2º Primaria',
            '3º Primaria',
            '4º Primaria',
            '5º Primaria',
            '6º Primaria',
            '1º ESO',
            '2º ESO',
            '3º ESO',
            '4º ESO',
            'Concreción Curricular Adaptada',
            '1º Bachillerato',
            '2º Bachillerato',
            'Formación Profesional Básica',
            // 'FP 1',
            '1º PMAR',
            '2º PMAR',
            'Otros'
        ];
        foreach ($mst_nivel_estudios as $clave => $valor) {
            NivelEstudio::firstOrCreate(
                [
                    'descripcion'   => $valor
                ],
                [
                    'estado'        => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
        }

        /**
         * @var string  $mst_areas_materias  The values of table mst_areas_materias.
         */
        $mst_areas_materias = [
            'Interdisciplinar',
            'Acondicionamiento Físico',
            'Ámbito Científico y Matemático',
            'Ámbito de Autonomía Personal',
            'Ámbito de Autonomía Social',
            'Ámbito de Comunicación y Representación',
            'Ámbito de Lenguas Extranjeras',
            'Ámbito Laboral',
            'Ámbito Lingüístico y Social',
            'Análisis musical',
            'Anatomía Aplicadas',
            'Antropología y SociologíaBioestadística',
            'Artes aplicadas a la escultura',
            'Artes Escénicas y Danza',
            'Artes Escénicas',
            'Biología Humana',
            'Biología y Geología',
            'Cerámica',
            'Ciencias Aplicadas a la Actividad Profesional',
            'Ciencias Aplicadas',
            'Ciencias de la Naturaleza',
            'Ciencias de la Tierra y del Medio Ambiente',
            'Ciencias Sociales',
            'Comunicación y Sociedad',
            'Cultura Audiovisual',
            'Cultura Audiovisual',
            'Cultura Científica',
            'Cultura Clásica',
            'Dibujo Artístico o Técnico',
            'Diseño',
            'Economía y Economía de la empresa',
            'Economía',
            'Educación Artística',
            'Educación Emocional y para la Creatividad',
            'Educación Física',
            'Educación para la ciudadanía y los derechos humanos',
            'Educación Plástica, Visual y Audiovisual',
            'Electrotecnia',
            'Estrategia para la Autonomía y la Cooperación',
            'Filosofía',
            'Física y Química',
            'Física',
            'Fotografía',
            'Fundamentos de Administración y Gestión',
            'Fundamentos del Arte',
            'Geografía e Historia',
            'Geografía',
            'Geología',
            'Griego',
            'Historia de España',
            'Historia de la Filosofía',
            'Historia de la Música y la Danza',
            'Historia del Arte',
            'Historia del Mundo Contemporáneo',
            'Historia y Geografía de Canarias',
            'Imagen y sonido',
            'Iniciación a la actividad emprendedora y empresarial',
            'Iniciación a la Astronomía',
            'La mitología y las artes',
            'Latín',
            'Lengua Castellana y Literatura',
            'Lenguaje y práctica musical',
            'Literatura canaria',
            'Literatura universal',
            'Matemáticas Académicas',
            'Matemáticas aplicadas a las Ciencias Sociales',
            'Matemáticas Aplicadas',
            'Matemáticas',
            'Música',
            'Prácticas Comunicativas y Creativas',
            // 'Primera Lengua Extranjera',
            'Primera Lengua Extranjera: Inglés',
            'Psicología',
            'Química',
            'Religión Católica',
            'Religión Evangélica',
            'Religión Islámica',
            'Religión',
            'Segunda Lengua Extranjera: Alemán',
            'Segunda Lengua Extranjera: Francés',
            'Segunda Lengua Extranjera: Italiano',
            'Segunda Lengua Extranjera: otras',
            'Sesiones profundización curricular',
            'Técnicas de Expresión Gráfico-Plástica',
            'Técnicas de Laboratorio',
            'Tecnología (E)',
            'Tecnología Industrial',
            'Tecnología',
            'Tecnologías de la información y la comunicación',
            'Tutoría',
            'Valores Éticos',
            'Valores Sociales y Cívicos',
            'Volumen',
            // Nuevas etiquetas sugieridas por Eli mayo 2022
            "Flora canaria",
            "Literatura canaria",
        ];
        foreach ($mst_areas_materias as $clave => $valor) {
            AreaMateria::firstOrCreate(
                [
                    'descripcion'   => $valor
                ],
                [
                    'estado'        => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
        }


        $estudios = NivelEstudio::all();
        $areasMaterias = AreaMateria::all();
        $ensenanzas = Ensenanza::all();
        foreach($estudios as $estudio){
            foreach($areasMaterias as $areaMateria){
                EstudioAreaMateria::firstOrCreate([
                    'mst_nivel_estudios_id' => $estudio->id,
                    'mst_area_materia_id' => $areaMateria->id,
                ],[
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
        foreach($estudios as $estudio){
            foreach($ensenanzas as $ensenanza){
                EnsenanzaEstudio::firstOrCreate([
                    'mst_ensenanzas_id' => $ensenanza->id,
                    'mst_nivel_estudios_id' => $estudio->id,
                ],[
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

    }
}
