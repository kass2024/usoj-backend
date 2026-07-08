<?php

namespace App\Services;

use App\Models\ClassYear;
use App\Models\DegreeLevel;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WebsiteCatalogueService
{
    private const CACHE_MINUTES = 15;

    private ?bool $pivotTableExists = null;

    public function getByCategory(string $category): Collection
    {
        return Cache::remember(
            "website_programmes.{$category}",
            now()->addMinutes(self::CACHE_MINUTES),
            fn () => $this->buildByCategory($category)
        );
    }

    public function clearCache(): void
    {
        foreach (['undergraduate', 'diploma', 'short_course'] as $category) {
            Cache::forget("website_programmes.{$category}");
        }
    }

    private function buildByCategory(string $category): Collection
    {
        $departments = Department::query()
            ->active()
            ->select(['id', 'name', 'abbr', 'slug', 'description', 'school_id', 'duration', 'mode', 'website_category'])
            ->with([
                'school:id,name,program_id,status',
                'school.program:id,name,status',
                'degreeLevels' => fn ($query) => $query->active()->select('degree_levels.id', 'degree_levels.name', 'degree_levels.slug', 'degree_levels.program_id', 'degree_levels.status'),
            ])
            ->whereHas('school', function ($query) {
                $query->where('status', 'active')
                    ->whereHas('program', fn ($program) => $program->where('status', 'active'));
            })
            ->orderBy('name')
            ->get();

        if ($departments->isEmpty()) {
            return collect();
        }

        $programIds = $departments
            ->map(fn (Department $department) => $department->school?->program?->id)
            ->filter()
            ->unique()
            ->values();

        $levelsByProgram = DegreeLevel::query()
            ->active()
            ->select(['id', 'name', 'slug', 'program_id', 'status'])
            ->whereIn('program_id', $programIds)
            ->orderBy('name')
            ->get()
            ->groupBy('program_id');

        $yearCounts = $this->yearCountsForDepartments($departments->pluck('id'));

        $rows = collect();

        foreach ($departments as $department) {
            $program = $department->school?->program;
            if (!$program) {
                continue;
            }

            $degreeLevels = $this->degreeLevelsForDepartment(
                $department,
                (int) $program->id,
                $levelsByProgram
            );

            foreach ($degreeLevels as $level) {
                $resolvedCategory = $this->resolveCategory($department, $level);
                if ($resolvedCategory !== $category) {
                    continue;
                }

                $yearCount = $yearCounts->get($department->id . '-' . $level->id, 0);

                $rows->push([
                    'id' => $department->id . '-' . $level->id,
                    'name' => $department->name,
                    'display_name' => $this->displayName($department),
                    'abbr' => $department->abbr,
                    'program' => $program->name,
                    'school' => $department->school->name,
                    'degree_level' => $level->name,
                    'duration' => $department->duration ?: $this->guessDuration($yearCount, $level),
                    'mode' => $department->mode ?: $this->guessMode($level),
                    'category' => $resolvedCategory,
                    'slug' => $department->slug,
                    'description' => $department->description,
                ]);
            }
        }

        return $rows->sortBy([
            ['program', 'asc'],
            ['school', 'asc'],
            ['name', 'asc'],
        ])->values();
    }

    private function yearCountsForDepartments(Collection $departmentIds): Collection
    {
        if ($departmentIds->isEmpty()) {
            return collect();
        }

        return ClassYear::query()
            ->whereIn('department_id', $departmentIds)
            ->select('department_id', 'degree_level_id')
            ->selectRaw('COUNT(DISTINCT year_name) as year_count')
            ->groupBy('department_id', 'degree_level_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->department_id . '-' . $row->degree_level_id => (int) $row->year_count,
            ]);
    }

    private function degreeLevelsForDepartment(
        Department $department,
        int $programId,
        Collection $levelsByProgram
    ): Collection {
        if ($this->pivotTableExists() && $department->relationLoaded('degreeLevels') && $department->degreeLevels->isNotEmpty()) {
            return $department->degreeLevels;
        }

        return $levelsByProgram->get($programId, collect());
    }

    private function pivotTableExists(): bool
    {
        if ($this->pivotTableExists === null) {
            $this->pivotTableExists = Schema::hasTable('department_degree_level');
        }

        return $this->pivotTableExists;
    }

    private function resolveCategory(Department $department, DegreeLevel $level): ?string
    {
        if ($department->website_category) {
            return $department->website_category;
        }

        $name = Str::lower($level->name . ' ' . ($level->slug ?? ''));

        if (Str::contains($name, ['diploma'])) {
            return 'diploma';
        }

        if (Str::contains($name, ['short', 'certificate', 'certificate course'])) {
            return 'short_course';
        }

        if (Str::contains($name, ['bachelor', 'licence', 'license', 'undergrad', 'ba ', 'bsc', 'bed'])) {
            return 'undergraduate';
        }

        if (Str::contains($name, ['master', 'msc', 'ma ', 'mphil', 'doctor', 'phd', 'doctorate'])) {
            return null;
        }

        return 'undergraduate';
    }

    private function guessDuration(int $years, DegreeLevel $level): string
    {
        if ($years > 0) {
            return $years . ' ' . Str::plural('Year', $years);
        }

        $name = Str::lower($level->name);

        if (Str::contains($name, ['diploma', 'short', 'certificate'])) {
            return '2 Years';
        }

        if (Str::contains($name, ['master', 'msc', 'ma '])) {
            return '2 Years';
        }

        if (Str::contains($name, ['doctor', 'phd'])) {
            return '3–5 Years';
        }

        return '3 Years';
    }

    private function guessMode(DegreeLevel $level): string
    {
        $name = Str::lower($level->name);

        if (Str::contains($name, ['in-service', 'recess', 'distance', 'weekend'])) {
            return 'In-service / Recess';
        }

        if (Str::contains($name, ['short', 'certificate'])) {
            return 'Flexible';
        }

        return 'Fulltime & Weekend';
    }

    private function displayName(Department $department): string
    {
        $name = trim($department->name);
        $abbr = strtoupper(trim((string) $department->abbr));

        if ($abbr !== '') {
            return $name . ' (' . $abbr . ')';
        }

        return $name;
    }
}
