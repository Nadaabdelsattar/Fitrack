<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Exceptions\AuthenticationException;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class EmailVerificationService
{
    public function sendVerificationNotification(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->notify(new VerifyEmailNotification());
    }

    public function verifyEmail(int $id, string $hash): User
    {
        $user = User::findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            return $user;
        }

        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new AuthenticationException('Invalid verification link.');
        }

        if ($user instanceof MustVerifyEmail && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $user;
    }

    public function resendVerificationEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $this->sendVerificationNotification($user);
    }
}
