<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GradeBand;
use Illuminate\Database\Seeder;

class GradeBandSeeder extends Seeder
{
    public function run(): void
    {
        $bands = [
            ['letter' => 'A', 'min_percentage' => 70, 'remark' => 'Excellent'],
            ['letter' => 'B', 'min_percentage' => 60, 'remark' => 'Very Good'],
            ['letter' => 'C', 'min_percentage' => 50, 'remark' => 'Good'],
            ['letter' => 'D', 'min_percentage' => 45, 'remark' => 'Fair'],
            ['letter' => 'E', 'min_percentage' => 40, 'remark' => 'Pass'],
            ['letter' => 'F', 'min_percentage' => 0, 'remark' => 'Fail'],
        ];

        foreach ($bands as $band) {
            GradeBand::query()->updateOrCreate(
                ['letter' => $band['letter']],
                $band
            );
        }
    }
}
