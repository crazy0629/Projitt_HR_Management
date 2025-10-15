<?php

namespace App\Models\Talent;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetentionRiskSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period',
        'risk',
        'source',
        'factors',
        'score',
    ];

    protected $casts = [
        'factors' => 'array',
        'score' => 'decimal:2',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // Scopes
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function scopeHighRisk($query)
    {
        return $query->where('risk', 'high');
    }

    public function scopeMediumRisk($query)
    {
        return $query->where('risk', 'medium');
    }

    public function scopeLowRisk($query)
    {
        return $query->where('risk', 'low');
    }

    // Helper Methods
    public function getRiskLabel()
    {
        return match ($this->risk) {
            'low' => 'Low Risk',
            'medium' => 'Medium Risk',
            'high' => 'High Risk',
            default => 'Unknown'
        };
    }

    public function getRiskColor()
    {
        return match ($this->risk) {
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            default => 'secondary'
        };
    }

    public function getFactorsList()
    {
        return is_array($this->factors) ? $this->factors : [];
    }

    public function hasHighRiskFactors()
    {
        $highRiskFactors = ['low_performance', 'no_promotion_2_years', 'manager_change', 'compensation_below_market'];
        $employeeFactors = $this->getFactorsList();

        return ! empty(array_intersect($highRiskFactors, $employeeFactors));
    }

    // Static Methods
    public static function getCurrentRisk($employeeId)
    {
        $currentPeriod = now()->format('Y-m');

        return self::where('employee_id', $employeeId)
            ->where('period', $currentPeriod)
            ->first();
    }

    public static function createForEmployee($employeeId, $risk, $factors = [], $score = null)
    {
        $period = now()->format('Y-m');

        return self::updateOrCreate(
            ['employee_id' => $employeeId, 'period' => $period],
            [
                'risk' => $risk,
                'factors' => $factors,
                'score' => $score,
                'source' => 'calculated',
            ]
        );
    }
}
