<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $username = config('admin.username', 'admin');
        $password = config('admin.password', 'admin123');

        User::updateOrCreate(
            ['email' => 'admin@portfolio.test'],
            [
                'name' => $username,
                'password' => Hash::make($password),
            ]
        );
    }
}
