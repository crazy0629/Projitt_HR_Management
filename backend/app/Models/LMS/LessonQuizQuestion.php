<?php

namespace App\Models\LMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonQuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'text',
        'explanation',
        'order_index',
        'type',
        'weight',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'weight' => 'integer',
    ];

    protected $attributes = [
        'order_index' => 0,
        'type' => 'single',
        'weight' => 1,
    ];

    // Relationships
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(LessonQuiz::class, 'quiz_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(LessonQuizOption::class, 'question_id');
    }

    // Scopes
    public function scopeOrderByIndex($query)
    {
        return $query->orderBy('order_index');
    }

    public function scopeWithOptions($query)
    {
        return $query->with(['options' => function ($q) {
            $q->orderBy('order_index');
        }]);
    }

    // Helper methods
    public function isSingleChoice(): bool
    {
        return $this->type === 'single';
    }

    public function isMultipleChoice(): bool
    {
        return $this->type === 'multi';
    }

    public function getCorrectOptions(): array
    {
        return $this->options()->where('is_correct', true)->pluck('id')->toArray();
    }

    public function getCorrectOptionIds(): array
    {
        return $this->getCorrectOptions();
    }

    public function hasCorrectAnswer(): bool
    {
        return $this->options()->where('is_correct', true)->exists();
    }

    public function getOptionsCount(): int
    {
        return $this->options()->count();
    }

    public function getCorrectOptionsCount(): int
    {
        return $this->options()->where('is_correct', true)->count();
    }

    public function validateAnswer(array $selectedOptionIds): bool
    {
        $correctOptionIds = $this->getCorrectOptionIds();

        // For single choice, only one option should be selected and it should be correct
        if ($this->isSingleChoice()) {
            return count($selectedOptionIds) === 1 &&
                   in_array($selectedOptionIds[0], $correctOptionIds);
        }

        // For multiple choice, all correct options should be selected and no incorrect ones
        if ($this->isMultipleChoice()) {
            return empty(array_diff($correctOptionIds, $selectedOptionIds)) &&
                   empty(array_diff($selectedOptionIds, $correctOptionIds));
        }

        return false;
    }

    public function getDisplayData(bool $includeCorrectAnswers = false): array
    {
        $data = [
            'id' => $this->id,
            'text' => $this->text,
            'type' => $this->type,
            'weight' => $this->weight,
            'options' => $this->options->map(function ($option) use ($includeCorrectAnswers) {
                return [
                    'id' => $option->id,
                    'text' => $option->text,
                    'is_correct' => $includeCorrectAnswers ? $option->is_correct : null,
                ];
            })->toArray(),
        ];

        if ($includeCorrectAnswers && $this->explanation) {
            $data['explanation'] = $this->explanation;
        }

        return $data;
    }
}
