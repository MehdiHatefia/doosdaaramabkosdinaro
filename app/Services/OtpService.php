<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class OtpService
{
    public function issue(Request $request, string $mobile): Otp
    {
        $now = now();
        $context = $this->context($request, $mobile);
        Log::info('OTP database issuance started', $context);

        $recentCount = Otp::where('mobile', $mobile)->where('created_at', '>=', $now->copy()->subMinutes(2))->count();
        if ($recentCount > 0) {
            Log::warning('OTP database rate limit rejected', $context + ['recent_count' => $recentCount]);
            throw new TooManyRequestsHttpException(null, 'لطفاً دو دقیقه بعد دوباره تلاش کنید.');
        }

        return DB::transaction(function () use ($request, $mobile, $now, $context) {
            $invalidated = Otp::where('mobile', $mobile)
                ->whereNull('consumed_at')->where('expires_at', '>=', $now)
                ->update(['consumed_at' => $now]);
            Log::info('Previous OTPs invalidated', $context + ['invalidated_count' => $invalidated]);

            $otpCode = (string) random_int(10000, 99999);
            $otp = Otp::create([
                'mobile' => $mobile,
                'code' => $otpCode,
                'expires_at' => $now->copy()->addMinutes(2),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            $logContext = $context + ['otp_id' => $otp->id];
            if (app()->environment(['local', 'testing'])) {
                $logContext['otp_code'] = $otpCode;
            }
            Log::info('OTP database record created', $logContext);
            return $otp;
        });
    }

    public function consume(Request $request, string $mobile, string $code): Otp
    {
        $context = $this->context($request, $mobile);
        Log::info('OTP database validation started', $context);

        return DB::transaction(function () use ($mobile, $code, $context) {
            $otp = Otp::where('mobile', $mobile)->whereNull('consumed_at')
                ->where('expires_at', '>=', now())->where('code', $code)
                ->lockForUpdate()->first();
            if (! $otp) {
                Log::warning('OTP database validation rejected', $context);
                throw (new ModelNotFoundException())->setModel(Otp::class);
            }

            $otp->update(['consumed_at' => now()]);
            Log::info('OTP consumed successfully', $context + ['otp_id' => $otp->id]);
            return $otp->fresh();
        });
    }

    public function validate(Request $request, string $mobile, string $code): Otp
    {
        $context = $this->context($request, $mobile);
        Log::info('OTP database pre-validation started', $context);

        $otp = Otp::where('mobile', $mobile)->whereNull('consumed_at')
            ->where('expires_at', '>=', now())->where('code', $code)->first();
        if (! $otp) {
            Log::warning('OTP database pre-validation rejected', $context);
            throw (new ModelNotFoundException())->setModel(Otp::class);
        }

        Log::info('OTP database pre-validation succeeded', $context + ['otp_id' => $otp->id]);
        return $otp;
    }

    private function context(Request $request, string $mobile): array
    {
        return [
            'function' => __METHOD__, 'user_id' => auth()->id(),
            'mobile' => $mobile, 'otp_id' => null,
            'payload' => [], 'trace' => $request->header('X-Request-Id'),
        ];
    }
}