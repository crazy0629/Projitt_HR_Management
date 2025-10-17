<?php

namespace App\Models\Talent;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipCheckin extends Model
{
    use HasFactory;

    protected $fillable = [
        'pip_id',
        'checkin_date',
        'summary',
        'status',
        'rating',
        'goals_progress',
        'manager_notes',
        'next_steps',
        'next_checkin_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'checkin_date' => 'date',
        'next_checkin_date' => 'date',
        'goals_progress' => 'array',
    ];

    // Relationships
    public function pip()
    {
        return $this->belongsTo(Pip::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeByPip($query, $pipId)
    {
        return $query->where('pip_id', $pipId);
    }

    public function scopeWithRating($query)
    {
        return $query->whereNotNull('rating');
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('checkin_date', 'desc');
    }

    // Helper Methods
    public function getRatingLabel()
    {
        if (! $this->rating) {
            return 'Not rated';
        }

        return match ($this->rating) {
            1 => 'Poor',
            2 => 'Below Expectations',
            3 => 'Meets Expectations',
            4 => 'Exceeds Expectations',
            5 => 'Outstanding',
            default => 'Unknown'
        };
    }

    public function getRatingColor()
    {
        if (! $this->rating) {
            return 'secondary';
        }

        return match ($this->rating) {
            1, 2 => 'danger',
            3 => 'warning',
            4, 5 => 'success',
            default => 'secondary'
        };
    }
}
