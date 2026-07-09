<?php

namespace App\Support;

use App\Models\Student;
use Carbon\Carbon;

class TranscriptProfile
{
    /** @return array<int, string> */
    public static function missingFields(Student $student): array
    {
        $missing = [];

        if (! self::gender($student)) {
            $missing[] = 'gender';
        }

        if (! $student->date_of_birth) {
            $missing[] = 'date_of_birth';
        }

        return $missing;
    }

    public static function isReady(Student $student): bool
    {
        return self::missingFields($student) === [];
    }

    public static function gender(Student $student): ?string
    {
        $gender = strtoupper(trim((string) ($student->gender ?? '')));

        return in_array($gender, ['MALE', 'FEMALE', 'OTHER'], true) ? $gender : null;
    }

    public static function genderLabel(Student $student): string
    {
        return self::gender($student) ?? 'N/A';
    }

    public static function dateOfBirthFormatted(Student $student): string
    {
        if (! $student->date_of_birth) {
            return 'N/A';
        }

        return Carbon::parse($student->date_of_birth)->format('d/m/Y');
    }

    public static function nationality(Student $student): string
    {
        $value = trim((string) ($student->nationality ?? ''));

        return $value !== '' ? strtoupper($value) : 'UGANDAN';
    }

    public static function completionYear(Student $student): int
    {
        return StudentCompletionYear::resolve($student);
    }

    public static function photoId(Student $student): string
    {
        return str_pad((string) $student->id, 10, '0', STR_PAD_LEFT);
    }

    /** @param array<int, string> $fields */
    public static function formatFieldList(array $fields): string
    {
        if ($fields === []) {
            return '';
        }

        $labels = [];
        foreach ($fields as $field) {
            $labels[] = str_replace('_', ' ', $field);
        }

        return implode(', ', $labels);
    }

    public static function missingFieldsLabel(Student $student): string
    {
        return self::formatFieldList(self::missingFields($student));
    }

    /** @return array<string, mixed> */
    public static function readinessPayload(Student $student): array
    {
        $missing = self::missingFields($student);

        return [
            'ready' => $missing === [],
            'missing' => $missing,
            'message' => $missing === []
                ? 'Student profile is ready for transcript generation.'
                : 'Please enter '.self::formatFieldList($missing).' before generating the transcript.',
        ];
    }
}
