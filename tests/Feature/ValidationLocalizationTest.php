<?php

namespace Tests\Feature;

use Tests\TestCase;

class ValidationLocalizationTest extends TestCase
{
    public function test_validation_errors_are_returned_in_persian(): void
    {
        $response = $this->postJson('/api/auth/send-otp', ['mobile' => '123']);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'اطلاعات وارد شده نامعتبر است')
            ->assertJsonPath('errors.mobile.0', 'قالب شماره موبایل معتبر نیست.');
    }
}