<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\GradeBand;
use Illuminate\Support\Collection;

class GradeBandService
{
    private ?Collection $bandsCache = null;

    /**
     * @return array{letter: string|null, remark: string|null}
     */
    public function determine(float $percentage): array
    {
        $bands = $this->bands();

        foreach ($bands as $band) {
            $min = (float) ($band['min'] ?? 0);
            if ($percentage >= $min) {
                return [
                    'letter' => $band['letter'] ?? null,
                    'remark' => $band['remark'] ?? null,
                ];
            }
        }

        return ['letter' => null, 'remark' => null];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{letter: string, min: float, remark: string|null}>
     */
    private function bands(): Collection
    {
        if ($this->bandsCache !== null) {
            return $this->bandsCache;
        }

        $bands = GradeBand::ordered()->get(['letter', 'min_percentage', 'remark'])
            ->map(fn (GradeBand $band) => [
                'letter' => $band->letter,
                'min' => (float) $band->min_percentage,
                'remark' => $band->remark,
            ]);

        if ($bands->isEmpty()) {
            $bands = collect(config('grading.bands', []));
        }

        return $this->bandsCache = $bands;
    }
}
