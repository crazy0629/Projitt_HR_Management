<?php

return [
    'attendance' => [
        'workday_start' => env('ATTENDANCE_WORKDAY_START', '09:00'),
        'expected_minutes' => (int) env('ATTENDANCE_EXPECTED_MINUTES', 480),
        'late_grace_minutes' => (int) env('ATTENDANCE_LATE_GRACE_MINUTES', 15),
    ],
];
