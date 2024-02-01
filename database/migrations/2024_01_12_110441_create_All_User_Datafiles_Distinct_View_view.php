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
        DB::connection('testing')->statement("CREATE OR REPLACE VIEW `All_User_Datafiles_Distinct_View` AS select distinct `d`.`datafile_id` AS `datafile_id`,`u`.`user_id` AS `user_id`,`u`.`identity_provider_id` AS `identity_provider_id`,`d`.`key` AS `key`,`d`.`creation_time` AS `creation_time`,`d`.`filename` AS `filename`,(`dd`.`datapool_id` is not null) AS `has_datapool`,`dd`.`completed` AS `completed` from (((`dev_datapool`.`User` `u` join `dev_datapool`.`User_Datafile` `ud` on((`u`.`user_id` = `ud`.`user_id`))) join `dev_datapool`.`Datafile` `d` on((`ud`.`datafile_id` = `d`.`datafile_id`))) left join `dev_datapool`.`Datapool_Datafile` `dd` on((`d`.`datafile_id` = `dd`.`datafile_id`)))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('testing')->statement("DROP VIEW IF EXISTS `All_User_Datafiles_Distinct_View`");
    }
};
