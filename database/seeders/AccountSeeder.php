<?php

namespace Database\Seeders;

use App\Models\Api\Account;
use App\Models\Api\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role('admin')->first();

        if (! $admin) {
            $this->command->warn('Admin user tidak ditemukan. Pastikan UserSeeder sudah dijalankan.');
            return;
        }

        $nasabah = [
            [
                'full_name'      => 'Budi Santoso',
                'nik'            => '1234567890123456',
                'phone'          => '081234567890',
                'address'        => 'Jl. Merdeka No. 123, Jakarta',
                'account_number' => 'ACC-0001',
                'balance'        => 5_000_000,
            ],
            [
                'full_name'      => 'Siti Aminah',
                'nik'            => '1234567890123457',
                'phone'          => '081234567891',
                'address'        => 'Jl. Sudirman No. 456, Jakarta',
                'account_number' => 'ACC-0002',
                'balance'        => 10_000_000,
            ],
            [
                'full_name'      => 'Andi Wijaya',
                'nik'            => '1234567890123458',
                'phone'          => '081234567892',
                'address'        => 'Jl. Thamrin No. 789, Jakarta',
                'account_number' => 'ACC-0003',
                'balance'        => 750_000,
            ],
        ];

        foreach ($nasabah as $data) {
            $customer = Customer::firstOrCreate(
                ['full_name' => $data['full_name']],
                [
                    'nik'        => $data['nik'],
                    'phone'      => $data['phone'],
                    'address'    => $data['address'],
                    'created_by' => $admin->id,
                ]
            );

            // Buat account untuk customer
            Account::firstOrCreate(
                ['account_number' => $data['account_number']],
                [
                    'customer_id' => $customer->id,
                    'balance'      => $data['balance'],
                    'created_by'   => $admin->id,
                ]
            );
        }
    }
}
