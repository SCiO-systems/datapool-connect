<?php

namespace App\Http\Controllers;

use App\Models\Datapool;
use App\Models\User;
use App\Models\UserOld;
use App\Models\Views\UserDatapoolRoleApiView;
use App\Services\DataXService;
use App\Services\DBService;
use App\Services\OAuthService;
use App\Services\utilitiesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery\Exception;
use Predis\Client as PredisClient;

class ApiController extends Controller
{

    public function createApi($userId, $datapool_id, DBService $DBService, OAuthService $OAuthService) {
        if (is_string($userId)) {
            $authResponse = $OAuthService->createAPI($userId, $datapool_id);
            $OAuthService->createGrant($authResponse['client_id']);
            $response = $DBService->createApi($userId, $datapool_id, $authResponse['client_id']);
            if ($response->isSuccessful()) {
                return response($authResponse, $response->getStatusCode());
            } else {
                return response($authResponse, $response->status());
            }
        } else {
            return response('Invalid user ID', 400);
        }
    }

    public function deleteApi($userId, $datapool_id, $api_id, DBService $DBService) {
            $response = $DBService->softDeleteApi($api_id);
            if ($response->isSuccessful()) {
                return response($response->getContent(), $response->getStatusCode());
            } else {
                return response($response->content(), $response->status());
            }
    }

    // Get all APIs that belong to $userId
    public function getAllUserApis($userId, DBService $DBService) {
        $response = $DBService->getAllUserApis($userId);
        if ($response->isSuccessful()) {
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response($response->content(), $response->status());
        }
    }

    // Get all APIs that belong to $datapool_id, regardless of user
    public function getAllDatapoolApis($userId, $datapool_id,  DBService $DBService) {
        $response = $DBService->getAllDatapoolApis($datapool_id);
        if ($response->isSuccessful()) {
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response($response->content(), $response->status());
        }
    }
    // Get the Api of a specific user for a specific datapool
    public function getUserDatapoolApi($userId, $datapool_id,  DBService $DBService) {
        $response = $DBService->getUserDatapoolApi($userId, $datapool_id);
        if ($response->isSuccessful()) {
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response($response->content(), $response->status());
        }
    }


    public function getAPISecret($userId, $datapoolId, $apiId, OAuthService $OAuthService) {
        $response = $OAuthService->getAPISecret("$apiId");
        if ($response->successful()) {
            return response($response->body(), $response->status());
        } else {
            return response($response->body(), $response->status());
        }
    }
}
