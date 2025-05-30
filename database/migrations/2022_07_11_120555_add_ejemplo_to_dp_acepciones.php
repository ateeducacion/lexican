<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEjemploToDpAcepciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dp_acepciones', function (Blueprint $table) {
            $table->string('ejemplo2', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dp_acepciones', function (Blueprint $table) {
            $table->dropColumn('ejemplo2');
        });
    }
}
