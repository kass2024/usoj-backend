<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $programs = Program::all();
        return view('settings.programs', compact('programs'));
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
            'name' => 'required|string|min:4|unique:programs,name',
            'description' => 'nullable',
            'status' => 'required',
        ]);
        $request->merge(['slug' => Str::slug($request->name)]);
        try {
            Program::create($request->all());
            return back()->with('message', 'Program added succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Program $program)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Program $program)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Program $program)
    {
        // validate the request
        $request->validate([
            'name' => "required|string|min:4|unique:programs,name, {$program->id}",
            'description' => 'nullable',
            'status' => 'required',
        ]);
        $request->merge(['slug' => Str::slug($request->name)]);
        try {
            $program->update($request->all());
            return back()->with('message', 'Program updated succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Program $program)
    {
        try {
            $program->delete();
            return back()->with('message', 'Program deleted succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
