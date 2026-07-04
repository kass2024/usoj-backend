<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\ClassYear;
use App\Models\Department;
use Illuminate\Http\Request;

class ProgramsApiController extends Controller
{
    public function getSchools($programId)
    {
        return School::where('program_id', $programId)->where('status', 'active')->select('id', 'name')->get();
    }
    public function getDepartments($schoolId)
    {
        return Department::where('school_id', $schoolId)->where('status', 'active')->select('id', 'name')->get();
    }
    public function getLevels($departmentId)
    {
        return ClassYear::with('degree_level')->where('department_id', $departmentId)->groupBy('degree_level_id')->get();
    }
}
