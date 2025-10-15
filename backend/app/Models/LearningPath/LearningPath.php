<?php

namespace App\Models\LearningPath;

use App\Models\Role\Role;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LearningPath extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'learning_paths';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'begin_month',
        'end_month',
        'status',
        'estimated_duration_hours',
        'metadata',
        'created_by',
        'updated_by',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Auto-generate slug on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($learningPath) {
            if (empty($learningPath->slug)) {
                $learningPath->slug = Str::slug($learningPath->name).'-'.Str::random(6);
            }
        });

        static::updating(function ($learningPath) {
            if ($learningPath->isDirty('name') && empty($learningPath->slug)) {
                $learningPath->slug = Str::slug($learningPath->name).'-'.Str::random(6);
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

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'learning_path_roles', 'learning_path_id', 'role_id')
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'learning_path_tags', 'learning_path_id', 'tag_id')
            ->withTimestamps();
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'learning_path_courses', 'learning_path_id', 'course_id')
            ->withPivot(['order_index', 'is_required', 'completion_criteria'])
            ->withTimestamps()
            ->orderBy('learning_path_courses.order_index');
    }

    public function criteria()
    {
        return $this->hasMany(LearningPathCriteria::class, 'learning_path_id');
    }

    public function assignments()
    {
        return $this->hasMany(LearningPathAssignment::class, 'learning_path_id');
    }

    public function logs()
    {
        return $this->hasMany(LearningPathLog::class, 'learning_path_id');
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

    public function scopeByRole($query, $roleId)
    {
        return $query->whereHas('roles', function ($q) use ($roleId) {
            $q->where('role_id', $roleId);
        });
    }

    public function scopeByTag($query, $tagId)
    {
        return $query->whereHas('tags', function ($q) use ($tagId) {
            $q->where('tag_id', $tagId);
        });
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

    public function canBePublished()
    {
        return $this->status === 'draft' && $this->courses()->count() > 0;
    }

    public function getTotalDurationAttribute()
    {
        return $this->courses()->sum('duration_hours') ?? 0;
    }

    public function getAssignedEmployeesCountAttribute()
    {
        return $this->assignments()->count();
    }

    public function getCompletedAssignmentsCountAttribute()
    {
        return $this->assignments()->where('status', 'completed')->count();
    }
}
