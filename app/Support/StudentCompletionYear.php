<?php

namespace App\Support;

use App\Models\Student;
use Carbon\Carbon;

class StudentCompletionYear
{
    public static function admissionYear(Student $student): int
    {
        if ($year = self::parseAdmissionYearFromRegNumber($student->reg_number ?? '')) {
            return $year;
        }

        if ($student->created_at) {
            return (int) Carbon::parse($student->created_at)->format('Y');
        }

        return (int) now()->year;
    }

    public static function resolve(Student $student): int
    {
        $student->loadMissing(['degreeLevel', 'degree_level']);

        $programYears = ProgramDuration::yearsForStudent($student);

        return self::admissionYear($student) + $programYears - 1;
    }

    public static function syncToStudent(Student $student): Student
    {
        $student->completion_year = self::resolve($student);

        return $student;
    }

    public static function parseAdmissionYearFromRegNumber(string $regNumber): ?int
    {
        $regNumber = trim($regNumber);

        if ($regNumber === '' || ! preg_match('/^(\d{2})/', $regNumber, $matches)) {
            return null;
        }

        $twoDigitYear = (int) $matches[1];
        $currentTwoDigit = (int) now()->format('y');
        $century = $twoDigitYear > $currentTwoDigit + 5 ? 1900 : 2000;

        return $century + $twoDigitYear;
    }

    public static function explanation(Student $student): string
    {
        $admission = self::admissionYear($student);
        $student->loadMissing(['degreeLevel', 'degree_level']);
        $years = ProgramDuration::yearsForStudent($student);
        $completion = self::resolve($student);

        return "Admission {$admission} + {$years}-year programme → completion {$completion}";
    }
}
