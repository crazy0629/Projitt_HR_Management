<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreWorkCalendarRequest;
use App\Http\Requests\HR\UpdateWorkCalendarRequest;
use App\Models\HR\WorkCalendar;
use App\Services\HR\LeaveSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkCalendarController extends Controller
{
    public function __construct(private readonly LeaveSetupService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->listWorkCalendars(
            $request->boolean('pagination', true),
            $request->integer('per_page', 15)
        );

        return successResponse(config('messages.success'), $data, 200);
    }

    public function store(StoreWorkCalendarRequest $request): JsonResponse
    {
        $calendar = $this->service->createWorkCalendar($request->validated());

        return successResponse(config('messages.success'), $calendar, 201);
    }

    public function show($workCalendarId): JsonResponse
    {
        $workCalendar = WorkCalendar::findOrFail($workCalendarId);
        $workCalendar->load('holidays');

        return successResponse(config('messages.success'), $workCalendar, 200);
    }

    public function update(UpdateWorkCalendarRequest $request, WorkCalendar $workCalendar): JsonResponse
    {
        $updated = $this->service->updateWorkCalendar($workCalendar, $request->validated());

        return successResponse(config('messages.success'), $updated, 200);
    }
}
