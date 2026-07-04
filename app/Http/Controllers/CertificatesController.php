<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Modules;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $student = Student::with(['department', 'degree_level'])->findOrFail($studentId);

        $coursesByYear = $this->getStudentCoursesFromSubmissions($student);

        $transcriptData = [
            'student' => $student,
            'courses' => $coursesByYear,
        ];

        $pdf = Pdf::loadView('certificates.transcript', $transcriptData)
            ->setPaper('a4', 'portrait');

        return $pdf->stream($student->reg_number . '_transcript.pdf');
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
                $q->whereHas('assignments.submissions', fn($qq) => $qq->where('student_id', $studentId))
                  ->orWhereHas('quizzes.submissions', fn($qq) => $qq->where('student_id', $studentId))
                  ->orWhereHas('exams.submissions', fn($qq) => $qq->where('student_id', $studentId));
            })
            ->get();

        $grouped = [];

        foreach ($modules as $module) {
            $course = $module->course;
            if (!$course) continue;

            $total = 0.0;
            $max   = 0.0;
            $has   = false;

            // Assignment=30, Quiz=30, Exam=40
            foreach ($module->assignments as $assignment) {
                $sub = $assignment->submissions->first();
                if ($sub) { $total += (float)$sub->marks_obtained; $max += 30; $has = true; }
            }
            foreach ($module->quizzes as $quiz) {
                $sub = $quiz->submissions->first();
                if ($sub) { $total += (float)$sub->marks_obtained; $max += 30; $has = true; }
            }
            foreach ($module->exams as $exam) {
                $sub = $exam->submissions->first();
                if ($sub) { $total += (float)$sub->marks_obtained; $max += 40; $has = true; }
            }

            if (!$has) continue;

            $moduleClassYear = $module->class_year;
            $yearName = $moduleClassYear->year_name ?? ($moduleClassYear->name ?? 'Year');

            $ay = $moduleClassYear->academic_year ?? null;
            $ayName = $ay->name ?? ($ay->title ?? ($ay->label ?? ''));

            $yearKey = trim($yearName . ' — ' . $ayName);

            // Final mark out of 20
            $marksOver20 = $max > 0 ? round(($total / $max) * 20, 2) : 0.0;
            $percentage  = $max > 0 ? round(($total / $max) * 100, 2) : 0.0;

            $credits     = (int) ($course->credits ?? 0);

            // ✅ new formulas
            $creditMax   = round($credits * $marksOver20, 2); // credits × mark(/20)
            $creditMarks = $creditMax;                        // same as above

            $courseKey = $course->code ?: ('course_' . $module->id);

            $grouped[$yearKey][$courseKey] = [
                'code'         => $course->code,
                'name'         => $course->name,
                'credits'      => $credits,
                'marks'        => $marksOver20,   // /20
                'credit_max'   => $creditMax,     // ✅ credits × mark(/20)
                'credit_marks' => $creditMarks,   // ✅ same value
                'percentage'   => $percentage,    // %
            ];
        }

        foreach ($grouped as $yearKey => $rows) {
            $grouped[$yearKey] = array_values($rows);
        }
        ksort($grouped, SORT_NATURAL);

        return $grouped;
    }
    public function generateDegree(Request $request, $studentId)
    {
        $studentId = decrypt($studentId);
        // Load student with related data, including academic_year
        $student = Student::with([
            'department',
            'degree_level',
            'class_students.class_year.academic_year', // <-- Add this line
            'class_students.class_year.modules.assignments.submissions',
            'class_students.class_year.modules.quizzes.submissions',
            'class_students.class_year.modules.exams.submissions',
            'class_students.class_year.modules.course',
        ])->findOrFail($studentId);

        // Prepare data for PDF
        $transcriptData = [
            'student' => $student,
            'courses' => $this->getStudentCoursesFromSubmissions($student),
        ];

        // Render the view for testing
        // return view('certificates.degree', $transcriptData);

        $pdf = Pdf::loadView('certificates.degree', $transcriptData)
            ->setPaper("a4", "landscape");
        return $pdf->stream($student->reg_number . '_degree.pdf');
    }

}
