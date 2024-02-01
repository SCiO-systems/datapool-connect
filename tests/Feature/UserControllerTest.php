<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic feature test example.
     */
    public function test_add_user_valid_body(): void
    {
        $postData = [
            'name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
        ];

        $response = $this->postJson('api/user/auth0|123456789123456789123456/add', $postData, []);

        $response->assertStatus(200);
    }

    public function test_add_user_invalid_body(): void
    {
        $postData = [
            'name' => fake()->firstName(),
        ];

        $response = $this->postJson('api/user/auth0|123456789123456789123456/add', $postData, []);

        $response->assertStatus(400);
    }
}
