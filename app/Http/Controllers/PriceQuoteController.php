<?php

namespace App\Http\Controllers;

use App\Http\Requests\PriceQuote\AddPriceQuoteRequest;
use App\Models\PriceQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PriceQuoteController extends Controller
{
    public function add(AddPriceQuoteRequest $request): JsonResponse
    {
        $object = new PriceQuote();
        $object->first_name      = $request->filled('first_name') ? $request->input('first_name') : null;
        $object->last_name       = $request->filled('last_name') ? $request->input('last_name') : null;
        $object->contact_code    = $request->filled('contact_code') ? $request->input('contact_code') : null;
        $object->contact_no      = $request->filled('contact_no') ? $request->input('contact_no') : null;
        $object->company_name    = $request->filled('company_name') ? $request->input('company_name') : null;
        $object->no_of_employee  = $request->filled('no_of_employee') ? $request->input('no_of_employee') : null;
        $object->email           = $request->filled('email') ? $request->input('email') : null;
        $object->contact_email   = $request->filled('contact_email') ? $request->input('contact_email') : null;
        $object->service         = $request->filled('service') ? $request->input('service') : null;
        $object->created_by      = Auth::id();
        $object->save();

        $object = PriceQuote::find($object->id);

        return $this->sendSuccess(config('messages.success'), $object, 200);
    }
}
