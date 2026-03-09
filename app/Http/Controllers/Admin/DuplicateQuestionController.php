<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ObjectiveQuestion;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DuplicateQuestionController extends Controller
{
    public function objective(): View
    {
        $groups = ObjectiveQuestion::query()
            ->select('subject_id', DB::raw('LOWER(question_text) as normalized_text'), DB::raw('COUNT(*) as total'))
            ->groupBy('subject_id', 'normalized_text')
            ->having('total', '>', 1)
            ->orderBy('subject_id')
            ->get();

        $duplicateGroups = $groups->map(function ($group) {
            $items = ObjectiveQuestion::query()
                ->with('subject')
                ->where('subject_id', $group->subject_id)
                ->whereRaw('LOWER(question_text) = ?', [$group->normalized_text])
                ->orderBy('id')
                ->get();

            return [
                'subject' => $items->first()?->subject,
                'question_text' => $items->first()?->question_text ?? 'Unknown question',
                'total' => $group->total,
                'items' => $items,
            ];
        });

        return view('admin.duplicate-questions.objective', [
            'duplicateGroups' => $duplicateGroups,
        ]);
    }

    public function objectiveDelete(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:bank_questions,id'],
        ]);

        $count = ObjectiveQuestion::query()->whereIn('id', $data['ids'])->delete();

        NotificationService::SUCCESS(sprintf('Deleted %d objective duplicate question%s.', $count, $count === 1 ? '' : 's'));

        return back();
    }

}
