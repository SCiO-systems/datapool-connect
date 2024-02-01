<?php

namespace Services;

use App\Models\Datapool;
use App\Services\DataXService;
use App\Services\DBService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DataXServiceTest extends TestCase
{
    public function test_get_datapool_valid(): void
    {
        Http::fake([
            env("QVANTUM_DOMAIN") . "/api/datapool/*" => Http::response("{}")
        ]);

        $dataXService = new DataXService();
        $response = $dataXService->getDatapool(fake()->uuid());

        $this->assertTrue($response->getStatusCode() == 200);
    }

    public function test_get_datapool_invalid(): void
    {
        Http::fake([
            env("QVANTUM_DOMAIN") . "/api/datapool/*" => Http::response("{\"message\":\"DatapoolNotFound\",\"error_code\":\"404\"}", 400)
        ]);

        $dataXService = new DataXService();
        $response = $dataXService->getDatapool(fake()->uuid());

        $this->assertTrue($response->getStatusCode() == 400);
    }
}
