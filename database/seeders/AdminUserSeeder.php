<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed satu user Administrator default.
     *
     * Idempotent: aman dijalankan berulang (updateOrCreate berdasarkan email).
     * Kredensial default WAJIB diganti setelah deploy ke client.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sekolah.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
