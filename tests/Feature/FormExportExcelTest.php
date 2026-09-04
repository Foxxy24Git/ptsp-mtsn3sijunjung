<?php
// tests/Feature/FormExportExcelTest.php
namespace Tests\Feature;

use App\Models\Form;
use App\Support\FormExcelExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class FormExportExcelTest extends TestCase
{
    use RefreshDatabase;

    public function test_xlsx_contains_labels_and_answers_in_field_order(): void
    {
        $form = Form::create(['title' => 'Daftar', 'slug' => 'daftar', 'status' => 'published']);
        $f1 = $form->fields()->create(['label' => 'Nama', 'type' => 'text', 'sort_order' => 1]);
        $f2 = $form->fields()->create(['label' => 'Ekskul', 'type' => 'checkbox', 'options' => ['Basket', 'Musik'], 'sort_order' => 2]);
        $form->submissions()->create(['data' => [$f1->id => 'Budi', $f2->id => ['Basket', 'Musik']]]);

        $rows = $this->readRows(FormExcelExporter::toXlsxString($form->fresh()));

        $this->assertSame(['Waktu', 'Nama', 'Ekskul'], $rows[0]);
        $this->assertSame('Budi', $rows[1][1]);
        $this->assertSame('Basket, Musik', $rows[1][2]);
    }

    public function test_download_returns_xlsx_response(): void
    {
        $form = Form::create(['title' => 'Kosong', 'slug' => 'kosong', 'status' => 'published']);

        $response = FormExcelExporter::download($form);

        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type'),
        );
        $this->assertStringContainsString('jawaban-kosong.xlsx', $response->headers->get('Content-Disposition'));
    }

    public function test_xlsx_neutralizes_formula_injection(): void
    {
        $form = Form::create(['title' => 'Daftar', 'slug' => 'daftar-formula', 'status' => 'published']);
        $f1 = $form->fields()->create(['label' => 'Nama', 'type' => 'text', 'sort_order' => 1]);
        $form->submissions()->create(['data' => [$f1->id => '=HYPERLINK("http://evil","x")']]);

        $rows = $this->readRows(FormExcelExporter::toXlsxString($form->fresh()));

        // Tersimpan apa adanya sebagai teks (StringCell), bukan formula aktif yang dieksekusi Excel.
        $this->assertSame('=HYPERLINK("http://evil","x")', $rows[1][1]);
    }

    public function test_all_permohonan_xlsx_contains_expected_columns_and_row(): void
    {
        $form = Form::create(['title' => 'SPP Surat Keterangan', 'slug' => 'spp-surat', 'status' => 'published']);
        $form->submissions()->create([
            'receipt_code' => 'PTSP-2609-0001',
            'applicant_name' => 'kurnia',
            'applicant_whatsapp' => '6282288230000',
            'applicant_email' => 'kurnia24fajri@gmail.com',
            'status' => 'diajukan',
            'data' => [],
        ]);

        $rows = $this->readRows(FormExcelExporter::toXlsxStringAll());

        $this->assertSame(
            ['Kode Resi', 'Layanan', 'Nama Pemohon', 'WhatsApp', 'Email', 'Status', 'Catatan', 'Tanggal Pengajuan'],
            $rows[0],
        );
        $this->assertSame('PTSP-2609-0001', $rows[1][0]);
        $this->assertSame('SPP Surat Keterangan', $rows[1][1]);
        $this->assertSame('6282288230000', $rows[1][3]);
        $this->assertSame('Diajukan', $rows[1][5]);
    }

    /** @return array<int, array<int, string>> */
    private function readRows(string $bytes): array
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx_read_');
        file_put_contents($path, $bytes);

        $reader = new Reader;
        $reader->open($path);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
            break;
        }

        $reader->close();
        unlink($path);

        return $rows;
    }
}
