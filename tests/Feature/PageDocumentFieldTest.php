<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Fitur "Dokumen Terkait" pada Halaman (Pages): admin bisa mengunggah satu atau
 * lebih berkas (PDF/DOC/DOCX) lewat koleksi media 'documents', tampil sebagai
 * kartu unduhan di halaman publik -- terpisah dari 'featured' yang tetap
 * khusus gambar banner.
 */
class PageDocumentFieldTest extends TestCase
{
    use RefreshDatabase;

    private function halaman(array $attributes = []): Page
    {
        return Page::create(array_merge([
            'title' => 'SOP Layanan',
            'slug' => 'sop-layanan',
            'status' => 'published',
        ], $attributes));
    }

    /**
     * PDF minimal tapi BENAR-BENAR VALID (bukan cuma header "%PDF-1.4" yang
     * dipadatkan): koleksi 'documents' sekarang punya media conversion yang
     * merender halaman pertama lewat Imagick+Ghostscript (lihat
     * Page::registerMediaConversions()) -- Ghostscript gagal/exception kalau
     * dikasih PDF rusak, jadi berkas uji harus bisa dirender sungguhan.
     * Dipadatkan lewat baris komentar PDF ("%...") sebelum %%EOF supaya bisa
     * menyasar ukuran tertentu tanpa merusak strukturnya.
     */
    private function pdfAsli(int $kilobytes): string
    {
        $base = <<<'PDF'
        %PDF-1.4
        1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
        2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj
        3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj
        4 0 obj<</Length 60>>stream
        BT /F1 24 Tf 20 100 Td (Dokumen Uji) Tj ET
        0 0 1 rg 20 20 100 40 re f
        endstream
        endobj
        5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj
        xref
        0 6
        trailer<</Size 6/Root 1 0 R>>
        startxref
        0
        %%EOF
        PDF;

        $padding = max(0, ($kilobytes * 1024) - strlen($base) - 1);

        return str_replace('%%EOF', '%'.str_repeat('0', $padding)."\n%%EOF", $base);
    }

    /**
     * Meniru persis alur nyata Filament SpatieMediaLibraryFileUpload (lihat
     * vendor/filament/spatie-laravel-media-library-plugin/.../SpatieMediaLibraryFileUpload.php
     * method saveUploadedFileUsing): nama berkas DI DISK diacak jadi ULID demi
     * keamanan (mencegah eksekusi file lewat nama tebakan), nama ASLI cuma
     * disimpan di kolom `name`. Kalau test cuma addMedia() polos tanpa
     * usingFileName(), Spatie melestarikan nama asli apa adanya -- beda dari
     * kondisi nyata -- jadi tidak akan menangkap bug salah pakai atribut.
     */
    private function unggahDokumen(Page $halaman, string $namaAsli, int $kilobytes): Media
    {
        $file = UploadedFile::fake()->createWithContent($namaAsli, $this->pdfAsli($kilobytes));

        return $halaman->addMedia($file)
            ->usingName(pathinfo($namaAsli, PATHINFO_FILENAME))
            ->usingFileName(Str::ulid().'.'.pathinfo($namaAsli, PATHINFO_EXTENSION))
            ->toMediaCollection('documents');
    }

    public function test_halaman_menampilkan_kartu_dokumen_terkait_setelah_admin_unggah(): void
    {
        Storage::fake('public');
        $halaman = $this->halaman();
        $this->unggahDokumen($halaman, 'SOP-Pembersihan.pdf', 100);

        $this->get('/sop-layanan')
            ->assertOk()
            ->assertSee('Dokumen Terkait')
            ->assertSee('SOP-Pembersihan.pdf')
            ->assertSee('100 KB');
    }

    public function test_dokumen_terkait_tidak_tampil_bila_halaman_tanpa_dokumen(): void
    {
        $halaman = $this->halaman();

        $this->get('/sop-layanan')
            ->assertOk()
            ->assertDontSee('Dokumen Terkait');
    }

    public function test_kartu_dokumen_menaut_ke_url_dan_nama_unduhan_yang_benar(): void
    {
        Storage::fake('public');
        $halaman = $this->halaman();
        $media = $this->unggahDokumen($halaman, 'SOP-Pembersihan.pdf', 100);

        $this->get('/sop-layanan')
            ->assertOk()
            ->assertSee($media->getUrl(), false)
            ->assertSee('download="SOP-Pembersihan.pdf"', false);
    }

    /**
     * Kartu PDF sekarang menampilkan pratinjau (thumbnail) halaman pertama
     * yang sungguhan dirender, bukan cuma ikon generik -- lewat media
     * conversion 'preview' (Imagick+Ghostscript, lihat Page model).
     */
    public function test_kartu_dokumen_pdf_menampilkan_pratinjau_halaman_pertama(): void
    {
        Storage::fake('public');
        $halaman = $this->halaman();
        $media = $this->unggahDokumen($halaman, 'SOP-Pembersihan.pdf', 100);

        $this->assertTrue($media->hasGeneratedConversion('preview'));

        $this->get('/sop-layanan')
            ->assertOk()
            ->assertSee('<img', false)
            ->assertSee($media->getUrl('preview'), false);
    }

    /**
     * Halaman dengan slug 'zona-integritas' dirender lewat template khusus
     * (pages/zona-integritas.blade.php, lihat PageController::show), bukan
     * pages/show.blade.php generik -- jadi partial dokumen harus dipasang di
     * kedua template, bukan cuma yang generik.
     */
    public function test_halaman_zona_integritas_juga_menampilkan_kartu_dokumen_terkait(): void
    {
        Storage::fake('public');
        $halaman = $this->halaman([
            'title' => 'Zona Integritas',
            'slug' => 'zona-integritas',
        ]);
        $this->unggahDokumen($halaman, 'Piagam-ZI.pdf', 50);

        $this->get('/zona-integritas')
            ->assertOk()
            ->assertSee('Dokumen Terkait')
            ->assertSee('Piagam-ZI.pdf');
    }
}
