<?php

namespace App\Models\Question;

use App\Models\Job\Job;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionAnswer extends Model
{
    use SoftDeletes;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'question_answers';

    /**
     * Mass assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question_id',
        'job_id',
        'user_id',
        'answer',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Casts.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'answer' => 'string',
    ];

    /**
     * Hidden fields from JSON.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    /**
     * Relationship: Belongs to Question.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Relationship: Belongs to Job.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Relationship: Belongs to User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retrieve a single question answer with related models.
     */
    public static function singleObject(int $id): ?self
    {
        return self::with(['question', 'job', 'user'])->find($id);
    }
}
