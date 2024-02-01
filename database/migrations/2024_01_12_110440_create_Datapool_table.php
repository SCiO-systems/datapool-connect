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
        Schema::connection('testing')->create('Datapool', function (Blueprint $table) {
            $table->integer('datapool_id', true);
            $table->string('mongo_id');
            $table->string('alias');
            $table->tinyInteger('deleted')->default(0);
            $table->string('name');
            $table->string('description', 2000)->nullable();
            $table->integer('records')->nullable();
            $table->enum('license', ['CC0', 'CC BY', 'CC BY-SA', 'CC BY-ND', 'CC BY-NC', 'CC BY-NC-SA', 'CC BY-NC-ND'])->nullable();
            $table->string('citation')->nullable();
            $table->enum('status', ['public', 'private'])->nullable()->default('private');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->dropIfExists('Datapool');
    }
};
