<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('testing')->create('Datapool_Datafile', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('datapool_id')->index('fk_Datapool_has_Datafile_Datapool1_idx');
            $table->integer('datafile_id')->index('fk_Datapool_has_Datafile_Datafile1_idx');
            $table->integer('version');
            $table->string('codebook_template', 250);
            $table->string('codebook', 250);
            $table->tinyInteger('completed')->default(0);
            $table->tinyInteger('current')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->dropIfExists('Datapool_Datafile');
    }
};
