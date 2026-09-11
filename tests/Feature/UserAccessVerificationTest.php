<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Bank;
use App\Models\BankPlan;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_create_advertisement_and_access_bookmarks(): void
    {
        $user = User::factory()->create(['is_verified' => true]);
        $bank = Bank::factory()->create();
        $plan = BankPlan::factory()->create(['bank_id' => $bank->id]);
        $province = Location::factory()->create(['parent_id' => null]);
        $city = Location::factory()->create(['parent_id' => $province->id]);

        $this->actingAs($user, 'sanctum')->postJson('/api/advertisements', [
            'bank_id' => $bank->id, 'bank_plan_id' => $plan->id, 'location_id' => $city->id,
            'type' => 'supply', 'title' => 'واگذاری امتیاز وام با شرایط مناسب',
            'description' => 'توضیحات کامل برای آگهی تستی و معتبر.', 'loan_amount' => 500000000,
            'assignment_price' => 80000000, 'profit_rate' => 4, 'installment_count' => 48,
        ])->assertCreated();

        $this->actingAs($user, 'sanctum')->getJson('/api/user/bookmarks')->assertOk();
    }
}