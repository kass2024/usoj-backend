<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Course;
use App\Models\Modules;
use App\Models\Lesson;
use App\Models\Department;
use App\Models\ClassYear; // table: class_years

class CoursesViewController extends Controller
{
    public function index(Request $request)
    {
        $student = Auth::guard('student')->user();
        $departmentId = $student?->department_id;

        // Optional: department name for header chip
        $departmentName = $departmentId ? Department::where('id', $departmentId)->value('name') : null;

        // 1) All class years for this student's department
        $years = ClassYear::query()
            ->where('department_id', $departmentId)
            ->orderBy('year_name')
            ->get(['id', 'year_name', 'semester']);

        // Read filters
        $selectedYearId = (int) $request->query('year_id', 0);
        $selectedSemester = $request->query('semester', '');

        // 2) If a year is selected, compute semester options that actually exist for that year
        $availableSemesters = collect();
        if ($selectedYearId) {
            // Limit to department's courses (status active)
            $courseIds = Course::where('department_id', $departmentId)
                ->where('status', 'active')
                ->pluck('id');

            $availableSemesters = Modules::query()
                ->whereIn('course_id', $courseIds)
                ->where('class_year_id', $selectedYearId)
                ->select('semester')
                ->distinct()
                ->orderBy('semester')
                ->pluck('semester')
                ->filter();
        }

        // 3) Only load modules once BOTH year and semester are chosen
        $filtersReady = $selectedYearId && $selectedSemester !== '' && $selectedSemester !== null;

        $moduleCards = collect();
        if ($filtersReady) {
            // Guard: ensure semester is valid (optional)
            if (!$availableSemesters->contains((int)$selectedSemester)) {
                // semester not valid for that year -> show nothing
                $availableSemesters = $availableSemesters; // unchanged
            } else {
                $courseIds = Course::where('department_id', $departmentId)
                    ->where('status', 'active')
                    ->pluck('id');

                $modules = Modules::query()
                    ->with(['course:id,name,code,department_id', 'classYear:id,year_name'])
                    ->whereIn('course_id', $courseIds)
                    ->where('class_year_id', $selectedYearId)
                    ->where('semester', (int) $selectedSemester)
                    ->withCount(['lessons' => function($q) {
                        $q->select(DB::raw('count(*)'));
                    }])
                    ->orderByDesc('id')
                    ->get(['id','academic_year_id','class_year_id','user_id','course_id','semester','created_at']);

                $moduleCards = $modules->map(function ($m) {
                    return (object)[
                        'id'            => $m->id,
                        'course'        => optional($m->course)->name,
                        'course_code'   => optional($m->course)->code,
                        'year_name'     => optional($m->classYear)->year_name,
                        'semester'      => $m->semester,
                        'lessons_count' => (int) ($m->lessons_count ?? 0),
                        'created_at'    => $m->created_at,
                    ];
                });
            }
        }

        return view('student.courses.index', [
            'departmentName'     => $departmentName,
            'years'              => $years,
            'selectedYearId'     => $selectedYearId,
            'selectedSemester'   => $selectedSemester,
            'availableSemesters' => $availableSemesters,
            'filtersReady'       => $filtersReady,
            'moduleCards'        => $moduleCards,
        ]);
    }
}
