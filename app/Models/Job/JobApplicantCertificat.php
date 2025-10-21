<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplicantCertificat extends Model {

    use SoftDeletes;

    protected $table = 'job_applicant_certificates';

    protected $fillable = [
        'job_id',
        'applicant_id',
        'title',
        'number',
        'issued_date',
        'expiration_date',
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
        'issued_date' => 'date',
        'expiration_date' => 'date',
    ];

    /**
     * Get all certificates for a given job and applicant.
     */
    public static function getByJobAndApplicant($jobId, $applicantId)
    {
        return self::where('job_id', $jobId)
                   ->where('applicant_id', $applicantId)
                   ->get();
    }
    
}
