<?php

namespace App\Http\Controllers\VideoCall;

use App\Models\VideoCall\AiNote;
use App\Models\VideoCall\Meeting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Events\AiNotesGenerated;

class AiNotesController extends Controller
{
    public function generate(Meeting $meeting)
    {
        $this->authorizeOwner($meeting);

        // Simple mock transcript and notes
        $transcript = "Transcript for meeting '{$meeting->title}' on ".now()->toDateTimeString().". This is a mocked transcript.";
        $keyPoints = [
            'Agenda reviewed',
            'Decisions captured',
            'Action items assigned',
        ];
        $sentiment = 'neutral';

        $note = AiNote::create([
            'meeting_id' => $meeting->id,
            'transcript_text' => $transcript,
            'key_points' => $keyPoints,
            'sentiment' => $sentiment,
        ]);
        event(new AiNotesGenerated($note));
        return response()->json($note, 201);
    }

    private function authorizeOwner(Meeting $meeting): void
    {
        abort_if($meeting->created_by !== Auth::id(), 403, 'Forbidden');
    }
}
