<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentPhotoController extends Controller
{
    public function show(Student $student): BinaryFileResponse
    {
        $isStaff = auth()->guard('web')->check();
        $loggedStudent = auth()->guard('student')->user();

        if (! $isStaff && (! $loggedStudent || (int) $loggedStudent->id !== (int) $student->id)) {
            abort(403);
        }

        $path = $student->profile_img;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return response()->file(public_path('images/profile.jpg'));
        }

        $absolute = Storage::disk('public')->path($path);
        $mime = mime_content_type($absolute) ?: 'image/jpeg';

        return response()->file($absolute, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
