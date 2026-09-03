<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PetugasUserSeeder extends Seeder
{
    /**
     * Seed satu akun Petugas demo untuk pengujian manual lokal.
     *
     * Idempotent: aman dijalankan berulang (updateOrCreate berdasarkan email).
     * Akun petugas sungguhan dibuat Administrator lewat resource "Kelola
     * Petugas" di panel admin — seeder ini murni kemudahan pengujian lokal.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'petugas@sekolah.test'],
            [
                'name' => 'Petugas Demo',
                'password' => Hash::make('password'),
                'role' => User::ROLE_PETUGAS,
                'email_verified_at' => now(),
            ],
        );
    }
}
