# Desain: PTSP Online (Pelayanan Terpadu Satu Pintu)

Tanggal: 2026-09-03
Status: disetujui untuk dilanjutkan ke rencana implementasi

## 1. Ringkasan

PTSP Online adalah aplikasi layanan publik sekolah/madrasah: masyarakat memilih
layanan dari katalog, mengisi formulir pengajuan beserta berkas, lalu menerima
kode resi untuk melacak status permohonannya. Seluruh isi katalog dan seluruh
field formulir dikelola operator lewat panel admin (CMS) tanpa menyentuh kode.

Fase 1 mencakup: katalog layanan, halaman rincian syarat, formulir pengajuan
dinamis, kode resi, halaman pelacakan publik, dan pengelolaan status permohonan
di panel admin.

Layanan awal yang di-seed:

1. SPP Pengambilan Ijazah
2. SPP Legalisasi Ijazah Online
3. SPP Surat Pengganti Ijazah Hilang
4. SPP Surat Pengganti Ijazah Rusak
5. SPP Kesalahan Penulisan Ijazah

## 2. Keputusan arsitektur

### 2.1 Fork dari CMS-web

PTSP Online dibangun sebagai **instance mandiri hasil fork CMS-web**: satu
codebase sendiri di folder proyek terpisah, dengan `.env`, database, dan git
repo sendiri.

Alasan: CMS-web sudah menyediakan seluruh fondasi yang dibutuhkan (Laravel 13,
Filament v5, layout publik Blade, sistem menu, `GeneralSettings`, medialibrary,
dan yang terpenting **form builder dinamis** lengkap dengan validasi dinamis dan
penyimpanan jawaban). Membangun dari nol berarti menulis ulang semua itu.
Menjadikannya modul di dalam CMS-web ditolak karena setiap klien CMS-web yang
tidak butuh PTSP akan ikut membawa kodenya.

Konsekuensi yang diterima: perbaikan bug di CMS-web tidak otomatis mengalir ke
PTSP Online, dan sebaliknya.

### 2.2 Nama folder & domain

Folder proyek harus bernama `ptsp-online` (tanpa spasi), sehingga Herd
memetakannya ke `http://ptsp-online.test`. Nama berspasi menghasilkan domain
yang tidak valid.

Catatan verifikasi lokal: URL `.test` tidak dapat dijangkau dari shell. Verifikasi
otomatis dilakukan lewat `php artisan serve` pada alamat loopback.

### 2.3 Modul CMS-web yang tidak dipakai

Modul Slide, Stat, LeaderQuote, Pendamping, Zona Integritas, Galeri, dan Post
**tidak dihapus** pada fase 1; cukup disembunyikan dari navigasi panel admin.

Alasan: menghapusnya berarti menyentuh migrasi, seeder, model, resource Filament,
dan layout beranda sekaligus — risiko kerusakan tinggi dengan nilai rendah.
Pembersihan dijadwalkan sebagai pekerjaan terpisah setelah PTSP berjalan.

### 2.4 Nama tabel dipertahankan, label diganti

Tabel `forms`, `form_fields`, dan `form_submissions` **tidak di-rename**, supaya
seluruh kode, relasi, dan test yang sudah ada tetap berfungsi. Yang diganti hanya
label yang dilihat pengguna:

| Tabel | Label di panel admin |
|---|---|
| `forms` | Layanan |
| `form_submissions` | Permohonan |

Operator tidak akan pernah melihat kata "form" di panel.

## 3. Model data

### 3.1 Kolom baru pada `forms`

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---|---|---|
| `is_service` | boolean | tidak | `true` | Pembeda layanan PTSP dari form biasa |
| `organizer` | string(100) | tidak | `PTSP` | Bagian kiri badge kartu |
| `work_unit_id` | FK → `work_units` | ya | `null` | Bagian kanan badge; dasar filter katalog |
| `duration_text` | string(50) | ya | `null` | Teks bebas: `30 Menit`, `1-2 Hari` |
| `fee_text` | string(50) | tidak | `Gratis` | Biaya layanan |
| `sort_order` | unsignedInteger | tidak | `0` | Menentukan penomoran `#1..#N` di katalog |
| `requirements` | longText | ya | `null` | Isi halaman Rincian (HTML dari rich editor) |
| `legal_basis` | longText | ya | `null` | Dasar hukum (opsional) |

