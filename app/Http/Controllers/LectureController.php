<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\Modules;
use App\Models\ClassStudent;
use App\Models\Exam;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class LectureController extends Controller
{
    //
    public function index($class)
    {
        $modules = Modules::where('user_id', Auth::user()->id)->where('class_year_id', $class)->get();
        $students = ClassStudent::where('class_year_id', $class)->get();
        return view('lecture.index', compact('modules', 'students'));
    }
    public function module($id)
    {
        $module = Modules::findOrFail($id);
        $lessons = Lesson::where('module_id', $id)->get();
        return view('lecture.module', compact('module', 'lessons'));
    }
    public function exams($id)
    {
        $module = Modules::findOrFail($id);
        $exams = Exam::where('module_id', $id)->orderByDesc('id')->get();
        return view('lecture.exams.index', compact('module', 'exams'));
    }
    public function assignments($id)
    {
        $module = Modules::findOrFail($id);
        $assignments = Assignment::where('module_id', $id)->orderByDesc('id')->get();
        return view('lecture.assignments.index', compact('module', 'assignments'));
    }
    public function quizzes($id)
    {
        $module = Modules::findOrFail($id);
        $quizzes = Quiz::where('module_id', $id)->orderByDesc('id')->get();
        return view('lecture.quizzes.index', compact('module', 'quizzes'));
    }
    public function store_lesson(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|max:255',
            'document' => 'required|mimes:pdf,doc,docx,ppt,pptx|max:5000',
        ], [
            'document.required' => 'The document field is required.',
            'document.mimes' => 'The document must be a file of type: pdf, doc, docx, ppt, pptx.',
            'document.max' => 'The document must not be greater than 5 MB.',
        ]);

        if ($request->hasFile('document')) {
            $document_url = $request->file('document')->store('module_documents', 'public');
        }
        try {
            Lesson::create([
                'title' => $request->title,
                'document' => $document_url,
                'module_id' => $id
            ]);
            return back()->with('success', 'Lesson added successfull');
        } catch (\Throwable $th) {
            return back()->with('error', 'Something wrong ask for support');
        }
    }

    public function update_lesson(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|max:255',
            'document' => 'nullable|mimes:pdf,doc,docx,ppt,pptx|max:5000',
        ], [
            'document.mimes' => 'The document must be a file of type: pdf, doc, docx, ppt, pptx.',
            'document.max' => 'The document must not be greater than 5 MB.',
        ]);

        $lesson = Lesson::findOrFail($id);

        try {
            if ($request->hasFile('document')) {
                if ($lesson->document && Storage::disk('public')->exists($lesson->document)) {
                    Storage::disk('public')->delete($lesson->document);
                }
                $document_url = $request->file('document')->store('module_documents', 'public');
                $lesson->document = $document_url;
            }

            $lesson->title = $request->title;
            $lesson->save();

            return back()->with('success', 'Lesson updated successfully');
        } catch (\Throwable $th) {
            return back()->with('error', 'Something went wrong, please ask for support');
        }
    }

    public function delete_lesson($id)
    {
        try {
            $lesson = Lesson::findOrFail($id);

            if ($lesson->document && Storage::disk('public')->exists($lesson->document)) {
                Storage::disk('public')->delete($lesson->document);
            }
            $lesson->delete();
            return back()->with('success', 'Lesson deleted successfully');
        } catch (\Throwable $th) {
            return back()->with('error', 'Something went wrong, please ask for support');
        }
    }

}
