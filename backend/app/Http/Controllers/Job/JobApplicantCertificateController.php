<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Http\Requests\Job\AddJobApplicantCertificateRequest;
use App\Http\Requests\Job\DeleteJobApplicantCertificateRequest;
use App\Http\Requests\Job\EditJobApplicantCertificateRequest;
use App\Models\Job\JobApplicantCertificat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobApplicantCertificateController extends Controller
{
    /**
     * Add a new certificate.
     */
    public function add(AddJobApplicantCertificateRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $certificate = new JobApplicantCertificat;
            $certificate->fill($data);
            $certificate->created_by = \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id();
            $certificate->updated_by = \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id();
            $certificate->save();

            return $this->sendSuccess($certificate, 'Certificate added successfully.');
        } catch (\Exception $exception) {
            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
    }

    /**
     * Edit an existing certificate.
     */
    public function edit(EditJobApplicantCertificateRequest $request): JsonResponse
    {
        $data = $request->validated();

        $certificate = JobApplicantCertificat::findOrFail($data['id']);
        $certificate->fill($data);
        $certificate->updated_by = \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id();
        $certificate->save();

        return $this->sendSuccess($certificate, 'Certificate updated successfully.');
    }

    /**
     * Get a single certificate by ID.
     */
    public function single($id): JsonResponse
    {
        $object = JobApplicantCertificat::where('id', $id)->first();

        return successResponse(config('messages.success'), $object, 200);
    }

    /**
     * List all certificates for a job and applicant.
     */
    public function listByApplicant(Request $request): JsonResponse
    {
        $certificates = JobApplicantCertificat::getByJobAndApplicant(
            $request->input('job_id'),
            $request->input('applicant_id')
        );

        return successResponse(config('messages.success'), $certificates, 200);
    }

    /**
     * Delete one or more certificates.
     */
    public function delete(DeleteJobApplicantCertificateRequest $request): JsonResponse
    {
        $ids = $request->input('ids');

        JobApplicantCertificat::whereIn('id', $ids)->update(['deleted_by' => \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id()]);
        JobApplicantCertificat::whereIn('id', $ids)->delete();

        return $this->sendSuccess([], 'Certificate(s) deleted successfully.');
    }
}
