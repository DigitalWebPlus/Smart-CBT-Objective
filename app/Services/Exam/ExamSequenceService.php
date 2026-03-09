<?php

declare(strict_types=1);

namespace App\Services\Exam;

use Illuminate\Support\Facades\DB;
use stdClass;

class ExamSequenceService
{
    public const EXAM_COUNTER = 'exam';
    public const QUESTION_COUNTER = 'exam_question';
    public const ATTEMPT_COUNTER = 'exam_attempt';

    public function next(string $name): int
    {
        return DB::transaction(function () use ($name): int {
            $counter = DB::table('exam_sequence_counters')
                ->lockForUpdate()
                ->where('name', $name)
                ->first();

            if ($counter === null) {
                $counter = new stdClass();
                $counter->next_id = 1;
                DB::table('exam_sequence_counters')->insert([
                    'name' => $name,
                    'next_id' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return 1;
            }

            $current = (int) $counter->next_id;

            DB::table('exam_sequence_counters')
                ->where('name', $name)
                ->update([
                    'next_id' => $current + 1,
                    'updated_at' => now(),
                ]);

            return $current;
        });
    }
}
