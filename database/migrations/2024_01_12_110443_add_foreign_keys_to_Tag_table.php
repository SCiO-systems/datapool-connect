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
        Schema::connection('testing')->table('Tag', function (Blueprint $table) {
            $table->foreign(['datapool_id'], 'tag_to_datapool')->references(['datapool_id'])->on('Datapool')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->table('Tag', function (Blueprint $table) {
            $table->dropForeign('tag_to_datapool');
        });
    }
};
