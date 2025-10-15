<?php

namespace App\Models\Talent;

use App\Models\Role\Role;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuccessionRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'incumbent_id',
        'criticality',
        'risk_level',
        'replacement_timeline',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function incumbent()
    {
        return $this->belongsTo(User::class, 'incumbent_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function candidates()
    {
        return $this->hasMany(SuccessionCandidate::class, 'succession_role_id');
    }

    public function readyCandidates()
    {
        return $this->hasMany(SuccessionCandidate::class, 'succession_role_id')
            ->where('readiness', 'ready');
    }

    public function developingCandidates()
    {
        return $this->hasMany(SuccessionCandidate::class, 'succession_role_id')
            ->where('readiness', 'developing');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHighCriticality($query)
    {
        return $query->where('criticality', 'high');
    }

    public function scopeHighRisk($query)
    {
        return $query->where('risk_level', 'high');
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->whereHas('role', function ($q) use ($department) {
            $q->where('department', $department);
        });
    }

    // Helper Methods
    public function isHighCriticality()
    {
        return $this->criticality === 'high';
    }

    public function isHighRisk()
    {
        return $this->risk_level === 'high';
    }

    public function getCriticalityLabel()
    {
        return match ($this->criticality) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
            default => 'Unknown',
        };
    }

    public function getCriticalityColor()
    {
        return match ($this->criticality) {
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'dark',
            default => 'secondary',
        };
    }

    public function getRiskLabel()
    {
        return match ($this->risk_level) {
            'low' => 'Low Risk',
            'medium' => 'Medium Risk',
            'high' => 'High Risk',
            default => 'Unknown',
        };
    }

    public function getRiskColor()
    {
        return match ($this->risk_level) {
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            default => 'secondary',
        };
    }

    public function getTimelineLabel()
    {
        return match ($this->replacement_timeline) {
            'immediate' => 'Immediate (0-30 days)',
            'short' => 'Short-term (1-6 months)',
            'medium' => 'Medium-term (6-12 months)',
            'long' => 'Long-term (1-2 years)',
            default => 'Unknown',
        };
    }

    public function getCandidateCount()
    {
        return $this->candidates()->count();
    }

    public function getReadyCandidateCount()
    {
        return $this->readyCandidates()->count();
    }

    public function getDevelopingCandidateCount()
    {
        return $this->developingCandidates()->count();
    }

    public function hasAdequateSuccession()
    {
        // Consider adequate if there's at least 1 ready candidate or 2+ developing candidates
        $readyCount = $this->getReadyCandidateCount();
        $developingCount = $this->getDevelopingCandidateCount();

        return $readyCount >= 1 || $developingCount >= 2;
    }

    public function getSuccessionHealth()
    {
        $readyCount = $this->getReadyCandidateCount();
        $developingCount = $this->getDevelopingCandidateCount();

        if ($readyCount >= 2) {
            return 'excellent';
        } elseif ($readyCount >= 1) {
            return 'good';
        } elseif ($developingCount >= 2) {
            return 'fair';
        } elseif ($developingCount >= 1) {
            return 'poor';
        } else {
            return 'critical';
        }
    }

    public function getSuccessionHealthColor()
    {
        return match ($this->getSuccessionHealth()) {
            'excellent' => 'success',
            'good' => 'info',
            'fair' => 'warning',
            'poor' => 'danger',
            'critical' => 'dark',
            default => 'secondary',
        };
    }

    // Static Methods
    public static function createForRole($roleId, $incumbentId = null, $data = [])
    {
        return self::create([
            'role_id' => $roleId,
            'incumbent_id' => $incumbentId,
            'criticality' => $data['criticality'] ?? 'medium',
            'risk_level' => $data['risk_level'] ?? 'medium',
            'replacement_timeline' => $data['replacement_timeline'] ?? 'medium',
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);
    }

    public static function getHighRiskRoles()
    {
        return self::active()
            ->where(function ($query) {
                $query->where('criticality', 'high')
                    ->orWhere('risk_level', 'high')
                    ->orWhere('replacement_timeline', 'immediate');
            })
            ->with(['role', 'incumbent', 'candidates'])
            ->get();
    }
}
