<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@bank.test'],
            [
                'name' => 'Admin Teller',
                'password' => Hash::make('admin123'),
            ]
        );

        $admin->assignRole('admin');

        $pimpinan = User::firstOrCreate(
            ['email' => 'pimpinan@bank.test'],
            [
                'name' => 'Pimpinan',
                'password' => Hash::make('admin123'),
            ]
        );

        $pimpinan->assignRole('pimpinan');
    }
}
