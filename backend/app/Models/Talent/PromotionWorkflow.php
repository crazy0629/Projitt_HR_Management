<?php

namespace App\Models\Talent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'steps',
        'is_active',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function promotions()
    {
        return $this->hasMany(PromotionCandidate::class, 'workflow_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper Methods
    public function getStepCount()
    {
        return count($this->steps ?? []);
    }

    public function getStepByOrder($order)
    {
        return ($this->steps ?? [])[$order - 1] ?? null;
    }

    public function getStepNames()
    {
        return array_map(function ($step) {
            return $step['name'] ?? 'Step '.($step['order'] ?? '');
        }, $this->steps ?? []);
    }

    public function hasFinanceApproval()
    {
        foreach ($this->steps ?? [] as $step) {
            if (($step['role'] ?? '') === 'finance') {
                return true;
            }
        }

        return false;
    }

    public function getRequiredRoles()
    {
        $roles = [];
        foreach ($this->steps ?? [] as $step) {
            if ($role = $step['role'] ?? null) {
                $roles[] = $role;
            }
        }

        return array_unique($roles);
    }

    // Static Methods
    public static function getDefault()
    {
        return self::active()->first() ?? self::create([
            'name' => 'Default Promotion Workflow',
            'description' => 'Standard promotion approval workflow',
            'steps' => [
                ['order' => 1, 'name' => 'Manager Approval', 'role' => 'manager'],
                ['order' => 2, 'name' => 'HR Business Partner', 'role' => 'hrbp'],
                ['order' => 3, 'name' => 'Director Approval', 'role' => 'director'],
            ],
            'is_active' => true,
        ]);
    }

    public static function getFinanceWorkflow()
    {
        return self::firstOrCreate(
            ['name' => 'Finance Required Workflow'],
            [
                'description' => 'Promotion workflow that requires finance approval for compensation changes',
                'steps' => [
                    ['order' => 1, 'name' => 'Manager Approval', 'role' => 'manager'],
                    ['order' => 2, 'name' => 'HR Business Partner', 'role' => 'hrbp'],
                    ['order' => 3, 'name' => 'Finance Approval', 'role' => 'finance'],
                    ['order' => 4, 'name' => 'Director Approval', 'role' => 'director'],
                ],
                'is_active' => true,
            ]
        );
    }
}
