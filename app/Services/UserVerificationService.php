<?php

namespace App\Services;

use App\Http\Requests\SubmitUserVerificationRequest;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserVerificationService
{
    public function submit(User $user, SubmitUserVerificationRequest $request): UserVerification
    {
        $validated = $request->validated();
        $documentFields = [
            'residence_document', 'national_card_front', 'national_card_back',
            'birth_certificate_p1', 'birth_certificate_p2', 'job_document',
        ];

        return DB::transaction(function () use ($user, $request, $validated, $documentFields) {
            $latest = $user->verifications()->latest()->first();
            if ($user->verifications()->where('status', UserVerification::STATUS_APPROVED)->exists()) {
                throw ValidationException::withMessages(['kyc' => 'حساب کاربری شما قبلاً تایید شده است و نیازی به ثبت مجدد نیست']);
            }
            if ($user->verifications()->where('status', UserVerification::STATUS_PENDING)->exists()) {
                throw ValidationException::withMessages(['kyc' => 'شما یک درخواست در صف بررسی دارید']);
            }
            if ($latest && $latest->status !== UserVerification::STATUS_REJECTED) {
                throw ValidationException::withMessages(['kyc' => 'ثبت درخواست جدید فقط پس از رد شدن آخرین پرونده امکان‌پذیر است']);
            }
            $paths = [];
            Storage::disk('local')->makeDirectory('kyc_documents/'.$user->id);
            foreach ($documentFields as $field) {
                $paths[$field.'_path'] = $request->hasFile($field)
                    ? $request->file($field)->store('kyc_documents/'.$user->id, 'local')
                    : null;
            }

            $verification = UserVerification::create([
                'user_id' => $user->id,
                    'home_phone' => $validated['home_phone'],
                    'postal_address' => $validated['postal_address'],
                    'national_code' => $validated['national_code'],
                    'national_card_serial' => $validated['national_card_serial'],
                    'iban' => strtoupper($validated['iban']),
                    'ownership_confirmed' => true,
                    'status' => UserVerification::STATUS_PENDING,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                    ...$paths,
            ]);

            $user->update([
                'name' => $validated['name'],
                'national_code' => $validated['national_code'],
                'iban' => strtoupper($validated['iban']),
                'is_verified' => false,
            ]);

            Log::info('User verification submitted', [
                'function' => __METHOD__,
                'user_id' => $user->id,
                'payload' => ['verification_id' => $verification->id, 'document_count' => count($paths)],
                'trace' => $request->header('X-Request-Id'),
            ]);

            return $verification->fresh(['user']);
        });
    }

    public function deleteDocuments(UserVerification $verification): void
    {
        foreach ([
            'residence_document_path', 'national_card_front_path', 'national_card_back_path',
            'birth_certificate_p1_path', 'birth_certificate_p2_path', 'job_document_path',
        ] as $field) {
            if ($verification->{$field}) Storage::disk('local')->delete($verification->{$field});
        }
    }
}
