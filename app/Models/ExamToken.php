<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id', 'exam_room_id', 'token',
        'issued_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ExamRoom::class, 'exam_room_id');
    }

    public function isValid(): bool
    {
        return $this->is_active
            && $this->issued_at->lte(now())
            && $this->expires_at->gte(now());
    }
}
