<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JalaliDateConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_jalali_birth_date_is_stored_as_gregorian_and_returned_as_jalali(): void
    {
        $user = User::factory()->create(['is_verified' => false]);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/auth/profile', [
            'birth_date' => '1370/01/01',
        ]);

        $response->assertOk()->assertJsonPath('user.birth_date', '1370/01/01');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'birth_date' => '1991-03-21',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/profile')
            ->assertOk()
            ->assertJsonPath('user.birth_date', '1370/01/01');
    }
}
