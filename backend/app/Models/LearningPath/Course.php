<?php

namespace App\Models\LearningPath;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'courses';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'learning_objectives',
        'difficulty_level',
        'duration_hours',
        'price',
        'instructor',
        'thumbnail_url',
        'video_url',
        'materials',
        'status',
        'rating',
        'enrollments_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'materials' => 'array',
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Auto-generate slug on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title).'-'.Str::random(6);
            }
        });

        static::updating(function ($course) {
            if ($course->isDirty('title') && empty($course->slug)) {
                $course->slug = Str::slug($course->title).'-'.Str::random(6);
            }
        });
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function learningPaths()
    {
        return $this->belongsToMany(LearningPath::class, 'learning_path_courses', 'course_id', 'learning_path_id')
            ->withPivot(['order_index', 'is_required', 'completion_criteria'])
            ->withTimestamps();
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByDifficulty($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }

    public function scopeByInstructor($query, $instructor)
    {
        return $query->where('instructor', 'LIKE', "%{$instructor}%");
    }

    public function scopeMinRating($query, $rating)
    {
        return $query->where('rating', '>=', $rating);
    }

    // Helper methods
    public function isPublished()
    {
        return $this->status === 'published';
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isFree()
    {
        return $this->price == 0;
    }

    public function getFormattedPriceAttribute()
    {
        return $this->price > 0 ? '$'.number_format($this->price, 2) : 'Free';
    }

    public function getDifficultyLabelAttribute()
    {
        return ucfirst($this->difficulty_level);
    }

    public function getFormattedDurationAttribute()
    {
        if ($this->duration_hours < 1) {
            return round($this->duration_hours * 60).' minutes';
        }

        return $this->duration_hours.' hour'.($this->duration_hours > 1 ? 's' : '');
    }
}
