<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassYear extends Model
{
    use HasFactory;
    protected $fillable = ['year_number', 'year_name', 'department_id', 'degree_level_id', 'semester'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function degree_level()
    {
        return $this->belongsTo(DegreeLevel::class);
    }
     public function academic_year()
    {
        return $this->belongsTo(AcademicYear::class);
    }
     public function modules()
    {
        return $this->hasMany(Modules::class); // adjust Module to your actual model
    }
     




    

}
