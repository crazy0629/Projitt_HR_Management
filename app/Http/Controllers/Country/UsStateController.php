<?php

namespace App\Http\Controllers\Country;

use App\Http\Controllers\Controller;
use App\Models\Country\UsStates;
use App\Models\Country\UsStats;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class UsStateController extends Controller
{
    public function index(Request $request): JsonResponse {
        
        $name = $request->get('name');

        $options = UsStates::query()
            ->when($name, fn($query) => $query->where('name', 'like', '%' . $name . '%'))
            ->orderBy('name')
            ->get();

        // Return success response with countries data
        return $this->sendSuccess($options, config('messages.success'));
    }
}
