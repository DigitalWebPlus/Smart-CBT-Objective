<?php

namespace Database\Factories;

use App\Models\ObjectiveQuestion;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ObjectiveQuestion>
 */
class ObjectiveQuestionFactory extends Factory
{
    protected $model = ObjectiveQuestion::class;

    public function definition(): array
    {
        $questionTypes = [
            ObjectiveQuestion::TYPE_MSA,
            ObjectiveQuestion::TYPE_MMA,
            ObjectiveQuestion::TYPE_TOF,
        ];

        return [
            'subject_id' => Subject::factory(),
            'question_text' => fake()->paragraph(),
            'question_type' => fake()->randomElement($questionTypes),
            'image_path' => null,
            'marks' => fake()->randomFloat(2, 1, 5),
            'is_active' => true,
            'explanation' => fake()->sentence(),
            'metadata' => null,
        ];
    }
}
