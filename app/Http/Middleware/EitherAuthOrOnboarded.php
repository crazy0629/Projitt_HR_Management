<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User\UserAuthToken;

class EitherAuthOrOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            $request->setUserResolver(fn () => $user);
            return $next($request);
        }

        $accessToken = $request->bearerToken();

        if ($accessToken) {
            $token = UserAuthToken::findToken($accessToken);

            if ($token && $token->name === 'applicant-onboarding' && $token->tokenable) {
                $request->setUserResolver(fn () => $token->tokenable);
                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
