<?php

namespace App\Http\Controllers\VideoCall;

use App\Models\VideoCall\Meeting;
use App\Models\VideoCall\MeetingParticipant;
use App\Models\VideoCall\Invitation;
use App\Http\Controllers\Controller;
use App\Models\VideoCall\RtcSignal;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RtcSignalController extends Controller
{
    public function send($meetingId, Request $request)
    {
        $meeting = $this->findMeeting($meetingId);
        $userId = Auth::guard('sanctum')->id();
        $this->ensureParticipant($meeting, $userId);

        $data = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'in:offer,answer,candidate'],
            'payload' => ['required', 'array'],
        ]);

        // Recipient may not have joined yet; allow queueing messages

        $this->ensureRecipient($meeting, (int) $data['to_user_id']);

        $signal = RtcSignal::create([
            'meeting_id' => $meeting->id,
            'from_user_id' => $userId,
            'to_user_id' => (int) $data['to_user_id'],
            'type' => $data['type'],
            'payload' => $data['payload'],
        ]);

        return response()->json($signal, 201);
    }

    public function inbox($meetingId, Request $request)
    {
        $meeting = $this->findMeeting($meetingId);
        $userId = Auth::guard('sanctum')->id();
        $this->ensureParticipant($meeting, $userId);
        $sinceId = (int) $request->query('since_id', 0);

        $signals = RtcSignal::where('meeting_id', $meeting->id)
            ->where('to_user_id', $userId)
            ->when($sinceId > 0, fn($q) => $q->where('id', '>', $sinceId))
            ->orderBy('id')
            ->get();

        return response()->json($signals);
    }

    public function ack($meetingId, Request $request)
    {
        $meeting = $this->findMeeting($meetingId);
        $userId = Auth::guard('sanctum')->id();
        $this->ensureParticipant($meeting, $userId);
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = RtcSignal::where('meeting_id', $meeting->id)
            ->where('to_user_id', $userId)
            ->whereIn('id', $data['ids'])
            ->update(['acknowledged_at' => now()]);

        return response()->json(['acknowledged' => $count]);
    }

    private function ensureParticipant(Meeting $meeting, int $userId): void
    {
        $exists = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();
        abort_unless($exists || $meeting->created_by === $userId, 403, 'Join meeting first');
    }

    private function ensureRecipient(Meeting $meeting, int $userId): void
    {
        if ($meeting->created_by === $userId) {
            return;
        }

        $activeParticipant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();

        if ($activeParticipant) {
            return;
        }

        $invited = Invitation::where('meeting_id', $meeting->id)
            ->where('invitee_user_id', $userId)
            ->whereIn('status', ['pending', 'accepted', 'proposed'])
            ->exists();

        abort_unless($invited, 422, 'Recipient not part of meeting');
    }

    private function findMeeting($meetingId): Meeting
    {
        return Meeting::findOrFail($meetingId);
    }
}
