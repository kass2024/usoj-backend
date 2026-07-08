<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCourseMaterial extends Model
{
    protected $fillable = [
        'ai_transcript_run_id',
        'student_id',
        'course_id',
        'module_id',
        'title',
        'pdf_path',
        'content_preview',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AiTranscriptRun::class, 'ai_transcript_run_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
