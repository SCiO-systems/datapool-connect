<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\UtilitiesService;

class DataXService
{
    public function __construct()
    {
        $this->domain = 'https://6f0a0394-9236-42f7-bb18-bb80ae279d35.mock.pstmn.io/';
        $this->clientID = env('SCIO_SERVICES_CLIENT_ID');
        $this->clientSecret = env('SCIO_SERVICES_CLIENT_SECRET');
        $this->requestTimeout = env('REQUEST_TIMEOUT_SECONDS', 10);
        $this->authUrl = env('SCIO_SERVICES_AUTH_URL');
        $this->audience = env('SCIO_SERVICES_AUDIENCE_DATAPOOL');
        $this->qvantum = env('QVANTUM_DOMAIN');

        if (env('APP_ENV') === 'testing') {
            $this->accessToken = 'mock token';
            $this->expiresIn = 1234;
        } else {
            $response = Http::timeout($this->requestTimeout)
                ->post($this->authUrl, [
                    'client_id' => $this->clientID,
                    'client_secret' => $this->clientSecret,
                    'audience' => $this->audience,
                    'grant_type' => 'client_credentials'
                ])->throw();

            $this->accessToken = $response->json('access_token');
            $this->expiresIn = (int)$response->json('expires_in');
            Log::info("Managed to get a token", [$this->accessToken, $this->expiresIn]);
        }
    }

