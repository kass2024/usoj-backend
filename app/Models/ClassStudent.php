<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassStudent extends Model
{
    use HasFactory;
    protected $fillable = ['academic_year_id', 'student_id', 'class_year_id'];
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class_year()
    {
        return $this->belongsTo(ClassYear::class); // adjust ClassYear to your actual model
    }
   

  
}
