<?php
// app/Support/FormCsvExporter.php
namespace App\Support;

use App\Models\Form;
use App\Models\FormSubmission;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormCsvExporter
{
    /**
     * Pemisah kolom. Titik-koma agar terbuka rapi per kolom di Excel dengan
     * setelan regional Indonesia/Eropa (yang memakai ";" sebagai list separator).
     */
    private const DELIMITER = ';';

    /** BOM UTF-8 agar Excel mengenali encoding & karakter tampil benar. */
    private const BOM = "\xEF\xBB\xBF";

    /**
     * Bangun isi CSV: header (Waktu + label field) lalu satu baris per submission.
     */
    public static function toCsvString(Form $form): string
    {
        $form->loadMissing('fields');
        $fields = $form->fields;

        $out = fopen('php://temp', 'r+');

        $header = [self::escapeCell('Waktu')];
        foreach ($fields as $field) {
            $header[] = self::escapeCell($field->label);
        }
        fputcsv($out, $header, self::DELIMITER, '"', '');

        foreach ($form->submissions()->orderBy('created_at')->get() as $submission) {
            $row = [self::escapeCell((string) $submission->created_at)];
            foreach ($fields as $field) {
                $value = $submission->data[$field->id] ?? '';
                $value = is_array($value) ? implode(', ', $value) : (string) $value;
                $row[] = self::escapeCell($value);
            }
            fputcsv($out, $row, self::DELIMITER, '"', '');
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return self::BOM.$csv;
    }

    /**
     * Netralisasi nilai sel agar tidak dieksekusi sebagai formula oleh aplikasi
     * spreadsheet (CSV/formula injection) saat karakter awal berbahaya.
     */
    private static function escapeCell(string $value): string
    {
        if ($value !== '' && str_contains("=+-@\t\r", $value[0])) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Stream CSV sebagai unduhan file.
     */
    public static function download(Form $form): StreamedResponse
    {
        $filename = 'jawaban-'.$form->slug.'.csv';
        $csv = self::toCsvString($form);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Bangun isi CSV seluruh permohonan lintas layanan, untuk panel Permohonan.
     */
    public static function toCsvStringAll(): string
    {
        $out = fopen('php://temp', 'r+');

        $header = array_map(
            self::escapeCell(...),
            ['Kode Resi', 'Layanan', 'Nama Pemohon', 'WhatsApp', 'Email', 'Status', 'Catatan', 'Tanggal Pengajuan'],
        );
        fputcsv($out, $header, self::DELIMITER, '"', '');

        foreach (FormSubmission::with('form')->latest('created_at')->cursor() as $submission) {
            $row = [
                self::escapeCell((string) $submission->receipt_code),
                self::escapeCell((string) $submission->form?->title),
                self::escapeCell((string) $submission->applicant_name),
                self::escapeCell((string) $submission->applicant_whatsapp),
                self::escapeCell((string) $submission->applicant_email),
                self::escapeCell(FormSubmission::STATUSES[$submission->status] ?? $submission->status),
                self::escapeCell((string) $submission->admin_note),
                self::escapeCell((string) $submission->created_at),
            ];
            fputcsv($out, $row, self::DELIMITER, '"', '');
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return self::BOM.$csv;
    }

    /** Stream CSV seluruh permohonan sebagai unduhan file. */
    public static function downloadAll(): StreamedResponse
    {
        $csv = self::toCsvStringAll();

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'permohonan-ptsp.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
