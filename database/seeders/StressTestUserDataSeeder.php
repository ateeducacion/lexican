<?php

use App\Models\CampoEntrada;
use App\Models\DicAula;
use App\Models\DiccionarioPersonalAcepcion;
use App\Models\DiccionarioPersonalEntrada;
use App\Models\Persona;
use App\Models\UserPersona;
use App\User;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Boolean;
use TCG\Voyager\Models\Role;

use function GuzzleHttp\json_encode;

// echo $rolAlumno->name;
// printf("\n");
// php generar nif 

// http://www.cervantesvirtual.com/portales/benito_perez_galdos/obra-visor/dona-perfecta-novela-original--0/html/ff498544-82b1-11df-acc7-002185ce6064_3.html#I_1_


function letraNIF ($dni) {
    /* Obtiene letra del NIF a partir del DNI */            
    $valor= (int) ($dni / 23);
    $valor *= 23;            
    $valor= $dni - $valor;            
    $letras= "TRWAGMYFPDXBNJZSQVHLCKEO";            
    $letraNif= substr ($letras, $valor, 1);            
    return $letraNif;            
}
function generarDNI(){
    $num = rand( 100000000, 199999999 );
    $num = substr($num, 1);
    $letra = letraNIF($num);
    return $num.$letra;
}
function createRandomUser($rol)
{
    $nombres = [ 'ANTONIO', 'MANUEL', 'JOSE', 'FRANCISCO', 'DAVID', 'JUAN', 'JOSE ANTONIO', 'JAVIER', 'DANIEL', 'JOSE LUIS', 'FRANCISCO JAVIER', 'CARLOS', 'JESUS', 'ALEJANDRO', 'MIGUEL', 'JOSE MANUEL', 'RAFAEL', 'MIGUEL ANGEL', 'PEDRO', 'PABLO', 'ANGEL', 'SERGIO', 'JOSE MARIA', 'FERNANDO', 'JORGE', 'LUIS', 'ALBERTO', 'JUAN CARLOS', 'ALVARO', 'ADRIAN', 'JUAN JOSE', 'DIEGO', 'RAUL', 'IVAN', 'JUAN ANTONIO', 'RUBEN', 'ENRIQUE', 'OSCAR', 'RAMON', 'VICENTE', 'ANDRES', 'JUAN MANUEL', 'JOAQUIN', 'SANTIAGO', 'VICTOR', 'EDUARDO', 'MARIO', 'ROBERTO', 'JAIME', 'FRANCISCO JOSE', 'MARCOS', 'IGNACIO', 'ALFONSO', 'JORDI', 'HUGO', 'RICARDO', 'SALVADOR', 'GUILLERMO', 'EMILIO', 'GABRIEL', 'MARC', 'GONZALO', 'JULIO', 'JULIAN', 'MOHAMED', 'JOSE MIGUEL', 'TOMAS', 'MARTIN', 'AGUSTIN', 'JOSE RAMON', 'NICOLAS', 'ISMAEL', 'JOAN', 'FELIX', 'SAMUEL', 'CRISTIAN', 'AITOR', 'LUCAS', 'HECTOR', 'JUAN FRANCISCO', 'IKER', 'JOSEP', 'JOSE CARLOS', 'ALEX', 'MARIANO', 'DOMINGO', 'SEBASTIAN', 'ALFREDO', 'CESAR', 'JOSE ANGEL', 'FELIPE', 'JOSE IGNACIO', 'VICTOR MANUEL', 'RODRIGO', 'LUIS MIGUEL', 'MATEO', 'JOSE FRANCISCO', 'JUAN LUIS', 'XAVIER', 'ALBERT', 'MARIA CARMEN', 'MARIA', 'CARMEN', 'ANA MARIA', 'JOSEFA', 'ISABEL', 'MARIA PILAR', 'MARIA DOLORES', 'LAURA', 'MARIA TERESA', 'ANA', 'CRISTINA', 'MARTA', 'MARIA ANGELES', 'FRANCISCA', 'LUCIA', 'MARIA ISABEL', 'MARIA JOSE', 'ANTONIA', 'DOLORES', 'SARA', 'PAULA', 'ELENA', 'MARIA LUISA', 'RAQUEL', 'ROSA MARIA', 'PILAR', 'CONCEPCION', 'MANUELA', 'MARIA JESUS', 'MERCEDES', 'JULIA', 'BEATRIZ', 'NURIA', 'SILVIA', 'ROSARIO', 'JUANA', 'ALBA', 'IRENE', 'TERESA', 'ENCARNACION', 'PATRICIA', 'MONTSERRAT', 'ANDREA', 'ROCIO', 'MONICA', 'ROSA', 'ALICIA', 'MARIA MAR', 'SONIA', 'SANDRA', 'ANGELA', 'MARINA', 'SUSANA', 'NATALIA', 'YOLANDA', 'MARGARITA', 'MARIA JOSEFA', 'CLAUDIA', 'EVA', 'MARIA ROSARIO', 'INMACULADA', 'SOFIA', 'MARIA MERCEDES', 'CARLA', 'ANA ISABEL', 'ESTHER', 'NOELIA', 'VERONICA', 'ANGELES', 'NEREA', 'CAROLINA', 'MARIA VICTORIA', 'EVA MARIA', 'INES', 'MIRIAM', 'MARIA ROSA', 'DANIELA', 'LORENA', 'ANA BELEN', 'MARIA ELENA', 'MARIA CONCEPCION', 'VICTORIA', 'AMPARO', 'MARIA ANTONIA', 'CATALINA', 'MARTINA', 'LIDIA', 'ALEJANDRA', 'CELIA', 'MARIA NIEVES', 'CONSUELO', 'OLGA', 'AINHOA', 'FATIMA', 'GLORIA', 'EMILIA', 'MARIA SOLEDAD', 'CLARA', 'MARIA CRISTINA', ];
    $apellidos = [ 'GARCIA', 'RODRIGUEZ', 'GONZALEZ', 'FERNANDEZ', 'LOPEZ', 'MARTINEZ', 'SANCHEZ', 'PEREZ', 'GOMEZ', 'MARTIN', 'JIMENEZ', 'RUIZ', 'HERNANDEZ', 'DIAZ', 'MORENO', 'MUÑOZ', 'ALVAREZ', 'ROMERO', 'ALONSO', 'GUTIERREZ', 'NAVARRO', 'TORRES', 'DOMINGUEZ', 'VAZQUEZ', 'RAMOS', 'GIL', 'RAMIREZ', 'SERRANO', 'BLANCO', 'MOLINA', 'MORALES', 'SUAREZ', 'ORTEGA', 'DELGADO', 'CASTRO', 'ORTIZ', 'MARIN', 'RUBIO', 'SANZ', 'NUÑEZ', 'MEDINA', 'IGLESIAS', 'CORTES', 'CASTILLO', 'GARRIDO', 'SANTOS', 'LOZANO', 'GUERRERO', 'CANO', 'PRIETO', 'MENDEZ', 'CRUZ', 'FLORES', 'HERRERA', 'GALLEGO', 'MARQUEZ', 'LEON', 'PEÑA', 'CALVO', 'CABRERA', 'VIDAL', 'CAMPOS', 'VEGA', 'FUENTES', 'CARRASCO', 'REYES', 'DIEZ', 'CABALLERO', 'NIETO', 'AGUILAR', 'SANTANA', 'PASCUAL', 'HERRERO', 'MONTERO', 'HIDALGO', 'GIMENEZ', 'LORENZO', 'IBAÑEZ', 'VARGAS', 'SANTIAGO', 'DURAN', 'FERRER', 'BENITEZ', 'MORA', 'ARIAS', 'VICENTE', 'CARMONA', 'CRESPO', 'ROMAN', 'SOTO', 'PASTOR', 'VELASCO', 'SAEZ', 'ROJAS', 'MOYA', 'PARRA', 'SOLER', 'BRAVO', 'GALLARDO', 'ESTEBAN', ];
    DB::beginTransaction();
    try {
        $name = $nombres[rand(0,count($nombres)-1)];
        $apell = $apellidos[rand(0,count($apellidos)-1)] . ' ' . $apellidos[rand(0,count($apellidos)-1)]; 
        $username = $name.rand(1001,9999);
        $email = mb_strtolower($username);
        $email = str_replace(' ', '_', $email);

        $pass = 'pass321';
        $user = User::Create([
            'email' => $email.'@example.com',
            'name' => $username,
            'password' => bcrypt($pass),
            'remember_token' => Str::random(60),
            'role_id' => $rol,
        ]);
        $user->save();
        
        $persona = Persona::firstOrCreate(
            ['NIF_NIE' => generarDNI() ],
            [
                'nombre' => $name,
                'apellidos' => $apell,
                'estado' => 1,
                'avatar_URL' => 'default.svg'
            ]
        );
        $persona->save();
        $userPersona = UserPersona::firstOrCreate(
            [
                'user_id' => $user->id,
                'persona_id' => $persona->id
            ]
        );
        $userPersona->save();
        // printf( "---------------------------------------------\n");
        printf( "creado usuario: ". $email."@example.com \n" );
        //  printf( "pass: '. $pass . ' nif:' .$persona->NIF_NIE. "\n" );
        DB::commit();

        return $userPersona;
        
    } catch (\Throwable $th) {

        printf( "fallo: " . $th->getMessage(). "\n" );
        customLoggin(
            config('ctes.log_levels.error'),
            config('ctes.log_types.create_or_update'),
            ['file' => $th->getFile(), 'line' => $th->getLine() ],
            // PHP_EOL . json_encode($request->all(), JSON_PRETTY_PRINT),
            PHP_EOL . 'Fallo al crear usario ' . $th->getMessage()
        );
        
        DB::rollBack();
    }
    
}

