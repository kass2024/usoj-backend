<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'description', 'credits', 'code', 'status', 'department_id', 'degree_level_id', 'year_index', 'semester'];
         public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function degreeLevel()
    {
        return $this->belongsTo(DegreeLevel::class, 'degree_level_id');
    }

    public function modules()
    {
        return $this->hasMany(Modules::class, 'course_id');
    }

    public function scopeActive($q){ return $q->where('status','active'); }
}
