<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // customer
            'customer.create',
            'customer.view',
            'customer.update',
            'customer.delete',

            // transaction
            'transaction.deposit',
            'transaction.withdraw',
            'transaction.transfer',
            'transaction.view',

            // user management
            'user.manage',

            // export
            'transaction.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::where('name', 'admin')->first();
        $pimpinan = Role::where('name', 'pimpinan')->first();

        // Admin (Teller)
        $admin->syncPermissions([
            'customer.create',
            'customer.view',
            'customer.update',

            'transaction.deposit',
            'transaction.withdraw',
            'transaction.transfer',
            'transaction.view',

            'transaction.export',
        ]);

        // Pimpinan
        $pimpinan->syncPermissions([
            'customer.view',
            'customer.delete',

            'transaction.view',
            'user.manage',
        ]);
    }
}
