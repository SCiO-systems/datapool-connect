<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\API;
use App\Models\Datafile;
use App\Models\Datapool;
use App\Models\Role;
use App\Models\User;
use App\Services\DataXService;
use App\Services\OAuthService;
use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class DbController extends Controller
{
    public function testToken(): string
    {
        return response()->json('test');
    }

    public function testMakeUser() {
        $user = new User();
        $user->identity_provider_id = 'testinggggg';
        $user->save();

        $dp = new Datapool();
        $dp->mongo_id = '1234';
        $dp->alias = 'test';
        $dp->deleted = 0;
        $dp->save();

        $df = new Datafile();
        $df->key = 'testkey';
        $df->creation_time = now();
        $df->save();

        $api = new API();
        $api->auth_zero_id = '5';
        $api->save();

        $role = Role::find(1);
//        $role->role_id = 1;
//        $role->role_name = 'administrator';
//        $role->save();

        $user2 = User::find(1);
        $user2->datafiles();

        $user->datapools()->attach($dp->datapool_id, ['role_id' => $role->role_id, 'api_id' => $api->api_id]);
        $user->datafiles()->attach($df->datafile_id);
        $dp->datafiles()->attach($df->datafile_id, ['order' => 1]);

        return "ok";
    }
}

