<?php

namespace App\Models\Job;

use App\Http\Controllers\Job\JobApplicationQuestionAnswerController;
use App\Models\Master\Master;
use App\Models\Media\Media;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplicant extends Model {

    use SoftDeletes;

    protected $table = 'job_applicants';

    protected $fillable = [
        'applicant_id',
        'job_id',
        'status',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'contact_code',
        'contact_number',
        'linkedin_link',
        'portfolio_link',
        'cv_media_id',
        'current_job_stage_id',
        'cover_media_id',
        'skill_ids',
        'other_links',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'skill_ids' => 'array',
        'other_links' => 'array',
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'skills',
        'questions',
        'work_experience', 
        'education',
        'certificate'
    ];


    public function getSkillsAttribute()
    {
        return Master::whereIn('id', $this->skill_ids ?? [])->get();
    }

    public function getQuestionsAttribute()
    {
        return JobApplicantQuestionAnswer::singleObject($this->job_id, $this->applicant_id);
    }

    public function getWorkExperienceAttribute()
    {
        return JobApplicantExperience::getByJobAndApplicant($this->job_id, $this->applicant_id);
    }

    public function getEducationAttribute()
    {
        return JobApplicantEducation::getByJobAndApplicant($this->job_id, $this->applicant_id);
    }

    public function getCertificateAttribute()
    {
        return JobApplicantCertificat::getByJobAndApplicant($this->job_id, $this->applicant_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function cvMedia()
    {
        return $this->belongsTo(Media::class, 'cv_media_id');
    }

    public function coverMedia()
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }



    public static function singleObject($jobId, $applicantId){

        $object = self::with([
            'applicant',
            'job',
            'cvMedia',
            'coverMedia'
        ])->where([
            'job_id' => $jobId,
            'applicant_id' => $applicantId
        ])->first();
    
        if (empty($object)) {
            return null;
        }
    
        // dd(JobApplicantExperience::getByJobAndApplicant($jobId, $applicantId));

        // $object->work_experience = JobApplicantExperience::getByJobAndApplicant($jobId, $applicantId);
        // $object->education = JobApplicantEducation::getByJobAndApplicant($jobId, $applicantId);
        // $object->certificate = JobApplicantCertificat::getByJobAndApplicant($jobId, $applicantId);

        return $object;

    }
    
}
