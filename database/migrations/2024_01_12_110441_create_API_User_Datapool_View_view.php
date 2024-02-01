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
        DB::connection('testing')->statement("CREATE OR REPLACE VIEW `API_User_Datapool_View` AS select `A`.`api_id` AS `api_id`,`A`.`auth_zero_id` AS `auth_zero_id`,`A`.`datapool_id` AS `datapool_id`,`A`.`user_id` AS `user_id`,`A`.`deleted` AS `deleted`,`U`.`identity_provider_id` AS `identity_provider_id`,`DP`.`name` AS `name`,`DP`.`mongo_id` AS `mongo_id` from ((`dev_datapool`.`API` `A` join `dev_datapool`.`User` `U` on((`A`.`user_id` = `U`.`user_id`))) join `dev_datapool`.`Datapool` `DP` on((`A`.`datapool_id` = `DP`.`datapool_id`)))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('testing')->statement("DROP VIEW IF EXISTS `API_User_Datapool_View`");
    }
};
