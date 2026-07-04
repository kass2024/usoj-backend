<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id',
        'answers',
        'marks_obtained',
        'quiz_id',
        'assignment_id',
        'exam_id',
    ];
    // quiz
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
    // assignment
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
    // exam
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }


    protected $casts = [
        'answers' => 'array',
    ];
}
