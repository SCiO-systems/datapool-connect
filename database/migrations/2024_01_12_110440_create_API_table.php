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
        Schema::connection('testing')->create('API', function (Blueprint $table) {
            $table->integer('api_id', true);
            $table->string('auth_zero_id')->nullable();
            $table->integer('datapool_id')->index('datapool_fk_idx');
            $table->integer('user_id')->index('user_fik_idx');
            $table->tinyInteger('deleted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->dropIfExists('API');
    }
};
