<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRoleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $roles = $user?->getRoleNames()->values()->all() ?? [];
        $context = [
            'function' => __METHOD__,
            'user_id' => $user?->id,
            'payload' => [],
            'trace' => $request->header('X-Request-Id'),
            'roles' => $roles,
            'path' => $request->path(),
        ];
        Log::info('Role access validation started', $context);

        $response = $next($request);
        Log::info($response->getStatusCode() >= 400 ? 'Role access denied' : 'Role access granted', $context + [
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}