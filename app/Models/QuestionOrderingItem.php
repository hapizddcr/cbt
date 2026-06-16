<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOrderingItem extends Model
{
    protected $fillable = ['question_id', 'content', 'correct_order', 'display_order'];

    protected $casts = [
        'correct_order' => 'integer',
        'display_order' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
