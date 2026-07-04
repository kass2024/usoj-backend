<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'status', 'description'];

    public function schools()
    {
        return $this->hasMany(School::class);
    }

     public function scopeActive($q){ return $q->where('status','active'); }


}

