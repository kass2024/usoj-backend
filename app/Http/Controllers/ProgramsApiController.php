<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\ClassYear;
use App\Models\Department;
use App\Services\WebsiteCatalogueService;
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

    public function getWebsiteProgrammes(Request $request, WebsiteCatalogueService $catalogue)
    {
        $category = $request->query('category');

        $allowed = ['undergraduate', 'diploma', 'short_course'];
        if (!$category || !in_array($category, $allowed, true)) {
            return response()->json([
                'message' => 'A valid category is required: undergraduate, diploma, or short_course.',
                'data' => [],
            ], 422);
        }

        $programmes = $catalogue->getByCategory($category);

        return response()
            ->json([
                'data' => $programmes,
                'meta' => [
                    'total' => $programmes->count(),
                    'category' => $category,
                    'source' => 'programs_schools_departments_degree_levels',
                ],
            ])
            ->header('Cache-Control', 'public, max-age=300');
    }
}
