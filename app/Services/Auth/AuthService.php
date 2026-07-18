<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Auth\AuthenticationException;

class AuthService
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly TokenService $tokenService,
        private readonly PasswordService $passwordService,
        private readonly Hasher $hasher,
    ) {
    }

    public function registerOwner(array $data): array
    {
        return $this->registrationService->registerOwner($data);
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if ($user === null || !$this->hasher->check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        if (!$user->hasVerifiedEmail()) {
            throw new AuthenticationException('Your email address is not verified.');
        }

        $token = $this->tokenService->createToken($user, 'login-token');

        return [
            'user' => $user->load('gym'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $this->tokenService->deleteCurrentToken($user);
    }

    public function logoutAll(User $user): void
    {
        $this->tokenService->deleteAllTokens($user);
    }

    public function me(User $user): User
    {
        return $user->load('gym');
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        $this->passwordService->changePassword($user, $currentPassword, $newPassword);
    }

    public function forgotPassword(string $email): void
    {
        $this->passwordService->forgotPassword($email);
    }

    public function resetPassword(string $email, string $token, string $password): void
    {
        $this->passwordService->resetPassword($email, $token, $password);
    }
}
