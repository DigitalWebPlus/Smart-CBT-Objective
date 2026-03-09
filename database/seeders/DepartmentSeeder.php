<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'General',
                'code' => 'GEN',
                'description' => 'Default department available to every candidate.',
                'is_default' => true,
            ],
            [
                'name' => 'Science',
                'code' => 'SCI',
                'description' => 'Covers Physics, Chemistry, Biology and related courses.',
            ],
            [
                'name' => 'Engineering',
                'code' => 'ENG',
                'description' => 'Mechanical, Electrical, Civil and allied engineering programs.',
            ],
            [
                'name' => 'Arts & Humanities',
                'code' => 'ART',
                'description' => 'Languages, arts and social sciences.',
            ],
        ];

        foreach ($departments as $data) {
            Department::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'code' => $data['code'] ?? null,
                    'description' => $data['description'] ?? null,
                    'is_default' => (bool) ($data['is_default'] ?? false),
                ]
            );
        }

        if (! Department::query()->where('is_default', true)->exists()) {
            Department::query()->first()?->update(['is_default' => true]);
        }
    }
}
