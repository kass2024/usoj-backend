<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Support\Str;

class CertificatePresenter
{
    public static function photoPath(Student $student): string
    {
        if ($student->profile_img && file_exists(storage_path('app/public/' . $student->profile_img))) {
            return storage_path('app/public/' . $student->profile_img);
        }

        return public_path('images/profile.jpg');
    }

    public static function photoUrl(Student $student): string
    {
        if ($student->profile_img) {
            return asset('storage/' . ltrim($student->profile_img, '/'));
        }

        return asset('images/profile.jpg');
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

    public static function completionYear(): string
    {
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
