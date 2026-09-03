<?php
// tests/Feature/FormExportCsvTest.php
namespace Tests\Feature;

use App\Models\Form;
use App\Support\FormCsvExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormExportCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_contains_labels_and_answers_in_field_order(): void
    {
        $form = Form::create(['title' => 'Daftar', 'slug' => 'daftar', 'status' => 'published']);
        $f1 = $form->fields()->create(['label' => 'Nama', 'type' => 'text', 'sort_order' => 1]);
        $f2 = $form->fields()->create(['label' => 'Ekskul', 'type' => 'checkbox', 'options' => ['Basket', 'Musik'], 'sort_order' => 2]);
        $form->submissions()->create(['data' => [$f1->id => 'Budi', $f2->id => ['Basket', 'Musik']]]);

        $csv = FormCsvExporter::toCsvString($form->fresh());
        $lines = array_values(array_filter(explode("\n", trim($csv))));

        // Header + 1 baris data.
        $this->assertStringContainsString('Waktu', $lines[0]);
        $this->assertStringContainsString('Nama', $lines[0]);
        $this->assertStringContainsString('Ekskul', $lines[0]);
        $this->assertStringContainsString('Budi', $lines[1]);
        $this->assertStringContainsString('Basket, Musik', $lines[1]);
    }

    public function test_download_returns_csv_response(): void
    {
        $form = Form::create(['title' => 'Kosong', 'slug' => 'kosong', 'status' => 'published']);

        $response = FormCsvExporter::download($form);

        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('jawaban-kosong.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_csv_uses_semicolon_delimiter_and_utf8_bom(): void
    {
        $form = Form::create(['title' => 'Daftar', 'slug' => 'daftar-sep', 'status' => 'published']);
        $f1 = $form->fields()->create(['label' => 'Nama', 'type' => 'text', 'sort_order' => 1]);
        $f2 = $form->fields()->create(['label' => 'Umur', 'type' => 'text', 'sort_order' => 2]);
        $form->submissions()->create(['data' => [$f1->id => 'Budi', $f2->id => '17']]);

        $csv = FormCsvExporter::toCsvString($form->fresh());

        // Diawali BOM UTF-8 agar Excel mengenali encoding.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        // Kolom dipisah dengan ';' (bukan koma) → terbuka rapi per kolom di Excel ID.
        $this->assertStringContainsString('Waktu;Nama;Umur', $csv);
        $this->assertStringContainsString(';Budi;17', $csv);
    }

    public function test_csv_neutralizes_formula_injection(): void
    {
        $form = Form::create(['title' => 'Daftar', 'slug' => 'daftar-formula', 'status' => 'published']);
        $f1 = $form->fields()->create(['label' => 'Nama', 'type' => 'text', 'sort_order' => 1]);
        $form->submissions()->create(['data' => [$f1->id => '=HYPERLINK("http://evil","x")']]);
        $form->submissions()->create(['data' => [$f1->id => 'Budi']]);

        $csv = FormCsvExporter::toCsvString($form->fresh());

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString("'Budi", $csv);
    }
}
