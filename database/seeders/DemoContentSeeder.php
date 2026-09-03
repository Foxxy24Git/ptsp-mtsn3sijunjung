<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoContentSeeder extends Seeder
{
    /**
     * Konten contoh untuk menguji frontend publik (Fase 5).
     *
     * Idempotent (updateOrCreate by slug). TIDAK dipanggil dari DatabaseSeeder
     * default — jalankan manual saat butuh data uji:
     *   php artisan db:seed --class=DemoContentSeeder
     */
    public function run(): void
    {
        // --- Kategori ---
        $categories = collect([
            ['name' => 'Pengumuman', 'slug' => 'pengumuman'],
            ['name' => 'Prestasi', 'slug' => 'prestasi'],
            ['name' => 'Kegiatan', 'slug' => 'kegiatan'],
        ])->map(fn ($c) => PostCategory::updateOrCreate(['slug' => $c['slug']], $c));

        // --- Posts (published) ---
        $posts = [
            ['title' => 'Penerimaan Peserta Didik Baru 2026 Resmi Dibuka', 'slug' => 'ppdb-2026-dibuka', 'kat' => 'pengumuman', 'days' => 1,
                'excerpt' => 'Pendaftaran siswa baru tahun ajaran 2026/2027 telah dibuka. Simak jadwal dan alur pendaftarannya.'],
            ['title' => 'Siswa Kami Raih Juara 1 Olimpiade Sains Nasional', 'slug' => 'juara-osn-2026', 'kat' => 'prestasi', 'days' => 3,
                'excerpt' => 'Kabar membanggakan datang dari ajang OSN tingkat nasional tahun ini.'],
            ['title' => 'Peringatan Hari Kemerdekaan ke-81', 'slug' => 'hut-ri-81', 'kat' => 'kegiatan', 'days' => 6,
                'excerpt' => 'Seluruh warga sekolah mengikuti upacara dan lomba dalam rangka HUT RI.'],
            ['title' => 'Jadwal Ujian Tengah Semester Ganjil', 'slug' => 'jadwal-uts-ganjil', 'kat' => 'pengumuman', 'days' => 9,
                'excerpt' => 'Berikut jadwal lengkap pelaksanaan UTS untuk semua jenjang kelas.'],
            ['title' => 'Kunjungan Industri ke Perusahaan Teknologi', 'slug' => 'kunjungan-industri', 'kat' => 'kegiatan', 'days' => 14,
                'excerpt' => 'Siswa kelas XII melakukan kunjungan industri untuk menambah wawasan dunia kerja.'],
        ];

        foreach ($posts as $p) {
            Post::updateOrCreate(['slug' => $p['slug']], [
                'title' => $p['title'],
                'excerpt' => $p['excerpt'],
                'content' => '<p>'.$p['excerpt'].'</p>'
                    .'<p>Informasi lebih lanjut akan disampaikan melalui kanal resmi sekolah. Pastikan Anda memantau pengumuman terbaru secara berkala.</p>'
                    .'<h2>Rincian</h2><ul><li>Poin pertama informasi.</li><li>Poin kedua informasi.</li></ul>',
                'post_category_id' => $categories->firstWhere('slug', $p['kat'])->id,
                'published_at' => Carbon::now()->subDays($p['days']),
                'status' => 'published',
            ]);
        }

        // Satu post draft — HARUS tidak tampil di frontend.
        Post::updateOrCreate(['slug' => 'draft-belum-terbit'], [
            'title' => 'Draft Berita (Belum Terbit)',
            'excerpt' => 'Ini draft dan tidak boleh muncul di frontend.',
            'content' => '<p>Rahasia.</p>',
            'status' => 'draft',
        ]);

        // --- Pages (published) ---
        $pages = [
            ['title' => 'Tentang Kami', 'slug' => 'tentang-kami',
                'content' => '<p>Selamat datang di website resmi sekolah kami. Kami berkomitmen memberikan pendidikan bermutu bagi setiap peserta didik.</p><h2>Sejarah Singkat</h2><p>Sekolah ini berdiri sejak lama dan terus berkembang mengikuti perkembangan zaman.</p>'],
            ['title' => 'Visi &amp; Misi', 'slug' => 'visi-misi',
                'content' => '<h2>Visi</h2><p>Menjadi sekolah unggul yang berkarakter dan berprestasi.</p><h2>Misi</h2><ul><li>Menyelenggarakan pembelajaran yang bermutu.</li><li>Membentuk karakter mulia peserta didik.</li></ul>'],
            ['title' => 'Kontak', 'slug' => 'kontak',
                'content' => '<p>Hubungi kami melalui informasi kontak yang tersedia di bagian bawah halaman.</p>'],
        ];

        foreach ($pages as $pg) {
            Page::updateOrCreate(['slug' => $pg['slug']], [
                'title' => $pg['title'],
                'content' => $pg['content'],
                'status' => 'published',
            ]);
        }

        // Satu page draft — HARUS 404 di frontend.
        Page::updateOrCreate(['slug' => 'halaman-draft'], [
            'title' => 'Halaman Draft',
            'content' => '<p>Rahasia.</p>',
            'status' => 'draft',
        ]);

        // --- Menu navigasi (termasuk submenu untuk uji parent/child) ---
        // Reset agar urutan & hierarki konsisten setiap kali di-seed ulang.
        Menu::query()->delete();

        Menu::create(['label' => 'Beranda', 'type' => 'url', 'target' => '/', 'sort_order' => 1]);

        $profil = Menu::create(['label' => 'Profil', 'type' => 'url', 'target' => '#', 'sort_order' => 2]);
        Menu::create(['label' => 'Tentang Kami', 'type' => 'page', 'target' => 'tentang-kami', 'sort_order' => 1, 'parent_id' => $profil->id]);
        Menu::create(['label' => 'Visi & Misi', 'type' => 'page', 'target' => 'visi-misi', 'sort_order' => 2, 'parent_id' => $profil->id]);

        Menu::create(['label' => 'Berita', 'type' => 'url', 'target' => '/berita', 'sort_order' => 3]);
        Menu::create(['label' => 'Kontak', 'type' => 'page', 'target' => 'kontak', 'sort_order' => 4]);
    }
}
