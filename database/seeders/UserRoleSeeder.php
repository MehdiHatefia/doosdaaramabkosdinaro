<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['mobile' => '09120000001', 'name' => 'مدیر ارشد سامانه', 'roles' => ['super-admin']],
            ['mobile' => '09120000002', 'name' => 'مدیر سیستم', 'roles' => ['admin']],
            ['mobile' => '09120000003', 'name' => 'اپراتور بررسی آگهی', 'roles' => ['operator']],
            ['mobile' => '09120000004', 'name' => 'فروشنده نمونه', 'roles' => ['seller']],
            ['mobile' => '09120000005', 'name' => 'متقاضی نمونه', 'roles' => ['buyer']],
            ['mobile' => '09120000006', 'name' => 'کاربر فعال بازار', 'roles' => ['seller', 'buyer']],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['mobile' => $userData['mobile']],
                ['name' => $userData['name']],
            );
            $user->syncRoles($userData['roles']);

            Log::info('Sample user role assignments synchronized', [
                'function' => __METHOD__,
                'user_id' => $user->id,
                'payload' => ['mobile' => $userData['mobile']],
                'trace' => null,
                'mobile' => $userData['mobile'],
                'roles' => $userData['roles'],
            ]);
        }
    }
}