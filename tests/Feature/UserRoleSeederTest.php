<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_seeder_creates_sample_users_with_exact_roles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(UserRoleSeeder::class);

        $expectedUsers = [
            '09120000001' => ['مدیر ارشد سامانه', ['super-admin']],
            '09120000002' => ['مدیر سیستم', ['admin']],
            '09120000003' => ['اپراتور بررسی آگهی', ['operator']],
            '09120000004' => ['فروشنده نمونه', ['seller']],
            '09120000005' => ['متقاضی نمونه', ['buyer']],
            '09120000006' => ['کاربر فعال بازار', ['seller', 'buyer']],
        ];

        foreach ($expectedUsers as $mobile => [$name, $roles]) {
            $user = User::where('mobile', $mobile)->first();

            $this->assertNotNull($user);
            $this->assertSame($name, $user->name);
            $this->assertSame($roles, $user->getRoleNames()->all());
        }
    }
}