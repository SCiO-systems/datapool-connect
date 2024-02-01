<?php

namespace App\Http\Controllers;

use App\Models\Datapool;
use App\Models\User;
use App\Models\Views\UserDatapoolRoleApiView;
use App\Services\DBService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class DatapoolUserController extends BaseController
{
    public function getDatapoolUsers($userId, $datapoolId, DBService $DBService) {
        if (is_string($datapoolId)) {
            $result = $DBService->getDatapoolUsers($datapoolId);
            return response($result->getContent(), $result->getStatusCode());
        } else {
            return response('Invalid ID', 400);
        }
    }

    public function getUsersNotInDatapool($userId, $datapoolId, DBService $DBService) {
        if (is_string($datapoolId)) {
            $result = $DBService->getUsersNotInDatapool($datapoolId);
            return response($result->getContent(), $result->getStatusCode());
        } else {
            return response('Invalid ID', 400);
        }
    }

    public function addUsersToDatapool(Request $request, $userId, $datapoolId, DBService $DBService) {
        if (is_string($datapoolId)) {
            $users = $request->input('users');
            $result = $DBService->addUsersToDatapool($datapoolId, $users);
            return response($result->getContent(), $result->getStatusCode());
        } else {
            return response('Invalid ID', 400);
        }
    }

    public function deleteUserFromDatapool($userId, $datapoolId, $datapoolUserId, DBService $DBService) {
        if (is_string($datapoolId)) {
            $result = $DBService->deleteUserFromDatapool($datapoolId, $datapoolUserId);
            return response($result->getContent(), $result->getStatusCode());
        } else {
            return response('Invalid ID', 400);
        }
    }

    public function updateUserRole($userId, $datapoolId, $datapoolUserId, $roleId, DBService $DBService) {
        if (is_string($datapoolId)) {
            $result = $DBService->updateUserRole($datapoolId, $datapoolUserId, $roleId);
            return response($result->getContent(), $result->getStatusCode());
        } else {
            return response('Invalid ID', 400);
        }
    }
}
