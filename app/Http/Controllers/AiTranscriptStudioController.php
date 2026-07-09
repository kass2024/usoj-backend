<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAiTranscriptRunJob;
use App\Models\AiTranscriptRun;
use App\Models\Student;
use App\Services\GeminiAiService;
use App\Services\TranscriptAiStudioService;
use App\Support\AiAssessmentResults;
use App\Support\CertificateGrades;
use App\Support\TranscriptProfile;
use App\Support\ProgramDuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AiTranscriptStudioController extends Controller
{
    public function index(Request $request, GeminiAiService $gemini, TranscriptAiStudioService $studio)
    {
        $student = null;
        $lastRun = null;
        $estimatedClass = null;
        $estimatedCgpa = null;
        $estimatedPercentage = null;
        $courseCount = 0;
        $programYears = ProgramDuration::BACHELOR_YEARS;
        $semesterSlots = ProgramDuration::semesterSlots($programYears);
        $scheduleSummary = [];
        $targetScale = 'cgpa';
        $targetValue = 4.5;
        $completedAiRun = null;
        $activeAiRun = null;
        $aiFillCompleted = false;

        if ($studentId = $request->session()->get('ai_studio_student_id')) {
            $student = Student::with(['department', 'degree_level'])->find($studentId);
            $lastRun = AiTranscriptRun::where('student_id', $studentId)->latest()->first();
            $completedAiRun = $student
                ? AiTranscriptRun::latestCompletedForStudent($student->id)
                : null;
            $activeAiRun = $student
                ? AiTranscriptRun::activeForStudent($student->id)
                : null;
            $aiFillCompleted = $completedAiRun !== null;

            $targetScale = $request->input(
                'target_scale',
                old('target_scale', $lastRun->options['target_scale'] ?? 'cgpa')
            );

            if ($targetScale === 'percentage') {
                $targetValue = (float) $request->input(
                    'target_value',
                    old('target_value', $lastRun->target_percentage ?? 76)
                );
            } else {
                $targetValue = (float) $request->input(
                    'target_value',
                    old('target_value', $lastRun->target_cgpa ?? 4.5)
                );
            }

            $resolved = $studio->resolveTarget($targetScale, $targetValue);
            $estimatedPercentage = $resolved['percentage'];
            $estimatedCgpa = $resolved['cgpa'];
            $estimatedClass = CertificateGrades::classifyCgpa($estimatedCgpa);

            if ($student) {
                $programYears = $studio->programYearsForStudent($student);
                $semesterSlots = ProgramDuration::semesterSlots($programYears);
                $schedule = $studio->buildProgramSchedule($student);
                $courseCount = $schedule->count();
                $scheduleSummary = $studio->scheduleSummary($student);
            }
        }

        $recentRuns = AiTranscriptRun::with(['student', 'triggeredBy'])
            ->latest()
            ->limit(10)
            ->get();

        return view('ai-transcript-studio.index', compact(
            'student',
            'lastRun',
            'recentRuns',
            'estimatedClass',
            'estimatedCgpa',
            'estimatedPercentage',
            'courseCount',
            'programYears',
            'semesterSlots',
            'scheduleSummary',
            'targetScale',
            'targetValue',
            'gemini',
            'completedAiRun',
            'activeAiRun',
            'aiFillCompleted',
        ));
    }

    public function lookup(Request $request)
    {
        $request->validate(['reg_number' => 'required|string']);

        $student = Student::with(['department', 'degree_level'])
            ->whereRaw('UPPER(reg_number) = ?', [strtoupper(trim($request->reg_number))])
            ->first();

        if (!$student) {
            return redirect()
                ->route('ai-transcript-studio.index')
                ->with('error', 'Student not found.')
                ->withInput();
        }

        $request->session()->put('ai_studio_student_id', $student->id);

        return redirect()
            ->route('ai-transcript-studio.index', array_filter([
                'target_scale' => $request->input('target_scale'),
                'target_value' => $request->input('target_value'),
            ]));
    }

    public function runRedirect()
    {
        return redirect()
            ->route('ai-transcript-studio.index')
            ->with('error', 'Use the "Run AI Transcript Fill" button to start generation. Do not refresh the run URL.');
    }

    public function run(Request $request, TranscriptAiStudioService $studio)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'target_scale' => ['required', Rule::in(['cgpa', 'percentage'])],
            'target_value' => 'required|numeric',
        ]);

        $scale = $data['target_scale'];
        $value = (float) $data['target_value'];

        if ($scale === 'cgpa' && ($value < 2 || $value > 5)) {
            return $this->runValidationError($request, 'CGPA must be between 2.0 and 5.0.');
        }

        if ($scale === 'percentage' && ($value < 45 || $value > 95)) {
            return $this->runValidationError($request, 'Percentage must be between 45 and 95.');
        }

        $resolved = $studio->resolveTarget($scale, $value);
        $student = Student::with(['department', 'degree_level'])->findOrFail($data['student_id']);

        AiTranscriptRun::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['pending', 'running'])
            ->each(function (AiTranscriptRun $activeRun) {
                $activeRun->markCancelled('Stopped — a new AI run was started.');
            });

        $options = [
            'generate_materials' => $request->boolean('generate_materials', true),
            'generate_assessments' => $request->boolean('generate_assessments', true),
            'bot_auto_mark' => $request->boolean('bot_auto_mark', true),
            'fast_mode' => $request->boolean('fast_mode', true),
            'target_scale' => $scale,
            'target_input_value' => $value,
        ];

        $run = $studio->startRun(
            $student,
            $resolved['percentage'],
            $options,
            $resolved['cgpa']
        );
        $request->session()->put('ai_studio_student_id', $student->id);

        ProcessAiTranscriptRunJob::dispatch($run)->afterResponse();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'run_id' => $run->id,
                'poll_url' => route('ai-transcript-studio.run.progress', $run),
                'cancel_url' => route('ai-transcript-studio.run.cancel', $run),
                'message' => 'AI transcript generation started.',
            ]);
        }

        return redirect()
            ->route('ai-transcript-studio.index', [
                'target_scale' => $scale,
                'target_value' => $value,
            ])
            ->with('success', 'AI run started. Watch progress on this page.')
            ->with('last_run_id', $run->id);
    }

    public function progress(AiTranscriptRun $run)
    {
        return response()->json($run->progressPayload());
    }

    public function showRun(AiTranscriptRun $run)
    {
        $run->load(['student.department', 'materials.course', 'questions.course', 'triggeredBy']);

        $assessmentResults = $run->student
            ? AiAssessmentResults::forRun($run->student, $run)
            : [];

        return view('ai-transcript-studio.show-run', compact('run', 'assessmentResults'));
    }

    public function generateTranscript(Student $student, CertificatesController $certificates)
    {
        $student->loadMissing(['department.school', 'degree_level']);

        if (! TranscriptProfile::isReady($student)) {
            return redirect()
                ->route('ai-transcript-studio.index')
                ->with('ai_studio_student_id', $student->id)
                ->with('transcript_profile_required', true)
                ->with('error', TranscriptProfile::readinessPayload($student)['message']);
        }

        if (! AiTranscriptRun::studentHasCompletedFill($student->id)) {
            return redirect()
                ->route('ai-transcript-studio.index')
                ->with('ai_studio_student_id', $student->id)
                ->with('error', 'Run AI Transcript Fill first to create materials, assessments, and marks before generating the PDF.');
        }

        return $certificates->streamTranscriptPdf($student);
    }

    public function cancel(Request $request, AiTranscriptRun $run)
    {
        if (!$run->markCancelled()) {
            $message = 'This run is not active (already finished or stopped).';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()
                ->route('ai-transcript-studio.index')
                ->with('error', $message);
        }

        $run->addProgressEvent('info', 'Stop requested — halting at next safe step.');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'AI run stop requested.',
                'status' => $run->fresh()->status,
            ]);
        }

        return redirect()
            ->route('ai-transcript-studio.index')
            ->with('success', 'AI run stop requested. It will halt shortly.');
    }

    public function destroy(Request $request, AiTranscriptRun $run)
    {
        if ($run->isActive()) {
            $run->markCancelled('Deleted while running.');
        }

        $run->load('materials');

        foreach ($run->materials as $material) {
            if ($material->pdf_path && Storage::disk('public')->exists($material->pdf_path)) {
                Storage::disk('public')->delete($material->pdf_path);
            }
        }

        $run->questions()->delete();
        $run->materials()->delete();
        $run->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'AI run deleted.']);
        }

        return redirect()
            ->route('ai-transcript-studio.index')
            ->with('success', 'AI run deleted.');
    }

    private function runValidationError(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()
            ->route('ai-transcript-studio.index')
            ->with('error', $message);
    }
}
