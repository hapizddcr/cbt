<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionMatchingPair extends Model
{
    protected $fillable = [
        'question_id', 'left_content', 'right_content',
        'left_image_path', 'right_image_path', 'order',
    ];

    protected $casts = ['order' => 'integer'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
