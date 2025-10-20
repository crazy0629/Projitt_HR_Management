<?php

namespace App\Http\Controllers\VideoCall;

use App\Models\VideoCall\AiNote;
use App\Models\VideoCall\Meeting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Events\AiNotesGenerated;

class AiNotesController extends Controller
{
    public function generate($meetingId)
    {
        $meeting = $this->authorizeOwner($meetingId);

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

    private function authorizeOwner($meetingId): Meeting
    {
        $meeting = Meeting::findOrFail($meetingId);

        abort_if($meeting->created_by !== Auth::guard('sanctum')->id(), 403, 'Forbidden');

        return $meeting;
    }
}
