<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Question extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_COMPLEX_MC = 'complex_mc';
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_SHORT_ANSWER = 'short_answer';
    public const TYPE_ESSAY = 'essay';
    public const TYPE_MATCHING = 'matching';
    public const TYPE_ORDERING = 'ordering';

    public const TYPES = [
        self::TYPE_MULTIPLE_CHOICE => 'Pilihan Ganda',
        self::TYPE_COMPLEX_MC => 'Pilihan Ganda Kompleks',
        self::TYPE_TRUE_FALSE => 'Benar / Salah',
        self::TYPE_SHORT_ANSWER => 'Isian Singkat',
        self::TYPE_ESSAY => 'Essai',
        self::TYPE_MATCHING => 'Menjodohkan',
        self::TYPE_ORDERING => 'Mengurutkan',
    ];

    protected $fillable = [
        'question_bank_id', 'type', 'content', 'image_path',
        'explanation', 'default_score', 'difficulty', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_score' => 'decimal:2',
    ];

    protected static $logAttributes = ['type', 'difficulty', 'is_active'];

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function matchingPairs(): HasMany
    {
        return $this->hasMany(QuestionMatchingPair::class)->orderBy('order');
    }

    public function orderingItems(): HasMany
    {
        return $this->hasMany(QuestionOrderingItem::class)->orderBy('display_order');
    }

    public function correctOptions(): HasMany
    {
        return $this->options()->where('is_correct', true);
    }

    public function isAutoGradable(): bool
    {
        return in_array($this->type, [
            self::TYPE_MULTIPLE_CHOICE,
            self::TYPE_COMPLEX_MC,
            self::TYPE_TRUE_FALSE,
            self::TYPE_SHORT_ANSWER,
            self::TYPE_MATCHING,
            self::TYPE_ORDERING,
        ], true);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
