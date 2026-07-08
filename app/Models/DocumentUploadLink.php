<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class DocumentUploadLink extends Model
{
    protected $fillable = [
        'name',
        'username',
        'password',
        'password_plain',
        'slug',
        'is_active',
        'expires_at',
        'created_by',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentExternalDocument::class, 'uploaded_via_link_id');
    }

    public function setPasswordAttribute($value): void
    {
        if (!$value) {
            return;
        }

        $this->attributes['password'] = str_starts_with((string) $value, '$2y$')
            ? $value
            : Hash::make($value);
    }

    public function isUsable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function checkPassword(string $plain): bool
    {
        return Hash::check($plain, $this->password);
    }
}
