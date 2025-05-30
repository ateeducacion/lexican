<?php
$ctes = [

    /*
    |--------------------------------------------------------------------------
    | Niveles de log
    |--------------------------------------------------------------------------
    |
    | Definición de los niveles de log que podrán ser usados
    |
    */
    'log_levels' => [
        'debug'     => 'debug',
        'info'      => 'info',
        'warning'   => 'warning',
        'error'     => 'error',
        'critical'  => 'critical'
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de eventos de log
    |--------------------------------------------------------------------------
    |
    | Definición de los tipos de log que podrán ser usados
    |
    */
    'log_types' => [
        'routeInfo'            => 'routeInfo',
        'create_or_update'     => 'createOrUpdate',
        'data_conflict'        => 'dataConflict',
        'data_base_error'      => 'dataBaseError',
        'action_not_available' => 'actionNotAvailable',
        'sync_error'           => 'syncError',
        'get_external_token'   => 'getExternalToken',
        'external_error'       => 'externalError',
        'info'                 => 'info',
    ],




    /*
    |--------------------------------------------------------------------------
    | estados
    |--------------------------------------------------------------------------
    |
    | Constantes que indican si el objeto está activo o inactivo
    |
    */
    'estados' => [
        'inactivo'   => '0',
        'activo'     => '1',
    ],

 /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    |
    | Constantes de la tabla roles
    |
    |
    | OJO: Esto en local instalando la bbdd de 0 es docente 3 y alumno 4 , pero
    | en producción es docente 1 y alumno 2
    |
    */
    'rol' => [
        // pre y pro
        'docente'   => '1',
        'alumno'     => '2',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | tipos_medios
    |--------------------------------------------------------------------------
    |
    | Constantes que indican qué tipo de medio es de una acepción
    |
    */
    'tipos_medios' => [
        'imagen'     => '1',
        'audio'      => '2',
        'video'      => '3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Directorios para los tipos de medios
    |--------------------------------------------------------------------------
    |
    | Constantes que indican qué tipo de medio es de una acepción
    |
    */
    'path_medios' => [
        'imagen'     => 'dp/medios/imagenes',
        'audio'      => 'dp/medios/audios',
        'video'      => 'dp/medios/videos',
        'avatares'   => 'avatares/oficiales', // lleva a storage/app/public/avatares/oficiales
        'documentos' => 'documentos', // Pdf Pautas Generales y que hacer..
        'tinyUploads' => 'tinyUploads', // imagenes subidas con tinyMce
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de imagenes
    |--------------------------------------------------------------------------
    |
    | Tipos de imagenes aceptadas en el formulario de acepciones
    |
    */
    'extensiones_medios' => [
        'imagen'     => 'jpg jpeg jpe gif png bmp tif tiff ico',
        'audio'      => 'mp3 ogg wav', // 'mp3 m4a m4b ra ram wav ogg oga mid midi wma mka',
        'video'      => 'mp4 ogg webm', // 'asf asx wax wmv wmx avi divx flv mov qt mpeg mpg mpe mp4 m4v ogv mkv',
    ],


    /*
    |--------------------------------------------------------------------------
    | Tamaños Entradas
    |--------------------------------------------------------------------------
    |
    | Constantes de los campos de entrada
    |
    */
    'constantes_entradas' => [
        'max_entrada'   => 150,
    ],


    /*
    |--------------------------------------------------------------------------
    | Tamaños Acepciones
    |--------------------------------------------------------------------------
    |
    | Constantes de los campos de acepción
    |
    */
    'constantes_acepciones' => [
        'max_definicion'   => 1000,
        'max_frase'         => 255,
    ],


    /*
    |--------------------------------------------------------------------------
    | primer_registro
    |--------------------------------------------------------------------------
    |
    | Constante que indica el número del primer id de registro a insertar
    |
    */
    'primer_registro' => '1',

    /*
    |--------------------------------------------------------------------------
    | campos_acepcion
    |--------------------------------------------------------------------------
    |
    | Constantes que se usan para cargar los combos de la acepción
    |
    */
    'campos_acepcion' => [
        'categoria'  => '1',
        'genero'     => '2',
        'numero'     => '3',
        'tematica'   => '4',
        'idioma'     => '9',
    ],

    /*
    |--------------------------------------------------------------------------

    | campos de diccionario de un aula
    |--------------------------------------------------------------------------
    |
    | Constantes que se usan en el diccionario de un aula
    |
    */
    'dic_aula' => [
        'max_acepciones'  => '10',
    ],


    /*
    | Abreviaturas
    |--------------------------------------------------------------------------
    |
    | Abreviaturas de los atributos de las acepciones. Por defecto se tomará como abreviatura los primeros abreviatura_tamano caracteres, salvo que exista una constante definida en abreviatura_personalizada
    |
    */
    'abreviatura_tamano' => 4,
    
    'abreviatura' =>
    [
        'Sustantivo'   => 'sust.',
        'Adjetivo'     => 'adj.',
        'Adverbio'     => 'adv.',
        'Conjunción'   => 'conj.',
        'Determinante' => 'det.',
        'Interjección' => 'interj.',
        'Preposición'  => 'prep.',
        'Pronombre'    => 'pron.',
        'Verbo'        => 'v.',
        'Masculino'    => 'm.',
        'Femenino'     => 'f.',
        'Neutro'       => 'n.',
        'Masculino y femenino' => 'm. y f.',
        'Singular' => 'sing.', 
        'Plural' => 'pl.',
    ],
    // Ids campos valores comparara con seeder por si se ha cambiado
    // siempree pueden diferir si se cambia en la bbdd
    'campos_valores' => [
        'categoria_gramatical' => [
            'Adjetivo' => 1, 
            'Sustantivo' => 2, 
            'Verbo' => 3, 
            'Preposición' => 4, 
            'Conjunción' => 5, 
            'Adverbio' => 6, 
            'Determinante' => 7, 
            'Interjección' => 8, 
            'Pronombre' => 9, 
        ],
        'genero' => [
            'Femenino'  => 10,
            'Masculino' => 11,
            'Masculino y femenino' => 12,
            'Neutro' => 13,
            'No tiene' => 14,
        ],
        'numero' => [
            'Singular' => 15,
            'Plural' => 16,
        ],
    ],
    // //  creo que estan mal por que los ids no coinciden con el nombre
    'abreviatura_personalizada' => [
        // '13'    => 'ef', // educacion_fisica
        '8'     => 'fem', // femenino
        '10'    => 'masc. y fem', // masculino y femenino
    ],


    /*
    |--------------------------------------------------------------------------
    | Thumbnail
    |--------------------------------------------------------------------------
    |
    | Sufijo para los ficheros de thumbnail de videos
    |
    */
    'video_thumbnail_sufijo'    => '_thumb',
    'imagen_thumbnail_tamano'   => 250,

    /*
    |--------------------------------------------------------------------------
    | Estados de la entradas y acepciones
    |--------------------------------------------------------------------------
    |
    | Constantes que se usan para los estados de las entradas y acepciones 
    |
    */
    'estados_entrada' => [
        'borrado_logico'    => '0',
        'visible'           => '1',
        'oculta'            => '2',
    ],


    /*
    |--------------------------------------------------------------------------
    | Estados de los envíos y envíos_entradas
    |--------------------------------------------------------------------------
    |
    | Constantes que se usan para los estados de los envíos y envíos_entradas
    |
    */
    'estados_envios' => [
        'borrado_logico'        => '0', // sólo para envíos_entradas
        'enviado'               => '1',
        'borrado_estudiante'    => '2', // No se usa. Pendiente de que lo use Inma
        'publicado'             => '3',
    ],


    /*
    |--------------------------------------------------------------------------
    | Resultados de funciones
    |--------------------------------------------------------------------------
    |
    | Constantes que se devuelven al ejecutar funciones
    |
    */
    'resultados' => [
        'entrada_borrada'   => '1',
        'entrada_enviada'   => '2',
        'entrada_publicada' => '3',
        'error_generico'    => '4',
        'error_entrada_previamente_borrada' => '5',
    ],



    /*
    |--------------------------------------------------------------------------
    | Configuración dropify 
    |--------------------------------------------------------------------------
    |
    | Constantes que se usan en los dropify de imagen, audio y video en el fichero dpAcepcionFormulario.blade
    | https://github.com/JeremyFagis/dropify
    |
    */
    'dropify' => [
        'image_max-file-size-preview'   => env('UPLOAD_MAXSIZE', '200M'),
        'image_data-max-file-size'      => env('UPLOAD_MAXSIZE', '200M'),
        'audio_data-max-file-size'      => env('UPLOAD_MAXSIZE', '200M'),
        'video_data-max-file-size'      => env('UPLOAD_MAXSIZE', '200M'),
    ],



    /*
    |--------------------------------------------------------------------------
    | Estados envío de Diccionarios de aula
    |--------------------------------------------------------------------------
    |
    | Indica el estado del envío en un diccionario de aula. Estudia el estado y la fecha de inicio del envío
    |
    */
    'estado_envio_habilitado' => [
        'inactivo'      => '0',
        'activo'        => '1',
        'planificado'   => '2',
    ],


    /*
    |--------------------------------------------------------------------------
    | Estados Comentarios de entrada
    |--------------------------------------------------------------------------
    |
    | Indica el estado del los comentarios a una entrada del diccionario de aula
    |
    */
    'estado_comentario_entrada' => [
        'no_visible'        => '0',
        'visible_no_leido'  => '1',
        'visible_si_leido'  => '2',
    ],



    /*
    |--------------------------------------------------------------------------
    | Estados del campo comentarios_visibles de Diccionarios de aula
    |--------------------------------------------------------------------------
    |
    | Indica si el diccionario de aula se puede seleccionar o no, y si sí se puede seleccionar pero sólo los comentarios recientes
    |
    */
    'comentarios_visibles' => [
        'no_visible'    => '0',
        'visible'       => '1',
        'anteriores_a'  => '2',
    ],


    /*
    |--------------------------------------------------------------------------
    | Numero entradas Scroll Infinito 
    |--------------------------------------------------------------------------
    |
    | Indica el numero de Entradas que se cargan por vez
    |
    */
    'scrollEntradas' => 10,

    /**
     * ---------------------------------------------------------
     * Nombre de las rutas para acceder a ellas
     */
    'rutas' => [
        'aula' => [
            'habilitarParticipante' => 'diccionarioaula.habilitarParticipante',
            'deshabilitarParticipante' => 'diccionarioaula.deshabilitarParticipante',
            'habilitarParticipanteAdmin' => 'diccionarioaula.habilitarParticipanteAdmin',
            'deshabilitarParticipanteAdmin' => 'diccionarioaula.deshabilitarParticipanteAdmin'
        ]
    ],


    /*
    |--------------------------------------------------------------------------
    | Opciones al enviar comentario de dicAula 
    |--------------------------------------------------------------------------
    |
    | Indica si se quiere dar de alta un nuevo comentario o editar el último
    |
    */
    'comentario_dicAula_opts' => [
        'opt_nuevo_comentario'  => 'opt_nuevo_comentario',
        'opt_editar_comentario' => 'opt_editar_comentario',
    ],


    /*
    |--------------------------------------------------------------------------
    | Valores de origen 
    |--------------------------------------------------------------------------
    |
    | Indica si se quiere dar de alta un nuevo comentario o editar el último
    |
    */
    'origen' => [
        'envio'  => '1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Valores de multiples
    |--------------------------------------------------------------------------
    |
    | Indica los valores de los multiples
    |
    */
    'multi' => [
        'multienseñanza' => '0',
        'multiareamateria' => '0',
        'multiestudio' => '0',
        'multigrupo' => '0',
        'estudiofinal' => 'ME' 
    ],

    /*
    |--------------------------------------------------------------------------
    | Valores visibilidad de los campos de entradas del diccionario
    |--------------------------------------------------------------------------
    |
    | Indica los valores de los multiples
    |
    */
    'visibilidad' => [
        'no_visible' => '0',
        'visible' => '1',
    ],

    'mst_campos_entrada' => [
        'categoria_gramatical' => 1,
        'genero' => 2,
        'numero' => 3, 
        'tematicas_generales' => 4,
        'frase_ejemplo' => 5,
        'video' => 6,
        'audio' => 7,
        'imagen' => 8,
        'lengua_idioma' => 9,
        'ejemplo2' => 10
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles Web Service CAU_CE
    |--------------------------------------------------------------------------
    |
    | Constantes que se usarán en la respuesta a la llamada al Web Service del CAU_CE
    |
    */
    'roles_ws_cauce' => [
        'id_rol_docente_pincel' => '1',
        'id_rol_alumnado_pincel' => '3',
        'id_rol_docente_centro_profesorado' => '4',
        'id_rol_tecnicos_educativos' => '5',
    ],

    /**
     *  Maximo vigencia
     */
    'vigencia_max' => env('VIGENCIA_MAX', '10'),
    'inicio_curso' => env('INICIO_CURSO', '30/8/2000'), // dia / mes
    'tematicas_max'=> env('TEMATICAS_MAX', '10'),
    
];
    
return $ctes;