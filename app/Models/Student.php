<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fname',
        'lname',
        'email',
        'password',
        'phone',
        'reg_number',
        'status',
        'department_id',
        'degree_level_id',
        'profile_img'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function degree_level()
    {
        return $this->belongsTo(DegreeLevel::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'class_students', 'student_id', 'course_id');
    }
    public function classYears()
    {
        return $this->belongsToMany(ClassYear::class, 'class_students', 'student_id', 'class_year_id');
    }

    // Define the relationship between Student and AcademicYear (via class_students pivot table)
    public function academicYears()
    {
        return $this->belongsToMany(AcademicYear::class, 'class_students', 'student_id', 'academic_year_id');
    }
    
    public function class_students()
    {
        return $this->hasMany(ClassStudent::class); // adjust ClassStudent to your actual model
    }

 
  public function degreeLevel(){ return $this->belongsTo(DegreeLevel::class); }
}
