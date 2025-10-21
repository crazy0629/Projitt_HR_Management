<?php

namespace App\Http\Controllers\Country;

use App\Http\Controllers\Controller;
use App\Models\Country\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends Controller {
    
    public function index(Request $request): JsonResponse {
        
        $name = $request->get('name');

        // Build query with optional name filter
        $options = Country::query()
            ->when($name, fn($query) => $query->where('name', 'like', '%' . $name . '%'))
            ->orderBy('name')
            ->get();

        // Return success response with countries data
        return $this->sendSuccess($options, config('messages.success'));
    }

}
