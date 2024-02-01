<?php

namespace App\Http\Controllers;

use App\Services\DBService;
use App\Services\UtilitiesService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class UserController extends BaseController
{
    public function makeUser(Request $request, $identity_provider_id, DBService $DBService)
    {
        if (UtilitiesService::validateAuthId($identity_provider_id)) {
            $result = $DBService->addUser($request, $identity_provider_id);

            return response($result->getContent(), $result->getStatusCode());
        }
        return response("Invalid auth0 id", 400);
    }
}
