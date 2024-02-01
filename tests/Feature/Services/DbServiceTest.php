<?php

namespace Services;

use App\Models\Datapool;
use App\Models\User;
use App\Services\DBService;
use Dflydev\DotAccessData\Data;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DbServiceTest extends TestCase
{
    use DatabaseTransactions;
    protected static bool $initial_seed = false;

    protected DBService $dbService;

    public function __construct(string $name)
    {
        $this->dbService = new DBService();
        parent::__construct($name);
    }

    public function setUp(): void
    {
        parent::setUp();

        if (!static::$initial_seed) {
            Artisan::call('migrate:fresh');
            Artisan::call(
                'db:seed', ['--class' => 'DatabaseSeeder']
            );
            static::$initial_seed = true;
        }
    }

    public function test_create_datapool_valid(): void
    {
        $user = User::factory()->create();

        $result = $this->dbService->createDatapool($user->identity_provider_id, fake()->uuid(), 'nameeee');

        $this->assertTrue($result->getStatusCode() == 200);
    }

    public function test_create_datapool_invalid_user(): void
    {
        $result = $this->dbService->createDatapool('bad', fake()->uuid(), 'nameeee');

        $this->assertTrue($result->getStatusCode() == 400);
    }

    public function test_get_datapool_users_valid_datapool(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'desc')->first();

        $result = $this->dbService->getDatapoolUsers($dp->mongo_id);

        $this->assertTrue($result->getStatusCode() == 200);
    }

    public function test_get_users_not_in_datapool(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'desc')->first();

        $result = $this->dbService->getUsersNotInDatapool($dp->mongo_id);

        $this->assertTrue($result->getStatusCode() == 200);
    }

    public function test_add_users_to_datapool_valid(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'desc')->first();
        $user = User::factory()->create();

        $result = $this->dbService->addUsersToDatapool($dp->mongo_id, [$user]);

        $this->assertTrue($result->getStatusCode() == 200);
    }

    public function test_add_users_to_datapool_invalid_datapool(): void
    {
        $user = User::factory()->create();

        $result = $this->dbService->addUsersToDatapool("bad", [$user]);

        $this->assertTrue($result->getStatusCode() == 400);
    }

    public function test_add_users_to_datapool_invalid_user(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'desc')->first();
        $user = User::factory()->create();

        $result = $this->dbService->addUsersToDatapool($dp->mongo_id, [["user_id" => "bad"]]);

        $this->assertTrue($result->getStatusCode() == 400);
    }

    public function test_delete_datapool_user_valid(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'asc')->first();
        $user = $dp->users()->first();

        Log::info("Datapool in question: ", [$dp]);
        Log::info("Attached User: ", [$user]);

        $result = $this->dbService->deleteUserFromDatapool($dp->mongo_id, $user->identity_provider_id);

        $this->assertTrue($result->getStatusCode() == 200);
    }

    public function test_delete_datapool_user_invalid_datapool(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'asc')->first();
        $user = $dp->users()->first();

        $result = $this->dbService->deleteUserFromDatapool('bad', $user->identity_provider_id);

        $this->assertTrue($result->getStatusCode() == 400);
    }

    public function test_delete_datapool_user_invalid_user(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'asc')->first();
        $user = User::factory()->create();

        $result = $this->dbService->deleteUserFromDatapool($dp->mongo_id, 'bad');

        $this->assertTrue($result->getStatusCode() == 400);
    }

    public function test_delete_datapool_user_invalid_no_relationship(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'asc')->first();
        $user = User::factory()->create();

        $result = $this->dbService->deleteUserFromDatapool($dp->mongo_id, $user->identity_provider_id);

        $this->assertTrue($result->getStatusCode() == 400);
    }

    public function test_update_user_role_valid(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'asc')->first();
        $user = $dp->users()->first();

        $result = $this->dbService->updateUserRole($dp->mongo_id, $user->identity_provider_id, 1);

        $this->assertTrue($result->getStatusCode() == 200);
    }

    public function test_update_user_role_invalid_datapool(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'asc')->first();
        $user = $dp->users()->first();

        $result = $this->dbService->updateUserRole('bad', $user->identity_provider_id, 1);

        $this->assertTrue($result->getStatusCode() == 400);
    }

    public function test_update_user_role_invalid_user(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'asc')->first();
        $user = $dp->users()->first();

        $result = $this->dbService->updateUserRole($dp->mongo_id, 'bad', 1);

        $this->assertTrue($result->getStatusCode() == 400);
    }

    public function test_update_user_role_invalid_no_relationship(): void
    {
        $dp = Datapool::orderBy('datapool_id', 'asc')->first();
        $user = User::factory()->create();

        $result = $this->dbService->updateUserRole($dp->mongo_id, $user->identity_provider_id, 1);

        $this->assertTrue($result->getStatusCode() == 400);
    }

    public function test_get_user_datafiles_valid(): void
    {
        $user = User::orderBy('user_id', 'asc')->first();

        $result = $this->dbService->getUserDatafiles($user->identity_provider_id);
        $json = json_decode($result->getContent(), true);

        $this->assertTrue(count($json) == 3);
    }

    public function test_add_datafiles_valid(): void
    {
        $user = User::orderBy('user_id', 'asc')->first();
        $fileKeys = ["uploads/" . fake()->unixTime . "_" . fake()->asciify('**************') . ".csv"];

        $result = $this->dbService->addDataFiles($user->identity_provider_id, $fileKeys);

        $this->assertTrue($result->getStatusCode() == 200);
    }


    public function test_pin_datapool_valid(): void
    {
        $user = User::factory()->create();
        $datapool = Datapool::orderBy('datapool_id', 'asc')->first();
        $result = $this->dbService->pinDatapool($user->identity_provider_id, $datapool->datapool_id);

        $this->assertTrue($result->getStatusCode() == 200);
    }

    public function test_pin_datapool_invalid_user(): void
    {
        $datapool = Datapool::orderBy('datapool_id', 'asc')->first();
        $result = $this->dbService->pinDatapool('bad', $datapool->datapool_id);

        $this->assertTrue($result->getStatusCode() == 400);
    }

    public function test_pin_datapool_invalid_pin_exists(): void
    {
        $user = User::factory()->create();
        $datapool = Datapool::orderBy('datapool_id', 'asc')->first();
        $this->dbService->pinDatapool($user->identity_provider_id, $datapool->datapool_id);
        $result = $this->dbService->pinDatapool($user->identity_provider_id, $datapool->datapool_id);

        $this->assertTrue($result->getStatusCode() == 409);
    }

    public function test_unpin_datapool_valid(): void
    {
        $user = User::factory()->create();
        $datapool = Datapool::orderBy('datapool_id', 'asc')->first();
        $this->dbService->pinDatapool($user->identity_provider_id, $datapool->datapool_id);
        $result = $this->dbService->unpinDatapool($user->identity_provider_id, $datapool->datapool_id);

        $this->assertTrue($result->getStatusCode() == 200);
    }

    public function test_unpin_datapool_invalid_user(): void
    {
        $user = User::factory()->create();
        $datapool = Datapool::orderBy('datapool_id', 'asc')->first();
        $this->dbService->pinDatapool($user->identity_provider_id, $datapool->datapool_id);
        $result = $this->dbService->unpinDatapool('bad', $datapool->datapool_id);

        $this->assertTrue($result->getStatusCode() == 400);
    }

    public function test_unpin_datapool_invalid_pin_doesnt_exist(): void
    {
        $user = User::factory()->create();
        $datapool = Datapool::orderBy('datapool_id', 'asc')->first();
        $result = $this->dbService->unpinDatapool($user->identity_provider_id, $datapool->datapool_id);

        $this->assertTrue($result->getStatusCode() == 409);
    }

    public function test_get_user_pinned_datapools_valid(): void
    {
        $user = User::orderBy('user_id', 'asc')->first();

        $result = $this->dbService->getPinnedDatapools($user->user_id);
        $json = json_decode($result->getContent(), true);

        $this->assertTrue(count($json) == 3);
    }
}
