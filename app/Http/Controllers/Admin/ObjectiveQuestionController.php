<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ObjectiveQuestionRequest;
use App\Http\Requests\ObjectiveQuestionImportRequest;
use App\Models\ObjectiveQuestion;
use App\Models\Subject;
use App\Services\NotificationService;
use App\Services\ProfileImageService;
use App\Services\QuestionBankTransferService;
use App\Services\ObjectiveQuestionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ObjectiveQuestionController extends Controller
{
    public function __construct(
        private ObjectiveQuestionService $objectiveQuestionService,
        private QuestionBankTransferService $questionBankTransferService
    ) {
    }

    public function index(Request $request): View
    {
        $typeSummary = ObjectiveQuestion::query()
            ->select('question_type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('question_type')
            ->pluck('total', 'question_type');

        $subjectId = $request->integer('subject_id');

        if ($subjectId) {
            $subject = \App\Models\Subject::query()->findOrFail($subjectId);

            $questions = ObjectiveQuestion::query()
                ->where('subject_id', $subject->id)
                ->with(['subject', 'options'])
                ->withCount('options')
                ->latest()
                ->paginate(15);

            return view('admin.question-banks.objective.index', [
                'mode' => 'subject',
                'subject' => $subject,
                'questions' => $questions,
                'typeSummary' => $typeSummary,
            ]);
        }

        $subjects = \App\Models\Subject::query()
            ->withCount([
                'objectiveQuestions as total_objective_questions',
                'objectiveQuestions as msa_count' => fn ($query) => $query->where('question_type', ObjectiveQuestion::TYPE_MSA),
                'objectiveQuestions as mma_count' => fn ($query) => $query->where('question_type', ObjectiveQuestion::TYPE_MMA),
                'objectiveQuestions as tof_count' => fn ($query) => $query->where('question_type', ObjectiveQuestion::TYPE_TOF),
                'objectiveQuestions as image_count' => fn ($query) => $query->whereNotNull('image_path'),
            ])
            ->orderBy('name')
            ->paginate(12);

        return view('admin.question-banks.objective.index', [
            'mode' => 'summary',
            'subjects' => $subjects,
            'typeSummary' => $typeSummary,
        ]);
    }

    public function create(Request $request): View
    {
        $selectedSubjectId = $request->integer('subject_id');
        $cloneId = $request->integer('clone_from');

        $question = new ObjectiveQuestion();

        if ($cloneId) {
            $source = ObjectiveQuestion::query()
                ->with('options')
                ->findOrFail($cloneId);

            $question = $source->replicate();
            $question->setRelation('options', $source->options);
            $selectedSubjectId = $selectedSubjectId ?: $source->subject_id;
        }

        return view('admin.question-banks.objective.create', [
            'question' => $question,
            'subjects' => \App\Models\Subject::query()->orderBy('name')->get(),
            'selectedSubjectId' => $selectedSubjectId,
        ]);
    }

    public function upload(Request $request): View
    {
        $selectedSubjectId = $request->integer('subject_id');

        return view('admin.question-banks.objective.upload', [
            'subjects' => Subject::query()->orderBy('name')->get(),
            'selectedSubjectId' => $selectedSubjectId,
        ]);
    }

    public function store(ObjectiveQuestionRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $data = $request->validated();
                $options = $data['options'];
                unset($data['options']);

                $data['image_path'] = ProfileImageService::upload(
                    $request->file('image'),
                    'questions/objective'
                );

                unset($data['image']);

                /** @var ObjectiveQuestion $question */
                $question = ObjectiveQuestion::query()->create($data);
                $this->objectiveQuestionService->syncOptions($question, $options);
            });

            NotificationService::CREATED('Question created successfully.');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();
        }

        return redirect()->route('admin.question-banks.create', [
            'subject_id' => $request->integer('subject_id') ?: null,
        ]);
    }

    public function edit(ObjectiveQuestion $objective): View
    {
        $objective->load('options');

        return view('admin.question-banks.objective.edit', [
            'question' => $objective,
            'subjects' => \App\Models\Subject::query()->orderBy('name')->get(),
            'selectedSubjectId' => $objective->subject_id,
        ]);
    }

    public function update(ObjectiveQuestionRequest $request, ObjectiveQuestion $objective): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $objective) {
                $data = $request->validated();
                $options = $data['options'];
                unset($data['options']);

                $data['image_path'] = ProfileImageService::upload(
                    $request->file('image'),
                    'questions/objective',
                    $objective->image_path
                );

                unset($data['image']);

                $objective->update($data);

                $this->objectiveQuestionService->syncOptions($objective, $options);
            });

            NotificationService::UPDATED('Question updated successfully.');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();
        }

        return redirect()->route('admin.question-banks.index');
    }

    public function destroy(Request $request, ObjectiveQuestion $objective): RedirectResponse
    {
        try {
            ProfileImageService::delete($objective->image_path);
            $objective->delete();

            NotificationService::DELETED('Question deleted successfully.');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();
        }

        $previousUrl = $request->headers->get('referer');
        if (! empty($previousUrl)) {
            return redirect()->to($previousUrl);
        }

        return redirect()->route('admin.question-banks.index', [
            'subject_id' => $objective->subject_id,
        ]);
    }

    public function import(ObjectiveQuestionImportRequest $request): RedirectResponse
    {
        $subject = \App\Models\Subject::query()->findOrFail($request->integer('subject_id'));

        try {
            $report = $this->questionBankTransferService->importObjectiveFromUploadedFile(
                $request->file('file'),
                $subject,
                $request->boolean('overwrite')
            );

            $created = $report['created'] ?? 0;
            $updated = $report['updated'] ?? 0;
            $skipped = $report['skipped'] ?? 0;
            $errors = $report['errors'] ?? [];

            if ($created > 0 || $updated > 0) {
                NotificationService::SUCCESS(sprintf(
                    'Questions import complete. %d created, %d updated, %d skipped.',
                    $created,
                    $updated,
                    $skipped
                ));
            }

            if (! empty($errors)) {
                $example = Str::limit($errors[0], 180);
                NotificationService::ERROR(sprintf(
                    'Import completed with %d error%s. Example: %s',
                    count($errors),
                    count($errors) === 1 ? '' : 's',
                    $example
                ));
            } elseif ($created === 0 && $updated === 0) {
                NotificationService::SUCCESS('Import finished. No records were created or updated.');
            }

            session()->flash('objective_import_report', $report);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR('Unable to import questions. Please verify the file and try again.');
        }

        return back();
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
        ]);

        $subject = \App\Models\Subject::query()->findOrFail($request->integer('subject_id'));

        $format = strtolower($request->input('format', 'json'));
        $export = $this->questionBankTransferService->exportObjective($subject, $format);
        $filename = sprintf(
            '%s-objective-questions-%s.%s',
            Str::slug($subject->code ?? $subject->name ?? 'subject'),
            now()->format('Ymd_His'),
            $export['extension']
        );

        return response()->streamDownload(
            static function () use ($export): void {
                echo $export['content'];
            },
            $filename,
            ['Content-Type' => $export['mime']]
        );
    }
}
