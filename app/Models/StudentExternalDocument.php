<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StudentExternalDocument extends Model
{
    protected $fillable = [
        'student_id',
        'type',
        'path',
        'original_name',
        'mime',
        'size',
        'uploaded_via_link_id',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploadLink(): BelongsTo
    {
        return $this->belongsTo(DocumentUploadLink::class, 'uploaded_via_link_id');
    }

    public function existsOnDisk(): bool
    {
        return $this->path && Storage::disk('public')->exists($this->path);
    }
}
