<?php

namespace App\Http\Controllers;

use App\Models\Modules;
use App\Models\Student;
use App\Support\CertificateGrades;
use App\Support\CertificatePresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificatesController extends Controller
{
    public function index(Request $request)
    {
        return view('certificates.index');
    }

    public function verifyRegNumber(Request $request)
    {
        if (!$request->filled('regNumber')) {
            return redirect()->back()->with('error', 'Registration number is required');
        }

        $student = Student::with('department')
            ->where('reg_number', $request->input('regNumber'))
            ->first();

        if (!$student) {
            return redirect()->back()->with('error', 'Student not found');
        }

        return view('certificates.review', [
            'student' => $student,
        ]);
    }

    public function generateTranscript(Request $request, $studentId)
    {
        $studentId = decrypt($studentId);
        $student = Student::with(['department.school', 'degree_level'])->findOrFail($studentId);

        $pdf = Pdf::loadView('certificates.transcript', $this->buildCertificateData($student))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 120,
            ]);

        return $pdf->stream($student->reg_number . '_transcript.pdf');
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
            ->back()
            ->with('success', 'Student photo updated successfully.');
    }

    public function generateDegree(Request $request, $studentId)
    {
        $studentId = decrypt($studentId);
        $student = Student::with(['department.school', 'degree_level'])->findOrFail($studentId);

        $pdf = Pdf::loadView('certificates.degree', $this->buildCertificateData($student))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 120,
            ]);

        return $pdf->stream($student->reg_number . '_degree.pdf');
    }

    private function buildCertificateData(Student $student): array
    {
        $grouped = $this->getStudentCoursesFromSubmissions($student);
        $semesters = CertificateGrades::buildSemesters($grouped);
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
            'serial_number'    => CertificatePresenter::serialNumber($student),
            'completion_year'  => CertificatePresenter::completionYear(),
            'issue_date'       => CertificatePresenter::formattedDate(),
            'student_fullname' => CertificatePresenter::studentFullName($student),
            'student_name'     => CertificatePresenter::studentDisplayName($student),
            'registrar_stamp'  => CertificatePresenter::registrarStampPath(),
            'vc_signature'     => CertificatePresenter::vcSignaturePath(),
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

            $ay = $moduleClassYear->academic_year ?? null;
            $ayName = $ay->name ?? ($ay->title ?? ($ay->label ?? ''));

            $yearKey = trim($yearName . ' — ' . $ayName);

            $marksOver20 = $max > 0 ? round(($total / $max) * 20, 2) : 0.0;
            $percentage = $max > 0 ? round(($total / $max) * 100, 2) : 0.0;
            $credits = (int) ($course->credits ?? 0);
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
            ];
        }

        foreach ($grouped as $yearKey => $rows) {
            $grouped[$yearKey] = array_values($rows);
        }
        ksort($grouped, SORT_NATURAL);

        return $grouped;
    }
}
