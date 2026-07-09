<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Program;
use App\Models\School;
use App\Support\CourseDegreeLevelResolver;
use App\Support\ProgramDuration;
use Illuminate\Http\Request;

class CoursesSchoolViewController extends Controller
{
    public function index()
    {
        $programs = Program::active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('courses.index', compact('programs'));
    }

    public function schoolsByProgram(Program $program)
    {
        return response()->json(
            $program->schools()
                ->active()
                ->whereHas('departments.courses', function ($q) {
                    $q->where('status', 'active');
                })
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    public function departmentsBySchool(School $school)
    {
        return response()->json(
            $school->departments()
                ->active()
                ->withCourses(true)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    public function levelsByDepartment(Department $department)
    {
        return response()->json(
            CourseDegreeLevelResolver::levelsForDepartment($department)->map(fn ($level) => [
                'id' => $level->id,
                'name' => $level->name,
                'program_years' => ProgramDuration::yearsForDegreeLevel($level),
                'semesters_per_year' => ProgramDuration::SEMESTERS_PER_YEAR,
                'semester_slots' => ProgramDuration::semesterSlotsForLevel($level),
                'structure_label' => ProgramDuration::structureLabel($level),
            ])->values()
        );
    }

    public function coursesByDepartment(Department $department, Request $request)
    {
        $request->validate([
            'degree_level' => 'nullable|exists:degree_levels,id',
        ]);

        $query = $department->courses()
            ->with('degreeLevel:id,name')
            ->orderBy('code');

        if ($request->filled('degree_level')) {
            $query->where('degree_level_id', (int) $request->degree_level);
        }

        return response()->json(
            $query->get(['id', 'code', 'name', 'credits', 'status', 'description', 'degree_level_id', 'year_index', 'semester'])
                ->map(fn ($course) => [
                    'id' => $course->id,
                    'code' => $course->code,
                    'name' => $course->name,
                    'credits' => $course->credits,
                    'status' => $course->status,
                    'description' => $course->description,
                    'degree_level_id' => $course->degree_level_id,
                    'year_index' => $course->year_index,
                    'semester' => $course->semester,
                    'level' => optional($course->degreeLevel)->name,
                    'year_sem' => $course->year_index
                        ? 'Y'.$course->year_index.' S'.($course->semester ?? '—')
                        : '—',
                ])
        );
    }
}
