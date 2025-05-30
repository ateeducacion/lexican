<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInitialStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*-------------------------------------------------*/
        /*-----------------TABLAS MAESTRAS-----------------*/
        /*-------------------------------------------------*/
        if (!Schema::hasTable('mst_ensenanzas')) {
            Schema::create('mst_ensenanzas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('descripcion', 255);
                $table->dateTime('fecha_baja')->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('mst_areas_materias')) {
            Schema::create('mst_areas_materias', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('descripcion', 255);
                $table->dateTime('fecha_baja')->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('mst_nivel_estudios')) {
            Schema::create('mst_nivel_estudios', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('descripcion', 255);
                $table->dateTime('fecha_baja')->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('mst_tipos_diccionario_aula')) {
            Schema::create('mst_tipos_diccionario_aula', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('tipo_diccionario', 150)->unique();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('mst_campos_entrada')) {
            Schema::create('mst_campos_entrada', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('nombre_campo', 150)->unique();
                $table->char('estado', 1);
                $table->dateTime('fecha_baja')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('mst_campos_valores')) {
            Schema::create('mst_campos_valores', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('mst_campo_entrada_id');
                $table->foreign('mst_campo_entrada_id')->references('id')->on('mst_campos_entrada');
                $table->string('descripcion', 255);
                $table->char('estado', 1);
                $table->dateTime('fecha_baja')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('mst_ensenanzas_estudios')) {
            Schema::create('mst_ensenanzas_estudios', function (Blueprint $table) {
                $table->unsignedBigInteger('mst_ensenanzas_id');
                $table->foreign('mst_ensenanzas_id')->references('id')->on('mst_ensenanzas');
                $table->unsignedBigInteger('mst_nivel_estudios_id');
                $table->foreign('mst_nivel_estudios_id')->references('id')->on('mst_nivel_estudios');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('mst_estudios_areas_materias')) {
            Schema::create('mst_estudios_areas_materias', function (Blueprint $table) {
                $table->unsignedBigInteger('mst_nivel_estudios_id');
                $table->foreign('mst_nivel_estudios_id')->references('id')->on('mst_nivel_estudios');
                $table->unsignedBigInteger('mst_area_materia_id');
                $table->foreign('mst_area_materia_id')->references('id')->on('mst_areas_materias');
                $table->timestamps();
                $table->softDeletes();
            });
        }


        /*------------------------------------------*/
        /*-----------------PERSONAS-----------------*/
        /*------------------------------------------*/
        if (!Schema::hasTable('personas')) {
            Schema::create('personas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('cial', 12)->unique()->nullable();
                $table->string('NIF_NIE', 9)->unique()->nullable();
                $table->string('pasaporte')->unique()->nullable();
                $table->string('nombre', 150);
                $table->string('apellidos', 150);
                $table->string('avatar_URL', 255);
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }


        /*------------------------------------------------------*/
        /*-----------------DICCIONARIO PERSONAL-----------------*/
        /*------------------------------------------------------*/
        if (!Schema::hasTable('dic_personal')) {
            Schema::create('dic_personal', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('persona_id');
                $table->foreign('persona_id')->references('id')->on('personas');
                $table->string('titulo', 150);
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('dp_entradas')) {
            Schema::create('dp_entradas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dic_personal_id');
                $table->foreign('dic_personal_id')->references('id')->on('dic_personal');
                $table->string('entrada', 150);
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('dp_acepciones')) {
            Schema::create('dp_acepciones', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dic_entrada_id');
                $table->foreign('dic_entrada_id')->references('id')->on('dp_entradas');
                $table->smallInteger('orden');
                $table->unsignedBigInteger('cat_gramatical_id')->nullable();
                $table->foreign('cat_gramatical_id')->references('id')->on('mst_campos_valores');
                $table->unsignedBigInteger('genero_id')->nullable();
                $table->foreign('genero_id')->references('id')->on('mst_campos_valores');
                $table->unsignedBigInteger('numero_id')->nullable();
                $table->foreign('numero_id')->references('id')->on('mst_campos_valores');
                $table->unsignedBigInteger('idioma_id')->nullable();
                $table->foreign('idioma_id')->references('id')->on('mst_campos_valores');
                $table->string('idioma_palabra', 150)->nullable();
                $table->string('definicion', 1000);
                $table->string('frase_ejemplo', 255)->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('dp_acepciones_tematicas')) {
            Schema::create('dp_acepciones_tematicas', function (Blueprint $table) {
                $table->unsignedBigInteger('dp_acepcion_id');
                $table->foreign('dp_acepcion_id')->references('id')->on('dp_acepciones');
                $table->unsignedBigInteger('tematica_id');
                $table->foreign('tematica_id')->references('id')->on('mst_campos_valores');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('dp_acepciones_medios')) {
            Schema::create('dp_acepciones_medios', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dic_acepcion_id');
                $table->foreign('dic_acepcion_id')->references('id')->on('dp_acepciones');
                $table->char('tipo_medio', 1);
                $table->string('nombre', 255);
                $table->string('url_externa', 255)->nullable();
                $table->string('url_interna', 255)->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }


        /*--------------------------------------------------*/
        /*-----------------DICCIONARIO AULA-----------------*/
        /*--------------------------------------------------*/
        if (!Schema::hasTable('dic_aula')) {
            Schema::create('dic_aula', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('persona_id');
                $table->foreign('persona_id')->references('id')->on('personas');
                $table->unsignedBigInteger('mst_tipo_dic_id');
                $table->foreign('mst_tipo_dic_id')->references('id')->on('mst_tipos_diccionario_aula');
                $table->string('titulo', 150);
                $table->string('descripcion', 255)->nullable();
                $table->char('letra_grupo', 1);
                $table->unsignedBigInteger('mst_nivel_estudios_id')->nullable();
                $table->foreign('mst_nivel_estudios_id')->references('id')->on('mst_nivel_estudios');
                $table->unsignedBigInteger('mst_area_materia_id');
                $table->foreign('mst_area_materia_id')->references('id')->on('mst_areas_materias');
                $table->unsignedBigInteger('mst_ensenanza_id')->default(1);
                $table->foreign('mst_ensenanza_id')->references('id')->on('mst_ensenanzas');
                $table->smallInteger('ano_ini_curso_escolar');
                $table->smallInteger('max_acepciones_entrada');
                $table->string('codigo', 7);
                $table->char('visible_estudiante', 1);
                $table->char('envios_habilitados', 1);
                $table->dateTime('envios_fecha_ini')->nullable();
                $table->dateTime('envios_fecha_fin')->nullable();
                $table->char('estado', 1);
                $table->char('comentarios_visibles', 1)->default(0);
                $table->dateTime('comentarios_visibles_anteriores_a', 1)->nullable();
                $table->char('pautas_maestras', 1)->default(1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('dic_aula_destinatario_avisos')) {
            Schema::create('dic_aula_destinatario_avisos', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dic_aula_id');
                $table->foreign('dic_aula_id')->references('id')->on('dic_aula');
                $table->string('email', 255)->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('dic_aula_pautas')) {
            Schema::create('dic_aula_pautas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dic_aula_id');
                $table->foreign('dic_aula_id')->references('id')->on('dic_aula');
                $table->string('url_fichero', 255)->nullable();
                $table->text('texto')->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('dic_aula_participantes')) {
            Schema::create('dic_aula_participantes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dic_aula_id');
                $table->foreign('dic_aula_id')->references('id')->on('dic_aula');
                $table->unsignedBigInteger('persona_id');
                $table->foreign('persona_id')->references('id')->on('personas');
                $table->unsignedBigInteger('rol_diccionario_id');
                $table->foreign('rol_diccionario_id')->references('id')->on('roles');
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('dic_aula_campos')) {
            Schema::create('dic_aula_campos', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dic_aula_id');
                $table->foreign('dic_aula_id')->references('id')->on('dic_aula');
                $table->unsignedBigInteger('mst_campo_entrada_id');
                $table->foreign('mst_campo_entrada_id')->references('id')->on('mst_campos_entrada');
                $table->char('visible', 1);
                $table->char('obligatorio', 1);
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }


        /*---------------------------------------------------------*/
        /*-----------------ENVÍOS DICCIONARIO AULA-----------------*/
        /*---------------------------------------------------------*/
        if (!Schema::hasTable('dp_envios')) {
            Schema::create('dp_envios', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->smallInteger('ano_ini_curso_escolar');
                $table->unsignedBigInteger('dic_personal_id');
                $table->foreign('dic_personal_id')->references('id')->on('dic_personal');
                $table->unsignedBigInteger('dic_aula_id');
                $table->foreign('dic_aula_id')->references('id')->on('dic_aula');
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('envios_entradas')) {
            Schema::create('envios_entradas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dp_envio_id');
                $table->foreign('dp_envio_id')->references('id')->on('dp_envios');
                $table->unsignedBigInteger('dp_entrada_id');
                $table->foreign('dp_entrada_id')->references('id')->on('dp_entradas');
                $table->string('entrada', 150);
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('envios_acepciones')) {
            Schema::create('envios_acepciones', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('envio_entrada_id');
                $table->foreign('envio_entrada_id')->references('id')->on('envios_entradas');
                $table->smallInteger('orden');
                $table->unsignedBigInteger('cat_gramatical_id')->nullable();
                $table->foreign('cat_gramatical_id')->references('id')->on('mst_campos_valores');
                $table->unsignedBigInteger('genero_id')->nullable();
                $table->foreign('genero_id')->references('id')->on('mst_campos_valores');
                $table->unsignedBigInteger('numero_id')->nullable();
                $table->foreign('numero_id')->references('id')->on('mst_campos_valores');
                $table->unsignedBigInteger('idioma_id')->nullable();
                $table->foreign('idioma_id')->references('id')->on('mst_campos_valores');
                $table->string('idioma_palabra', 150)->nullable();
                $table->string('definicion', 1000);
                $table->string('frase_ejemplo', 255)->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('envios_acepciones_tematicas')) {
            Schema::create('envios_acepciones_tematicas', function (Blueprint $table) {
                $table->unsignedBigInteger('envio_acepcion_id');
                $table->foreign('envio_acepcion_id')->references('id')->on('envios_acepciones');
                $table->unsignedBigInteger('tematica_id');
                $table->foreign('tematica_id')->references('id')->on('mst_campos_valores');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('envios_acepciones_medios')) {
            Schema::create('envios_acepciones_medios', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('envio_acepcion_id');
                $table->foreign('envio_acepcion_id')->references('id')->on('envios_acepciones');
                $table->char('tipo_medio', 1);
                $table->string('nombre', 255);
                $table->string('url_externa', 255)->nullable();
                $table->string('url_interna', 255)->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }


        /*--------------------------------------------------*/
        /*-----------------DICCIONARIO AULA-----------------*/
        /*--------------------------------------------------*/
        if (!Schema::hasTable('dic_aula_entradas')) {
            Schema::create('dic_aula_entradas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dic_aula_id');
                $table->foreign('dic_aula_id')->references('id')->on('dic_aula');
                $table->unsignedBigInteger('envio_entrada_id');
                $table->foreign('envio_entrada_id')->references('id')->on('envios_entradas');
                $table->char('origen', 1);
                $table->bigInteger('dic_origen', null)->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }


        /*--------------------------------------------------------------------*/
        /*-----------------RELACIÓN PERSONAS USUARIOS CENTROS-----------------*/
        /*--------------------------------------------------------------------*/
        if (!Schema::hasTable('users_personas')) {
            Schema::create('users_personas', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')->references('id')->on('users');
                $table->unsignedBigInteger('persona_id');
                $table->foreign('persona_id')->references('id')->on('personas');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('centros')) {
            Schema::create('centros', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('cod_centro', 8);
                $table->string('denominacion', 255);
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('users_centros')) {
            Schema::create('users_centros', function (Blueprint $table) {
                $table->unsignedBigInteger('centro_id');
                $table->foreign('centro_id')->references('id')->on('centros');
                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')->references('id')->on('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }


        /*-------------------------------------------------------------------*/
        /*-----------------RELACIÓN DICCIONARIO AULA CENTROS-----------------*/
        /*-------------------------------------------------------------------*/
        if (!Schema::hasTable('centros_dic_aula')) {
            Schema::create('centros_dic_aula', function (Blueprint $table) {
                $table->unsignedBigInteger('centro_id');
                $table->foreign('centro_id')->references('id')->on('centros');
                $table->unsignedBigInteger('dic_aula_id');
                $table->foreign('dic_aula_id')->references('id')->on('dic_aula');
                $table->timestamps();
                $table->softDeletes();
            });
        }


        /*---------------------------------------------*/
        /*-----------------COMENTARIOS-----------------*/
        /*---------------------------------------------*/
        if (!Schema::hasTable('comentarios_generales')) {
            Schema::create('comentarios_generales', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dic_personal_id');
                $table->foreign('dic_personal_id')->references('id')->on('dic_personal');
                $table->unsignedBigInteger('persona_id');
                $table->foreign('persona_id')->references('id')->on('personas');
                $table->unsignedBigInteger('dic_aula_id');
                $table->foreign('dic_aula_id')->references('id')->on('dic_aula');
                $table->text('comentario');
                $table->dateTime('fecha_envio')->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('comentarios_entradas')) {
            Schema::create('comentarios_entradas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('envio_entrada_id');
                $table->foreign('envio_entrada_id')->references('id')->on('envios_entradas');
                $table->unsignedBigInteger('persona_id');
                $table->foreign('persona_id')->references('id')->on('personas');
                $table->unsignedBigInteger('dic_aula_id');
                $table->foreign('dic_aula_id')->references('id')->on('dic_aula');
                $table->text('comentario');
                $table->dateTime('fecha_envio')->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }


        /*------------------------------------------------------*/
        /*-----------------ENTRADAS COMPARTIDAS-----------------*/
        /*------------------------------------------------------*/
        if (!Schema::hasTable('entradas_compartidas')) {
            Schema::create('entradas_compartidas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dic_personal_id');
                $table->foreign('dic_personal_id')->references('id')->on('dic_personal');
                $table->unsignedBigInteger('dic_aula_entrada_id');
                $table->foreign('dic_aula_entrada_id')->references('id')->on('dic_aula_entradas');
                $table->dateTime('fecha_comparticion')->nullable();
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }


        /*----------------------------------------*/
        /*-----------------AVISOS-----------------*/
        /*----------------------------------------*/
        if (!Schema::hasTable('avisos')) {
            Schema::create('avisos', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->char('tipo_aviso', 1);
                $table->char('destinatario', 1);
                $table->unsignedBigInteger('dic_personal_id');
                $table->unsignedBigInteger('dic_aula_id');
                $table->char('email', 1);
                $table->char('estado', 1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('avisos_dp_envios')) {
            Schema::create('avisos_dp_envios', function (Blueprint $table) {
                $table->unsignedBigInteger('avisos_id');
                $table->foreign('avisos_id')->references('id')->on('avisos');
                $table->unsignedBigInteger('dp_envios_id');
                $table->foreign('dp_envios_id')->references('id')->on('dp_envios');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('avisos_coment_generales')) {
            Schema::create('avisos_coment_generales', function (Blueprint $table) {
                $table->unsignedBigInteger('avisos_id');
                $table->foreign('avisos_id')->references('id')->on('avisos');
                $table->unsignedBigInteger('comentario_general_id');
                $table->foreign('comentario_general_id')->references('id')->on('comentarios_generales');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('avisos_coment_entradas')) {
            Schema::create('avisos_coment_entradas', function (Blueprint $table) {
                $table->unsignedBigInteger('avisos_id');
                $table->foreign('avisos_id')->references('id')->on('avisos');
                $table->unsignedBigInteger('comentario_entrada_id');
                $table->foreign('comentario_entrada_id')->references('id')->on('comentarios_entradas');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('avisos_entradas_compartidas')) {
            Schema::create('avisos_entradas_compartidas', function (Blueprint $table) {
                $table->unsignedBigInteger('avisos_id');
                $table->foreign('avisos_id')->references('id')->on('avisos');
                $table->unsignedBigInteger('entrada_compartida_id');
                $table->foreign('entrada_compartida_id')->references('id')->on('entradas_compartidas');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        /*----------------------------------------------------------------*/
        /*-----------------INTRODUCCIÓN DE DATOS MAESTROS-----------------*/
        /*----------------------------------------------------------------*/
        Artisan::call('db:seed', [
            '--class' => 'MasterTablesDataSeeder'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dp_acepciones_tematicas');
        Schema::dropIfExists('envios_acepciones_tematicas');
        Schema::dropIfExists('centros_dic_aula');
        Schema::dropIfExists('envios_acepciones_medios');
        Schema::dropIfExists('envios_acepciones');
        Schema::dropIfExists('avisos_dp_envios');
        Schema::dropIfExists('avisos_entradas_compartidas');
        Schema::dropIfExists('avisos_coment_entradas');
        Schema::dropIfExists('avisos_coment_generales');
        Schema::dropIfExists('entradas_compartidas');
        Schema::dropIfExists('comentarios_generales');
        Schema::dropIfExists('comentarios_entradas');
        Schema::dropIfExists('dic_aula_entradas');
        Schema::dropIfExists('envios_entradas');
        Schema::dropIfExists('dp_envios');
        Schema::dropIfExists('dp_acepciones_medios');
        Schema::dropIfExists('dp_acepciones');
        Schema::dropIfExists('dp_entradas');
        Schema::dropIfExists('dic_personal');
        Schema::dropIfExists('dic_aula_participantes');
        Schema::dropIfExists('dic_aula_pautas');
        Schema::dropIfExists('dic_aula_destinatario_avisos');
        Schema::dropIfExists('dic_aula_campos');
        Schema::dropIfExists('dic_aula');
        Schema::dropIfExists('users_centros');
        Schema::dropIfExists('users_personas');
        Schema::dropIfExists('personas');
        Schema::dropIfExists('centros');
        Schema::dropIfExists('mst_ensenanzas_estudios');
        Schema::dropIfExists('mst_estudios_areas_materias');
        Schema::dropIfExists('mst_ensenanzas');
        Schema::dropIfExists('mst_areas_materias');
        Schema::dropIfExists('mst_nivel_estudios');
        Schema::dropIfExists('mst_tipos_diccionario_aula');
        Schema::dropIfExists('mst_campos_valores');
        Schema::dropIfExists('mst_campos_entrada');
        Schema::dropIfExists('avisos');
    }

    /**
     * Función que devuelve un array con las claves foráneas de la tabla recibida como parámetro.
     *
     * @param String $table
     * @return Array
     */
    public function listTableForeignKeys($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();

        return array_map(function ($key) {
            return $key->getName();
        }, $conn->listTableForeignKeys($table));
    }
}
