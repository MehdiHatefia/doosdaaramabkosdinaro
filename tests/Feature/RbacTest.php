<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Otp;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_have_buyer_and_seller_roles_at_the_same_time(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['buyer', 'seller']);

        $this->assertTrue($user->hasRole('buyer'));
        $this->assertTrue($user->hasRole('seller'));
        $this->assertTrue($user->hasAnyRole(['buyer', 'operator']));
    }

    public function test_regular_user_receives_forbidden_on_admin_route(): void
    {
        $user = User::factory()->create();
        $user->assignRole('buyer');

        $this->actingAs($user, 'sanctum')->getJson('/api/admin/ads')->assertForbidden();
    }

    public function test_first_otp_registration_assigns_buyer_and_seller_roles(): void
    {
        Otp::create(['mobile' => '09123456789', 'code' => '12345', 'expires_at' => now()->addMinutes(2)]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'mobile' => '09123456789', 'code' => '12345',
        ])->assertJson(['is_new_user' => true]);
        $user = User::where('mobile', '09123456789')->firstOrFail();

        $response->assertOk()->assertJsonPath('user.roles.0.name', 'buyer');
        $this->assertTrue($user->hasRole('buyer'));
        $this->assertTrue($user->hasRole('seller'));
    }
}