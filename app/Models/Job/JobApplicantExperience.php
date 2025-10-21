<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplicantExperience extends Model
{
    use SoftDeletes;

    protected $table = 'job_applicant_experiences';

    protected $fillable = [
        'job_id',
        'applicant_id',
        'job_title',
        'company',
        'location',
        'from_date',
        'to_date',
        'is_currently_working',
        'role_description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_currently_working' => 'boolean',
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    /**
     * Get all experiences for a given job and applicant.
     */
    public static function getByJobAndApplicant($jobId, $applicantId)
    {
        return self::where('job_id', $jobId)
                   ->where('applicant_id', $applicantId)
                   ->get();
    }
}
