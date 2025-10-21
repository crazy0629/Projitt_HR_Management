<?php

namespace App\Helpers;

use Carbon\Carbon;

class TimeHelper
{
    /**
     * Calculate duration between two times.
     *
     * @param  string  $startTime  Start time (format: 'H:i:s')
     * @param  string  $endTime  End time (format: 'H:i:s')
     * @return string Duration in HH:MM:SS format
     */
    public static function calculateDuration($startTime, $endTime)
    {

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $duration = $end->diff($start)->format('%H:%I:%S');

        return $duration;
    }

    /**
     * Check if time1 is greater than time2.
     *
     * @param  string  $time1  First time (format: 'H:i:s')
     * @param  string  $time2  Second time (format: 'H:i:s')
     * @return bool True if time1 is greater than time2, false otherwise
     */
    public static function isTimeGreaterThan($time1, $time2)
    {

        $carbonTime1 = Carbon::parse($time1);
        $carbonTime2 = Carbon::parse($time2);

        return $carbonTime1->gt($carbonTime2);
    }
}
