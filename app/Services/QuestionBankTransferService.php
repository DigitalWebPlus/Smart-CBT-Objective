<?php

namespace App\Services;

use App\Models\ObjectiveQuestion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

class QuestionBankTransferService
{
    public function __construct(private ObjectiveQuestionService $objectiveQuestionService)
    {
    }

    public function importObjectiveFromUploadedFile(UploadedFile $file, $subject, bool $overwrite = false): array
    {
        $records = $this->parseUploadedRecords($file);

        return $this->importObjectiveRecords($records, $subject, $overwrite);
    }

    public function exportObjective($subject, string $format = 'json'): array
    {
        $questions = $subject->objectiveQuestions()->with('options')->orderBy('id')->get();

        $payload = $questions->map(function (ObjectiveQuestion $question) {
            return [
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'marks' => (float) $question->marks,
                'is_active' => (bool) $question->is_active,
                'explanation' => $question->explanation,
                'metadata' => is_array($question->metadata) ? $question->metadata : [],
                'options' => $question->options->map(function ($option) {
                    return [
                        'label' => $option->label,
                        'description' => $option->description,
                        'is_correct' => (bool) $option->is_correct,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return $this->formatExportPayload(
            $payload,
            $format,
            $this->objectiveExportColumns(),
            ['options', 'metadata']
        );
    }


    protected function decodeJsonArray(UploadedFile $file): array
    {
        try {
            $records = json_decode($file->get(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw ValidationException::withMessages([
                'file' => 'Invalid JSON file: ' . $exception->getMessage(),
            ]);
        }

        if (! is_array($records)) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file must contain a JSON array of questions.',
            ]);
        }

        return array_values($records);
    }

    protected function encodeJson(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function importObjectiveRecords(array $records, $subject, bool $overwrite): array
    {
        $report = [
            'total' => count($records),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($records, $subject, $overwrite, &$report) {
            foreach ($records as $index => $record) {
                try {
                    $payload = $this->normalizeObjectiveRecord($record);
                } catch (InvalidArgumentException $exception) {
                    $report['errors'][] = sprintf('Row %d: %s', $index + 1, $exception->getMessage());
                    $report['skipped']++;
                    continue;
                }

                $question = $subject->objectiveQuestions()->where('question_text', $payload['question_text'])->first();

                if ($question && ! $overwrite) {
                    $report['skipped']++;
                    continue;
                }

                if (! $question) {
                    $question = new ObjectiveQuestion();
                    $question->subject_id = $subject->id;
                    $report['created']++;
                } else {
                    $report['updated']++;
                }

                $question->fill(Arr::only($payload, [
                    'question_text',
                    'question_type',
                    'marks',
                    'is_active',
                    'explanation',
                    'metadata',
                ]));

                $question->save();

                $this->objectiveQuestionService->syncOptions($question, $payload['options']);
            }
        });

        return $report;
    }

    protected function normalizeObjectiveRecord(array $record): array
    {
        $questionText = trim((string) Arr::get($record, 'question_text', ''));
        if ($questionText === '') {
            throw new InvalidArgumentException('Question text is required.');
        }

        $questionType = strtolower((string) Arr::get($record, 'question_type', ObjectiveQuestion::TYPE_MSA));
        $allowedTypes = [
            ObjectiveQuestion::TYPE_MSA,
            ObjectiveQuestion::TYPE_MMA,
            ObjectiveQuestion::TYPE_TOF,
        ];
        if (! in_array($questionType, $allowedTypes, true)) {
            throw new InvalidArgumentException('Invalid question type provided.');
        }

        $marks = (float) Arr::get($record, 'marks', 1);
        if ($marks <= 0) {
            throw new InvalidArgumentException('Marks must be greater than zero.');
        }

        $options = Arr::get($record, 'options', []);
        if (! is_array($options)) {
            $options = $this->decodeArrayColumn($options, 'options');
        }

        if (count($options) < 2) {
            throw new InvalidArgumentException('Provide at least two options for each objective question.');
        }

        $normalizedOptions = [];
        $correctCount = 0;
        foreach ($options as $index => $option) {
            $label = trim((string) Arr::get($option, 'label', chr(65 + $index)));
            $description = Arr::get($option, 'description');
            $isCorrect = filter_var(Arr::get($option, 'is_correct'), FILTER_VALIDATE_BOOL);

            if ($isCorrect) {
                $correctCount++;
            }

            $normalizedOptions[] = [
                'label' => $label,
                'description' => $description,
                'is_correct' => $isCorrect,
                'image_path' => Arr::get($option, 'image_path'),
            ];
        }

        if ($correctCount === 0) {
            throw new InvalidArgumentException('Mark at least one option as the correct answer.');
        }

        if ($questionType === ObjectiveQuestion::TYPE_MSA && $correctCount !== 1) {
            throw new InvalidArgumentException('Single-answer questions must have exactly one correct option.');
        }

        if ($questionType === ObjectiveQuestion::TYPE_MMA && $correctCount < 2) {
            throw new InvalidArgumentException('Multiple-answer questions must have at least two correct options.');
        }

        if ($questionType === ObjectiveQuestion::TYPE_TOF) {
            if (count($normalizedOptions) !== 2) {
                throw new InvalidArgumentException('True/False questions must have exactly two options.');
            }

            if ($correctCount !== 1) {
                throw new InvalidArgumentException('True/False questions must have one correct option.');
            }
        }

        $metadata = Arr::get($record, 'metadata', []);
        if (! is_array($metadata)) {
            $metadata = $this->decodeArrayColumn($metadata, 'metadata');
        }

        return [
            'question_text' => $questionText,
            'question_type' => $questionType,
            'marks' => $marks,
            'is_active' => filter_var(Arr::get($record, 'is_active', true), FILTER_VALIDATE_BOOL),
            'explanation' => Arr::get($record, 'explanation'),
            'metadata' => $metadata,
            'options' => $normalizedOptions,
        ];
    }


    protected function parseUploadedRecords(UploadedFile $file): array
    {
        $extension = $this->detectExtension($file);

        return match ($extension) {
            'csv', 'xlsx' => $this->parseSpreadsheetRecords($file, $extension),
            default => $this->decodeJsonArray($file),
        };
    }

    protected function detectExtension(UploadedFile $file): string
    {
        $candidates = [
            strtolower((string) $file->getClientOriginalExtension()),
            strtolower((string) $file->extension()),
            strtolower((string) $file->guessExtension()),
        ];

        foreach ($candidates as $candidate) {
            if (! empty($candidate)) {
                return $candidate;
            }
        }

        return 'json';
    }

    protected function parseSpreadsheetRecords(UploadedFile $file, string $extension): array
    {
        $path = $file->getRealPath() ?: $file->getPathname();
        $type = $extension === 'xlsx' ? 'xlsx' : 'csv';

        try {
            $rows = SimpleExcelReader::create($path, $type)
                ->getRows()
                ->map(fn (array $row) => $this->normalizeSpreadsheetRow($row))
                ->filter(fn (array $row) => ! $this->isEmptyRow($row))
                ->values()
                ->all();
        } catch (\Throwable $throwable) {
            throw ValidationException::withMessages([
                'file' => 'Unable to read spreadsheet: ' . $throwable->getMessage(),
            ]);
        }

        return $rows;
    }

    protected function normalizeSpreadsheetRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            if ($key === null) {
                continue;
            }

            $column = Str::snake(trim((string) $key));
            $normalized[$column] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    protected function decodeArrayColumn(mixed $value, string $field): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException(sprintf('Invalid %s JSON: %s', $field, $exception->getMessage()));
            }

            if (! is_array($decoded)) {
                throw new InvalidArgumentException(sprintf('The %s column must contain a JSON array or object.', $field));
            }

            return $decoded;
        }

        return [];
    }

    protected function formatExportPayload(array $records, string $format, array $columns, array $jsonColumns = []): array
    {
        $format = strtolower($format);

        if (in_array($format, ['csv', 'xlsx'], true)) {
            $flatRecords = $this->flattenRecordsForExport($records, $columns, $jsonColumns);

            if ($format === 'csv') {
                return [
                    'content' => $this->generateCsvContent($flatRecords, $columns),
                    'mime' => 'text/csv',
                    'extension' => 'csv',
                ];
            }

            return [
                'content' => $this->generateXlsxContent($flatRecords, $columns),
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'extension' => 'xlsx',
            ];
        }

        return [
            'content' => $this->encodeJson($records),
            'mime' => 'application/json',
            'extension' => 'json',
        ];
    }

    protected function flattenRecordsForExport(array $records, array $columns, array $jsonColumns): array
    {
        return array_map(function (array $record) use ($columns, $jsonColumns) {
            $row = [];

            foreach ($columns as $column) {
                $value = Arr::get($record, $column);

                if (in_array($column, $jsonColumns, true)) {
                    $row[$column] = $this->stringifyJsonValue($value);
                } elseif (is_bool($value)) {
                    $row[$column] = $value ? 'true' : 'false';
                } else {
                    $row[$column] = $value ?? '';
                }
            }

            return $row;
        }, $records);
    }

    protected function stringifyJsonValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    protected function generateCsvContent(array $rows, array $columns): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $columns);

        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $column) {
                $line[] = $row[$column] ?? '';
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv ?: '';
    }

    protected function generateXlsxContent(array $rows, array $columns): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';
        $writer = SimpleExcelWriter::create($tempPath, 'xlsx');
        $writer->addHeader($columns);

        foreach ($rows as $row) {
            $writer->addRow(array_map(fn ($column) => $row[$column] ?? '', $columns));
        }

        $writer->close();

        $contents = file_get_contents($tempPath);
        @unlink($tempPath);

        return $contents ?: '';
    }

    protected function objectiveExportColumns(): array
    {
        return [
            'question_text',
            'question_type',
            'marks',
            'is_active',
            'explanation',
            'metadata',
            'options',
        ];
    }

}
