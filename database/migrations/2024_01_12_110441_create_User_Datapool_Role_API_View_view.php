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
        DB::connection('testing')->statement("CREATE OR REPLACE VIEW `User_Datapool_Role_API_View` AS select `u`.`user_id` AS `user_id`,`u`.`name` AS `name`,`u`.`surname` AS `surname`,`u`.`email` AS `email`,`u`.`identity_provider_id` AS `identity_provider_id`,`d`.`datapool_id` AS `datapool_id`,`d`.`mongo_id` AS `mongo_id`,`d`.`name` AS `datapool_name`,`d`.`alias` AS `alias`,`d`.`deleted` AS `deleted`,`d`.`description` AS `description`,`d`.`license` AS `license`,`d`.`records` AS `records`,`d`.`citation` AS `citation`,`d`.`status` AS `status`,`r`.`role_name` AS `role_name` from (((`dev_datapool`.`User` `u` join `dev_datapool`.`User_Datapool` `ud` on((`u`.`user_id` = `ud`.`user_id`))) join `dev_datapool`.`Datapool` `d` on((`ud`.`datapool_id` = `d`.`datapool_id`))) join `dev_datapool`.`Role` `r` on((`ud`.`role_id` = `r`.`role_id`)))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('testing')->statement("DROP VIEW IF EXISTS `User_Datapool_Role_API_View`");
    }
};
