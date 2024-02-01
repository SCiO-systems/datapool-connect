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
        DB::connection('testing')->statement("CREATE OR REPLACE VIEW `Not_Assigned_Datafiles` AS select `u`.`user_id` AS `user_id`,`u`.`identity_provider_id` AS `identity_provider_id`,`u`.`name` AS `name`,`u`.`surname` AS `surname`,`u`.`email` AS `email`,`df`.`datafile_id` AS `datafile_id` from (`dev_datapool`.`User` `u` join `dev_datapool`.`User_Datafile` `df` on((`u`.`user_id` = `df`.`user_id`))) where `df`.`datafile_id` in (select `dev_datapool`.`Datapool_Datafile`.`datafile_id` from `dev_datapool`.`Datapool_Datafile`) is false");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('testing')->statement("DROP VIEW IF EXISTS `Not_Assigned_Datafiles`");
    }
};
