<?php

namespace App\Http\Controllers;

use App\Models\DocumentUploadLink;
use App\Models\Student;
use App\Models\StudentExternalDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentUploadPortalController extends Controller
{
    public function showLogin(string $slug)
    {
        $link = DocumentUploadLink::where('slug', $slug)->firstOrFail();

        if (!$link->isUsable()) {
            return view('document-portal.inactive');
        }

        if (session('document_upload_link_id') === $link->id) {
            return redirect()->route('document-portal.dashboard', $slug);
        }

        return view('document-portal.login', compact('link'));
    }

    public function login(Request $request, string $slug)
    {
        $link = DocumentUploadLink::where('slug', $slug)->firstOrFail();

        if (!$link->isUsable()) {
            return view('document-portal.inactive');
        }

        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (
            strcasecmp($credentials['username'], $link->username) !== 0
            || !$link->checkPassword($credentials['password'])
        ) {
            return back()
                ->withInput()
                ->with('error', 'Invalid username or password.');
        }

        session([
            'document_upload_link_id' => $link->id,
            'document_upload_link_slug' => $link->slug,
        ]);

        return redirect()->route('document-portal.dashboard', $slug);
    }

    public function logout(string $slug)
    {
        session()->forget([
            'document_upload_link_id',
            'document_upload_link_slug',
            'portal_student_id',
        ]);

        return redirect()
            ->route('document-portal.login', $slug)
            ->with('success', 'Logged out successfully.');
    }

    public function dashboard(Request $request, string $slug)
    {
        /** @var DocumentUploadLink $link */
        $link = $request->attributes->get('document_upload_link');
        $student = null;
        $externalTranscript = null;
        $externalDegree = null;

        if ($studentId = session('portal_student_id')) {
            $student = Student::with(['department', 'externalTranscript', 'externalDegree'])
                ->find($studentId);

            if ($student) {
                $externalTranscript = $student->externalTranscript;
                $externalDegree = $student->externalDegree;
            }
        }

        return view('document-portal.dashboard', compact(
            'link',
            'student',
            'externalTranscript',
            'externalDegree'
        ));
    }

    public function lookup(Request $request, string $slug)
    {
        $request->validate([
            'reg_number' => 'required|string|max:50',
        ]);

        $regNumber = trim($request->input('reg_number'));

        $student = Student::with(['department', 'externalTranscript', 'externalDegree'])
            ->whereRaw('UPPER(reg_number) = ?', [strtoupper($regNumber)])
            ->first();

        if (!$student) {
            session()->forget('portal_student_id');

            return redirect()
                ->route('document-portal.dashboard', $slug)
                ->with('error', 'Student not found for registration number: ' . $regNumber)
                ->withInput();
        }

        session(['portal_student_id' => $student->id]);

        return redirect()
            ->route('document-portal.dashboard', $slug)
            ->with('success', 'Student found. You can upload transcript and/or degree.');
    }

    public function upload(Request $request, string $slug)
    {
        /** @var DocumentUploadLink $link */
        $link = $request->attributes->get('document_upload_link');

        $data = $request->validate([
            'type' => 'required|in:transcript,degree',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $studentId = session('portal_student_id');
        if (!$studentId) {
            return redirect()
                ->route('document-portal.dashboard', $slug)
                ->with('error', 'Look up a registration number first.');
        }

        $student = Student::findOrFail($studentId);

        $file = $request->file('document');
        $path = $file->store('external_documents/' . $student->id, 'public');

        $existing = StudentExternalDocument::where('student_id', $student->id)
            ->where('type', $data['type'])
            ->first();

        if ($existing) {
            if ($existing->existsOnDisk()) {
                Storage::disk('public')->delete($existing->path);
            }

            $existing->update([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_via_link_id' => $link->id,
            ]);
        } else {
            StudentExternalDocument::create([
                'student_id' => $student->id,
                'type' => $data['type'],
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_via_link_id' => $link->id,
            ]);
        }

        return redirect()
            ->route('document-portal.dashboard', $slug)
            ->with('upload_success', ucfirst($data['type']) . ' uploaded successfully.')
            ->with('upload_type', $data['type']);
    }
}
