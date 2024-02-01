<?php

namespace Database\Factories;

use App\Models\Datafile;
use App\Models\Datapool;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Datapool>
 */
class DatafileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->unixTime . "_" . fake()->asciify('**************') . ".csv";
        return [
            'key' => 'uploads/' . $filename,
            'creation_time' => fake()->dateTime,
            'filename' => $filename,
        ];
    }

    protected $model = Datafile::class;

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
