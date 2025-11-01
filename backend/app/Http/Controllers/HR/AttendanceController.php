<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\CheckInRequest;
use App\Http\Requests\HR\CheckOutRequest;
use App\Http\Requests\HR\StoreAttendanceLogRequest;
use App\Services\HR\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = array_filter([
            'employee_id' => $request->integer('employee_id'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'is_missing' => $request->input('is_missing'),
            'is_late' => $request->input('is_late'),
        ], static fn ($value) => $value !== null && $value !== '');

        $data = $this->service->listAttendance(
            $request->boolean('pagination', true),
            $request->integer('per_page', 15),
            $filters
        );

        return successResponse(config('messages.success'), $data, 200);
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $employee = Auth::user();
        $record = $this->service->checkIn($employee, $request->validated());

        return successResponse(config('messages.success'), $record, 200);
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $employee = Auth::user();
        $record = $this->service->checkOut($employee, $request->validated());

        return successResponse(config('messages.success'), $record, 200);
    }

    public function store(StoreAttendanceLogRequest $request): JsonResponse
    {
        $record = $this->service->ingest($request->validated());

        return successResponse(config('messages.success'), $record, 201);
    }
}
