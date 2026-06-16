<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id', 'name', 'start_time', 'end_time',
        'duration_minutes', 'token_lifetime_minutes', 'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'token_lifetime_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(ExamRoom::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'exam_session_student')
            ->withPivot('exam_room_id', 'participant_number', 'seat_number')
            ->withTimestamps();
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(ExamToken::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isOngoing(): bool
    {
        $now = now();
        return $this->is_active
            && $this->start_time->lte($now)
            && $this->end_time->gte($now);
    }

    public function getActiveTokenAttribute(): ?ExamToken
    {
        return $this->tokens()
            ->where('is_active', true)
            ->where('issued_at', '<=', now())
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();
    }
}
