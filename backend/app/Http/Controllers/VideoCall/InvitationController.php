<?php

namespace App\Http\Controllers\VideoCall;

use App\Models\VideoCall\Invitation;
use App\Models\VideoCall\Meeting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Events\InvitationSent;
use App\Events\InvitationResponded;

class InvitationController extends Controller
{
    public function invite($meetingId, Request $request)
    {
        $meeting = $this->authorizeOwner($meetingId);

        $data = $request->validate([
            'invitee_user_id' => ['nullable', 'exists:users,id'],
            'invitee_email' => ['nullable', 'email'],
        ]);

        if (empty($data['invitee_user_id']) && empty($data['invitee_email'])) {
            return response()->json(['message' => 'Provide invitee_user_id or invitee_email'], 422);
        }

        $invitation = Invitation::create([
            'meeting_id' => $meeting->id,
            'inviter_id' => Auth::guard('sanctum')->id(),
            'invitee_user_id' => $data['invitee_user_id'] ?? null,
            'invitee_email' => $data['invitee_email'] ?? null,
            'status' => 'pending',
            'token' => Str::random(32),
        ]);
        event(new InvitationSent($invitation));
        return response()->json($invitation, 201);
    }

    public function accept($invitationId)
    {
        $invitation = $this->ensureInvitationAccess($invitationId);
        $invitation->update(['status' => 'accepted', 'responded_at' => now()]);
        event(new InvitationResponded($invitation));
        return response()->json($invitation);
    }

    public function reject($invitationId)
    {
        $invitation = $this->ensureInvitationAccess($invitationId);
        $invitation->update(['status' => 'rejected', 'responded_at' => now()]);
        event(new InvitationResponded($invitation));
        return response()->json($invitation);
    }

    public function propose($invitationId, Request $request)
    {
        $invitation = $this->ensureInvitationAccess($invitationId);
        $data = $request->validate([
            'proposed_time' => ['required', 'date'],
        ]);
        $invitation->update(['status' => 'proposed', 'proposed_time' => $data['proposed_time'], 'responded_at' => now()]);
        event(new InvitationResponded($invitation));
        return response()->json($invitation);
    }

    private function authorizeOwner($meetingId): Meeting
    {
        $meeting = Meeting::findOrFail($meetingId);
        abort_if($meeting->created_by !== Auth::guard('sanctum')->id(), 403, 'Forbidden');

        return $meeting;
    }

    private function ensureInvitationAccess($invitationId): Invitation
    {
        $invitation = Invitation::with('meeting')->findOrFail($invitationId);

        // Allow invitee (by user id or email match with authenticated user) or meeting owner to act
        $user = Auth::guard('sanctum')->user();
        $userId = Auth::guard('sanctum')->id();
        $userEmail = $user?->email;
        $isOwner = $invitation->meeting->created_by === $userId;
        $isInviteeId = $invitation->invitee_user_id && $invitation->invitee_user_id === $userId;
        $isInviteeEmail = $invitation->invitee_email && $invitation->invitee_email === $userEmail;
        abort_unless($isOwner || $isInviteeId || $isInviteeEmail, 403, 'Forbidden');

        return $invitation;
    }
}
