<?php

namespace App\Http\Controllers;

use App\Models\Modules;
use App\Models\Student;
use App\Support\CertificateGrades;
use App\Support\CertificatePresenter;
use App\Support\ProgramDuration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CertificatesController extends Controller
{
    public function index(Request $request)
    {
        $student = null;
        $externalTranscript = null;
        $externalDegree = null;

        if ($studentId = session('certificate_student_id')) {
            $student = Student::with(['department', 'externalTranscript', 'externalDegree'])
                ->find($studentId);

            if ($student) {
                $externalTranscript = $student->externalTranscript;
                $externalDegree = $student->externalDegree;
            }
        }

        return view('certificates.index', compact(
            'student',
            'externalTranscript',
            'externalDegree'
        ));
    }

    public function verifyRegNumber(Request $request)
    {
        if (!$request->filled('regNumber')) {
            return redirect()
                ->route('certificates.index')
                ->with('error', 'Registration number is required');
        }

        $regNumber = trim($request->input('regNumber'));

        $student = Student::with(['department', 'externalTranscript', 'externalDegree'])
            ->whereRaw('UPPER(reg_number) = ?', [strtoupper($regNumber)])
            ->first();

        if (!$student) {
            return redirect()
                ->route('certificates.index')
                ->with('error', 'Student not found for registration number: ' . $regNumber)
                ->withInput();
        }

        return redirect()
            ->route('certificates.index')
            ->with('certificate_student_id', $student->id)
            ->withInput();
    }

    public function generateTranscript(Request $request, $studentId)
    {
        $studentId = decrypt($studentId);
        $student = Student::with(['department.school', 'degree_level'])->findOrFail($studentId);

        return $this->streamTranscriptPdf($student);
    }

    public function streamTranscriptPdf(Student $student)
    {
        $student->loadMissing(['department.school', 'degree_level']);

        return $this->makeTranscriptPdf($student)->stream($student->reg_number . '_transcript.pdf');
    }

    public function deletePhoto($studentId)
    {
        $studentId = decrypt($studentId);
        $student = Student::findOrFail($studentId);

        if ($student->profile_img && Storage::disk('public')->exists($student->profile_img)) {
            Storage::disk('public')->delete($student->profile_img);
        }

        $student->update(['profile_img' => null]);

        return redirect()
            ->route('certificates.index')
            ->with('certificate_student_id', $student->id)
            ->with('success', 'Student photo deleted successfully.');
    }

    public function emailDocuments(Request $request, $studentId)
    {
        $studentId = decrypt($studentId);

        $request->validate([
            'email' => 'required|email',
            'documents' => 'required|in:transcript,degree,both',
        ]);

        $student = Student::with(['department.school', 'degree_level'])->findOrFail($studentId);
        $recipient = $request->input('email');
        $documents = $request->input('documents');

        $attachments = [];

        if (in_array($documents, ['transcript', 'both'], true)) {
            $attachments[] = [
                'pdf' => $this->makeTranscriptPdf($student),
                'filename' => $student->reg_number . '_transcript.pdf',
            ];
        }

        if (in_array($documents, ['degree', 'both'], true)) {
            $attachments[] = [
                'pdf' => $this->makeDegreePdf($student),
                'filename' => $student->reg_number . '_degree.pdf',
            ];
        }

        try {
            Mail::send('emails.certificate-documents', [
                'student' => $student,
                'documents' => $documents,
            ], function ($message) use ($recipient, $student, $attachments) {
                $message->to($recipient)
                    ->subject('University of Saint Joseph Mbarara - Academic Documents for ' . $student->reg_number);

                foreach ($attachments as $attachment) {
                    $message->attachData(
                        $attachment['pdf']->output(),
                        $attachment['filename'],
                        ['mime' => 'application/pdf']
                    );
                }
            });
        } catch (\Throwable $e) {
            return redirect()
                ->route('certificates.index')
                ->with('certificate_student_id', $student->id)
                ->with('error', 'Failed to send email: ' . $e->getMessage());
        }

        return redirect()
            ->route('certificates.index')
            ->with('certificate_student_id', $student->id)
            ->with('success', 'Documents sent successfully to ' . $recipient . '.');
    }

    public function uploadPhoto(Request $request, $studentId)
    {
        $studentId = decrypt($studentId);

        $request->validate([
            'profile_img' => 'required|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        $student = Student::findOrFail($studentId);

        if ($student->profile_img && Storage::disk('public')->exists($student->profile_img)) {
            Storage::disk('public')->delete($student->profile_img);
        }

        $path = $request->file('profile_img')->store('profile_images', 'public');
        $student->update(['profile_img' => $path]);

        return redirect()
            ->route('certificates.index')
            ->with('certificate_student_id', $student->id)
            ->with('success', 'Student photo updated successfully.');
    }

    public function generateDegree(Request $request, $studentId)
    {
        $studentId = decrypt($studentId);
        $student = Student::with(['department.school', 'degree_level'])->findOrFail($studentId);

        return $this->makeDegreePdf($student)->stream($student->reg_number . '_degree.pdf');
    }

    public function viewExternalTranscript($studentId)
    {
        return $this->streamExternalDocument($studentId, 'transcript');
    }

    public function viewExternalDegree($studentId)
    {
        return $this->streamExternalDocument($studentId, 'degree');
    }

    private function streamExternalDocument($studentId, string $type)
    {
        $studentId = decrypt($studentId);
        $student = Student::findOrFail($studentId);

        $document = $student->externalDocuments()
            ->where('type', $type)
            ->first();

        if (!$document || !$document->existsOnDisk()) {
            return redirect()
                ->route('certificates.index')
                ->with('certificate_student_id', $student->id)
                ->with('error', 'No external ' . $type . ' has been uploaded for this student.');
        }

        $absolute = Storage::disk('public')->path($document->path);
        $downloadName = $document->original_name
            ?: ($student->reg_number . '_external_' . $type . '.pdf');

        return response()->file($absolute, [
            'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
        ]);
    }

    private function makeTranscriptPdf(Student $student)
    {
        return Pdf::loadView('certificates.transcript', $this->buildCertificateData($student))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultFont' => 'Times-Roman',
            ]);
    }

    private function makeDegreePdf(Student $student)
    {
        return Pdf::loadView('certificates.degree', $this->buildCertificateData($student))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultFont' => 'Times-Roman',
            ]);
    }

    private function buildCertificateData(Student $student): array
    {
        $grouped = $this->getStudentCoursesFromSubmissions($student);
        $programYears = ProgramDuration::yearsForStudent($student);
        $semesters = CertificateGrades::buildSemesters($grouped, $programYears);
        $finalCgpa = CertificateGrades::finalCgpa($semesters);

        return [
            'student'          => $student,
            'courses'          => $grouped,
            'semesters'        => $semesters,
            'final_cgpa'       => $finalCgpa,
            'award'            => CertificatePresenter::awardName($student),
            'class_label'      => CertificateGrades::classifyCgpa($finalCgpa),
            'degree_class'     => CertificateGrades::degreeClassLabel($finalCgpa),
            'faculty'          => CertificatePresenter::facultyName($student),
            'program'          => CertificatePresenter::programName($student),
            'photo_path'       => CertificatePresenter::photoPath($student),
            'photo_data_uri'   => CertificatePresenter::photoDataUri($student),
            'show_photo'       => true,
            'crest_data_uri'   => CertificatePresenter::crestDataUri(),
            'serial_number'    => CertificatePresenter::serialNumber($student),
            'completion_year'  => CertificatePresenter::completionYear(),
            'issue_date'       => CertificatePresenter::formattedDate(),
            'student_fullname' => CertificatePresenter::studentFullName($student),
            'student_name'     => CertificatePresenter::studentDisplayName($student),
            'registrar_stamp'  => CertificatePresenter::registrarStampPath(),
            'registrar_stamp_data_uri' => CertificatePresenter::registrarStampDataUri(),
            'registrar_stamp_only_data_uri' => CertificatePresenter::registrarStampOnlyDataUri(),
            'registrar_signature_only_data_uri' => CertificatePresenter::registrarSignatureOnlyDataUri(),
            'vc_signature'     => CertificatePresenter::vcSignaturePath(),
            'vc_signature_data_uri'    => CertificatePresenter::vcSignatureDataUri(),
        ];
    }

    private function getStudentCoursesFromSubmissions(Student $student): array
    {
        $studentId = $student->id;

        $modules = Modules::query()
            ->with([
                'course',
                'class_year.academic_year',
                'assignments.submissions' => function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                },
                'quizzes.submissions' => function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                },
                'exams.submissions' => function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                },
            ])
            ->where(function ($q) use ($studentId) {
                $q->whereHas('assignments.submissions', fn ($qq) => $qq->where('student_id', $studentId))
                    ->orWhereHas('quizzes.submissions', fn ($qq) => $qq->where('student_id', $studentId))
                    ->orWhereHas('exams.submissions', fn ($qq) => $qq->where('student_id', $studentId));
            })
            ->get();

        $grouped = [];

        foreach ($modules as $module) {
            $course = $module->course;
            if (!$course) {
                continue;
            }

            $total = 0.0;
            $max = 0.0;
            $has = false;

            foreach ($module->assignments as $assignment) {
                $sub = $assignment->submissions->first();
                if ($sub) {
                    $total += (float) $sub->marks_obtained;
                    $max += 30;
                    $has = true;
                }
            }
            foreach ($module->quizzes as $quiz) {
                $sub = $quiz->submissions->first();
                if ($sub) {
                    $total += (float) $sub->marks_obtained;
                    $max += 30;
                    $has = true;
                }
            }
            foreach ($module->exams as $exam) {
                $sub = $exam->submissions->first();
                if ($sub) {
                    $total += (float) $sub->marks_obtained;
                    $max += 40;
                    $has = true;
                }
            }

            if (!$has) {
                continue;
            }

            $moduleClassYear = $module->class_year;
            $yearName = $moduleClassYear->year_name ?? ($moduleClassYear->name ?? 'Year');

            $yearIndex = 1;
            if (preg_match('/(\d+)/', (string) $yearName, $yearMatch)) {
                $yearIndex = max(1, (int) $yearMatch[1]);
            }

            $ay = $moduleClassYear->academic_year ?? null;
            $ayName = $ay->name ?? ($ay->title ?? ($ay->label ?? ($ay->period ?? '')));

            $yearKey = trim($yearName . ' — ' . $ayName);

            $marksOver20 = $max > 0 ? round(($total / $max) * 20, 2) : 0.0;
            $percentage = $max > 0 ? round(($total / $max) * 100, 2) : 0.0;
            $credits = CertificateGrades::resolveCourseCredits($course);
            $creditMax = round($credits * $marksOver20, 2);

            $courseKey = $course->code ?: ('course_' . $module->id);

            $grouped[$yearKey][$courseKey] = [
                'code'         => $course->code,
                'name'         => $course->name,
                'credits'      => $credits,
                'marks'        => $marksOver20,
                'credit_max'   => $creditMax,
                'credit_marks' => $creditMax,
                'percentage'   => $percentage,
                'year_index'   => $yearIndex,
                'semester'     => max(1, min(2, (int) ($module->semester ?: 1))),
            ];
        }

        foreach ($grouped as $yearKey => $rows) {
            $grouped[$yearKey] = array_values($rows);
        }
        ksort($grouped, SORT_NATURAL);

        return $grouped;
    }
}
