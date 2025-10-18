<?php

namespace App\Events;

use App\Models\VideoCall\Recording;

class RecordingEnded
{
    public function __construct(public Recording $recording)
    {
    }
}
