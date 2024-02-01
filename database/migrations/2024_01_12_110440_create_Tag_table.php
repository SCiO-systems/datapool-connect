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
        Schema::connection('testing')->create('Tag', function (Blueprint $table) {
            $table->integer('tag_id', true);
            $table->string('tag')->nullable();
            $table->enum('type', ['crop', 'country', 'region'])->nullable();
            $table->integer('datapool_id')->nullable()->index('tag_to_datapool_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->dropIfExists('Tag');
    }
};