function borrarUsuario($userPersona){
    // obtener user id 
    // obtenver persona id 
    // borrar UserPersona
    // borra persona
    // borrar user
}

/**
 * Undocumented function
 *
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
 * @param DiccionarioPersonalEntrada $entrada
 * @param String $lipsum
 * @param int $orden
 * @param bool $conFoto
 *
 * @return DiccionarioPersonalAcepcion
 */
function crearAcepcion( DiccionarioPersonalEntrada $entrada, String $lipsum, int $orden, bool $conFoto) {

    $ini = rand(0,strlen($lipsum));
    $fin = rand($ini,strlen($lipsum));
    $textoAcepcion = mb_substr($lipsum,$ini,$fin);
    if (strlen($textoAcepcion)>600) {
        $textoAcepcion = mb_substr($textoAcepcion,0, 600);
    }
    
    $iniIdioma_palabra = rand(0,strlen($lipsum));
    $finIdioma_palabra = rand($ini,strlen($lipsum));
    $idioma_palabra = mb_substr($lipsum,$iniIdioma_palabra,$finIdioma_palabra);
    if (strlen($idioma_palabra)>20) {
        $idioma_palabra = mb_substr($textoAcepcion,0, 20);
    }

    $datosAcepcion = new \stdClass();
    $datosAcepcion->dic_entrada_id = $entrada->id;
    $datosAcepcion->definicion = $textoAcepcion;

    $datosAcepcion->orden = $orden;
    $datosAcepcion->cat_gramatical_id = rand(1,10) ;
    $datosAcepcion->cat_gramatical_id = $datosAcepcion->cat_gramatical_id==10 ? null : $datosAcepcion->cat_gramatical_id;
    $datosAcepcion->genero_id = rand(10,15);
    $datosAcepcion->genero_id = $datosAcepcion->genero_id==15 ? null : $datosAcepcion->genero_id;
    $datosAcepcion->numero_id = rand(15,17);
    $datosAcepcion->numero_id = $datosAcepcion->numero_id==17 ? null : $datosAcepcion->numero_id;
    // $datosAcepcion->tipologia_id = 1;
    $datosAcepcion->idioma_palabra = $idioma_palabra;
    $datosAcepcion->idioma_id = rand(56,100);
    $datosAcepcion->frase_ejemplo = Str::random(5);
    // $acepcion->estado = config('ctes.estados.activo');
    
    $acepcion = dpAcepcion_Add($datosAcepcion);
    printf(',');

    // agregar imagen random 
    try {
        if ($conFoto) {
            $radomImageUrl = 'https://source.unsplash.com/random/250x187';
            $contents = file_get_contents($radomImageUrl);
            $name = Str::random(40). '.jpg';
            $file = '/tmp/' . $name;
            // printf("\n creando archivo $name en $file");
            file_put_contents($file, $contents);
            $uploaded_file = new HttpUploadedFile($file, $name);        
            // printf("\n $uploaded_file->getClientOriginalName()  \n ");
            $acepcionMedioImagen = dpMedio_TratarMedio($uploaded_file, config('ctes.tipos_medios.imagen'), $entrada->id, $acepcion->id);
            printf('f');
        }        
    } catch (\Throwable $th) {
        printf( "\nfallo: " . $th->getMessage(). "\n" );
        customLoggin(
            config('ctes.log_levels.error'),
            config('ctes.log_types.create_or_update'),
            ['file' => $th->getFile(), 'line' => $th->getLine() ],
            PHP_EOL . json_encode($acepcion->all(), JSON_PRETTY_PRINT),
            PHP_EOL . 'Fallo al crear imagen para la acepcion ' . $th->getMessage()
        );
    }
    

    return $acepcion;
}

