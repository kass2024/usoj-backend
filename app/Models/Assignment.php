<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'module_id', 'due_date'];
    public function module()
    {
        return $this->belongsTo(Modules::class);
    }
    // questions
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
    // submissions
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
    // student submissions
    public function student_submission($student_id)
    {
        return $this->submissions()->where('student_id', $student_id)->first();
    }
     public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    // (Optional) If you ALSO relate to modules/lessons, keep these too:


    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }
    protected $casts = [
        'due_date' => 'datetime',
    ];
}
