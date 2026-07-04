<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|min:4|',
            'code' => 'required|string|min:2|unique:courses,code',
            'description' => 'nullable',
            'department_id' => 'required',
            'credits' => 'required|numeric|min:0',
            'status' => 'required',
        ]);
        $request->merge(['slug' => Str::slug($request->name)]);

        try {
            Course::create($request->all());
            return back()->with('message', 'Courses added successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($department)
    {
        $courses = Course::where('department_id', $department)->orderByDesc('id')->get();
        return view('settings.courses', compact('courses', 'department'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course)
    {
        $request->validate([
            'name' => 'required|string|min:4|',
            'code' => 'required|string|min:2|unique:courses,code,' . $course->id,
            'description' => 'nullable',
            'department_id' => 'required',
            'credits' => 'required|numeric|min:0',
            'status' => 'required',
        ]);
        $request->merge(['slug' => Str::slug($request->name)]);


        try {
            $course->update($request->all());
            return back()->with('message', 'Courses updated successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        try {
            $course->delete();
            return back()->with('message', 'Courses deleted successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
