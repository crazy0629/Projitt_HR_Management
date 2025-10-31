<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreLeaveAccrualRuleRequest;
use App\Http\Requests\HR\UpdateLeaveAccrualRuleRequest;
use App\Models\HR\LeaveAccrualRule;
use App\Services\HR\LeaveSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveAccrualRuleController extends Controller
{
    public function __construct(private readonly LeaveSetupService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->listAccrualRules(
            $request->boolean('pagination', true),
            $request->integer('per_page', 15)
        );

        return successResponse(config('messages.success'), $data, 200);
    }

    public function store(StoreLeaveAccrualRuleRequest $request): JsonResponse
    {
        $rule = $this->service->createAccrualRule($request->validated());

        return successResponse(config('messages.success'), $rule, 201);
    }

    public function show(LeaveAccrualRule $leaveAccrualRule): JsonResponse
    {
        $leaveAccrualRule->load('leaveType');

        return successResponse(config('messages.success'), $leaveAccrualRule, 200);
    }

    public function update(UpdateLeaveAccrualRuleRequest $request, LeaveAccrualRule $leaveAccrualRule): JsonResponse
    {
        $updated = $this->service->updateAccrualRule($leaveAccrualRule, $request->validated());

        return successResponse(config('messages.success'), $updated, 200);
    }
}
