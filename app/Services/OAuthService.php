<?php

namespace App\Services;

use Auth0\SDK\Configuration\SdkConfiguration;
use http\Message\Body;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Predis\Client as PredisClient;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\PredisAdapter;

class OAuthService
{
    protected $audience;
    protected $clientID;
    protected $domain;
    protected $clientSecret;

    public function __construct()
    {
        $this->audience = env('SCIO_SERVICES_AUDIENCE_DATAPOOL');
        $this->domain = env('AUTH0_DOMAIN');
        $this->clientID = env('SCIO_SERVICES_CLIENT_ID');
        $this->clientSecret = env('SCIO_SERVICES_CLIENT_SECRET');
    }

    public function getManagementAPIToken($testData = []) {
        $apiURL = "https://" . $this->domain . "/oauth/token";

        if (env('APP_ENV') === 'testing') {
            $postData = $testData;
        } else {
            $postData = [
                "grant_type" => "client_credentials",
                "client_id" => $this->clientID,
                "client_secret" => $this->clientSecret,
                "audience" => "https://sciosystems.eu.auth0.com/api/v2/"
            ];
        }

        Log::info("Post fields: ", $postData);

        $response = Http::asForm()->post($apiURL, $postData);
        Log::info("Management API Token Response", [$response]);

        if ($response->successful()) {
            $responseBody = json_decode($response->getBody());
            return $responseBody->access_token;
        }
        return null;
    }

    public function createGrant($clientId) {
        $accessToken = $this->getManagementAPIToken();

        $postHeaders = [
            "Authorization" => "Bearer " . $accessToken,
        ];

        $body = [
            "client_id" => $clientId,
            "audience" => $this->audience,
            "scope" => []
        ];

        Log::info($postHeaders);
        $postURL = "https://" . $this->domain . "/api/v2/client-grants";

        $response = Http::withHeaders($postHeaders)->post($postURL, $body);
        $responseBody = json_decode($response->getBody());
        Log::info("Authorizing API through management API with body ", [$responseBody]);
        return $responseBody;
    }

    public function createAPI($userId, $datapoolId) {
        $accessToken = $this->getManagementAPIToken();

        $postHeaders = [
            "Authorization" => "Bearer " . $accessToken,
        ];

        $body = [
            "name" => $userId . "_" . $datapoolId,
            "app_type" => "non_interactive",

        ];

        Log::info("Sending request with body: ", $body);
        Log::info("And headers: ", [$postHeaders]);

        $postURL = "https://" . $this->domain . "/api/v2/clients";
        Log::info($postURL);

        $response = Http::withHeaders($postHeaders)->post($postURL, $body);
        $responseBody = json_decode($response->getBody(), true);
        Log::info("Creating API through management API with body ", $responseBody);
        return ["client_id" => $responseBody['client_id'], "client_secret" => $responseBody['client_secret']];
    }

    public function getAPISecret($apiId) {
        $accessToken = $this->getManagementAPIToken();

        $headers = [
            "Authorization" => "Bearer " . $accessToken,
        ];

        Log::info("And headers: ", [$headers]);

        $url = "https://" . $this->domain . "/api/v2/clients/" . $apiId . "?fields=client_secret&include_fields=true";
        Log::info($url);

        $response = Http::withHeaders($headers)->get($url);
        $responseBody = json_decode($response->getBody(), true);
        Log::info("Getting API credentials ", $responseBody);
        return $response;
    }


    public function deleteGrant(Request $request) {
        $accessToken = $this->getManagementAPIToken();

        $postHeaders = [
            "Authorization" => "Bearer " . $accessToken,
        ];

        Log::info($postHeaders);
        $postURL = "https://" . $this->domain . "/api/v2/client-grants";

        $response = Http::withHeaders($postHeaders)->delete($postURL, $request);
        $responseBody = json_decode($response->getBody());
        Log::info("Deleting client grant through management API with body ", [$responseBody]);
        return $responseBody;
    }

    public function deleteAPI(Request $request) {
        $accessToken = $this->getManagementAPIToken();

        $postHeaders = [
            "Authorization" => "Bearer " . $accessToken,
        ];

        Log::info($postHeaders);
        $postURL = "https://" . $this->domain . "/api/v2/resource-servers";

        $response = Http::withHeaders($postHeaders)->delete($postURL, $request);
        $responseBody = json_decode($response->getBody());
        Log::info("Authorizing API through management API with body ", [$responseBody]);
        return $responseBody;
    }

    public function validateAccessToken(): array
    {
        //Retrieve the header containing the access token
        try {
            $header = $_SERVER[('HTTP_AUTHORIZATION')];
        } catch (\Exception $e) {
            Log::error("Something went wrong with the Authorization header: ", [$e]);
            return ["result" => "failed", "errorMessage" => "No access token provided", "code" => 401];
        }

        $jwt = trim($header);

        // Remove the 'Bearer ' prefix, if present, in the event we're getting an Authorization header that's using it.
        if (substr($jwt, 0, 7) === 'Bearer ') {
            $jwt = substr($jwt, 7);
        }

        try {
            $config = new SdkConfiguration(
                strategy: SdkConfiguration::STRATEGY_API,
                domain: 'https://sciosystems.eu.auth0.com',
                audience: [env('SCIO_SERVICES_AUDIENCE_DATAPOOL')]
            );

            $auth0 = new \Auth0\SDK\Auth0($config);

            $redis = Redis::connection();
            $cache = new RedisAdapter($redis->client());
            $auth0->configuration()->setTokenCache($cache);

            $token = $auth0->decode($jwt, null, null, null, null, null, null, \Auth0\SDK\Token::TYPE_ACCESS_TOKEN);

            Log::info("Decoded token:", $token->toArray());
            return ["result" => "Authentication successful", "code" => 200];
        } catch (\Exception $e) {
            Log::error("Something went wrong with the Authorization header: ", [$e]);
            return ["result" => "Authentication failed", "errorMessage" => $e->getMessage(), "code" => 401];
        }
    }
}
