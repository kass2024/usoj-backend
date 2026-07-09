<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Department;
use App\Models\DegreeLevel;
use App\Support\CourseDegreeLevelResolver;
use App\Support\Spreadsheet\SpreadsheetReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CourseBulkImportService
{
    protected int $created = 0;

    protected int $updated = 0;

    protected int $skipped = 0;

    /** @var array<int, string> */
    protected array $errors = [];

    public function import(UploadedFile $file, int $departmentId, int $degreeLevelId): self
    {
        $this->resetCounters();

        $department = Department::query()->find($departmentId);
        if (! $department) {
            $this->errors[] = 'Selected department was not found.';

            return $this;
        }

        $defaultLevel = CourseDegreeLevelResolver::resolveForDepartment($department, $degreeLevelId);
        if (! $defaultLevel) {
            $this->errors[] = 'Selected degree level is not valid for this department.';

            return $this;
        }

        try {
            $rows = $this->readRows($file);
        } catch (Throwable $e) {
            $this->errors[] = 'Could not read the uploaded file: '.$e->getMessage();

            return $this;
        }

        if ($rows->isEmpty()) {
            $this->errors[] = 'The file has no course rows. Add data on the "Courses" sheet and try again.';

            return $this;
        }

        $seenCodes = [];

        DB::transaction(function () use ($rows, $department, $defaultLevel, &$seenCodes) {
            foreach ($rows as $index => $row) {
                $line = $index + 2;
                $normalized = $this->normalizeRow($row);

                if ($this->isRowEmpty($normalized)) {
                    continue;
                }

                $degreeLevel = $this->resolveRowDegreeLevel(
                    $department,
                    $normalized,
                    $defaultLevel,
                    $line
                );

                if (! $degreeLevel) {
                    continue;
                }

                $code = strtoupper(trim((string) ($normalized['code'] ?? '')));
                $name = trim((string) ($normalized['name'] ?? ''));
                $credits = $normalized['credits'] ?? null;
                $status = $this->normalizeStatus($normalized['status'] ?? 'active');
                $description = trim((string) ($normalized['description'] ?? ''));

                if ($code === '') {
                    $this->rejectRow($line, 'Course Code is required.');

                    continue;
                }

                $seenKey = $code.'|'.$degreeLevel->id;
                if (isset($seenCodes[$seenKey])) {
                    $this->rejectRow($line, "Duplicate course code \"{$code}\" for {$degreeLevel->name} in this file (also on row {$seenCodes[$seenKey]}).");

                    continue;
                }
                $seenCodes[$seenKey] = $line;

                if (strlen($code) < 2) {
                    $this->rejectRow($line, 'Course Code must be at least 2 characters.');

                    continue;
                }

                if ($name === '') {
                    $this->rejectRow($line, 'Course Name is required.');

                    continue;
                }

                if (strlen($name) < 4) {
                    $this->rejectRow($line, 'Course Name must be at least 4 characters.');

                    continue;
                }

                if (! is_numeric($credits)) {
                    $this->rejectRow($line, 'Credits must be a number.');

                    continue;
                }

                $credits = (int) $credits;
                if ($credits < 1 || $credits > 12) {
                    $this->rejectRow($line, 'Credits must be between 1 and 12.');

                    continue;
                }

                if ($status === null) {
                    $this->rejectRow($line, 'Status must be active or inactive.');

                    continue;
                }

                $existingByCode = Course::query()
                    ->where('code', $code)
                    ->where('department_id', $department->id)
                    ->where('degree_level_id', $degreeLevel->id)
                    ->first();

                $existingByName = Course::query()
                    ->where('name', $name)
                    ->where('department_id', $department->id)
                    ->where('degree_level_id', $degreeLevel->id)
                    ->when($existingByCode, fn ($q) => $q->where('id', '!=', $existingByCode->id))
                    ->first();

                if ($existingByName) {
                    $this->rejectRow($line, "Course name \"{$name}\" is already used for {$degreeLevel->name} in this department.");

                    continue;
                }

                $payload = [
                    'name' => $name,
                    'slug' => $this->uniqueSlug($name, $code, $existingByCode?->id),
                    'description' => $description !== '' ? $description : null,
                    'credits' => $credits,
                    'code' => $code,
                    'status' => $status,
                    'department_id' => $department->id,
                    'degree_level_id' => $degreeLevel->id,
                ];

                if ($existingByCode) {
                    $existingByCode->update($payload);
                    $this->updated++;
                } else {
                    Course::query()->create($payload);
                    $this->created++;
                }
            }
        });

        return $this;
    }

    protected function resolveRowDegreeLevel(
        Department $department,
        array $normalized,
        DegreeLevel $defaultLevel,
        int $line
    ): ?DegreeLevel {
        $rowLevel = trim((string) ($normalized['degree_level'] ?? ''));

        if ($rowLevel === '') {
            return $defaultLevel;
        }

        $resolved = CourseDegreeLevelResolver::resolveForDepartment($department, null, $rowLevel);

        if (! $resolved) {
            $this->rejectRow($line, "Degree level \"{$rowLevel}\" was not found for this department's program.");

            return null;
        }

        return $resolved;
    }

    protected function readRows(UploadedFile $file): Collection
    {
        return SpreadsheetReader::rowsFromUpload($file, 'Courses');
    }

    protected function normalizeRow(array $row): array
    {
        $mapped = [];

        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeHeader((string) $key);
            $mapped[$normalizedKey] = is_string($value) ? trim($value) : $value;
        }

        return [
            'code' => $mapped['course_code'] ?? $mapped['course-code'] ?? $mapped['code'] ?? null,
            'name' => $mapped['course_name'] ?? $mapped['course-name'] ?? $mapped['name'] ?? null,
            'degree_level' => $mapped['degree_level'] ?? $mapped['degree-level'] ?? null,
            'credits' => $mapped['credits'] ?? null,
            'status' => $mapped['status'] ?? null,
            'description' => $mapped['description'] ?? null,
        ];
    }

    protected function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace('*', '', $header);
        $header = preg_replace('/\s+/', '_', $header) ?? $header;

        return trim($header, '_');
    }

    protected function isRowEmpty(array $row): bool
    {
        foreach (['code', 'name', 'degree_level', 'credits', 'status', 'description'] as $field) {
            $value = $row[$field] ?? null;
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function normalizeStatus(mixed $status): ?string
    {
        $status = strtolower(trim((string) $status));

        if ($status === '') {
            return 'active';
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            return $status;
        }

        if (in_array($status, ['yes', 'enabled', '1'], true)) {
            return 'active';
        }

        if (in_array($status, ['no', 'disabled', '0'], true)) {
            return 'inactive';
        }

        return null;
    }

    protected function uniqueSlug(string $name, string $code, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::slug($code);
        $slug = $base;
        $suffix = 1;

        while (
            Course::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.Str::slug($code).($suffix > 1 ? '-'.$suffix : '');
            $suffix++;
        }

        return $slug;
    }

    protected function rejectRow(int $line, string $message): void
    {
        $this->skipped++;
        $this->errors[] = "Row {$line}: {$message}";
    }

    protected function resetCounters(): void
    {
        $this->created = 0;
        $this->updated = 0;
        $this->skipped = 0;
        $this->errors = [];
    }

    public function created(): int
    {
        return $this->created;
    }

    public function updated(): int
    {
        return $this->updated;
    }

    public function skipped(): int
    {
        return $this->skipped;
    }

    /** @return array<int, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function summaryMessage(): string
    {
        $parts = [];

        if ($this->created > 0) {
            $parts[] = "{$this->created} created";
        }

        if ($this->updated > 0) {
            $parts[] = "{$this->updated} updated";
        }

        if ($this->skipped > 0) {
            $parts[] = "{$this->skipped} skipped";
        }

        $summary = $parts !== [] ? implode(', ', $parts) : 'No courses were imported';

        if ($this->errors !== []) {
            $preview = array_slice($this->errors, 0, 8);
            $more = count($this->errors) > 8 ? ' …and '.(count($this->errors) - 8).' more.' : '';
            $summary .= '. Issues: '.implode(' | ', $preview).$more;
        }

        return $summary;
    }
}
