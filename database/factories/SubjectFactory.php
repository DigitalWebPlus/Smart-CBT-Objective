<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true) . ' Studies',
            'code' => strtoupper(Str::random(6)),
            'level' => fake()->randomElement(['100', '200', '300', '400']),
            'status' => Subject::STATUS_ACTIVE,
            'cover_image_path' => null,
            'description' => fake()->sentence(),
        ];
    }
}
