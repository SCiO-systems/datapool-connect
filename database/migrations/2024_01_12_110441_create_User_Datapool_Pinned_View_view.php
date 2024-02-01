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
        DB::connection('testing')->statement("CREATE OR REPLACE VIEW `User_Datapool_Pinned_View` AS select `u`.`user_id` AS `user_id`,`d`.`datapool_id` AS `datapool_id`,`d`.`mongo_id` AS `mongo_id`,`d`.`alias` AS `alias`,`d`.`deleted` AS `deleted`,`d`.`name` AS `name`,`pd`.`pin_id` AS `pin_id` from ((`dev_datapool`.`User` `u` join `dev_datapool`.`Pinned_Datapool` `pd` on((`u`.`user_id` = `pd`.`user_id`))) join `dev_datapool`.`Datapool` `d` on((`pd`.`datapool_id` = `d`.`datapool_id`)))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('testing')->statement("DROP VIEW IF EXISTS `User_Datapool_Pinned_View`");
    }
};
