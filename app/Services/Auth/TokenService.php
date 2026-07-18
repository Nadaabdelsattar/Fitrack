<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;

class TokenService
{
    public function createToken(User $user, string $name = 'auth-token'): string
    {
        return $user->createToken($name)->plainTextToken;
    }

    public function deleteCurrentToken(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }
    }

    public function deleteAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
