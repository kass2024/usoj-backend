<?php

namespace App\Http\Controllers;

use App\Services\WebsiteCatalogueService;
use Illuminate\Http\Request;

class WebsiteProgrammeApiController extends Controller
{
    public function __construct(private WebsiteCatalogueService $catalogue)
    {
    }

    public function index(Request $request)
    {
        $category = $request->query('category');

        $allowed = ['undergraduate', 'diploma', 'short_course'];
        if (!$category || !in_array($category, $allowed, true)) {
            return response()->json([
                'message' => 'A valid category is required: undergraduate, diploma, or short_course.',
            ], 422);
        }

        $programmes = $this->catalogue->getByCategory($category);

        return response()->json([
            'data' => $programmes,
            'meta' => [
                'total' => $programmes->count(),
                'category' => $category,
                'source' => 'programs_schools_departments_degree_levels',
            ],
        ]);
    }
}
