<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Bank;
use App\Models\BankPlan;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_receives_json_unauthorized_response_for_kyc_status(): void
    {
        $this->getJson('/api/user/kyc/status')
            ->assertUnauthorized()
            ->assertJson(['message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید']);
    }

    public function test_unverified_user_can_read_and_update_profile(): void
    {
        $user = User::factory()->create(['is_verified' => false]);

        $this->actingAs($user, 'sanctum')->getJson('/api/user/profile')->assertOk();
        $this->actingAs($user, 'sanctum')->putJson('/api/user/profile', ['nickname' => 'کاربر مجاز'])->assertOk();
    }

    public function test_new_user_without_verification_receives_not_submitted_status(): void
    {
        $user = User::factory()->create(['is_verified' => false]);

        $this->actingAs($user, 'sanctum')->getJson('/api/user/kyc/status')
            ->assertOk()
            ->assertJson(['status' => 'not_submitted', 'data' => null]);
    }

    public function test_unverified_user_can_submit_and_read_kyc_status(): void
    {
        $user = User::factory()->create(['is_verified' => false]);

        $this->actingAs($user, 'sanctum')->postJson('/api/user/kyc/submit', [
            'name' => 'کاربر تست',
            'national_id' => '0012345678',
        ])->assertUnprocessable()->assertJsonPath('errors.national_code.0', 'وارد کردن کد ملی الزامی است.');

        $this->actingAs($user, 'sanctum')->getJson('/api/user/kyc/status')->assertOk()->assertJsonPath('is_verified', false);
    }

    public function test_unverified_user_cannot_create_or_bookmark(): void
    {
        $user = User::factory()->create(['is_verified' => false]);
        $advertisement = Advertisement::factory()->create(['status' => Advertisement::STATUS_PUBLISHED]);

        $this->actingAs($user, 'sanctum')->postJson('/api/advertisements', $this->payload())->assertForbidden()->assertJson([
            'code' => 'KYC_REQUIRED',
            'message' => 'شما به این بخش دسترسی ندارید. لطفاً ابتدا فرایند احراز هویت خود را تکمیل کنید.',
        ]);
        $this->actingAs($user, 'sanctum')->getJson('/api/user/bookmarks')->assertForbidden()->assertJsonPath('code', 'KYC_REQUIRED');
        $this->actingAs($user, 'sanctum')->postJson('/api/user/advertisements/'.$advertisement->id.'/bookmark')->assertForbidden()->assertJsonPath('code', 'KYC_REQUIRED');
    }

    public function test_verified_user_can_create_advertisement(): void
    {
        $user = User::factory()->create(['is_verified' => true]);
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/advertisements', $this->payload());

        $response->assertCreated()->assertJsonPath('data.status', Advertisement::STATUS_PENDING_APPROVAL);
    }

    public function test_unverified_viewer_does_not_receive_public_seller_contact(): void
    {
        $seller = User::factory()->create(['is_verified' => true, 'mobile' => '09120000000', 'show_phone_publicly' => true]);
        $advertisement = Advertisement::factory()->create(['user_id' => $seller->id, 'status' => Advertisement::STATUS_PUBLISHED]);
        $viewer = User::factory()->create(['is_verified' => false]);

        $this->actingAs($viewer, 'sanctum')->getJson('/api/advertisements/'.$advertisement->id)
            ->assertOk()
            ->assertJsonMissingPath('data.advertiser_mobile');
    }

    private function payload(): array
    {
        $bank = Bank::factory()->create();
        $plan = BankPlan::factory()->create(['bank_id' => $bank->id]);
        $province = Location::factory()->create(['parent_id' => null]);
        $city = Location::factory()->create(['parent_id' => $province->id]);

        return [
            'bank_id' => $bank->id,
            'bank_plan_id' => $plan->id,
            'location_id' => $city->id,
            'type' => 'supply',
            'title' => 'واگذاری امتیاز وام با شرایط مناسب',
            'description' => 'توضیحات کامل و شرایط واگذاری این آگهی برای بررسی.',
            'loan_amount' => 500000000,
            'assignment_price' => 80000000,
            'profit_rate' => 4,
            'installment_count' => 48,
        ];
    }
}
