<?php

namespace Services;

use App\Services\UtilitiesService;
use Tests\TestCase;

class UtilitiesServiceTest extends TestCase
{
    public function test_validate_id_valid(): void
    {
        $result = UtilitiesService::validateAuthId('auth0|123456789123456789123456');

        $this->assertTrue($result);
    }

    public function test_validate_id_invalid_no_bar(): void
    {
        $result = UtilitiesService::validateAuthId('auth0123456789123456789123456');

        $this->assertFalse($result);
    }

    public function test_validate_id_invalid_no_auth(): void
    {
        $result = UtilitiesService::validateAuthId('auth|123456789123456789123456');

        $this->assertFalse($result);
    }

    public function test_validate_id_invalid_bad_length(): void
    {
        $result = UtilitiesService::validateAuthId('auth0|1234123456789123456');

        $this->assertFalse($result);
    }

    public function test_validate_orcid_valid(): void
    {
        $result = UtilitiesService::validateAuthId('oauth2|orcid-connection|0009-0009-3688-5537');

        $this->assertTrue($result);
    }
}
