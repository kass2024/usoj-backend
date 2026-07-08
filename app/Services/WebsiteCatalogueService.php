<?php

namespace App\Services;

use App\Models\ClassYear;
use App\Models\DegreeLevel;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WebsiteCatalogueService
{
    public function getByCategory(string $category): Collection
    {
        $rows = collect();

        $departments = Department::query()
            ->active()
            ->with(['school.program'])
            ->whereHas('school', function ($query) {
                $query->where('status', 'active')
                    ->whereHas('program', fn ($program) => $program->where('status', 'active'));
            })
            ->orderBy('name')
            ->get();

        foreach ($departments as $department) {
            $program = $department->school?->program;
            if (!$program) {
                continue;
            }

            $degreeLevels = $this->degreeLevelsForDepartment($department, $program->id);

            foreach ($degreeLevels as $level) {
                $resolvedCategory = $this->resolveCategory($department, $level);
                if ($resolvedCategory !== $category) {
                    continue;
                }

                $rows->push([
                    'id' => $department->id . '-' . $level->id,
                    'name' => $department->name,
                    'display_name' => $this->displayName($department),
                    'abbr' => $department->abbr,
                    'program' => $program->name,
                    'school' => $department->school->name,
                    'degree_level' => $level->name,
                    'duration' => $department->duration ?: $this->guessDuration($department, $level),
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

    private function degreeLevelsForDepartment(Department $department, int $programId): Collection
    {
        if (Schema::hasTable('department_degree_level')) {
            $linked = $department->degreeLevels()->active()->get();
            if ($linked->isNotEmpty()) {
                return $linked;
            }
        }

        return DegreeLevel::query()
            ->active()
            ->where('program_id', $programId)
            ->orderBy('name')
            ->get();
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

    private function guessDuration(Department $department, DegreeLevel $level): string
    {
        $years = ClassYear::query()
            ->where('department_id', $department->id)
            ->where('degree_level_id', $level->id)
            ->distinct('year_name')
            ->count('year_name');

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
