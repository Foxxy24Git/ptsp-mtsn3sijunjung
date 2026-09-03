<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PetugasUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_satu_akun_petugas_demo(): void
    {
        $this->seed(PetugasUserSeeder::class);

        $petugas = User::where('email', 'petugas@sekolah.test')->sole();
        $this->assertSame(User::ROLE_PETUGAS, $petugas->role);
    }

    public function test_seeder_aman_dijalankan_dua_kali(): void
    {
        $this->seed(PetugasUserSeeder::class);
        $this->seed(PetugasUserSeeder::class);

        $this->assertSame(1, User::where('email', 'petugas@sekolah.test')->count());
    }
}
