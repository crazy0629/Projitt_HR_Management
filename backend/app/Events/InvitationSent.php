<?php

namespace App\Events;

use App\Models\VideoCall\Invitation;

class InvitationSent
{
    public function __construct(public Invitation $invitation)
    {
    }
}
