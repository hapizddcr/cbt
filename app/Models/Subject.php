<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Models\Concerns\LogsActivity as LogsActivityTrait;

class Subject extends Model
{
    use HasFactory, LogsActivityTrait, SoftDeletes;

    protected $fillable = ['code', 'name', 'group', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static $logAttributes = ['code', 'name', 'group', 'is_active'];

    public function classrooms(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_subject_teacher')
            ->using(ClassroomSubjectTeacher::class)
            ->withPivot('teacher_id')
            ->withTimestamps();
    }

    public function teachers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'classroom_subject_teacher')
            ->withPivot('classroom_id')
            ->withTimestamps();
    }

    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
