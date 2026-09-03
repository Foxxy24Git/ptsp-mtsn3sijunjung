# DEPLOY.md — Panduan Deploy ke Shared Hosting cPanel

Panduan langkah demi langkah untuk men-deploy **CMS Sekolah** (Laravel 13 + Filament v5)
ke **shared hosting cPanel murah yang TIDAK punya SSH & TIDAK punya Composer di server**.

**Strategi inti:** semua proses build (`composer`, `npm`) dijalankan **di komputer lokal**,
lalu **seluruh folder — termasuk `vendor/` dan `public/build` — di-zip dan diupload manual**
lewat cPanel File Manager (atau FTP). Server hanya menjalankan PHP; tidak meng-install apa pun.

> 1 client = 1 folder + 1 database, instance terpisah (bukan multi-tenant).
> Ulangi panduan ini untuk tiap client. Untuk update client yang sudah jalan, lihat [Bagian 6](#6-update-ke-client-yang-sudah-berjalan).

---

## 0. Prasyarat

**Di lokal (mesin developer):**
- PHP 8.3+ dan Composer terpasang.
- Node.js + npm terpasang (hanya untuk build asset — TIDAK diperlukan di server).
- Akses ke database lokal (untuk export data awal).

**Di hosting (cPanel client):**
- PHP **8.3 atau 8.4** bisa dipilih (MultiPHP Manager).
- MySQL (buat database + user lewat menu *MySQL Databases*).
- Ekstensi PHP aktif (umumnya sudah default): `bcmath`, `ctype`, `curl`, `dom`,
  `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`.
  > `gd` & `intl` sering perlu dinyalakan manual — cek di *Select PHP Version → Extensions*.

---

## 1. Build di Lokal

Jalankan dari root project. Perintah ini menyiapkan `vendor/` versi produksi dan asset frontend.

```bash
# 1a. Dependency PHP versi produksi (tanpa paket dev, autoloader dioptimasi)
composer install --no-dev --optimize-autoloader

# 1b. Build asset frontend (Vite → menghasilkan public/build/)
npm install          # sekali saja, atau jika package.json berubah
npm run build
```

> **Kenapa `--no-dev`?** Membuang paket dev (Pint, PHPUnit, Pail, dll) sehingga `vendor/`
> lebih kecil & bersih untuk produksi.
> **Setelah `--no-dev`**, kalau mau lanjut ngoding di lokal, jalankan `composer install` biasa
> lagi untuk mengembalikan paket dev.

### 1c. Siapkan APP_KEY (karena server tak bisa jalankan artisan)

Server tanpa SSH tidak bisa `php artisan key:generate`. Ambil nilainya dari lokal lalu
tempel manual ke `.env` produksi nanti (lihat [Bagian 5](#5-checklist-pasca-upload)):

```bash
php artisan key:generate --show
# contoh output: base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
# SIMPAN nilai ini — akan dipakai di .env produksi.
```

### 1d. JANGAN cache config sebelum zip

**Penting.** Jangan jalankan `php artisan config:cache` / `optimize` sebelum mem-paket.
Cache itu akan "membekukan" nilai `.env` **lokal** (DB, APP_URL) ke dalam
`bootstrap/cache/config.php`, dan server tanpa SSH tidak bisa menghapusnya lagi.
Pastikan folder `bootstrap/cache/` **tidak berisi** `config.php`, `routes-*.php`, `events.php`,
`packages.php`, atau `services.php` hasil cache. Kalau ada, hapus:

```bash
php artisan optimize:clear
```

`--optimize-autoloader` (langkah 1a) aman — itu hanya autoloader Composer, bukan cache config.

---

## 2. Membuat Paket (zip) untuk Upload

Zip **seluruh isi project**, **kecuali** yang tercantum di daftar "jangan diupload".

### ✅ WAJIB diikutkan (walau di-`.gitignore`)
- `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `resources/`, `storage/`
- `public/` **beserta** `public/build/` (hasil `npm run build`)
- **`vendor/`** ← inti dari strategi ini (server tak punya Composer)
- `artisan`, `composer.json`, `composer.lock`, `package.json`

> `vendor/` dan `public/build/` di-`.gitignore` (tidak masuk git), tapi **HARUS** ikut di zip.
> Kalau lupa, situs akan blank/500 (autoload hilang) atau tanpa CSS (asset hilang).

### ❌ JANGAN diupload
| Item | Alasan |
|---|---|
| `node_modules/` | Hanya untuk build lokal; besar & tak dipakai server. |
| `.git/` | History repo, tak perlu di produksi. |
| `.env` (punya lokal) | Berisi kredensial lokal; server pakai `.env` produksi sendiri. |
| `tests/`, `phpunit.xml` | Tidak dipakai di produksi. |
| `storage/logs/*.log` | Log lokal, mulai bersih di server. |
| `storage/app/public/*` isi media lokal | Opsional — lihat catatan simbolic link di [Bagian 5](#5-checklist-pasca-upload). |
| `bootstrap/cache/*.php` | Cache config lokal (lihat 1d). |

**Cara zip cepat (macOS/Linux), sudah mengecualikan yang berat:**

```bash
zip -r ../cms-deploy.zip . \
  -x "node_modules/*" ".git/*" "tests/*" "*.log" \
     ".env" "storage/framework/cache/data/*"
```

Upload `cms-deploy.zip` ke cPanel File Manager, lalu **Extract** di lokasi target.
(File Manager cPanel jauh lebih cepat meng-extract 1 zip daripada FTP ribuan file `vendor/`.)

---

## 3. Menyesuaikan Document Root

Laravel butuh **hanya isi folder `public/`** yang boleh diakses publik; sisanya (`app/`,
`vendor/`, `.env`, dll) harus di luar jangkauan browser. Ada **dua cara**. Pilih salah satu.

### Opsi A — Arahkan domain ke subfolder `public/` (paling bersih, disarankan)

Upload/extract seluruh project ke satu folder (mis. `/home/USER/cms/`), lalu **set document
root domain ke `.../cms/public`**:

- Domain utama: *WHM/cPanel → Domains* (atau minta host) ubah document root ke `cms/public`.
- Addon/Subdomain: saat membuatnya di *Domains / Subdomains*, isi **Document Root = `cms/public`**.

**Tidak perlu mengubah kode sama sekali.** Struktur jadi:

```
/home/USER/cms/          ← seluruh project (di luar public_html)
  app/  vendor/  .env  ...
  public/               ← INI yang jadi document root domain
```

> Kalau panel hosting mengizinkan mengubah document root, **selalu pilih Opsi A**.

### Opsi B — Pindahkan isi `public/` ke root domain (kalau doc root tak bisa diubah)

Banyak hosting mengunci document root ke `public_html`. Solusinya: taruh project di luar
`public_html`, dan pindahkan **isi** `public/` ke dalam `public_html`.

1. Upload/extract project ke folder di luar web root, mis. `/home/USER/cms_app/`.
2. **Pindahkan isi** folder `cms_app/public/` (yaitu `index.php`, `.htaccess`, `build/`,
   `favicon.ico`, `robots.txt`, dll) ke `/home/USER/public_html/`. Folder `public/` kosong
   boleh dihapus.
3. Edit `public_html/index.php` — arahkan path ke folder project. Yang semula relatif
   `../` (satu tingkat) menjadi `../cms_app/`:

```php
// SEBELUM (index.php bawaan, project & public jadi satu induk):
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// SESUDAH (public_html terpisah dari folder project 'cms_app'):
if (file_exists($maintenance = __DIR__.'/../cms_app/storage/framework/maintenance.php')) {
require __DIR__.'/../cms_app/vendor/autoload.php';
$app = require_once __DIR__.'/../cms_app/bootstrap/app.php';
```

> Sesuaikan `cms_app` dengan nama folder project yang kamu pakai. Ada **3 baris** yang diubah
> (`$maintenance`, `vendor/autoload.php`, `bootstrap/app.php`).
> Pastikan `.htaccess` bawaan Laravel ikut pindah ke `public_html` (untuk URL rapi tanpa `index.php`).

---

## 4. Siapkan Database

Karena tak ada SSH, **tidak** menjalankan `php artisan migrate` di server. Sebagai gantinya,
**export database lokal → import ke server** lewat phpMyAdmin.

1. Di lokal, pastikan DB sudah lengkap: `php artisan migrate --seed` (schema + admin default +
   tabel `sessions`/`cache`/`jobs`).
2. Export DB lokal:
   ```bash
   mysqldump -u root -p cms_web > cms_dump.sql
   ```
   (atau lewat phpMyAdmin lokal → Export → SQL)
3. Di cPanel: *MySQL Databases* → buat **database baru** + **user baru** → **Add user to
   database** (beri All Privileges). Catat nama DB, user, password.
4. *phpMyAdmin* di cPanel → pilih DB baru → **Import** → unggah `cms_dump.sql`.

> Dump ini sudah berisi user admin default (`admin@sekolah.test` / `password`) — **WAJIB
> diganti** setelah login (lihat README). Tabel `sessions`/`cache` boleh kosong, tak masalah.

---

## 5. Checklist Pasca-Upload

Kerjakan berurutan setelah file & DB siap:

- [ ] **Buat `.env` produksi** di root project server (bukan di `public_html`). Salin dari
  `.env.example`, lalu sesuaikan:
  ```dotenv
  APP_NAME="Nama Sekolah Client"
  APP_ENV=production
  APP_KEY=base64:...        # tempel nilai dari `php artisan key:generate --show` (langkah 1c)
  APP_DEBUG=false           # WAJIB false di produksi
  APP_URL=https://domainclient.sch.id

  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1         # atau 'localhost' sesuai info hosting
  DB_DATABASE=namauser_cms  # nama DB dari cPanel (biasanya berprefix user)
  DB_USERNAME=namauser_admin
  DB_PASSWORD=passwordkuat

  # Shared hosting tak punya worker background → jalankan job inline:
  QUEUE_CONNECTION=sync
  ```
- [ ] **APP_KEY terisi.** Kalau `.env` kosong `APP_KEY=`, situs error. Tempel nilai `base64:...`
  dari langkah 1c. (Tak bisa `key:generate` di server tanpa SSH.)
- [ ] **`APP_DEBUG=false`** & `APP_ENV=production`. Jangan pernah biarkan `true` di produksi
  (bocor stack trace & kredensial).
- [ ] **Database sudah di-import** (Bagian 4) dan kredensial di `.env` cocok.
- [ ] **Permission folder writable** — set `storage/` dan `bootstrap/cache/` ke **755**
  (rekursif). Kalau upload media / tulis log gagal, naikkan ke **775**. Jangan pakai 777.
  > Lewat File Manager: klik kanan folder → *Change Permissions* → centang rekursif.
- [ ] **Media library / symbolic link `public/storage`.** Media (logo, gambar unggulan) ditulis
  ke `storage/app/public` dan diakses publik lewat symlink `public/storage`. Symlink ini
  **tidak ikut** di zip (di-`.gitignore`, & path lokalnya absolut). Buat ulang di server:
  - **Kalau host izinkan symlink:** buat file sekali-pakai `public/link.php` berisi
    `<?php symlink(__DIR__.'/../storage/app/public', __DIR__.'/storage');` (sesuaikan `../`
    dengan struktur Opsi A/B), buka di browser sekali, lalu **hapus filenya**.
  - **Kalau symlink diblokir:** ubah `.env` → `MEDIA_DISK=public` diarahkan ke direktori nyata,
    atau paling praktis: buat folder biasa `public/storage` dan set `FILESYSTEM_DISK` agar
    menulis langsung ke situ. (Untuk MVP paling mudah: aktifkan symlink; mayoritas cPanel bisa.)
  > Tanpa langkah ini, teks & layout muncul tapi **gambar tidak tampil**.
- [ ] **PHP version** di *MultiPHP Manager* = 8.3/8.4; ekstensi `gd` & `intl` aktif.
- [ ] **Uji:**
  - Buka `https://domainclient.sch.id` → homepage tampil (dengan CSS).
  - Buka `/admin` → bisa login (`admin@sekolah.test` / `password`), lalu **ganti password**.
  - Upload 1 gambar di admin → cek gambar tampil di frontend (verifikasi symlink OK).

---

## 6. Update ke Client yang Sudah Berjalan

Saat ada perubahan kode/fitur untuk client existing, **jangan re-upload semua** dan **jangan
timpa data client**. Re-build di lokal, lalu upload hanya folder yang berubah sesuai jenis
perubahannya:

| Jenis perubahan | Yang di-build ulang di lokal | Folder/file yang di-upload ulang |
|---|---|---|
| Logika PHP / Blade / route / config | — | `app/`, `resources/`, `routes/`, `config/` (yang berubah saja) |
| Tambah/ubah **package** Composer | `composer install --no-dev --optimize-autoloader` | **seluruh `vendor/`** + `composer.lock` |
| Perubahan **CSS/JS** (Tailwind/Vite) | `npm run build` | **seluruh `public/build/`** |
| Perubahan **skema database** (migration baru) | — | jalankan migration-nya via phpMyAdmin (lihat bawah) |

### ⛔ JANGAN PERNAH ditimpa saat update
- **`.env` client** — berisi APP_KEY, kredensial DB, & APP_URL milik client itu.
- **Database client** — data konten mereka.
- **`storage/app/public/`** (media yang sudah diupload client) — foto/logo mereka.
- **`storage/logs/`, `bootstrap/cache/`** — biarkan milik server.

### Migration untuk client existing (tanpa SSH)
Karena tak ada `php artisan migrate` di server, terapkan perubahan skema lewat **phpMyAdmin**:
- Lihat SQL yang dihasilkan migration baru (di lokal: `php artisan migrate --pretend` menampilkan
  query-nya), lalu jalankan query itu di phpMyAdmin DB client; **atau**
- Buat rute sementara terproteksi yang memanggil `Artisan::call('migrate', ['--force' => true])`,
  akses sekali, lalu **hapus rutenya**.

> **Pola per-client (mirip `PANDUAN.txt` CMS lama):** simpan satu catatan kecil per client
> (mis. `PANDUAN-<client>.txt`) berisi: nama folder di server, struktur (Opsi A/B), nama DB &
> user, versi/tanggal deploy terakhir, dan daftar file yang di-upload pada update terakhir.
> Ini bikin update berikutnya cepat & tak salah timpa.

---

## 7. Troubleshooting Singkat

| Gejala | Kemungkinan sebab & solusi |
|---|---|
| Halaman **blank / HTTP 500** | `vendor/` tak terupload; atau `APP_KEY` kosong; atau permission `storage/`. Cek `storage/logs/laravel.log`. |
| **500 tapi tak ada info** | Sementara set `APP_DEBUG=true` untuk lihat error, baca pesan, **lalu kembalikan `false`**. |
| Situs tampil **tanpa CSS/JS** | `public/build/` tak terupload, atau `APP_URL` salah. Pastikan `npm run build` sudah dijalankan & foldernya ikut. |
| **Gambar tak muncul** | Symlink `public/storage` belum dibuat (lihat Bagian 5). |
| **419 / session expired** saat login admin | `APP_KEY` beda dari saat DB dibuat, atau tabel `sessions` tak ada. Pastikan DB ter-import lengkap. |
| **CSS lama masih muncul** setelah update | Hash file di `public/build` berubah; pastikan `public/build/manifest.json` ikut ter-upload (bukan sisa lama). |

---

**Selesai.** Setelah semua checklist Bagian 5 hijau, CMS siap dipakai client.
Ulangi Bagian 1–5 untuk tiap client baru; gunakan Bagian 6 untuk update berkelanjutan.
