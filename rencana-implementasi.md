# Rencana Implementasi — CMS Sekolah (Laravel + Filament)

Dokumen ini adalah PRD + rencana implementasi untuk membangun **Core CMS Sekolah** versi baru menggunakan Laravel. Dipakai sebagai acuan saat vibe coding di sesi berikutnya (folder Laravel baru).

---

## 1. Ringkasan & Tujuan

Membangun CMS (Content Management System) untuk website sekolah, di mana:

- **Developer (kamu)** membangun & memelihara kode.
- **Client (pihak sekolah)** login ke admin panel untuk mengubah konten website **tanpa perlu ngoding**.

CMS ini adalah **produk yang dipakai berulang**: satu master codebase, di-deploy ke banyak client dengan **instance terpisah** (1 client = 1 folder + 1 database di hosting masing-masing). **Bukan multi-tenant** — tidak ada data yang terhubung antar client. Ke depan konsep yang sama bisa diturunkan untuk vertikal lain (UMKM, website desa), tapi **fokus pertama = sekolah**.

## 2. Keputusan Teknis (sudah final)

| Aspek | Keputusan |
|---|---|
| Bahasa | PHP (100% di server) |
| Framework | Laravel (versi stabil terbaru) |
| Admin panel | **Filament** (auto-generate CRUD) di `/admin` |
| Frontend publik | **Blade** (server-rendered, bukan SPA) |
| Database | MySQL |
| Node.js | Hanya di mesin lokal untuk build asset (Vite). **Tidak** perlu di server client. |
| Deployment | Build & `composer install` di lokal → zip seluruh folder (termasuk `vendor/`) → upload manual via cPanel File Manager/FTP |
| Hosting target | Shared hosting cPanel murah (tanpa SSH/Composer di server) |
| Update ke client | Manual per client (re-build & re-upload folder yang berubah) |
| Role/akses | **1 role saja: Administrator** (akses penuh) |

## 3. Cakupan MVP — "Core CMS"

Yang dibangun di tahap pertama (modul spesifik sekolah seperti Academic, Admission/PPDB, Employees, Student, Scholarships, Achievements **menyusul di tahap berikutnya**, bukan sekarang):

1. **Auth admin** — login Filament, 1 user Administrator.
2. **Pages** — halaman statis (Tentang Kami, Visi Misi, Kontak, dll).
3. **Posts (Berita & Pengumuman)** — konten ber-tanggal, ber-kategori, ada gambar unggulan.
4. **Post Categories** — kategori untuk post.
5. **Media Library** — upload file terpusat, bisa dipakai ulang lintas Page/Post.
6. **Menu Navigasi** — admin atur sendiri menu website (urutan, label, target link).
7. **Settings situs** — identitas sekolah (nama, logo, alamat, kontak, warna tema dasar).
8. **Frontend publik (Blade)** — homepage, halaman Page by slug, listing + detail Post, menu & settings dibaca dari DB.

## 4. Packages yang Dipakai

- `filament/filament` — admin panel builder.
- `spatie/laravel-medialibrary` + plugin Filament (`filament/spatie-laravel-media-library-plugin`) — media library & file picker reusable.
- `spatie/laravel-settings` + plugin Filament settings — pengaturan situs key-value yang bisa diubah dari UI tanpa deploy ulang.

> Catatan: **tidak** pakai Spatie Permission / Filament Shield karena hanya 1 role.

## 5. Arsitektur Data

### Tabel & Model

- **users** — bawaan Laravel (admin login). Seeder untuk 1 admin default.
- **pages** — `id, title, slug (unique), content (longText), status (draft|published), timestamps`.
- **post_categories** — `id, name, slug (unique), timestamps`.
- **posts** — `id, title, slug (unique), excerpt (nullable), content (longText), post_category_id (FK, nullable), published_at (nullable), status (draft|published), timestamps`. Gambar unggulan via media library (collection `featured`).
- **menus** — `id, label, sort_order (int), type (page|post|url), target (string: slug/URL), parent_id (nullable, self-FK untuk submenu), timestamps`.
- **settings** — dikelola oleh `spatie/laravel-settings` (class `GeneralSettings`: `site_name, logo, address, phone, email, primary_color`, dll).

### Relasi

- `Post` belongsTo `PostCategory`.
- `PostCategory` hasMany `Post`.
- `Menu` self-referencing (`parent_id`) untuk nested menu.
- `Page` & `Post` implement `HasMedia` (Spatie) untuk gambar.

## 6. Alur Data

Admin login → Filament `/admin` → CRUD tersimpan ke MySQL → frontend Blade baca langsung dari DB yang sama saat request → pengunjung lihat perubahan segera setelah admin save. Tanpa API layer / cache khusus untuk MVP.

