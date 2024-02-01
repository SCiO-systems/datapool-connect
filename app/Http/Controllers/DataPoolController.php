<?php

namespace App\Http\Controllers;

use App\Models\Datapool;
use App\Models\User;
use App\Models\UserOld;
use App\Models\Views\UserDatapoolRoleApiView;
use App\Services\DataXService;
use App\Services\DBService;
use App\Services\utilitiesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery\Exception;
use Predis\Client as PredisClient;

class DataPoolController extends Controller
{

    public function getDatapoolSearchResults($datapoolId, $from, $to, Request $request, DataXService $dataXService): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        $query = $request->all();
        $response = $dataXService->getDatapoolSearchResults($datapoolId, $from, $to, $query);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function getPrivateDatapoolSearchResults($userId, $datapoolId, $from, $to, Request $request, DataXService $dataXService): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        $query = $request->all();
        $response = $dataXService->getDatapoolSearchResults($datapoolId, $from, $to, $query);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function exportCsv($index, Request $request, DataXService $dataXService): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        $query = $request->all();
        $response = $dataXService->exportCsv($index, $query);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function privateDatapoolExportCsv($userId, $index, Request $request, DataXService $dataXService): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        $query = $request->all();
        $response = $dataXService->exportCsv($index, $query);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function getHistogramData($index, $variable, DataXService $dataXService, Request $request): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        $query = $request->all();
        $response = $dataXService->getHistogramData($variable, $query, $index);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function getPrivateDatapoolHistogramData($userId, $index, $variable, DataXService $dataXService, Request $request): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        $query = $request->all();
        $response = $dataXService->getHistogramData($variable, $query, $index);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function getDatapoints($index, DataXService $dataXService): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        $response = $dataXService->getDatapoints($index);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());
        }
    }
    public function getPrivateDatapoolsDatapoints($userid, $index, DataXService $dataXService): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        $response = $dataXService->getDatapoints($index);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function getUserPrivateDatapools($userId, DBService $DBService, DataXService $DataXService) {
        if (is_string($userId)) {
            $this->runJobUpdatesProcess($userId, $DBService, $DataXService);

            $response = $DBService->getDatapools('private', $userId);
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response('Invalid user ID', 400);
        }
    }

    public function getUserDatapools($userId, DBService $DBService, DataXService $DataXService) {
        if (is_string($userId)) {
            $this->runJobUpdatesProcess($userId, $DBService, $DataXService);

            $response = $DBService->getDatapools('user', $userId);
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response('Invalid user ID', 400);
        }
    }

    private function runJobUpdatesProcess($userId, DBService $DBService, DataXService $DataXService) {
        $jobsArray = $DBService->getPendingJobs($userId);
        Log::info($jobsArray);
        $dataXResults = [];
        foreach ($jobsArray as $job) {
            $response = $DataXService->getJob($job['mongo_id']);
            if ($response->isSuccessful()) {
                $responseArray = json_decode($response->getContent(), true);
                $dataXResults[] = $responseArray;
            }
        }
        Log::info($dataXResults);
        $dp = $DBService->updateJobResults($dataXResults, $userId);
        if ($dp) {
            Log::info($dp);
            $response = $DataXService->getIndexMetadata($dp->datafiles[0]->pivot->codebook, $dp->alias);
            Log::info($response);
            Log::info($dp);
            if ($response->isSuccessful()) {
                $responseArray = json_decode($response->getContent(), true);
                $DataXService->updateDatapool($responseArray, $dp, $userId);
                $DBService->updateDatapoolMetadata($responseArray, $dp);
                $DBService->parseTagMetadata($responseArray, $dp);
            }
        }
    }


    public function createDatapool(Request $request, $userId, DataXService $DataXService, DBService $DBService) {
        if (is_string($userId)) {
            $response = $DataXService->createDatapool($request->getContent());
            Log::info($response->status());
            if ($response->isSuccessful()) {
                $datapoolId = json_decode($response->content(), false)->id;
                $name = $request->input('name');

                $response = $DBService->createDatapool($userId, $datapoolId, $name);
                return response($response->getContent(), $response->getStatusCode());
            } else {
                return response($response->content(), $response->status());
            }
        } else {
            return response('Invalid user ID', 400);
        }
    }

    public function getDatapoolById($datapoolId, DataXService $dataXService): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        $response = $dataXService->getDatapool($datapoolId);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function getPrivateDatapoolById($userId, $datapoolId, DataXService $dataXService): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        $response = $dataXService->getDatapool($datapoolId);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function getPublicDatapools(DBService $DBService): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\GuzzleHttp\Promise\PromiseInterface|\Exception|\Illuminate\Http\Client\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|null
    {
        $response = $DBService->getDatapools('public', null);
        return response($response->getContent(), $response->getStatusCode());
    }

    public function deleteDatapoolById($userId, $datapoolId, DBService $DBService): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        $dp = Datapool::where('mongo_id', $datapoolId)->first();
        $apis = $DBService->getAllDatapoolApis($dp->datapool_id);
        $apis = $apis->getContent();
        $apis = json_decode($apis, true);
        $apis = $apis["apis"];
        Log::info('Apis ', $apis);
        foreach ($apis as  $api) {
            Log::info('Api ', [$api["auth_zero_id"]]);
            $DBService->softDeleteApi($api["auth_zero_id"]);
        }
        $response = $DBService->softDeleteDatapool($datapoolId);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function publishDatapool($userId, $datapoolId, DBService $DBService)
    {
        $response = $DBService->changeDatapoolPublishStatus($datapoolId, 'public');
        return $response;
    }

    public function unpublishDatapool($userId, $datapoolId, DBService $DBService)
    {
        $response = $DBService->changeDatapoolPublishStatus($datapoolId, 'private');
        return $response;
    }

    public function pinDatapool($userId,  $datapoolId, DBService $DBService) {
        $response = $DBService->pinDatapool($userId, $datapoolId);
        Log::info($response->status());
        if ($response->isSuccessful()) {
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function unpinDatapool($userId,  $datapoolId, DBService $DBService) {
        $response = $DBService->unpinDatapool($userId, $datapoolId);
        Log::info($response->status());
        if ($response->isSuccessful()) {
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function getPinnedDatapools($userId, DBService $DBService) {
        $user = User::where('identity_provider_id', $userId)->first();
        $response = $DBService->getPinnedDatapools($user->user_id);
        Log::info($response->status());
        if ($response->isSuccessful()) {
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response($response->content(), $response->status());
        }
    }

    public function renameDatapool(Request $request, $userId, $datapoolId, DBService $DBService) {
        $name = $request->input('name');
        $response = $DBService->renameDatapool($datapoolId, $name);
        return $response;
    }

    public function editDatapoolMetadata(Request $request, $userId, $datapoolId, DBService $DBService) {
        $body = $request->all();
        $body = json_decode(json_encode($body), FALSE);
        $response = $DBService->editDatapoolMetadata($body->description, $body->citation, $body->license, $datapoolId);
        return $response;
    }

    public function addDatapoolMetadataAndPublish(Request $request, $userId, $datapoolId, DBService $DBService) {
        $body = $request->all();
        $body = json_decode(json_encode($body), FALSE);
        $metadataResponse = $DBService->editDatapoolMetadata($body->description, $body->citation, $body->license, $datapoolId);
        $publishResponse = $DBService->changeDatapoolPublishStatus($datapoolId, 'public');
        return $metadataResponse . $publishResponse;
    }

    public function getCurrentCodebookDownloadUrl($userId, $datapoolId, DBService $DBService) {
        try {
            return $DBService->getCurrentCodebookPresignedUrl($datapoolId);
        } catch (Exception $e){
            return response($e->getMessage(), 400);
        }
    }

}
