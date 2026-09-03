<?php

namespace Tests\Feature;

use App\Filament\Resources\Petugas\Pages\CreatePetugas;
use App\Filament\Resources\Petugas\Pages\EditPetugas;
use App\Filament\Resources\Petugas\Pages\ListPetugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PetugasResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]));
    }

    public function test_admin_bisa_membuat_akun_petugas(): void
    {
        Livewire::test(CreatePetugas::class)
            ->fillForm([
                'name' => 'Siti Rahma',
                'email' => 'siti@sekolah.test',
                'password' => 'rahasia123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $petugas = User::where('email', 'siti@sekolah.test')->sole();
        $this->assertSame(User::ROLE_PETUGAS, $petugas->role);
        $this->assertTrue(Hash::check('rahasia123', $petugas->password));
    }

    public function test_daftar_petugas_tidak_menampilkan_akun_administrator(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS, 'name' => 'Petugas Satu']);
        $adminLain = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR, 'name' => 'Admin Lain']);

        Livewire::test(ListPetugas::class)
            ->assertCanSeeTableRecords([$petugas])
            ->assertCanNotSeeTableRecords([$adminLain]);
    }

    public function test_mengedit_tanpa_isi_password_tidak_mengubah_password_lama(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);
        $passwordLama = $petugas->password;

        Livewire::test(EditPetugas::class, ['record' => $petugas->getRouteKey()])
            ->fillForm(['name' => 'Nama Baru', 'email' => $petugas->email, 'password' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($passwordLama, $petugas->fresh()->password);
        $this->assertSame('Nama Baru', $petugas->fresh()->name);
    }

    public function test_admin_bisa_menghapus_akun_petugas(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        Livewire::test(EditPetugas::class, ['record' => $petugas->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($petugas);
    }
}
