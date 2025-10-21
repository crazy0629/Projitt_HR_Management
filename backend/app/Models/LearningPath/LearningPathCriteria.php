<?php

namespace App\Models\LearningPath;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningPathCriteria extends Model
{
    use HasFactory;

    protected $table = 'learning_path_criteria';

    protected $fillable = [
        'learning_path_id',
        'field',
        'operator',
        'value',
        'connector',
        'group_index',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    // Scopes
    public function scopeByGroup($query, $groupIndex)
    {
        return $query->where('group_index', $groupIndex);
    }

    // Helper methods
    public function getDecodedValue()
    {
        // Try to decode JSON, otherwise return as string
        $decoded = json_decode($this->value, true);

        return $decoded !== null ? $decoded : $this->value;
    }

    public function setValueAttribute($value)
    {
        // If array, encode as JSON
        $this->attributes['value'] = is_array($value) ? json_encode($value) : $value;
    }
}