function crearEntradaDP($diccionarioId, $conFotos)
{
    
    $obrasGaldos = [ 
        "La Fontana de Oro", "La sombra ", "El audaz", "Doña Perfecta", "Gloria", "Marianela", "La familia de León Roch", "La desheredada", "El amigo Manso", "El doctor Centeno", "Tormento", "La de Bringas", "Lo prohibido", "Fortunata y Jacinta", "Celín, Tropiquillos y Theros", "Miau", "La incógnita", "Torquemada en la hoguera", "Realidad", "Ángel Guerra", "Tristana", "La loca de la casa", "Torquemada en la cruz", "Torquemada en el purgatorio", "Torquemada y San Pedro", "Nazarín", "Halma", "Misericordia", "El abuelo", "Casandra", "El caballero encantado", "La razón de la sinrazón", "Trafalgar", "La Corte de Carlos IV", "El 19 de marzo y el 2 de mayo", "Bailén", "Napoleón en Chamartín", "Zaragoza", "Gerona", "Cádiz", "Juan Martín el Empecinado", "La batalla de los Arapiles", "El equipaje del rey José", "Memorias de un cortesano de 1815", "La segunda casaca", "El Grande Oriente", "7 de julio", "Los cien mil hijos de San Luis", "El terror de 1824", "Un voluntario realista", "Los Apostólicos", "Un faccioso más y algunos frailes menos", "Zumalacárregui", "Mendizábal", "De Oñate a la Granja", "Luchana", "La campaña del Maestrazgo", "La estafeta romántica", "Vergara", "Montes de Oca", "Los Ayacuchos", "Bodas reales", "Las tormentas del 48", "Narváez", "Los duendes de la camarilla", "La revolución de julio", "O'Donnell", "Aita Tettauen", "Carlos VI en la Rápita", "La vuelta al mundo en la Numancia", "Prim", "La de los tristes destinos", "España sin rey", "España trágica", "Amadeo I", "La Primera República", "De Cartago a Sagunto", "Cánovas", "Episodios nacionales para niños", "Quién mal hace, bien no espere", "La expulsión de los moriscos", "Un joven de provecho", "Realidad", "La loca de la casa", "Gerona", "La de San Quintín", "Los condenados", "Voluntad", "Doña Perfecta", "La fiera", "Electra", "Alma y Vida", "Mariucha", "El abuelo", "Bárbara", "Amor y ciencia", "Zaragoza", "Pedro Minio", "Casandra", "Celia en los infiernos", "Alceste", "Sor Simona", "El tacaño Salomón", "Santa Juana de Castilla", "Antón Caballero", "Crónicas de Portugal", "La casa de Shakespeare", "«Discurso de ingreso en la Real Academia Española»", "Santillana", "Memoranda", "Memorias de un desmemoriado (autobiografía)", "Política española I", "Política española II", "Arte y crítica", "Fisonomías sociales", "Nuestro teatro", "Cronicón 1883 a 1886", "Toledo", "Viajes y fantasías", "Crónica de Madrid", "Cartas a Mesonero Romanos", "Crónica de la Quincena", "Madrid", "Los prólogos de Galdós", "Un viaje redondo por el bachiller Sansón Carrasco", "Tertulias de ''El Ómnibus''", "Una noche a bordo", "Una industria que vive de la muerte", "Crónicas futuras de Gran Canaria", "Necrología de un prototipo", "Manicomio político social", "La conjuración de las palabras", "Dos de mayo de 1808, dos de septiembre de 1870", "Un tribunal literario", "El artículo de fondo", "La mujer del filósofo", "La novela en el tranvía", "La pluma en el viento o el viaje de la vida", "Aquel", "Una historia que parece cuento o un cuento que parece historia", "La mula y el buey", "La princesa y el granuja", "Theros", "Junio", "Tropiquillos", "Celín", "¿Dónde está mi cabeza?", "El pórtico de la gloria", "Rompecabezas", "Fumándose las colonias", "Ciudades viejas. El Toboso" 
        ];

    $canarylipsum = 
        "Cuando el tren mixto descendente, núm. 65 (no es preciso nombrar la línea), se detuvo en la pequeña estación situada entre los kilómetros 171 y 172, casi todos los viajeros de segunda y tercera clase se quedaron durmiendo o bostezando dentro de los coches, porque el frío penetrante de la madrugada no convidaba a pasear por el desamparado andén. El único viajero de primera que en el tren venía bajó apresuradamente, y dirigiéndose a los empleados, preguntoles si aquel era el apeadero de Villahorrenda. (Este nombre, como otros muchos que después se verán, es propiedad del autor.) -En Villahorrenda estamos -repuso el conductor, cuya voz se confundía con el cacarear de las gallinas que en aquel momento eran subidas al furgón-. Se me había olvidado llamarle a Vd., señor de Rey. Creo que ahí le esperan a Vd. con las caballerías. -¡Pero hace aquí un frío de tres mil demonios! -dijo el viajero envolviéndose en su manta-. ¿No hay en el apeadero algún sitio dónde descansar y reponerse antes de emprender un viaje a caballo por este país de hielo? No había concluido de hablar, cuando el conductor, llamado por las apremiantes obligaciones de su oficio, marchose, dejando a nuestro desconocido caballero con la palabra en la boca. Vio este que se acercaba otro empleado con un farol pendiente de la derecha mano, el cual movíase al compás de la marcha, proyectando geométrica serie de ondulaciones luminosas. La luz caía sobre el piso del andén, formando un zig-zag semejante al que describe la lluvia de una regadera. -¿Hay fonda o dormitorio en la estación de Villahorrenda? -preguntó el viajero al del farol. -Aquí no hay nada -respondió este secamente, corriendo hacia los que cargaban y echándoles tal rociada de votos, juramentos, blasfemias y atroces invocaciones que hasta las gallinas escandalizadas de tan grosera brutalidad, murmuraron dentro de sus cestas. -Lo mejor será salir de aquí a toda prisa   -dijo el caballero para su capote-. El conductor me anunció que ahí estaban las caballerías. Esto pensaba, cuando sintió que una sutil y respetuosa mano le tiraba suavemente del abrigo. Volviose y vio una oscura masa de paño pardo sobre sí misma revuelta y por cuyo principal pliegue asomaba el avellanado rostro astuto de un labriego castellano. Fijose en la desgarbada estatura que recordaba al chopo entre los vegetales; vio los sagaces ojos que bajo el ala de ancho sombrero de terciopelo viejo resplandecían; vio la mano morena y acerada que empuñaba una vara verde, y el ancho pie que, al moverse, hacía sonajear el hierro de la espuela. -¿Es Vd. el Sr. D. José de Rey? -preguntó echando mano al sombrero. -Sí; y Vd. -repuso el caballero con alegría- será el criado de doña Perfecta que viene a buscarme a este apeadero para conducirme a Orbajosa. -El mismo. Cuando Vd. guste marchar... La jaca corre como el viento. Me parece que el señor D. José ha de ser buen jinete. Verdad es que a quien de casta le viene... -¿Por dónde se sale? -dijo el viajero con impaciencia-. Vamos, vámonos de aquí, señor... ¿Cómo se llama Vd.? -Me llamo Pedro Lucas -respondió el del paño pardo, repitiendo la intención de quitarse el sombrero- pero me llaman el tío Licurgo. ¿En dónde está el equipaje del señorito? -Allí bajo el reloj lo veo. Son tres bultos. Dos maletas y un mundo de libros para el Sr. D. Cayetano. Tome Vd. el talón. Un momento después señor y escudero hallábanse a espaldas de la barraca llamada estación, frente a un caminejo que partiendo de allí se perdía en las vecinas lomas desnudas, donde confusamente se distinguía el miserable caserío de Villahorrenda. Tres caballerías debían transportar todo, hombres y mundos. Una jaca, de no mala estampa, era destinada al caballero. El tío Licurgo oprimiría los lomos de un cuartago venerable, algo desvencijado aunque seguro, y el macho cuyo freno debía regir un joven zagal de piernas listas y fogosa sangre, cargaría el equipaje. Antes de que la caravana se pusiese en movimiento, partió el tren, que se iba escurriendo por la vía con la parsimoniosa cachaza de un tren mixto. Sus pasos, retumbando cada vez más lejanos, producían ecos profundos bajo tierra. Al entrar en el túnel del kilómetro 172, lanzó el vapor por el silbato, y un aullido estrepitoso resonó en los aires. El túnel, echando por su negra boca un hálito blanquecino, clamoreaba como una trompeta, al oír su enorme voz, despertaban aldeas, villas, ciudades, provincias. Aquí cantaba un gallo, más allá otro. Principiaba a amanecer. "
        ;
    // generar palabra random 
    $titulo = $obrasGaldos[rand(0,count($obrasGaldos)-1)] .' '.Str::random(3);
    
    $entrada = dpEntrada_AddToDiccionario( $titulo, $diccionarioId );
    // echo "entrada: $entrada \n";
    $n_acepciones = rand(1,3);
    // echo $n_acepciones."\n";

    for ($j=0; $j < $n_acepciones; $j++) { 
        // $conFoto = ($j == 0)? true : false;
        if ( $conFotos == 'first' && $j==0 ) $conFoto = true;
        else $conFoto = $conFotos;
        $acepcion = crearAcepcion($entrada, $canarylipsum, $j+1, $conFoto );
        $ListaAcepcionMedios = dpEntrada_GetListaAcepcionMediosByAcepcionId($acepcion->id);
        // printf('medios '. json_encode($ListaAcepcionMedios, JSON_PRETTY_PRINT));
    }

    if ($entrada) {
        // echo "\n creada entrada : " . $titulo . "\n";
        return $entrada;
    }
}

