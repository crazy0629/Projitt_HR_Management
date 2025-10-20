<?php

namespace App\Http\Controllers\VideoCall;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TokenController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'token' => base64_encode(json_encode([
                'sub' => $user->id,
                'name' => $user->name,
                'issued_at' => now()->toIso8601String(),
                'nonce' => Str::uuid()->toString(),
            ])),
        ]);
    }
}
