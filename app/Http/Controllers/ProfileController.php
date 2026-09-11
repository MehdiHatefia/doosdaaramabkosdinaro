<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Http\Requests\SubmitUserVerificationRequest;
use App\Services\UserVerificationService;
use App\Traits\HasJalaliDates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use HasJalaliDates;
    public function show(Request $request)
    {
        $user = $request->user('sanctum')->load(['province', 'city']);

        Log::info('[ProfileController:show] Profile loaded', [
            'function' => __METHOD__,
            'user_id' => $user->id,
            'payload' => [],
            'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['user' => $user]);
    }

    public function update(Request $request)
    {
        $user = $request->user('sanctum');
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'min:2', 'max:100'],
            'nickname' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'national_code' => ['nullable', 'digits:10', Rule::unique('users', 'national_code')->ignore($user->id)],
            'birth_date' => ['nullable', 'regex:/^(13|14)\d{2}[\/-]\d{1,2}[\/-]\d{1,2}$/'],
            'iban' => ['nullable', 'regex:/^IR[0-9]{24}$/i'],
            'province_id' => ['nullable', 'integer', 'exists:locations,id'],
            'city_id' => ['nullable', 'integer', 'exists:locations,id'],
            'show_phone_publicly' => ['sometimes', 'boolean'],
        ]);

        if ($user->is_verified) {
            unset($validated['name'], $validated['national_code']);
        }

        if (array_key_exists('province_id', $validated) && array_key_exists('city_id', $validated)) {
            $cityBelongsToProvince = Location::whereKey($validated['city_id'])
                ->where('parent_id', $validated['province_id'])
                ->exists();
            if (! $cityBelongsToProvince) {
                return response()->json(['message' => 'شهر انتخاب‌شده متعلق به استان انتخابی نیست.'], 422);
            }
        }

        $user->update($validated);
        $user->load(['province', 'city']);

        Log::info('[ProfileController:update] Profile updated', [
            'function' => __METHOD__,
            'user_id' => $user->id,
            'payload' => array_diff_key($validated, array_flip(['national_code'])),
            'trace' => $request->header('X-Request-Id'),
            'is_verified' => $user->is_verified,
        ]);

        return response()->json(['user' => $user]);
    }

    public function uploadAvatar(Request $request)
    {
        $user = $request->user('sanctum');
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $path = $validated['avatar']->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        Log::info('[ProfileController:update] Avatar uploaded', [
            'function' => __METHOD__,
            'user_id' => $user->id,
            'payload' => ['avatar' => $path],
            'trace' => $request->header('X-Request-Id'),
        ]);

        $freshUser = $user->fresh(['province', 'city']);

        return response()->json([
            'message' => 'تصویر پروفایل با موفقیت به‌روزرسانی شد.',
            'avatar_url' => $freshUser->avatar ? route('user.profile.avatar') : null,
            'user' => $freshUser,
        ]);
    }

    public function showAvatar(Request $request)
    {
        $user = $request->user('sanctum');
        abort_unless($user->avatar && Storage::disk('public')->exists($user->avatar), 404);

        return Storage::disk('public')->response($user->avatar);
    }

    public function submitKyc(SubmitUserVerificationRequest $request, UserVerificationService $service)
    {
        $user = $request->user('sanctum');
        try {
            $verification = $service->submit($user, $request);
        } catch (Throwable $exception) {
            Log::error('[KYC:SubmitFailed]', [
                'function' => __METHOD__,
                'user_id' => $user?->id,
                'payload' => $request->except(['password', 'token', 'code']),
                'trace' => $request->header('X-Request-Id'),
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            throw $exception;
        }

        return response()->json([
            'message' => 'مدارک شما ثبت شد و در انتظار بررسی است.',
            'status' => $verification->status,
            'verification_id' => $verification->id,
        ]);
    }

    public function kycStatus(Request $request)
    {
        $user = $request->user('sanctum');
        $verification = $user->verification;

        Log::info('[ProfileController:kycStatus] KYC status loaded', [
            'function' => __METHOD__,
            'user_id' => $user->id,
            'payload' => [],
            'trace' => $request->header('X-Request-Id'),
        ]);

        if ($verification === null) {
            return response()->json([
                'status' => 'not_submitted',
                'data' => null,
                'is_verified' => (bool) $user->is_verified,
            ]);
        }

        return response()->json([
            'status' => $user->is_verified ? 'approved' : $verification->status,
            'is_verified' => (bool) $user->is_verified,
            'data' => [
                'id' => $verification->id,
                'status' => $verification->status,
                'name' => $user->name,
                'national_code' => $verification->national_code,
                'national_card_serial' => $verification->national_card_serial,
                'home_phone' => $verification->home_phone,
                'postal_address' => $verification->postal_address,
                'iban' => $verification->iban,
                'ownership_confirmed' => $verification->ownership_confirmed,
                'rejection_reason' => $verification->rejection_reason,
                'reviewed_at' => $verification->reviewed_at,
                'submitted_at' => $verification->created_at,
            ],
        ]);
    }

    public function kycHistory(Request $request)
    {
        $history = $request->user('sanctum')->verifications()->latest()->get()->map(fn ($verification) => [
            'id' => $verification->id,
            'status' => $verification->status,
            'rejection_reason' => $verification->rejection_reason,
            'submitted_at' => $verification->created_at ? self::gregorianToJalali($verification->created_at).' '.$verification->created_at->format('H:i') : null,
            'reviewed_at' => $verification->reviewed_at ? self::gregorianToJalali($verification->reviewed_at).' '.$verification->reviewed_at->format('H:i') : null,
        ]);

        Log::info('[ProfileController:kycHistory] KYC history loaded', [
            'function' => __METHOD__, 'user_id' => $request->user('sanctum')->id,
            'payload' => ['count' => $history->count()], 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['data' => $history]);
    }

    public function kycDetails(Request $request, int $verificationId)
    {
        $verification = $request->user('sanctum')->verifications()->findOrFail($verificationId);
        $documentFields = [
            'national_card_front' => 'national_card_front_path',
            'national_card_back' => 'national_card_back_path',
            'birth_certificate_p1' => 'birth_certificate_p1_path',
            'birth_certificate_p2' => 'birth_certificate_p2_path',
            'residence_document' => 'residence_document_path',
            'job_document' => 'job_document_path',
        ];

        return response()->json(['data' => [
            'id' => $verification->id,
            'status' => $verification->status,
            'submitted_at' => $verification->created_at ? self::gregorianToJalali($verification->created_at).' '.$verification->created_at->format('H:i') : null,
            'national_code' => $verification->national_code,
            'national_card_serial' => $verification->national_card_serial,
            'home_phone' => $verification->home_phone,
            'postal_address' => $verification->postal_address,
            'iban' => $verification->iban,
            'documents' => collect($documentFields)->mapWithKeys(fn ($path, $type) => [
                $type => $verification->{$path} ? route('user.kyc.document', [$verification->id, $type]) : null,
            ]),
        ]]);
    }

    public function kycDocument(Request $request, int $verificationId, string $type): StreamedResponse
    {
        $verification = $request->user('sanctum')->verifications()->findOrFail($verificationId);
        $paths = [
            'national_card_front' => 'national_card_front_path', 'national_card_back' => 'national_card_back_path',
            'birth_certificate_p1' => 'birth_certificate_p1_path', 'birth_certificate_p2' => 'birth_certificate_p2_path',
            'residence_document' => 'residence_document_path', 'job_document' => 'job_document_path',
        ];
        abort_unless(isset($paths[$type]) && $verification->{$paths[$type]}, 404);
        $path = $verification->{$paths[$type]};
        abort_unless(Storage::disk('local')->exists($path), 404);

        Log::info('[ProfileController:kycDocument] KYC document viewed', [
            'function' => __METHOD__, 'user_id' => $request->user('sanctum')->id,
            'payload' => ['verification_id' => $verification->id, 'type' => $type], 'trace' => $request->header('X-Request-Id'),
        ]);

        return Storage::disk('local')->response($path);
    }
}
