<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserVerification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserVerificationReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_user_can_submit_complete_verification_with_private_documents(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->post('/api/user/kyc/submit', $this->filesPayload());

        $response->assertOk()->assertJsonPath('status', 'pending');
        $this->assertDatabaseHas('user_verifications', ['user_id' => $user->id, 'status' => 'pending']);
        $storedFiles = Storage::disk('local')->allFiles('kyc_documents/'.$user->id);
        $this->assertCount(6, $storedFiles);
        $this->assertTrue(collect($storedFiles)->contains(fn ($path) => str_ends_with($path, '.jpg')));
    }

    public function test_operator_can_approve_verification_and_verify_user(): void
    {
        [$user, $verification] = $this->submittedVerification();
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator, 'sanctum')->patchJson('/api/admin/verifications/'.$verification->id.'/review', ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_verified' => true]);
        $this->assertDatabaseHas('user_verifications', ['id' => $verification->id, 'reviewed_by' => $operator->id, 'status' => 'approved']);
    }

    public function test_regular_user_cannot_review_verifications(): void
    {
        [, $verification] = $this->submittedVerification();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/admin/verifications')->assertForbidden();
        $this->actingAs($user, 'sanctum')->patchJson('/api/admin/verifications/'.$verification->id.'/review', ['status' => 'approved'])->assertForbidden();
    }

    public function test_duplicate_national_code_is_rejected_without_server_error(): void
    {
        $existing = User::factory()->create(['national_code' => '0012345678']);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->post('/api/user/kyc/submit', $this->filesPayload())
            ->assertUnprocessable()
            ->assertJsonPath('errors.national_code.0', 'کد ملی قبلاً ثبت شده است.');

        $this->assertDatabaseMissing('user_verifications', ['user_id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $existing->id, 'national_code' => '0012345678']);
    }

    public function test_approved_user_cannot_submit_another_verification(): void
    {
        [$user, $verification] = $this->submittedVerification();
        $verification->update(['status' => UserVerification::STATUS_APPROVED]);
        $user->update(['is_verified' => true]);

        $this->actingAs($user, 'sanctum')->post('/api/user/kyc/submit', $this->filesPayload())
            ->assertUnprocessable()
            ->assertJsonPath('errors.kyc.0', 'حساب کاربری شما قبلاً تایید شده است و نیازی به ثبت مجدد نیست');

        $this->assertSame(1, UserVerification::where('user_id', $user->id)->count());
    }

    public function test_pending_user_cannot_submit_another_verification(): void
    {
        [$user] = $this->submittedVerification();

        $this->actingAs($user, 'sanctum')->post('/api/user/kyc/submit', $this->filesPayload())
            ->assertUnprocessable()
            ->assertJsonPath('errors.kyc.0', 'شما یک درخواست در صف بررسی دارید');

        $this->assertSame(1, UserVerification::where('user_id', $user->id)->count());
    }

    public function test_rejected_user_can_submit_a_new_verification_and_history_is_ordered(): void
    {
        [$user, $verification] = $this->submittedVerification();
        $verification->update(['status' => UserVerification::STATUS_REJECTED, 'rejection_reason' => 'کیفیت تصویر مناسب نیست']);

        $this->actingAs($user, 'sanctum')->post('/api/user/kyc/submit', $this->filesPayload())
            ->assertOk()
            ->assertJsonPath('status', UserVerification::STATUS_PENDING);

        $this->assertSame(2, UserVerification::where('user_id', $user->id)->count());
        $this->actingAs($user, 'sanctum')->getJson('/api/user/kyc/history')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', UserVerification::STATUS_PENDING)
            ->assertJsonPath('data.1.rejection_reason', 'کیفیت تصویر مناسب نیست');
    }

    public function test_user_can_view_only_their_own_kyc_details(): void
    {
        [$user, $verification] = $this->submittedVerification();
        $otherUser = User::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/user/kyc/'.$verification->id.'/details')
            ->assertOk()
            ->assertJsonPath('data.id', $verification->id)
            ->assertJsonPath('data.national_code', '0012345678');

        $this->actingAs($otherUser, 'sanctum')->getJson('/api/user/kyc/'.$verification->id.'/details')
            ->assertNotFound();
    }

    private function submittedVerification(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->post('/api/user/kyc/submit', $this->filesPayload())->assertOk();
        return [$user, UserVerification::where('user_id', $user->id)->firstOrFail()];
    }

    private function filesPayload(): array
    {
        return [
            'name' => 'کاربر احراز',
            'national_code' => '0012345678',
            'national_card_serial' => 'ABC123456',
            'home_phone' => '02112345678',
            'postal_address' => 'تهران، خیابان نمونه، پلاک ۱',
            'iban' => 'IR123456789012345678901234',
            'ownership_confirmed' => '1',
            'residence_document' => UploadedFile::fake()->create('residence.jpg', 10, 'image/jpeg'),
            'job_document' => UploadedFile::fake()->create('job.jpg', 10, 'image/jpeg'),
            'national_card_front' => UploadedFile::fake()->create('national_card_front.jpg', 10, 'image/jpeg'),
            'national_card_back' => UploadedFile::fake()->create('national_card_back.jpg', 10, 'image/jpeg'),
            'birth_certificate_p1' => UploadedFile::fake()->create('birth_certificate_p1.jpg', 10, 'image/jpeg'),
            'birth_certificate_p2' => UploadedFile::fake()->create('birth_certificate_p2.jpg', 10, 'image/jpeg'),
        ];
    }
}
