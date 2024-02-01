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
        Schema::connection('testing')->create('User_Datafile', function (Blueprint $table) {
            $table->integer('user_id')->index('fk_User_has_Datafile_User1_idx');
            $table->integer('datafile_id')->index('fk_User_has_Datafile_Datafile1_idx');

            $table->primary(['user_id', 'datafile_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->dropIfExists('User_Datafile');
    }
};
