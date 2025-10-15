<?php

namespace App\Models\Talent;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'author_id',
        'body',
        'visibility',
        'is_sensitive',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Scopes
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByVisibility($query, $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }

    public function scopeNonSensitive($query)
    {
        return $query->where('is_sensitive', false);
    }

    // Helper Methods
    public function getVisibilityLabel()
    {
        return match ($this->visibility) {
            'hr_only' => 'HR Only',
            'manager_chain' => 'Manager Chain',
            'employee_visible' => 'Employee Visible',
            default => 'Unknown'
        };
    }

    public function getVisibilityColor()
    {
        return match ($this->visibility) {
            'hr_only' => 'danger',
            'manager_chain' => 'warning',
            'employee_visible' => 'success',
            default => 'secondary'
        };
    }

    public function canBeViewedBy($userId)
    {
        if ($this->author_id === $userId) {
            return true;
        }

        if ($this->visibility === 'hr_only') {
            // Only HR can view
            return User::find($userId)?->hasRole('hr') ?? false;
        }

        if ($this->visibility === 'manager_chain') {
            // Employee's manager chain can view
            return User::find($userId)?->isInManagerChainOf($this->employee_id) ?? false;
        }

        if ($this->visibility === 'employee_visible') {
            // Employee themselves can view
            return $this->employee_id === $userId;
        }

        return false;
    }
}
