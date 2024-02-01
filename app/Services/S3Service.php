<?php

namespace App\Services;

use Aws\S3\S3Client;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class S3Service
{
 protected $s3;

    public function __construct()
    {
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' =>env('AWS_BUCKET'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }
    public function generateTempUrl($s3FileKey)
    {
        try {
            $s3TempUrl=Storage::disk('s3')->temporaryUrl($s3FileKey, now()->addMinutes(10));
            return $s3TempUrl;
        } catch (\Exception $e) {
            Log::info('Error generating temporary URL: ' . $e->getMessage());
            return (('Error generating temporary URL: ' . $e->getMessage()));
        }

    }

    public static function downloadAndStoreS3($tempS3Url, $destinationKey)
    {
        // Create a Guzzle HTTP client
        $httpClient = new Client();
        try {
            // Download the file from the temporary URL
            $response = $httpClient->get($tempS3Url);

            // Upload the downloaded file to the destination S3 bucket
            Storage::disk('s3')->put($destinationKey, $response->getBody());

            return $destinationKey;
        } catch (GuzzleException $e) {
//            echo 'Error: ' . $e->getMessage();
            return response($e->getMessage(), 400);
        }


    }

}


