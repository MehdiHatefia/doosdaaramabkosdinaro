<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Bank;
use App\Models\BankPlan;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_create_an_advertisement(): void
    {
        $this->postJson('/api/advertisements', [])->assertUnauthorized();
    }

    public function test_unverified_user_cannot_create_an_advertisement(): void
    {
        $user = User::factory()->create(['is_verified' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/advertisements', [])
            ->assertForbidden()
            ->assertJson([
                'message' => 'شما به این بخش دسترسی ندارید. لطفاً ابتدا فرایند احراز هویت خود را تکمیل کنید.',
                'code' => 'KYC_REQUIRED',
            ]);
    }

    public function test_verified_user_can_create_a_pending_advertisement(): void
    {
        $user = User::factory()->create(['is_verified' => true]);
        $bank = Bank::factory()->create();
        $plan = BankPlan::factory()->create(['bank_id' => $bank->id]);
        $province = Location::factory()->create(['parent_id' => null]);
        $city = Location::factory()->create(['parent_id' => $province->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/advertisements', [
                'bank_id' => $bank->id,
                'bank_plan_id' => $plan->id,
                'location_id' => $city->id,
                'title' => 'واگذاری امتیاز وام',
                'type' => 'supply',
                'loan_amount' => 300,
                'transfer_price' => 18,
                'interest_rate' => 2,
                'description' => 'توضیحات آگهی',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'واگذاری امتیاز وام')
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_public_advertisements_validate_filters(): void
    {
        $this->getJson('/api/advertisements?type=invalid')->assertUnprocessable();
    }

    public function test_public_advertisements_only_return_approved_active_bank_listings(): void
    {
        $user = User::factory()->create(['is_verified' => true]);
        $activeBank = Bank::factory()->create(['is_active' => true]);
        $inactiveBank = Bank::factory()->create(['is_active' => false]);
        $approved = Advertisement::factory()->create(['user_id' => $user->id, 'bank_id' => $activeBank->id, 'status' => 'approved']);
        Advertisement::factory()->create(['user_id' => $user->id, 'bank_id' => $activeBank->id, 'status' => 'pending']);
        Advertisement::factory()->create(['user_id' => $user->id, 'bank_id' => $inactiveBank->id, 'status' => 'approved']);

        $response = $this->getJson('/api/advertisements');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $approved->id);
    }

    public function test_public_advertisement_detail_returns_full_approved_listing(): void
    {
        $user = User::factory()->create(['mobile' => '09123456789', 'is_verified' => true]);
        $bank = Bank::factory()->create(['is_active' => true]);
        $plan = BankPlan::factory()->create(['bank_id' => $bank->id]);
        $province = Location::factory()->create();
        $city = Location::factory()->create(['parent_id' => $province->id]);
        $advertisement = Advertisement::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'bank_plan_id' => $plan->id,
            'location_id' => $city->id,
        ]);

        $this->actingAs($user, 'sanctum')->getJson('/api/advertisements/'.$advertisement->id)
            ->assertOk()
            ->assertJsonPath('data.id', $advertisement->id)
            ->assertJsonPath('data.bank', $bank->name)
            ->assertJsonPath('data.bank_plan.title', $plan->title)
            ->assertJsonPath('data.city', $city->name)
            ->assertJsonPath('data.province', $province->name)
            ->assertJsonPath('data.advertiser_mobile', $user->mobile);
    }

    public function test_public_advertisement_detail_hides_unpublished_listing(): void
    {
        $advertisement = Advertisement::factory()->create(['status' => 'pending']);

        $this->getJson('/api/advertisements/'.$advertisement->id)->assertNotFound();
    }

    public function test_authenticated_user_receives_only_own_advertisements(): void
    {
        $user = User::factory()->create(['is_verified' => true]);
        $user->assignRole('seller');
        $otherUser = User::factory()->create();
        $bank = Bank::factory()->create(['is_active' => true]);
        $ownAd = Advertisement::factory()->create(['user_id' => $user->id, 'bank_id' => $bank->id, 'status' => 'pending']);
        Advertisement::factory()->create(['user_id' => $otherUser->id, 'bank_id' => $bank->id, 'status' => 'approved']);

        $this->actingAs($user, 'sanctum')->getJson('/api/user/ads')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ownAd->id);
    }

    public function test_advertisement_belongs_to_a_bank(): void
    {
        $bank = Bank::factory()->create();
        $advertisement = Advertisement::factory()->create(['bank_id' => $bank->id]);

        $this->assertTrue($advertisement->bank->is($bank));
        $this->assertTrue($bank->advertisements->contains($advertisement));
    }

    public function test_location_has_parent_and_children_relationships(): void
    {
        $province = Location::factory()->create(['parent_id' => null]);
        $city = Location::factory()->create(['parent_id' => $province->id]);

        $this->assertTrue($city->parent->is($province));
        $this->assertTrue($province->children->contains($city));
        $this->assertTrue(Location::provinces()->whereKey($province->id)->exists());
        $this->assertFalse(Location::provinces()->whereKey($city->id)->exists());
    }

    public function test_advertisement_belongs_to_a_plan_and_city(): void
    {
        $bank = Bank::factory()->create();
        $plan = BankPlan::factory()->create(['bank_id' => $bank->id]);
        $province = Location::factory()->create();
        $city = Location::factory()->create(['parent_id' => $province->id]);
        $advertisement = Advertisement::factory()->create([
            'bank_id' => $bank->id,
            'bank_plan_id' => $plan->id,
            'location_id' => $city->id,
        ]);

        $this->assertTrue($advertisement->bankPlan->is($plan));
        $this->assertTrue($advertisement->location->is($city));
        $this->assertTrue($plan->advertisements->contains($advertisement));
        $this->assertTrue($city->advertisements->contains($advertisement));
    }

    public function test_public_advertisements_can_be_filtered_by_location_id(): void
    {
        $bank = Bank::factory()->create(['is_active' => true]);
        $province = Location::factory()->create();
        $city = Location::factory()->create(['parent_id' => $province->id]);
        $otherCity = Location::factory()->create(['parent_id' => $province->id]);
        $user = User::factory()->create();
        $matching = Advertisement::factory()->create(['user_id' => $user->id, 'bank_id' => $bank->id, 'location_id' => $city->id]);
        Advertisement::factory()->create(['user_id' => $user->id, 'bank_id' => $bank->id, 'location_id' => $otherCity->id]);

        $this->getJson('/api/advertisements?location_id='.$city->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.city', $city->name)
            ->assertJsonPath('data.0.province', $province->name);
    }

    public function test_public_locations_and_bank_plans_endpoints_return_nested_data(): void
    {
        $province = Location::factory()->create();
        $city = Location::factory()->create(['parent_id' => $province->id]);
        $bank = Bank::factory()->create(['is_active' => true]);
        $plan = BankPlan::factory()->create(['bank_id' => $bank->id, 'is_active' => true]);

        $this->getJson('/api/locations/provinces')
            ->assertOk()
            ->assertJsonPath('data.0.id', $province->id)
            ->assertJsonPath('data.0.children.0.id', $city->id);
        $this->getJson('/api/banks/'.$bank->id.'/plans')
            ->assertOk()
            ->assertJsonPath('data.0.id', $plan->id);
    }

    public function test_public_advertisements_support_standard_sorting_options(): void
    {
        $bank = Bank::factory()->create(['is_active' => true]);
        $user = User::factory()->create();
        $advertisements = collect([
            ['loan_amount' => 300, 'transfer_price' => 30, 'created_at' => '2026-09-01 10:00:00'],
            ['loan_amount' => 900, 'transfer_price' => 50, 'created_at' => '2026-09-03 10:00:00'],
            ['loan_amount' => 500, 'transfer_price' => 10, 'created_at' => '2026-09-02 10:00:00'],
        ])->map(fn (array $attributes) => Advertisement::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'loan_amount' => $attributes['loan_amount'],
            'transfer_price' => $attributes['transfer_price'],
            'created_at' => Carbon::parse($attributes['created_at']),
            'updated_at' => Carbon::parse($attributes['created_at']),
        ]));

        $this->getJson('/api/advertisements?sort=latest')
            ->assertOk()
            ->assertJsonPath('data.0.id', $advertisements[1]->id);
        $this->getJson('/api/advertisements?sort=amount_desc')
            ->assertOk()
            ->assertJsonPath('data.0.id', $advertisements[1]->id)
            ->assertJsonPath('data.1.id', $advertisements[2]->id);
        $this->getJson('/api/advertisements?sort=price_asc')
            ->assertOk()
            ->assertJsonPath('data.0.id', $advertisements[2]->id)
            ->assertJsonPath('data.1.id', $advertisements[0]->id);
    }

    public function test_invalid_sort_falls_back_to_latest(): void
    {
        $bank = Bank::factory()->create(['is_active' => true]);
        $user = User::factory()->create();
        $older = Advertisement::factory()->create(['user_id' => $user->id, 'bank_id' => $bank->id, 'created_at' => now()->subDay()]);
        $newer = Advertisement::factory()->create(['user_id' => $user->id, 'bank_id' => $bank->id, 'created_at' => now()]);

        $this->getJson('/api/advertisements?sort=unknown')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id);
    }
}