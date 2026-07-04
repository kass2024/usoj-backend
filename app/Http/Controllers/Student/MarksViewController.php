<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Submission;

class MarksViewController extends Controller
{
   public function index(Request $request)
    {
        $studentId = auth()->guard('student')->id();

        $submissions = Submission::with([
                // Exams
                'exam.module.course', 'exam.questions',
                // Quizzes
                'quiz.module.course', 'quiz.questions',
                // Assignments
                'assignment.module.course', 'assignment.questions',
            ])
            ->where('student_id', $studentId)
            ->latest()
            ->get();

        return view('student.marks.index', compact('submissions'));
    }

    public function show(Submission $submission)
    {
        // Optional: ensure the submission belongs to the authenticated student
        abort_unless($submission->student_id === auth()->guard('student')->id(), 403);

        $submission->load([
            'exam.module.course', 'exam.questions',
            'quiz.module.course', 'quiz.questions',
            'assignment.module.course', 'assignment.questions',
        ]);

        // Derive context for a detail view (if you build one)
        $type = $submission->exam ? 'Exam' : ($submission->quiz ? 'Quiz' : ($submission->assignment ? 'Assignment' : 'Submission'));
        $title = $submission->exam->title
            ?? $submission->quiz->title
            ?? $submission->assignment->title
            ?? '—';

        // Compute total marks like your Blade does
        $totalMarks =
              optional($submission->exam?->questions)->sum('marks')
            ?? $submission->exam->total_marks
            ?? optional($submission->quiz?->questions)->sum('marks')
            ?? optional($submission->assignment?->questions)->sum('marks')
            ?? 0;

        return view('student.marks.show', [
            'submission'  => $submission,
            'type'        => $type,
            'title'       => $title,
            'totalMarks'  => $totalMarks,
        ]);
    }
}
