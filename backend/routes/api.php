<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Coding\CodingAssessmentAssignmentController;
use App\Http\Controllers\Coding\CodingAssessmentController;
use App\Http\Controllers\Coding\CodingSubmissionController;
use App\Http\Controllers\VideoCall\AiNotesController;
use App\Http\Controllers\VideoCall\InvitationController;
use App\Http\Controllers\VideoCall\MeetingController;
use App\Http\Controllers\VideoCall\PresenceController;
use App\Http\Controllers\VideoCall\RecordingController;
use App\Http\Controllers\VideoCall\RtcSignalController;
use App\Http\Controllers\VideoCall\TokenController;

// Protected application routes
Route::middleware('auth:sanctum')->group(function () {
    // Meetings
    Route::get('/meetings', [MeetingController::class, 'index']);
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show']);
    Route::post('/meetings', [MeetingController::class, 'store']);
    Route::put('/meetings/{meeting}', [MeetingController::class, 'update']);
    Route::post('/meetings/{meeting}/start', [MeetingController::class, 'start']);
    Route::post('/meetings/{meeting}/end', [MeetingController::class, 'end']);

    // Invitations
    Route::post('/meetings/{meeting}/invite', [InvitationController::class, 'invite']);
    Route::post('/invitations/{invitation}/accept', [InvitationController::class, 'accept']);
    Route::post('/invitations/{invitation}/reject', [InvitationController::class, 'reject']);
    Route::post('/invitations/{invitation}/propose', [InvitationController::class, 'propose']);

    // Recordings (mock)
    Route::get('/meetings/{meeting}/recordings', [RecordingController::class, 'index']);
    Route::post('/meetings/{meeting}/recordings/start', [RecordingController::class, 'start']);
    Route::post('/meetings/{meeting}/recordings/end', [RecordingController::class, 'end']);
    Route::get('/recordings/{recording}/download', [RecordingController::class, 'download']);

    // AI Notes (mock)
    Route::post('/meetings/{meeting}/notes', [AiNotesController::class, 'generate']);

    // Presence (WebRTC)
    Route::post('/meetings/{meeting}/presence/join', [PresenceController::class, 'join']);
    Route::post('/meetings/{meeting}/presence/leave', [PresenceController::class, 'leave']);
    Route::get('/meetings/{meeting}/participants', [PresenceController::class, 'participants']);

    // Signaling (WebRTC via REST polling)
    Route::post('/meetings/{meeting}/rtc/send', [RtcSignalController::class, 'send']);
    Route::get('/meetings/{meeting}/rtc/inbox', [RtcSignalController::class, 'inbox']);
    Route::post('/meetings/{meeting}/rtc/ack', [RtcSignalController::class, 'ack']);

    Route::post('/video/token', TokenController::class);

    Route::prefix('coding')->group(function () {
        Route::get('/assessments', [CodingAssessmentController::class, 'index']);
        Route::post('/assessments', [CodingAssessmentController::class, 'store']);
        Route::get('/assessments/{assessment}', [CodingAssessmentController::class, 'show']);
        Route::put('/assessments/{assessment}', [CodingAssessmentController::class, 'update']);
        Route::delete('/assessments/{assessment}', [CodingAssessmentController::class, 'destroy']);

        Route::get('/assignments', [CodingAssessmentAssignmentController::class, 'index']);
        Route::post('/assessments/{assessment}/assign', [CodingAssessmentAssignmentController::class, 'store']);
        Route::get('/assignments/{assignment}', [CodingAssessmentAssignmentController::class, 'show']);

        Route::get('/submissions', [CodingSubmissionController::class, 'index']);
        Route::post('/assignments/{assignment}/submissions', [CodingSubmissionController::class, 'store']);
        Route::get('/submissions/{submission}', [CodingSubmissionController::class, 'show']);
        Route::post('/submissions/{submission}/review', [CodingSubmissionController::class, 'review']);
    });
});
