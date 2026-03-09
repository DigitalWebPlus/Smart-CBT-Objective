<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\Concerns\HandlesProfilePhotos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CandidateSeeder extends Seeder
{
    use HandlesProfilePhotos;

    public function run(): void
    {
        $candidates = [
            [
                'name' => 'Ada Lovelace',
                'email' => 'candidate1@example.com',
                'registration_number' => 'CBT-2025-001',
                'phone' => '+1-555-0100',
                'address' => '12 Logic Lane, London',
                'status' => User::STATUS_ACTIVE,
            ],
            [
                'name' => 'Alan Turing',
                'email' => 'candidate2@example.com',
                'registration_number' => 'CBT-2025-002',
                'phone' => '+1-555-0101',
                'address' => '23 Enigma Ave, Manchester',
                'status' => User::STATUS_SUSPENDED,
            ],
            [
                'name' => 'Grace Hopper',
                'email' => 'candidate3@example.com',
                'registration_number' => 'CBT-2025-003',
                'phone' => '+1-555-0102',
                'address' => '41 Compiler Street, New York',
                'status' => User::STATUS_INACTIVE,
            ],
            [
                'name' => 'Margaret Hamilton',
                'email' => 'candidate4@example.com',
                'registration_number' => 'CBT-2025-004',
                'phone' => '+1-555-0103',
                'address' => '8 Apollo Way, Boston',
                'status' => User::STATUS_ACTIVE,
            ],
            [
                'name' => 'Tim Berners-Lee',
                'email' => 'candidate5@example.com',
                'registration_number' => 'CBT-2025-005',
                'phone' => '+1-555-0104',
                'address' => '35 Web Court, Geneva',
                'status' => User::STATUS_BANNED,
            ],
        ];

        $departments = Department::query()->orderBy('name')->get();
        $defaultDepartmentIds = $departments->where('is_default', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (empty($defaultDepartmentIds) && $departments->isNotEmpty()) {
            $defaultDepartmentIds = [(int) $departments->first()->id];
        }

        foreach ($candidates as $index => $data) {
            $candidate = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'registration_number' => $data['registration_number'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'status' => $data['status'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if ($candidate->wasRecentlyCreated || blank($candidate->photo)) {
                $candidate->update([
                    'photo' => 'uploads/candidates/student.jpg',
                ]);
            }

            $assignedDepartments = $defaultDepartmentIds;
            if ($departments->isNotEmpty()) {
                $extraDepartment = $departments[$index % max($departments->count(), 1)] ?? null;
                if ($extraDepartment) {
                    $assignedDepartments[] = (int) $extraDepartment->id;
                }
            }

            $candidate->departments()->syncWithoutDetaching(array_unique($assignedDepartments));
        }
    }
}
