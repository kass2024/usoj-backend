<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\ClassYear;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassesController extends Controller
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'year_name' => [
                'required',
                'string',
                'min:4',
                Rule::unique('class_years')->where(function ($query) use ($request) {
                    return $query->where('department_id', $request->department_id)
                        ->where('degree_level_id', $request->degree_level_id);
                }),
            ],
            'degree_level_id' => 'required|integer',
            'department_id' => 'required|integer',
            'semester' => 'required|numeric|min:1|max:5',
        ]);

        try {
            ClassYear::create($request->all());
            return back()->with('message', 'Class added successfully');
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
        return view('settings.classes', compact('departments', 'school'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClassYear $class)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClassYear $class)
    {
        $request->validate([
            'year_name' => [
                'required',
                'string',
                'min:4',
                Rule::unique('class_years')->where(function ($query) use ($class) {
                    return $query->where('department_id', $class->department_id)
                        ->where('degree_level_id', $class->degree_level_id)
                        ->where('id', '!=', $class->id);
                }),
            ],
            'semester' => 'required|numeric|min:1|max:5',
        ]);

        try {
            $class->update([
                "year_name" => $request->year_name,
                "semester" => $request->semester
            ]);
            return back()->with('message', 'Class updated successfully');
        } catch (\Throwable $th) {
            // throw $th;
            return back()->with('error', $th->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClassYear $class)
    {
        try {
            $class->delete();
            return back()->with('message', 'Class deleted succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
