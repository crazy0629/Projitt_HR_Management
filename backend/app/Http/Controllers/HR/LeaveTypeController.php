<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreLeaveTypeRequest;
use App\Http\Requests\HR\UpdateLeaveTypeRequest;
use App\Models\HR\LeaveType;
use App\Services\HR\LeaveSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function __construct(private readonly LeaveSetupService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->listLeaveTypes(
            $request->boolean('pagination', true),
            $request->integer('per_page', 15)
        );

        return successResponse(config('messages.success'), $data, 200);
    }

    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $leaveType = $this->service->createLeaveType($request->validated());

        return successResponse(config('messages.success'), $leaveType, 201);
    }

    public function show($leaveTypeId): JsonResponse
    {
        $leaveType = LeaveType::findOrFail($leaveTypeId);
        $leaveType->load('accrualRules');

        return successResponse(config('messages.success'), $leaveType, 200);
    }

    public function update(UpdateLeaveTypeRequest $request, $leaveTypeId): JsonResponse
    {
        $leaveType = LeaveType::findOrFail($leaveTypeId);
        $updated = $this->service->updateLeaveType($leaveType, $request->validated());

        return successResponse(config('messages.success'), $updated, 200);
    }
}