## 7. Error Handling

- Validasi form via rules bawaan Filament (required, unique slug, dll).
- Slug Page/Post tidak ketemu → `abort(404)` + Blade error page sederhana.
- Tidak ada exception handling rumit untuk MVP.

## 8. Testing

- MVP: **manual testing** di browser (isi form admin, cek tampilan publik).
- Automated feature test dasar menyusul saat produk distabilkan (tidak blocking MVP).

---

## 9. Tahapan Implementasi (urutan kerja di sesi berikutnya)

### Fase 0 — Setup Project
- [ ] `laravel new` (atau `composer create-project`) project baru.
- [ ] Konfigurasi `.env`: koneksi MySQL, `APP_NAME`, `APP_URL`.
- [ ] Buat database lokal.
- [ ] Install Filament + jalankan `filament:install --panels` (panel di `/admin`).
- [ ] Install `spatie/laravel-medialibrary` + plugin Filament (publish & migrate).
- [ ] Install `spatie/laravel-settings` + plugin Filament.
- [ ] `git init` + commit awal.

### Fase 1 — Auth & Admin Panel
- [ ] Konfigurasi User model untuk Filament (`FilamentUser` bila perlu).
- [ ] Seeder admin default (email + password, dicatat di README).
- [ ] Verifikasi bisa login ke `/admin`.

### Fase 2 — Migrations & Models
- [ ] Migration + model: `Page`.
- [ ] Migration + model: `PostCategory`.
- [ ] Migration + model: `Post` (FK ke `post_categories`).
- [ ] Migration + model: `Menu` (self-FK `parent_id`).
- [ ] Implement `HasMedia` di `Page` & `Post`, definisikan media collection.
- [ ] Buat `GeneralSettings` class + migration settings + seeder default.

### Fase 3 — Filament Resources
- [ ] `PageResource` — form (title, slug auto dari title, rich editor content, status, media upload) + table (title, status, updated_at) + filter status.
- [ ] `PostCategoryResource` — form (name, slug) + table sederhana.
- [ ] `PostResource` — form (title, slug, excerpt, rich content, kategori (select), published_at (datepicker), status, featured image) + table (title, kategori, status, published_at) + filter kategori & status.
- [ ] `MenuResource` — form (label, type select, target, parent select, sort_order) + table reorderable (drag sort) + tampilkan hierarki.
- [ ] Integrasi media library plugin di form Page & Post (SpatieMediaLibraryFileUpload).

### Fase 4 — Settings Page
- [ ] Filament custom Settings Page (nama situs, logo upload, alamat, telepon, email, warna tema).
- [ ] Pastikan tersimpan via `spatie/laravel-settings` & bisa dibaca frontend.

### Fase 5 — Frontend Publik (Blade)
- [ ] Layout utama Blade: baca `GeneralSettings` (nama/logo/kontak) + render menu dari tabel `menus` (termasuk submenu).
- [ ] `HomeController` + view: post terbaru + halaman/section yang di-pin.
- [ ] `PageController@show` (by slug) + view.
- [ ] `PostController@index` (listing + filter kategori) & `@show` (detail) + view.
- [ ] Routing publik + route model binding by slug.
- [ ] Blade error page 404 sederhana.
- [ ] Styling dasar (Tailwind via Vite) mengikuti `primary_color` dari settings bila memungkinkan.

### Fase 6 — Persiapan Deployment (dokumentasi, belum deploy)
- [ ] Tulis `DEPLOY.md`: cara build lokal (composer install --no-dev --optimize-autoloader, npm run build), zip termasuk `vendor/` & `public/build`, langkah upload ke cPanel.
- [ ] Dokumentasikan penyesuaian document root shared hosting (isi `public/` dipindah ke root domain, sesuaikan path di `index.php`, atau arahkan domain ke subfolder `public/`).
- [ ] Checklist pasca-upload: set `.env` production, `php artisan key:generate` (atau isi APP_KEY manual), import DB, cek permission folder `storage/` & `bootstrap/cache/`.

---

## 10. Definisi Selesai (MVP)

- Admin bisa login ke `/admin`.
- Admin bisa CRUD Pages, Posts, Categories, Menu, dan atur Settings + upload media — semua tanpa sentuh kode.
- Website publik menampilkan homepage, halaman, listing & detail berita, dengan menu + identitas situs yang mengikuti data admin.
- Berjalan mulus di lokal; langkah deploy ke shared hosting terdokumentasi.

## 11. Di Luar Scope MVP (menyusul)

- Modul sekolah: Academic, Admission/PPDB, Employees, Student profile, Scholarships, Achievements.
- Multi-role & permission granular.
- Multi-tenant.
- Automated test suite.
- Vertikal lain (UMKM, desa).
