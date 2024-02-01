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
        Schema::connection('testing')->table('User_Datapool', function (Blueprint $table) {
            $table->foreign(['api_id'], 'fk_User_Datapool_API1')->references(['api_id'])->on('API')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign(['datapool_id'], 'fk_User_has_Datapool_Datapool1')->references(['datapool_id'])->on('Datapool')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign(['role_id'], 'fk_User_has_Datapool_Role1')->references(['role_id'])->on('Role')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign(['user_id'], 'fk_User_has_Datapool_User')->references(['user_id'])->on('User')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('testing')->table('User_Datapool', function (Blueprint $table) {
            $table->dropForeign('fk_User_Datapool_API1');
            $table->dropForeign('fk_User_has_Datapool_Datapool1');
            $table->dropForeign('fk_User_has_Datapool_Role1');
            $table->dropForeign('fk_User_has_Datapool_User');
        });
    }
};
