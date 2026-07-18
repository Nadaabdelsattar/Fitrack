<?php
declare(strict_types=1);

namespace App\Services\Tenant;

use App\Exceptions\NoAuthenticatedUserException;
use App\Exceptions\NoGymAssignedException;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GymContext
{
    public function user(): User
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            throw new NoAuthenticatedUserException('No authenticated user found.');
        }

        return $user;
    }

    public function gym(): Gym
    {
        $user = $this->user();

        if ($user->gym_id === null) {
            throw new NoGymAssignedException('Authenticated user has no assigned gym.');
        }

        return $user->gym()->firstOrFail();
    }

    public function gymId(): int
    {
        $user = $this->user();

        if ($user->gym_id === null) {
            throw new NoGymAssignedException('Authenticated user has no assigned gym.');
        }

        return $user->gym_id;
    }
}
