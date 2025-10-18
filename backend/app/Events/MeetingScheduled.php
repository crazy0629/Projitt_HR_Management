<?php

namespace App\Events;

use App\Models\VideoCall\Meeting;

class MeetingScheduled
{
    public function __construct(public Meeting $meeting)
    {
    }
}
