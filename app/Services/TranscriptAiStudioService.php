<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AiCourseMaterial;
use App\Models\AiQuestionBank;
use App\Models\AiTranscriptRun;
use App\Models\Assignment;
use App\Models\ClassStudent;
use App\Models\ClassYear;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Modules;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\Submission;
use App\Models\User;
use App\Support\CertificateGrades;
use App\Support\ProgramDuration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TranscriptAiStudioService
{
    public function __construct(
        private readonly GeminiAiService $gemini,
    ) {}

    public function percentageFromCgpa(float $cgpa): float
    {
        return min(95, max(45, 60 + ($cgpa / 5.0) * 20));
    }

    public function cgpaFromPercentage(float $percentage): float
    {
        return CertificateGrades::fromPercentage($percentage)['gp'];
    }

    /**
     * @return array{percentage: float, cgpa: float}
     */
    public function resolveTarget(string $scale, float $value): array
    {
        if ($scale === 'cgpa') {
            $cgpa = round(min(5, max(2, $value)), 2);
            $percentage = round($this->percentageFromCgpa($cgpa), 2);

            return ['percentage' => $percentage, 'cgpa' => $cgpa];
        }

        $percentage = round(min(95, max(45, $value)), 2);
        $cgpa = round($this->cgpaFromPercentage($percentage), 2);

        return ['percentage' => $percentage, 'cgpa' => $cgpa];
    }

    private function marksForPercentage(float $percentage, string $type, int $courseId = 0): int
    {
        $max = in_array($type, ['assignment', 'quiz'], true) ? 30 : 40;

        return (int) round(($percentage / 100) * $max);
    }

    /**
     * Build per-course marks with varied GP/GD that average to the target CGPA.
     *
     * @return array<int, array{gp: float, percentage: float, marks: array{assignment: int, quiz: int, exam: int}}>
     */
    public function buildCourseGradePlan(float $targetCgpa, Collection $schedule, int $studentId): array
    {
        $entries = [];
        $palette = CertificateGrades::gradePointPalette();

        foreach ($schedule as $index => $entry) {
            $course = $entry['course'];
            $entries[] = [
                'course_id' => (int) $course->id,
                'credits' => CertificateGrades::resolveCourseCredits($course),
                'index' => $index,
            ];
        }

        if ($entries === []) {
            return [];
        }

        $gps = [];
        foreach ($entries as $entry) {
            $hash = crc32("{$studentId}:{$entry['course_id']}:{$entry['index']}");
            $gps[] = $palette[$hash % count($palette)];
        }

        $gps = $this->calibrateGpListToExactTarget(
            $gps,
            array_column($entries, 'credits'),
            max(2.0, min(5.0, round($targetCgpa, 2)))
        );

        $plan = [];
        foreach ($entries as $i => $entry) {
            $split = CertificateGrades::marksSplitForGp($gps[$i]);
            $plan[$entry['course_id']] = [
                'gp' => $split['gp'],
                'percentage' => $split['percentage'],
                'marks' => [
                    'assignment' => $split['assignment'],
                    'quiz' => $split['quiz'],
                    'exam' => $split['exam'],
                ],
            ];
        }

        return $plan;
    }

    /** @deprecated Use buildCourseGradePlan */
    public function buildCoursePercentagePlan(float $targetCgpa, Collection $schedule, int $studentId): array
    {
        $plan = [];
        foreach ($this->buildCourseGradePlan($targetCgpa, $schedule, $studentId) as $courseId => $entry) {
            $plan[$courseId] = $entry['percentage'];
        }

        return $plan;
    }

    public function courseTargetPercentage(
        float $basePercent,
        int $studentId,
        int $courseId,
        int $courseIndex,
        array $plan = []
    ): float {
        if (isset($plan[$courseId]['percentage'])) {
            return (float) $plan[$courseId]['percentage'];
        }

        if (isset($plan[$courseId]) && is_numeric($plan[$courseId])) {
            return (float) $plan[$courseId];
        }

        return $this->fallbackCoursePercentage($basePercent, $studentId, $courseId, $courseIndex);
    }

    private function fallbackCoursePercentage(
        float $basePercent,
        int $studentId,
        int $courseId,
        int $courseIndex
    ): float {
        $hash = crc32("{$studentId}:{$courseId}:{$courseIndex}");
        $spread = (($hash % 27) - 13) * 0.55;
        $indexBias = (($courseIndex % 9) - 4) * 0.35;

        return round(min(95, max(45, $basePercent + $spread + $indexBias)), 2);
    }

    /**
     * @param  array<float>  $gps
     * @param  array<int>  $credits
     * @return array<float>
     */
    private function calibrateGpListToExactTarget(array $gps, array $credits, float $targetCgpa): array
    {
        $steps = CertificateGrades::gpSteps();
        $targetCgpa = round($targetCgpa, 2);

        for ($iter = 0; $iter < 3000; $iter++) {
            $current = $this->weightedGpAverageFromMarks($gps, $credits);

            if (round($current, 2) === $targetCgpa) {
                break;
            }

            $diff = $targetCgpa - $current;
            $improved = false;

            foreach ($gps as $i => $gp) {
                $stepIdx = array_search($gp, $steps, true);
                if ($stepIdx === false) {
                    continue;
                }

                $candidates = [];
                if ($diff > 0 && $stepIdx > 0) {
                    $candidates[] = $steps[$stepIdx - 1];
                }
                if ($diff < 0 && $stepIdx < count($steps) - 1) {
                    $candidates[] = $steps[$stepIdx + 1];
                }

                foreach ($candidates as $newGp) {
                    $trial = $gps;
                    $trial[$i] = $newGp;
                    $trialAvg = $this->weightedGpAverageFromMarks($trial, $credits);
                    $oldErr = abs($current - $targetCgpa);
                    $newErr = abs($trialAvg - $targetCgpa);

                    if ($newErr + 0.0001 < $oldErr) {
                        $gps = $trial;
                        $improved = true;
                        break 2;
                    }
                }
            }

            if (!$improved) {
                break;
            }
        }

        return $gps;
    }

    /**
     * @param  array<float>  $gps
     * @param  array<int>  $credits
     */
    private function weightedGpAverageFromMarks(array $gps, array $credits): float
    {
        $weighted = 0.0;
        $units = 0;

        foreach ($gps as $i => $gp) {
            $cu = max(1, (int) ($credits[$i] ?? 3));
            $resolvedGp = CertificateGrades::marksSplitForGp($gp)['gp'];
            $weighted += $resolvedGp * $cu;
            $units += $cu;
        }

        return $units > 0 ? round($weighted / $units, 2) : 0.0;
    }

    /**
     * @param  array<float>  $gps
     * @param  array<int>  $credits
     */
    private function weightedGpAverage(array $gps, array $credits): float
    {
        $weighted = 0.0;
        $units = 0;

        foreach ($gps as $i => $gp) {
            $cu = max(1, (int) ($credits[$i] ?? 3));
            $weighted += $gp * $cu;
            $units += $cu;
        }

        return $units > 0 ? round($weighted / $units, 2) : 0.0;
    }

    /**
     * @param  array<int, array{gp: float, percentage: float, marks: array}>  $gradePlan
     */
    public function previewCgpaFromGradePlan(Collection $schedule, array $gradePlan): float
    {
        $gps = [];
        $credits = [];

        foreach ($schedule as $entry) {
            $course = $entry['course'];
            $plan = $gradePlan[$course->id] ?? null;
            $gps[] = $plan['gp'] ?? CertificateGrades::fromPercentage($plan['percentage'] ?? 76)['gp'];
            $credits[] = CertificateGrades::resolveCourseCredits($course);
        }

        return $this->weightedGpAverage($gps, $credits);
    }

    /**
     * @param  array<int, array{gp: float, percentage: float, marks: array}>  $gradePlan
     */
    private function rebalanceSubmissionsToTargetCgpa(
        Student $student,
        float $targetCgpa,
        Collection $schedule,
        array $gradePlan
    ): void {
        $targetCgpa = round($targetCgpa, 2);
        $steps = CertificateGrades::gpSteps();

        for ($attempt = 0; $attempt < 200; $attempt++) {
            $achieved = round($this->previewCgpa($student), 2);

            if ($achieved === $targetCgpa) {
                return;
            }

            $diff = $targetCgpa - $achieved;
            $adjusted = false;

            foreach ($schedule as $entry) {
                $course = $entry['course'];
                $plan = $gradePlan[$course->id] ?? null;

                if (!$plan) {
                    continue;
                }

                $currentGp = (float) ($plan['gp'] ?? 0);
                $stepIdx = array_search($currentGp, $steps, true);

                if ($stepIdx === false) {
                    continue;
                }

                $newGp = null;
                if ($diff > 0 && $stepIdx > 0) {
                    $newGp = $steps[$stepIdx - 1];
                } elseif ($diff < 0 && $stepIdx < count($steps) - 1) {
                    $newGp = $steps[$stepIdx + 1];
                }

                if ($newGp === null) {
                    continue;
                }

                if ($this->applyGradePlanToCourseSubmissions($student, $course->id, $newGp)) {
                    $split = CertificateGrades::marksSplitForGp($newGp);
                    $gradePlan[$course->id] = [
                        'gp' => $split['gp'],
                        'percentage' => $split['percentage'],
                        'marks' => [
                            'assignment' => $split['assignment'],
                            'quiz' => $split['quiz'],
                            'exam' => $split['exam'],
                        ],
                    ];
                    $adjusted = true;
                    break;
                }
            }

            if (!$adjusted) {
                break;
            }
        }
    }

    private function applyGradePlanToCourseSubmissions(Student $student, int $courseId, float $gp): bool
    {
        $split = CertificateGrades::marksSplitForGp($gp);

        $module = Modules::query()
            ->where('course_id', $courseId)
            ->where(function ($q) use ($student) {
                $q->whereHas('assignments.submissions', fn ($qq) => $qq->where('student_id', $student->id))
                    ->orWhereHas('quizzes.submissions', fn ($qq) => $qq->where('student_id', $student->id))
                    ->orWhereHas('exams.submissions', fn ($qq) => $qq->where('student_id', $student->id));
            })
            ->with(['assignments.submissions', 'quizzes.submissions', 'exams.submissions'])
            ->first();

        if (!$module) {
            return false;
        }

        $updated = false;

        foreach ($module->assignments as $assessment) {
            $sub = $assessment->submissions->firstWhere('student_id', $student->id);
            if ($sub) {
                $sub->update(['marks_obtained' => $split['assignment']]);
                $updated = true;
            }
        }

        foreach ($module->quizzes as $assessment) {
            $sub = $assessment->submissions->firstWhere('student_id', $student->id);
            if ($sub) {
                $sub->update(['marks_obtained' => $split['quiz']]);
                $updated = true;
            }
        }

        foreach ($module->exams as $assessment) {
            $sub = $assessment->submissions->firstWhere('student_id', $student->id);
            if ($sub) {
                $sub->update(['marks_obtained' => $split['exam']]);
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * @param  array<int, float>  $plan
     */
    public function previewCgpaWithPlan(Student $student, array $plan): float
    {
        $schedule = $this->buildProgramSchedule($student);
        $gps = [];
        $credits = [];

        foreach ($schedule as $index => $entry) {
            $course = $entry['course'];
            $percent = $plan[$course->id] ?? 76.0;
            $grades = CertificateGrades::fromPercentage($percent);
            $gps[] = $grades['gp'];
            $credits[] = CertificateGrades::resolveCourseCredits($course);
        }

        return $this->weightedGpAverage($gps, $credits);
    }

    /**
     * @param  array{generate_materials?:bool,generate_assessments?:bool,bot_auto_mark?:bool}  $options
     */
    public function startRun(
        Student $student,
        float $targetPercentage,
        array $options = [],
        ?float $targetCgpa = null
    ): AiTranscriptRun {
        $options = array_merge([
            'generate_materials' => true,
            'generate_assessments' => true,
            'bot_auto_mark' => true,
            'fast_mode' => true,
        ], $options);

        $targetPercentage = round(min(95, max(45, $targetPercentage)), 2);
        $targetCgpa = $targetCgpa !== null
            ? round(min(5, max(2, $targetCgpa)), 2)
            : $this->cgpaFromPercentage($targetPercentage);

        return AiTranscriptRun::create([
            'student_id' => $student->id,
            'triggered_by' => Auth::id(),
            'target_percentage' => $targetPercentage,
            'target_cgpa' => $targetCgpa,
            'status' => 'pending',
            'options' => $options,
            'log' => [],
            'progress' => ['percent' => 0, 'steps' => [], 'events' => []],
        ]);
    }

    public function processRun(AiTranscriptRun $run): AiTranscriptRun
    {
        $this->beginLongRunningProcess();

        $run->update(['status' => 'running']);

        try {
            if (!$this->gemini->isConfigured()) {
                throw new \RuntimeException('Gemini is not configured. Add GOOGLE_AI_API_KEY to .env');
            }

            $student = Student::with(['department', 'degree_level'])->findOrFail($run->student_id);
            $options = $run->options ?? [];
            $targetPercentage = (float) $run->target_percentage;
            $targetCgpa = (float) ($run->target_cgpa ?? $this->cgpaFromPercentage($targetPercentage));

            $programYears = ProgramDuration::yearsForStudent($student);
            $schedule = $this->buildProgramSchedule($student);

            if ($schedule->isEmpty()) {
                throw new \RuntimeException('No courses found for this student program/department.');
            }

            $this->initCourseProgressSteps($run, $schedule, $options, $programYears);

            $courseGradePlan = $this->buildCourseGradePlan(
                $targetCgpa,
                $schedule,
                $student->id
            );

            $plannedCgpa = $this->previewCgpaFromGradePlan($schedule, $courseGradePlan);
            $run->appendLog(
                'Grade plan calibrated: target CGPA ' . round($targetCgpa, 2)
                . ', planned CGPA ' . round($plannedCgpa, 2) . '.'
            );

            $run->setStepStatus('init', 'active', 'Initializing AI transcript run');
            $run->setStepStatus('init', 'done');
            $run->setStepStatus('clear_marks', 'active', 'Clearing old marks from database');

            $deleted = Submission::where('student_id', $student->id)->delete();
            $run->appendLog("Cleared {$deleted} existing submission(s) for fresh transcript marks.");
            $run->setStepStatus('clear_marks', 'done');

            $run->setStepStatus('schedule', 'active', "Building {$programYears}-year program schedule");
            $run->appendLog(
                ProgramDuration::label($programYears) . ' program schedule: ' . $schedule->count()
                . ' course(s) across ' . ProgramDuration::semesterSlots($programYears) . ' semesters. Target: '
                . $targetPercentage . '%.'
            );
            $run->setStepStatus('schedule', 'done');

            $lecturerId = $this->resolveLecturerId($student);
            $classYears = $this->ensureProgramClassYears($student, $programYears);
            $academicYears = $this->ensureProgramAcademicYears($programYears);

            foreach ($classYears as $index => $classYear) {
                ClassStudent::firstOrCreate([
                    'student_id' => $student->id,
                    'class_year_id' => $classYear->id,
                ], [
                    'academic_year_id' => $academicYears[$index]->id,
                ]);
            }

            $run->addProgressEvent(
                'info',
                ($options['fast_mode'] ?? true)
                    ? 'Fast mode: skipping Gemini API — built-in questions + question bank reuse.'
                    : 'Full mode: Gemini generates course questions.'
            );

            $prefetchedQuestions = $this->prefetchQuestionsInParallel($run, $schedule, $options);
            if ($stopped = $this->stopIfCancelled($run)) {
                return $stopped;
            }

            $submissionBatch = [];
            $now = now();
            $courseIndex = 0;

            foreach ($schedule as $entry) {
                $this->beginLongRunningProcess();

                if ($stopped = $this->stopIfCancelled($run)) {
                    return $stopped;
                }

                $course = $entry['course'];
                $stepId = $this->courseStepId($course->id);
                $coursePercent = $this->courseTargetPercentage(
                    $targetPercentage,
                    $student->id,
                    $course->id,
                    $courseIndex,
                    $courseGradePlan
                );

                $run->setStepStatus(
                    $stepId,
                    'active',
                    "Y{$entry['year_index']} S{$entry['semester']}: {$course->code} — processing"
                );

                DB::transaction(function () use (
                    $run,
                    $student,
                    $course,
                    $entry,
                    $lecturerId,
                    $coursePercent,
                    $options,
                    $stepId,
                    $prefetchedQuestions,
                    &$submissionBatch,
                    $now
                ) {
                    $module = $this->ensureModule(
                        $course,
                        $lecturerId,
                        $entry['academic_year_id'],
                        $entry['class_year_id'],
                        $entry['semester']
                    );

                    if ($options['generate_materials'] && !($options['fast_mode'] ?? true)) {
                        $this->generateCourseMaterialPdf($run, $student, $course, $module, $stepId, $options);
                    }

                    if ($options['generate_assessments'] || $options['bot_auto_mark']) {
                        $this->generateAssessmentsAndQuestions(
                            $run,
                            $course,
                            $module,
                            (bool) ($options['generate_assessments'] ?? true),
                            $stepId,
                            $prefetchedQuestions[$course->id] ?? null
                        );
                    }

                    if ($options['bot_auto_mark']) {
                        $newRows = $this->buildBotSubmissionRows(
                            $student,
                            $module,
                            $coursePercent,
                            $now,
                            $courseGradePlan[$course->id]['marks'] ?? null
                        );
                        $submissionBatch = array_merge($submissionBatch, $newRows);
                        $run->increment('submissions_created', count($newRows));
                    }

                    $run->increment('courses_processed');
                });

                $run->setStepStatus(
                    $stepId,
                    'done',
                    "Y{$entry['year_index']} S{$entry['semester']}: {$course->code} — {$coursePercent}%"
                );
                $run->appendLog(
                    "Year {$entry['year_index']} Sem {$entry['semester']}: {$course->code} — {$coursePercent}%"
                );

                $courseIndex++;
            }

            if ($submissionBatch !== []) {
                foreach (array_chunk($submissionBatch, 500) as $chunk) {
                    Submission::insert($chunk);
                }
            }

            $this->rebalanceSubmissionsToTargetCgpa($student, $targetCgpa, $schedule, $courseGradePlan);

            $run->setStepStatus('finalize', 'active', 'Calculating final CGPA');
            $achieved = $this->previewCgpa($student);
            $run->update([
                'status' => 'completed',
                'achieved_cgpa' => round($achieved, 2),
            ]);
            $run->setStepStatus('finalize', 'done', 'Transcript fill completed');
            $run->appendLog(
                'Completed ' . ProgramDuration::label($programYears) . ' fill. Target CGPA: ' . round($targetCgpa, 2)
                . '. Achieved CGPA: ' . round($achieved, 2) . '.'
            );

            return $run->fresh();
        } catch (Throwable $e) {
            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $run->addProgressEvent('error', 'Run failed: ' . $e->getMessage());
            $run->appendLog('Failed: ' . $e->getMessage());

            return $run->fresh();
        }
    }

    /** @deprecated Use startRun + processRun */
    public function run(Student $student, float $targetPercentage, array $options = []): AiTranscriptRun
    {
        $run = $this->startRun($student, $targetPercentage, $options);

        return $this->processRun($run);
    }

    /**
     * @param  array{generate_materials?:bool,generate_assessments?:bool,bot_auto_mark?:bool}  $options
     */
    private function initCourseProgressSteps(AiTranscriptRun $run, Collection $schedule, array $options, int $programYears): void
    {
        $steps = [
            ['id' => 'init', 'label' => 'Initialize AI run'],
            ['id' => 'clear_marks', 'label' => 'Clear old marks'],
            ['id' => 'schedule', 'label' => 'Build ' . ProgramDuration::label($programYears) . ' schedule'],
        ];

        if ($this->needsGeminiPrefetch($options)) {
            $steps[] = ['id' => 'prefetch_questions', 'label' => 'Prefetch Gemini questions'];
        }

        foreach ($schedule as $entry) {
            $course = $entry['course'];
            $steps[] = [
                'id' => $this->courseStepId($course->id),
                'label' => "Y{$entry['year_index']} S{$entry['semester']}: {$course->code}",
            ];
        }

        $steps[] = ['id' => 'finalize', 'label' => 'Finalize transcript'];

        $run->initProgress($steps);

        if ($options['generate_materials'] ?? true) {
            if ($options['fast_mode'] ?? true) {
                $run->addProgressEvent('info', 'Fast mode: skipping PDF render (transcript marks only). Turn off Fast mode for AI PDFs.');
            } else {
                $run->addProgressEvent('info', 'Course PDFs will be generated via Gemini AI.');
            }
        }
        if ($options['bot_auto_mark'] ?? true) {
            $run->addProgressEvent('info', 'USJ AI Bot will auto-mark all assessments (no student answers).');
        }
    }

    private function needsGeminiPrefetch(array $options): bool
    {
        return ($options['generate_assessments'] ?? true)
            && !($options['fast_mode'] ?? true)
            && !$this->gemini->usesFallbackOnly();
    }

    private function prefetchQuestionsInParallel(
        AiTranscriptRun $run,
        Collection $schedule,
        array $options
    ): array {
        if (!($options['generate_assessments'] ?? true)) {
            return [];
        }

        if (($options['fast_mode'] ?? true) || $this->gemini->usesFallbackOnly()) {
            $message = $this->gemini->usesFallbackOnly()
                ? 'cPanel safe mode: skipping Gemini API — using built-in questions for all courses.'
                : 'Fast mode: using built-in questions (no Gemini wait for 63 courses).';

            $run->addProgressEvent('info', $message);

            $prefetched = [];
            foreach ($schedule as $entry) {
                $course = $entry['course'];
                if ($this->courseHasQuestionBank($course->id)) {
                    continue;
                }
                $prefetched[(int) $course->id] = $this->fallbackCombinedQuestions($course);
            }

            return $prefetched;
        }

        $requests = [];

        foreach ($schedule as $entry) {
            $course = $entry['course'];
            if ($this->courseHasQuestionBank($course->id)) {
                continue;
            }
            $requests[(string) $course->id] = [
                'prompt' => $this->combinedQuestionsPrompt($course),
                'system' => 'You are a USJ exam author. Return compact valid JSON only.',
            ];
        }

        if ($requests === []) {
            $run->addProgressEvent('info', 'All courses have reusable question bank entries — skipping Gemini for questions.');

            return [];
        }

        $mode = config('gemini.sequential_mode', false) ? 'sequential' : 'parallel';
        $run->addProgressEvent(
            'info',
            'Prefetching questions for ' . count($requests) . " course(s) via {$mode} Gemini ("
            . $this->gemini->parallelLimit() . ' at a time).'
        );

        $prefetched = [];
        $chunks = array_chunk($requests, $this->gemini->parallelLimit(), true);
        $chunkDelayMs = max(0, (int) config('gemini.request_delay_ms', 500));
        $totalRequests = count($requests);
        $completedRequests = 0;

        $run->setPrefetchProgress(0, $totalRequests);

        foreach ($chunks as $chunkIndex => $chunk) {
            $this->beginLongRunningProcess();

            if ($this->wasCancelled($run)) {
                return $prefetched;
            }

            if ($chunkIndex > 0 && $chunkDelayMs > 0) {
                usleep($chunkDelayMs * 1000);
            }

            try {
                $results = $this->gemini->poolGenerateJson($chunk);
            } catch (Throwable $e) {
                $results = [];
                foreach (array_keys($chunk) as $courseId) {
                    $results[$courseId] = 'Gemini pool error: ' . $e->getMessage();
                }
            }

            foreach ($results as $courseId => $result) {
                if (!is_array($result)) {
                    $message = is_string($result) ? $result : 'Invalid question payload from Gemini';
                    $run->addProgressEvent('error', "Course {$courseId}: {$message}", 'Using fallback questions');
                    $entry = $schedule->first(fn ($e) => (string) $e['course']->id === (string) $courseId);
                    $course = $entry['course'] ?? null;
                    $prefetched[(int) $courseId] = $course
                        ? $this->fallbackCombinedQuestions($course)
                        : [];
                    continue;
                }
                $prefetched[(int) $courseId] = $result;
            }

            $completedRequests += count($chunk);
            $run->setPrefetchProgress($completedRequests, $totalRequests);
        }

        return $prefetched;
    }

    private function courseHasQuestionBank(int $courseId): bool
    {
        return AiQuestionBank::where('course_id', $courseId)->exists();
    }

    private function combinedQuestionsPrompt(Course $course): string
    {
        return <<<PROMPT
For {$course->code} ({$course->name}), generate MCQs as JSON:
{
  "assignment": [{"title":"...","marks":5,"choices":["A","B","C","D"],"correct_index":0}],
  "quiz": [3 items same format],
  "exam": [5 items same format]
}
Keep titles short. correct_index is 0-based.
PROMPT;
    }

    private function fallbackCombinedQuestions(Course $course): array
    {
        return [
            'assignment' => $this->fallbackQuestions($course, 3),
            'quiz' => $this->fallbackQuestions($course, 3),
            'exam' => $this->fallbackQuestions($course, 5),
        ];
    }

    private function courseStepId(int $courseId): string
    {
        return 'course_' . $courseId;
    }

    private function wasCancelled(AiTranscriptRun $run): bool
    {
        return $run->fresh()->status === 'cancelled';
    }

    private function stopIfCancelled(AiTranscriptRun $run): ?AiTranscriptRun
    {
        if (!$this->wasCancelled($run)) {
            return null;
        }

        $run->addProgressEvent('info', 'Run stopped by user.');

        return $run->fresh();
    }

    private function beginLongRunningProcess(): void
    {
        $limit = (int) config('gemini.run_max_execution_time', 0);

        if ($limit === 0) {
            @set_time_limit(0);
            @ini_set('max_execution_time', '0');
        } else {
            @set_time_limit($limit);
            @ini_set('max_execution_time', (string) $limit);
        }
    }

    public function buildProgramSchedule(Student $student): Collection
    {
        $programYears = ProgramDuration::yearsForStudent($student);
        $classYears = $this->ensureProgramClassYears($student, $programYears);
        $academicYears = $this->ensureProgramAcademicYears($programYears);

        $courses = Course::query()
            ->where('department_id', $student->department_id)
            ->where(function ($q) use ($student) {
                if ($student->degree_level_id) {
                    $q->where('degree_level_id', $student->degree_level_id)
                        ->orWhereNull('degree_level_id');
                }
            })
            ->where('status', 'active')
            ->orderBy('code')
            ->get();

        if ($courses->isEmpty()) {
            $courses = Course::query()
                ->where('department_id', $student->department_id)
                ->when($student->degree_level_id, function ($q) use ($student) {
                    $q->where(function ($qq) use ($student) {
                        $qq->where('degree_level_id', $student->degree_level_id)
                            ->orWhereNull('degree_level_id');
                    });
                })
                ->orderBy('code')
                ->get();
        }

        if ($courses->isEmpty()) {
            return collect();
        }

        $existingModules = Modules::query()
            ->with('classYear')
            ->whereIn('course_id', $courses->pluck('id'))
            ->whereHas('classYear', function ($q) use ($student) {
                $q->where('department_id', $student->department_id)
                    ->where('degree_level_id', $student->degree_level_id);
            })
            ->orderBy('id')
            ->get()
            ->unique('course_id')
            ->keyBy('course_id');

        $classYearById = $classYears->keyBy('id');
        $schedule = collect();
        $unassigned = collect();

        foreach ($courses as $course) {
            $module = $existingModules->get($course->id);

            if ($course->year_index && $course->semester) {
                $yearIndex = max(1, min($programYears, (int) $course->year_index));
                $semester = ProgramDuration::normalizeYearSemester(
                    $yearIndex,
                    (int) $course->semester,
                    $programYears
                )['semester'];
                $classYear = $classYears[$yearIndex - 1] ?? $classYears->first();

                $schedule->push([
                    'course' => $course,
                    'class_year_id' => $classYear->id,
                    'academic_year_id' => $academicYears[$yearIndex - 1]->id ?? $academicYears[0]->id,
                    'semester' => $semester,
                    'year_index' => $yearIndex,
                ]);

                continue;
            }

            if ($module) {
                $classYear = $module->classYear ?? $classYearById->get($module->class_year_id);
                $yearIndex = $this->resolveYearIndex($classYear, $classYears, $programYears);

                $schedule->push([
                    'course' => $course,
                    'class_year_id' => $module->class_year_id,
                    'academic_year_id' => $module->academic_year_id ?: $academicYears[$yearIndex - 1]->id,
                    'semester' => ProgramDuration::normalizeYearSemester(
                        $yearIndex,
                        (int) ($module->semester ?: 1),
                        $programYears
                    )['semester'],
                    'year_index' => $yearIndex,
                ]);
            } else {
                $unassigned->push($course);
            }
        }

        $slots = [];
        foreach ($classYears as $index => $classYear) {
            $yearNum = $index + 1;
            $slots[] = [
                'class_year_id' => $classYear->id,
                'academic_year_id' => $academicYears[$index]->id,
                'semester' => 1,
                'year_index' => $yearNum,
            ];
            $slots[] = [
                'class_year_id' => $classYear->id,
                'academic_year_id' => $academicYears[$index]->id,
                'semester' => ProgramDuration::SEMESTERS_PER_YEAR,
                'year_index' => $yearNum,
            ];
        }

        $this->distributeCoursesAcrossSlots($unassigned, $slots, $schedule);

        return $schedule
            ->sortBy(fn ($item) => sprintf('%02d-%d-%s', $item['year_index'], $item['semester'], $item['course']->code))
            ->values();
    }

    /** @deprecated Use buildProgramSchedule */
    public function buildFourYearProgramSchedule(Student $student): Collection
    {
        return $this->buildProgramSchedule($student);
    }

    /**
     * @param  array<int, array<string, mixed>>  $slots
     */
    private function distributeCoursesAcrossSlots(Collection $courses, array $slots, Collection $schedule): void
    {
        $courseList = $courses->values()->all();
        $totalSlots = count($slots);

        if ($totalSlots === 0 || $courseList === []) {
            return;
        }

        $count = count($courseList);
        $basePerSlot = intdiv($count, $totalSlots);
        $remainder = $count % $totalSlots;
        $offset = 0;

        foreach ($slots as $slotIndex => $slot) {
            $take = $basePerSlot + ($slotIndex < $remainder ? 1 : 0);
            $batch = array_slice($courseList, $offset, $take);
            $offset += $take;

            foreach ($batch as $course) {
                $schedule->push(array_merge($slot, ['course' => $course]));
            }
        }
    }

    public function programYearsForStudent(Student $student): int
    {
        return ProgramDuration::yearsForStudent($student);
    }

    /**
     * @return array<int, array{year_index: int, semester: int, courses: array<int, string>}>
     */
    public function scheduleSummary(Student $student): array
    {
        $summary = [];

        foreach ($this->buildProgramSchedule($student) as $entry) {
            $key = $entry['year_index'] . '-' . $entry['semester'];
            $summary[$key]['year_index'] = $entry['year_index'];
            $summary[$key]['semester'] = $entry['semester'];
            $summary[$key]['courses'][] = $entry['course']->code;
        }

        ksort($summary, SORT_NATURAL);

        return array_values($summary);
    }

    public function previewCgpa(Student $student): float
    {
        $controller = app(\App\Http\Controllers\CertificatesController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('buildCertificateData');
        $method->setAccessible(true);
        $data = $method->invoke($controller, $student);

        return CertificateGrades::finalCgpa($data['semesters'] ?? []);
    }

    /** @return Collection<int, ClassYear> */
    private function ensureProgramClassYears(Student $student, int $programYears): Collection
    {
        $years = collect();

        for ($i = 1; $i <= $programYears; $i++) {
            $years->push(ClassYear::firstOrCreate([
                'year_name' => "Year {$i}",
                'department_id' => $student->department_id,
                'degree_level_id' => $student->degree_level_id,
            ], [
                'semester' => 1,
            ]));
        }

        return $years->sortBy(function (ClassYear $cy) {
            if (preg_match('/(\d+)/', $cy->year_name, $m)) {
                return (int) $m[1];
            }

            return 99;
        })->values();
    }

    /** @return array<int, AcademicYear> */
    private function ensureProgramAcademicYears(int $programYears): array
    {
        $existing = AcademicYear::query()->orderBy('period')->get();

        if ($existing->count() >= $programYears) {
            return $existing->take($programYears)->values()->all();
        }

        $baseYear = now()->year - ($programYears - 1);
        $years = [];

        for ($i = 0; $i < $programYears; $i++) {
            $start = $baseYear + $i;
            $period = "{$start}-" . ($start + 1);
            $years[] = AcademicYear::firstOrCreate(['period' => $period]);
        }

        return $years;
    }

    private function resolveYearIndex(?ClassYear $classYear, Collection $classYears, int $programYears): int
    {
        if (! $classYear) {
            return 1;
        }

        if (preg_match('/(\d+)/', $classYear->year_name, $m)) {
            return max(1, min($programYears, (int) $m[1]));
        }

        $position = $classYears->search(fn (ClassYear $cy) => $cy->id === $classYear->id);

        return $position === false ? 1 : min($programYears, $position + 1);
    }

    private function resolveLecturerId(Student $student): int
    {
        $lecturer = User::query()
            ->where('department_id', $student->department_id)
            ->where('role', 'lecture')
            ->first();

        return $lecturer?->id
            ?? User::query()->whereIn('role', ['admin', 'super_admin', 'head_of_department'])->value('id')
            ?? Auth::id()
            ?? 1;
    }

    private function ensureModule(
        Course $course,
        int $lecturerId,
        int $academicYearId,
        int $classYearId,
        int $semester
    ): Modules {
        return Modules::firstOrCreate([
            'course_id' => $course->id,
            'class_year_id' => $classYearId,
            'academic_year_id' => $academicYearId,
            'semester' => $semester,
        ], [
            'user_id' => $lecturerId,
        ]);
    }

    private function generateCourseMaterialPdf(
        AiTranscriptRun $run,
        Student $student,
        Course $course,
        Modules $module,
        string $stepId,
        array $options = []
    ): void {
        $fastMode = $options['fast_mode'] ?? true;
        $relative = 'ai_materials/' . $student->id . '/' . Str::slug($course->code) . '.pdf';

        if ($fastMode) {
            $content = $this->fallbackCourseMaterial($course);
        } else {
            $prompt = <<<PROMPT
Brief USJ course outline for {$course->code} — {$course->name} ({$course->credits} CU).
Sections: Overview, Outcomes, 8-week topics, References. Plain text only.
PROMPT;

            try {
                $content = $this->gemini->generateText(
                    $prompt,
                    'USJ academic author.',
                    (int) config('gemini.material_max_tokens', 3072)
                );
            } catch (Throwable $e) {
                $run->addProgressEvent(
                    'error',
                    "Gemini API error ({$course->code} PDF): " . $e->getMessage(),
                    'Using local fallback course outline'
                );
                $run->setStepStatus($stepId, 'warning');
                $content = $this->fallbackCourseMaterial($course);
            }
        }

        $html = view('ai-transcript-studio.material-pdf', [
            'course' => $course,
            'student' => $student,
            'content' => nl2br(e($content)),
        ])->render();

        $absolute = Storage::disk('public')->path($relative);

        if (!is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0755, true);
        }

        Pdf::loadHTML($html)->setPaper('a4', 'portrait')->save($absolute);

        AiCourseMaterial::create([
            'ai_transcript_run_id' => $run->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => $course->code . ' — Course Material',
            'pdf_path' => $relative,
            'content_preview' => Str::limit(strip_tags($content), 500),
        ]);

        $run->increment('materials_created');
    }

    private function fallbackCourseMaterial(Course $course): string
    {
        return implode("\n\n", [
            "Overview\n{$course->name} ({$course->code}) — {$course->credits} credit units.",
            "Learning Outcomes\nStudents will understand core concepts, apply knowledge, and complete assessments.",
            "Week-by-week Topics\nWeek 1-8: Introduction, theory, practice, revision, and assessment preparation.",
            "References\nUSJ faculty materials and standard textbooks for {$course->name}.",
        ]);
    }

    private function generateAssessmentsAndQuestions(
        AiTranscriptRun $run,
        Course $course,
        Modules $module,
        bool $seedWithAi = true,
        string $stepId = '',
        ?array $prefetchedPayload = null
    ): void {
        $assignment = Assignment::firstOrCreate(
            ['module_id' => $module->id, 'title' => 'AI Assignment — ' . $course->code],
            ['due_date' => now()->addMonths(3)]
        );

        $quiz = Quiz::firstOrCreate(
            ['module_id' => $module->id, 'title' => 'AI Quiz — ' . $course->code],
            ['start_date' => now()->subMonth(), 'end_date' => now()->addMonths(3)]
        );

        $exam = Exam::firstOrCreate(
            ['module_id' => $module->id, 'title' => 'AI Exam — ' . $course->code],
            ['start_date' => now()->subMonth(), 'end_date' => now()->addMonths(3)]
        );

        $needsAssignment = $assignment->questions()->count() === 0;
        $needsQuiz = $quiz->questions()->count() === 0;
        $needsExam = $exam->questions()->count() === 0;

        if (!$needsAssignment && !$needsQuiz && !$needsExam) {
            return;
        }

        if ($this->cloneQuestionsFromBank($run, $course, $assignment, $quiz, $exam, $needsAssignment, $needsQuiz, $needsExam)) {
            return;
        }

        if (!$seedWithAi) {
            return;
        }

        $payload = $prefetchedPayload ?? $this->fallbackCombinedQuestions($course);

        if ($needsAssignment && !empty($payload['assignment'])) {
            $this->persistQuestionItems($run, $course, $payload['assignment'], $assignment, null, null, 'assignment');
        }
        if ($needsQuiz && !empty($payload['quiz'])) {
            $this->persistQuestionItems($run, $course, $payload['quiz'], null, $quiz, null, 'quiz');
        }
        if ($needsExam && !empty($payload['exam'])) {
            $this->persistQuestionItems($run, $course, $payload['exam'], null, null, $exam, 'exam');
        }
    }

    private function cloneQuestionsFromBank(
        AiTranscriptRun $run,
        Course $course,
        Assignment $assignment,
        Quiz $quiz,
        Exam $exam,
        bool $needsAssignment,
        bool $needsQuiz,
        bool $needsExam
    ): bool {
        $bank = AiQuestionBank::where('course_id', $course->id)->get();

        if ($bank->isEmpty()) {
            return false;
        }

        $byType = $bank->groupBy('assessment_type');
        $cloned = false;

        if ($needsAssignment && $byType->has('assignment')) {
            $this->persistQuestionItems(
                $run,
                $course,
                $byType->get('assignment')->map(fn ($b) => $b->payload ?? [
                    'title' => $b->title,
                    'marks' => $b->marks,
                    'choices' => collect($b->choices ?? [])->pluck('label')->all(),
                    'correct_index' => 0,
                ])->all(),
                $assignment,
                null,
                null,
                'assignment'
            );
            $cloned = true;
        }

        if ($needsQuiz && $byType->has('quiz')) {
            $this->persistQuestionItems(
                $run,
                $course,
                $byType->get('quiz')->map(fn ($b) => $b->payload ?? [
                    'title' => $b->title,
                    'marks' => $b->marks,
                    'choices' => collect($b->choices ?? [])->pluck('label')->all(),
                    'correct_index' => 0,
                ])->all(),
                null,
                $quiz,
                null,
                'quiz'
            );
            $cloned = true;
        }

        if ($needsExam && $byType->has('exam')) {
            $this->persistQuestionItems(
                $run,
                $course,
                $byType->get('exam')->map(fn ($b) => $b->payload ?? [
                    'title' => $b->title,
                    'marks' => $b->marks,
                    'choices' => collect($b->choices ?? [])->pluck('label')->all(),
                    'correct_index' => 0,
                ])->all(),
                null,
                null,
                $exam,
                'exam'
            );
            $cloned = true;
        }

        if ($cloned) {
            $run->addProgressEvent('info', "Reused question bank for {$course->code} (no Gemini call).");
        }

        return $cloned;
    }

    private function persistQuestionItems(
        AiTranscriptRun $run,
        Course $course,
        array $items,
        ?Assignment $assignment,
        ?Quiz $quiz,
        ?Exam $exam,
        string $type
    ): void {
        if (!isset($items[0])) {
            $items = [$items];
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $choices = array_values($item['choices'] ?? ['Option A', 'Option B', 'Option C', 'Option D']);
            $correctIndex = (int) ($item['correct_index'] ?? 0);
            $structuredChoices = [];

            foreach ($choices as $i => $label) {
                $structuredChoices[] = [
                    'id' => $i + 1,
                    'label' => is_array($label) ? ($label['label'] ?? 'Option') : $label,
                    'is_correct' => $i === $correctIndex,
                ];
            }

            $question = Question::create([
                'title' => $item['title'] ?? "Question on {$course->name}",
                'type' => 'radio',
                'marks' => (int) ($item['marks'] ?? 5),
                'choices' => $structuredChoices,
                'assignment_id' => $assignment?->id,
                'quiz_id' => $quiz?->id,
                'exam_id' => $exam?->id,
            ]);

            AiQuestionBank::create([
                'ai_transcript_run_id' => $run->id,
                'course_id' => $course->id,
                'question_id' => $question->id,
                'assessment_type' => $type,
                'title' => $question->title,
                'question_type' => 'radio',
                'marks' => $question->marks,
                'choices' => $structuredChoices,
                'payload' => $item,
            ]);

            $run->increment('questions_saved');
        }
    }

    private function fallbackQuestions(Course $course, int $count): array
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = [
                'title' => "Explain a core concept in {$course->name} (Q{$i})",
                'marks' => 5,
                'choices' => ['Correct answer', 'Wrong 1', 'Wrong 2', 'Wrong 3'],
                'correct_index' => 0,
            ];
        }

        return $items;
    }

    private function buildBotSubmissionRows(
        Student $student,
        Modules $module,
        float $coursePercent,
        $now,
        ?array $exactMarks = null
    ): array {
        $module->load(['assignments.questions', 'quizzes.questions', 'exams.questions', 'course']);
        $rows = [];
        $courseId = (int) ($module->course_id ?? 0);

        foreach ($module->assignments as $assessment) {
            $rows[] = $this->botSubmissionRow(
                $student->id,
                'assignment',
                $assessment->id,
                null,
                null,
                $coursePercent,
                $assessment->questions,
                $now,
                $courseId,
                $exactMarks['assignment'] ?? null
            );
        }

        foreach ($module->quizzes as $assessment) {
            $rows[] = $this->botSubmissionRow(
                $student->id,
                'quiz',
                null,
                $assessment->id,
                null,
                $coursePercent,
                $assessment->questions,
                $now,
                $courseId,
                $exactMarks['quiz'] ?? null
            );
        }

        foreach ($module->exams as $assessment) {
            $rows[] = $this->botSubmissionRow(
                $student->id,
                'exam',
                null,
                null,
                $assessment->id,
                $coursePercent,
                $assessment->questions,
                $now,
                $courseId,
                $exactMarks['exam'] ?? null
            );
        }

        return $rows;
    }

    private function botSubmissionRow(
        int $studentId,
        string $type,
        ?int $assignmentId,
        ?int $quizId,
        ?int $examId,
        float $coursePercent,
        $questions,
        $now,
        int $courseId = 0,
        ?int $exactMarks = null
    ): array {
        $marksObtained = $exactMarks ?? $this->marksForPercentage($coursePercent, $type, $courseId);

        return [
            'student_id' => $studentId,
            'answers' => json_encode($this->buildBotAnswers($questions, $marksObtained)),
            'marks_obtained' => $marksObtained,
            'assignment_id' => $assignmentId,
            'quiz_id' => $quizId,
            'exam_id' => $examId,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function buildBotAnswers($questions, int $totalMarks): array
    {
        $answers = [];
        $questions = $questions ?? collect();

        if ($questions->isEmpty()) {
            return [[
                'question_id' => 0,
                'type' => 'open',
                'answer' => 'Auto-marked by USJ AI Bot',
                'marks_obtained' => $totalMarks,
                'source' => 'ai_bot',
            ]];
        }

        $perQuestion = max(1, (int) floor($totalMarks / max(1, $questions->count())));
        $remainder = $totalMarks;

        foreach ($questions as $q) {
            $marks = min($remainder, $perQuestion);
            $remainder -= $marks;

            $correctId = null;
            if (is_array($q->choices)) {
                foreach ($q->choices as $choice) {
                    if (!empty($choice['is_correct'])) {
                        $correctId = $choice['id'] ?? 1;
                        break;
                    }
                }
            }

            $answers[] = [
                'question_id' => $q->id,
                'type' => $q->type,
                'answer' => $correctId ?? 'Auto-marked by USJ AI Bot',
                'marks_obtained' => $marks,
                'source' => 'ai_bot',
            ];
        }

        if ($remainder > 0 && !empty($answers)) {
            $answers[0]['marks_obtained'] += $remainder;
        }

        return $answers;
    }
}
