<?php

namespace Services;

use App\Services\OAuthService;
use App\Services\UtilitiesService;
use Tests\TestCase;

class OAuthServiceTest extends TestCase
{
    public function test_get_management_token_valid(): void
    {
        $testData = [
            "grant_type" => "client_credentials",
            "client_id" => "0tzltOHt06kAbxKJdFwbl1aaS9xZZKN9",
            "client_secret" => "fjvD10XkkzH8rXb2GDiYs9_mmMYKPpf_nd1Xt7qAuOj_M0MxRG-dK1JQN6VvCYbt",
            "audience" => "https://sciosystems.eu.auth0.com/api/v2/"
        ];
        $oAuth = new OAuthService();
        $result = $oAuth->getManagementAPIToken($testData);

        $this->assertTrue(is_string($result));
    }

    public function test_get_management_token_invalid(): void
    {
        $testData = [
            "grant_type" => "client_credentials",
            "client_id" => "0tzltOHt06kAbxKJdabcl1aaS9xZZKN9",
            "client_secret" => "fjvD10XkkzH8rXb2GDiYs9_mmMYKPpf_nd1Xt7qAuOj_M0MxRG-dK1JQN6VvCYbt",
            "audience" => "https://sciosystems.eu.auth0.com/api/v2/"
        ];
        $oAuth = new OAuthService();
        $result = $oAuth->getManagementAPIToken($testData);

        $this->assertFalse(is_string($result));
    }
}
