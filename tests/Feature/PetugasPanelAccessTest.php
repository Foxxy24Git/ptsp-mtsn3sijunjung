<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Models\Form;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PetugasPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_pergi_saat_akses_petugas(): void
    {
        $this->get('/petugas')->assertRedirect();
    }

    public function test_petugas_bisa_membuka_dashboard_petugas(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas)->get('/petugas')->assertOk();
    }

    /**
     * Batas aksesnya tetap: petugas tidak pernah bisa merender halaman admin.
     * Yang berubah cuma cara menolaknya — dulu 403 buntu, sekarang dilempar ke
     * form login panel itu supaya pengguna bisa langsung ganti akun.
     */
    public function test_petugas_diarahkan_ke_form_login_admin_saat_membuka_panel_admin(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas)->get('/admin')->assertRedirect(url('/admin/login'));
    }

    public function test_admin_diarahkan_ke_form_login_petugas_saat_membuka_panel_petugas(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/petugas')->assertRedirect(url('/petugas/login'));
    }

    public function test_login_tidak_terlempar_ke_panel_lain_karena_intended_url_basi(): void
    {
        // Tamu yang sempat membuka /petugas bikin Filament menyimpan
        // url.intended = /petugas. Kalau setelah itu dia login sebagai admin,
        // redirect()->intended() bawaan Filament menurutinya dan melempar
        // admin ke panel petugas — yang jelas ditolak untuknya. Tujuan akhir
        // login harus selalu panel tempat dia baru saja login.
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMINISTRATOR,
            'password' => 'password',
        ]);

        $this->get('/petugas')->assertRedirect(url('/petugas/login'));

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->set('data.email', $admin->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertRedirect(url('/admin'));
    }

    public function test_membuka_form_login_panel_lain_mengakhiri_sesi_yang_lama(): void
    {
        // Satu guard `web` hanya boleh memegang satu identitas. Kalau sesi
        // lama dibiarkan hidup saat form login panel lain dibuka,
        // `password_hash_web` di sesi tetap milik user lama dan middleware
        // AuthenticateSession akan melogout semuanya di request berikutnya —
        // login jadi gagal diam-diam. Jadi sesi lama sengaja diakhiri di
        // sini supaya login berikutnya berangkat dari sesi yang bersih.
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas)->get('/admin/login')->assertOk();

        $this->assertGuest();
    }

    public function test_admin_yang_sudah_login_tetap_bisa_membuka_form_login_petugas(): void
    {
        // Bug: admin yang sudah login otomatis di-redirect ke dashboard
        // petugas oleh Login::mount() bawaan Filament (admin & petugas
        // berbagi guard `web`), lalu middleware Authenticate menolaknya
        // dengan 403 — admin tak pernah sampai melihat form login petugas.
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->actingAs($admin)
            ->get('/petugas/login')
            ->assertOk()
            ->assertSee('Sign in');
    }

    public function test_petugas_yang_sudah_login_tetap_bisa_membuka_form_login_admin(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas)
            ->get('/admin/login')
            ->assertOk()
            ->assertSee('Sign in');
    }

    public function test_admin_bisa_login_ulang_sebagai_petugas_setelah_buka_form_login_petugas(): void
    {
        // Menguji sampai tuntas ke submit form (bukan cuma GET halaman
        // login): memastikan sesi admin yang di-logout paksa oleh
        // Login::mount() tidak meninggalkan state rusak yang menggagalkan
        // proses autentikasi petugas berikutnya di request yang sama.
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);
        $petugas = User::factory()->create([
            'role' => User::ROLE_PETUGAS,
            'password' => 'password',
        ]);

        $this->actingAs($admin)->get('/petugas/login')->assertOk();

        Filament::setCurrentPanel(Filament::getPanel('petugas'));

        Livewire::test(Login::class)
            ->set('data.email', $petugas->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($petugas);
    }

    public function test_petugas_melihat_antrian_permohonan(): void
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);
        $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [],
        ]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas/permohonan')
            ->assertOk()
            ->assertSee('PTSP-2609-A7K3QX')
            ->assertSee('Budi Santoso');
    }

    public function test_petugas_tidak_melihat_tombol_export_excel(): void
    {
        // FormSubmissionsTable (dipakai ulang dari admin) punya toolbar
        // Export Excel bawaan — resource petugas sengaja menghapusnya
        // (spec §2.4), jadi ini pengaman supaya perubahan tak sengaja pada
        // PermohonanResource tidak mengembalikannya diam-diam.
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas/permohonan')
            ->assertOk()
            ->assertDontSee('Export Excel');
    }

    public function test_admin_tetap_melihat_tombol_export_excel(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]))
            ->get('/admin/form-submissions')
            ->assertOk()
            ->assertSee('Export Excel');
    }
}
