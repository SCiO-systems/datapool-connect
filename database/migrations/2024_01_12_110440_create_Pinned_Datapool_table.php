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
        Schema::connection('testing')->create('Pinned_Datapool', function (Blueprint $table) {
            $table->integer('pin_id', true);
            $table->integer('datapool_id')->index('fk_to_datapool_idx');
            $table->integer('user_id')->index('fk_to_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->dropIfExists('Pinned_Datapool');
    }
};
