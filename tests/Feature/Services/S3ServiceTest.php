<?php

namespace Services;

use App\Services\S3Service;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class S3ServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_generate_temp_url_valid(): void
    {
        $fakeFilesystem = Storage::fake('s3');

        $proxyMockedFakeFilesystem = Mockery::mock($fakeFilesystem);
        $proxyMockedFakeFilesystem->shouldReceive('temporaryUrl')
            ->andReturnUsing(function (string $path, \DateTimeInterface $expiration, array $options = []) {
                $result = Storage::disk('s3')->exists($path);
                if ($result) {
                    return 'http://some-signed-url.test/' . $path;
                } else {
                   throw new Exception('File not found');
                }

            });

        Storage::set('s3', $proxyMockedFakeFilesystem);

        $fakeFilesystem->put("test-file.txt", "This is the test file content.");
        Log::info("Exists: ", [$fakeFilesystem->exists("test-file.txt")]);

        $s3Service = new S3Service();
        $result = $s3Service->generateTempUrl("test-file.txt");
        Log::info($result);

        $this->assertTrue(true);
    }
}
