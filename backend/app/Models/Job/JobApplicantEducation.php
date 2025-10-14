<?php

namespace App\Models\Job;

use App\Models\Master\Master;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplicantEducation extends Model
{
    use SoftDeletes;

    protected $table = 'job_applicant_educations';

    protected $fillable = [
        'job_id',
        'applicant_id',
        'school',
        'degree_id',
        'field_of_study',
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Relationship: degree (from masters table)
     */
    public function degree()
    {
        return $this->belongsTo(Master::class, 'degree_id');
    }

    /**
     * Get all educations for a given job and applicant.
     */
    public static function getByJobAndApplicant($jobId, $applicantId)
    {
        return self::with('degree')
            ->where('job_id', $jobId)
            ->where('applicant_id', $applicantId)
            ->get();
    }
}
