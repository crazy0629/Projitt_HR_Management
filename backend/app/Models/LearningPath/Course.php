<?php

namespace App\Models\LearningPath;

use App\Models\Category;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
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
        'category_id',
        'type',
        'url',
        'file_path',
        'file_type',
        'file_size',
        'difficulty_level',
        'duration_minutes',
        'duration_hours', // Legacy support
        'price',
        'instructor',
        'thumbnail_url',
        'video_url', // Legacy support
        'materials',
        'status',
        'rating',
        'enrollments_count',
        'learning_paths_count',
        'assigned_users_count',
        'last_used_at',
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

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'course_tags', 'course_id', 'tag_id')
            ->withTimestamps();
    }

    public function learningPaths()
    {
        return $this->belongsToMany(LearningPath::class, 'learning_path_courses', 'course_id', 'learning_path_id')
            ->withPivot(['order_index', 'is_required', 'completion_criteria'])
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    // Legacy scopes for backward compatibility
    public function scopePublished($query)
    {
        return $query->where('status', 'active'); // Map to active
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'archived'); // Map to archived
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeWithTag($query, $tagId)
    {
        return $query->whereHas('tags', function ($q) use ($tagId) {
            $q->where('tag_id', $tagId);
        });
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
        // Prefer duration_minutes over legacy duration_hours
        $minutes = $this->duration_minutes ?? ($this->duration_hours ? $this->duration_hours * 60 : 0);

        if ($minutes < 60) {
            return $minutes.' min';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours.' hour'.($hours > 1 ? 's' : '');
        }

        return $hours.'h '.$remainingMinutes.'min';
    }

    // Course Library specific methods
    public function isExternalLink()
    {
        return $this->type === 'external_link';
    }

    public function isFileUpload()
    {
        return $this->type === 'file_upload';
    }

    public function getFileUrl()
    {
        if ($this->file_path) {
            return Storage::url($this->file_path);
        }

        return null;
    }

    public function getDisplayUrl()
    {
        return $this->isExternalLink() ? $this->url : $this->getFileUrl();
    }

    public function getFileSizeFormatted()
    {
        if (! $this->file_size) {
            return null;
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getTypeLabel()
    {
        return match ($this->type) {
            'external_link' => 'External Link',
            'file_upload' => 'File Upload',
            'video' => 'Video',
            'text' => 'Text',
            default => ucfirst($this->type)
        };
    }

    public function updateUsageStats()
    {
        $this->learning_paths_count = $this->learningPaths()->count();
        $this->assigned_users_count = $this->learningPaths()
            ->withCount('assignments')
            ->sum('assignments_count');
        $this->last_used_at = now();
        $this->save();
    }
}
