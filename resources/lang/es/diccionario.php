<?php

return [

    // Generales
    'Seleccionar' => 'Seleccionar',
    'Restablecer' => 'Restablecer',

    /*
    |--------------------------------------------------------------------------
    | Diccionario Personal
    |--------------------------------------------------------------------------
    |
    */
    'ERROR' => 'Se ha producido un error',


    'URLmala' => 'No existe o no tiene permiso para acceder a esta información',
    'URLnoExiste' => 'No existe la entrada con id ',

    // 'entrada_id.required' => 'No se ha recibido el identificador de la entrada',
    // 'entrada_entrada.required' => 'Debe especificar una entrada',
    // 'entrada_entrada.max' => 'La entrada tiene una longitud máxima de ',
    // 'definicion.required' => 'Debe especificar una definición',
    // 'definicion.max' => 'La definición tiene una longitud máxima de ',
    // 'max_frase.max' => 'La frase de ejemplo tiene una longitud máxima de ',

    'entrada_existe' => 'No se puede modificar la entrada porque ya existe otra con ese mismo texto',
    'entrada_borrada' => 'Se ha borrado la entrada',
    'entrada_publicada' => 'No se puede borrar la entrada porque ya se ha publicado',
    'entrada_pubicar' => 'La entrada se ha publicado',
    'entrada_publicada_diccionario' => 'Contacta con la persona administradora del diccionario de aula para que anule la publicación de la entrada',
    'entrada_no_pubicar' => 'La publicación de la entrada se ha anulado',
    'entrada_ocultada' => 'Se ha ocultado la entrada',
    'entrada_visible' => 'Se ha recuperado la visibilidad de la entrada',
    'entradas_visibles' => 'Para recuperar la visibilidad de la entrada pulsa en el icono "ojo" de la entrada',
    'acepcion_borrada' => 'Se ha borrado la acepción',
    'acepcion_ocultada' => 'Se ha ocultado la acepción',
    'acepcion_visible' => 'Se ha recuperado la visibilidad de la acepción',
    'acepcion_editar' => 'Editar acepción',


    // Mensajes de confirmación
    'confirm_entrada_enviada' => 'La entrada ":entrada" ha sido enviada al diccionario de aula. ¿Estás seguro o segura de querer eliminarla? Si confirmas el borrado, la entrada se borrará sin posibilidad de recuperarla.',
    'confirm_entrada_borrar' => '¿Estás seguro o segura de borrar la entrada?',
    'confirm_acepcion_borrar' => '¿Estás seguro o segura de borrar la acepción?',
    'confirm_acepcion_borrar_entrada' => 'Al borrar esta acepción se va a borrar la entrada. ¿Estás seguro o segura?',
    'confirm_acepcion_borrar_info' => '¿Estás seguro o segura de borrar la acepción nº:numero de ":entrada"?',
    'confirm_imagen_borrar' => '¿Estás seguro o segura de borrar la imagen?',
    'confirm_audio_borrar' => '¿Estás seguro o segura de borrar el audio?',
    'confirm_video_borrar' => '¿Estás seguro o segura de borrar el vídeo?',
    'confirm_titulo' => 'CONFIRMACIÓN',


    // Entrada - Acepción
    'acepcion_anadir' => 'Añadir acepción',
    'entrada_anadir' => 'Añadir Entrada',
    'acepcion_borrar' => 'Borrar acepción',
    'entrada_borrar' => 'Borrar entrada',
    'entrada_anadir_placeholder' => "Escribe aquí una entrada y pulse ...",
    // 'entrada_introduce_datos' => "Introduce los datos para  ",
    'entrada_acepciones_anadidas' => "Acepciones añadidas",
    'entrada_entrada' => 'Añadir una nueva entrada:',
    'entrada_acepcion_anadida' => "Se ha añadido la acepción para ",
    'entrada_pulsa_anadir_acepcion_1' => "Pulse añadir ",
    'entrada_pulsa_anadir_acepcion_2' => " o escriba una nueva entrada",
    'entrada_existe_1' => "La entrada ",
    'entrada_existe_2' => " ya existe",
    'entrada_crear' => " Crear entrada",
    'acepcion_borrar_imagen' => "Borrar imagen",
    'acepcion_borrar_audio' => "Borrar audio",
    'acepcion_borrar_video' => "Borrar vídeo",
    'acepcion_tematica_anadir' => "Añadir",
    'acepcion_tematica_quitar' => "Quitar",

    // Formulario acepción
    'acepcion_seleccione_valor' => '--Seleccionar--',

    'acepcion_orden' => 'Orden Acepción',
    'acepcion_categoria' => 'Categoría Gramatical',
    'acepcion_genero' => 'Género',
    'acepcion_numero' => 'Número',
    'acepcion_tematica' => 'Etiquetas',
    'acepcion_idioma' => 'Lengua',
    'acepcion_definicion' => 'Definición',
    'acepcion_masdatos' => 'Más datos',
    'acepcion_ejemplouso' => 'Ejemplo de uso',
    'acepcion_placeholder_maximo' => '(Máximo :Max Caracteres)',
    'acepcion_creacion' => "Creación de nueva acepción",
    'acepcion_edicion' => "Edición de acepción",
    'acepcion_otro_idioma' => 'Lengua',
    'acepcion_idioma_traduccion' => 'Traducción',

    'acepcion_dropify_arrastra_click' => 'Arrastra y suelta un archivo aquí o haz clic',
    'acepcion_dropify_msg_max_size' => 'Peso máximo permitido: :size',
    'acepcion_dropify_arrastra_reemplazar' => 'Arrastra y suelta un archivo o haz clic para reemplazar',
    'acepcion_dropify_eliminar' => 'Eliminar',
    'acepcion_dropify_error' => 'Lo sentimos, ha ocurrido un error',
    'acepcion_dropify_grande' => 'El fichero es demasiado grande. Máximo ',
    'acepcion_dropify_no_permitido' => 'El tipo de fichero no está permitido. Los permitidos son ',

    'acepcion_guardar' => 'GUARDAR',
    'acepcion_cancelar' => 'CANCELAR',
    'acepcion_enviar' => 'ENVIAR',

     // Campos formulario acepción
     'campo_definicion' => 'El campo definición es obligatorio.',

    // abecedario
    'Por_Letra' => 'Por Letra',
    'Inicio' => 'Inicio',
    'diccionario_personal' => 'Diccionario Personal',
    'diccionario_aula' => 'Diccionario de Aula',
    'Busqueda' => 'Búsqueda',

    // nentradas
    'nentradas_ver_todas' => 'Ver todas las entradas',
    'nentradas_cero_entradas' => 'No hay entradas en este diccionario',

    // Buscador
    'buscar_coincide' => 'Se encontró una coincidencia exacta',
    'buscar_noencotrado' => 'No existe la entrada',
    'buscar_ceroEntradas' => 'No se encontró ningún resultado',
    'buscar_titulo' => 'ENTRADAS EN ESTE DICCIONARIO',


    // Iconos boton-icono
    'icono_borrar' => 'Borrar',
    'icono_editar' => 'Ir a edición de entrada',
    'icono_enviar' => 'Enviar',
    'icono_vercomentarios' => 'Ver comentarios docente',
    'icono_ocultar' => 'Ocultar',

    // consulta buscar entradas
    'Buscar' => 'Buscar',

    // Acepcion
    'acepcion_leermas' => 'Leer más...',
    // Menu Envíos y comentarios
    'unirse_dic' => 'UNIRSE A DICCIONARIO',
    'enviar_entradas' => 'ENVIAR ENTRADAS',
    'ver_comentarios' => 'VER COMENTARIOS',
    // Menu Gestion de diccionario
    'modificar_dic' => 'MODIFICAR DICCIONARIO',
    'exportar_pdf' => 'EXPORTAR A PDF',
    'subir_portfolio' => 'SUBIR A PORTAFOLIO',

    // Envío de entrada
    'modal_enviar_entrada_title' => 'ENVIAR ENTRADA',
    'modal_enviar_entrada_oculta_body' => //'',
        '¡Vaya! La entrada está oculta y no se puede enviar.<br />'.
        'Utiliza el icono de ocultar/mostrar '.
        ' <img src=\':imagen\' /> '.
        'para mostrar la entrada y después envíala',

    // Formulario unirse a un diccionario de aula
    'modal_unirse_diccionario_title' => 'UNIRSE A DICCIONARIO DE AULA',
    'modal_unirse_diccionario_body_enviar_entradas' => 'Para enviar entradas de tu diccionario primero debes unirte a un diccionario de aula introduciendo el código que te ha proporcionado el profesorado.',
    'modal_unirse_diccionario_body' => 'Únete a un diccionario de aula introduciendo su código',
    'modal_unirse_diccionario_curso_centro' => 'CENTRO',
    'modal_unirse_diccionario_curso_escolar' => 'CURSO ESCOLAR',
    'modal_unirse_diccionario_curso' => 'nivel Estudio',
    'modal_unirse_diccionario_grupo' => 'grupo',
    'modal_unirse_diccionario_codigo' => 'CÓDIGO',
    'modal_unirse_diccionario_error_noexiste' => 'no existe ningún diccionario de aula con el código indicado para este curso escolar, revisa los datos introducidos o habla con tu profesor/a',
    'modal_unirse_diccionario_error_yaunido' => 'Ya estabas unido o unida al diccionario de aula con código ":codigo"',
    'modal_unirse_diccionario_error_noautorizado' => 'No tiene autorización para unirse, hable con el coordinador o coordinadora de este diccionario',
    'modal_unirse_diccionario_success' => 'Te has unido correctamente al diccionario de aula con el código ":codigo"',


    // Enviar diccionario completo
    'modal_enviar_diccionario_titulo'  => 'ENVIAR DICCIONARIO PERSONAL',
    'modal_enviar_diccionario_tooltip' => 'Enviar el diccionario personal al diccionario de aula',
    'modal_enviar_diccionario_mensaje' => 'Enviar el diccionario personal al diccionario de aula ":diccionario"',
    'modal_enviar_diccionario_noentradas' => 'No se puede enviar el diccionario personal porque no tiene ninguna entrada',


    // Mensaje envío diccionario personal
    'envio_exito' => 'Se han enviado correctamente las entradas del diccionario personal al diccionario de aula ":diccionario"',
    'envio_entrada_exito' => 'Se ha enviado correctamente ":entrada" al diccionario de aula ":diccionario"',
    'envio_error' => 'No ha sido posible enviar el diccionario personal al diccionario de aula ":diccionario"',
    'envio_error_entradas_faltan_datos' => 'a alguna de las entradas enviadas le faltan los siguientes datos:',
    'envio_entrada_error' => 'No ha sido posible enviar ":entrada" al diccionario de aula ":diccionario"',
    'envio_entrada_error_faltan_campos' => 'revise los campos obligatorios',
    'envio_error_no_diccionarios' => 'Debes seleccionar un diccionario de aula',
    'envio_fecha_anterior' => 'último envío realizado el',
    'envio_diccionario_no_enviado' => 'Nunca se ha enviado',
    'envio_ultimo_envio_diccionario' => 'último envío diccionario',
    'envio_entrada_fecha_anterior' => 'último envío de esta entrada realizado el',
    'envio_entrada_no_enviado' => 'nunca se ha enviado esta entrada',


    // Enviar entrada a un diccionario
    'modal_enviar_entrada_un_diccionario' => 'Vas a enviar la entrada ":entrada" al diccionario ":diccionario" de ":nombreprofe"',
    'modal_enviar_entrada_un_diccionario_tooltip' => 'Enviar entrada al diccionario de aula',


    // Formulario seleccionar diccionarios de aula
    'envio_seleccionaraulas' => 'ENVÍO DE DICCIONARIO PERSONAL A DICCIONARIO DE AULA',
    'title_envio_seleccionaraulas' => 'Envío De Diccionario Personal A Diccionario De Aula',
    // TODO: valorar si
    // 'envio_seleccionadiccionarios_diccionario' => 'Selecciona los diccionarios​ de aula ​ a los que quieres enviar las entradas de tu diccionario​ personal'
    'envio_seleccionadiccionarios_diccionario' => 'Selecciona el diccionario al que quieres enviar las entradas de tu diccionario personal',
    'envio_seleccionadiccionarios_entrada' => 'Selecciona los diccionarios a los que quieres enviar la entrada ":entrada"',
    'envio_recuerda' => 'Entradas diccionario. Recuerda que las entradas ocultas (en gris), no se enviarán.',


    // Ocultar Entradas y Acepciones
    'modal_entrada_ocultar_titulo'  => 'OCULTAR ENTRADA',
    'modal_entrada_mostrar_titulo'  => 'MOSTRAR ENTRADA',
    'modal_entrada_ocultar_tooltip' => 'Ocultar entrada',
    'modal_entrada_anular_titulo'   => 'ANULAR PUBLICACIÓN',
    'ultima_acepcion'               => 'No se puede ocultar la última acepción de una entrada, si lo desea puede anular la publicación de la entrada',
    'modal_entrada_anular_tooltip'   => 'Anular publicación',
    'modal_entrada_publicar_tooltip'=> 'Publicar',
    'modal_entrada_eliminar_tooltip'=> 'Eliminar',
    'modal_entrada_mostrar_tooltip' => 'Mostrar entrada',
    'modal_entrada_ocultar'         => '¿Quieres ocultar la entrada ":entrada"?<br />Las entradas ocultas no se podrán enviar al diccionario de aula.',
    'modal_entrada_mostrar'         => '¿Quieres mostrar la entrada ":entrada"?',
    'modal_entrada_anular'          => '¿Quieres anular la publicación de la entrada ":entrada"?',

    'modal_acepcion_ocultar_titulo' => 'OCULTAR ACEPCIÓN',
    'modal_acepcion_mostrar_titulo' => 'MOSTRAR ACEPCIÓN',
    'modal_acepcion_ocultar_tooltip' => 'Ocultar acepción',
    'modal_acepcion_mostrar_tooltip' => 'Mostrar acepción',
    'modal_acepcion_ocultar'        => '¿Quieres ocultar la acepción nº:numero de ":entrada"?<br />Las acepciones ocultas no se podrán enviar al diccionario de aula.',
    'modal_acepcion_mostrar'        => '¿Quieres mostrar la acepción nº:numero de ":entrada"?',


    // Botones
    'boton_aceptar' => 'Aceptar',
    'boton_cancelar' => 'Cancelar',
    'boton_cerrar' => 'Cerrar',
    'boton_volver' => 'VOLVER',
    'boton_descartar' => 'DESCARTAR',


    // Botones de Acciones
    'boton_anadir_entrada' => 'AÑADIR ENTRADA',
    'boton_envios_comentarios' => 'ENVÍOS Y COMENTARIOS',
    'boton_gestion_diccionario' => 'GESTIÓN DE DICCIONARIO',


    // Comentarios
    'comentarios_diccionario_miga' => 'Comentarios al diccionario',
    'comentarios_diccionario_titulo' => 'Ver comentarios del o de la docente',
    'comentarios_diccionario_selecciona' => 'Selecciona el diccionario del que quieras ver los comentarios',
    'comentarios_diccionario_generales' => 'Comentarios generales',
    'comentarios_diccionario_entradas' => 'Comentarios a entradas',
    'comentarios_diccionario_vermas' => 'Ver más.',
    'comentarios_diccionario_vermenos' => 'Ver menos.',
    'comentarios_entrada_entradas' => 'Comentarios a la entrada.',
    'comentarios_entrada_sincomentarios' => 'La entrada no tiene comentarios o no son visibles.',
    'comentarios_diccionario_sincomentarios' => 'No hay comentarios o no son visibles.',
    'comentarios_entrada_tooltip' => 'Comentarios a la entrada',
    'comentarios_entrada_title' => 'Comentarios a la entrada',

    'comentarios_add_config_visibilidad' => 'La visibilidad actual para comentarios es :config_visibilidad para el alumnado',
    'comentarios_add_estudiante' => 'Participante',
    'comentarios_add_diccionario_generales' => 'Comentarios Generales',
    'comentarios_add_opt_nuevo_comentario' => 'Nuevo comentario',
    'comentarios_add_opt_editar_comentario' => 'Editar el último comentario',
    'comentarios_add_comentarios_guardados' => 'Historial de comentarios',
    'comentarios_add_success' => 'Se ha guardado el comentario para el diccionario de :nombre_persona',
    'comentarios_comentarios_visibles_no_visible' => 'No Visible',
    'comentarios_comentarios_visibles_visible' => 'Visible',
    'comentarios_comentarios_visibles_anteriores_a' => 'Anteriores al :fecha',
    'comentarios_add_confirm_borrar' => '¿Está seguro o segura de borrar el comentario?',
    'comentarios_add_borrado' => 'Se ha borrado el comentario',


    // Formulario crear/actualizar diccionario aula
    'tooltip_configurar_visibilidad_comentarios' => 'Permite mostrar (o no) al alumnado los comentarios de las entradas',
    'tooltip_configurar_envios' => 'Permite (o no) enviar entradas desde el diccionario personal al diccionario de aula',
    'tooltip_configurar_visibilidad' => 'Permite (o no) que el alumnado vea el diccionario',
    'tooltip_configurar_envio_entradas' => 'Permite enviar entradas a partir de esta fecha',
    'tooltip_fecha_configurar_visibilidad'       => 'Los comentarios posteriores a esta fecha no estarán visibles',

    // Botones pag inicial Diccionario de aula
    'aulacreate' => 'Crear diccionario de aula',
    'ver_publicar_entradas' => 'Ver y Publicar entradas',
    'comentarios_generales' => 'Comentarios generales',
    'gestionar_aula' => 'Gestionar diccionario de aula',
    'invitar_a_docente' => 'Invitar docente',
    'modificar_dic_aula' => 'Modificar diccionario de aula',
    'exportar_pdf' => 'Exportar pdf',
    'subir_portfolio' => 'Subir portfolio',
    'unirse_dic' => 'Unirse a un diccionario',

    // Crear dic de aula
    'Crear diccionario de aula' => 'Crear diccionario de aula',
    'Modificar diccionario de aula' => 'Modificar diccionario de aula',
    'Propietario' => 'Coordinador o coordinadora',
    'Curso escolar' => 'Curso escolar',
    'Código' => 'Código',
    'Tipo de diccionario' => 'Tipo de diccionario',
    'Seleccione un valor...' => 'Seleccione un valor...',
    'Nombre del diccionario' => 'Nombre del diccionario',
    'Descripción' => 'Descripción',
    'Enseñanza' => 'Enseñanza',
    'Estudio' => 'Estudio',
    'Nivel de Estudio' => 'Nivel de Estudio',
    'Grupo' => 'Grupo',
    'Multigrupo' => 'Multigrupo',
    'Area materia' => 'Área/Materia/Ámbito/Otros',
    'Crear diccionario vacio' => 'Crear diccionario vacío',
    'Importar entradas' => 'Importar entradas',
    'Importar entradas de diccionario de otros cursos' => 'Importar entradas de diccionario de otros cursos',
    'Importar entradas de otro diccionario' => 'Importar entradas de otro diccionario',
    'Configuración entradas' => 'Configuración entradas',
    'Número máximo de acepciones por entrada' => 'Número máximo de acepciones por entrada',
    'Campos visibles en entradas' => 'Campos visibles en entradas',
    'Campos obligatorios en entradas' => 'Campos obligatorios en entradas',
    'Pautas diccionario' => 'Pautas diccionario',
    'Pautas' => 'Pautas',
    'Pautas generales a todos los diccionarios' => 'Pautas generales a todos los diccionarios',
    'Pautas específicas' => 'Pautas específicas',
    'Visibilidad, envíos y avisos' => 'Visibilidad, envíos y avisos',
    'Enviar avisos de envios de entradas al correo electrónico:' => '​Avisar de envíos de entradas al correo
    electrónico:',
    'Visible para el alumnado' => 'Visible para el alumnado',
    'No visible para el alumnado' => 'No visible para el alumnado',
    'Permisos de envío del diccionario del aula' => 'Permisos de envío del diccionario del aula',
    'Permitir envíos de entradas desde diccionario personal' => 'Permitir envíos de entradas desde diccionario personal',
    'No permitir envíos de entradas desde diccionario personal' => 'No permitir envíos de entradas desde diccionario personal',
    'Fecha de inicio para los envios' => 'Fecha de inicio para los envíos',
    'diccionario.tooltip_configurar_visibilidad_comentarios' => 'diccionario.tooltip_configurar_visibilidad_comentarios',
    'Configurar visibilidad de comentarios para el alumnado' => 'Configurar visibilidad de comentarios para el alumnado',
    'No visible' => 'No visibles',
    'Visible' => 'Visibles',
    'Visibles los anteriores a:' => 'No visibles los posteriores al',
    'diccionario.tooltip_fecha_configurar_visibilidad' => 'diccionario.tooltip_fecha_configurar_visibilidad',
    'Fecha para los comentarios' => 'Fecha para los comentarios',
    'Participantes' => 'Participantes',
    'Estudiantes' => 'Estudiantes',
    'Todos los que dispongan del código de diccionario' => 'Quienes dispongan del código de diccionario',
    'Estudiantes unidos al diccionario' => 'Alumnado unido al diccionario',
    'Nombre' => 'Nombre',
    'Apellidos' => 'Apellidos',
    'Estado' => 'Estado',
    'Administracion' => 'Administración',
    // 'Volver' => 'Volver',
    'Profesorado invitado' => 'Profesorado',
    'Profesorado' => 'Profesorado',
    'Email' => 'Email',
    'Añadir profesorado invitado' => 'Añadir profesorado',
    'Modificar diccionario' => 'Modificar diccionario',
    'eliminar_diccionario ' => 'Eliminar diccionario',
    'Crear diccionario' => 'Crear diccionario',
    'Confirmación' => 'Confirmación',
    'Guardar' => 'Guardar',
    'Crear' => 'Crear',
    'Editar' => 'Editar',
    'diccionario_creado_success' => 'Diccionario de aula creado',
    'diccionario_modificado_success' => 'Diccionario de aula modificado',
    'diccionario_borrado_success' => 'Diccionario de aula borrado',
    // fin crear dic de alua


    'ver_video' => 'Ver vídeo',
    'oir_audio' => 'Oír audio',
    'DiccionarioAula' => 'Diccionario de Aula',
    'entradas' => 'entradas',
    'Entradas' => 'Entradas',

    'tooltip_envios_deshabilitados' => 'Diccionario con envíos deshabilitados',
    'title_diccionario-modificar-ver' =>'Modificar Diccionario Personal',
    'selecione_nuevo_avatar' =>'Selecciona un nuevo avatar',

    'guardado_ok' => 'Se han guardado correctamente los cambios',

    'logout' => 'Desconectar',

    'falta_campo' => 'Es obligatorio rellenar',
    'falta_campo_plural'=> 'Es obligatorio rellenar',

    // 'mensaje_dicAula_no_visible' => 'Actualmente no visible para el alumnado consultar con docente',
    'mensaje_dicAula_no_visible' => 'Diccionario de aula temporalmente no visible',
    'mensaje_dicAula_deshabilitado' => 'Actualmente no visible para el alumnado consultar con docente',

    'diccionario_sin_comentarios' => 'Diccionario sin comentarios',

    // TODO: revisar 
    'ver_acepcion_frase_ejemplo' => 'Más datos. ',
    // 'ver_acepcion_frase_ejemplo' => '',
    'ver_acepcion_ejemplo2' => '',
    'ver_acepcion_tematica' => 'Etiquetas',

    'no_hay_comentarios' => 'no hay comentarios',
    'ver_pautas_generales' => 'ver pautas generales',

    'aula_no_unido_diccionario' => 'No estás unida o unido a ningún diccionario o este no está visible para el alumnado.
    ',

    'coordinador' => 'Coordinador / Coordinadora',
    'coordinadores' => 'Coordinación',
    'diccionario' => 'diccionario',
    'contraportada_personal_titulo' => 'Tu diccionario personal',
    'contraportada_aula_titulo' => 'Diccionario de aula',
    'contraportada_descripcion' => 'LexiCán es un diccionario en línea donde podrás crear tus propias entradas con varias acepciones, con la posibilidad de introducir imágenes, audios y vídeos.',

    'autor' => 'Autor / Autora',

    'creditos' => 'Créditos - © Gobierno de Canarias 2020-:to',
    'aviso_legal' => 'https://www.gobiernodecanarias.org/principal/avisolegal.html',
    'politica_privacidad' => 'https://www.gobiernodecanarias.org/eucd/politica_privacidad/',

    'envio_error_faltan_datos' => ' le faltan los siguientes datos:',
    'enviado_una'=> 'Enviado una vez',
    'enviado_veces'=> 'Enviado ## veces',

    'tooltip_palabra-enviada' => 'La palabra ya fue enviada. Se puede volver a enviar pulsando sobre ella.',
    'tooltip_palabra-error' => 'Las palabras con errores no se pueden enviar. Pulsa sobre ella para ver los errores y corregirlos',
    'tooltip_palabra-oculta' => 'Las palabras ocultas no se pueden enviar. Pulsa sobre ella para enviarla',

    'enviarDiccionario_marcada_para_enviar' => 'Marcadas para enviar',
    'enviarDiccionario_palabra_oculta' => 'Palabras ocultas',
    'enviarDiccionario_nunca_enviada' => 'Nunca enviadas',
    'enviarDiccionario_total_palabras' => 'Total palabras',
    'enviarDiccionario_leyenda' => 'Leyenda',
    'enviarDiccionario_ultimo_envio' => "Último envío ##",

    'diccionario_borrado_success' => "Se ha borrado correctamente el diccionario",
    'eliminar_diccionario' => "Borrar diccionario",
    'eliminar_diccioanario__confirmar' => "Esta de acuerdo con borrar el diccionario, no se podrá revertir esta acción",
    'eliminar_diccioanario__enviadas' => 'Este diccionario ya tiene envíos de entradas. Si se elimina se borrarán también los envíos ¿Desea eliminar de forma permanente este diccionario?',
    'eliminar_diccioanario__pubilcadas' => 'No es posible eliminar un diccionario de aula que tiene entradas publicadas',
    'eliminar_diccionario__desea_eliminar' => '¿Desea eliminar de forma permanente este diccionario?',    
    'eliminar_diccionario__participantes_unidos' => 'Este diccionario ya tiene participantes que se han unido. ',

    'alfabeticamente' =>  'Alfabéticamente',
    'por_fecha' => 'Por fecha',
    'nota_invitar_docente' => 'Recuerde que el o la docente debe tener un nombramiento activo para poder acceder al diccionario',
    'tooltip_entrada_modificada' => 'Entrada modificada por docente',
    'Envio' => 'Envío',
    'Desde' => 'Desde',
    'Hasta' => 'Hasta',
    'estadoEnvio_todas' => 'Todas',
    'estadoEnvio_publicado' => 'Publicada',
    'estadoEnvio_noPublicado' => 'No publicada',
    'msg_nuevo_codigo' => 'código: ',
    'fallo_generando_codigo' => 'No se puedo generar el código de aula',
    'disponibles' => 'disponibles',
    'seleccionadas' => 'seleccionadas',
    'buscadorAvanzado_tooltip' => 'Mostrar opciones avanzadas del buscador',
    'filtros_activos' => 'Filtros activos',

    'entradaPublicar_yaEnviadaPor' => 'La entrada ":entrada" enviada por :participante ya está publicada en el diccionario ":diccionario".',
    'entradaEliminar_confirmar' => 'Va a eliminar, sin posibilidad de recuperación, el envío de la entrada ":entrada" realizado por ":participante".',
    'entradaPublicar_deseaAnular' => '¿Desea anular la publicación?',

    'entradaPublicar_publicarPor' => 'Va a publicar en el diccionario de aula ":diccionario" la entrada ":entrada" enviada por :participante',

    'entradaPublicarListado_publicarPor'        => 'Va a publicar en el diccionario de aula ":diccionario" las entradas resultantes de la búsqueda (:n_entradas entradas)' ,
    'entradaPublicarListado_publicarMultiple'   => 'Publicación múltiple de entradas en el diccionario ":diccionario":',
    'entradaPublicarListado_entradasBusqueda'   => 'Número de entradas resultado de la búsqueda: :n',
    'entradaPublicarListado_entradasOk'         => 'Número de entradas que se publicarán: :n'                                                                              ,
    'entradaPublicarListado_noHayEntradas'      => 'No hay entradas para publicar.'                                                                                        ,
    'entradaPublicarListado_noSePublicaran'     => 'Detalle de entradas que no se publicarán:'                                                                             ,
    'entradaPublicarListado_yaPublicadas'       => 'Por estar ya publicadas(:n): '                                                                                         ,
    'entradaPublicarListado_duplicadaEnListado' => 'Por estar repetidas en el resultado de la búsqueda(:n): '                                                              ,
    // 'entradaPublicarListado_'                   => ''                                                                                                                        ,

    'tooltip_faltaDiccionarioAula' => 'Crear o unirse a un diccionario de aula para activar',
    'dpDiccionairoEnviarFormulario_enviadasConAnterioridad' => 'Enviadas con anterioridad ',
    'dpDiccionairoEnviarFormulario_ocultas' => 'Ocultas ',
    'dpDiccionairoEnviarFormulario_conErrores' => 'Con errores ',

    'daEntradaEdit_titulo' => 'Editar nombre de entrada',
    'comentariosPost_tinyComentarioError' => 'No puede enviar un comentario vacío',

    'invitarmail_enviado' => 'Se ha enviado el email',
    'invitarmail_error' => 'No se pudo enviar el email',

    // Cambio de nombres en los roles para no tener que modificar la bbdd donde estan como "alumno" y "docente"
    'Alumno' => 'Estudiante',
    'Docente' => 'Docente',
    'quepuedohacer' => '¿Qué puedo hacer?',

    'entradaPublicada_title' => 'Entrada ya publicada',

    'aula_diccionarios_cero_docente' => 'Todavía no has creado ningún diccionario de aula para este curso escolar, utiliza la opción o únete a un diccionario al que estés invitado (solo en el caso de que tenga una invitación a unirse a un diccionario).',
    'aula_diccionarios_cero_alumno' => 'Únete a un diccionario de aula para poder consultarlo, pulsa sobre el icono del diccionario.',

    'publicar' => 'publicar',
    'publicar_todo' => 'Publicar todo',

    'importar_no_existen_diccionarios'   => 'no existen diccionarios que se puedan importar',
    'importar_desde'                     => 'Entradas importadas desde ',
    'deshabilitar acceso al diccionario' => 'deshabilitar acceso al diccionario',
    'habilitar acceso al diccionario'    => 'habilitar acceso al diccionario',
    'deshabilitar permisos de edicion'   => 'quitar permisos de administración',
    'habilitar permisos de edicion'      => 'dar permisos de administración',
    'usuario habilitado'                 => 'usuario habilitado',
    'usuario deshabilitado'              => 'usuario deshabilitado',
    'usuario con permisos edicion'       => 'usuario con permisos administración',
    'usuario sin permisos edicion'       => 'usuario sin permisos administración',
    'no_puedes_auto_excluirte' => 'No puedes autoexcluirte del diccionario',
    'no_puedes_auto_autodeshabilitarte'  => 'No puedes quitarte permisos de administración a ti mismo',
    'no_puedes_auto_autodeshabilitarte_edicion' => 'No puedes quitarte permisos de administración a ti mismo',
    'solo_propietario_puede_deshabilitar_edicion' => 'Solo el propietario puede editar los permisos de administración',
    'no_puedes_eliminar_ultimo_docente' => 'No puedes eliminar el último coordinador',
    'error_al_guadar' => 'No se pudo realizar el cambio',

    'entradaAulaListado_creadorYfecha' => 'por :nombre :apellidos el :fecha',
    'tooltip_vigencia' => 'Cuando un diccionario deja de estar vigente, el alumnado no podrá enviar entradas ni unirse al diccionario pero se podrá consultar desde la opción de hístoricos.',

    'tooltip_vigencia' => 'Cuando un diccionario deja de estar vigente, el alumnado no podrá enviar entradas ni unirse al diccionario pero se podrá consultar desde la opción de historicos.',
    'diccionario_inactivo'  => 'diccionario inactivo' ,
    'no se puede modificar' => 'no se puede modificar',
    
    'vigencia__select_item' => 'Vigente hasta el final de :curso (:n curso)|Vigente hasta el final de :curso (:n cursos)',
    'entradasAula_limpiarFiltros' => 'Limpiar filtros',
    'entradasAula_no_enviado_entrada' => 'No se ha enviado ninguna entrada',

    'cookies_msg' => 'Lexicán utiliza cookies propias para su correcto funcionamiento, pero no recogen información de carácter personal. Usted puede permitir su uso o rechazarlo, o también puede cambiar su configuración siempre que lo desee. Dispone de más información en <a href="https://www.gobiernodecanarias.org/principal/politica-de-cookies/">la página del Gobierno de Canarias</a>',

    'pubilcarEntrada_ya_publicada' => 'Error: entrada duplicada',
    
    'exportarpdf__titulo' => 'Exportar a PDF',
    'exportarpdf__no_tematicas_seleccionadas' => 'No se ha seleccionado ninguna eticqueta, seleccione al menos una o desmarque la opción',
    'exportarpdf__no_entradas' => 'No se ha encontrado ninguna entrada',
    'exportarpdf__solo_etiquetadas' => 'Exportar las acepciones marcadas con las etiquetas seleccionadas',
    // 'exportarpdf__mostrar_ocultas' => 'Exporta las entradas ocultas [:nEntradas]',
    'exportarpdf__mostrar_ocultas' => 'Exportar las entradas y acepciones ocultas',
    'exportarpdf__confirmar_exportar' => 'Exportar a PDF',

    'falta_acepcion' => 'No tiene acepciones para enviar',
    'envio_error_faltan_acepciones' => 'No se puede enviar la entrada porque no tiene acepciones, revisar ocultas',

    'buscador_filtrarCategorias' => 'etiquetas',
    'buscador_filtrandoCategorias' => 'Filtro:',
    'buscador_borrarFiltros' => 'x',
    'buscador_noexisteEnCategoria' => 'con la etiqueta selecionada|con las etiquetas seleccionadas',
    'buscador_sindatos' => 'Debe especificar texto o categoria a buscar',
    
    // 'buscador_borrarFiltros' => 'borrar todas',
    'importar_DiccionariosActivos' => 'Diccionarios Actuales',
    'importar_DiccionariosInactivos' => 'Diccionarios años anteriores',



];
