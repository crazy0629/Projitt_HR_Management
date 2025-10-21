<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Http\Requests\Job\AddJobDetailRequest;
use App\Http\Requests\Job\ChangeStatusRequest;
use App\Http\Requests\Job\DeleteJobRequest;
use App\Http\Requests\Job\DuplicateJobRequest;
use App\Http\Requests\Job\EditJobDescriptionRequest;
use App\Http\Requests\Job\EditJobDetailRequest;
use App\Http\Requests\Job\EditJobMediaRequest;
use App\Http\Requests\Job\EditJobQuestionRequest;
use App\Http\Requests\Job\ListWithFiltersJobRequest;
use App\Http\Requests\Job\PublishJobRequest;
use App\Models\Job\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /**
     * Store a new job.
     */
    public function add(AddJobDetailRequest $request): JsonResponse
    {
        $job = new Job();

        $job->title              = $request->input('title');
        $job->description        = $request->input('description');
        $job->no_of_job_opening  = $request->input('no_of_job_opening', 1);
        $job->department_id      = $request->input('department_id');
        $job->employment_type_id = $request->input('employment_type_id');
        $job->location_type_id   = $request->input('location_type_id');
        $job->country_id         = $request->input('country_id');
        $job->state              = $request->input('state');
        $job->salary_from        = $request->input('salary_from');
        $job->salary_to          = $request->input('salary_to');
        $job->deadline           = $request->input('deadline');
        $job->skill_ids          = $request->input('skill_ids', []);

        $job->created_by = Auth::id();
        $job->save();

        $job = Job::singleObject($job->id);
        return $this->sendSuccess($job, config('messages.success'));
    }


    /**
     * Edit a new job.
     */
    public function edit(EditJobDetailRequest $request): JsonResponse {

        $job = Job::findOrFail($request->input('id'));

        if (!$job) {
            return $this->sendError('Job not found', 404);
        }

        $job->title              = $request->input('title');
        $job->description        = $request->input('description');
        $job->no_of_job_opening  = $request->input('no_of_job_opening', 1);
        $job->department_id      = $request->input('department_id');
        $job->employment_type_id = $request->input('employment_type_id');
        $job->location_type_id   = $request->input('location_type_id');
        $job->country_id         = $request->input('country_id');
        $job->state              = $request->input('state');
        $job->salary_from        = $request->input('salary_from');
        $job->salary_to          = $request->input('salary_to');
        $job->deadline           = $request->input('deadline');
        $job->skill_ids          = $request->input('skill_ids', []);

        $job->updated_by = Auth::id();
        $job->save();

        $job = Job::singleObject($job->id);
        return $this->sendSuccess($job, config('messages.success'));
    }


    /**
     * Update job description.
     */
    public function editDescription(EditJobDescriptionRequest $request): JsonResponse
    {
        $job = Job::findOrFail($request->input('id'));

        $job->description = $request->input('description');
        $job->updated_by  = Auth::id();
        $job->save();

        $job = Job::singleObject($job->id);
        return $this->sendSuccess($job, config('messages.success'));
    }

    /**
     * Update job description.
     */
    public function publishJob(PublishJobRequest $request): JsonResponse
    {
        $job = Job::findOrFail($request->input('id'));

        $job->description = $request->input('description');
        $job->status  = 'open';
        $job->save();

        $job = Job::singleObject($job->id);
        return $this->sendSuccess($job, config('messages.success'));
    }

    /**
     * Update job media.
     */
    public function editMedia(EditJobMediaRequest $request): JsonResponse
    {
        $job = Job::findOrFail($request->input('id'));

        $job->media_ids   = $request->input('media_ids', []);
        $job->updated_by  = Auth::id();
        $job->save();

        $job = Job::singleObject($job->id);
        return $this->sendSuccess($job, config('messages.success'));
    }

    /**
     * Update job questions.
     */
    public function editQuestions(EditJobQuestionRequest $request): JsonResponse
    {
        $job = Job::findOrFail($request->input('id'));

        $job->question_ids = $request->input('question_ids', []);
        $job->updated_by   = Auth::id();
        $job->save();

        $job = Job::singleObject($job->id);
        return $this->sendSuccess($job, config('messages.success'));
    }

    /**
     * Get a single job.
     */
    public function single($id): JsonResponse
    {
        $job = Job::singleObject($id);
        return $this->sendSuccess($job, config('messages.success'));
    }

    /**
     * List jobs with filters and optional pagination.
     */
    public function listAllWithFilters(ListWithFiltersJobRequest $request): JsonResponse
    {
        $query = Job::filterData($request);

        $data = $this->getData(
            $query,
            $request->input('pagination'),
            $request->input('per_page'),
            $request->input('page')
        );

        return $this->sendSuccess($data, config('messages.success'));
    }

    /**
     * Intellisense search on jobs.
     */
    public function intellisenseSearch(Request $request): JsonResponse
    {
        $results = Job::intellisenseSearch($request);
        return $this->sendSuccess($results, config('messages.success'));
    }

    public function changeStatus(ChangeStatusRequest $request){

        $ids = $request->input('ids');

        Job::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->update([
                'status' => $request->input('status'),
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);

        // $job = Job::singleObject($job->id);
        return $this->sendSuccess(null, config('messages.success'));

    }

    public function duplicateJob($id)
    {
        // Get the existing job
        $existingJob = Job::findOrFail($id);
    
        if (!$existingJob) {
            return $this->sendError('Job not found', []);
        }

        // Create a duplicate
        $job = $existingJob->replicate();
    
        // Reset any fields you want different in the new copy
        $job->status = 'open';
        $job->created_by = Auth::id();
        $job->created_at = now();
        $job->updated_at = now();
    
        // Save the new job
        $job->save();
    
        // Fetch the full object (if you have relationships to load)
        $job = Job::singleObject($job->id);
    
        return $this->sendSuccess($job, config('messages.success'));
    }

    public function delete(DeleteJobRequest $request): JsonResponse {

        $object = Job::whereIn('id', $request->input('ids'))->update([
            'deleted_by' => Auth::id(),
            'deleted_at' => now(),
        ]);

        return successResponse(config('messages.success'), $object, 200);
    }
}
