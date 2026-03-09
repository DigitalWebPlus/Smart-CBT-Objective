<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            [
                'name' => 'Mathematics',
                'code' => 'MATH-101',
                'is_active' => true,
                'description' => 'Foundation level algebra and calculus.',
            ],
            [
                'name' => 'Chemistry',
                'code' => 'CHEM-110',
                'is_active' => true,
                'description' => 'Introductory chemistry and lab safety.',
            ],
            [
                'name' => 'Physics',
                'code' => 'PHYS-201',
                'is_active' => true,
                'description' => 'Mechanics, waves, and thermodynamics.',
            ],
            [
                'name' => 'Computer Science',
                'code' => 'COMP-301',
                'is_active' => true,
                'description' => 'Data structures and algorithms.',
            ],
        ];

        foreach ($subjects as $subject) {
            Subject::query()->updateOrCreate(
                ['code' => $subject['code']],
                $subject
            );
        }
    }
}
