<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTableDicAulaDicPermanente extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dic_aula_atemporales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dic_aula_id');
            $table->foreign('dic_aula_id')->references('id')->on('dic_aula');
            $table->char('estado', 1);
            $table->timestamp('hasta')->nullable();;
            // $table->timestamps();
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dic_aula_atemporales');
    }
}
