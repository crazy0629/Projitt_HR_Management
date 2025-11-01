<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreLeaveRequestRequest;
use App\Http\Requests\HR\UpdateLeaveRequestRequest;
use App\Http\Requests\HR\UpdateLeaveRequestStatusRequest;
use App\Models\HR\LeaveRequest;
use App\Services\HR\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function __construct(private readonly LeaveRequestService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'employee_id' => $request->integer('employee_id'),
            'leave_type_id' => $request->integer('leave_type_id'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ];

        $data = $this->service->listLeaveRequests(
            $request->boolean('pagination', true),
            $request->integer('per_page', 15),
            array_filter($filters)
        );

        return successResponse(config('messages.success'), $data, 200);
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $leaveRequest = $this->service->createLeaveRequest($request->validated());

        return successResponse(config('messages.success'), $leaveRequest, 201);
    }

    public function show($leaveRequestId): JsonResponse
    {
        $leaveRequest = LeaveRequest::with(['employee', 'leaveType', 'approver'])
            ->findOrFail($leaveRequestId);

        return successResponse(config('messages.success'), $leaveRequest, 200);
    }

    public function update(UpdateLeaveRequestRequest $request, $leaveRequestId): JsonResponse
    {
        $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
        $updated = $this->service->updateLeaveRequest($leaveRequest, $request->validated());

        return successResponse(config('messages.success'), $updated, 200);
    }

    public function destroy($leaveRequestId): JsonResponse
    {
        $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
        $this->service->deleteLeaveRequest($leaveRequest);

        return successResponse(config('messages.success'), null, 200);
    }

    public function updateStatus(UpdateLeaveRequestStatusRequest $request, $leaveRequestId): JsonResponse
    {
        $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
        $payload = $request->validated();
        $updated = $this->service->changeStatus($leaveRequest, $payload['status'], $payload);

        return successResponse(config('messages.success'), $updated, 200);
    }
}
