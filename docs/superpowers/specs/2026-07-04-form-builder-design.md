# Desain: Fitur Form Builder (ala Google Form)

**Tanggal:** 2026-07-04
**Status:** Disetujui (menunggu review spec akhir)

## Ringkasan

Menambahkan fitur *form builder* ke CMS: admin dapat membuat form berisi
pertanyaan (mis. "Pendaftaran Siswa Baru"), menerbitkannya sebagai halaman
publik `/form/{slug}`, menautkannya dari menu navigasi, lalu melihat dan
mengekspor jawaban yang masuk melalui panel Filament.

## Keputusan Desain (hasil brainstorming)

- **Akses:** Form diisi oleh **publik tanpa login** di halaman publik; bisa
  ditautkan dari menu navigasi.
- **Tipe field yang didukung (5):** `text` (teks singkat), `textarea` (teks
  panjang), `select` (pilih satu / dropdown), `checkbox` (pilih banyak),
  `file` (upload file/foto). Setiap field punya flag `required`.
- **Kelola jawaban:** lihat daftar & detail di admin, export CSV/Excel, dukung
  upload file. **Tidak ada** notifikasi email.
- **Pendekatan penyimpanan:** Opsi B — field bertabel + jawaban JSON (3 tabel).

## Model Data (3 tabel)

### `forms`
| kolom | tipe | keterangan |
|---|---|---|
| id | bigint | |
| title | string | judul form |
| slug | string, unik | untuk URL `/form/{slug}` |
| description | text, nullable | pengantar di atas form |
| success_message | text, nullable | pesan setelah sukses (default "Terima kasih…") |
| status | string | `draft` / `published`; hanya `published` diakses publik |
| timestamps | | |

### `form_fields` (relasi ke form, diurutkan)
| kolom | tipe | keterangan |
|---|---|---|
| id | bigint | id stabil → jadi key jawaban |
| form_id | FK → forms (cascade delete) | |
| label | string | teks pertanyaan |
| type | string | `text` / `textarea` / `select` / `checkbox` / `file` |
| options | json, nullable | daftar pilihan (untuk select & checkbox) |
| required | boolean, default false | wajib diisi |
| sort_order | integer, default 0 | urutan tampil |

### `form_submissions` (1 baris per pengiriman)
| kolom | tipe | keterangan |
|---|---|---|
| id | bigint | |
| form_id | FK → forms (cascade delete) | |
| data | json | jawaban di-key oleh `form_field.id` |
| timestamps | | `created_at` = waktu kirim |

Contoh `data`: `{"12":"Budi","13":["Basket","Musik"],"14":"forms/abc.jpg"}`
(key = id field, nilai = string / array / path file).

### Model Eloquent
- `Form` — `hasMany(FormField)` (orderBy sort_order), `hasMany(FormSubmission)`,
  route-key `slug`.
- `FormField` — `belongsTo(Form)`; cast `options` → array.
- `FormSubmission` — `belongsTo(Form)`; cast `data` → array; helper untuk
  memetakan `data` (key id) → pasangan label→jawaban saat ditampilkan.

## Admin (Filament: `FormResource`)

Mengikuti pola resource yang ada (Pages/Posts/Menus): Resource + Schemas + Tables + Pages.

### Form builder (Create/Edit Form)
- Field: `title`, `slug` (auto dari title, dapat diedit), `description`,
  `success_message`, `status`.
- **Repeater `fields`** (relationship ke `form_fields`):
  - `label`, `type` (Select 5 opsi), `options` (muncul hanya bila
    type = select/checkbox), `required` (Toggle).
  - Reorderable → `sort_order` mengikuti posisi.

### Daftar Form (ListForms)
- Kolom: Judul, Slug, Status (badge), jumlah jawaban.
- Aksi: Edit, Delete, tautan ke jawaban.

### Melihat jawaban
- Relation manager **Submissions** di halaman Edit Form (atau halaman terpisah).
- Tabel jawaban: tanggal kirim + ringkasan field; detail = semua pertanyaan →
  jawaban (file sebagai link/preview). Read-only; boleh dihapus.
- **Tombol Export CSV** di header: unduh semua jawaban form (kolom = label
  field, baris = tiap submission). Format `.csv` (kompatibel Excel), tanpa
  dependency baru.

## Halaman Publik & Submission

### Rute (routes/web.php)
```
GET  /form/{form:slug}   → FormController@show    (name: forms.show)
POST /form/{form:slug}   → FormController@submit
```
Didaftarkan **sebelum** catch-all `/{page:slug}`, dan segmen `form`
dikecualikan dari regex catch-all halaman (seperti `admin`/`up`).

### show()
- Hanya form `published` (draft → 404).
- Blade me-loop `form_fields` urut `sort_order` → input HTML per tipe:
  text → `input[type=text]`, textarea → `textarea`, select → `select`,
  checkbox → banyak `input[type=checkbox]` (name array), file → `input[type=file]`.
- Memakai `layouts/app` + Tailwind, konsisten dengan situs.

### submit()
1. Validasi server-side dinamis dari definisi field:
   - `required` → wajib.
   - `select`/`checkbox` → nilai harus termasuk `options` (anti-tamper).
   - `file` → batasi tipe (gambar/pdf) & ukuran (mis. maks 2–5 MB).
2. File diupload ke disk `public` di `forms/…`; simpan path di `data`.
3. Simpan `FormSubmission` dengan `data` di-key oleh `field.id`.
4. Redirect balik dengan `success_message` (pola Post/Redirect/Get).

### Error handling
- Slug tidak ada / draft → 404.
- Validasi gagal → kembali ke form dengan pesan error + `old()` input.
- Upload gagal → pesan error; submission tidak tersimpan sebagian.

## Integrasi Menu

Menyambung fitur menu yang ada (`page`/`post`/`url`):
- Tambah opsi tipe **`form`** di `MenuForm` (Select) dan `Menu::url()`:
  `'form' => route('forms.show', $target)` → `/form/{slug}`.
- Target form sebaiknya jadi **dropdown pilih form** (bukan ketik manual slug)
  agar tidak salah slug.

## Testing (feature tests)

- Form `published` bisa dibuka; `draft` → 404.
- Submit valid → tersimpan di DB dengan `data` benar; muncul pesan sukses.
- Field `required` kosong → gagal validasi, tidak tersimpan.
- Nilai select/checkbox di luar `options` → ditolak.
- Upload file → tersimpan & path tercatat (pakai `Storage::fake`).
- `Menu::url()` tipe `form` → `/form/{slug}`.
- Export CSV → header = label field, baris = jawaban.

## Di Luar Cakupan (YAGNI)

- Notifikasi email saat ada jawaban baru.
- Login/registrasi user publik.
- Tipe field lanjutan (tanggal khusus, rating, grid, logika bercabang).
- Batas jumlah pengiriman / anti-spam CAPTCHA (bisa jadi iterasi berikutnya).
