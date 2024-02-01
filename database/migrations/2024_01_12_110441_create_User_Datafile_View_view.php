<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('testing')->statement("CREATE OR REPLACE VIEW `User_Datafile_View` AS select `u`.`user_id` AS `user_id`,`u`.`identity_provider_id` AS `identity_provider_id`,`d`.`datafile_id` AS `datafile_id`,`d`.`key` AS `key`,`d`.`creation_time` AS `creation_time`,`d`.`filename` AS `filename` from ((`dev_datapool`.`User` `u` join `dev_datapool`.`User_Datafile` `ud` on((`u`.`user_id` = `ud`.`user_id`))) join `dev_datapool`.`Datafile` `d` on((`ud`.`datafile_id` = `d`.`datafile_id`)))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('testing')->statement("DROP VIEW IF EXISTS `User_Datafile_View`");
    }
};
