<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Lesson;
use App\Models\Modules;
use App\Models\Question;
use App\Models\Submission;
use App\Models\ClassStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentAccountController extends Controller
{
    //
   
    public function quizzes()
    {

        $modules = $this->classModules();
        $quizzes = Quiz::whereIn('module_id', $modules)->get();
        return view('student.quizzes.index', compact('quizzes'));
    }
    // quiz_submission
    public function quiz_submission(Quiz $quiz)
    {
        $questions = $quiz->questions;
        return view('student.quizzes.submission', compact('quiz', 'questions'));
    }
    public function save_quiz_submission(Request $request, Quiz $quiz)
    {
        $processedAnswers = [];
        $totalMarks = 0;

        foreach ($request->input('questions', []) as $questionId => $data) {
            $question = Question::findOrFail($questionId);
            $correctChoices = collect($question->choices)
                ->where('is_correct', true)
                ->pluck('id');

            $submissionData = [
                'question_id' => $questionId,
                'type' => $question->type,
            ];

            // Handle different question types
            switch ($question->type) {
                case 'radio':
                    $submissionData['answer'] = $data['answer'] ?? null;
                    $marksObtained = $correctChoices->contains($data['answer'])
                        ? $question->marks
                        : 0;
                    break;

                case 'checkbox':
                    $selectedAnswers = collect($data['answer'] ?? []);
                    $correctSelectedCount = $selectedAnswers->intersect($correctChoices)->count();
                    $submissionData['answer'] = $data['answer'] ?? [];
                    $marksObtained = ($correctSelectedCount / $correctChoices->count()) * $question->marks;
                    break;

                case 'open':
                    $submissionData['answer'] = $data['answer'] ?? null;
                    $marksObtained = 0; // Manual grading
                    break;

                case 'file':
                    if ($request->hasFile("questions.{$questionId}.file")) {
                        $file = $request->file("questions.{$questionId}.file");
                        $filePath = $file->store('submissions', 'public');
                        $submissionData['file'] = $filePath;
                    }
                    $marksObtained = 0; // Manual grading
                    break;

                default:
                    $marksObtained = 0;
            }

            $submissionData['marks_obtained'] = $marksObtained;
            $processedAnswers[] = $submissionData;
            $totalMarks += $marksObtained;
        }


        $submission = new Submission([
            'student_id' => auth()->guard('student')->id(),
            'quiz_id' => $quiz->id,
            'answers' => $processedAnswers,
            'marks_obtained' => $totalMarks
        ]);

        $submission->save();

        return to_route('student.quizzes')->with('success', 'Quiz submitted successfully');
    }

    public function view_submission(Submission $submission)
    {
        $totalQuizMarks = $submission->quiz->questions->sum('marks');

        return view('student.quizzes.submission_view', [
            'submission' => $submission,
            'totalQuizMarks' => $totalQuizMarks
        ]);
    }

    public function assignments()
    {
        return view('student.assignments');
    }
    public function exams()
    {
        return view('student.exams');
    }
    protected function studentClass()
    {
        $class = ClassStudent::where(
            'student_id',
            Auth::guard('student')->user()->id,
        )->latest()->first()->class_year_id;
        return $class;
    }
    // class modules
    protected function classModules()
    {
        $class = $this->studentClass();
        $modules = Modules::where('class_year_id', $class)->pluck('id')->toArray();
        return $modules;
    }
}
