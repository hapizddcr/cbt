<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id', 'name', 'capacity', 'supervisor_id',
    ];

    protected $casts = ['capacity' => 'integer'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(ExamSessionStudent::class);
    }
}
