<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBroker as PasswordBrokerContract;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordService
{
    public function __construct(
        private readonly Hasher $hasher,
        private readonly PasswordBrokerFactory $brokerFactory,
    ) {
    }

    public function forgotPassword(string $email): void
    {
        $this->broker()->sendResetLink(['email' => $email]);
    }

    public function resetPassword(string $email, string $token, string $password): void
    {
        $status = $this->broker()->reset([
            'email' => $email,
            'token' => $token,
            'password' => $password,
            'password_confirmation' => $password,
        ], function (User $user, string $password) {
            $user->password = $this->hasher->make($password);
            $user->setRememberToken(Str::random(60));
            $user->save();
        });

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => ['The password reset token is invalid or has expired.'],
            ]);
        }
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$this->hasher->check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password = $this->hasher->make($newPassword);
        $user->setRememberToken(Str::random(60));
        $user->save();
    }

    private function broker(): PasswordBrokerContract
    {
        return $this->brokerFactory->broker();
    }
}
