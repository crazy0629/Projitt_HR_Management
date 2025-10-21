<?php

namespace App\Http\Controllers\Interview;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interview\AddInterviewRequest;
use App\Http\Requests\Interview\ListWithFiltersRequest;
use App\Models\Interview\Interview;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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
