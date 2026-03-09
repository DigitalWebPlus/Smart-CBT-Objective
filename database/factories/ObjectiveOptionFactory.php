<?php

namespace Database\Factories;

use App\Models\ObjectiveOption;
use App\Models\ObjectiveQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ObjectiveOption>
 */
class ObjectiveOptionFactory extends Factory
{
    protected $model = ObjectiveOption::class;

    public function definition(): array
    {
        return [
            'objective_question_id' => ObjectiveQuestion::factory(),
            'label' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_correct' => false,
            'display_order' => fake()->numberBetween(1, 5),
            'image_path' => null,
        ];
    }
}
