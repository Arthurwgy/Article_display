<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '管理员',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'coin_balance' => 0,
                'gold_balance' => 0,
            ]
        );
    }
}
