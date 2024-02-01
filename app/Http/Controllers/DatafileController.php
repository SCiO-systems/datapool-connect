<?php

namespace App\Http\Controllers;

use App\Models\Datafile;
use App\Models\Datapool;
use App\Models\User;
use App\Models\Views\AllUserDatafilesView;
use App\Models\Views\UserDatapoolRoleApiView;
use App\Services\DBService;
use App\Services\DataXService;
use App\Services\S3Service;
use App\Services\UtilitiesService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;

class DatafileController extends BaseController
{
    public function getUserDatafiles($userId, DBService $DBService)
    {
        if (is_string($userId)) {
            $response = $DBService->getUserDatafiles($userId);
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response('Invalid user ID', 400);
        }
    }

    public function addFilesToDatapool(Request $request, $userId, $datapoolId, DBService $DBService)
    {
        if (is_string($datapoolId)) {
            $datafiles = $request->input('datafiles');
            $response = $DBService->addFilesToDatapool($userId, $datapoolId, $datafiles);
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response('Invalid ID', 400);
        }
    }

    public function addDataFiles(Request $request, $userId, DBService $DBService)
    {
        $fileKey = $request->input('fileKey');
        Log::info($fileKey);
        $response = $DBService->addDataFiles($userId, $fileKey);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());

        }
    }

    public function getDatafileDatapools($userId, $datafileId, DBService $DBService)
    {
        $response = $DBService->getFileDatapools($datafileId);
        if ($response->isSuccessful()) {
            return $response;
        } else {
            return response($response->content(), $response->status());

        }
    }

    public function getDatapoolDatafiles($userId, $datapoolId, DBService $DBService)
    {
        if (is_string($userId)) {
            $response = $DBService->getDatapoolFiles($datapoolId);
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response('Invalid user ID', 400);
        }
    }

    public function getDatapoolDatafilesInverse($userId, $datapoolId, DBService $DBService)
    {
        if (is_string($userId)) {
            $response = $DBService->getFilesNotInDatapool($userId, $datapoolId);
            return response($response->getContent(), $response->getStatusCode());
        } else {
            return response('Invalid user ID', 400);
        }
    }

    public function generateCodebook(Request $request, DataXService $DataXService, $datapoolId)
    {
        $dataFile = $request->input("dataFile"); //s3 file key
        $version = $request->input("version");
        $destinationKey = $request->input("destinationKey");

        $temporarySignedUrl = Storage::disk('s3')->temporaryUrl($dataFile, now()->addMinutes(10));

        $body = [
            "datapoolId" => $datapoolId,
            "dataFile" => $temporarySignedUrl,
            "version" => $version,
        ];
        // Log the request body before sending the request
        Log::info('Request Body Before Sending: ' . print_r($body, true));

        $response = $DataXService->generateCodebook($body);
        Log::info(json_encode($response));

        $qvantumTempUrl = $response->json('download_link');
        $codebookKey = UtilitiesService::downloadAndStoreS3($qvantumTempUrl, $destinationKey);
        $codebookTempUrl = Storage::disk('s3')->temporaryUrl($codebookKey, now()->addMinutes(10));

        return response(["url" => $codebookTempUrl, "key" => $codebookKey]);

    }


    public function validateCodebook(Request $request, DataXService $DataXService)
    {
        $codebookKey = $request->input('codebook');

        $codebookKeyUrl = Storage::disk('s3')->temporaryUrl($codebookKey, now()->addMinutes(10));

        $response = $DataXService->validateCodebook($codebookKeyUrl);
        return $response;
    }


    public function generateVersion(Request $request, $userId, $datapoolId, DataXService $DataXService, DBService $DBService)
    {
        $dbResponse = $DBService->generateVersion($datapoolId, $request);
        if ($dbResponse->isSuccessful()) {
            $indexingResponse = $DataXService->prepareIndexing($request);
            $jobResponse = $DataXService->createJob($request, $indexingResponse);
            return $DBService->insertJob($userId, $datapoolId, $request->input('dataFileId'), $jobResponse);
        } else {
            return response($dbResponse->content(), $dbResponse->status());
        }
    }

    public function getPresignedUrl(Request $request, $userId, $datafileId, DBService $DBService)
    {
        try {
            return $DBService->getDatafileDownloadUrl($datafileId);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function deleteDatafile($userId, $datafileId, DBService $DBService)
    {
        try {
            $DBService->deleteDatafile($datafileId);
            return response('File deleted', 200);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }



    public function transformCrop(Request $request, DataXService $DataXService)
    {
        try {
            $s3Key = $request->input("s3Key");
            $model = $request->input("model");
            $s3TempUrl = Storage::disk('s3')->temporaryUrl($s3Key, now()->addMinutes(10));
            Log::info("S3TempUrl: " . $s3TempUrl);
            $qvantumTempUrl = $DataXService->generateCrop($s3TempUrl, $model);
            Log::info("qvantum url " . $qvantumTempUrl);
            return  $qvantumTempUrl["download_link"];
        } catch (Exception $e){
            return response($e->getMessage(), 400);

        }

    }

    public function cleanSurveyData(Request $request, $userid, $type, DataXService $DataXService, S3Service $S3Service)
    {
        try {
            if($type=='simple') {
                $s3DataKey = $request->input("s3DataKey");
                $s3DataTempUrl=$S3Service->generateTempUrl($s3DataKey);
                Log::info("S3TempUrl: " . $s3DataTempUrl);
                $qvantumTempUrl = $DataXService->simpleCleanData($s3DataTempUrl);
                Log::info("qvantum url " . $qvantumTempUrl);
            }
            else if($type=='full'){
                $s3DataKey = $request->input("s3DataKey");
                $s3FormKey = $request->input("s3FormKey");
                $s3CodebookKey = $request->input("s3CodebookKey");
                $s3DataTempUrl=$S3Service->generateTempUrl($s3DataKey);
                $s3FormTempUrl=$S3Service->generateTempUrl($s3FormKey);
                $s3CodebookTempUrl=$S3Service->generateTempUrl($s3CodebookKey);
                $qvantumTempUrl = $DataXService->fullCleanData($s3DataTempUrl, $s3FormTempUrl, $s3CodebookTempUrl);
                Log::info("qvantum url " . $qvantumTempUrl);
            }
            return  $qvantumTempUrl["download_link"];
        } catch (Exception $e){
            return response($e->getMessage(), 400);

        }

    }

}


