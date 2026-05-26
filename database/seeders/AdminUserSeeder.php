<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@site.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin@1234'),
                'is_admin' => true,
            ]
        );
    }
}
