<?php

namespace App\Models\Talent;

use App\Models\LearningPath\LearningPath;
use App\Models\Role\Role;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuccessionCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'succession_role_id',
        'employee_id',
        'target_role_id',
        'readiness',
        'development_plan',
        'learning_path_id',
        'target_ready_date',
        'strengths',
        'development_areas',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'development_plan' => 'array',
        'strengths' => 'array',
        'development_areas' => 'array',
        'target_ready_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function successionRole()
    {
        return $this->belongsTo(SuccessionRole::class, 'succession_role_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function targetRole()
    {
        return $this->belongsTo(Role::class, 'target_role_id');
    }

    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeReady($query)
    {
        return $query->where('readiness', 'ready');
    }

    public function scopeDeveloping($query)
    {
        return $query->where('readiness', 'developing');
    }

    public function scopeLongTerm($query)
    {
        return $query->where('readiness', 'long_term');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByRole($query, $roleId)
    {
        return $query->where('target_role_id', $roleId);
    }

    public function scopeReadyByDate($query, $date)
    {
        return $query->where('target_ready_date', '<=', $date);
    }

    // State Methods
    public function isReady()
    {
        return $this->readiness === 'ready';
    }

    public function isDeveloping()
    {
        return $this->readiness === 'developing';
    }

    public function isLongTerm()
    {
        return $this->readiness === 'long_term';
    }

    // Helper Methods
    public function getReadinessLabel()
    {
        return match ($this->readiness) {
            'ready' => 'Ready Now',
            'developing' => 'In Development',
            'long_term' => 'Long-term Potential',
            default => 'Unknown',
        };
    }

    public function getReadinessColor()
    {
        return match ($this->readiness) {
            'ready' => 'success',
            'developing' => 'warning',
            'long_term' => 'info',
            default => 'secondary',
        };
    }

    public function getDaysToReadiness()
    {
        if (! $this->target_ready_date || $this->isReady()) {
            return 0;
        }

        return now()->diffInDays($this->target_ready_date);
    }

    public function getReadinessProgress()
    {
        if ($this->isReady()) {
            return 100;
        }

        if (! $this->target_ready_date) {
            return 0;
        }

        $totalDays = $this->created_at->diffInDays($this->target_ready_date);
        $elapsedDays = $this->created_at->diffInDays(now());

        if ($totalDays <= 0) {
            return 100;
        }

        return min(100, round(($elapsedDays / $totalDays) * 100));
    }

    public function getLearningPathProgress()
    {
        if (! $this->learningPath) {
            return 0;
        }

        // This would integrate with learning path completion tracking
        // For now, return a placeholder value based on readiness
        return match ($this->readiness) {
            'ready' => 100,
            'developing' => 65,
            'long_term' => 25,
            default => 0,
        };
    }

    public function getStrengthsList()
    {
        return is_array($this->strengths) ? $this->strengths : [];
    }

    public function getDevelopmentAreasList()
    {
        return is_array($this->development_areas) ? $this->development_areas : [];
    }

    public function getDevelopmentPlanItems()
    {
        return is_array($this->development_plan) ? $this->development_plan : [];
    }

    public function hasLearningPath()
    {
        return ! is_null($this->learning_path_id);
    }

    // Business Logic
    public function promoteToReady($note = null)
    {
        if ($this->isReady()) {
            return $this;
        }

        $this->readiness = 'ready';
        $this->target_ready_date = now()->toDateString();
        $this->save();

        $this->logActivity('promoted_to_ready', ['note' => $note]);

        return $this;
    }

    public function updateReadiness($readiness, $targetDate = null)
    {
        $oldReadiness = $this->readiness;

        $this->readiness = $readiness;
        if ($targetDate) {
            $this->target_ready_date = $targetDate;
        }
        $this->save();

        $this->logActivity('readiness_updated', [
            'old_readiness' => $oldReadiness,
            'new_readiness' => $readiness,
            'target_date' => $targetDate,
        ]);

        return $this;
    }

    public function assignLearningPath($learningPathId)
    {
        $this->learning_path_id = $learningPathId;
        $this->save();

        $this->logActivity('learning_path_assigned', [
            'learning_path_id' => $learningPathId,
        ]);

        return $this;
    }

    public function updateDevelopmentPlan($plan)
    {
        $this->development_plan = $plan;
        $this->save();

        $this->logActivity('development_plan_updated');

        return $this;
    }

    public function addStrength($strength)
    {
        $strengths = $this->getStrengthsList();
        if (! in_array($strength, $strengths)) {
            $strengths[] = $strength;
            $this->strengths = $strengths;
            $this->save();
        }

        return $this;
    }

    public function removeStrength($strength)
    {
        $strengths = $this->getStrengthsList();
        $strengths = array_values(array_filter($strengths, fn ($s) => $s !== $strength));
        $this->strengths = $strengths;
        $this->save();

        return $this;
    }

    public function addDevelopmentArea($area)
    {
        $areas = $this->getDevelopmentAreasList();
        if (! in_array($area, $areas)) {
            $areas[] = $area;
            $this->development_areas = $areas;
            $this->save();
        }

        return $this;
    }

    public function removeDevelopmentArea($area)
    {
        $areas = $this->getDevelopmentAreasList();
        $areas = array_values(array_filter($areas, fn ($a) => $a !== $area));
        $this->development_areas = $areas;
        $this->save();

        return $this;
    }

    private function logActivity($action, $payload = [])
    {
        AuditLog::create([
            'actor_id' => \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id(),
            'entity_type' => 'SuccessionCandidate',
            'entity_id' => $this->id,
            'action' => $action,
            'payload_json' => array_merge($payload, [
                'readiness' => $this->readiness,
                'employee_id' => $this->employee_id,
                'target_role_id' => $this->target_role_id,
            ]),
            'created_at' => now(),
        ]);
    }

    // Static Methods
    public static function createForEmployee($employeeId, $successionRoleId, $data = [])
    {
        return self::create([
            'succession_role_id' => $successionRoleId,
            'employee_id' => $employeeId,
            'target_role_id' => $data['target_role_id'] ?? null,
            'readiness' => $data['readiness'] ?? 'long_term',
            'development_plan' => $data['development_plan'] ?? [],
            'learning_path_id' => $data['learning_path_id'] ?? null,
            'target_ready_date' => $data['target_ready_date'] ?? null,
            'strengths' => $data['strengths'] ?? [],
            'development_areas' => $data['development_areas'] ?? [],
            'is_active' => true,
            'created_by' => \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id(),
            'updated_by' => \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id(),
        ]);
    }

    public static function getReadyCandidatesForRole($roleId)
    {
        return self::active()
            ->ready()
            ->where('target_role_id', $roleId)
            ->with(['employee', 'learningPath'])
            ->get();
    }

    public static function getDevelopingCandidates()
    {
        return self::active()
            ->developing()
            ->with(['employee', 'targetRole', 'learningPath'])
            ->get();
    }
}
