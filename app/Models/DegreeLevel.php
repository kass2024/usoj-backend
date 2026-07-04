<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DegreeLevel extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'status', 'program_id', 'description'];
    // A degree level belongs to a program
    public function program()
    {
        return $this->belongsTo(Program::class);
    }
    public function classes()
    {
        return $this->hasMany(ClassYear::class);
    }

    public function classesForDepartmemt($departmentId)
    {
        return $this->hasMany(ClassYear::class)->where('department_id', $departmentId);
    }

      public function scopeActive($q){ return $q->where('status','active'); }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_degree_level')
                    ->withTimestamps();
    }

}
