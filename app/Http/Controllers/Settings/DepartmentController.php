<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|min:4|unique:departments,name',
            'abbr' => 'required|string|min:2|max:3|uppercase|unique:departments,abbr',
            'description' => 'nullable',
            'school_id' => 'required',
            'status' => 'required',
            'duration' => 'nullable|string|max:50',
            'mode' => 'nullable|string|max:100',
            'website_category' => 'nullable|in:undergraduate,diploma,short_course',
        ]);
        $request->merge(['slug' => Str::slug($request->name)]);

        try {
            Department::create($request->all());
            return back()->with('message', 'Department added successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($school)
    {
        $departments = Department::where('school_id', $school)->orderByDesc('id')->get();
        return view('settings.departments', compact('departments', 'school'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|min:4|unique:departments,name,' . $department->id,
            'abbr' => 'required|string|min:2|max:3|uppercase|unique:departments,abbr,' . $department->id,
            'description' => 'nullable',
            'school_id' => 'required',
            'status' => 'required',
            'duration' => 'nullable|string|max:50',
            'mode' => 'nullable|string|max:100',
            'website_category' => 'nullable|in:undergraduate,diploma,short_course',
        ]);
        $request->merge(['slug' => Str::slug($request->name)]);
        try {
            $department->update($request->all());
            return back()->with('message', 'Department updated successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        // Delete the department
        try {
            $department->delete();
            return back()->with('message', 'Department deleted successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
