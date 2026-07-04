<?php

namespace App\Support;

class CertificateGrades
{
    public static function fromPercentage(float $percentage): array
    {
        return match (true) {
            $percentage >= 80   => ['gp' => 5.0, 'gd' => 'A'],
            $percentage >= 75   => ['gp' => 4.5, 'gd' => 'B+'],
            $percentage >= 70   => ['gp' => 4.0, 'gd' => 'B'],
            $percentage >= 65   => ['gp' => 3.5, 'gd' => 'B'],
            $percentage >= 60   => ['gp' => 3.0, 'gd' => 'C+'],
            $percentage >= 55   => ['gp' => 2.5, 'gd' => 'C'],
            $percentage >= 45   => ['gp' => 1.5, 'gd' => 'C'],
            $percentage >= 40   => ['gp' => 1.0, 'gd' => 'D+'],
            $percentage >= 35   => ['gp' => 0.5, 'gd' => 'D'],
            default             => ['gp' => 0.0, 'gd' => 'F'],
        };
    }

    public static function semesterGpa(array $courses): float
    {
        $weighted = 0.0;
        $units = 0;

        foreach ($courses as $course) {
            $cu = (int) ($course['credits'] ?? 0);
            $gp = (float) ($course['gp'] ?? 0);
            if ($cu <= 0) {
                continue;
            }
            $weighted += $gp * $cu;
            $units += $cu;
        }

        return $units > 0 ? round($weighted / $units, 2) : 0.0;
    }

    public static function classifyCgpa(float $cgpa): string
    {
        return match (true) {
            $cgpa >= 4.40 => 'First Class Honours',
            $cgpa >= 3.60 => 'Second Class Honours (Upper Division)',
            $cgpa >= 2.80 => 'Second Class Honours (Lower Division)',
            $cgpa >= 2.00 => 'Third Class / Pass',
            default       => 'Fail',
        };
    }

    public static function degreeClassLabel(float $cgpa): string
    {
        return match (true) {
            $cgpa >= 4.40 => 'First Class',
            $cgpa >= 3.60 => 'Second Class - Upper Division',
            $cgpa >= 2.80 => 'Second Class - Lower Division',
            $cgpa >= 2.00 => 'Pass',
            default       => 'Fail',
        };
    }

    /**
     * Split year-grouped courses into semester blocks for transcript layout.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $grouped
     * @return array<int, array<string, mixed>>
     */
    public static function buildSemesters(array $grouped): array
    {
        $semesters = [];
        $yearIndex = 0;
        $runningCgpaNumerator = 0.0;
        $runningCgpaUnits = 0;

        foreach ($grouped as $yearKey => $courses) {
            $yearIndex++;
            $chunks = array_chunk(array_values($courses), max(1, (int) ceil(count($courses) / 2)));

            foreach ($chunks as $semesterIndex => $chunk) {
                $semesterNumber = $semesterIndex + 1;
                $enriched = [];

                foreach ($chunk as $course) {
                    $grades = self::fromPercentage((float) ($course['percentage'] ?? 0));
                    $enriched[] = array_merge($course, $grades);
                }

                $gpa = self::semesterGpa($enriched);

                foreach ($enriched as $course) {
                    $cu = (int) ($course['credits'] ?? 0);
                    $runningCgpaNumerator += ((float) $course['gp']) * $cu;
                    $runningCgpaUnits += $cu;
                }

                $cgpa = $runningCgpaUnits > 0
                    ? round($runningCgpaNumerator / $runningCgpaUnits, 2)
                    : 0.0;

                $semesters[] = [
                    'title'   => "YEAR {$yearIndex} SEMESTER {$semesterNumber} — {$yearKey}",
                    'courses' => $enriched,
                    'gpa'     => $gpa,
                    'cgpa'    => $cgpa,
                ];
            }
        }

        return $semesters;
    }

    public static function finalCgpa(array $semesters): float
    {
        if (empty($semesters)) {
            return 0.0;
        }

        return (float) end($semesters)['cgpa'];
    }
}
