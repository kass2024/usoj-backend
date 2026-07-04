<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'abbr', 'slug', 'description', 'program_id', 'status'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
  
      public function departments()
    {
        return $this->hasMany(Department::class);
    }
    public function courses()
    {
        return $this->hasMany(Course::class);
    }
     public function school()
    {
        return $this->belongsTo(School::class);
    }
    public function scopeActive($q){ return $q->where('status','active'); }
}
