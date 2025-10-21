<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Demo\AddDemoRequest;
use App\Models\Demo\Demo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    public function add(AddDemoRequest $request): JsonResponse
    {
        $object = new Demo();
        $object->first_name       = $request->filled('first_name') ? $request->input('first_name') : null;
        $object->last_name        = $request->filled('last_name') ? $request->input('last_name') : null;
        $object->email            = $request->filled('email') ? $request->input('email') : null;
        $object->contact_code     = $request->filled('contact_code') ? $request->input('contact_code') : null;
        $object->contact_no       = $request->filled('contact_no') ? $request->input('contact_no') : null;
        $object->company          = $request->filled('company') ? $request->input('company') : null;
        $object->company_size     = $request->filled('company_size') ? $request->input('company_size') : null;
        $object->industry         = $request->filled('industry') ? $request->input('industry') : null;
        $object->how_hear_bout_us = $request->filled('how_hear_bout_us') ? $request->input('how_hear_bout_us') : null;
        $object->service = $request->filled('service') ? $request->input('service') : null;
        $object->created_by       = Auth::id();
        $object->save();

        $object = Demo::find($object->id);

        return $this->sendSuccess(config('messages.success'), $object, 200);
    }
}
