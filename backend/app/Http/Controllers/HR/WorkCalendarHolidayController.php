<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreWorkCalendarHolidayRequest;
use App\Http\Requests\HR\UpdateWorkCalendarHolidayRequest;
use App\Models\HR\WorkCalendarHoliday;
use App\Services\HR\LeaveSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkCalendarHolidayController extends Controller
{
    public function __construct(private readonly LeaveSetupService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->listHolidays(
            $request->boolean('pagination', true),
            $request->integer('per_page', 15)
        );

        return successResponse(config('messages.success'), $data, 200);
    }

    public function store(StoreWorkCalendarHolidayRequest $request): JsonResponse
    {
        $holiday = $this->service->createHoliday($request->validated());

        return successResponse(config('messages.success'), $holiday, 201);
    }

    public function show($workCalendarHolidayId): JsonResponse
    {
        $workCalendarHoliday = WorkCalendarHoliday::findOrFail($workCalendarHolidayId);
        $workCalendarHoliday->load('calendar');

        return successResponse(config('messages.success'), $workCalendarHoliday, 200);
    }

    public function update(UpdateWorkCalendarHolidayRequest $request, $workCalendarHolidayId): JsonResponse
    {
        $workCalendarHoliday = WorkCalendarHoliday::findOrFail($workCalendarHolidayId);
        $updated = $this->service->updateHoliday($workCalendarHoliday, $request->validated());

        return successResponse(config('messages.success'), $updated, 200);
    }
}
