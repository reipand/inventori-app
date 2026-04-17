<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@toko.com'],
            [
                'password' => 'password123',
                'role' => 'pengelola',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
    }
}
