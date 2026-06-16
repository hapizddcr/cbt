<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Exam extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPE_DAILY = 'daily';
    public const TYPE_MIDTERM = 'midterm';
    public const TYPE_FINAL = 'final';
    public const TYPE_TRYOUT = 'tryout';
    public const TYPE_QUIZ = 'quiz';

    protected $fillable = [
        'subject_id', 'question_bank_id', 'creator_id',
        'title', 'description', 'type', 'duration_minutes',
        'total_questions', 'max_score', 'passing_score',
        'question_per_page', 'shuffle_questions', 'shuffle_options',
        'show_result', 'show_answer', 'allow_review', 'max_attempts',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'total_questions' => 'integer',
        'question_per_page' => 'integer',
        'max_attempts' => 'integer',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'show_result' => 'boolean',
        'show_answer' => 'boolean',
        'allow_review' => 'boolean',
        'is_active' => 'boolean',
        'max_score' => 'decimal:2',
        'passing_score' => 'decimal:2',
    ];

    protected static $logAttributes = ['title', 'type', 'duration_minutes', 'is_active'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot('order', 'score')
            ->orderByPivot('order');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isInProgress(): bool
    {
        return $this->sessions()
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->exists();
    }
}
