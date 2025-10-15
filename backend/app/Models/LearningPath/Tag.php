<?php

namespace App\Models\LearningPath;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'usage_count',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Auto-generate slug on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name')) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function learningPaths()
    {
        return $this->belongsToMany(LearningPath::class, 'learning_path_tags', 'tag_id', 'learning_path_id')
            ->withTimestamps();
    }

    // Scopes
    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    public function scopeByColor($query, $color)
    {
        return $query->where('color', $color);
    }

    // Helper methods
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    public function decrementUsage()
    {
        $this->decrement('usage_count');
    }
}
