<?php

namespace App\Events;

use App\Models\VideoCall\Invitation;

class InvitationResponded
{
    public function __construct(public Invitation $invitation)
    {
    }
}
