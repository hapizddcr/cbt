<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Student extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id', 'nisn', 'nis', 'name', 'gender',
        'birth_place', 'birth_date', 'address', 'phone',
        'parent_name', 'parent_phone', 'photo_path',
    ];

    protected $casts = ['birth_date' => 'date'];

    protected static $logAttributes = ['nisn', 'nis', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_student')
            ->withPivot('student_number')
            ->withTimestamps();
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->nis . ' - ' . $this->name;
    }
}
