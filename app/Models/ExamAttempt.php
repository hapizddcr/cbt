<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_GRADED = 'graded';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'exam_id', 'exam_session_id', 'student_id', 'participant_number',
        'status', 'started_at', 'submitted_at', 'ends_at',
        'time_remaining_seconds', 'score', 'score_auto', 'score_manual',
        'percentage', 'is_passed', 'violation_count', 'ip_address', 'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'ends_at' => 'datetime',
        'time_remaining_seconds' => 'integer',
        'violation_count' => 'integer',
        'score' => 'decimal:2',
        'score_auto' => 'decimal:2',
        'score_manual' => 'decimal:2',
        'percentage' => 'decimal:2',
        'is_passed' => 'boolean',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(ExamViolation::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS && $this->ends_at->isFuture();
    }

    public function hasExpired(): bool
    {
        return $this->ends_at->isPast() && $this->status === self::STATUS_IN_PROGRESS;
    }

    public function answeredCount(): int
    {
        return $this->answers()->whereNotNull('answered_at')->count();
    }
}
