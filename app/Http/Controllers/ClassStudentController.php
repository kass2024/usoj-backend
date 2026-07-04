<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\ClassStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassStudentController extends Controller
{
    //
    public function index($slug, $year)
    {
        $students_in_class = ClassStudent::where('class_year_id', $year)->get();
        $students_in_class_ids = ClassStudent::where('academic_year_id', AcademicYear::latest()->first()->id)->pluck('student_id')->toArray();

        // Get students in the same department but not in the class
        $students = Student::where('department_id', Auth::user()->department_id)
            ->whereNotIn('id', $students_in_class_ids)
            ->get();
        return view('hod.students', compact('students', 'students_in_class', 'year'));
    }
    public function store(Request $request, $year)
    {
        $request->validate(['students' => 'required|array']);

        try {
            foreach ($request->students as $studentId) {
                ClassStudent::create([
                    'academic_year_id' => AcademicYear::latest()->first()->id,
                    'student_id' => $studentId,
                    'class_year_id' => $year,
                ]);
            }
            return back()->with('message', 'Student add Succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
    public function destroy($id)
    {
        try {
            ClassStudent::findOrFail($id)->delete();
            return back()->with('message', 'Student delete Succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
