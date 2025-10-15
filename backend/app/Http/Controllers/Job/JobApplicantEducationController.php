<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Http\Requests\Job\AddJobApplicantEducationRequest;
use App\Http\Requests\Job\DeleteJobApplicantEducationRequest;
use App\Http\Requests\Job\EditJobApplicantEducationRequest;
use App\Models\Job\JobApplicantEducation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobApplicantEducationController extends Controller
{
    /**
     * Add a new education.
     */
    public function add(AddJobApplicantEducationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $education = new JobApplicantEducation;
            $education->fill($data);
            $education->created_by = auth()->id();
            $education->updated_by = auth()->id();
            $education->save();

            $object = JobApplicantEducation::with('degree')->where('id', $education->id)->first();

            return $this->sendSuccess($object, 'Education added successfully.');
        } catch (\Exception $exception) {
            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
    }

    /**
     * Edit an existing education.
     */
    public function edit(EditJobApplicantEducationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $education = JobApplicantEducation::findOrFail($data['id']);
        $education->fill($data);
        $education->updated_by = auth()->id();
        $education->save();

        $object = JobApplicantEducation::with('degree')->where('id', $education->id)->first();

        return $this->sendSuccess($object, 'Education updated successfully.');
    }

    /**
     * Get a single education record by ID.
     */
    public function single($id): JsonResponse
    {
        $object = JobApplicantEducation::with('degree')->where('id', $id)->first();

        return successResponse(config('messages.success'), $object, 200);
    }

    /**
     * List all education records by job and applicant.
     */
    public function listByApplicant(Request $request): JsonResponse
    {
        $educations = JobApplicantEducation::getByJobAndApplicant(
            $request->input('job_id'),
            $request->input('applicant_id')
        );

        return successResponse(config('messages.success'), $educations, 200);
    }

    /**
     * Delete one or more education records.
     */
    public function delete(DeleteJobApplicantEducationRequest $request): JsonResponse
    {
        $ids = $request->input('ids');

        JobApplicantEducation::whereIn('id', $ids)->update(['deleted_by' => auth()->id()]);
        JobApplicantEducation::whereIn('id', $ids)->delete();

        return $this->sendSuccess([], 'Education record(s) deleted successfully.');
    }
}
