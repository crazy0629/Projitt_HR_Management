<?php

namespace App\Http\Middleware;

use App\Models\User\UserAuthToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicantOnboarded
{
    /**
     * Handle the incoming request and ensure it's authenticated via applicant-onboarding token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accessToken = $request->bearerToken();

        if (! $accessToken) {
            return response()->json(['message' => 'Access token missing.'], 401);
        }

        $token = UserAuthToken::findToken($accessToken);

        if (! $token || $token->name !== 'applicant-onboarding') {
            return response()->json(['message' => 'Access restricted to applicant onboarding only.'], 403);
        }

        $user = $token->tokenable;

        if (! $user) {
            return response()->json(['message' => 'Invalid applicant user.'], 403);
        }

        // Manually bind user to request
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
