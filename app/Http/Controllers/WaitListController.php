<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddWaitListRequest;
use App\Models\WaitList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaitListController extends Controller {
    
    public function add(AddWaitListRequest $request): JsonResponse
    {
        // Validate request
        $data = $request->validated();

        // Create new whitelist record
        $whiteList = WaitList::create([
            'name'       => $data['name'] ?? null,
            'email'      => $data['email'],
            'company_name'      => $data['company_name'],
            'company_email'      => $data['company_email'],
            'created_at' => now(), // auto set created_at
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Whitelist entry added successfully.',
            'data'    => $whiteList,
        ]);
    }


    public function listAllWithFilters(Request $request): JsonResponse {

        $object = WaitList::select('name', 'email', 'created_at', 'company_name', 'company_email');
        $object = getData($object, $request->input('pagination'), $request->input('per_page'), $request->input('page'));
        return successResponse(config('messages.success'), $object, 200);
    }

}
