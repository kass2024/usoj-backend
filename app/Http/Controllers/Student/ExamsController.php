<?php

namespace App\Http\Controllers\Student;

use App\Models\Question;
use App\Models\Submission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Traits\StudentTraits;

class ExamsController extends Controller
{
    use StudentTraits;
    public function index()
    {

        $modules = $this->classModules();
        $exams = Exam::whereIn('module_id', $modules)->get();
        return view('student.exams.index', compact('exams'));
    }
    public function submission(Exam $exam)
    {
        $questions = $exam->questions;
        return view('student.exams.submission', compact('exam', 'questions'));
    }

    public function save_submission(Request $request, Exam $exam)
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
            'exam_id' => $exam->id,
            'answers' => $processedAnswers,
            'marks_obtained' => $totalMarks
        ]);

        $submission->save();

        return to_route('student.exams.index')->with('success', 'Exam submitted successfully');
    }

    public function view_submission(Submission $submission)
    {
        $totalExamMarks = $submission->exam->questions->sum('marks');

        return view('student.exams.submission_view', [
            'submission' => $submission,
            'totalExamMarks' => $totalExamMarks
        ]);
    }
}