`duration_text` sengaja berupa teks bebas, bukan angka + satuan, karena satuannya
bercampur antar layanan (menit, jam, hari) dan sebagian berupa rentang.

`work_unit_id` bersifat nullable dan memakai `nullOnDelete`, sehingga menghapus
satu satuan kerja tidak ikut menghapus layanannya.

### 3.2 Tabel baru `work_units`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | id | |
| `name` | string(100) | mis. `Tata Usaha (TU)`, `Kesiswaan`, `P2M2` |
| `slug` | string, unique | dipakai di query string filter |
| `sort_order` | unsignedInteger, default 0 | urutan chip filter |
| `timestamps` | | |

### 3.3 Kolom baru pada `form_fields`

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---|---|---|
| `help_text` | string(255) | ya | `null` | Keterangan kecil di bawah field |

Kolom `type` menerima dua nilai tambahan: `date` dan `number`, melengkapi
`text`, `textarea`, `select`, `checkbox`, dan `file` yang sudah ada.

### 3.4 Kolom baru pada `form_submissions`

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---|---|---|
| `receipt_code` | string(20), unique, index | ya | `null` | mis. `PTSP-2609-A7K3QX` |
| `applicant_name` | string(150) | ya | `null` | Nama pemohon |
| `applicant_whatsapp` | string(20), index | ya | `null` | Disimpan ternormalisasi (`628...`) |
| `applicant_email` | string(150) | ya | `null` | Email pemohon |
| `status` | string(20), index | tidak | `diajukan` | `diajukan`, `diproses`, `selesai`, `ditolak` |
| `admin_note` | text | ya | `null` | Catatan terakhir dari operator |

Kolom identitas dan `receipt_code` dibuat nullable **di tingkat basis data**, tetapi
tetap **wajib di tingkat aplikasi** untuk setiap pengajuan layanan (lihat 5.1).
Alasannya: tabel `form_submissions` juga menampung jawaban form biasa
(`is_service = false`) yang tidak memiliki blok identitas pemohon. Menjadikan
kolom-kolom ini `NOT NULL` akan membuat form non-layanan gagal disimpan.

Kolom `data` (JSON) tetap menyimpan jawaban field dinamis, dengan `id` field
sebagai kunci — pola yang sudah dipakai CMS-web.

Identitas pemohon disimpan di kolom tersendiri, **bukan** di dalam `data`, karena
harus bisa dicari, difilter, dan dipakai sebagai verifikasi pelacakan.

Status disimpan sebagai `string` dengan konstanta di model, bukan `enum` MySQL,
supaya penambahan status di masa depan tidak memerlukan perubahan skema.

### 3.5 Tabel baru `submission_status_logs`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | id | |
| `form_submission_id` | FK, cascadeOnDelete | |
| `status` | string(20) | status setelah perubahan |
| `note` | text, nullable | catatan operator saat itu |
| `user_id` | FK → `users`, nullable, nullOnDelete | operator yang mengubah |
| `created_at` | timestamp | dipakai sebagai waktu timeline |

Baris pertama dibuat otomatis saat permohonan masuk (status `diajukan`), sehingga
timeline selalu punya titik awal.

## 4. Alur publik

### 4.1 Rute

```
GET  /layanan                  katalog kartu layanan
GET  /layanan/{slug}           halaman rincian syarat
GET  /layanan/{slug}/ajukan    formulir pengajuan
POST /layanan/{slug}/ajukan    proses submit
GET  /permohonan/selesai       halaman sukses (kode resi)
GET  /lacak                    formulir pelacakan
POST /lacak                    hasil pelacakan
```

Rute catch-all halaman statis (`/{page:slug}`) saat ini hanya mengecualikan
`admin`, `up`, dan `form`. Regex-nya **wajib** ditambah `layanan`, `lacak`, dan
`permohonan`, jika tidak seluruh rute di atas akan tertelan olehnya.

Hanya layanan dengan `status = published` dan `is_service = true` yang tampil di
katalog dan dapat diajukan; selain itu 404.

### 4.2 Katalog

