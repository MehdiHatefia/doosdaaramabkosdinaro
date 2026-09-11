<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $token = $user?->currentAccessToken();
        $context = [
            'function' => __METHOD__,
            'user_id' => $user?->id,
            'payload' => [],
            'trace' => $request->header('X-Request-Id'),
        ];
        Log::info('Logout started', $context);

        try {
            $token?->delete();
            Log::info('Logout completed', $context);
        } catch (\Throwable $exception) {
            Log::error('Logout failed', $context + ['exception' => $exception->getMessage()]);
            throw $exception;
        }

        return response()->json(['message' => 'با موفقیت خارج شدید.']);
    }

    public function sendOtp(Request $request, OtpService $otpService): JsonResponse
    {
        $request->merge(['mobile' => $this->normalizeDigits($request->input('mobile'))]);
        $validated = $request->validate([
            'mobile' => ['required', 'regex:/^09[0-9]{9}$/'],
        ]);
        $otpService->issue($request, $validated['mobile']);

        return response()->json(['message' => 'کد تایید ارسال شد.']);
    }

    public function verifyOtp(Request $request, OtpService $otpService): JsonResponse
    {
        $request->merge([
            'mobile' => $this->normalizeDigits($request->input('mobile')),
            'code' => $this->normalizeDigits($request->input('code')),
        ]);
        $validated = $request->validate([
            'mobile' => ['required', 'regex:/^09[0-9]{9}$/'],
            'code' => ['required', 'digits:5'],
        ]);
        $context = ['function' => __METHOD__, 'user_id' => auth()->id(), 'mobile' => $validated['mobile'], 'otp_id' => null, 'payload' => [], 'trace' => $request->header('X-Request-Id')];
        Log::info('OTP verification started', $context);

        try {
            [$user, $isNewUser, $otp] = DB::transaction(function () use ($request, $otpService, $validated) {
                $otp = $otpService->consume($request, $validated['mobile'], $validated['code']);
                $user = User::firstOrCreate(
                    ['mobile' => $validated['mobile']],
                    ['is_verified' => false, 'role' => 'user']
                );
                return [$user, $user->wasRecentlyCreated, $otp];
            });
            $context['otp_id'] = $otp->id;
            if ($isNewUser) {
                Event::dispatch(new Registered($user));
                Role::findOrCreate('buyer', 'web');
                Role::findOrCreate('seller', 'web');
                $user->syncRoles(['buyer', 'seller']);
                Log::info('New user registered through OTP', $context + ['user_id' => $user->id]);
            } else {
                Log::info('Existing user login continued', $context + ['user_id' => $user->id]);
            }
        } catch (ModelNotFoundException $exception) {
            Log::warning('OTP verification rejected', $context);
            return response()->json(['message' => 'کد تایید نادرست یا منقضی شده است.'], 422);
        } catch (\Throwable $exception) {
            Log::error('OTP verification failed', $context + ['exception' => $exception->getMessage()]);
            throw $exception;
        }

        try {
            $token = $user->createToken('web')->plainTextToken;
        } catch (\Throwable $exception) {
            Log::error('Authentication token creation failed', $context + ['user_id' => $user->id, 'exception' => $exception->getMessage()]);
            throw $exception;
        }
        Log::info('OTP verification completed', $context + ['user_id' => $user->id]);

        $user->load('roles');

        return response()->json([
            'token' => $token,
            'user' => $user,
            'is_new_user' => $isNewUser,
        ]);
    }

    public function completeRegistration(Request $request, OtpService $otpService): JsonResponse
    {
        $request->merge([
            'mobile' => $this->normalizeDigits($request->input('mobile')),
            'code' => $this->normalizeDigits($request->input('code')),
        ]);
        $validated = $request->validate([
            'mobile' => ['required', 'regex:/^09[0-9]{9}$/', 'exists:otps,mobile'],
            'code' => ['required', 'digits:5'],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
        ]);
        $context = [
            'function' => __METHOD__,
            'user_id' => auth()->id(),
            'mobile' => $validated['mobile'],
            'otp_id' => null,
            'payload' => ['name' => $validated['name'], 'has_email' => filled($validated['email'] ?? null)],
            'trace' => $request->header('X-Request-Id'),
        ];
        Log::info('Registration completion started', $context);

        try {
            $otp = $otpService->consume($request, $validated['mobile'], $validated['code']);
            $context['otp_id'] = $otp->id;
            $user = User::firstOrCreate(
                ['mobile' => $validated['mobile']],
                ['name' => $validated['name'], 'email' => $validated['email'] ?? null, 'role' => 'user']
            );

            if (! $user->wasRecentlyCreated) {
                Log::warning('Registration completion found existing user', $context + ['user_id' => $user->id]);
            }
            if ($user->wasRecentlyCreated) {
                Role::findOrCreate('buyer', 'web');
                Role::findOrCreate('seller', 'web');
                $user->syncRoles(['buyer', 'seller']);
            }
        } catch (ModelNotFoundException $exception) {
            Log::warning('Registration OTP rejected', $context);
            return response()->json(['message' => 'کد تایید نادرست یا منقضی شده است.'], 422);
        } catch (\Throwable $exception) {
            Log::error('Registration completion failed', $context + ['exception' => $exception->getMessage()]);
            throw $exception;
        }

        $token = $user->createToken('web')->plainTextToken;
        Log::info('Registration completed and login token created', $context + ['user_id' => $user->id]);

        return response()->json([
            'token' => $token,
            'user' => $user->load('roles'),
        ], 201);
    }

    private function normalizeDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}