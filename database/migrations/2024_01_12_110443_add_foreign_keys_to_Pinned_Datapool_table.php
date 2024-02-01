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
        Schema::connection('testing')->table('Pinned_Datapool', function (Blueprint $table) {
            $table->foreign(['datapool_id'], 'fk_to_datapool')->references(['datapool_id'])->on('Datapool');
            $table->foreign(['user_id'], 'fk_to_user')->references(['user_id'])->on('User');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->table('Pinned_Datapool', function (Blueprint $table) {
            $table->dropForeign('fk_to_datapool');
            $table->dropForeign('fk_to_user');
        });
    }
};
