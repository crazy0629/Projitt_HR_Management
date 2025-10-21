<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Http\Requests\Applicant\ChangeApplicantEmail;
use App\Http\Requests\Job\EditJobApplicantContactInfoRequest;
use App\Http\Requests\Job\EditJobApplicantCvAndCoverRequest;
use App\Http\Requests\Job\EditJobApplicantInfoRequest;
use App\Http\Requests\Job\GetJobApplicantRequest;
use App\Http\Requests\Job\RejectJobApplicantRequest;
use App\Http\Requests\Job\UpdateCurrentStage;
use App\Http\Requests\Job\UpdateJobApplicantStatusRequest;
use App\Models\Country\Country;
use App\Models\Job\Job;
use App\Models\Job\JobApplicant;
use App\Models\Master\Master;
use App\Models\User\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobApplicantController extends Controller
{
    /**
     * Get a single job applicant.
     */
    public function single(Request $request): JsonResponse
    {

        $object = JobApplicant::singleObject($request->input('job_id'), $request->input('applicant_id'));

        return $this->sendSuccess($object, config('messages.success'));
    }

    /**
     * Add or Update job applicant contact info.
     */
    public function editJobApplicantContactInfo(EditJobApplicantContactInfoRequest $request): JsonResponse
    {

        DB::beginTransaction();

        try {

            $jobApplicant = JobApplicant::singleObject($request->job_id, $request->applicant_id);

            if (! $jobApplicant) {
                // Create new JobApplicant if not found
                $jobApplicant = new JobApplicant;
                $jobApplicant->job_id = $request->job_id;
                $jobApplicant->applicant_id = $request->applicant_id;
                $jobApplicant->created_by = \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id(); // optional: track creator
                $jobApplicant->save();
            }

            $jobApplicantData = $request->only([
                'address',
                'city',
                'state',
                'zip_code',
                'country',
                'contact_code',
                'contact_number'
            ]);
            $jobApplicantData['updated_by'] = \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id();
            $jobApplicant->fill($jobApplicantData)->save();

            $user = $jobApplicant->applicant;
            if ($user) {
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                if ($request->filled('middle_name')) {
                    $user->middle_name = $request->middle_name;
                }
                $user->save();
            }

            DB::commit();

            $profile = JobApplicant::singleObject($request->job_id, $request->applicant_id);

            return $this->sendSuccess($profile, config('messages.success'));
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
    }

    /**
     * Update job applicant CV and cover letter media.
     */
    public function editJobApplicantCvAndCover(EditJobApplicantCvAndCoverRequest $request): JsonResponse
    {
        try {
            $jobApplicant = JobApplicant::singleObject($request->job_id, $request->applicant_id);
            if (! $jobApplicant) {
                return $this->sendError('Job applicant not found.', []);
            }

            $jobApplicant->cv_media_id = $request->cv_media_id;
            $jobApplicant->cover_media_id = $request->cover_media_id;
            $jobApplicant->updated_by = \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id();
            $jobApplicant->save();

            $updated = JobApplicant::singleObject($request->job_id, $request->applicant_id);

            return $this->sendSuccess($updated, config('messages.success'));
        } catch (\Exception $exception) {
            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
    }

    /**
     * Update job applicant info.
     */
    public function editJobApplicantInfo(EditJobApplicantInfoRequest $request): JsonResponse
    {
        try {
            $jobApplicant = JobApplicant::singleObject($request->job_id, $request->applicant_id);

            if (! $jobApplicant) {
                return $this->sendError('Job applicant not found.', []);
            }

            $jobApplicant->fill([
                'skill_ids' => $request->skill_ids,
                'linkedin_link' => $request->linkedin_link,
                'portfolio_link' => $request->portfolio_link,
                'other_links' => $request->other_links,
                'updated_by' => \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id(),
            ])->save();

            $updated = JobApplicant::singleObject($request->job_id, $request->applicant_id);

            return $this->sendSuccess($updated, config('messages.success'));
        } catch (\Exception $exception) {
            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
    }

    /**
     * Update status.
     */
    public function submitJobApplicantion(UpdateJobApplicantStatusRequest $request): JsonResponse
    {
        $job = JobApplicant::where([
            'job_id' => $request->job_id,
            'applicant_id' => $request->applicant_id,
        ])->first();
        $job->status = 'submitted';
        $job->save();

        $updated = JobApplicant::singleObject($request->job_id, $request->applicant_id);

        return $this->sendSuccess($updated, config('messages.success'));
    }

    /**
     * get applicant jobs.
     */
    public function getApplicantJobs(UpdateJobApplicantStatusRequest $request): JsonResponse
    {
        $jobs = DB::table('job_applicants')
            ->select('id', 'job_id', 'applicant_id', 'created_at', 'status')->where('applicant_id', $request->input('applicant_id'))
            ->get();

        foreach ($jobs as $job) {
            $jobDetail = DB::table('jobs')
                ->select('id', 'title', 'status', 'location_type_id', 'country_id', 'state')
                ->where('id', $job->job_id)
                ->first();

            if ($jobDetail) {
                $jobDetail->location_type = Master::find($jobDetail->location_type_id);
                $jobDetail->country = Country::find($jobDetail->country_id);
            }

            $job->job = $jobDetail;
        }

        return $this->sendSuccess($jobs, config('messages.success'));
    }

    /**
     * get applicant jobs.
     */
    public function getJobsApplicant(GetJobApplicantRequest $request): JsonResponse
    {
        $jobId = $request->input('job_id');

        $query = DB::table('job_applicants as ja')
            ->join('users as u', function ($join) {
                $join->on('ja.applicant_id', '=', 'u.id')
                    ->whereNull('u.deleted_at');
            })
            ->where('ja.job_id', $jobId)
            ->whereNull('ja.deleted_at')
            ->select(
                'ja.id',
                'ja.applicant_id',
                'ja.created_at',
                'ja.status',
                'u.first_name',
                'u.middle_name',
                'u.last_name',
                'u.uuid'
            );

        $result = $this->getData($query, $request->input('pagination'), $request->input('per_page'), $request->input('page'));
        return $this->sendSuccess($result, config('messages.success'));
    }

    /**
     * change email address.
     */
    public function changeEmail(ChangeApplicantEmail $request): JsonResponse
    {
        $existingEmail = $request->input('existing_email');
        $newEmail = $request->input('new_email');

        $user = User::where('email', $existingEmail)
            ->whereNull('deleted_at')
            ->first();

        if (! $user) {
            return $this->sendError('User not found.', 404);
        }

        $user->email = $newEmail;
        $user->save();

        return $this->sendSuccess(null, 'Email updated successfully.');
    }


    public function rejectJobApplicant(RejectJobApplicantRequest $request): JsonResponse
    {
        $ids = $request->input('applicant_ids');

        DB::table('job_applicants')
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->update([
                'status' => 'rejected',
                'updated_at' => now(),
            ]);

        return $this->sendSuccess(null, 'Applicants rejected successfully.');

    }
    
    public function UpdateCurrentStage(UpdateCurrentStage $request): JsonResponse {

        $application = JobApplicant::where('applicant_id', $request->applicant_id)
            ->where('job_id', $request->job_id)
            ->whereNull('deleted_at') // keep only if you use soft deletes
            ->first();
    
        if (!$application) {
            return $this->sendError('Job application not found for this applicant and job.');
        }
    
        $application->current_job_stage_id = $request->current_job_stage_id;
        $application->save();
    
        return $this->sendSuccess($application, 'Current stage updated successfully.');
    }
    

}
