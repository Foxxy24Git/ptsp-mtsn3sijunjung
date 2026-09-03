# Desain: Portal Petugas PTSP Online

Tanggal: 2026-09-03
Status: disetujui untuk dilanjutkan ke rencana implementasi

## 1. Ringkasan

Menambahkan peran **Petugas** (staf sekolah) yang dapat login lewat tautan di
pojok kanan atas situs publik, memproses permohonan yang masuk, mengubah
status permohonan sampai selesai, atau menolaknya — semua lewat sebuah panel
kerja (dashboard) tersendiri yang terpisah dari panel Administrator.

Bagian pemohon (katalog layanan, formulir pengajuan, kode resi, dan halaman
pelacakan publik `/lacak`) **sudah lengkap** dari fase sebelumnya dan **tidak
diubah** oleh fitur ini. Begitu pula model status (`diajukan`, `diproses`,
`selesai`, `ditolak`), tabel riwayat `submission_status_logs`, dan aksi
`App\Actions\UpdateSubmissionStatus` — seluruhnya sudah ada dan dipakai ulang
apa adanya. Yang benar-benar baru hanyalah **siapa yang boleh mengelola
permohonan dan lewat pintu mana**: sampai saat ini hanya satu role
Administrator lewat `/admin`; fitur ini menambah role Petugas dengan pintu
masuknya sendiri.

## 2. Keputusan arsitektur

### 2.1 Peran sebagai kolom pada tabel `users`, bukan tabel terpisah

Ditambahkan satu kolom `role` pada tabel `users` yang sudah ada, disimpan
sebagai `string` dengan konstanta di model — pola yang sama persis dengan
kolom `status` pada `form_submissions` (lihat spec PTSP Online §3.4). Alasan
konsistensi yang sama: menambah role baru di masa depan tidak memerlukan
perubahan skema.

Tidak dibuat tabel `petugas` terpisah karena akun petugas hanyalah akun login
biasa (nama, email, password) — tidak ada atribut lain yang membedakannya dari
`users` yang sudah ada.

### 2.2 Panel Filament kedua, bukan halaman Blade kustom

Dibuat panel Filament baru (`petugas`) di `/petugas`, terpisah dari panel
`admin` yang sudah ada, alih-alih membangun login & dashboard kustom dengan
Blade.

Alasan: panel admin sekarang sudah memiliki seluruh logika yang dibutuhkan
petugas — tabel Permohonan dengan filter status, aksi **Ubah Status** yang
sekaligus menulis riwayat (mencakup kebutuhan "proses" maupun "tolak"), dan
modal Detail dengan unduhan berkas ber-autentikasi. Panel kedua berarti
seluruh itu dipakai ulang langsung tanpa menulis ulang login, middleware
sesi, form validasi, atau proteksi unduhan berkas. Membangun versi Blade
kustom berarti menduplikasi semua itu untuk hasil yang secara fungsional
sama.

Konsekuensi yang diterima: tampilan panel petugas mengikuti gaya visual
Filament (bersih, mirip `/admin`), bukan gaya hijau situs publik.

### 2.3 Hierarki akses: Administrator mencakup Petugas

Administrator dapat mengakses **kedua** panel (`/admin` dan `/petugas`);
Petugas hanya dapat mengakses `/petugas`. Ini bukan dua peran yang setara,
melainkan satu tangga akses — Administrator adalah superset dari Petugas.

### 2.4 Reuse langsung komponen tabel/form admin, bukan duplikasi

Resource Permohonan di panel petugas memakai ulang **class konfigurasi tabel
dan form yang sama** dengan yang sudah dipakai `FormSubmissionResource` di
panel admin (kolom, filter, aksi Ubah Status, modal Detail — identik).
Kolom/aksi tersebut didefinisikan satu kali; kedua panel merujuk ke sana.

Konsekuensi yang diterima: mengubah tabel Permohonan di admin (mis. menambah
kolom) otomatis ikut mengubah tampilan di panel petugas. Ini disengaja (satu
sumber kebenaran), bukan kebocoran bug.

Perbedaan eksplisit dari admin: aksi toolbar **Export CSV** tidak disertakan
di panel petugas — ekspor seluruh data permohonan bersifat kebutuhan
pelaporan admin, bukan bagian dari "memproses permohonan" yang diminta.

## 3. Model data

### 3.1 Kolom baru pada `users`

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---|---|---|
| `role` | string(20) | tidak | `administrator` | `administrator` atau `petugas` |

Kolom dibuat dengan default `administrator` sehingga akun admin default yang
sudah ada otomatis mendapat nilai ini tanpa migrasi data terpisah.

### 3.2 Konstanta pada model `User`

Dua nilai role, dengan array label untuk ditampilkan di form (pola yang sama
dengan `FormSubmission::STATUSES`):

