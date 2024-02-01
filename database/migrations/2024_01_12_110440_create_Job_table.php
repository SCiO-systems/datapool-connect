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
        Schema::connection('testing')->create('Job', function (Blueprint $table) {
            $table->integer('job_id', true);
            $table->integer('user_id')->index('FK_UserJob_idx');
            $table->string('mongo_id', 45);
            $table->enum('type', ['index'])->nullable()->default('index');
            $table->integer('datapool_id')->nullable();
            $table->integer('datafile_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->dropIfExists('Job');
    }
};
