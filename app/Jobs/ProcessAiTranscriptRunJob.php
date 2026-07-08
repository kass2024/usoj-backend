<?php

namespace App\Jobs;

use App\Models\AiTranscriptRun;
use App\Services\TranscriptAiStudioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAiTranscriptRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Allow long runs for large programs (63+ courses). */
    public int $timeout = 7200;

    public int $tries = 1;

    public function __construct(public AiTranscriptRun $run) {}

    public function handle(TranscriptAiStudioService $studio): void
    {
        $limit = (int) config('gemini.run_max_execution_time', 0);
        if ($limit === 0) {
            @set_time_limit(0);
            @ini_set('max_execution_time', '0');
        } else {
            @set_time_limit($limit);
            @ini_set('max_execution_time', (string) $limit);
        }

        $studio->processRun($this->run->fresh());
    }
}
