<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;

class StudentPhoto
{
    public static function url(?Student $student = null, ?string $profileImgPath = null): string
    {
        $path = $profileImgPath ?? $student?->profile_img;

        if (! $path) {
            return asset('images/profile.jpg');
        }

        $relative = ltrim($path, '/');

        if (self::isPubliclyReachable($relative)) {
            return asset('storage/'.$relative);
        }

        if ($student && Storage::disk('public')->exists($relative)) {
            return route('student-photos.show', $student);
        }

        return asset('images/profile.jpg');
    }

    public static function isPubliclyReachable(string $relativePath): bool
    {
        $publicStorage = public_path('storage');

        if (! is_dir($publicStorage)) {
            return false;
        }

        return is_file($publicStorage.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    }
}
