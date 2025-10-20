<?php

namespace App\Http\Controllers\VideoCall;

use App\Models\VideoCall\Meeting;
use App\Models\VideoCall\Recording;
use App\Http\Controllers\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Events\RecordingStarted;
use App\Events\RecordingEnded;

/**
 * Manage mock recordings for a meeting (start, end, list, download).
 */
class RecordingController extends Controller
{
    /** List recordings with pagination/sorting. */
    public function index($meetingId, Request $request)
    {
        $meeting = $this->authorizeOwner($meetingId);
        $data = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', 'in:created_at,started_at,ended_at'],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
        ]);
        $perPage = $data['per_page'] ?? 10;
        $sortBy = $data['sort_by'] ?? 'created_at';
        $sortDir = $data['sort_dir'] ?? 'desc';
        $recs = $meeting->recordings()
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
        return response()->json($recs);
    }

    /** Start a mock recording and dispatch RecordingStarted. */
    public function start($meetingId)
    {
        $meeting = $this->authorizeOwner($meetingId);

        // Snapshot accepted participants (emails or user ids)
        $participants = $meeting->invitations()
            ->where('status', 'accepted')
            ->get()
            ->map(function ($inv) {
                return $inv->invitee_email ?: ('user:'.$inv->invitee_user_id);
            })->values()->all();

        $rec = Recording::create([
            'meeting_id' => $meeting->id,
            'started_at' => now(),
            'participants' => $participants,
        ]);
        event(new RecordingStarted($rec));
        return response()->json($rec, 201);
    }

    /** End the latest active mock recording and dispatch RecordingEnded. */
    public function end($meetingId)
    {
        $meeting = $this->authorizeOwner($meetingId);
        $rec = $meeting->recordings()->whereNull('ended_at')->latest()->first();
        if (!$rec) {
            return response()->json(['error' => 'no_active_recording', 'message' => 'No active recording'], 404);
        }

        $filename = 'recordings/meeting-'.$meeting->id.'-'.now()->format('Ymd_His').'.txt';
        Storage::disk('local')->put($filename, "Mock recording for meeting {$meeting->id} finished at ".now()->toDateTimeString());

        $rec->update([
            'ended_at' => now(),
            'file_path' => $filename,
        ]);
        event(new RecordingEnded($rec));
        return response()->json($rec);
    }

    /** Download the mock recording file (owner only). */
    public function download($recordingId)
    {
        $recording = Recording::with('meeting')->find($recordingId);

        if (!$recording) {
            return response()->json([
                'error' => 'recording_not_found',
                'message' => 'Recording not found.',
            ], 404);
        }

        $meeting = $recording->meeting;

        if (!$meeting) {
            return response()->json([
                'error' => 'meeting_missing',
                'message' => 'Recording is not linked to a meeting.',
            ], 404);
        }

        $this->authorizeOwner($meeting->id);

        if (!$recording->file_path || !Storage::disk('local')->exists($recording->file_path)) {
            return response()->json(['error' => 'file_unavailable', 'message' => 'File not available'], 404);
        }
        return Storage::disk('local')->download($recording->file_path, basename($recording->file_path));
    }

    private function authorizeOwner($meetingId): Meeting
    {
        $meeting = Meeting::find($meetingId);

        if (!$meeting) {
            throw new HttpResponseException(response()->json([
                'error' => 'meeting_not_found',
                'message' => 'Meeting not found for this recording.',
            ], 404));
        }

        if ($meeting->created_by !== Auth::guard('sanctum')->id()) {
            throw new HttpResponseException(response()->json([
                'error' => 'forbidden',
                'message' => 'You do not own this meeting.',
            ], 403));
        }

        return $meeting;
    }
}
