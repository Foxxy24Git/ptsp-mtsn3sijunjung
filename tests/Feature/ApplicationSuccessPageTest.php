<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationSuccessPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_menampilkan_kode_resi_dari_session(): void
    {
        $this->withSession([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'service_title' => 'SPP Pengambilan Ijazah',
        ])->get('/permohonan/selesai')
            ->assertOk()
            ->assertSee('PTSP-2609-A7K3QX')
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertSee('Simpan kode ini');
    }

    public function test_tanpa_session_dialihkan_ke_halaman_lacak(): void
    {
        // Mencegah kode resi orang lain ditemukan dengan membuka URL langsung.
        $this->get('/permohonan/selesai')->assertRedirect('/lacak');
    }
}
