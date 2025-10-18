<?php

namespace App\Events;

use App\Models\VideoCall\Recording;

class RecordingStarted
{
    public function __construct(public Recording $recording)
    {
    }
}
