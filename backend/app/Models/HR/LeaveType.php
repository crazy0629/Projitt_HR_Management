<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_paid',
        'requires_approval',
        'default_allocation_days',
        'max_balance',
        'carry_forward_limit',
        'accrual_method',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'default_allocation_days' => 'float',
        'max_balance' => 'float',
        'carry_forward_limit' => 'float',
        'metadata' => 'array',
    ];

    public function accrualRules()
    {
        return $this->hasMany(LeaveAccrualRule::class);
    }
}
