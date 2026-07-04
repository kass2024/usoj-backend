<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\School;
use App\Models\Department;

class CoursesSchoolViewController extends Controller
{
    public function index()
    {
        $programs = Program::active()
            ->orderBy('name')
            ->get(['id','name']);

        return view('courses.index', compact('programs'));
    }

    public function schoolsByProgram(Program $program)
    {
        // Only schools that have at least one department WITH courses
        return response()->json(
            $program->schools()
                ->active()
                ->whereHas('departments.courses', function ($q) {
                    $q->where('status','active');
                })
                ->orderBy('name')
                ->get(['id','name'])
        );
    }

    public function departmentsBySchool(School $school)
    {
        // Only departments that actually have courses
        return response()->json(
            $school->departments()
                ->active()
                ->withCourses(true) // active courses only
                ->orderBy('name')
                ->get(['id','name'])
        );
    }

    public function coursesByDepartment(Department $department)
    {
        // Return the department’s active courses (change if you want all)
        return response()->json(
            $department->courses()
                ->active()
                ->orderBy('code')
                ->get(['id','code','name','credits','status','description'])
        );
    }
}
