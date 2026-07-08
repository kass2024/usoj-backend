<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiTranscriptRun extends Model
{
    protected $fillable = [
        'student_id',
        'triggered_by',
        'target_cgpa',
        'target_percentage',
        'achieved_cgpa',
        'courses_processed',
        'materials_created',
        'questions_saved',
        'submissions_created',
        'status',
        'options',
        'log',
        'progress',
        'error_message',
    ];

    protected $casts = [
        'target_cgpa' => 'decimal:2',
        'target_percentage' => 'decimal:2',
        'achieved_cgpa' => 'decimal:2',
        'options' => 'array',
        'log' => 'array',
        'progress' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(AiCourseMaterial::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AiQuestionBank::class);
    }

    public function appendLog(string $message): void
    {
        $log = $this->log ?? [];
        $log[] = ['time' => now()->toDateTimeString(), 'message' => $message];
        $this->update(['log' => $log]);
    }

    public function initProgress(array $steps): void
    {
        $this->progress = [
            'percent' => 0,
            'current_step' => $steps[0]['id'] ?? null,
            'steps' => array_map(fn ($step) => [
                'id' => $step['id'],
                'label' => $step['label'],
                'status' => 'pending',
            ], $steps),
            'events' => [],
        ];
        $this->save();
    }

    public function setStepStatus(string $stepId, string $status, ?string $label = null): void
    {
        $this->refresh();
        $progress = $this->progress ?? ['steps' => [], 'events' => [], 'percent' => 0];
        $found = false;

        foreach ($progress['steps'] as &$step) {
            if ($step['id'] === $stepId) {
                $step['status'] = $status;
                if ($label !== null) {
                    $step['label'] = $label;
                }
                $found = true;
            } elseif ($status === 'active' && ($step['status'] ?? '') === 'active') {
                $step['status'] = 'done';
            }
        }
        unset($step);

        if (!$found && $label !== null) {
            $progress['steps'][] = [
                'id' => $stepId,
                'label' => $label,
                'status' => $status,
            ];
        }

        if ($status === 'active') {
            $progress['current_step'] = $stepId;
        }

        $progress['percent'] = $this->calculatePercent($progress['steps']);
        $this->update(['progress' => $progress]);
    }

    public function addProgressEvent(string $type, string $message, ?string $fallback = null): void
    {
        $this->refresh();
        $progress = $this->progress ?? ['steps' => [], 'events' => [], 'percent' => 0];
        $progress['events'][] = [
            'time' => now()->toDateTimeString(),
            'type' => $type,
            'message' => $message,
            'fallback' => $fallback,
        ];
        $this->update(['progress' => $progress]);

        $logLine = $message;
        if ($fallback) {
            $logLine .= " | Fallback: {$fallback}";
        }
        $this->appendLog($logLine);
    }

    public function setPrefetchProgress(int $completed, int $total): void
    {
        $this->refresh();
        $progress = $this->progress ?? ['steps' => [], 'events' => [], 'percent' => 0];
        $label = "Prefetching Gemini questions ({$completed}/{$total})";

        foreach ($progress['steps'] as &$step) {
            if ($step['id'] === 'prefetch_questions') {
                $step['label'] = $label;
                $step['status'] = $completed >= $total ? 'done' : 'active';
            }
        }
        unset($step);

        $progress['current_step'] = 'prefetch_questions';
        $progress['prefetch'] = ['completed' => $completed, 'total' => $total];
        $progress['percent'] = $this->calculatePercent($progress['steps'], $completed, $total);
        $this->update(['progress' => $progress]);
    }

    public function progressPayload(): array
    {
        $this->refresh();
        $progress = $this->progress ?? ['percent' => 0, 'steps' => [], 'events' => []];

        $steps = $progress['steps'] ?? [];
        $completedSteps = 0;
        foreach ($steps as $step) {
            if (($step['status'] ?? '') === 'done') {
                $completedSteps++;
            }
        }

        return [
            'id' => $this->id,
            'status' => $this->status,
            'percent' => (int) ($progress['percent'] ?? 0),
            'current_step' => $progress['current_step'] ?? null,
            'steps' => $steps,
            'events' => $progress['events'] ?? [],
            'completed_steps' => $completedSteps,
            'total_steps' => count($steps),
            'achieved_cgpa' => $this->achieved_cgpa,
            'target_percentage' => $this->target_percentage,
            'courses_processed' => $this->courses_processed,
            'materials_created' => $this->materials_created,
            'questions_saved' => $this->questions_saved,
            'submissions_created' => $this->submissions_created,
            'error_message' => $this->error_message,
            'done' => in_array($this->status, ['completed', 'failed'], true),
        ];
    }

    private function calculatePercent(array $steps, ?int $prefetchCompleted = null, ?int $prefetchTotal = null): int
    {
        if (empty($steps)) {
            return 0;
        }

        $total = count($steps);
        $done = 0.0;

        foreach ($steps as $step) {
            $status = $step['status'] ?? 'pending';
            if ($step['id'] === 'prefetch_questions' && $prefetchTotal !== null && $prefetchTotal > 0) {
                $done += min(1, $prefetchCompleted / $prefetchTotal);
                continue;
            }

            if ($status === 'done') {
                $done++;
            } elseif ($status === 'active') {
                $done += 0.5;
            } elseif ($status === 'warning') {
                $done += 0.9;
            }
        }

        return (int) min(100, round(($done / $total) * 100));
    }
}
