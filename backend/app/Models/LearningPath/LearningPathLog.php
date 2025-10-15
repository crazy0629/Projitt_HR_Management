<?php

namespace App\Models\LearningPath;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningPathLog extends Model
{
    use HasFactory;

    protected $table = 'learning_path_logs';

    protected $fillable = [
        'learning_path_id',
        'user_id',
        'action',
        'payload',
        'previous_data',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
        'previous_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public static function logAction($learningPathId, $userId, $action, $payload = null, $previousData = null)
    {
        return static::create([
            'learning_path_id' => $learningPathId,
            'user_id' => $userId,
            'action' => $action,
            'payload' => $payload,
            'previous_data' => $previousData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
