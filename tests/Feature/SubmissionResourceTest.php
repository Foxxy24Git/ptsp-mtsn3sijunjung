<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_permohonan_menampilkan_resi_dan_pemohon(): void
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);
        $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [],
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/form-submissions')
            ->assertOk()
            ->assertSee('PTSP-2609-A7K3QX')
            ->assertSee('Budi Santoso')
            ->assertSee('SPP Pengambilan Ijazah');
    }

    public function test_tamu_tidak_bisa_membuka_daftar_permohonan(): void
    {
        $this->get('/admin/form-submissions')->assertRedirect();
    }
}
