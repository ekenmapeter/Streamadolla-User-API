<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@streamadolla.com'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'password' => Hash::make('password'), // You should change this after first login
            ]
        );
    }
}
