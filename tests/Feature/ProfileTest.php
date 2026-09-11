<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_cannot_change_name_or_national_code(): void
    {
        $user = User::factory()->create([
            'name' => 'نام اصلی',
            'national_code' => '0012345678',
            'is_verified' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/auth/profile', [
            'name' => 'نام تغییر یافته',
            'national_code' => '0098765432',
            'nickname' => 'نامک',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'نام اصلی',
            'national_code' => '0012345678',
            'nickname' => 'نامک',
        ]);
    }

    public function test_profile_rejects_invalid_iban_and_duplicate_email(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/profile', ['iban' => 'IR123', 'email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['iban', 'email']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/profile', ['email' => $otherUser->email])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_profile_can_be_saved_without_iban(): void
    {
        $user = User::factory()->create(['iban' => null]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/profile', ['nickname' => 'بدون شبا'])
            ->assertOk()
            ->assertJsonPath('user.nickname', 'بدون شبا');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'iban' => null]);
    }
}
