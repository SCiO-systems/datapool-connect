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
        Schema::connection('testing')->create('User_Datapool', function (Blueprint $table) {
            $table->integer('user_id')->index('fk_User_has_Datapool_User_idx');
            $table->integer('datapool_id')->index('fk_User_has_Datapool_Datapool1_idx');
            $table->integer('role_id')->index('fk_User_has_Datapool_Role1_idx');
            $table->integer('api_id')->nullable()->index('fk_User_Datapool_API1_idx');

            $table->primary(['user_id', 'datapool_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->dropIfExists('User_Datapool');
    }
};
