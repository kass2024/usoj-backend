<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Course;
use App\Models\Department;
use App\Models\Modules;
use App\Models\Lesson;
use App\Models\Assignment;
use App\Models\Exam;
use App\Models\Quiz;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Admin dashboard
     */
    public function index(Request $request)
    {
        // ================= KPIs =================
        $totalStudents     = Student::count();
        $totalCourses      = Course::count();
        $totalDepartments  = Department::count();
        $totalModules      = Modules::count();
        $totalLessons      = Lesson::count();
        $totalAssignments  = Assignment::count();
        $totalExams        = Exam::count();
        $totalQuizzes      = Quiz::count();

        // ================= Enrollment by month (current year, fill missing months) =================
        $now  = Carbon::now();
        $year = $now->year;

        $raw = Student::selectRaw('MONTH(created_at) as m, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupBy('m')
            ->orderBy('m')
            ->get()
            ->keyBy('m');

        $months = [];
        $enrollments = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::createFromDate($year, $i, 1)->format('M');
            $enrollments[] = isset($raw[$i]) ? (int) $raw[$i]->total : 0;
        }

        // ================= Courses by department (labels = department names) =================
        $courseDistribution = Course::select('departments.name as department', DB::raw('COUNT(courses.id) as total'))
            ->join('departments', 'departments.id', '=', 'courses.department_id')
            ->groupBy('departments.name')
            ->orderBy('departments.name')
            ->get();

        $deptLabels = $courseDistribution->pluck('department')->values();
        $deptTotals = $courseDistribution->pluck('total')->map(fn ($v) => (int) $v)->values();

        // ================= Students per department (what powers the "Students Enrolled by Department" chart) =================
        // Left join so departments with 0 students appear as 0 in the chart.
        $studentDistribution = Department::leftJoin('students', 'students.department_id', '=', 'departments.id')
            ->select('departments.name as department', DB::raw('COUNT(students.id) as total'))
            ->groupBy('departments.name')
            ->orderByDesc('total')
            ->get();

        $deptStudentLabels = $studentDistribution->pluck('department')->values();
        $deptStudentTotals = $studentDistribution->pluck('total')->map(fn ($v) => (int) $v)->values();

        // ================= Students list (for table) =================
        $progressFilter = $request->integer('progress', 0);

        $studentsQuery = Student::with('department')->latest();
        if (in_array($progressFilter, [50, 70, 90], true)) {
            // Adjust field name if your progress is stored elsewhere
            $studentsQuery->where('progress', '>=', $progressFilter);
        }
        $students = $studentsQuery->get();

        // ================= Recent entries (optional; safe to keep) =================
        $recentStudents    = Student::with('department')->latest()->limit(8)->get();
        $recentAssignments = Assignment::with('course')->latest()->limit(8)->get();

        // ================= Exams & Quizzes tracking =================
        $examsUpcoming = Exam::with('course')
            ->where('start_date', '>', $now)
            ->orderBy('start_date')
            ->limit(10)
            ->get();

        $examsActive = Exam::with('course')
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->orderBy('start_date')
            ->get();

        $examsPast = Exam::with('course')
            ->where('end_date', '<', $now)
            ->orderByDesc('end_date')
            ->limit(10)
            ->get();

        $quizzesUpcoming = Quiz::with('course')
            ->where('start_date', '>', $now)
            ->orderBy('start_date')
            ->limit(10)
            ->get();

        $quizzesActive = Quiz::with('course')
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->orderBy('start_date')
            ->get();

        $quizzesPast = Quiz::with('course')
            ->where('end_date', '<', $now)
            ->orderByDesc('end_date')
            ->limit(10)
            ->get();

        $examKpis = [
            'upcoming' => $examsUpcoming->count(),
            'active'   => $examsActive->count(),
            'past'     => $examsPast->count(),
        ];

        $quizKpis = [
            'upcoming' => $quizzesUpcoming->count(),
            'active'   => $quizzesActive->count(),
            'past'     => $quizzesPast->count(),
        ];

        // ================= Distinct years for the Year dropdown (table filters) =================
        $distinctYears = Student::selectRaw('YEAR(created_at) as y')
            ->whereNotNull('created_at')
            ->groupBy('y')
            ->orderBy('y', 'desc')
            ->pluck('y');

        // ================= Return view with all data =================
        return view('admin.index', [
            // KPIs
            'totalStudents'    => $totalStudents,
            'totalCourses'     => $totalCourses,
            'totalDepartments' => $totalDepartments,
            'totalModules'     => $totalModules,
            'totalLessons'     => $totalLessons,
            'totalAssignments' => $totalAssignments,
            'totalExams'       => $totalExams,
            'totalQuizzes'     => $totalQuizzes,

            // Enrollment trend
            'months'      => $months,
            'enrollments' => $enrollments,

            // Courses by department
            'deptLabels'  => $deptLabels,
            'deptTotals'  => $deptTotals,

            // Students by department (for the requested chart)
            'deptStudentLabels' => $deptStudentLabels,
            'deptStudentTotals' => $deptStudentTotals,

            // Students table + optional progress filter
            'students'        => $students,
            'progressFilter'  => $progressFilter,

            // Recent entries
            'recentStudents'    => $recentStudents,
            'recentAssignments' => $recentAssignments,

            // Exams + Quizzes
            'examsUpcoming'  => $examsUpcoming,
            'examsActive'    => $examsActive,
            'examsPast'      => $examsPast,
            'quizzesUpcoming'=> $quizzesUpcoming,
            'quizzesActive'  => $quizzesActive,
            'quizzesPast'    => $quizzesPast,
            'examKpis'       => $examKpis,
            'quizKpis'       => $quizKpis,

            // Filters + now
            'distinctYears' => $distinctYears,
            'nowIso'        => $now->toIso8601String(),
        ]);
    }
}
