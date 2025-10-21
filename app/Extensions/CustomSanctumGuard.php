<?php

namespace App\Extensions;

use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class CustomSanctumGuard
{
    public function __invoke(PersonalAccessToken $accessToken): ?HasApiTokens
    {
        if ($accessToken->name === 'applicant-onboarding') {
            return null;
        }

        return $accessToken->tokenable;
    }
}
