<?php

namespace App\Models\Coding;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodingSubmissionReview extends Model
{
    protected $fillable = [
        'submission_id',
        'reviewer_id',
        'score_adjustment',
        'comment',
        'rubric_scores',
    ];

    protected $casts = [
        'score_adjustment' => 'float',
        'rubric_scores' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(CodingSubmission::class, 'submission_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
