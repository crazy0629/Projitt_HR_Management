<?php

namespace App\Models\Job;

use App\Models\Question\Question;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplicantQuestionAnswer extends Model
{
    use SoftDeletes;

    protected $table = 'job_applicant_question_answers';

    protected $fillable = [
        'question_id',
        'job_id',
        'applicant_id',
        'answer',
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function singleObject($jobId, $applicantId)
    {
        $job = Job::select('id', 'question_ids')->find($jobId);

        if (! $job || empty($job->question_ids)) {
            return collect();
        }

        $questionIds = is_array($job->question_ids)
            ? $job->question_ids
            : json_decode($job->question_ids, true);

        $questions = Question::whereIn('id', $questionIds)->get()->keyBy('id');

        foreach ($questions as $question) {
            $question->applicant_answer = JobApplicantQuestionAnswer::where('job_id', $jobId)
                ->where('applicant_id', $applicantId)
                ->where('question_id', $question->id)
                ->value('answer');
        }

        return $questions->values(); // return as indexed collection
    }
}
