<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_transcript_runs', function (Blueprint $table) {
            $table->json('progress')->nullable()->after('log');
        });
    }

    public function down(): void
    {
        Schema::table('ai_transcript_runs', function (Blueprint $table) {
            $table->dropColumn('progress');
        });
    }
};