Menampilkan seluruh layanan terbit, diurutkan menurut `sort_order` menaik dengan
`id` menaik sebagai pemecah seri bila `sort_order` sama.

Penomoran `#1..#N` pada kartu bersifat **global dan tetap**: nomor dihitung dari
posisi layanan pada urutan penuh, bukan dari posisinya pada hasil yang sedang
tersaring. Menyaring ke satuan kerja tertentu tidak mengubah nomor sebuah
layanan, sehingga nomor dapat dipakai sebagai rujukan lisan di loket.

Filter satuan kerja berupa deretan chip yang dapat diklik (`Seluruh Satuan Kerja`
diikuti tiap satuan kerja), bekerja lewat query string `?unit={slug}` sehingga
tetap berfungsi tanpa JavaScript dan dapat di-bookmark. Chip dipilih menggantikan
dropdown karena di ponsel dropdown memerlukan dua ketukan dan menyembunyikan
pilihan yang tersedia.

Tersedia pula kotak pencarian judul (`?q=`), dicocokkan dengan `LIKE` pada kolom
`title`.

### 4.3 Formulir pengajuan

Formulir terdiri dari dua blok.

**Blok 1 — Identitas & Kontak Pemohon (bawaan sistem, tidak dikelola admin).**
Berisi Nama Lengkap Pemohon, Layanan yang Dituju (terkunci, terisi otomatis),
Nomor WhatsApp, dan Alamat Email. Blok ini selalu ada di setiap layanan.

**Blok 2 — Kelengkapan Berkas & Data Isian (dikelola admin).**
Berisi field dinamis dari `form_fields`, bernomor otomatis mulai dari 1 mengikuti
`sort_order`. Di sinilah operator menambah atau mengubah berkas yang dibutuhkan.

### 4.4 Halaman sukses

Setelah submit berhasil, kode resi dikirim lewat session sekali pakai dan
pengguna diarahkan ke `/permohonan/selesai`. Bila halaman itu dibuka langsung
atau di-refresh tanpa session, pengguna diarahkan ke `/lacak`.

URL yang dapat ditebak sengaja dihindari agar kode resi orang lain tidak dapat
ditemukan dengan menaikkan angka pada URL.

### 4.5 Pelacakan

Pemohon memasukkan kode resi **dan** 4 digit terakhir nomor WhatsApp yang
didaftarkan. Bila cocok, ditampilkan: nama layanan, tanggal pengajuan, badge
status, catatan operator terakhir, dan timeline dari `submission_status_logs`.

Isian formulir dan berkas yang diunggah **tidak** ditampilkan di halaman ini.
Kombinasi resi + 4 digit adalah verifikasi ringan yang tidak cukup kuat untuk
membuka data pribadi lengkap.

