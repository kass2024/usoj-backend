<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'abbr', 'slug', 'description', 'school_id', 'status'];
    public function school()
    {
        return $this->belongsTo(School::class);
    }
    public function scopeActive($q){ return $q->where('status','active'); }

    // Only departments that actually have courses
    public function scopeWithCourses($q, $activeCoursesOnly = true){
        return $q->whereHas('courses', function($cq) use ($activeCoursesOnly) {
            if ($activeCoursesOnly) $cq->where('status','active');
        });
    }


    public function courses(){ return $this->hasMany(Course::class); }
    public function degreeLevels()
{
    // pivot table name: department_degree_level (recommended)
    return $this->belongsToMany(DegreeLevel::class, 'department_degree_level')
                ->withTimestamps();
}
 public function students() { return $this->hasMany(Student::class); }
}
