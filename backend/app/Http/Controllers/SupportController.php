<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddSupportRequest;
use App\Models\Support;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SupportController extends Controller
{
    /**
     * Store a new Support record.
     */
    public function add(AddSupportRequest $request): JsonResponse
    {
        $support = new Support();
        $support->full_name                = $request->filled('full_name') ? $request->input('full_name') : null;
        $support->email                    = $request->filled('email') ? $request->input('email') : null;
        $support->company_name             = $request->filled('company_name') ? $request->input('company_name') : null;
        $support->question_category_id     = $request->filled('question_category_id') ? $request->input('question_category_id') : null;
        $support->question                 = $request->filled('question') ? $request->input('question') : null;
        $support->preferred_response_method= $request->filled('preferred_response_method') ? $request->input('preferred_response_method') : null;
        $support->media_id                 = $request->filled('media_id') ? $request->input('media_id') : null;
        $support->save();

        // re-fetch latest state
        $support = Support::find($support->id);

        return $this->sendSuccess(config('messages.success'), $support, 200);
    }
}
