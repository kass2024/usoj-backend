<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Program;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get all schools
        $schools = School::orderByDesc('id')->get();
        $programs = Program::where('status', 'active')->get();

        return view('settings.schools', compact('schools', 'programs'));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $programs = Program::where('status', 'active')->get();
        return view('settings.schools.create', compact('programs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|min:4|unique:schools,name',
            'description' => 'nullable',
            'program_id' => 'required',
            'status' => 'required',
        ]);
        $request->merge(['slug' => Str::slug($request->name)]);

        try {
            School::create($request->all());
            return back()->with('message', 'School added successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(School $school)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(School $school)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, School $school)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|min:4|unique:schools,name,' . $school->id,
            'description' => 'nullable',
            'program_id' => 'required',
            'status' => 'required',
        ]);
        $request->merge(['slug' => Str::slug($request->name)]);
        try {
            $school->update($request->all());
            return back()->with('message', 'School updated successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(School $school)
    {
        // Delete the school
        try {
            $school->delete();
            return back()->with('message', 'School deleted successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
