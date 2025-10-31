<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveAccrualRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_type_id',
        'frequency',
        'amount',
        'max_balance',
        'carry_forward_limit',
        'onboarding_waiting_period_days',
        'effective_from',
        'effective_to',
        'eligibility_criteria',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'max_balance' => 'float',
        'carry_forward_limit' => 'float',
        'onboarding_waiting_period_days' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'eligibility_criteria' => 'array',
        'metadata' => 'array',
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
