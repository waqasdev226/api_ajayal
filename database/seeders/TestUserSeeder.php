<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
            'enabled' => true,
            'reference' => 'AL_TEST001',
            'cash' => 10000.00,
            'profit' => 500.00,
            'total_profit' => 1500.00,
            'min_ratio' => 5.00,
            'max_ratio' => 15.00,
            'currency' => 'USD',
            'city' => 'Test City',
            'document_type' => 'passport',
        ]);

        // Create another test user for transfers
        User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'phone' => '0987654321',
            'password' => Hash::make('password123'),
            'enabled' => true,
            'reference' => 'AL_TEST002',
            'cash' => 5000.00,
            'profit' => 250.00,
            'total_profit' => 750.00,
            'min_ratio' => 5.00,
            'max_ratio' => 15.00,
            'currency' => 'USD',
            'city' => 'Test City 2',
            'document_type' => 'id_card',
        ]);

        // Create an agent
        \App\Models\Agent::create([
            'name' => 'Test Agent',
            'email' => 'agent@example.com',
            'password' => Hash::make('password123'),
        ]);
    }
}
