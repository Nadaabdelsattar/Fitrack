<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly EmailVerificationService $emailVerificationService)
    {
    }

    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $this->emailVerificationService->verifyEmail($id, $hash);

        return $this->successResponse([], 'Email verified successfully.');
    }

    public function resend(Request $request): JsonResponse
    {
        $this->emailVerificationService->resendVerificationEmail($request->user());

        return $this->successResponse([], 'Verification email resent successfully.');
    }
}
