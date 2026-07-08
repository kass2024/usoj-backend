<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAiTranscriptRunJob;
use App\Models\AiTranscriptRun;
use App\Models\Student;
use App\Services\GeminiAiService;
use App\Services\TranscriptAiStudioService;
use App\Support\CertificateGrades;
use Illuminate\Http\Request;
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
        $targetScale = 'cgpa';
        $targetValue = 4.5;

        if ($studentId = $request->session()->get('ai_studio_student_id')) {
            $student = Student::with(['department', 'degree_level'])->find($studentId);
            $lastRun = AiTranscriptRun::where('student_id', $studentId)->latest()->first();

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
                $courseCount = $studio->buildFourYearProgramSchedule($student)->count();
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
            'targetScale',
            'targetValue',
            'gemini'
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

        return view('ai-transcript-studio.show-run', compact('run'));
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
