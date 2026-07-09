<?php

namespace App\Support;

use App\Models\Student;

class TranscriptSemesterLabels
{
    /**
     * @param  array<int, array<string, mixed>>  $semesters
     * @return array<int, array<string, mixed>>
     */
    public static function apply(array $semesters, Student $student): array
    {
        $programYears = ProgramDuration::yearsForStudent($student);
        $completionYear = TranscriptProfile::completionYear($student);
        $baseYear = $completionYear - $programYears;

        foreach ($semesters as $index => $semester) {
            $yearIndex = (int) ($semester['year_index'] ?? 1);
            $semesterNumber = (int) ($semester['semester'] ?? 1);
            $calendarYear = $baseYear + $yearIndex;
            $session = $semesterNumber === 1 ? 'JANUARY' : 'MAY';

            $semesters[$index]['title'] = sprintf(
                'YEAR %d SEMESTER %d %s %d',
                $yearIndex,
                $semesterNumber,
                $session,
                $calendarYear
            );
        }

        return $semesters;
    }
}
