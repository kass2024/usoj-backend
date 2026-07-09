<?php

namespace App\Support;

class CourseCodeScheduler
{
    /**
     * Parse year and semester from codes like ICT1101 (Y1 S1), ICT2208 (Y2 S2).
     *
     * @return array{year_index: int, semester: int}|null
     */
    public static function parseYearSemesterFromCode(string $code, int $programYears = 4): ?array
    {
        $code = strtoupper(trim($code));

        if (preg_match('/^[A-Z]+(\d)(\d)\d{2}$/', $code, $match)) {
            $year = (int) $match[1];
            $semester = (int) $match[2];

            if ($year >= 1 && $year <= $programYears && $semester >= 1 && $semester <= ProgramDuration::SEMESTERS_PER_YEAR) {
                return ['year_index' => $year, 'semester' => $semester];
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{code?: string|null, name: string, year_index?: int|null, semester?: int|null}>  $courses
     * @return array<int, array{code: string, name: string, year_index: int, semester: int, credits: int, status: string}>
     */
    public static function schedule(
        array $courses,
        string $codePrefix,
        int $programYears,
        int $defaultCredits = 3
    ): array {
        $slots = [];
        for ($year = 1; $year <= $programYears; $year++) {
            for ($semester = 1; $semester <= ProgramDuration::SEMESTERS_PER_YEAR; $semester++) {
                $slots[] = ['year_index' => $year, 'semester' => $semester, 'courses' => []];
            }
        }

        $unscheduled = [];

        foreach ($courses as $index => $course) {
            $name = trim((string) ($course['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $code = strtoupper(trim((string) ($course['code'] ?? '')));
            $yearIndex = isset($course['year_index']) ? (int) $course['year_index'] : null;
            $semester = isset($course['semester']) ? (int) $course['semester'] : null;

            if ($code !== '') {
                $parsed = self::parseYearSemesterFromCode($code, $programYears);
                if ($parsed) {
                    $yearIndex = $parsed['year_index'];
                    $semester = $parsed['semester'];
                }
            }

            $entry = [
                'code' => $code,
                'name' => $name,
                'credits' => (int) ($course['credits'] ?? $defaultCredits),
                'status' => (string) ($course['status'] ?? 'active'),
                'year_index' => $yearIndex,
                'semester' => $semester,
                '_order' => $index,
            ];

            if ($yearIndex && $semester) {
                $normalized = ProgramDuration::normalizeYearSemester($yearIndex, $semester, $programYears);
                $slotKey = ($normalized['year_index'] - 1) * ProgramDuration::SEMESTERS_PER_YEAR
                    + ($normalized['semester'] - 1);
                if (isset($slots[$slotKey])) {
                    $entry['year_index'] = $normalized['year_index'];
                    $entry['semester'] = $normalized['semester'];
                    $slots[$slotKey]['courses'][] = $entry;
                    continue;
                }
            }

            $unscheduled[] = $entry;
        }

        foreach ($unscheduled as $entry) {
            usort($slots, fn ($a, $b) => count($a['courses']) <=> count($b['courses']));
            $slots[0]['courses'][] = $entry;
        }

        $scheduled = [];
        $slotCounters = [];

        foreach ($slots as $slot) {
            foreach ($slot['courses'] as $course) {
                $year = $slot['year_index'];
                $semester = $slot['semester'];
                $key = "{$year}-{$semester}";
                $slotCounters[$key] = ($slotCounters[$key] ?? 0) + 1;
                $sequence = $slotCounters[$key];

                $code = $course['code'] !== ''
                    ? $course['code']
                    : self::generateCode($codePrefix, $year, $semester, $sequence);

                $credits = CourseCreditEstimator::estimate($course['name'], $code, $year);

                $scheduled[] = [
                    'code' => $code,
                    'name' => $course['name'],
                    'credits' => $credits,
                    'status' => in_array($course['status'], ['active', 'inactive'], true) ? $course['status'] : 'active',
                    'year_index' => $year,
                    'semester' => $semester,
                ];
            }
        }

        usort($scheduled, function ($a, $b) {
            return [$a['year_index'], $a['semester'], $a['code']]
                <=> [$b['year_index'], $b['semester'], $b['code']];
        });

        return $scheduled;
    }

    public static function generateCode(string $prefix, int $year, int $semester, int $sequence): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z]/', '', $prefix) ?: 'CRS');

        return sprintf('%s%d%d%02d', $prefix, $year, $semester, $sequence);
    }

    public static function departmentCodePrefix(string $departmentName): string
    {
        $clean = preg_replace('/[^a-zA-Z\s]/', '', $departmentName) ?? '';
        $words = preg_split('/\s+/', trim($clean)) ?: [];

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 2).substr($words[1], 0, 1));
        }

        $letters = strtoupper(preg_replace('/[^A-Z]/', '', strtoupper($clean)) ?: 'CRS');

        return substr($letters, 0, 3) ?: 'CRS';
    }
}
