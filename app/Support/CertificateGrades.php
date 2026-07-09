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
    public static function gpSteps(): array
    {
        return [5.0, 4.5, 4.0, 3.5, 3.0, 2.5, 2.0, 1.5, 1.0, 0.5, 0.0];
    }

    /** @return array<float> */
    public static function gradePointPaletteForTarget(float $targetCgpa): array
    {
        $steps = self::gpSteps();
        $targetCgpa = self::snapGp($targetCgpa);
        $targetIdx = array_search($targetCgpa, $steps, true);

        if ($targetIdx === false) {
            return $steps;
        }

        $palette = [];
        foreach ([-3, -2, -1, 0, 1, 2, 3, -2, -1, 0, 1, 2, 0, -1, 1, 0, 2, -2, 1, -1, 0, 2, -1, 1, 0, -2] as $offset) {
            $idx = $targetIdx + $offset;
            if ($idx >= 0 && $idx < count($steps)) {
                $palette[] = $steps[$idx];
            }
        }

        return $palette !== [] ? $palette : $steps;
    }

    /** @return array<float> */
    public static function gradePointPalette(): array
    {
        return self::gradePointPaletteForTarget(4.0);
    }

    /**
     * Integer marks (30/30/40) that produce the requested GP on the transcript.
     *
     * @return array{assignment: int, quiz: int, exam: int, percentage: float, gp: float}
     */
    public static function marksSplitForGp(float $gp): array
    {
        $targetGp = self::snapGp($gp);
        $idealTotal = (int) round(self::percentageForGp($targetGp));
        $total = $idealTotal;

        for ($try = 0; $try < 8; $try++) {
            $candidate = $idealTotal + (($try % 2 === 0) ? (int) ceil($try / 2) : -(int) ceil($try / 2));
            $candidate = max(0, min(100, $candidate));

            if (self::fromPercentage((float) $candidate)['gp'] === $targetGp) {
                $total = $candidate;
                break;
            }
        }

        $assign = (int) round(30 * $total / 100);
        $quiz = (int) round(30 * $total / 100);
        $exam = $total - $assign - $quiz;

        if ($exam > 40) {
            $overflow = $exam - 40;
            $exam = 40;
            $quiz = max(0, $quiz - (int) ceil($overflow / 2));
            $assign = max(0, $assign - (int) floor($overflow / 2));
        }

        $assign = min(30, max(0, $assign));
        $quiz = min(30, max(0, $quiz));
        $exam = min(40, max(0, $exam));

        $actualTotal = $assign + $quiz + $exam;
        $percentage = round(($actualTotal / 100) * 100, 2);
        $resolved = self::fromPercentage($percentage);

        return [
            'assignment' => $assign,
            'quiz' => $quiz,
            'exam' => $exam,
            'percentage' => $percentage,
            'gp' => $resolved['gp'],
        ];
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
    public static function buildSemesters(array $grouped, ?int $programYears = null): array
    {
        $slotCourses = [];

        foreach ($grouped as $yearKey => $courses) {
            $courses = array_values($courses);
            if ($courses === []) {
                continue;
            }

            $yearIdx = (int) ($courses[0]['year_index'] ?? self::parseYearIndexFromKey($yearKey));
            $hasSemester = array_key_exists('semester', $courses[0]);

            if ($hasSemester) {
                foreach ($courses as $course) {
                    $sem = max(1, min(2, (int) ($course['semester'] ?? 1)));
                    $slotCourses[sprintf('%02d-%d', $yearIdx, $sem)][] = $course;
                }

                continue;
            }

            $chunks = array_chunk($courses, max(1, (int) ceil(count($courses) / 2)));
            foreach ($chunks as $semesterIndex => $chunk) {
                $sem = $semesterIndex + 1;
                foreach ($chunk as $course) {
                    $slotCourses[sprintf('%02d-%d', $yearIdx, $sem)][] = $course;
                }
            }
        }

        ksort($slotCourses, SORT_NATURAL);

        $maxYear = $programYears;
        if (! $maxYear) {
            $maxYear = 1;
            foreach (array_keys($slotCourses) as $key) {
                $year = (int) explode('-', $key)[0];
                $maxYear = max($maxYear, $year);
            }
        }

        $semesters = [];
        $runningCgpaNumerator = 0.0;
        $runningCgpaUnits = 0;

        for ($year = 1; $year <= $maxYear; $year++) {
            for ($semesterNumber = 1; $semesterNumber <= \App\Support\ProgramDuration::SEMESTERS_PER_YEAR; $semesterNumber++) {
                $chunk = $slotCourses[sprintf('%02d-%d', $year, $semesterNumber)] ?? [];
                if ($chunk === []) {
                    continue;
                }

                $enriched = [];
                foreach ($chunk as $course) {
                    if (isset($course['gp'], $course['gd'])) {
                        $grades = ['gp' => (float) $course['gp'], 'gd' => (string) $course['gd']];
                    } else {
                        $grades = self::fromPercentage((float) ($course['percentage'] ?? 0));
                    }
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
                    'title'      => "YEAR {$year} — SEMESTER {$semesterNumber}",
                    'year_index' => $year,
                    'semester'   => $semesterNumber,
                    'courses'    => $enriched,
                    'gpa'        => $gpa,
                    'cgpa'       => $cgpa,
                ];
            }
        }

        return $semesters;
    }

    private static function parseYearIndexFromKey(string $yearKey): int
    {
        if (preg_match('/year\s*(\d+)/i', $yearKey, $match)) {
            return max(1, (int) $match[1]);
        }

        return 1;
    }

    public static function finalCgpa(array $semesters): float
    {
        if (empty($semesters)) {
            return 0.0;
        }

        return (float) end($semesters)['cgpa'];
    }
}
