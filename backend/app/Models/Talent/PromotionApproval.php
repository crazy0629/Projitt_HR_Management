<?php

namespace App\Models\Talent;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'step_order',
        'approver_id',
        'decision',
        'decision_note',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    // Relationships
    public function promotion()
    {
        return $this->belongsTo(PromotionCandidate::class, 'promotion_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('decision', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('decision', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('decision', 'rejected');
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('decision', ['approved', 'rejected']);
    }

    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    public function scopeForPromotion($query, $promotionId)
    {
        return $query->where('promotion_id', $promotionId);
    }

    // State Methods
    public function isPending()
    {
        return $this->decision === 'pending';
    }

    public function isApproved()
    {
        return $this->decision === 'approved';
    }

    public function isRejected()
    {
        return $this->decision === 'rejected';
    }

    public function isCompleted()
    {
        return in_array($this->decision, ['approved', 'rejected']);
    }

    public function canDecide()
    {
        return $this->isPending();
    }

    // Helper Methods
    public function getDaysToDecision()
    {
        if (! $this->decided_at) {
            return $this->created_at->diffInDays(now());
        }

        return $this->created_at->diffInDays($this->decided_at);
    }

    public function getStatusLabel()
    {
        return match ($this->decision) {
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    public function getStatusColor()
    {
        return match ($this->decision) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    // Business Logic
    public function approve($note = null)
    {
        if (! $this->canDecide()) {
            throw new \Exception('This approval step cannot be modified');
        }

        $this->decision = 'approved';
        $this->decision_note = $note;
        $this->decided_at = now();
        $this->save();

        return $this;
    }

    public function reject($reason)
    {
        if (! $this->canDecide()) {
            throw new \Exception('This approval step cannot be modified');
        }

        $this->decision = 'rejected';
        $this->decision_note = $reason;
        $this->decided_at = now();
        $this->save();

        return $this;
    }
}
