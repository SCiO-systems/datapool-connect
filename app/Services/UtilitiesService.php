<?php

namespace App\Services;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

class UtilitiesService
{
    public static function transformAlias($aliasString)
    {
        $timestamp = (new DateTime())->getTimestamp();
        return Str::replace(' ', '_', Str::lower($aliasString)) . $timestamp;
    }

    public static function parseTimestamp($timestamp)
    {
        $floatSec = $timestamp / 1000.0;
        return DateTime::createFromFormat("U\.u", sprintf('%1.6F', $floatSec));
    }

    public static function parseS3FileKey($fileKey)
    {
        $stringFileKey = strval($fileKey);
        $parts = explode("/", $stringFileKey);

        // Find the position of the underscore (_) to determine the end of the timestamp
        $underscorePosition = strpos($parts[1], '_');
        // Extract the timestamp using substring
        $timestamp = substr($parts[1], 0, $underscorePosition);
        // Extract the file name with extension using substring
        $filename = substr($parts[1], $underscorePosition + 1);

        return ['fileKey' => $parts[1], 'timestamp' => $timestamp, 'filename' => $filename];
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
            return response($e->getMessage(), 400);
        }
    }


    public static function validateAuthId($authId) {
        if (Str::contains($authId, "|")) {
            $idPieces = explode("|", $authId);
            if ($idPieces[0] == "auth0") {
                if (Str::length($idPieces[1]) == 24) {
                    return true;
                }
            } else if ($idPieces[0] == "oauth2") {
                if ($idPieces[1] == "orcid-connection") {
                    if (Str::length($idPieces[2]) == 19) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