```
ROLE_ADMINISTRATOR = 'administrator'
ROLE_PETUGAS = 'petugas'
ROLES = ['administrator' => 'Administrator', 'petugas' => 'Petugas']
```

### 3.3 Tidak ada perubahan pada tabel permohonan

`form_submissions`, `submission_status_logs`, konstanta
`FormSubmission::STATUSES`, method `FormSubmission::recordStatus()`, dan
`App\Actions\UpdateSubmissionStatus` **tidak berubah sama sekali**. Seluruh
kebutuhan "lihat total", "sedang diproses", "ubah status", dan "tolak" sudah
terpenuhi oleh empat nilai status yang ada.

## 4. Auth & panel Filament

### 4.1 `User::canAccessPanel()` menjadi sadar-role

Logika akses berubah dari "semua user boleh masuk panel apa pun" menjadi:

- Panel `admin` → hanya `role = administrator`.
- Panel `petugas` → `role = administrator` **atau** `role = petugas`.

### 4.2 Panel `petugas` sebagai panel Filament kedua

Karakteristik panel baru:

- Id & path: `petugas` (`/petugas`), bukan panel default.
- Guard: memakai guard `web` yang sama dengan panel admin (satu tabel
  `users`, satu sesi login) — bukan guard terpisah. Pembeda akses murni lewat
  `canAccessPanel()` di §4.1, bukan lewat sistem otentikasi yang berbeda.
- Warna aksen berbeda dari panel admin (yang memakai Amber) — dipakai
  `Color::Sky` (biru) — supaya staf yang kebetulan punya akses ke keduanya
  bisa langsung mengenali sedang berada di panel mana.
- Resource & widget panel ini didaftarkan dari lokasi terpisah dari
  resource/widget admin, sehingga resource admin (Layanan, Halaman, Berita,
  Menu, Satuan Kerja, Pengaturan, dst.) **tidak ikut muncul** di navigasi
  petugas — bukan disembunyikan satu per satu, tapi memang tidak pernah
  didaftarkan ke panel ini.
- Login memakai halaman login bawaan Filament (`/petugas/login`) — tidak ada
  form login kustom yang perlu ditulis.

### 4.3 Tidak ada rute registrasi publik

Aplikasi ini tidak memiliki rute `/register` atau sejenisnya — satu-satunya
jalan masuk adalah halaman login tiap panel. Tidak diperlukan pengamanan
tambahan untuk mencegah pendaftaran mandiri karena jalur itu memang tidak
ada.

## 5. Panel Petugas — isi

### 5.1 Dashboard: widget statistik

Layar pertama setelah login menampilkan lima angka: **Total Permohonan**,
**Diajukan** (baru masuk), **Diproses**, **Selesai**, dan **Ditolak** — murni
hitungan `FormSubmission` per status, dibaca langsung dari data yang sudah
ada (tidak ada tabel/kolom agregat baru).

### 5.2 Menu Permohonan

Satu resource yang menampilkan seluruh permohonan (tanpa dikelompokkan per
petugas — antrian bersama, lihat §9), dengan:

- Kolom, filter (status, layanan), dan urutan yang sama dengan tabel
  Permohonan di admin.
- Aksi **Ubah Status**: memilih status baru (termasuk `ditolak`) dan menulis
  catatan; secara internal memanggil `UpdateSubmissionStatus` yang sama
  dipakai admin, sehingga otomatis tercatat di riwayat yang juga tampil di
  halaman lacak publik.
- Aksi **Detail**: identitas pemohon, seluruh isian, dan unduhan berkas —
  sama dengan admin. Berkas bisa diunduh petugas karena proteksi unduhan
  (`SubmissionFileController`) memeriksa "apakah user sedang login" secara
  generik pada guard `web` yang sama — tidak ada perubahan kode yang
  diperlukan di sana.
- Tidak ada aksi "Buat Permohonan" (sama seperti admin — permohonan hanya
  lahir dari pengajuan publik) dan tidak ada aksi "Export CSV" (§2.4).

Navigasi panel ini hanya berisi Dashboard + Permohonan — tanpa grup navigasi
karena cuma satu resource kerja.

## 6. Kelola akun petugas (di panel admin)

Ditambahkan satu resource baru **di panel admin** (bukan panel petugas),
sehingga hanya Administrator yang bisa mengelola akun staf:

- Menampilkan hanya user dengan `role = petugas` — akun Administrator tidak
  pernah muncul atau bisa diubah lewat resource ini.
- Form: nama, email (wajib unik), password (wajib saat membuat akun baru;
  saat mengedit, dikosongkan berarti password tidak berubah).
- Role akun yang dibuat lewat resource ini selalu `petugas` — tidak ada
  pilihan lain di form (mencegah admin tidak sengaja membuat akun
  administrator baru lewat pintu yang salah).
