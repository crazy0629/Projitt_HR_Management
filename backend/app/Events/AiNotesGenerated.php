<?php

namespace App\Events;

use App\Models\VideoCall\AiNote;

class AiNotesGenerated
{
    public function __construct(public AiNote $note)
    {
    }
}
