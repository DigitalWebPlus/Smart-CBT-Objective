<?php

namespace Database\Seeders;

use App\Models\ObjectiveOption;
use App\Models\ObjectiveQuestion;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class ObjectiveQuestionSeeder extends Seeder
{
    private const SUBJECT_QUESTION_DISTRIBUTION = [
        'MATH-101' => 4,
        'CHEM-110' => 4,
        'PHYS-201' => 4,
        'COMP-301' => 4,
    ];

    public function run(): void
    {
        if (Subject::query()->count() === 0) {
            $this->call(SubjectSeeder::class);
        }

        foreach (self::SUBJECT_QUESTION_DISTRIBUTION as $subjectCode => $questionCount) {
            $subject = Subject::query()->firstWhere('code', $subjectCode);

            if (! $subject) {
                continue;
            }

            for ($index = 1; $index <= $questionCount; $index++) {
                $questionType = $this->determineQuestionType($index);
                $questionText = $this->buildQuestionText($subject->name, $questionType, $index);

                $question = ObjectiveQuestion::query()->updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'question_text' => $questionText,
                    ],
                    [
                        'subject_id' => $subject->id,
                        'question_text' => $questionText,
                        'question_type' => $questionType,
                        'marks' => $this->marksForType($questionType),
                        'is_active' => true,
                        'explanation' => 'Seeder generated question for demo attempts.',
                        'metadata' => [
                            'source' => 'ObjectiveQuestionSeeder',
                            'version' => 1,
                        ],
                    ]
                );

                $question->options()->delete();

                foreach ($this->buildOptions($questionType) as $order => $option) {
                    ObjectiveOption::query()->create([
                        'objective_question_id' => $question->id,
                        'label' => $option['label'],
                        'description' => $option['description'] ?? null,
                        'is_correct' => $option['is_correct'],
                        'display_order' => $order + 1,
                    ]);
                }
            }
        }
    }

    private function determineQuestionType(int $index): string
    {
        return match ($index % 3) {
            1 => ObjectiveQuestion::TYPE_MSA,
            2 => ObjectiveQuestion::TYPE_MMA,
            default => ObjectiveQuestion::TYPE_TOF,
        };
    }

    private function marksForType(string $questionType): int
    {
        return match ($questionType) {
            ObjectiveQuestion::TYPE_MSA => 2,
            ObjectiveQuestion::TYPE_MMA => 3,
            ObjectiveQuestion::TYPE_TOF => 1,
            default => 2,
        };
    }

    private function buildQuestionText(string $subjectName, string $questionType, int $index): string
    {
        $typeLabel = match ($questionType) {
            ObjectiveQuestion::TYPE_MSA => 'Single Answer',
            ObjectiveQuestion::TYPE_MMA => 'Multiple Answer',
            ObjectiveQuestion::TYPE_TOF => 'True/False',
        };

        return sprintf(
            '%s %s Question #%d: %s',
            $subjectName,
            $typeLabel,
            $index,
            fake()->sentence(12)
        );
    }

    private function buildOptions(string $questionType): array
    {
        return match ($questionType) {
            ObjectiveQuestion::TYPE_TOF => [
                ['label' => 'True', 'is_correct' => true],
                ['label' => 'False', 'is_correct' => false],
            ],
            ObjectiveQuestion::TYPE_MMA => $this->generateOptionSet(2),
            default => $this->generateOptionSet(1),
        };
    }

    private function generateOptionSet(int $correctCount): array
    {
        $options = [];

        for ($i = 0; $i < 4; $i++) {
            $text = ucfirst(fake()->words(3, true));
            $options[] = [
                'label' => $text,
                'description' => $text,
                'is_correct' => false,
            ];
        }

        $correctIndexes = (array) array_rand($options, $correctCount);

        foreach ($correctIndexes as $index) {
            $options[$index]['is_correct'] = true;
        }

        return $options;
    }
}
