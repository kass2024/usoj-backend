<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Support\Str;

class CertificatePresenter
{
    public static function imageDataUri(?string $absolutePath): ?string
    {
        if (!$absolutePath || !is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: 'image/png';
        $contents = @file_get_contents($absolutePath);

        if ($contents === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    public static function photoPath(Student $student): string
    {
        foreach (self::photoCandidatePaths($student) as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return public_path('images/profile.jpg');
    }

    /** @return array<int, string> */
    public static function photoCandidatePaths(Student $student): array
    {
        if (!$student->profile_img) {
            return [public_path('images/profile.jpg')];
        }

        $relative = ltrim($student->profile_img, '/');

        return array_values(array_unique([
            storage_path('app/public/' . $relative),
            public_path('storage/' . $relative),
            public_path($relative),
        ]));
    }

    public static function photoDataUri(Student $student): ?string
    {
        foreach (self::photoCandidatePaths($student) as $path) {
            $uri = self::imageDataUri($path);
            if ($uri) {
                return $uri;
            }
        }

        return self::imageDataUri(public_path('images/profile.jpg'));
    }

    public static function crestDataUri(): ?string
    {
        foreach ([
            public_path('images/usj-crest.png'),
            public_path('images/usjm.png'),
        ] as $path) {
            $uri = self::imageDataUri($path);
            if ($uri) {
                return $uri;
            }
        }

        return null;
    }

    public static function registrarStampDataUri(): ?string
    {
        return self::registrarStampCombinedDataUri();
    }

    public static function registrarStampCombinedDataUri(): ?string
    {
        foreach ([
            public_path('images/usoj/degree-registrar-stamp-clean.png'),
            public_path('images/usoj/registrar-stamp-clean.png'),
            public_path('images/usoj/degree-registrar-stamp.png'),
            public_path('images/usoj/registrar-stamp.png'),
        ] as $path) {
            $uri = self::imageDataUri($path);
            if ($uri) {
                return $uri;
            }
        }

        return null;
    }

    public static function registrarStampOnlyDataUri(): ?string
    {
        foreach ([
            public_path('images/usoj/registrar-stamp-only-clean.png'),
            public_path('images/usoj/registrar-stamp-only.png'),
        ] as $path) {
            $uri = self::imageDataUri($path);
            if ($uri) {
                return $uri;
            }
        }

        return null;
    }

    public static function registrarSignatureOnlyDataUri(): ?string
    {
        foreach ([
            public_path('images/usoj/registrar-signature-only-clean.png'),
            public_path('images/usoj/registrar-signature-only.png'),
        ] as $path) {
            $uri = self::imageDataUri($path);
            if ($uri) {
                return $uri;
            }
        }

        return null;
    }

    public static function vcSignatureDataUri(): ?string
    {
        foreach ([
            public_path('images/usoj/vc-signature-clean.png'),
            public_path('images/usoj/vc-signature.png'),
        ] as $path) {
            $uri = self::imageDataUri($path);
            if ($uri) {
                return $uri;
            }
        }

        return null;
    }

    public static function degreeBackgroundColor(): string
    {
        return '#FFFFFF';
    }

    public static function photoUrl(Student $student): string
    {
        return StudentPhoto::url($student);
    }

    public static function registrarStampPath(): string
    {
        return public_path('images/usoj/degree-registrar-stamp.png');
    }

    public static function vcSignaturePath(): string
    {
        return public_path('images/usoj/vc-signature.png');
    }

    public static function facultyName(Student $student): string
    {
        $school = optional(optional($student->department)->school)->name;

        return strtoupper($school ?: 'MANAGEMENT SCIENCE');
    }

    public static function programName(Student $student): string
    {
        $degree = optional($student->degree_level)->name;
        $department = optional($student->department)->name;

        if ($degree && $department) {
            return strtoupper($degree . ' IN ' . $department);
        }

        return strtoupper($degree ?: $department ?: 'BACHELOR PROGRAMME');
    }

    public static function awardName(Student $student): string
    {
        $degree = optional($student->degree_level)->name;
        $department = optional($student->department)->name;

        if ($degree && $department) {
            return strtoupper($degree . ' IN ' . $department);
        }

        return strtoupper($degree ?: 'DEGREE AWARD');
    }

    public static function studentFullName(Student $student): string
    {
        return strtoupper(trim($student->fname . ' ' . $student->lname));
    }

    public static function studentDisplayName(Student $student): string
    {
        return Str::title(trim($student->fname . ' ' . $student->lname));
    }

    public static function serialNumber(Student $student): string
    {
        return 'S/N: ' . str_pad((string) $student->id, 12, '0', STR_PAD_LEFT);
    }

    public static function completionYear(?Student $student = null): string
    {
        if ($student) {
            return (string) TranscriptProfile::completionYear($student);
        }

        return (string) now()->year;
    }

    public static function formattedDate(): string
    {
        return now()->format('d') . self::ordinalSuffix((int) now()->format('j')) . '-' . now()->format('F-Y');
    }

    private static function ordinalSuffix(int $day): string
    {
        if ($day >= 11 && $day <= 13) {
            return 'th';
        }

        return match ($day % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }
}
