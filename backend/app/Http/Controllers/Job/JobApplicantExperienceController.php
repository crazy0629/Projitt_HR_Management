<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Http\Requests\Job\AddJobApplicantExperience;
use App\Http\Requests\Job\DeleteJobApplicantExperience;
use App\Http\Requests\Job\EditJobApplicantExperience;
use App\Models\Job\JobApplicantExperience;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobApplicantExperienceController extends Controller
{
    /**
     * Add a new experience.
     */
    public function add(AddJobApplicantExperience $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $experience = new JobApplicantExperience;
            $experience->fill($data);
            $experience->created_by = auth()->id();
            $experience->updated_by = auth()->id();
            $experience->save();

            return $this->sendSuccess($experience, 'Experience added successfully.');
        } catch (\Exception $exception) {
            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
    }

    /**
     * Edit an existing experience.
     */
    public function edit(EditJobApplicantExperience $request): JsonResponse
    {
        $data = $request->validated();

        $experience = JobApplicantExperience::findOrFail($data['id']);
        $experience->fill($data);
        $experience->updated_by = auth()->id();
        $experience->save();

        return $this->sendSuccess($experience, 'Experience updated successfully.');
    }

    /**
     * Get a single experience by ID.
     */
    public function single($id): JsonResponse
    {
        $object = JobApplicantExperience::where('id', $id)->first();

        return successResponse(config('messages.success'), $object, 200);
    }

    /**
     * List all experiences for a job and applicant.
     */
    public function listByApplicant(Request $request): JsonResponse
    {
        $experiences = JobApplicantExperience::getByJobAndApplicant($request->input('job_id'), $request->input('applicant_id'));

        return successResponse(config('messages.success'), $experiences, 200);
    }

    /**
     * Delete one or more experiences.
     */
    public function delete(DeleteJobApplicantExperience $request): JsonResponse
    {
        $ids = $request->input('ids');

        JobApplicantExperience::whereIn('id', $ids)->update(['deleted_by' => auth()->id()]);
        JobApplicantExperience::whereIn('id', $ids)->delete();

        return $this->sendSuccess([], 'Experience(s) deleted successfully.');
    }
}
