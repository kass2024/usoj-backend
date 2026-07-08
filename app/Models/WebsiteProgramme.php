<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteProgramme extends Model
{
    protected $fillable = [
        'name',
        'duration',
        'mode',
        'category',
        'status',
        'slug',
        'description',
        'sort_order',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
