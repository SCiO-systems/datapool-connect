<?php

namespace Database\Factories;

use App\Models\Datapool;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Datapool>
 */
class DatapoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mongo_id' => fake()->uuid(),
            'alias' => 'test-alias',
            'deleted' => 0,
            'name' => 'Test Alias',
            'description' => 'Test Description',
            'records' => fake()->randomNumber(),
            'license' => 'CC0',
            'citation' => 'TEST',
            'status' => 'private'
        ];
    }

    protected $model = Datapool::class;

//    /**
//     * Indicate that the model's email address should be unverified.
//     */
//    public function unverified(): static
//    {
//        return $this->state(fn (array $attributes) => [
//            'email_verified_at' => null,
//        ]);
//    }
}
