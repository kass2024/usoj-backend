<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modules extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'class_year_id',
        'user_id',
        'course_id',
        'semester'
    ];

    /* ================== Core Relations (camelCase) ================== */

    /** The course this module belongs to */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /** The instructor/teacher assigned to this module */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The class year this module belongs to */
    public function classYear()
    {
        return $this->belongsTo(ClassYear::class, 'class_year_id');
    }

    /** The academic year this module belongs to */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /** All assignments for this module */
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'module_id');
    }

    /** All quizzes for this module */
    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'module_id');
    }

    /** All exams for this module */
    public function exams()
    {
        return $this->hasMany(Exam::class, 'module_id');
    }

    /** All lessons for this module */
    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'module_id');
    }

    /* ============== Backward-compatible snake_case aliases ============== */
    // These let existing code call with('class_year') / with('academic_year') safely.

    public function class_year()
    {
        return $this->classYear();
    }

    public function academic_year()
    {
        return $this->academicYear();
    }

    /* ------------------- Query Scopes ------------------- */

    /** Filter modules by semester */
    public function scopeBySemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    /** Filter modules by academic year */
    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /** Filter modules by class year */
    public function scopeByClassYear($query, $classYearId)
    {
        return $query->where('class_year_id', $classYearId);
    }
}
