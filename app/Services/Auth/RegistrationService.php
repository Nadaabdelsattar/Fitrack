<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\DatabaseManager;

class RegistrationService
{
    public function __construct(
        private readonly Hasher $hasher,
        private readonly DatabaseManager $database,
        private readonly TokenService $tokenService,
        private readonly EmailVerificationService $emailVerificationService,
    ) {
    }

    public function registerOwner(array $data): array
    {
        return $this->database->transaction(function () use ($data): array {
            $gym = Gym::create([
                'name' => $data['gym_name'],
                'email' => $data['gym_email'],
                'phone' => $data['gym_phone'],
                'address' => $data['gym_address'],
            ]);

            $user = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => $this->hasher->make($data['password']),
                'role' => UserRole::admin,
                'gym_id' => $gym->id,
            ]);

            $user->load('gym');

            $this->emailVerificationService->sendVerificationNotification($user);

            $token = $this->tokenService->createToken($user, 'owner-token');

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }
}
