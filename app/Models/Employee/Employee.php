<?php

namespace App\Models\Employee;

use App\Models\Country\Country;
use App\Models\Master\Master;
use App\Models\Role\Role;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'employee_type',
        'job_title',

        'country_id',
        'department_id',
        'manager_id',
        'role_id',

        'contract_start_date',
        'earning_structure',
        'rate',

        'onboarding_check_list_ids',
        'learning_path_id',
        'benefit_ids',
    ];

    protected $casts = [
        'contract_start_date'       => 'date',
        'rate'                      => 'decimal:2',
        'learning_path_id'          => 'integer',
        'onboarding_check_list_ids' => 'array',
        'benefit_ids'               => 'array',
    ];

    /**
     * Expose resolved master records alongside the raw *_ids arrays.
     */
    protected $appends = [
        'onboarding_check_list',
        'benefits',
    ];

    /* ===========================
     |  Relationships
     |===========================*/
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function department()
    {
        // masters table
        return $this->belongsTo(Master::class, 'department_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /* ===========================
     |  Virtual attributes
     |===========================*/

    /**
     * Get the full Onboarding Checklist master records.
     * Returns a Collection<Master>.
     */
    public function getOnboardingCheckListAttribute()
    {
        $ids = $this->onboarding_check_list_ids ?: [];
        if (empty($ids)) {
            return collect();
        }

        return Master::whereIn('id', $ids)->get();
    }

    /**
     * Get the full Benefit master records.
     * Returns a Collection<Master>.
     */
    public function getBenefitsAttribute()
    {
        $ids = $this->benefit_ids ?: [];
        if (empty($ids)) {
            return collect();
        }

        return Master::whereIn('id', $ids)->get();
    }

    /* ===========================
     |  Scopes (optional helpers)
     |===========================*/
    public function scopeType($query, string $type)
    {
        return $query->where('employee_type', $type);
    }

    public function scopeEarningStructure($query, string $structure)
    {
        return $query->where('earning_structure', $structure);
    }
}
