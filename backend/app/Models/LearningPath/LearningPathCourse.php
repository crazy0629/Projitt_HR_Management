<?php

namespace App\Models\LearningPath;

use Illuminate\Database\Eloquent\Relations\Pivot;

class LearningPathCourse extends Pivot
{
    protected $table = 'learning_path_courses';

    protected $fillable = [
        'learning_path_id',
        'course_id',
        'order_index',
        'is_required',
        'completion_criteria',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
