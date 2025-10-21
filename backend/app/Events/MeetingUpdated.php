<?php

namespace App\Events;

use App\Models\VideoCall\Meeting;

class MeetingUpdated
{
    public function __construct(public Meeting $meeting)
    {
    }
}
