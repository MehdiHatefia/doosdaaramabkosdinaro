<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request): ?string {
            return $request->is('api/*') ? null : '/';
        });

        $middleware->alias([
            'role.audit' => \App\Http\Middleware\LogRoleAccess::class,
            'verified.user' => \App\Http\Middleware\EnsureUserIsVerified::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request): JsonResponse {
            return response()->json([
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request): JsonResponse {
            return response()->json(['message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید'], Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request): JsonResponse {
            return response()->json(['message' => 'شما اجازه دسترسی به این بخش را ندارید'], Response::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request): JsonResponse {
            return response()->json(['message' => 'مورد درخواستی یافت نشد'], Response::HTTP_NOT_FOUND);
        });

        $exceptions->render(function (Throwable $exception, Request $request): ?JsonResponse {
            $status = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            if (! $request->expectsJson() || $status < Response::HTTP_INTERNAL_SERVER_ERROR) {
                return null;
            }

            Log::error('Unhandled server exception', [
                'function' => __METHOD__,
                'user_id' => $request->user()?->id,
                'payload' => $request->except(['password', 'token', 'code']),
                'trace' => $request->header('X-Request-Id'),
                'exception' => $exception,
            ]);

            return response()->json(['message' => 'خطایی در برقراری ارتباط با سرور رخ داده است. لطفاً لحظاتی دیگر تلاش کنید'], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
