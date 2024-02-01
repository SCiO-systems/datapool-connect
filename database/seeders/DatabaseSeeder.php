<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Datafile;
use App\Models\Datapool;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('Role')->insert([
            "role_id" => 0,
            "role_name" => "viewer"
        ]);

        DB::table('Role')->insert([
            "role_id" => 1,
            "role_name" => "administrator"
        ]);

        DB::table('Role')->insert([
            "role_id" => 2,
            "role_name" => "data_curator"
        ]);

        $datapools = Datapool::factory()
            ->count(3)
            ->hasAttached(Datafile::factory()->count(1),
                [
                    "version" => 1,
                    "completed" => 1,
                    "current" => 1,
                    "codebook" => fake()->asciify('**************') . ".xlsx",
                    "codebook_template" => fake()->asciify('**************') . ".xlsx"
                ]
            );

        User::factory()
            ->count(10)
            ->hasAttached(
                $datapools,
                ['role_id' => 1]
            )
            ->hasAttached(
                $datapools,
                [],
                'pinnedDatapools'
            )
            ->hasAttached(
                Datafile::factory()->count(3)
            )
            ->create();
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
