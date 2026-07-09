<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Department;
use App\Models\DegreeLevel;
use App\Support\CourseCodeScheduler;
use App\Support\CourseDegreeLevelResolver;
use App\Support\ProgramDuration;
use App\Support\Utf8Sanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CourseTextImportService
{
    protected int $created = 0;

    protected int $updated = 0;

    protected int $skipped = 0;

    /** @var array<int, string> */
    protected array $errors = [];

    public function __construct(
        private readonly GeminiAiService $gemini,
    ) {}

    /**
     * @return array<int, array{code: string, name: string, credits: int, status: string, year_index: int, semester: int}>
     */
    public function preview(string $rawText, Department $department, DegreeLevel $degreeLevel): array
    {
        $parsed = $this->parseRawText($rawText);

        if ($parsed === []) {
            return [];
        }

        $scheduled = $this->scheduleCourses($parsed, $department, $degreeLevel);

        return Utf8Sanitizer::cleanArray($scheduled);
    }

    public function import(string $rawText, int $departmentId, int $degreeLevelId): self
    {
        $this->resetCounters();

        $department = Department::query()->find($departmentId);
        if (! $department) {
            $this->errors[] = 'Selected department was not found.';

            return $this;
        }

        $degreeLevel = CourseDegreeLevelResolver::resolveForDepartment($department, $degreeLevelId);
        if (! $degreeLevel) {
            $this->errors[] = 'Selected degree level is not valid for this department.';

            return $this;
        }

        $scheduled = $this->preview($rawText, $department, $degreeLevel);

        if ($scheduled === []) {
            $this->errors[] = 'No courses found in the pasted text. Use one course per line.';

            return $this;
        }

        DB::transaction(function () use ($scheduled, $department, $degreeLevel) {
            foreach ($scheduled as $index => $row) {
                $line = $index + 1;
                $code = strtoupper(trim($row['code']));
                $name = trim($row['name']);

                if ($code === '' || $name === '') {
                    $this->rejectRow($line, 'Course code and name are required.');

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
                    $this->rejectRow($line, "Course name \"{$name}\" already exists for this level.");

                    continue;
                }

                $payload = [
                    'name' => $name,
                    'slug' => $this->uniqueSlug($name, $code, $existingByCode?->id),
                    'description' => null,
                    'credits' => (int) $row['credits'],
                    'code' => $code,
                    'status' => $row['status'],
                    'department_id' => $department->id,
                    'degree_level_id' => $degreeLevel->id,
                    'year_index' => ProgramDuration::normalizeYearSemester(
                        (int) $row['year_index'],
                        (int) $row['semester'],
                        ProgramDuration::yearsForDegreeLevel($degreeLevel)
                    )['year_index'],
                    'semester' => ProgramDuration::normalizeYearSemester(
                        (int) $row['year_index'],
                        (int) $row['semester'],
                        ProgramDuration::yearsForDegreeLevel($degreeLevel)
                    )['semester'],
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

    /**
     * @return array<int, array{code?: string|null, name: string, credits?: int, status?: string}>
     */
    public function parseRawText(string $rawText): array
    {
        $rawText = Utf8Sanitizer::clean($rawText);
        $courses = [];

        foreach (preg_split('/\R+/', $rawText) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $line = preg_replace('/^\d+[\).\]]\s*/', '', $line) ?? $line;

            if (preg_match('/^([A-Za-z0-9][A-Za-z0-9._-]*)\s*[-–—:]\s*(.+)$/u', $line, $match)) {
                $courses[] = [
                    'code' => strtoupper(trim($match[1])),
                    'name' => Utf8Sanitizer::clean(trim($match[2])),
                ];

                continue;
            }

            if (preg_match('/^([A-Za-z]{2,}\d{3,4})\s+(.+)$/u', $line, $match)) {
                $courses[] = [
                    'code' => strtoupper(trim($match[1])),
                    'name' => Utf8Sanitizer::clean(trim($match[2])),
                ];

                continue;
            }

            $courses[] = ['code' => null, 'name' => Utf8Sanitizer::clean($line)];
        }

        return $courses;
    }

    /**
     * @param  array<int, array{code?: string|null, name: string}>  $parsed
     * @return array<int, array{code: string, name: string, credits: int, status: string, year_index: int, semester: int}>
     */
    protected function scheduleCourses(array $parsed, Department $department, DegreeLevel $degreeLevel): array
    {
        $programYears = ProgramDuration::yearsForDegreeLevel($degreeLevel);
        $prefix = CourseCodeScheduler::departmentCodePrefix($department->name);

        if ($this->gemini->isConfigured() && ! $this->gemini->usesFallbackOnly()) {
            try {
                $aiScheduled = $this->scheduleWithGemini($parsed, $department, $degreeLevel, $programYears, $prefix);
                if ($aiScheduled !== []) {
                    return $aiScheduled;
                }
            } catch (Throwable) {
                // Fall through to local scheduler.
            }
        }

        return CourseCodeScheduler::schedule($parsed, $prefix, $programYears);
    }

    /**
     * @param  array<int, array{code?: string|null, name: string}>  $parsed
     * @return array<int, array{code: string, name: string, credits: int, status: string, year_index: int, semester: int}>
     */
    protected function scheduleWithGemini(
        array $parsed,
        Department $department,
        DegreeLevel $degreeLevel,
        int $programYears,
        string $codePrefix
    ): array {
        $courseLines = collect($parsed)->map(function ($course, $i) {
            $code = $course['code'] ?? '';
            $name = $course['name'] ?? '';

            return ($i + 1).'. '.($code !== '' ? "{$code} — {$name}" : $name);
        })->implode("\n");

        $semesterSlots = ProgramDuration::semesterSlots($programYears);

        $prompt = <<<PROMPT
Department: {$department->name}
Degree level: {$degreeLevel->name}
Program structure: {$programYears} years, each with 2 semesters ({$semesterSlots} semesters total).
Code prefix for auto-generated codes: {$codePrefix}

Parse these courses and return JSON:
{
  "courses": [
    {
      "code": "ICT1101",
      "name": "Introduction to ICT",
      "credits": 3,
      "year_index": 1,
      "semester": 1,
      "status": "active"
    }
  ]
}

Rules:
- Bachelor programs use years 1-4 with semesters 1 and 2 each year (8 semesters).
- Master programs use years 1-2 with semesters 1 and 2 each year (4 semesters).
- This program has {$programYears} year(s) and {$semesterSlots} semester slot(s).
- Keep existing codes when provided.
- Generate missing codes as {$codePrefix}YYSNN (Y=year 1-{$programYears}, S=semester 1-2, NN=01-99).
- Place courses in logical year/semester order (foundations in year 1, advanced in later years).
- If codes like ICT1101 exist, year=first digit after letters, semester=second digit (11=Y1S1, 12=Y1S2, 21=Y2S1).
- Credits: 6 for projects/internships, 4 for labs/practicals, 2 for skills/seminar courses, 3 for standard theory courses.
- All courses status "active".
- Return ALL courses from the list below.

Courses:
{$courseLines}
PROMPT;

        $payload = $this->gemini->generateJson(
            $prompt,
            'You are a university curriculum planner. Return compact valid JSON only.',
            (int) config('gemini.material_max_tokens', 8192)
        );

        $rows = $payload['courses'] ?? [];
        if (! is_array($rows) || $rows === []) {
            return [];
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = Utf8Sanitizer::clean(trim((string) ($row['name'] ?? '')));
            if ($name === '') {
                continue;
            }

            $normalized[] = [
                'code' => isset($row['code']) ? strtoupper(trim((string) $row['code'])) : null,
                'name' => $name,
                'credits' => (int) ($row['credits'] ?? 3),
                'status' => (string) ($row['status'] ?? 'active'),
                'year_index' => isset($row['year_index']) ? (int) $row['year_index'] : null,
                'semester' => isset($row['semester']) ? (int) $row['semester'] : null,
            ];
        }

        return CourseCodeScheduler::schedule($normalized, $codePrefix, $programYears);
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
        $this->errors[] = "Course {$line}: {$message}";
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
            $preview = array_slice($this->errors, 0, 6);
            $more = count($this->errors) > 6 ? ' …and '.(count($this->errors) - 6).' more.' : '';
            $summary .= '. Issues: '.implode(' | ', $preview).$more;
        }

        return $summary;
    }
}
