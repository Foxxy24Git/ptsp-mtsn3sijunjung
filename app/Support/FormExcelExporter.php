<?php
// app/Support/FormExcelExporter.php
namespace App\Support;

use App\Models\Form;
use App\Models\FormSubmission;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FormExcelExporter
{
    private const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /** Lebar kolom tetap untuk export seluruh permohonan (panel Permohonan). */
    private const ALL_COLUMNS = [
        'Kode Resi' => 18.0,
        'Layanan' => 24.0,
        'Nama Pemohon' => 22.0,
        'WhatsApp' => 16.0,
        'Email' => 28.0,
        'Status' => 12.0,
        'Catatan' => 34.0,
        'Tanggal Pengajuan' => 20.0,
    ];

    /**
     * Bangun file xlsx jawaban satu form (header Waktu + label field),
     * kembalikan isinya sebagai string biner.
     */
    public static function toXlsxString(Form $form): string
    {
        $path = self::buildFormWorkbook($form);
        $bytes = file_get_contents($path);
        unlink($path);

        return $bytes;
    }

    /** Stream xlsx jawaban satu form sebagai unduhan file. */
    public static function download(Form $form): BinaryFileResponse
    {
        $path = self::buildFormWorkbook($form);

        return response()
            ->download($path, 'jawaban-'.$form->slug.'.xlsx', ['Content-Type' => self::CONTENT_TYPE])
            ->deleteFileAfterSend(true);
    }

    /**
     * Bangun file xlsx seluruh permohonan lintas layanan (panel Permohonan),
     * kembalikan isinya sebagai string biner.
     */
    public static function toXlsxStringAll(): string
    {
        $path = self::buildAllWorkbook();
        $bytes = file_get_contents($path);
        unlink($path);

        return $bytes;
    }

    /** Stream xlsx seluruh permohonan sebagai unduhan file. */
    public static function downloadAll(): BinaryFileResponse
    {
        $path = self::buildAllWorkbook();

        return response()
            ->download($path, 'permohonan-ptsp.xlsx', ['Content-Type' => self::CONTENT_TYPE])
            ->deleteFileAfterSend(true);
    }

    private static function buildFormWorkbook(Form $form): string
    {
        $form->loadMissing('fields');
        $fields = $form->fields;

        $columns = ['Waktu' => 20.0];
        foreach ($fields as $field) {
            $columns[$field->label] = self::widthForLabel($field->label);
        }

        $rows = [];
        foreach ($form->submissions()->orderBy('created_at')->get() as $submission) {
            $row = [self::formatDate($submission->created_at)];
            foreach ($fields as $field) {
                $value = $submission->data[$field->id] ?? '';
                $row[] = is_array($value) ? implode(', ', $value) : (string) $value;
            }
            $rows[] = $row;
        }

        return self::writeWorkbook($columns, $rows);
    }

    private static function buildAllWorkbook(): string
    {
        $rows = [];
        foreach (FormSubmission::with('form')->latest('created_at')->cursor() as $submission) {
            $rows[] = [
                (string) $submission->receipt_code,
                (string) $submission->form?->title,
                (string) $submission->applicant_name,
                (string) $submission->applicant_whatsapp,
                (string) $submission->applicant_email,
                FormSubmission::STATUSES[$submission->status] ?? $submission->status,
                (string) $submission->admin_note,
                self::formatDate($submission->created_at),
            ];
        }

        return self::writeWorkbook(self::ALL_COLUMNS, $rows);
    }

    /**
     * Tulis workbook xlsx ke file sementara dan kembalikan path-nya.
     * Header dicetak tebal & baris pertama dibekukan (freeze) agar tetap
     * terlihat saat admin scroll data yang panjang.
     *
     * @param  array<string, float>  $columns  label kolom => lebar kolom
     * @param  iterable<array<int, string>>  $rows
     */
    private static function writeWorkbook(array $columns, iterable $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ptsp_export_');

        $writer = new Writer;
        $writer->openToFile($path);

        $sheet = $writer->getCurrentSheet();
        $sheet->setSheetView((new SheetView)->setFreezeRow(2));

        $index = 1;
        foreach ($columns as $width) {
            $sheet->setColumnWidth($width, $index++);
        }

        $headerStyle = (new Style)
            ->setFontBold()
            ->setBackgroundColor(Color::toARGB(Color::rgb(243, 244, 246)));

        $writer->addRow(self::row(array_keys($columns), $headerStyle));
        foreach ($rows as $row) {
            $writer->addRow(self::row($row));
        }

        $writer->close();

        return $path;
    }

    /**
     * Bangun baris dari nilai teks polos. Setiap sel dibuat eksplisit sebagai
     * StringCell (bukan lewat Cell::fromValue/Row::fromValues) supaya nilai
     * yang diawali "=" dari input pemohon tidak pernah dibaca openspout
     * sebagai formula aktif (proteksi formula/CSV injection).
     *
     * @param  string[]  $values
     */
    private static function row(array $values, ?Style $style = null): Row
    {
        $cells = array_map(static fn (string $value): StringCell => new StringCell($value, null), $values);

        return new Row($cells, $style);
    }

    private static function formatDate(\DateTimeInterface $date): string
    {
        return $date->format('d M Y H:i');
    }

    private static function widthForLabel(string $label): float
    {
        return (float) max(14, min(40, mb_strlen($label) + 6));
    }
}
