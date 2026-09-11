<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');
        $isVerified = (bool) $user?->is_verified;

        Log::info('[Gate:CheckVerification]', [
            'function' => __METHOD__,
            'user_id' => $user?->id,
            'payload' => ['is_verified' => $isVerified, 'allowed' => $isVerified && auth('sanctum')->check()],
            'trace' => $request->header('X-Request-Id'),
        ]);

        if (! auth('sanctum')->check() || ! $isVerified) {
            Log::warning('Unverified user blocked from protected action', [
                'function' => __METHOD__,
                'user_id' => $user?->id,
                'payload' => [],
                'trace' => null,
            ]);

            return response()->json([
                'message' => 'شما به این بخش دسترسی ندارید. لطفاً ابتدا فرایند احراز هویت خود را تکمیل کنید.',
                'code' => 'KYC_REQUIRED',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
