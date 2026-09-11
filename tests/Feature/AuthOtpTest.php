<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_mobile_receives_an_otp(): void
    {
        $response = $this->postJson('/api/auth/send-otp', ['mobile' => '09123456789']);

        $response->assertOk()->assertJsonStructure(['message']);
        $otp = Otp::where('mobile', '09123456789')->firstOrFail();
        $this->assertMatchesRegularExpression('/^[0-9]{5}$/', $otp->code);
        $this->assertNotNull($otp->expires_at);
        $this->assertSame('127.0.0.1', $otp->ip_address);
    }

    public function test_invalid_mobile_is_rejected(): void
    {
        $this->postJson('/api/auth/send-otp', ['mobile' => '0912345678'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mobile');
    }

    public function test_persian_digits_are_normalized_for_otp_flow(): void
    {
        $this->postJson('/api/auth/send-otp', ['mobile' => '۰۹۱۲۳۴۵۶۷۸۹'])->assertOk();

        $this->assertDatabaseHas('otps', ['mobile' => '09123456789']);
    }

    public function test_sending_again_within_one_minute_is_rate_limited(): void
    {
        $this->postJson('/api/auth/send-otp', ['mobile' => '09123456789'])->assertOk();

        $this->postJson('/api/auth/send-otp', ['mobile' => '09123456789'])
            ->assertStatus(429);
    }

    public function test_new_user_verification_registers_user_and_returns_token(): void
    {
        $this->postJson('/api/auth/send-otp', ['mobile' => '09123456789']);
        $code = Otp::where('mobile', '09123456789')->latest()->value('code');

        $response = $this->postJson('/api/auth/verify-otp', [
            'mobile' => '09123456789',
            'code' => $code,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'mobile', 'is_verified'], 'is_new_user'])
            ->assertJsonPath('is_new_user', true);
        $this->withToken($response->json('token'))->getJson('/api/user/profile')->assertOk();
        $this->assertDatabaseHas('users', ['mobile' => '09123456789', 'role' => 'user']);
        $this->assertNotNull(Otp::where('mobile', '09123456789')->latest()->value('consumed_at'));
    }

    public function test_new_user_can_continue_to_profile_after_otp_login(): void
    {
        $this->postJson('/api/auth/send-otp', ['mobile' => '09111111111']);
        $code = Otp::where('mobile', '09111111111')->latest()->value('code');

        $response = $this->postJson('/api/auth/verify-otp', ['mobile' => '09111111111', 'code' => $code]);

        $response->assertOk()->assertJsonPath('is_new_user', true)->assertJsonPath('user.mobile', '09111111111');
        $this->assertDatabaseHas('users', ['mobile' => '09111111111', 'is_verified' => false]);
    }

    public function test_existing_user_can_log_in_again(): void
    {
        $user = User::factory()->create(['mobile' => '09123456789']);
        Otp::create(['mobile' => '09123456789', 'code' => '12345', 'expires_at' => now()->addMinutes(2)]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'mobile' => $user->mobile,
            'code' => '12345',
        ]);

        $response->assertOk()->assertJsonPath('user.id', $user->id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_authenticated_user_can_logout_and_revoke_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJson(['message' => 'با موفقیت خارج شدید.']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_expired_otp_is_rejected(): void
    {
        Otp::create(['mobile' => '09123456789', 'code' => '12345', 'expires_at' => now()->subSecond()]);

        $this->postJson('/api/auth/verify-otp', ['mobile' => '09123456789', 'code' => '12345'])
            ->assertStatus(422);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_consumed_otp_is_rejected(): void
    {
        Otp::create(['mobile' => '09123456789', 'code' => '12345', 'expires_at' => now()->addMinutes(2), 'consumed_at' => now()]);

        $this->postJson('/api/auth/verify-otp', ['mobile' => '09123456789', 'code' => '12345'])
            ->assertStatus(422);
        $this->assertDatabaseCount('users', 0);
    }
}