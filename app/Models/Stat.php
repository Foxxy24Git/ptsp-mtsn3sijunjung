<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stat extends Model
{
    protected $fillable = [
        'icon',
        'label',
        'value',
        'suffix',
        'color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Pilihan ikon terkurasi & relevan (Heroicon outline), dikelompokkan per tema
     * agar admin mudah memilih — bukan daftar penuh yang penuh panah/arah.
     * Key = nama komponen (mis. heroicon-o-users), value = label yang mudah dibaca.
     */
    public static function iconOptions(): array
    {
        return [
            // Akademik / pendidikan
            'heroicon-o-academic-cap' => 'Topi Wisuda / Akademik',
            'heroicon-o-book-open' => 'Buku Terbuka',
            'heroicon-o-newspaper' => 'Koran / Berita',
            'heroicon-o-pencil-square' => 'Pensil / Menulis',
            'heroicon-o-document-text' => 'Dokumen',
            'heroicon-o-clipboard-document-check' => 'Akreditasi / Terverifikasi',
            'heroicon-o-clipboard-document-list' => 'Daftar / Data',
            'heroicon-o-chart-bar' => 'Grafik Batang',
            'heroicon-o-presentation-chart-bar' => 'Presentasi Grafik',
            'heroicon-o-presentation-chart-line' => 'Presentasi Tren',
            'heroicon-o-chart-pie' => 'Diagram Lingkaran',
            'heroicon-o-calculator' => 'Kalkulator',
            'heroicon-o-language' => 'Bahasa',
            'heroicon-o-beaker' => 'Laboratorium',
            'heroicon-o-light-bulb' => 'Ide / Inovasi',
            'heroicon-o-puzzle-piece' => 'Puzzle',
            'heroicon-o-trophy' => 'Trofi / Prestasi',
            'heroicon-o-star' => 'Bintang',
            'heroicon-o-sparkles' => 'Unggulan',

            // Orang / komunitas
            'heroicon-o-users' => 'Banyak Orang',
            'heroicon-o-user-group' => 'Grup / Komunitas',
            'heroicon-o-user' => 'Satu Orang',
            'heroicon-o-user-circle' => 'Profil',
            'heroicon-o-user-plus' => 'Anggota Baru',
            'heroicon-o-hand-raised' => 'Angkat Tangan',
            'heroicon-o-hand-thumb-up' => 'Jempol / Suka',
            'heroicon-o-heart' => 'Hati / Kesehatan',
            'heroicon-o-face-smile' => 'Kepuasan',

            // Pemerintahan / perangkat desa / administrasi
            'heroicon-o-building-library' => 'Gedung Pemerintah',
            'heroicon-o-building-office' => 'Kantor',
            'heroicon-o-building-office-2' => 'Gedung Perkantoran',
            'heroicon-o-building-storefront' => 'Toko / UMKM',
            'heroicon-o-home' => 'Rumah',
            'heroicon-o-home-modern' => 'Rumah Modern',
            'heroicon-o-identification' => 'KTP / Identitas',
            'heroicon-o-finger-print' => 'Sidik Jari',
            'heroicon-o-shield-check' => 'Keamanan / Terjamin',
            'heroicon-o-scale' => 'Hukum / Keadilan',
            'heroicon-o-flag' => 'Bendera',
            'heroicon-o-map' => 'Peta',
            'heroicon-o-map-pin' => 'Lokasi',
            'heroicon-o-key' => 'Kunci / Akses',
            'heroicon-o-document-check' => 'Dokumen Sah',
            'heroicon-o-folder' => 'Folder / Arsip',
            'heroicon-o-archive-box' => 'Arsip',
            'heroicon-o-check-badge' => 'Terakreditasi',
            'heroicon-o-receipt-percent' => 'Kwitansi / Pajak',

            // Kesehatan / rumah sakit
            'heroicon-o-lifebuoy' => 'Bantuan / Darurat',
            'heroicon-o-plus-circle' => 'Medis / Tambah',

            // Bisnis / komersial
            'heroicon-o-briefcase' => 'Pekerjaan / Karier',
            'heroicon-o-shopping-bag' => 'Belanja',
            'heroicon-o-shopping-cart' => 'Keranjang',
            'heroicon-o-credit-card' => 'Kartu / Pembayaran',
            'heroicon-o-wallet' => 'Dompet',
            'heroicon-o-banknotes' => 'Uang',
            'heroicon-o-currency-dollar' => 'Dolar',
            'heroicon-o-currency-rupee' => 'Rupee',
            'heroicon-o-tag' => 'Label / Harga',
            'heroicon-o-ticket' => 'Tiket',
            'heroicon-o-gift' => 'Hadiah',
            'heroicon-o-truck' => 'Pengiriman',
            'heroicon-o-cube' => 'Produk / Paket',
            'heroicon-o-globe-alt' => 'Dunia / Global',
            'heroicon-o-globe-asia-australia' => 'Asia',
            'heroicon-o-rocket-launch' => 'Peluncuran / Cepat',
            'heroicon-o-megaphone' => 'Pengumuman',

            // Komunikasi / teknologi / web
            'heroicon-o-envelope' => 'Email / Surat',
            'heroicon-o-phone' => 'Telepon',
            'heroicon-o-chat-bubble-left-right' => 'Obrolan / Chat',
            'heroicon-o-bell' => 'Notifikasi',
            'heroicon-o-camera' => 'Kamera',
            'heroicon-o-photo' => 'Foto',
            'heroicon-o-video-camera' => 'Video',
            'heroicon-o-musical-note' => 'Musik',
            'heroicon-o-microphone' => 'Mikrofon',
            'heroicon-o-computer-desktop' => 'Komputer',
            'heroicon-o-device-phone-mobile' => 'Ponsel',
            'heroicon-o-cpu-chip' => 'Chip / Teknologi',
            'heroicon-o-server' => 'Server',
            'heroicon-o-cloud' => 'Cloud',
            'heroicon-o-wifi' => 'WiFi / Internet',
            'heroicon-o-code-bracket' => 'Kode / Program',
            'heroicon-o-command-line' => 'Terminal',
            'heroicon-o-qr-code' => 'QR Code',
            'heroicon-o-calendar-days' => 'Kalender',
            'heroicon-o-clock' => 'Jam / Waktu',
            'heroicon-o-cog-6-tooth' => 'Pengaturan',
            'heroicon-o-wrench-screwdriver' => 'Perbaikan / Teknik',
            'heroicon-o-fire' => 'Populer / Tren',
            'heroicon-o-sun' => 'Cuaca',
        ];
    }

    /**
     * Sama seperti iconOptions(), tapi label berupa HTML (ikon SVG + nama) untuk
     * dipakai pada Select ->allowHtml() di admin, agar ikon terlihat langsung.
     */
    public static function iconHtmlOptions(): array
    {
        static $options = null;

        if ($options !== null) {
            return $options;
        }

        $options = [];

        foreach (self::iconOptions() as $name => $label) {
            $icon = svg($name, 'w-5 h-5 shrink-0')->toHtml();
            $options[$name] = '<span style="display:inline-flex;align-items:center;gap:.6rem">'
                .$icon.'<span>'.e($label).'</span></span>';
        }

        return $options;
    }

    /**
     * Apakah value murni angka (untuk animasi count-up di frontend).
     */
    public function isNumericValue(): bool
    {
        return is_numeric($this->value);
    }

    /**
     * Warna teks yang kontras di atas warna isian (putih atau gelap),
     * berdasarkan luminansi --stat-color yang dipilih admin.
     */
    public function contrastColor(): string
    {
        $hex = ltrim((string) $this->color, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '#ffffff';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

        return $luminance > 0.6 ? '#111827' : '#ffffff';
    }
}
