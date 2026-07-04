<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ClassYear;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
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
    $request->validate([
        'fname' => 'required|string|min:3',
        'lname' => 'required|string|min:3',
        'email' => 'required|email|unique:students,email',
        'phone' => 'required|unique:students,phone',
        'status' => 'required',
        'date_created' => 'required|date',
        'department_id' => 'required',
        'degree_level_id' => 'required',
    ]);

    // ✅ Get the selected department
    $department = Department::findOrFail($request->department_id);

    // ✅ Extract year from date_created
    $year = Carbon::parse($request->date_created)->format('y'); // two-digit year

    // ✅ Generate registration number using year from date_created
    $regNumber = $year . $department->abbr . str_pad(
        Student::where('department_id', $request->department_id)->max('id') + 1,
        3,
        '0',
        STR_PAD_LEFT
    );

    // ✅ Generate random password and hash it
    $rawPassword = Str::random(8);
    $hashedPassword = bcrypt($rawPassword);

    // ✅ Prepare student data
    $studentData = $request->merge([
        'password' => $hashedPassword,
        'reg_number' => $regNumber
    ])->except(['_token']);

    try {
        // ✅ Create student
        $student = Student::create($studentData);

        // ✅ Generate Admission Letter PDF
        $pdf = PDF::loadView('emails.admission-letter', [
            'student' => $student,
            'department' => $department,
            'regNumber' => $regNumber
        ]);

        // ✅ Send Welcome Email with Admission Letter
        Mail::send('emails.welcome', ['student' => $student, 'password' => $rawPassword], function ($message) use ($student, $pdf) {
            $message->to($student->email)
                ->subject('Welcome to University of Saint Joseph Mbarara')
                ->attachData($pdf->output(), 'admission_letter.pdf');
        });

        return back()->with('message', 'Student added Successfully. Welcome email sent.');
    } catch (\Throwable $th) {
        return back()->with('error', $th->getMessage());
    }
}

    /**
     * Display the specified resource.
     */
    public function show($department)
    {
        $students = Student::where('department_id', $department)->where('degree_level_id', request('level'))->orderByDesc('id')->get();
        $levels = ClassYear::where('department_id', $department)->groupBy('degree_level_id')->get();
        return view('admin.students', compact('students', 'department', 'levels'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'fname' => 'required|string|min:3',
            'lname' => 'required|string|min:3',
            'email' => 'required|email|unique:students,email,' . $student->id,
            'phone' => 'required|unique:students,phone,' . $student->id,
            'status' => 'required',
        ]);

        try {
            $student->update($request->all());
            return back()->with('message', 'Student update Succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        try {
            $student->delete();
            return back()->with('message', 'Student deleted Succesfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
