<?php

namespace App\Support;

class CertificateGrades
{
    /** @var array<int, array{min: float, gp: float, gd: string}> */
    private const BANDS = [
        ['min' => 80, 'gp' => 5.0, 'gd' => 'A'],
        ['min' => 75, 'gp' => 4.5, 'gd' => 'B+'],
        ['min' => 70, 'gp' => 4.0, 'gd' => 'B'],
        ['min' => 65, 'gp' => 3.5, 'gd' => 'B-'],
        ['min' => 60, 'gp' => 3.0, 'gd' => 'C+'],
        ['min' => 55, 'gp' => 2.5, 'gd' => 'C'],
        ['min' => 50, 'gp' => 2.0, 'gd' => 'C-'],
        ['min' => 45, 'gp' => 1.5, 'gd' => 'D+'],
        ['min' => 40, 'gp' => 1.0, 'gd' => 'D'],
        ['min' => 35, 'gp' => 0.5, 'gd' => 'D-'],
        ['min' => 0,  'gp' => 0.0, 'gd' => 'F'],
    ];

    public static function fromPercentage(float $percentage): array
    {
        foreach (self::BANDS as $band) {
            if ($percentage >= $band['min']) {
                return ['gp' => $band['gp'], 'gd' => $band['gd']];
            }
        }

        return ['gp' => 0.0, 'gd' => 'F'];
    }

    public static function percentageForGp(float $gp): float
    {
        $gp = max(0.0, min(5.0, round($gp, 2)));

        return match (true) {
            $gp >= 5.0   => 85.0,
            $gp >= 4.5   => 77.5,
            $gp >= 4.0   => 72.5,
            $gp >= 3.5   => 67.5,
            $gp >= 3.0   => 62.5,
            $gp >= 2.5   => 57.5,
            $gp >= 2.0   => 52.5,
            $gp >= 1.5   => 47.5,
            $gp >= 1.0   => 42.5,
            $gp >= 0.5   => 37.5,
            default      => 30.0,
        };
    }

    /** @return array<float> */
    public static function gradePointPalette(): array
    {
        return [5.0, 4.5, 4.0, 3.5, 3.0, 2.5, 4.5, 4.0, 5.0, 3.5, 4.0, 4.5, 3.0, 3.5, 4.0, 2.5, 4.5, 5.0, 3.5, 4.0, 4.5, 3.0, 4.0, 3.5, 2.5, 3.0];
    }

    public static function snapGp(float $gp): float
    {
        $gp = max(0.0, min(5.0, $gp));
        $steps = [5.0, 4.5, 4.0, 3.5, 3.0, 2.5, 2.0, 1.5, 1.0, 0.5, 0.0];
        $closest = $steps[0];
        $bestDiff = abs($gp - $closest);

        foreach ($steps as $step) {
            $diff = abs($gp - $step);
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $closest = $step;
            }
        }

        return $closest;
    }

    public static function resolveCourseCredits(object $course): int
    {
        $credits = (int) ($course->credits ?? 0);

        if ($credits >= 2 && $credits <= 6) {
            return $credits;
        }

        $code = (string) ($course->code ?? $course->id ?? 'course');

        return 2 + (crc32($code) % 4);
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
                    'title'      => "YEAR {$yearIndex} — SEMESTER {$semesterNumber}",
                    'year_index' => $yearIndex,
                    'courses'    => $enriched,
                    'gpa'        => $gpa,
                    'cgpa'       => $cgpa,
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
