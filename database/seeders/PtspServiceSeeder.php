<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

/**
 * Data awal PTSP. Idempotent: dicocokkan berdasarkan slug dan TIDAK pernah
 * menimpa layanan yang sudah ada, supaya perubahan operator tidak hilang
 * bila seeder dijalankan ulang setelah deploy.
 */
class PtspServiceSeeder extends Seeder
{
    private const SATUAN_KERJA = [
        ['name' => 'Tata Usaha (TU)', 'slug' => 'tata-usaha', 'sort_order' => 1],
        ['name' => 'Kesiswaan', 'slug' => 'kesiswaan', 'sort_order' => 2],
        ['name' => 'P2M2', 'slug' => 'p2m2', 'sort_order' => 3],
    ];

    private const LAYANAN = [
        [
            'slug' => 'pengambilan-ijazah',
            'title' => 'SPP Pengambilan Ijazah',
            'duration_text' => '30 Menit',
            'sort_order' => 1,
            'fields' => [
                ['label' => 'Nama Murid', 'type' => 'text', 'required' => true],
            ],
        ],
        [
            'slug' => 'legalisasi-ijazah-online',
            'title' => 'SPP Legalisasi Ijazah Online',
            'duration_text' => '1-2 Hari',
            'sort_order' => 2,
            'fields' => [
                ['label' => 'Tanda terima pengambilan ijazah/STTB', 'type' => 'text', 'required' => true],
                ['label' => 'Nama', 'type' => 'text', 'required' => true],
                ['label' => 'Upload Ijazah', 'type' => 'file', 'required' => false],
            ],
        ],
        [
            'slug' => 'surat-pengganti-ijazah-hilang',
            'title' => 'SPP Surat Pengganti Ijazah Hilang',
            'duration_text' => '1 Jam',
            'sort_order' => 3,
            'fields' => [
                ['label' => 'Nama', 'type' => 'text', 'required' => true],
                ['label' => 'Upload Surat Kehilangan', 'type' => 'file', 'required' => true],
                ['label' => 'Fotokopi Ijazah', 'type' => 'file', 'required' => true],
                ['label' => 'Foto 3x4', 'type' => 'file', 'required' => true],
            ],
        ],
        [
            'slug' => 'surat-pengganti-ijazah-rusak',
            'title' => 'SPP Surat Pengganti Ijazah Rusak',
            'duration_text' => '58 Menit',
            'sort_order' => 4,
            'fields' => [
                ['label' => 'Nama', 'type' => 'text', 'required' => true],
                ['label' => 'Ijazah Asli', 'type' => 'file', 'required' => true],
                ['label' => 'Ijazah Fotokopi', 'type' => 'file', 'required' => true],
            ],
        ],
        [
            'slug' => 'kesalahan-penulisan-ijazah',
            'title' => 'SPP Kesalahan Penulisan Ijazah',
            'duration_text' => '1 Jam',
            'sort_order' => 5,
            'fields' => [
                ['label' => 'Nama', 'type' => 'text', 'required' => true],
                ['label' => 'Ijazah Asli', 'type' => 'file', 'required' => true],
                ['label' => 'Ijazah Fotokopi', 'type' => 'file', 'required' => true],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::SATUAN_KERJA as $satuan) {
            WorkUnit::firstOrCreate(['slug' => $satuan['slug']], $satuan);
        }

        $tataUsaha = WorkUnit::where('slug', 'tata-usaha')->sole();

        foreach (self::LAYANAN as $definisi) {
            $layanan = Form::firstOrCreate(
                ['slug' => $definisi['slug']],
                [
                    'title' => $definisi['title'],
                    'status' => 'published',
                    'is_service' => true,
                    'organizer' => 'PTSP',
                    'work_unit_id' => $tataUsaha->id,
                    'duration_text' => $definisi['duration_text'],
                    'fee_text' => 'Gratis',
                    'sort_order' => $definisi['sort_order'],
                    'requirements' => '<p>Rincian persyaratan layanan ini belum diisi. Silakan ubah lewat menu Layanan di panel admin.</p>',
                    'success_message' => 'Permohonan Anda telah kami terima.',
                ],
            );

            // Field hanya dibuat saat layanan baru lahir. Bila operator sudah
            // menyesuaikan daftar isian, seeder tidak boleh menambahinya lagi.
            if (! $layanan->wasRecentlyCreated) {
                continue;
            }

            foreach ($definisi['fields'] as $urutan => $field) {
                $layanan->fields()->create([
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'required' => $field['required'],
                    'sort_order' => $urutan + 1,
                ]);
            }
        }
    }
}
