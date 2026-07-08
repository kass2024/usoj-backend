<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE ai_transcript_runs MODIFY COLUMN status "
            . "ENUM('pending', 'running', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "UPDATE ai_transcript_runs SET status = 'failed' WHERE status = 'cancelled'"
        );
        DB::statement(
            "ALTER TABLE ai_transcript_runs MODIFY COLUMN status "
            . "ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending'"
        );
    }
};
