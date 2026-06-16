<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class QuestionBank extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'subject_id', 'creator_id', 'name', 'description',
        'level', 'is_shared',
    ];

    protected $casts = ['is_shared' => 'boolean'];

    protected static $logAttributes = ['name', 'level', 'is_shared'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function getQuestionCountAttribute(): int
    {
        return $this->questions()->count();
    }
}
