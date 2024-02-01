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
        Schema::connection('testing')->table('User_Datafile', function (Blueprint $table) {
            $table->foreign(['datafile_id'], 'fk_User_has_Datafile_Datafile1')->references(['datafile_id'])->on('Datafile')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign(['user_id'], 'fk_User_has_Datafile_User1')->references(['user_id'])->on('User')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->table('User_Datafile', function (Blueprint $table) {
            $table->dropForeign('fk_User_has_Datafile_Datafile1');
            $table->dropForeign('fk_User_has_Datafile_User1');
        });
    }
};
