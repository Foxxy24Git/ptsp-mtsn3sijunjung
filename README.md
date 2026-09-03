# PTSP Online

Aplikasi Pelayanan Terpadu Satu Pintu (PTSP) untuk sekolah/madrasah: masyarakat memilih layanan dari katalog, mengajukan permohonan lewat formulir dinamis beserta berkas, lalu melacak statusnya dengan kode resi. Seluruh isi katalog dan field formulir dikelola operator lewat panel admin (CMS) tanpa menyentuh kode.

Proyek ini adalah fork mandiri dari CMS-web — satu instance dengan `.env`, database, dan repo git sendiri, bukan modul di dalam CMS-web.

Acuan desain ada di [`docs/superpowers/specs/2026-09-03-ptsp-online-design.md`](docs/superpowers/specs/2026-09-03-ptsp-online-design.md), rencana implementasi ada di [`docs/superpowers/plans/2026-09-03-ptsp-online.md`](docs/superpowers/plans/2026-09-03-ptsp-online.md).

## Tech Stack

| Aspek | Pilihan |
|---|---|
| Framework | Laravel 13 (PHP 8.4) |
| Admin panel | Filament v5 di `/admin` |
| Frontend publik | Blade (server-rendered) |
| Database | MySQL |
| Media | `spatie/laravel-medialibrary` |
| Settings | `spatie/laravel-settings` |
| Role/akses | 1 role: **Administrator** (tanpa sistem permission) |

## Setup Lokal

```bash
composer install
npm install
cp .env.example .env          # sesuaikan koneksi MySQL
php artisan key:generate
php artisan migrate --seed     # migrasi + seed admin default
npm run build                  # atau: npm run dev
php artisan serve              # atau akses via Herd: http://ptsp-online.test
```

## Admin Panel

- URL: `/admin` (mis. `http://ptsp-online.test/admin`)

### Kredensial Admin Default

| Field | Nilai |
|---|---|
| Email | `admin@sekolah.test` |
| Password | `password` |

> ⚠️ **Kredensial default ini WAJIB diganti setelah deploy ke client.** Ganti lewat admin panel (profil) atau jalankan ulang `AdminUserSeeder` setelah mengubah nilainya.

Seeder admin ada di `database/seeders/AdminUserSeeder.php` (idempotent — aman dijalankan ulang).
