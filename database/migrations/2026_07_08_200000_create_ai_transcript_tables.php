<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_transcript_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('target_cgpa', 4, 2);
            $table->decimal('achieved_cgpa', 4, 2)->nullable();
            $table->unsignedSmallInteger('courses_processed')->default(0);
            $table->unsignedSmallInteger('materials_created')->default(0);
            $table->unsignedSmallInteger('questions_saved')->default(0);
            $table->unsignedSmallInteger('submissions_created')->default(0);
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->json('options')->nullable();
            $table->json('log')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_transcript_run_id')->nullable()->constrained('ai_transcript_runs')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->string('title');
            $table->string('pdf_path');
            $table->longText('content_preview')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_question_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_transcript_run_id')->nullable()->constrained('ai_transcript_runs')->nullOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->enum('assessment_type', ['assignment', 'quiz', 'exam']);
            $table->string('title');
            $table->string('question_type');
            $table->unsignedSmallInteger('marks')->default(1);
            $table->json('choices')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_question_bank');
        Schema::dropIfExists('ai_course_materials');
        Schema::dropIfExists('ai_transcript_runs');
    }
};
