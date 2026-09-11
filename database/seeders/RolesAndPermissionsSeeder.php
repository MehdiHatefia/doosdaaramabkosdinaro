<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage-banks', 'approve-ads', 'reject-ads', 'create-ads',
            'manage-own-ads', 'submit-loan-requests', 'search-ads',
            'send-offers', 'view-reports', 'manage-users', 'manage-finance', 'review-kyc',
            'manage-settings', 'view-logs',
        ];
        foreach ($permissions as $permission) Permission::findOrCreate($permission, 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rolePermissions = [
            'super-admin' => $permissions,
            'admin' => ['manage-banks', 'view-reports', 'manage-users', 'manage-finance', 'review-kyc'],
            'operator' => ['approve-ads', 'reject-ads', 'review-kyc'],
            'seller' => ['create-ads', 'manage-own-ads'],
            'buyer' => ['submit-loan-requests', 'search-ads', 'send-offers'],
        ];
        foreach ($rolePermissions as $roleName => $names) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($names);
            Log::info('RBAC role permissions synchronized', [
                'function' => __METHOD__, 'user_id' => null, 'payload' => [],
                'trace' => null, 'role' => $roleName, 'permissions' => $names,
            ]);
        }
    }
}