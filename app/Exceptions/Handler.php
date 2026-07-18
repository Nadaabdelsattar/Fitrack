<?php
declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\NoAuthenticatedUserException;
use App\Exceptions\NoGymAssignedException;
use App\Exceptions\TenantAccessDeniedException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            // Customize report logic if needed.
        });
    }

    protected function invalidJson(Request $request, ValidationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $exception->errors(),
        ], $exception->status);
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            if ($e instanceof NoAuthenticatedUserException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Unauthenticated.',
                    'errors' => [],
                ], 401);
            }

            if ($e instanceof NoGymAssignedException || $e instanceof TenantAccessDeniedException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Access denied.',
                    'errors' => [],
                ], 403);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Unauthenticated.',
                    'errors' => [],
                ], 401);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Http error.',
                    'errors' => [],
                ], $e->getStatusCode());
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Server Error.',
            ], 500);
        }

        return parent::render($request, $e);
    }
}
