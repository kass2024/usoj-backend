<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Modules;
use App\Models\ClassStudent;
use App\Models\Submission;
use App\Models\Course;
use App\Models\Department;
use App\Models\DegreeLevel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // ─────────────────────────────────────────────────────
        // Current student
        // ─────────────────────────────────────────────────────
        $student        = Auth::guard('student')->user();
        $studentId      = $student?->id;
        $departmentId   = $student?->department_id;
        $degreeLevelId  = $student?->degree_level_id;

        // Names for header
        $departmentName   = $departmentId ? Department::where('id', $departmentId)->value('name') : null;
        $degreeLevelName  = $degreeLevelId ? DegreeLevel::where('id', $degreeLevelId)->value('name') : null;

        // ─────────────────────────────────────────────────────
        // Department scope (courses → modules)
        // ─────────────────────────────────────────────────────
        $deptCourseIds = Course::where('department_id', $departmentId)->pluck('id');
        $deptModuleIds = Modules::whereIn('course_id', $deptCourseIds)->pluck('id');

        // ─────────────────────────────────────────────────────
        // Department totals (distinct assessments inside dept)
        // ─────────────────────────────────────────────────────
        $deptModulesCount = $deptModuleIds->count();
        $deptLessonsCount = Lesson::whereIn('module_id', $deptModuleIds)->count();

        // Distinct assessments (based on submissions linkage to their parent)
        $deptExamsCount = Submission::whereNotNull('exam_id')
            ->whereHas('exam', fn($q) => $q->whereIn('module_id', $deptModuleIds))
            ->distinct('exam_id')->count('exam_id');

        $deptQuizzesCount = Submission::whereNotNull('quiz_id')
            ->whereHas('quiz', fn($q) => $q->whereIn('module_id', $deptModuleIds))
            ->distinct('quiz_id')->count('quiz_id');

        $deptAssignmentsCount = Submission::whereNotNull('assignment_id')
            ->whereHas('assignment', fn($q) => $q->whereIn('module_id', $deptModuleIds))
            ->distinct('assignment_id')->count('assignment_id');

        // ─────────────────────────────────────────────────────
        // Student classes
        // ─────────────────────────────────────────────────────
        $classesCount = ClassStudent::where('student_id', $studentId)->count();

        // ─────────────────────────────────────────────────────
        // Helper: compute total marks for a submission row
        // ─────────────────────────────────────────────────────
        $totalMarksOf = function ($s) {
            return optional(optional($s->exam)->questions)->sum('marks')
                ?? optional($s->exam)->total_marks
                ?? optional(optional($s->quiz)->questions)->sum('marks')
                ?? optional(optional($s->assignment)->questions)->sum('marks')
                ?? 0;
        };

        // ─────────────────────────────────────────────────────
        // RECENT submissions (limit 20) for table
        // ─────────────────────────────────────────────────────
        $submissions = Submission::with([
                'exam.questions', 'exam.module.course',
                'quiz.questions', 'quiz.module.course',
                'assignment.questions', 'assignment.module.course',
            ])
            ->where('student_id', $studentId)
            ->latest()
            ->take(20)
            ->get();

        // ─────────────────────────────────────────────────────
        // OVERALL Average % (ALL dept-scoped submissions)
        // iyateranye byose ubundi iyashyire ku ijana
        // ─────────────────────────────────────────────────────
        $allScoredInDept = Submission::with(['exam.questions','quiz.questions','assignment.questions'])
            ->where('student_id', $studentId)
            ->whereNotNull('marks_obtained')
            ->where(function ($q) use ($deptModuleIds) {
                $q->whereHas('exam', fn($qq) => $qq->whereIn('module_id', $deptModuleIds))
                  ->orWhereHas('quiz', fn($qq) => $qq->whereIn('module_id', $deptModuleIds))
                  ->orWhereHas('assignment', fn($qq) => $qq->whereIn('module_id', $deptModuleIds));
            })
            ->get();

        $avgPercent = null;
        if ($allScoredInDept->isNotEmpty()) {
            $totals = $allScoredInDept->reduce(function ($carry, $s) use ($totalMarksOf) {
                $total = (float) $totalMarksOf($s);
                if ($total > 0) {
                    $carry['got']   += (float) $s->marks_obtained;
                    $carry['total'] += $total;
                }
                return $carry;
            }, ['got' => 0.0, 'total' => 0.0]);

            if ($totals['total'] > 0) {
                $avgPercent = round(($totals['got'] / $totals['total']) * 100, 1);
            }
        }

        // ─────────────────────────────────────────────────────
        // Student's assessment mix (how many submissions by type)
        // ─────────────────────────────────────────────────────
        $mix = [
            'Exams'       => Submission::where('student_id', $studentId)->whereNotNull('exam_id')->count(),
            'Quizzes'     => Submission::where('student_id', $studentId)->whereNotNull('quiz_id')->count(),
            'Assignments' => Submission::where('student_id', $studentId)->whereNotNull('assignment_id')->count(),
        ];

        // ─────────────────────────────────────────────────────
        // PENDING BY TYPE (Not Submitted + Not Graded), dept-scoped
        // ─────────────────────────────────────────────────────
        // Not submitted yet (LEFT JOIN to submissions)
        $pendingToSubmitExams = DB::table('exams')
            ->whereIn('exams.module_id', $deptModuleIds)
            ->leftJoin('submissions', function ($join) use ($studentId) {
                $join->on('submissions.exam_id', '=', 'exams.id')
                     ->where('submissions.student_id', '=', $studentId);
            })
            ->whereNull('submissions.id')
            ->count();

        $pendingToSubmitQuizzes = DB::table('quizzes')
            ->whereIn('quizzes.module_id', $deptModuleIds)
            ->leftJoin('submissions', function ($join) use ($studentId) {
                $join->on('submissions.quiz_id', '=', 'quizzes.id')
                     ->where('submissions.student_id', '=', $studentId);
            })
            ->whereNull('submissions.id')
            ->count();

        $pendingToSubmitAssignments = DB::table('assignments')
            ->whereIn('assignments.module_id', $deptModuleIds)
            ->leftJoin('submissions', function ($join) use ($studentId) {
                $join->on('submissions.assignment_id', '=', 'assignments.id')
                     ->where('submissions.student_id', '=', $studentId);
            })
            ->whereNull('submissions.id')
            ->count();

        // Submitted but not graded yet
        $pendingNotGradedExams = Submission::where('student_id', $studentId)
            ->whereNotNull('exam_id')
            ->whereNull('marks_obtained')
            ->whereHas('exam', fn($q) => $q->whereIn('module_id', $deptModuleIds))
            ->count();

        $pendingNotGradedQuizzes = Submission::where('student_id', $studentId)
            ->whereNotNull('quiz_id')
            ->whereNull('marks_obtained')
            ->whereHas('quiz', fn($q) => $q->whereIn('module_id', $deptModuleIds))
            ->count();

        $pendingNotGradedAssignments = Submission::where('student_id', $studentId)
            ->whereNotNull('assignment_id')
            ->whereNull('marks_obtained')
            ->whereHas('assignment', fn($q) => $q->whereIn('module_id', $deptModuleIds))
            ->count();

        // Final pending per type
        $pendingByType = [
            'Exams'       => $pendingToSubmitExams       + $pendingNotGradedExams,
            'Quizzes'     => $pendingToSubmitQuizzes     + $pendingNotGradedQuizzes,
            'Assignments' => $pendingToSubmitAssignments + $pendingNotGradedAssignments,
        ];

        // ─────────────────────────────────────────────────────
        // Recent table mapping (per-row % out of 100)
        // ─────────────────────────────────────────────────────
        $recent = $submissions->map(function ($s) use ($totalMarksOf) {
            $total = $totalMarksOf($s);
            $pct   = ($s->marks_obtained !== null && $total > 0)
                ? round(($s->marks_obtained / $total) * 100, 1)
                : null;

            $type = $s->exam ? 'Exam' : ($s->quiz ? 'Quiz' : ($s->assignment ? 'Assignment' : 'Submission'));
            $title = optional($s->exam)->title ?? optional($s->quiz)->title ?? optional($s->assignment)->title ?? '—';
            $course = optional(optional($s->exam)->module)->course->name
                ?? optional(optional($s->quiz)->module)->course->name
                ?? optional(optional($s->assignment)->module)->course->name
                ?? null;

            return (object)[
                'id'         => $s->id,
                'type'       => $type,
                'title'      => $title,
                'course'     => $course,
                'score'      => $s->marks_obtained,
                'total'      => $total,
                'pct'        => $pct,
                'created_at' => $s->created_at,
            ];
        });

        // ─────────────────────────────────────────────────────
        // Return view (no monthly, keep empty arrays)
        // ─────────────────────────────────────────────────────
        return view('student.index', [
            // Header labels
            'departmentName'      => $departmentName,
            'degreeLevelName'     => $degreeLevelName,

            // Dept totals
            'deptModulesCount'    => $deptModulesCount,
            'deptLessonsCount'    => $deptLessonsCount,
            'deptExamsCount'      => $deptExamsCount,
            'deptQuizzesCount'    => $deptQuizzesCount,
            'deptAssignmentsCount'=> $deptAssignmentsCount,

            // Student KPIs / charts
            'classesCount'        => $classesCount,
            'avgPercent'          => $avgPercent,   // ← overall weighted (all dept submissions)

            // Charts: keep empty so "Monthly" chart shows nothing
            'monthlyLabels'       => [],
            'monthlyAverages'     => [],

            'mix'                 => $mix,

            // Pending by type (Exams / Quizzes / Assignments)
            'pendingByType'       => $pendingByType,

            // Recent table
            'recent'              => $recent,
        ]);
    }

    public function module($id)
    {
        $module  = Modules::findOrFail($id);
        $lessons = Lesson::where('module_id', $id)->get();
        return view('student.module', compact('module', 'lessons'));
    }
}
