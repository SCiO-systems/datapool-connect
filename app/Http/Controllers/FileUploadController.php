<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class FileUploadController extends Controller
{
    protected $s3;

        public function __construct()
    {
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    public function generatePresignedUrls(Request $request)
    {
        $fileKey = $request->input('fileKey'); // The S3 object key (file path)
        $numParts = $request->input('numParts');

        // Initialize an array to store the pre-signed URLs for each part.
        $preSignedUrls = [];

        // Check if the object exists
        if ($this ->s3->doesObjectExist((env('AWS_BUCKET')), 'uploads/'.'vasilis/'.$fileKey))
            return response()->json(['message' => 'File Already Exists'], 400);
            else
            {
                try {
                    // Step 1: Initiate Multipart Upload
                    $cmd = $this->s3->getCommand('CreateMultipartUpload', [
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => 'uploads/'.'vasilis/'.$fileKey,
                    ]);

                    $response = $this->s3->execute($cmd);
                    $uploadId = $response['UploadId'];

                    // Step 2: Generate Pre-Signed URLs for Parts
                    for ($partNumber = 1; $partNumber <= $numParts; $partNumber++) {
                        $cmd = $this->s3->getCommand('UploadPart', [
                            'Bucket'     => env('AWS_BUCKET'),
                            'Key'        => 'uploads/'.'vasilis/'.$fileKey,
                            'PartNumber' => $partNumber,
                            'UploadId'   => $uploadId,
                        ]);

                        $request = $this->s3->createPresignedRequest($cmd, '+1 hour');
                        $preSignedUrls[] = (string) $request->getUri();
                    }

                    // Return the pre-signed URLs for all parts.
                    return response()->json(['preSignedUrls' => $preSignedUrls, 'uploadId' => $uploadId]);
                } catch (AwsException $e) {
                    return response()->json(['error' => $e->getAwsErrorMessage()], 400);
                }
            }
    }


    public function completeMultipartUpload(Request $request)
    {
        $fileKey = $request->input('fileKey'); // The S3 object key (file path)
        $uploadId = $request->input('uploadID');


        try {
            $result = $this->s3->listParts([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => 'uploads/' .'vasilis/'. $fileKey,
                'UploadId' => $uploadId,
            ]);
            $parts = $result['Parts'];

            $this->s3->completeMultipartUpload([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => 'uploads/' . 'vasilis/'.$fileKey,
                'UploadId' => $uploadId,
                'MultipartUpload' => ['Parts' => $parts],
            ]);


            return response()->json(['message' => 'Upload completed']);
        } catch (AwsException $e) {
            return response()->json(['error' => $e->getAwsErrorMessage()], 400);
        }
    }

    public function abortMultipartUpload(Request $request)
    {
        $fileKey = $request->input('fileKey'); // The S3 object key (file path)
        $uploadId = $request->input('uploadID');

        try {
            $this->s3->abortMultipartUpload([
                'Bucket'   => env('AWS_BUCKET'),
                'Key' => 'uploads/' .'vasilis/'. $fileKey,
                'UploadId' => $uploadId,
            ]);
            return response()->json(['message' => 'Upload Aborted']);
        } catch (AwsException $e) {
            return response()->json(['error' => $e->getAwsErrorMessage()], 400);
        }
    }



    public function uploadPresignedUrl(request $request)
    {
        $fileKey = $request->input('fileKey');// The s3 destination of the file to be uploaded

        if ($this ->s3->doesObjectExist((env('AWS_BUCKET')), 'uploads/'.'vasilis/'.$fileKey))
            return response()->json(['message' => 'File Already Exists'], 400);
        else{
            try{
                $cmd = $this->s3->getCommand('PutObject', [
                    'Bucket' => env('AWS_BUCKET'),
                    'Key'    => 'uploads/'.'vasilis/'. $fileKey,
                ]);

                $request = $this->s3->createPresignedRequest($cmd, '+1 hour'); // Adjust the expiration time as needed

                $presignedUrl = (string) $request->getUri();

                return response()->json(['presignedUrl' => $presignedUrl]);
            } catch (AwsException $e) {
                return response()->json(['error' => $e->getAwsErrorMessage()], 400);

            }

        }


    }







    public function initiateUpload(Request $request)
    {
        // Handle the initial file upload request and initiate the multi-part upload

        $filename = $request->file('file')->getClientOriginalName();

        try {
            $result = $this->s3->createMultipartUpload([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => 'uploads/' . $filename,
            ]);

            $uploadId = $result['UploadId'];

            return response()->json(['uploadId' => $uploadId]);
        } catch (AwsException $e) {
            return response()->json(['error' => $e->getAwsErrorMessage()], 400);
        }


    }

    public function uploadChunk(Request $request)
    {
        // Handle the chunk uploads and append them to the multi-part upload


        $uploadId = $request->input('uploadId');
        $chunk = $request->file('file');
        if (!$chunk) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }
        $chunk->store('temp'); // Store the file temporarily
        $partNumber = $request->input('partNumber');

        $filePath = storage_path('app/temp/' . $chunk->hashName()); // Get the file path
        $file = fopen($filePath, 'r'); // Open the file

        try {
            $result =$this->s3->uploadPart([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => 'uploads/' . $chunk->getClientOriginalName(),
                'UploadId' => $uploadId,
                'PartNumber' => $partNumber,
               'Body' => $file
            ]);

            fclose($file); // Close the file

            $etag = $result['@metadata'];

            return response()->json(['message' => 'Chunk uploaded', 'ETag' => $etag]);
        } catch (AwsException $e) {
            return response()->json(['error' => $e->getAwsErrorMessage()], 400);
        }
    }

    public function completeUpload(Request $request)
    {
        // Complete the multi-part upload and save the file in S3

        $uploadId = $request->input('uploadId');
        $filename = $request->input('filename');

        try {
            $result = $this->s3->listParts([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => 'uploads/' . $filename,
                'UploadId' => $uploadId,
            ]);

            $parts = $result['Parts'];

//            $this->s3->completeMultipartUpload([
//                'Bucket' => env('AWS_BUCKET'),
//                'Key' => 'uploads/' . $filename,
//                'UploadId' => $uploadId,
//                'MultipartUpload' => ['Parts' => $parts],
//            ]);

            $cmd = $this->s3->getCommand('CompleteMultipartUpload', [
                'Bucket'   => env('AWS_BUCKET'),
                'Key'      => $filename,
                'UploadId' => $uploadId,
                'MultipartUpload' => [
                    'Parts' => $parts,
                ],
            ]);

            $request = $this->s3->createPresignedRequest($cmd, '+1 hour');

            // Return the pre-signed URL to the client
            $preSignedUrl = (string) $request->getUri();


//            return response()->json(['message' => 'Upload completed']);
            return $preSignedUrl;

        } catch (AwsException $e) {
            return response()->json(['error' => $e->getAwsErrorMessage()], 400);
        }
    }


    public function generatePresignedMultipartUploadUrl(Request $request)
    {
        $key = $request->input('key'); // The object key (file path) you want to upload


        try {
            $cmd = $this->s3->getCommand('CreateMultipartUpload', [
                'Bucket' => env('AWS_BUCKET'),
//                'Key' => 'uploads/file.jpg',
                'Key'    => $key,
            ]);

            $request = $this->s3->createPresignedRequest($cmd, '+1 hour'); // Adjust the expiration time as needed

            $presignedUrl = (string)$request->getUri();
            return response()->json(['presignedUrl' => $presignedUrl]);

        } catch (AwsException $e) {
            return response()->json(['error' => $e->getAwsErrorMessage()], 400);

        }
    }


}

