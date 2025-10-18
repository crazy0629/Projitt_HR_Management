<?php

namespace App\Events;

use App\Models\VideoCall\Meeting;

class MeetingStarted
{
    public function __construct(public Meeting $meeting)
    {
    }
}
