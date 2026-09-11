<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Bank;
use App\Models\BankPlan;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertisementLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_verified_user_can_create_canonical_pending_approval_advertisement(): void
    {
        $user = User::factory()->create(['is_verified' => true]);
        $user->assignRole('seller');
        $data = $this->validPayload();

        $this->actingAs($user, 'sanctum')->postJson('/api/advertisements', $data)
            ->assertCreated()
            ->assertJsonPath('data.status', Advertisement::STATUS_PENDING_APPROVAL)
            ->assertJsonPath('data.assignment_price', $data['assignment_price']);
    }

    public function test_unverified_user_is_blocked_by_verification_middleware(): void
    {
        $user = User::factory()->create(['is_verified' => false]);
        $user->assignRole('seller');

        $this->actingAs($user, 'sanctum')->postJson('/api/advertisements', $this->validPayload())
            ->assertForbidden()
            ->assertJsonPath('code', 'KYC_REQUIRED');
    }

    public function test_owner_can_update_and_resubmit_advertisement(): void
    {
        [$user, $advertisement] = $this->ownedAdvertisement();

        $this->actingAs($user, 'sanctum')->putJson('/api/user/advertisements/'.$advertisement->id, [...$this->validPayload(), 'title' => 'عنوان ویرایش شده آگهی وام'])
            ->assertOk()
            ->assertJsonPath('data.status', Advertisement::STATUS_PENDING_APPROVAL);
    }

    public function test_non_owner_cannot_update_or_delete_advertisement(): void
    {
        [, $advertisement] = $this->ownedAdvertisement();
        $other = User::factory()->create(['is_verified' => true]);
        $other->assignRole('seller');

        $this->actingAs($other, 'sanctum')->putJson('/api/user/advertisements/'.$advertisement->id, $this->validPayload())->assertForbidden();
        $this->actingAs($other, 'sanctum')->deleteJson('/api/user/advertisements/'.$advertisement->id)->assertForbidden();
    }

    public function test_owner_can_mark_advertisement_as_handed_over(): void
    {
        [$user, $advertisement] = $this->ownedAdvertisement();

        $this->actingAs($user, 'sanctum')->patchJson('/api/user/advertisements/'.$advertisement->id.'/status', ['status' => 'handed_over'])
            ->assertOk()
            ->assertJsonPath('data.status', Advertisement::STATUS_HANDED_OVER);
    }

    private function ownedAdvertisement(): array
    {
        $user = User::factory()->create(['is_verified' => true]);
        $user->assignRole('seller');
        $advertisement = Advertisement::factory()->create(['user_id' => $user->id, 'status' => Advertisement::STATUS_PUBLISHED]);

        return [$user, $advertisement];
    }

    private function validPayload(): array
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
