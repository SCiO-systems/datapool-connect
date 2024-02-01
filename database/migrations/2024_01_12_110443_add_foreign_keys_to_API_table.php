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
        Schema::connection('testing')->table('API', function (Blueprint $table) {
            $table->foreign(['datapool_id'], 'datapool_fk')->references(['datapool_id'])->on('Datapool');
            $table->foreign(['user_id'], 'user_fik')->references(['user_id'])->on('User');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->table('API', function (Blueprint $table) {
            $table->dropForeign('datapool_fk');
            $table->dropForeign('user_fik');
        });
    }
};
