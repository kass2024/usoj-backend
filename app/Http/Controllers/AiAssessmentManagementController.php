<?php

namespace App\Http\Controllers;

use App\Models\AiTranscriptRun;
use App\Models\Student;
use App\Models\Submission;
use App\Support\AiAssessmentCatalog;
use App\Support\AiAssessmentResults;
use App\Support\CertificateGrades;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiAssessmentManagementController extends Controller
{
    public function assessments(Request $request)
    {
        $type = $request->input('type');
        $search = $request->input('q');
        $assessments = AiAssessmentCatalog::allAssessments($type ?: null, $search ?: null);
        $counts = AiAssessmentCatalog::counts();

        return view('ai-transcript-studio.assessments.index', compact(
            'assessments',
            'counts',
            'type',
            'search'
        ));
    }

    public function showAssessment(Request $request, string $type, int $id)
    {
        $assessment = AiAssessmentCatalog::findAssessment($type, $id);

        if (! $assessment) {
            return redirect()
                ->route('ai-transcript-studio.assessments.index')
                ->with('error', 'AI assessment not found.');
        }

        /** @var \App\Models\Assignment|\App\Models\Quiz|\App\Models\Exam $model */
        $model = $assessment['model'];
        $submissions = $model->submissions->load('student');
        $studentId = $request->integer('student_id') ?: null;
        $selectedSubmission = $studentId
            ? $submissions->firstWhere('student_id', $studentId)
            : $submissions->first();

        return view('ai-transcript-studio.assessments.show', compact(
            'assessment',
            'model',
            'submissions',
            'selectedSubmission',
            'studentId'
        ));
    }

    public function marking(Request $request)
    {
        $search = $request->input('q');
        $students = AiAssessmentCatalog::studentsWithAiMarks($search ?: null);
        $counts = AiAssessmentCatalog::counts();

        return view('ai-transcript-studio.marking.index', compact('students', 'counts', 'search'));
    }

    public function showStudentMarking(Student $student)
    {
        $student->load(['department', 'degree_level']);

        $run = AiTranscriptRun::query()
            ->where('student_id', $student->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        $assessmentResults = $run
            ? AiAssessmentResults::forRun($student, $run)
            : [];

        if ($assessmentResults === [] && AiTranscriptRun::studentHasCompletedFill($student->id)) {
            $run = AiTranscriptRun::latestCompletedForStudent($student->id);
            $assessmentResults = $run
                ? AiAssessmentResults::forRun($student, $run)
                : [];
        }

        return view('ai-transcript-studio.marking.show', compact(
            'student',
            'run',
            'assessmentResults'
        ));
    }

    public function runs()
    {
        $runs = AiTranscriptRun::with(['student', 'triggeredBy'])
            ->latest()
            ->paginate(20);

        return view('ai-transcript-studio.runs.index', compact('runs'));
    }

    public function updateSubmissionMark(Request $request, Submission $submission)
    {
        $data = $request->validate([
            'marks_obtained' => 'required|integer|min:0|max:100',
            'assessment_type' => ['required', Rule::in(['assignment', 'quiz', 'exam'])],
            'assessment_id' => 'required|integer',
        ]);

        $max = $data['assessment_type'] === 'exam' ? 40 : 30;

        if ($data['marks_obtained'] > $max) {
            return back()->with('error', "Marks cannot exceed {$max} for this assessment type.");
        }

        $submission->load(['assignment', 'quiz', 'exam']);

        $ownsAiAssessment = match ($data['assessment_type']) {
            'assignment' => $submission->assignment_id === (int) $data['assessment_id']
                && $submission->assignment?->title
                && str_starts_with($submission->assignment->title, AiAssessmentCatalog::ASSIGNMENT_PREFIX),
            'quiz' => $submission->quiz_id === (int) $data['assessment_id']
                && $submission->quiz?->title
                && str_starts_with($submission->quiz->title, AiAssessmentCatalog::QUIZ_PREFIX),
            'exam' => $submission->exam_id === (int) $data['assessment_id']
                && $submission->exam?->title
                && str_starts_with($submission->exam->title, AiAssessmentCatalog::EXAM_PREFIX),
            default => false,
        };

        if (! $ownsAiAssessment) {
            return back()->with('error', 'This submission is not linked to the selected AI assessment.');
        }

        $submission->update(['marks_obtained' => $data['marks_obtained']]);

        $pct = round(($data['marks_obtained'] / $max) * 100, 1);
        $grades = CertificateGrades::fromPercentage($pct);

        return back()->with(
            'success',
            "Marks updated to {$data['marks_obtained']}/{$max} (GP {$grades['gp']}, {$grades['gd']}). Re-generate transcript PDF to reflect course totals."
        );
    }
}
