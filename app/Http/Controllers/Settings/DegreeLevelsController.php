<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\DegreeLevel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class DegreeLevelsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $degree_levels = DegreeLevel::all();
        $programs = Program::where('status', 'active')->get();
        return view('settings.degree-levels', compact('degree_levels', 'programs'));
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
            'name' => 'required|string|min:4|unique:degree_levels,name',
            'description' => 'nullable',
            'program_id' => 'required',
            'status' => 'required',
        ]);
        // Add a slug to the request
        $request->merge(['slug' => Str::slug($request->name)]);

        // Create a new degree level
        try {
            DegreeLevel::create($request->all());
            return back()->with('message', 'Degree level added successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DegreeLevel $degree_level)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DegreeLevel $degree_level)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DegreeLevel $degree_level)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|min:4|unique:degree_levels,name,' . $degree_level->id,
            'description' => 'nullable',
            'program_id' => 'required',
            'status' => 'required',
        ]);
        // Add a slug to the request
        $request->merge(['slug' => Str::slug($request->name)]);
        // Update the degree level
        try {
            $degree_level->update($request->all());
            return back()->with('message', 'Degree level updated successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DegreeLevel $degree_level)
    {
        // Delete the degree level
        try {
            $degree_level->delete();
            return back()->with('message', 'Degree level deleted successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }

    }
    // category
    public function category($program_id)
    {
        $degree_levels = DegreeLevel::where('program_id', $program_id)->where('status', 'active')->get();
        return response()->json($degree_levels);

    }
}
