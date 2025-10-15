<?php

namespace App\Models\LMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonQuizOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'text',
        'is_correct',
        'order_index',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'order_index' => 'integer',
    ];

    protected $attributes = [
        'is_correct' => false,
        'order_index' => 0,
    ];

    // Relationships
    public function question(): BelongsTo
    {
        return $this->belongsTo(LessonQuizQuestion::class, 'question_id');
    }

    // Scopes
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }

    public function scopeOrderByIndex($query)
    {
        return $query->orderBy('order_index');
    }

    // Helper methods
    public function isCorrect(): bool
    {
        return $this->is_correct;
    }

    public function isIncorrect(): bool
    {
        return ! $this->is_correct;
    }
}