function crearDiccionarioAula($persona_id, $titulo)
{
    $codigo = 'TES'.Str::random(3);
    $params = [
        'codigoAleatorio'        => $codigo,
        'persona_id'             => $persona_id,
        'nombreDiccionario'      => $titulo,
        'descripcionDiccionario' => "Desc $titulo",
        'mst_tipo_dic_id'        => 1,
        'nombreDiccionario'      => $titulo .' '. $codigo,
        'ano_ini_curso_escolar'  => getAnoIniCursoEscolar(new Datetime()),
        'maxAcepciones'          => 5,
        'visibilidad'            => config('ctes.estados.activo'),
        'habilitarEnvio'         => config('ctes.estados.activo'),
        'visibilidadComentarios' => config('ctes.estados.activo'),
        'pautasEspecificas'      => 'pautas test',
        'estudio' => null,

    ];
    foreach (CampoEntrada::all() as $mst_campos_entrada) {
        $params[$mst_campos_entrada->id.'_visible'] = config('ctes.estados.activo');
        // $params[$mst_campos_entrada->id.'_obligatorio'] = config('ctes.estados.inactivo');
    }
    
    $dic = daCreateOrUpdateDicAulaByPersonaId( $persona_id, $params );
    if ( $dic instanceof DicAula ){
        // printf("Creado diccionarios $dic->codigo \n");
        return $dic;
    } else {
        printf("error creando diccionairo \n");
        printf( json_encode($dic, JSON_PRETTY_PRINT));
        return $dic;
    }

}
class StressTestUserDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // no ejecutar en produccion
        if (!App::environment('production')) {
        $start = microtime(true);
        // numero de profesores y alumonos que se van a crear:
        $alumnado = 2000;
        $profesorado = 500;

        $rolDocente = Role::where('name', 'docente')->firstOrFail();
        $rolAlumno = Role::where('name', 'alumno')->firstOrFail();
        // $diccionariosAula = [];        
        // Crear un diccionario de aula 
        $dicAula = new \stdClass();
        
        // Cerar 500 Coordinadores
        for ($coord_index=0; $coord_index < $profesorado; $coord_index++) {
            printf("-USUARIO Docente $coord_index ");
            $userPersona = createRandomUser($rolDocente->id);
            // printf("creado usuario $userPersona->user_id  Persona: $userPersona->persona_id \n ");
            if ($userPersona){
                // printf(  json_encode($userPersona, JSON_PRETTY_PRINT) );
                $user_id = $userPersona->user_id;
                $persona_id = $userPersona->persona_id;
                // creo diccionario personal
                if (! $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id) ) {
                    $diccionario = dpDiccionario_AddDiccionario($persona_id);
                }

                // poner como coordinador del diccionario
                if ( $coord_index==0 ) {
                    // printf(" se va a crear diccionario para $persona_id \n ");
                    $dicAula = crearDiccionarioAula($persona_id, 'Diccionario Test');
                    $docentePrimero_id = $user_id ;
                } else {
                    // printf(" se agrega $persona_id a diccionario test \n ");
                    daDiccionarioAulaUnirUsuario($dicAula, $persona_id);
                }

                printf('Entradas');
                for ($j=0; $j < 5; $j++) { 
                    $conFoto = false;
                    if( $coord_index==0 ) $conFoto = true;
                    $entrada = crearEntradaDP($diccionario->id, $conFoto);
                    dpEnvio_EnviarEntradaDicPersonalADicAula( $entrada, $dicAula );
                    // printf( "\nenviada entrada a $entrada->entrada a $dicAula->titulo\n");
                    printf('.');
                }
                printf("\n");

            } else {
                printf("ERROR CREANDO ENTRADAS\n");
            }
        }


        // Crear 2000 participantes 
        for ($i=1; $i < $alumnado; $i++) {
            printf("-USUARIO $i ");
            $userPersona = createRandomUser($rolAlumno->id);
            // printf("creado usuario $userPersona->user_id  Persona: $userPersona->persona_id \n ");
            if ($userPersona){
                // printf(  json_encode($userPersona, JSON_PRETTY_PRINT) );
                $user_id = $userPersona->user_id;
                $persona_id = $userPersona->persona_id;
                // creo diccionario personal
                if (! $diccionario = dpDiccionario_GetDiccionarioByPersonaId($persona_id) ) {
                    $diccionario = dpDiccionario_AddDiccionario($persona_id);
                }

                // Unir al usuario al diccionario de aula 
                $dicAulaParticipante = daDiccionarioAulaUnirUsuario($dicAula, $persona_id);

                // crear 20 entradas por participante
                printf('Entradas');
                for ($j=0; $j < 20; $j++) { 
                    $conFotos = ($j==0); // solo fotos en la primera entrada
                    $entrada = crearEntradaDP($diccionario->id, $conFotos);
                    // enviar entradas 
                    // dpEnvio_EnviarEntradaADiccionarioAula($dp_envio_id, $entrada)
                    dpEnvio_EnviarEntradaDicPersonalADicAula( $entrada, $dicAula );
                    // printf( "\nenviada entrada a $entrada->entrada a $dicAula->titulo\n");
                    printf('.');
                }
                printf("\n");
                // printf(" Creadas entradas  usuario $i \n");
                
            } else {
                printf("ERROR CREANDO ENTRADAS\n");
            }
        }

        // agergar a docente@docente como coordinador
        try {
            $docente = User::where('email', 'docente@docente.com')->get()->first();
            $docentePersona = UserPersona::where('user_id', $docente->id)->get()->first();
            daDiccionarioAulaUnirUsuario($dicAula, $docentePersona->persona_id);
        } catch (\Throwable $th) {
            //throw $th;
            echo $th->getMessage();
            echo "";
        }
        

        
        $docentePrimero = User::find($docentePrimero_id);

        printf("Dic: $dicAula->codigo Email docente: $docentePrimero->email \n");

        $time_elapsed_secs = microtime(true) - $start;
        printf("tiempo ejecucion: " . gmdate("H:i:s", $time_elapsed_secs ) . "\n" );
        
        } // fin si no esta produccion if(!App::environment('production'))
    }
}
