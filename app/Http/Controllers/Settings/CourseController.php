<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Services\CourseForceDeleteService;
use App\Services\CourseTextImportService;
use App\Support\CourseDegreeLevelResolver;
use App\Support\DestructiveActionConfirmation;
use App\Support\ProgramDuration;
use App\Support\Utf8Sanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $this->validateCourse($request);

        try {
            Course::create($validated);

            return back()->with('message', 'Courses added successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function show($department)
    {
        $courses = Course::where('department_id', $department)->orderByDesc('id')->get();

        return view('settings.courses', compact('courses', 'department'));
    }

    public function edit(Course $course)
    {
        //
    }

    public function update(Request $request, Course $course)
    {
        $validated = $this->validateCourse($request, $course);

        try {
            $course->update($validated);

            return back()->with('message', 'Courses updated successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(Course $course, CourseForceDeleteService $forceDelete)
    {
        try {
            $label = $course->code ?: $course->name;
            $counts = $forceDelete->delete($course);

            return back()->with('message', $forceDelete->summaryMessage($label, $counts));
        } catch (\Throwable $th) {
            return back()->with('error', 'Could not delete course: '.$th->getMessage());
        }
    }

    public function bulkDeleteChallenge(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'degree_level_id' => 'required|exists:degree_levels,id',
        ]);

        $department = Department::query()->findOrFail($data['department_id']);
        $degreeLevel = CourseDegreeLevelResolver::resolveForDepartment(
            $department,
            (int) $data['degree_level_id']
        );

        if (! $degreeLevel) {
            return response()->json(['message' => 'Invalid degree level for this department.'], 422);
        }

        $courseCount = Course::query()
            ->where('department_id', $department->id)
            ->where('degree_level_id', $degreeLevel->id)
            ->count();

        $code = DestructiveActionConfirmation::issue('courses.bulk_delete', [
            'department_id' => $department->id,
            'degree_level_id' => $degreeLevel->id,
        ]);

        return response()->json([
            'code' => $code,
            'course_count' => $courseCount,
            'scope_label' => $department->name.' · '.$degreeLevel->name,
            'expires_in_minutes' => 10,
        ]);
    }

    public function bulkDeleteAll(Request $request, CourseForceDeleteService $forceDelete)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'degree_level_id' => 'required|exists:degree_levels,id',
            'confirmation_code' => 'required|string|size:6',
        ]);

        if (! DestructiveActionConfirmation::validate('courses.bulk_delete', $data['confirmation_code'], [
            'department_id' => $data['department_id'],
            'degree_level_id' => $data['degree_level_id'],
        ])) {
            return back()->with('error', 'Invalid or expired confirmation code. Open the delete dialog again to get a new code.');
        }

        $department = Department::query()->findOrFail($data['department_id']);
        $degreeLevel = CourseDegreeLevelResolver::resolveForDepartment(
            $department,
            (int) $data['degree_level_id']
        );

        if (! $degreeLevel) {
            return back()->with('error', 'Invalid degree level for this department.');
        }

        try {
            $counts = $forceDelete->deleteAllInScope($department->id, $degreeLevel->id);
            $scopeLabel = $department->name.' · '.$degreeLevel->name;

            return back()->with('message', $forceDelete->bulkSummaryMessage($scopeLabel, $counts));
        } catch (\Throwable $th) {
            return back()->with('error', 'Bulk delete failed: '.$th->getMessage());
        }
    }

    public function parseBulkText(Request $request, CourseTextImportService $importService)
    {
        try {
            $data = $request->validate([
                'department_id' => 'required|exists:departments,id',
                'degree_level_id' => 'required|exists:degree_levels,id',
                'course_text' => 'required|string|min:10',
            ]);

            $data['course_text'] = Utf8Sanitizer::clean($data['course_text']);

            $department = Department::query()->findOrFail($data['department_id']);
            $degreeLevel = CourseDegreeLevelResolver::resolveForDepartment($department, (int) $data['degree_level_id']);

            if (! $degreeLevel) {
                return response()->json(['message' => 'Invalid degree level for this department.'], 422);
            }

            $preview = $importService->preview($data['course_text'], $department, $degreeLevel);

            if ($preview === []) {
                return response()->json(['message' => 'No courses detected. Paste one course per line.'], 422);
            }

            return response()->json(Utf8Sanitizer::cleanArray([
                'courses' => $preview,
                'count' => count($preview),
                'program_years' => ProgramDuration::yearsForDegreeLevel($degreeLevel),
                'semesters_per_year' => ProgramDuration::SEMESTERS_PER_YEAR,
                'semester_slots' => ProgramDuration::semesterSlotsForLevel($degreeLevel),
                'structure_label' => ProgramDuration::structureLabel($degreeLevel),
            ]));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Could not analyse courses. Check your text and try again.',
            ], 422);
        }
    }

    public function bulkTextImport(Request $request, CourseTextImportService $importService)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'degree_level_id' => 'required|exists:degree_levels,id',
            'course_text' => 'required|string|min:10',
        ]);

        $result = $importService->import(
            Utf8Sanitizer::clean($data['course_text']),
            (int) $data['department_id'],
            (int) $data['degree_level_id']
        );

        if ($request->expectsJson() || $request->ajax()) {
            $status = ($result->created() === 0 && $result->updated() === 0) ? 422 : 200;

            return response()->json([
                'message' => $result->summaryMessage(),
                'created' => $result->created(),
                'updated' => $result->updated(),
                'errors' => $result->errors(),
            ], $status);
        }

        if ($result->created() === 0 && $result->updated() === 0) {
            return back()
                ->with('error', $result->summaryMessage())
                ->with('import_errors', $result->errors());
        }

        return back()
            ->with('message', $result->summaryMessage())
            ->with('import_errors', $result->errors());
    }

    /** @return array<string, mixed> */
    protected function validateCourse(Request $request, ?Course $course = null): array
    {
        $departmentId = (int) $request->input('department_id');
        $degreeLevelId = (int) $request->input('degree_level_id');

        $department = Department::query()->findOrFail($departmentId);
        $degreeLevel = CourseDegreeLevelResolver::resolveForDepartment($department, $degreeLevelId);

        if (! $degreeLevel) {
            abort(422, 'Selected degree level does not belong to this department\'s program.');
        }

        $programYears = ProgramDuration::yearsForDegreeLevel($degreeLevel);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:4',
                Rule::unique('courses', 'name')
                    ->where(fn ($q) => $q
                        ->where('department_id', $departmentId)
                        ->where('degree_level_id', $degreeLevelId))
                    ->ignore($course?->id),
            ],
            'code' => [
                'required',
                'string',
                'min:2',
                Rule::unique('courses', 'code')
                    ->where(fn ($q) => $q
                        ->where('department_id', $departmentId)
                        ->where('degree_level_id', $degreeLevelId))
                    ->ignore($course?->id),
            ],
            'description' => 'nullable',
            'department_id' => 'required|exists:departments,id',
            'degree_level_id' => 'required|exists:degree_levels,id',
            'credits' => 'required|numeric|min:1|max:12',
            'status' => 'required|in:active,inactive',
            'year_index' => "nullable|integer|min:1|max:{$programYears}",
            'semester' => 'nullable|integer|in:1,2',
        ]);

        $validated['slug'] = $this->uniqueSlug(
            $validated['name'],
            $validated['code'],
            $course?->id
        );

        return $validated;
    }

    protected function uniqueSlug(string $name, string $code, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::slug($code);
        $slug = $base;
        $suffix = 1;

        while (
            Course::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.Str::slug($code).($suffix > 1 ? '-'.$suffix : '');
            $suffix++;
        }

        return $slug;
    }
}