Bila tidak cocok, pesan yang ditampilkan bersifat umum ("Kode resi atau nomor
tidak cocok") tanpa memberi tahu bagian mana yang salah.

## 5. Validasi & keamanan

### 5.1 Aturan validasi

| Field | Aturan |
|---|---|
| Nama pemohon | wajib, string, maks 150 |
| Nomor WhatsApp | wajib, hanya digit setelah normalisasi, 10-15 digit |
| Email | wajib, format email, maks 150 |
| `text` | wajib/opsional, string, maks 5000 |
| `textarea` | wajib/opsional, string, maks 5000 |
| `select` | wajib/opsional, harus salah satu dari `options` |
| `checkbox` | wajib/opsional, array; tiap nilai harus ada di `options` |
| `date` | wajib/opsional, format tanggal |
| `number` | wajib/opsional, numerik |
| `file` | wajib/opsional, mimes `jpg,jpeg,png,pdf`, maks 5120 KB |

Nomor WhatsApp dinormalkan sebelum disimpan: spasi, tanda hubung, dan tanda
kurung dibuang; awalan `0` diganti `62`; awalan `+` dibuang. Tujuannya agar
pencocokan 4 digit terakhir saat pelacakan konsisten.

Aturan `is_unique` per field yang sudah ada dipertahankan apa adanya.

### 5.2 Penyimpanan berkas

Berkas unggahan disimpan di disk `local` (di luar `public/`), **bukan** disk
`public` seperti pada CMS-web saat ini. Berkas PTSP berisi ijazah, KTP, dan surat
kehilangan; pada disk publik siapa pun yang mengetahui atau menebak nama file
dapat mengunduhnya tanpa autentikasi.

Unduhan hanya tersedia lewat rute ber-autentikasi di panel admin, yang membaca
file melalui `Storage::download()`.

### 5.3 Anti-penyalahgunaan

- **Honeypot**: satu field tersembunyi pada formulir pengajuan; bila terisi,
  permintaan ditolak diam-diam. CAPTCHA sengaja tidak dipakai karena menyulitkan
  pemohon yang tidak terbiasa dengan teknologi.
- **Rate limit pengajuan**: 5 pengajuan per jam per alamat IP.
- **Rate limit pelacakan**: 10 percobaan per menit per alamat IP, untuk mencegah
  penebakan 4 digit terakhir nomor WhatsApp secara brute force.

### 5.4 Kode resi

Format: `PTSP-{YY}{MM}-{6 karakter}`, contoh `PTSP-2609-A7K3QX`.

Alfabet karakter acak membuang `0`, `O`, `1`, dan `I` karena mudah tertukar saat
kode dibaca dari kertas atau disebutkan lewat telepon.

Keunikan dijamin oleh indeks `unique` pada kolom; bila terjadi bentrok, kode
dibangkitkan ulang. Kode dibuat di dalam transaksi bersamaan dengan penyimpanan
permohonan.

## 6. Panel admin

Grup navigasi baru bernama **PTSP** berisi tiga menu.

### 6.1 Layanan

Tabel: nomor urut (dapat digeser untuk mengubah urutan, langsung memengaruhi
`#1..#N` di katalog), judul, badge satuan kerja, durasi, biaya, status, dan
jumlah permohonan masuk.

Halaman edit dibagi menjadi empat tab agar tidak menjadi satu formulir raksasa:

| Tab | Isi |
|---|---|
| Informasi Layanan | judul, slug (otomatis dari judul), penyelenggara, satuan kerja, durasi, biaya, ringkasan (kolom `description`) |
| Rincian & Syarat | rich editor `requirements`, `legal_basis` |
| Field Formulir | repeater dengan urutan geser: label, tipe, opsi, wajib, tidak boleh duplikat, keterangan |
| Pengaturan | status draft/terbit, pesan sukses |

Tab **Field Formulir** adalah tempat operator menambah atau mengubah berkas yang
dibutuhkan. Menambahkan satu baris bertipe *Upload Berkas* berlabel "Fotokopi KK"
lalu menyimpan akan langsung mengubah formulir publik tanpa perubahan kode.

### 6.2 Permohonan

Tabel: kode resi, nama pemohon, layanan, tanggal, badge status. Filter tersedia
untuk status, layanan, dan rentang tanggal.

Aksi baris **Ubah Status**: memilih status baru dan menulis catatan; menyimpan
akan memperbarui `status` dan `admin_note` sekaligus menulis satu baris ke
`submission_status_logs`.

Halaman detail menampilkan identitas pemohon, seluruh isian, dan daftar berkas
dengan tombol unduh, serta riwayat status.

Export CSV memakai ulang pola yang sudah ada di CMS-web (delimiter `;` dan BOM
UTF-8) agar terbuka rapi per kolom di Excel.

Membuat permohonan secara manual dari panel tidak disediakan; permohonan hanya
lahir dari pengajuan publik.

### 6.3 Satuan Kerja

Master sederhana: nama, slug (otomatis), dan urutan.

### 6.4 Data awal (seeder)

Seeder idempotent (aman dijalankan ulang, dicocokkan berdasarkan `slug`) mengisi:

- Tiga satuan kerja: `Tata Usaha (TU)`, `Kesiswaan`, `P2M2`.
- Lima layanan pada bab 1, seluruhnya `organizer = PTSP`, satuan kerja
  `Tata Usaha (TU)`, `fee_text = Gratis`, status `published`, dengan `sort_order`
  1 sampai 5 sesuai urutan penyebutan.
- Field formulir tiap layanan sesuai gambar acuan:

| Layanan | Field (urut) |
|---|---|
| SPP Pengambilan Ijazah | Nama Murid (teks, wajib) |
| SPP Legalisasi Ijazah Online | Tanda terima pengambilan ijazah/STTB (teks, wajib); Nama (teks, wajib); Upload Ijazah (berkas, opsional) |
| SPP Surat Pengganti Ijazah Hilang | Nama (teks, wajib); Upload Surat Kehilangan (berkas, wajib); Fotokopi Ijazah (berkas, wajib); Foto 3x4 (berkas, wajib) |
| SPP Surat Pengganti Ijazah Rusak | Nama (teks, wajib); Ijazah Asli (berkas, wajib); Ijazah Fotokopi (berkas, wajib) |
| SPP Kesalahan Penulisan Ijazah | Nama (teks, wajib); Ijazah Asli (berkas, wajib); Ijazah Fotokopi (berkas, wajib) |

Nilai `duration_text` mengikuti gambar acuan: berturut-turut `30 Menit`,
`1-2 Hari`, `1 Jam`, `58 Menit`, `1 Jam`.

Isi `requirements` diisi teks awal ringkas yang jelas ditujukan untuk diganti
operator; seeder tidak menimpa data yang sudah diubah operator.

## 7. Desain visual

### 7.1 Token

Seluruh aksen memakai `var(--color-primary)` yang sudah disuntikkan layout dari
`GeneralSettings`, dengan nilai default instance PTSP diset hijau `#1B6B4A`.
Warna tidak di-hardcode agar sekolah dengan identitas warna berbeda dapat
menggantinya dari menu Pengaturan. Font tetap Instrument Sans.

Turunan yang dipakai berulang: `primary` (tombol utama, ikon), `primary/10`
(latar badge dan area unggah), `gray-50` (strip meta kartu), `gray-200` (garis
tepi), `red-500` (penanda wajib).

### 7.2 Kartu layanan

Empat lapis dari atas ke bawah:

1. **Baris label** — pil kategori `{organizer} / {satuan kerja}` berikon tag di
   kiri, nomor urut `#N` di kanan.
2. **Judul** — semibold, dijepit maksimal 2 baris agar tinggi kartu seragam.
3. **Strip meta** — kotak abu muda: durasi di kiri, biaya di kanan. Dua informasi
   ini paling sering ditanyakan di loket, karena itu ditonjolkan.
4. **Dua tombol sejajar** — `Rincian` bergaya garis tepi dan `Ajukan` bergaya
   solid. Perbedaan bobot disengaja: yang solid adalah aksi yang diharapkan.

Grid: 3 kolom di desktop, 2 di tablet, 1 di ponsel. Hover menaikkan kartu tipis
dan menebalkan bayangan.

### 7.3 Halaman rincian

Dua kolom di desktop: kiri berisi `requirements`, kanan berupa kartu ringkas yang
menempel saat digulir berisi durasi, biaya, satuan kerja, dasar hukum, dan tombol
`Ajukan Permohonan`. Di ponsel kartu ringkas pindah ke atas isi agar tombolnya
terlihat tanpa menggulir jauh.

### 7.4 Halaman formulir

Blok Identitas berupa kartu putih dengan header berikon, garis bawah, dan
penanda `*Wajib diisi dengan benar` di kanan. Isinya grid 2 kolom yang menjadi
1 kolom di ponsel. Tiap input memiliki ikon di dalam kotaknya.

Blok Kelengkapan Berkas menampilkan field bernomor; field teks berikon pensil,
field berkas berikon dokumen. Field opsional diberi penanda `(Opsional)` secara
eksplisit, bukan sekadar tanpa bintang merah, karena ketiadaan tanda bersifat
ambigu.

Area unggah: kotak bergaris putus-putus dengan ikon awan, teks `Klik atau Tarik
File ke Area Ini`, dan baris format `PDF, JPG, PNG (Maksimal 5MB)`. Setelah file
dipilih, kotak berubah menampilkan nama file, ukuran, dan tombol ganti/hapus —
tanpa umpan balik ini pemohon tidak yakin berkasnya sudah masuk. Drag-drop
memerlukan JavaScript; tanpa JavaScript komponen jatuh balik ke input file biasa,
memakai penanda kelas `.js` yang sudah ada di layout.

Tombol kirim berbentuk pil besar `Kirim Permohonan Sekarang` berikon pesawat
kertas, rata kanan. Saat diklik tombol dikunci dan berganti teks `Mengirim...`
untuk mencegah pengajuan ganda.

Placeholder ditulis sebagai kalimat wajar (`Masukkan nama murid...`) tanpa
menyertakan nomor urut field.

### 7.5 Halaman sukses & pelacakan

Halaman sukses: ikon centang, kode resi tampil besar dengan pemisah antar blok
agar mudah dibaca ulang, tombol Salin dan Cetak Bukti, serta imbauan menyimpan
kode.

Halaman pelacakan: satu kartu pencarian; hasilnya badge status dan timeline
vertikal bertitik.

### 7.6 Aksesibilitas

- Rasio kontras teks terhadap latar minimal 4.5:1.
- Setiap input memiliki `<label>` sungguhan, bukan hanya placeholder.
- Cincin fokus keyboard terlihat jelas pada seluruh tombol, chip, dan area unggah.
- Area unggah tetap berisi `<input type="file">` asli sehingga dapat dioperasikan
  dengan keyboard dan pembaca layar.
- Badge status selalu menampilkan warna **dan** teks, tidak pernah warna saja.
- Pesan error validasi ditampilkan di bawah field terkait, tidak hanya menumpuk
  di bagian atas halaman.

## 8. Pengujian

Test feature yang ditulis bersamaan dengan implementasi:

1. Katalog hanya memuat layanan `published` + `is_service`; layanan draft tidak muncul.
2. Filter satuan kerja lewat query string menyaring hasil dengan benar.
3. Membuka `/layanan/{slug}/ajukan` untuk layanan draft menghasilkan 404.
4. Pengajuan valid membuat satu permohonan berstatus `diajukan`, berkode resi unik, dengan satu baris log status.
5. Pengajuan tanpa field wajib ditolak beserta pesan error pada field terkait.
6. Pengajuan dengan honeypot terisi tidak membuat permohonan.
7. Berkas unggahan tidak dapat diakses tanpa autentikasi.
8. Pelacakan dengan resi + 4 digit yang benar menampilkan status; kombinasi salah ditolak dengan pesan umum.
9. Mengubah status dari panel menambah baris ke `submission_status_logs`.

Test unit:

10. Generator kode resi selalu menghasilkan format yang benar dan tidak pernah memuat karakter `0`, `O`, `1`, `I`.
11. Normalisasi nomor WhatsApp mengubah `0812-3456-7890`, `+62 812 3456 7890`, dan `081234567890` menjadi bentuk yang sama.

## 9. Di luar cakupan fase 1

Hal-hal berikut sengaja tidak dikerjakan sekarang dan menunggu fase berikutnya:

- Pengiriman email otomatis berisi kode resi dan pemberitahuan perubahan status.
  Konsekuensinya, teks bantuan pada field email **tidak boleh** menjanjikan
  pengiriman email; kode resi ditampilkan di layar dan dapat dicetak.
- Notifikasi WhatsApp.
- Status "Perlu Perbaikan Berkas" beserta mekanisme unggah ulang oleh pemohon.
- Status yang dapat ditambah sendiri oleh operator.
- Akun/login untuk pemohon.
- Template surat balasan otomatis dan dashboard statistik layanan.
- Pembersihan modul CMS-web yang tidak dipakai.

## 10. Risiko

| Risiko | Penanganan |
|---|---|
| Verifikasi lacak hanya 4 digit WA | Rate limit 10/menit per IP; data pribadi lengkap tidak ditampilkan di halaman lacak |
| Berkas identitas tersimpan di server | Disk privat di luar `public/`, unduhan hanya lewat rute ber-autentikasi |
| Operator salah mengatur field lalu formulir rusak | Perubahan field tidak mengubah permohonan lama karena `data` memakai `id` field sebagai kunci |
| Katalog membesar hingga puluhan layanan | Filter chip + pencarian judul; penomoran mengikuti urutan tampil |
| Fork menyimpang jauh dari CMS-web | Diterima secara sadar; nama tabel dipertahankan agar penggabungan perbaikan tetap mungkin |
