<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserVerificationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['status' => ['nullable', 'in:pending,approved,rejected']]);
        $verifications = UserVerification::with(['user:id,name,mobile,national_code', 'reviewer:id,name'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);

        Log::info('Admin verification queue listed', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => $filters, 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json($verifications);
    }

    public function show(Request $request, UserVerification $verification)
    {
        $verification->load(['user', 'reviewer:id,name']);
        Log::info('Admin verification details viewed', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => ['verification_id' => $verification->id], 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['data' => $this->safeData($verification)]);
    }

    public function review(Request $request, UserVerification $verification)
    {
        $payload = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:2000'],
        ]);
        $reviewer = $request->user();

        DB::transaction(function () use ($verification, $payload, $reviewer) {
            $verification->update([
                'status' => $payload['status'],
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $payload['status'] === 'rejected' ? $payload['rejection_reason'] : null,
            ]);
            $verification->user()->update(['is_verified' => $payload['status'] === 'approved']);
        });

        $verification->load('user');

        Log::info('User verification reviewed', [
            'function' => __METHOD__, 'user_id' => $reviewer->id,
            'payload' => ['verification_id' => $verification->id, 'status' => $payload['status']],
            'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['data' => $this->safeData($verification->fresh(['user', 'reviewer:id,name']))]);
    }

    public function document(Request $request, UserVerification $verification, string $field): StreamedResponse
    {
        $allowed = [
            'residence' => 'residence_document_path',
            'national-card-front' => 'national_card_front_path',
            'national-card-back' => 'national_card_back_path',
            'birth-certificate-p1' => 'birth_certificate_p1_path',
            'birth-certificate-p2' => 'birth_certificate_p2_path',
            'job' => 'job_document_path',
        ];
    abort_unless(isset($allowed[$field]) && $verification->{$allowed[$field]}, 404);
        $path = $verification->{$allowed[$field]};
        abort_unless(Storage::disk('local')->exists($path), 404);

        Log::info('Verification document downloaded', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => ['verification_id' => $verification->id, 'document' => $field],
            'trace' => $request->header('X-Request-Id'),
        ]);

        return Storage::disk('local')->download($path);
    }

    private function safeData(UserVerification $verification): array
    {
        return [
            'id' => $verification->id,
            'status' => $verification->status,
            'user' => $verification->user,
            'home_phone' => $verification->home_phone,
            'postal_address' => $verification->postal_address,
            'national_code' => $verification->national_code,
            'national_card_serial' => $verification->national_card_serial,
            'iban' => $verification->iban,
            'ownership_confirmed' => $verification->ownership_confirmed,
            'rejection_reason' => $verification->rejection_reason,
            'reviewed_by' => $verification->reviewer,
            'reviewed_at' => $verification->reviewed_at,
            'documents' => [
                'residence' => route('admin.verifications.document', [$verification, 'residence']),
                'national_card_front' => route('admin.verifications.document', [$verification, 'national-card-front']),
                'national_card_back' => route('admin.verifications.document', [$verification, 'national-card-back']),
                'birth_certificate_p1' => route('admin.verifications.document', [$verification, 'birth-certificate-p1']),
                'birth_certificate_p2' => route('admin.verifications.document', [$verification, 'birth-certificate-p2']),
                'job' => route('admin.verifications.document', [$verification, 'job']),
            ],
        ];
    }
}
