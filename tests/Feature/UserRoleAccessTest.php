<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_default_adalah_administrator(): void
    {
        $user = User::factory()->create();

        // Sengaja TIDAK memanggil refresh(): default harus sudah benar di
        // objek hasil create(), bukan cuma setelah dibaca ulang dari DB —
        // itulah yang membuat canAccessPanel() aman dipakai di request yang
        // sama dengan pembuatan user.
        $this->assertSame('administrator', $user->role);
    }

    public function test_administrator_hanya_bisa_akses_panel_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->assertTrue($admin->canAccessPanel(Panel::make()->id('admin')));
        $this->assertFalse($admin->canAccessPanel(Panel::make()->id('petugas')));
    }

    public function test_petugas_hanya_bisa_akses_panel_petugas(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->assertFalse($petugas->canAccessPanel(Panel::make()->id('admin')));
        $this->assertTrue($petugas->canAccessPanel(Panel::make()->id('petugas')));
    }

    public function test_panel_tak_dikenal_selalu_ditolak(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->assertFalse($admin->canAccessPanel(Panel::make()->id('entah-apa')));
    }

    public function test_daftar_role_berisi_dua_nilai(): void
    {
        $this->assertSame(
            ['administrator', 'petugas'],
            array_keys(User::ROLES),
        );
    }
}
