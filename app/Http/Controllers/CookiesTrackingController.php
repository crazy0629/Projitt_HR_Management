<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCookiesTrackingRequest;
use App\Models\CookiesTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CookiesTrackingController extends Controller
{
    /**
     * Store a new CookiesTracking record.
     */
    public function add(AddCookiesTrackingRequest $request): JsonResponse
    {
        $tracking = new CookiesTracking();
        $tracking->session_id        = $request->filled('session_id') ? $request->input('session_id') : null;
        $tracking->consent_status    = $request->filled('consent_status') ? $request->input('consent_status') : null;
        $tracking->consent_timestamp = $request->filled('consent_timestamp') ? $request->input('consent_timestamp') : now();
        $tracking->user_agent        = $request->filled('user_agent') ? $request->input('user_agent') : null;
        $tracking->ip_address        = $request->filled('ip_address') ? $request->input('ip_address') : null;
        $tracking->save();

        $tracking = CookiesTracking::find($tracking->id);
        return $this->sendSuccess(config('messages.success'), $tracking, 200);

    }
}
