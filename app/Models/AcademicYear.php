<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class AcademicYear extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name', 'semester', 'is_active', 'start_date', 'end_date'];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static $logAttributes = ['name', 'semester', 'is_active'];

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function current(): ?self
    {
        return self::active()->first();
    }
}
