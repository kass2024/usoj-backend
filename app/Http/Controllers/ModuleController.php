<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\Modules;
use App\Models\ClassYear;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    public function index($slug, $year)
    {
        $modules_in_class = Modules::where('class_year_id', $year)->get();
        $modules_in_class_ids = Modules::where('academic_year_id', AcademicYear::latest()->first()->id)->pluck('course_id')->toArray();
        $lectures = User::where('status', 'active')->where('role', 'lecture')->get();
        $courses = Course::where('department_id', Auth::user()->department_id)
            ->whereNotIn('id', $modules_in_class_ids)
            ->get();
        $class_year = ClassYear::findOrFail($year);

        return view('hod.modules', compact('courses', 'modules_in_class', 'lectures', 'class_year'));
    }
    public function store(Request $request, $year)
    {
        $request->validate(['user_id' => 'required', 'course_id' => 'required', 'semester' => 'required']);

        try {
            Modules::create([
                'academic_year_id' => AcademicYear::latest()->first()->id,
                'class_year_id' => $year,
                'user_id' => $request->user_id,
                'course_id' => $request->course_id,
                'semester' => $request->semester,
            ]);
            return back()->with('message', 'Student added Succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
    public function update(Request $request, $id)
    {
        $request->validate(['user_id' => 'required', 'course_id' => 'required', 'semester' => 'required']);

        try {
            Modules::findOrFail($id)->update([
                'user_id' => $request->user_id,
                'course_id' => $request->course_id,
                'semester' => $request->semester,
            ]);
            return back()->with('message', 'Student updated Succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
    public function destroy($id)
    {
        try {
            Modules::findOrFail($id)->delete();
            return back()->with('message', 'Course delete Succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
