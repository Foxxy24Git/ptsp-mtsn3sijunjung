<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_services_published_menyaring_draft_dan_form_biasa(): void
    {
        Form::create(['title' => 'Layanan Terbit', 'slug' => 'terbit', 'status' => 'published', 'is_service' => true]);
        Form::create(['title' => 'Layanan Draft', 'slug' => 'draft', 'status' => 'draft', 'is_service' => true]);
        Form::create(['title' => 'Form Kontak', 'slug' => 'kontak', 'status' => 'published', 'is_service' => false]);

        $hasil = Form::query()->services()->published()->get();

        $this->assertCount(1, $hasil);
        $this->assertSame('Layanan Terbit', $hasil->first()->title);
    }

    public function test_scope_ordered_mengurutkan_sort_order_lalu_id(): void
    {
        $b = Form::create(['title' => 'B', 'slug' => 'b', 'status' => 'published', 'sort_order' => 2]);
        $a = Form::create(['title' => 'A', 'slug' => 'a', 'status' => 'published', 'sort_order' => 1]);
        $c = Form::create(['title' => 'C', 'slug' => 'c', 'status' => 'published', 'sort_order' => 1]);

        $this->assertSame(
            [$a->id, $c->id, $b->id],
            Form::query()->ordered()->pluck('id')->all(),
        );
    }

    public function test_layanan_terhubung_ke_satuan_kerja(): void
    {
        $unit = WorkUnit::create(['name' => 'Tata Usaha (TU)', 'slug' => 'tata-usaha', 'sort_order' => 1]);
        $layanan = Form::create([
            'title' => 'Pengambilan Ijazah',
            'slug' => 'pengambilan-ijazah',
            'status' => 'published',
            'work_unit_id' => $unit->id,
            'organizer' => 'PTSP',
            'duration_text' => '30 Menit',
            'fee_text' => 'Gratis',
        ]);

        $this->assertSame('Tata Usaha (TU)', $layanan->workUnit->name);
        $this->assertTrue($unit->services->contains($layanan));
    }

    public function test_menghapus_satuan_kerja_tidak_menghapus_layanannya(): void
    {
        $unit = WorkUnit::create(['name' => 'Kesiswaan', 'slug' => 'kesiswaan']);
        $layanan = Form::create(['title' => 'Mutasi', 'slug' => 'mutasi', 'status' => 'published', 'work_unit_id' => $unit->id]);

        $unit->delete();

        $this->assertNull($layanan->fresh()->work_unit_id);
        $this->assertNotNull($layanan->fresh());
    }

    public function test_is_published_service_hanya_benar_untuk_layanan_terbit(): void
    {
        $terbit = Form::create(['title' => 'A', 'slug' => 'a', 'status' => 'published', 'is_service' => true]);
        $draft = Form::create(['title' => 'B', 'slug' => 'b', 'status' => 'draft', 'is_service' => true]);
        $biasa = Form::create(['title' => 'C', 'slug' => 'c', 'status' => 'published', 'is_service' => false]);

        $this->assertTrue($terbit->isPublishedService());
        $this->assertFalse($draft->isPublishedService());
        $this->assertFalse($biasa->isPublishedService());
    }

    public function test_field_menyimpan_help_text(): void
    {
        $layanan = Form::create(['title' => 'X', 'slug' => 'x', 'status' => 'published']);
        $field = $layanan->fields()->create([
            'label' => 'Tanggal Lahir',
            'type' => 'date',
            'help_text' => 'Sesuai akta kelahiran.',
            'sort_order' => 1,
        ]);

        $this->assertSame('Sesuai akta kelahiran.', $field->fresh()->help_text);
        $this->assertSame('date', $field->fresh()->type);
    }
}
