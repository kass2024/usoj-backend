<?php

namespace App\Support;

use App\Models\DegreeLevel;
use App\Models\Department;
use Illuminate\Support\Collection;

class CourseDegreeLevelResolver
{
    /** @return Collection<int, DegreeLevel> */
    public static function levelsForDepartment(Department $department): Collection
    {
        $programId = $department->program_id
            ?? optional(optional($department->school)->program)->id;

        $query = DegreeLevel::query()->active()->orderBy('name');

        if ($programId) {
            $query->where('program_id', $programId);
        }

        return $query->get();
    }

    public static function resolveForDepartment(
        Department $department,
        int|string|null $degreeLevelId = null,
        ?string $degreeLevelName = null
    ): ?DegreeLevel {
        $levels = self::levelsForDepartment($department);

        if ($degreeLevelId) {
            return $levels->firstWhere('id', (int) $degreeLevelId);
        }

        if ($degreeLevelName) {
            $needle = strtolower(trim($degreeLevelName));

            return $levels->first(function (DegreeLevel $level) use ($needle) {
                return strtolower($level->name) === $needle
                    || strtolower($level->slug) === $needle;
            });
        }

        return null;
    }
}
