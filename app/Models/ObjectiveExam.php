<?php

declare(strict_types=1);

namespace App\Models;

class ObjectiveExam extends AbstractExam
{
    protected $table = 'exam_objective_exams';

    protected function subjectsPivotTable(): string
    {
        return 'exam_objective_subject_assignments';
    }
}
