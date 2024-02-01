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
        DB::connection('testing')->statement("CREATE OR REPLACE VIEW `Datapool_Datafile_View` AS select `df`.`datapool_id` AS `datapool_id`,`df`.`mongo_id` AS `mongo_id`,`df`.`alias` AS `alias`,`df`.`deleted` AS `deleted`,`df`.`description` AS `description`,`df`.`license` AS `license`,`df`.`records` AS `records`,`df`.`citation` AS `citation`,`d`.`datafile_id` AS `datafile_id`,`d`.`key` AS `key`,`d`.`creation_time` AS `creation_time`,`d`.`filename` AS `filename`,`ud`.`version` AS `version`,`ud`.`completed` AS `completed` from ((`dev_datapool`.`Datapool` `df` join `dev_datapool`.`Datapool_Datafile` `ud` on((`df`.`datapool_id` = `ud`.`datapool_id`))) join `dev_datapool`.`Datafile` `d` on((`ud`.`datafile_id` = `d`.`datafile_id`)))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('testing')->statement("DROP VIEW IF EXISTS `Datapool_Datafile_View`");
    }
};
