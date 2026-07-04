<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $academics = AcademicYear::orderByDesc('id')->get();
        return view('settings.academic-years', compact('academics'));
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
            'period' => [
                'required',
                'string',
                'min:9',
                'unique:academic_years,period',
                'regex:/^\d{4}-\d{4}$/',
            ],
        ]);

        try {
            AcademicYear::create([
                'period' => $request->period
            ]);
            return back()->with('message', 'Academic year added succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AcademicYear $academic_year)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AcademicYear $academic_year)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AcademicYear $academic_year)
    {
        $request->validate([
            'period' => [
                'required',
                'string',
                'min:9',
                'unique:academic_years,period,' . $academic_year->id,
                'regex:/^\d{4}-\d{4}$/',
            ],
        ]);

        try {
            $academic_year->update([
                'period' => $request->period
            ]);
            return back()->with('message', 'Academic year updated succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademicYear $academic_year)
    {
        try {
            $academic_year->delete();
            return back()->with('message', 'Academic year deleted succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
