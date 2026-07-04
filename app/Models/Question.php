<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'type',
        'marks',
        'choices',
        'quiz_id',
        'assignment_id',
        'exam_id',
    ];
    protected $casts = [
        'choices' => 'array',
    ];
}