- Aksi: tambah, ubah, hapus. Menghapus akun petugas berarti mencabut akses
  login staf tersebut; tidak mempengaruhi permohonan yang pernah mereka
  proses (kolom `user_id` di `submission_status_logs` sudah `nullOnDelete`).
- Reset password petugas dilakukan manual oleh Administrator lewat form edit
  (isi ulang kolom password) — tidak ada alur "lupa password" self-service
  untuk petugas (lihat §9).
- Ditempatkan di grup navigasi "PTSP" yang sudah ada di admin, supaya
  berkumpul dengan Layanan, Permohonan, dan Satuan Kerja.

## 7. Entry point publik

### 7.1 Tautan di header

Satu tautan di pojok kanan atas layout publik, sejajar dengan navigasi
desktop dan tetap terjangkau di tampilan mobile:

- Bila user yang sedang login berrole `petugas` → tautan berbunyi
  **"Dashboard Petugas — {nama}"**, menuju `/petugas`.
- Selain itu (tamu, atau Administrator yang kebetulan sedang browsing situs
  publik sambil login) → tautan berbunyi **"Masuk Petugas"**, juga menuju
  `/petugas`. Filament sendiri yang mengarahkan ke halaman login bila belum
  diautentikasi, atau ke dashboard bila ternyata sudah (mis. Administrator
  yang mengklik tautan ini akan langsung masuk ke `/petugas` karena
  hierarki akses §2.3, bukan bug).
- Logout dilakukan lewat menu akun bawaan Filament di dalam panel
  `/petugas` — tidak ada tombol logout kustom di situs publik.

### 7.2 Gaya visual

Mengikuti token warna header yang sudah ada (`--header-fg`, `--header-hover`,
dst., lihat `resources/views/layouts/app.blade.php`) supaya menyatu dengan
header, ditambah ikon kecil (mis. gembok/user) agar terlihat sebagai area
staf dan bukan tautan konten biasa.

## 8. Pengujian

Test feature yang ditulis bersamaan dengan implementasi:

1. User `role = petugas` bisa login dan mengakses `/petugas`, tapi diarahkan
   pergi (bukan mendapat isi panel) saat mencoba mengakses `/admin`.
2. User `role = administrator` bisa mengakses `/admin` maupun `/petugas`.
3. Tamu yang mengakses `/petugas` diarahkan ke halaman login, bukan
   menerima error 500/403 mentah.
4. Widget statistik menampilkan angka yang benar untuk tiap status, dengan
   beberapa `FormSubmission` berstatus campuran sebagai data uji.
5. Petugas mengubah status permohonan lewat aksi Ubah Status di panel
   petugas menulis satu baris baru ke `submission_status_logs` (memakai
   ulang `UpdateSubmissionStatus`).
6. Petugas menolak permohonan (memilih status `ditolak` lewat aksi yang
   sama) tercatat dan mengubah `status` permohonan menjadi `ditolak`.
7. Resource Petugas di panel admin: Administrator dapat membuat, mengedit,
   dan menghapus akun petugas; daftar yang tampil tidak pernah memuat akun
   `role = administrator`.
8. Header publik menampilkan "Masuk Petugas" saat belum login dan
   "Dashboard Petugas — {nama}" saat login sebagai petugas.

Tidak ada logika murni baru yang perlu unit test terpisah — satu-satunya
logika baru (`canAccessPanel`) adalah method Eloquent yang paling pas diuji
lewat feature test HTTP di atas.

## 9. Di luar cakupan fase ini

- Penugasan/pembagian permohonan ke petugas tertentu — antrian dibagi
  bersama; semua petugas melihat dan bisa memproses seluruh permohonan.
- Notifikasi (email/WhatsApp) ke petugas saat ada permohonan baru masuk.
- Alur "lupa password" self-service untuk petugas — reset dilakukan manual
  oleh Administrator.
- Role tambahan di luar Administrator dan Petugas (mis. "Supervisor").
- Log aktivitas login/akses terpisah dari riwayat status permohonan yang
  sudah ada.

## 10. Risiko

| Risiko | Penanganan |
|---|---|
| Panel admin dan petugas berbagi guard yang sama | Ini pola resmi multi-panel Filament; pembeda akses murni lewat `canAccessPanel()`, bukan sesi terpisah. Diterima secara sadar. |
| Resource Permohonan petugas memakai ulang class tabel/form admin | Perubahan pada tabel admin ikut memengaruhi tampilan petugas — disengaja (§2.4), didokumentasikan agar tidak mengejutkan saat maintenance berikutnya. |
| Administrator melihat tautan "Masuk Petugas" di situs publik saat login | Bukan kebocoran akses baru — Administrator memang sudah berhak masuk `/petugas` (§2.3). |
| Akun petugas dihapus sementara masih ada permohonan yang pernah mereka proses | Riwayat status tidak ikut terhapus (`user_id` nullable, `nullOnDelete`); hanya kehilangan atribusi nama petugas pada baris riwayat lama. |
