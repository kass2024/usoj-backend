<?php

namespace App\Support;

use App\Models\DegreeLevel;
use App\Models\Student;

class ProgramDuration
{
    public const BACHELOR_YEARS = 4;

    public const MASTER_YEARS = 2;

    public const SEMESTERS_PER_YEAR = 2;

    public static function yearsForStudent(Student $student): int
    {
        return self::yearsForDegreeLevel($student->degreeLevel ?? $student->degree_level);
    }

    public static function yearsForDegreeLevel(?DegreeLevel $level): int
    {
        if (! $level) {
            return self::BACHELOR_YEARS;
        }

        $label = strtolower(trim(($level->name ?? '').' '.($level->slug ?? '')));

        if (self::isMasterLevel($label)) {
            return self::MASTER_YEARS;
        }

        if (self::isBachelorLevel($label)) {
            return self::BACHELOR_YEARS;
        }

        return self::BACHELOR_YEARS;
    }

    public static function isMasterLevel(string $label): bool
    {
        return (bool) preg_match('/\b(master|masters|msc|ma|mba|mphil|postgraduate|postgrad)\b/i', $label);
    }

    public static function isBachelorLevel(string $label): bool
    {
        return (bool) preg_match('/\b(bachelor|bachelors|bsc|ba|undergrad|undergraduate|honours|honors)\b/i', $label);
    }

    public static function semesterSlots(int $years): int
    {
        return $years * self::SEMESTERS_PER_YEAR;
    }

    public static function semesterSlotsForLevel(?DegreeLevel $level): int
    {
        return self::semesterSlots(self::yearsForDegreeLevel($level));
    }

    /**
     * @return array{year_index: int, semester: int}
     */
    public static function normalizeYearSemester(int $yearIndex, int $semester, int $programYears): array
    {
        return [
            'year_index' => max(1, min($programYears, $yearIndex)),
            'semester' => max(1, min(self::SEMESTERS_PER_YEAR, $semester)),
        ];
    }

    public static function label(int $years): string
    {
        return "{$years}-year";
    }

    public static function structureLabel(?DegreeLevel $level): string
    {
        $years = self::yearsForDegreeLevel($level);
        $slots = self::semesterSlots($years);
        $kind = self::degreeKindLabel($level);

        return "{$kind}: {$years} years × ".self::SEMESTERS_PER_YEAR." semesters = {$slots} semesters";
    }

    public static function shortStructureLabel(?DegreeLevel $level): string
    {
        $years = self::yearsForDegreeLevel($level);

        return "{$years} years × ".self::SEMESTERS_PER_YEAR.' semesters';
    }

    public static function degreeKindLabel(?DegreeLevel $level): string
    {
        $years = self::yearsForDegreeLevel($level);

        return $years === self::MASTER_YEARS ? 'Master' : 'Bachelor';
    }
}
