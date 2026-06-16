<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Teacher extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id', 'nip', 'nuptk', 'name', 'gender',
        'birth_place', 'birth_date', 'address', 'phone', 'photo_path',
    ];

    protected $casts = ['birth_date' => 'date'];

    protected static $logAttributes = ['nip', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'classroom_subject_teacher')
            ->withPivot('classroom_id')
            ->withTimestamps();
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_subject_teacher')
            ->withPivot('subject_id')
            ->withTimestamps();
    }

    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class, 'creator_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'creator_id');
    }
}
