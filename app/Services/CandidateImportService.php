<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;
use Spatie\SimpleExcel\SimpleExcelReader;

class CandidateImportService
{
    public function importFromUploadedFile(UploadedFile $file, bool $overwrite = false): array
    {
        $records = $this->parseUploadedRecords($file);

        return $this->importRecords($records, $overwrite);
    }

    protected function importRecords(array $records, bool $overwrite): array
    {
        $report = [
            'total' => count($records),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($records, $overwrite, &$report): void {
            foreach ($records as $index => $record) {
                try {
                    $payload = $this->normalizeCandidateRecord($record);
                } catch (InvalidArgumentException $exception) {
                    $report['errors'][] = sprintf('Row %d: %s', $index + 1, $exception->getMessage());
                    $report['skipped']++;
                    continue;
                }

                $existing = $this->findExistingCandidate($payload);

                if ($existing && ! $overwrite) {
                    $report['skipped']++;
                    continue;
                }

                if (! $existing) {
                    $candidate = new User();
                    $report['created']++;
                } else {
                    $candidate = $existing;
                    $report['updated']++;
                }

                $data = Arr::only($payload, [
                    'name',
                    'email',
                    'registration_number',
                    'phone',
                    'address',
                    'status',
                ]);

                if (! empty($payload['photo'])) {
                    $data['photo'] = $payload['photo'];
                }

                if (! $candidate->exists) {
                    $password = $payload['password'] ?: Str::random(12);
                    $data['password'] = Hash::make($password);
                } elseif (! empty($payload['password'])) {
                    $data['password'] = Hash::make($payload['password']);
                }

                $candidate->fill($data);
                $candidate->save();

                $candidate->departments()->sync($payload['department_ids']);
            }
        });

        return $report;
    }

    protected function normalizeCandidateRecord(array $record): array
    {
        $name = trim((string) Arr::get($record, 'name', ''));
        if ($name === '') {
            throw new InvalidArgumentException('Candidate name is required.');
        }

        $email = strtolower(trim((string) Arr::get($record, 'email', '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid email address is required.');
        }

        $status = strtolower((string) Arr::get($record, 'status', User::STATUS_ACTIVE));
        if (! in_array($status, User::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status provided.');
        }

        $registrationNumber = trim((string) Arr::get($record, 'registration_number', ''));
        $phone = trim((string) Arr::get($record, 'phone', ''));
        $address = trim((string) Arr::get($record, 'address', ''));
        $password = trim((string) Arr::get($record, 'password', ''));
        $photo = trim((string) Arr::get($record, 'photo', ''));

        $departmentIds = $this->resolveDepartmentIds($record);

        return [
            'name' => $name,
            'email' => $email,
            'registration_number' => $registrationNumber !== '' ? $registrationNumber : null,
            'phone' => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
            'status' => $status,
            'password' => $password !== '' ? $password : null,
            'photo' => $photo !== '' ? $photo : null,
            'department_ids' => $departmentIds,
        ];
    }

    protected function findExistingCandidate(array $payload): ?User
    {
        $query = User::query();

        $email = $payload['email'] ?? '';
        $registrationNumber = $payload['registration_number'] ?? '';

        if ($email !== '') {
            $query->where('email', $email);
        }

        if ($registrationNumber !== '') {
            $query->orWhere('registration_number', $registrationNumber);
        }

        return $query->first();
    }

    protected function resolveDepartmentIds(array $record): array
    {
        $departmentIds = $this->normalizeIdList(Arr::get($record, 'department_ids', []));
        if (! empty($departmentIds)) {
            return $departmentIds;
        }

        $tokens = $this->normalizeStringList(
            Arr::get(
                $record,
                'department_codes',
                Arr::get($record, 'department_names', Arr::get($record, 'departments', ''))
            )
        );

        if (! empty($tokens)) {
            return Department::query()
                ->whereIn('code', $tokens)
                ->orWhereIn('name', $tokens)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return $this->defaultDepartmentIds();
    }

    protected function defaultDepartmentIds(): array
    {
        $ids = Department::query()->where('is_default', true)->pluck('id')->all();

        if (! empty($ids)) {
            return array_map('intval', $ids);
        }

        $fallback = Department::query()->limit(1)->pluck('id')->all();

        return array_map('intval', $fallback);
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
                'file' => 'The uploaded file must contain a JSON array of candidates.',
            ]);
        }

        return array_values($records);
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

    protected function normalizeStringList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(static function ($item) {
                $item = trim((string) $item);

                return $item !== '' ? $item : null;
            }, $value)));
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

                if (is_array($decoded)) {
                    return $this->normalizeStringList($decoded);
                }
            } catch (JsonException $exception) {
                // Fall through to comma-separated parsing.
            }

            $parts = preg_split('/[;,|]+/', $value) ?: [];

            return array_values(array_filter(array_map(static function ($item) {
                $item = trim((string) $item);

                return $item !== '' ? $item : null;
            }, $parts)));
        }

        return [];
    }

    protected function normalizeIdList(mixed $value): array
    {
        $list = $this->normalizeStringList($value);
        $ids = array_values(array_unique(array_filter(array_map('intval', $list))));

        return $ids;
    }
}
