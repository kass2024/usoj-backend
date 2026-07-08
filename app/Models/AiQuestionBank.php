<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiQuestionBank extends Model
{
    protected $table = 'ai_question_bank';

    protected $fillable = [
        'ai_transcript_run_id',
        'course_id',
        'question_id',
        'assessment_type',
        'title',
        'question_type',
        'marks',
        'choices',
        'payload',
    ];

    protected $casts = [
        'choices' => 'array',
        'payload' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
