<?php

namespace App\Events;

use App\Models\VideoCall\Meeting;

class MeetingEnded
{
    public function __construct(public Meeting $meeting)
    {
    }
}
