<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpdatedByPersonaIdToEnviosEntradas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('envios_entradas', function (Blueprint $table) {
            $table->integer('updated_by_persona_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('envios_entradas', function (Blueprint $table) {
            $table->dropColumn('updated_by_persona_id');
        });
    }
}
