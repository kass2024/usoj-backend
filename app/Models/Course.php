<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'description', 'credits', 'code', 'status', 'department_id'];
         public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function scopeActive($q){ return $q->where('status','active'); }
}
