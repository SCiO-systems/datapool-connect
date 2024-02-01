<?php

namespace App\Http\Controllers;

use App\Models\Datafile;
use App\Services\DBService;
use Illuminate\Routing\Controller;

use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class UppyController extends Controller
{
    protected $s3;

    public function __construct()
    {
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    public function getUploadParameters(Request $request)
    {
        $fileKey = $request->input('fileKey');// The s3 destination of the file to be uploaded

        if ($this->s3->doesObjectExist((env('AWS_BUCKET')),$fileKey))
            return response()->json(['message' => 'File Already Exists'], 400);
        else {
            try {
                $cmd = $this->s3->getCommand('PutObject', [
                    'Bucket' => env('AWS_BUCKET'),
                    'Key' => $fileKey,
                ]);

                $request = $this->s3->createPresignedRequest($cmd, '+1 hour'); // Adjust the expiration time as needed

                $presignedUrl = (string)$request->getUri();

                $parameters = [
                    'method' => 'PUT',
                    'url' => $presignedUrl,
                    'headers' => [
                        'Content-Type' => "¯\_(ツ)_/¯",
                    ],
                ];

                return response()->json(['parameters' => $parameters]);
            } catch (AwsException $e) {
                return response()->json(['error' => $e->getAwsErrorMessage()], 400);
            }
        }

    }

    public function initiateUpload(Request $request)
    {
        // Handle the initial file upload request and initiate the multi-part upload

        $fileKey = $request->input('fileKey');
        if ($this->s3->doesObjectExist((env('AWS_BUCKET')),$fileKey))
            return response()->json(['message' => 'File Already Exists'], 400);
        else {
            try {
                $result = $this->s3->createMultipartUpload([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key' =>$fileKey,
                ]);

                $uploadId = $result['UploadId'];

                return response()->json(['uploadId' => $uploadId,'fileKey'=>$fileKey]);
            } catch (AwsException $e) {
                return response()->json(['error' => $e->getAwsErrorMessage()], 400);
            }
        }

    }

    public function getUploadedParts(Request $request, $id)
    {
        $fileKey = $request->query('fileKey');

        try {
            $result = $this->s3->listParts([
                'Bucket' => env('AWS_BUCKET'),
                'Key' =>$fileKey,
                'UploadId' => $id,
            ]);
            $parts = $result['Parts'];
            if (!$parts) //no parts uploaded
                return response()->json([]);
            else
                return response()->json($parts);


        } catch (AwsException $e) {
            return response()->json(['error' => $e->getAwsErrorMessage()], 400);
        }
    }


    public function getPartPresignedUrl(Request $request, $id, $partNumber)
    {
        $fileKey = $request->query('fileKey');

        if ($this->s3->doesObjectExist((env('AWS_BUCKET')),$fileKey))
            return response()->json(['message' => 'File Already Exists'], 400);
        else {
            try {
                $cmd = $this->s3->getCommand('UploadPart', [
                    'Bucket' => env('AWS_BUCKET'),
                    'Key' =>$fileKey,
                    'PartNumber' => $partNumber,
                    'UploadId' => $id,
                ]);

                $request = $this->s3->createPresignedRequest($cmd, '+1 hour');
                return response()->json(['url' => (string)$request->getUri()]);

            } catch (AwsException $e) {
                return response()->json(['error' => $e->getAwsErrorMessage()], 400);
            }
        }
    }

    public function completeMultipartUpload(Request $request,$id, DBService $DBService)
    {
        $fileKey = $request->input('fileKey');

        try {
            $result = $this->s3->listParts([
                'Bucket' => env('AWS_BUCKET'),
                'Key' =>$fileKey,
                'UploadId' => $id,
            ]);

            $parts = $result['Parts'];

            $this->s3->completeMultipartUpload([
                'Bucket' => env('AWS_BUCKET'),
                'Key' =>$fileKey,
                'UploadId' => $id,
                'MultipartUpload' => ['Parts' => $parts],
            ]);

            if ($result['@metadata']['statusCode'] === 200) {
                return response()->json(['message' => 'Upload completed']);
            } else {
                return response()->json(['error' => 'Upload completion failed'], 400);
            }
        } catch (AwsException $e) {
            return response()->json(['error' => $e->getAwsErrorMessage()], 400);

        }

    }

    public function abortMultipartUpload(Request $request,$id)
    {
        $fileKey = $request->query('fileKey');

        try {
            $this->s3->abortMultipartUpload([
                'Bucket'   => env('AWS_BUCKET'),
                'Key' =>$fileKey,
                'UploadId' => $id,
            ]);
            return response()->json(['message' => 'Upload Aborted']);
        } catch (AwsException $e) {
            return response()->json(['error' => $e->getAwsErrorMessage()], 400);
        }
    }


}