    public function getHistogramData($variable, $query, $index)
    {
        try {
            $response = Http::timeout($this->requestTimeout)
                ->acceptJson()
                ->asJson()
                ->withToken($this->accessToken)
                ->post($this->qvantum . "/api/datapool/histogram/" . $index . "/" . $variable . '/1', $query);

            $response->throw();

            Log::info('Qvantum histogram data response: ', [$response]);
            return response($response->body(), $response->status());
        } catch (Exception $e) {
            Log::info('Qvantum histogram data exception: ', [$e->getMessage()]);
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function getDatapoints($index)
    {
        try {
            $response = Http::timeout($this->requestTimeout)
                ->acceptJson()
                ->asJson()
                ->withToken($this->accessToken)
                ->get($this->qvantum . "/api/datapool/aggregation/" . $index . "/enriched_datasetid");

            $response->throw();
            Log::info('Qvantum datapoints response: ', [$response]);
            return response($response->body(), $response->status());
        } catch (Exception $e) {
            Log::info('Qvantum datapoints exception: ', [$e->getMessage()]);
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function exportCsv($index, $query): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            $response = Http::timeout($this->requestTimeout)
                ->acceptJson()
                ->asJson()
                ->withToken($this->accessToken)
                ->post($this->qvantum . "/api/datapool/query/" . $index . '/export', $query);

            $response->throw();
            Log::info('Qvantum export csv response: ', [$response]);
            return response($response->body(), $response->status());
        } catch (Exception $e) {
            Log::info('Qvantum export csv exception: ', [$e->getMessage()]);
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function getDatapoolSearchResults($datapoolId, $from, $to, $query)
    {
        try {
            $response = Http::timeout($this->requestTimeout)
                ->acceptJson()
                ->asJson()
                ->withToken($this->accessToken)
                ->post($this->qvantum . "/api/datapool/query/" . $datapoolId . "/" . $from . "/" . $to, $query);

            $response->throw();
            Log::info('Qvantum advanced search results response: ', [$response]);
            return response($response->body(), $response->status());
        } catch (Exception $e) {
            Log::info('Qvantum advanced search results exception: ', [$e->getMessage()]);
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function createDatapool(): \Illuminate\Http\Response
    {
        try {
            $body = ['countries' => [], "regions" => [], "crops" => [], "records" => 0, "filters" => [], "histogramVariables" => []];

            $response = Http::timeout($this->requestTimeout)
                ->acceptJson()
                ->asJson()
                ->withToken($this->accessToken)
                ->withBody(json_encode($body))
                ->post($this->qvantum . "/api/datapool/");

            $response->throw();
            Log::info('Qvantum create datapool response: ', [$response]);
            return response($response->body(), $response->status());
        } catch (Exception $e) {
            Log::info('Qvantum create datapool exception: ', [$e->getMessage()]);
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function getDatapool($datapoolId): \Illuminate\Http\Response
    {
        try {
            $response = Http::timeout($this->requestTimeout)
                ->acceptJson()
                ->asJson()
                ->withToken($this->accessToken)
                ->get($this->qvantum . "/api/datapool/" . $datapoolId);

            $response->throw();
            Log::info('Qvantum datapool: ', [$response]);
            return response($response->body(), $response->status());
        } catch (Exception $e) {
            Log::info('Qvantum get datapool by id exception: ', [$e->getMessage()]);
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function generateCodebook($body)
    {

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->qvantum . '/api/datapool/codebook/template', $body);
            // Log the response body
            $response->throw();
            Log::info('Generate response body: ' . $response->body());
            return $response;
        } catch (\Exception $e) {
            // Log any exceptions that may occur during the request
            Log::error('Qvantum codebook generation exception : ' . $e->getMessage());
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function validateCodebook($codebookUrl)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->qvantum . '/api/datapool/codebook/validate', $codebookUrl);
            Log::info('Validate response body: ' . $response->body());

            $response->throw();
            Log::info('Qvantum codebook validation exception: ', [$response]);
            return response($response->body(), $response->status());
        } catch (Exception $e) {
            Log::info('Qvantum codebook validation exception: ', [$e->getMessage()]);
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function prepareIndexing(Request $request)
    {
        try {
            $codebookKey=$request->input('codebook');
            $dataFileKey=$request->input('dataFileKey');

            $codebookKeyUrl=Storage::disk('s3')->temporaryUrl($codebookKey, now()->addMinutes(10));
            $dataFileUrl =Storage::disk('s3')->temporaryUrl($dataFileKey, now()->addMinutes(10));
            $body=["codebook"=>$codebookKeyUrl, "dataFile"=>$dataFileUrl];

            $response = Http::withHeaders([
                'Authorization' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->qvantum . '/api/datapool/resources', $body);
            Log::info('Prepare indexing response body: ' . $response->body());

            $response->throw();
            Log::info('Qvantum prepare indexing result: ', [$response]);
            $decodedResponse = json_decode($response->body(), true);
            Log::info($decodedResponse);
            if (isset($decodedResponse['download_link'])) {
                $versionQvantumUrl = $decodedResponse['download_link'];
                //Store the generated version on s3
                Storage::disk('s3')->put('versions/' . $decodedResponse['key'], $versionQvantumUrl);
                return response($response->body(), $response->status());
            }
            else
            {
                return response('Download link not found in the Qvantum response', 500);
            }
        } catch (Exception $e) {
            Log::info('Qvantum version creation exception: ', [$e->getMessage()]);
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function createJob(Request $request, Response $indexingResponse)
    {
        try {
            $responseArray = json_decode($indexingResponse->getContent(), true);

            $body=[
                "allFile"=> $responseArray['download_link'],
                "alias"=>$request->input('alias'),
                "version"=>$request->input('version')
            ];
            $response = Http::withHeaders([
                'Authorization' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->qvantum . '/api/datapool/job', $body);
            Log::info('Create job response body: ' . $response->body());

            $response->throw();
            Log::info('Qvantum job created successfully: ', [$response]);
            return response($response->body(), $response->status());
        } catch (Exception $e) {
            Log::info('Qvantum job creation exception: ', [$e->getMessage()]);
            return response($e->getMessage(), 500);
        }
    }

    public function getJob($jobId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->qvantum . '/api/datapool/job/'.$jobId);
            Log::info('Get job response body: ' . $response->body());

            $response->throw();
            Log::info('Qvantum job found successfully: ', [$response]);
            return response($response->body(), $response->status());
        } catch (Exception $e) {
            Log::info('Qvantum job not found : ', [$e->getMessage()]);
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function getIndexMetadata($codebookId, $alias)
    {
        try {
            $codebookTempUrl = Storage::disk('s3')->temporaryUrl($codebookId, now()->addMinutes(10));
            $response = Http::withHeaders([
                'Authorization' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->qvantum . '/api/datapool/metadata', ['codebook' => $codebookTempUrl, 'alias' => $alias]);
            Log::info('Get index metadata response body: ' . $response->body());

            $response->throw();
            Log::info('Qvantum metadata index response: ', [$response]);
            return response($response->body(), $response->status());
        } catch (Exception $e) {
            Log::info('Qvantum metadata index exception: ', [$e->getMessage()]);
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function updateDatapool($body, $dp, $userId)
    {
        try {
            $body['id'] = $dp->mongo_id;
            $response = Http::withHeaders([
                'Authorization' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->put($this->qvantum . '/api/datapool/'.$dp->mongo_id, $body);
            Log::info('Update datapool response body: ' . $response->body());

            $response->throw();
            Log::info('Qvantum datapool update exception: ', [$response]);
            return response($response->body(), $response->status());
        } catch (Exception $e) {
            Log::info('Qvantum datapool update exception: ', [$e->getMessage()]);
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function generateCrop($s3TempUrl,$model)
    {
        $body=["link"=>$s3TempUrl];
        Log::info("this is the body for the qvantum request ",$body);
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->qvantum . '/api/datapool/croptransform/'.$model, $body);

            $response->throw();
            // Log the response body
            Log::info('Qvantum transformed crop download link: ' . $response->body());
            return $response;
        } catch (\Exception $e) {
            Log::error('Qvantum crop transformer exception : ' . $e->getMessage());
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function simpleCleanData($s3DataTempUrl)
    {

        $response = Http::timeout($this->requestTimeout)
            ->post($this->authUrl, [
                'client_id' => $this->clientID,
                'client_secret' => $this->clientSecret,
                'audience' => env('SCIO_SERVICES_AUDIENCE_SCRIBE'),
                'grant_type' => 'client_credentials'
            ]);

        $response->throw();

        $accessToken = $response->json('access_token');
        $expiresIn = (int)$response->json('expires_in');
        Log::info("Managed to get a token", [$accessToken, $expiresIn]);

        $body=["data"=>$s3DataTempUrl];

        Log::info("this is the body for the qvantum request ",$body);
        try {
            $response = Http::withHeaders([
                'Authorization' => $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->qvantum . '/api/scribe/allstata/simple/false', $body);
            // Log the response body
            Log::info('Qvantum cleaned survey data download link: ' . $response->body());
            return $response;
        } catch (\Exception $e) {
            Log::error('Qvantum survey data cleaner exception : ' . $e->getMessage());
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function fullCleanData($s3DataTempUrl, $s3FormTempUrl, $s3CodebookTempUrl)
    {

        $response = Http::timeout($this->requestTimeout)
            ->post($this->authUrl, [
                'client_id' => $this->clientID,
                'client_secret' => $this->clientSecret,
                'audience' => env('SCIO_SERVICES_AUDIENCE_SCRIBE'),
                'grant_type' => 'client_credentials'
            ])->throw();

        $accessToken = $response->json('access_token');
        $expiresIn = (int)$response->json('expires_in');
        Log::info("Managed to get a token", [$accessToken, $expiresIn]);


        $body=[
            "form"=>$s3FormTempUrl,
            "data"=>$s3DataTempUrl,
            "codebook"=>$s3CodebookTempUrl
        ];
        Log::info("this is the body for the qvantum request ",$body);
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->qvantum . '/api/scribe/allstata/simple/false', $body);
            // Log the response body
            Log::info('Qvantum cleaned survey data download link: ' . $response->body());
            return $response;
        } catch (\Exception $e) {
            Log::error('Qvantum survey data cleaner exception : ' . $e->getMessage());
            return response($e->getMessage(), $e->getCode());
        }
    }

}
