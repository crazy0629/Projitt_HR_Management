<?php

namespace App\Http\Controllers\Interview;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interview\AddInterviewRequest;
use App\Http\Requests\Interview\EditInterviewRequest;
use App\Http\Requests\Interview\ListWithFiltersRequest;
use App\Models\Interview\Interview;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Interview\ChangeInterviewStatusRequest;

class InterviewController extends Controller
{
    public function add(AddInterviewRequest $request): JsonResponse
    {
        try {
            $object = new Interview;

            $object->schedule_type = $request->input('schedule_type');
            $object->mode = $request->input('mode');
            $object->interviewers_ids = $request->input('interviewers_ids');
            $object->job_id = (int) $request->input('job_id');
            $object->applicant_id = (int) $request->input('applicant_id');
            $object->message = $request->filled('message') ? $request->input('message') : null;

            if ($request->input('schedule_type') === 'propose_time') {
                $object->date = $request->input('date');
                $object->time = $request->input('time');
            }

            $object->status = $request->filled('status') ? $request->input('status') : 'review';
            $object->created_by = Auth::id();
            $object->save();

            return $this->sendSuccess(config('messages.success'), $object);
        } catch (\Exception $e) {
            return $this->sendError('Failed to add interview.', $e->getMessage());
        }
    }

    public function edit(EditInterviewRequest $request): JsonResponse {

        try {
            $object = Interview::whereNull('deleted_at')->findOrFail($request->input('id'));

            $object->schedule_type    = $request->input('schedule_type');
            $object->mode             = $request->input('mode');
            $object->interviewers_ids = $request->input('interviewers_ids');
            $object->job_id           = (int) $request->input('job_id');
            $object->applicant_id     = (int) $request->input('applicant_id');
            $object->message          = $request->filled('message') ? $request->input('message') : null;

            if ($request->input('schedule_type') === 'propose_time') {
                $object->date = $request->input('date');
                $object->time = $request->input('time');
            } else {
                $object->date = null;
                $object->time = null;
            }

            // keep status as is, unless provided
            if ($request->filled('status')) {
                $object->status = $request->input('status');
            }

            $object->updated_by = Auth::id();
            $object->save();

            return $this->sendSuccess(config('messages.success'), $object);
        } catch (\Exception $e) {
            return $this->sendError('Failed to edit interview.', $e->getMessage());
        }
    }


    public function changeStatus(ChangeInterviewStatusRequest $request): JsonResponse
    {
        try {

            $interview = Interview::whereNull('deleted_at')->findOrFail($request->input('id'));

            $oldStatus = $interview->status;
            $newStatus = $request->input('status');

            // No-op if status is unchanged
            if ($oldStatus === $newStatus) {
                return $this->sendSuccess('Status unchanged.', [
                    'id'         => $interview->id,
                    'status'     => $newStatus,
                    'updated_by' => Auth::id(),
                ]);
            }

            $interview->status     = $newStatus;
            $interview->updated_by = Auth::id();
            $interview->save();

            return $this->sendSuccess(config('messages.success'), [
                'id'          => $interview->id,
                'old_status'  => $oldStatus,
                'new_status'  => $newStatus,
                'updated_by'  => Auth::id(),
                'updated_at'  => $interview->updated_at,
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Failed to change interview status.', $e->getMessage());
        }
    }


        /**
     * List jobs with filters and optional pagination.
     */
    public function listAllWithFilters(ListWithFiltersRequest $request): JsonResponse
    {

        $query = Interview::filterData($request);
        $result = $this->getData($query, $request->input('pagination'), $request->input('per_page'), $request->input('page'));

        return $this->sendSuccess($result, config('messages.success'));
    }
}
