<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_transcript_runs', function (Blueprint $table) {
            $table->decimal('target_percentage', 5, 2)->nullable()->after('target_cgpa');
        });
    }

    public function down(): void
    {
        Schema::table('ai_transcript_runs', function (Blueprint $table) {
            $table->dropColumn('target_percentage');
        });
    }
};
