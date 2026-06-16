<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Classroom extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'academic_year_id', 'name', 'grade', 'major',
        'homeroom_teacher_id', 'capacity', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean', 'capacity' => 'integer'];

    protected static $logAttributes = ['name', 'grade', 'major', 'is_active'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'classroom_student')
            ->withPivot('student_number')
            ->withTimestamps();
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'classroom_subject_teacher')
            ->withPivot('teacher_id')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getStudentCountAttribute(): int
    {
        return $this->students()->count();
    }
}
