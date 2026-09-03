# Tata Letak Beranda: Reorder + Show/Hide Section

**Tanggal:** 2026-07-03
**Status:** Disetujui (menunggu review spec)

## Tujuan

Memberi admin kemampuan untuk (1) mengatur **urutan** dan (2) **menampilkan/menyembunyikan** section pada halaman beranda publik langsung dari admin panel, tanpa mengubah kode.

Saat ini urutan section di `resources/views/home.blade.php` bersifat *hardcoded*: Hero/Slider → Statistik → Pimpinan → Pendamping/Waka → Berita Terbaru. Item **di dalam** tiap section sudah bisa diurutkan (punya `sort_order` masing-masing), tetapi urutan antar-section belum bisa diatur.

## Scope

5 section beranda yang sudah ada (fixed, tidak bisa tambah/hapus section baru):

| key          | Label (admin)        | Partial                       | Data source (HomeController) |
|--------------|----------------------|-------------------------------|------------------------------|
| `hero`       | Hero / Slider        | `partials.slider` + fallback  | `$slides`                    |
| `stats`      | Statistik            | `partials.stats`              | `$stats`                     |
| `leader`     | Pimpinan             | `partials.leaders`            | `$leader`                    |
| `pendamping` | Pendamping / Waka    | `partials.pendampings`        | `$pendampings`               |
| `posts`      | Berita Terbaru       | (inline di home.blade)        | `$posts`                     |

**Di luar scope:** menambah section custom baru, config per-section (judul custom, dsb.), reorder item di dalam section (sudah ada).

## Arsitektur

Menggunakan pola yang sudah dipakai project ini: `spatie/laravel-settings` (`GeneralSettings` + `SettingsPage` Filament). Tidak menambah tabel DB relasional baru — cukup satu field array di settings, karena 5 section bersifat tetap.

### 1. Penyimpanan (`App\Settings\GeneralSettings`)

Tambah field:

```php
public array $home_sections;
```

Struktur: array terurut, tiap elemen `['key' => string, 'visible' => bool]`. **Posisi array = urutan tampil.**

Migration baru `database/settings/2026_07_03_060000_add_home_sections_to_general_settings.php` (pola sama seperti `add_header_color`). Default = urutan sekarang, semua `visible => true`, agar tampilan tidak berubah sampai admin mengubahnya:

```php
$this->migrator->add('general.home_sections', [
    ['key' => 'hero',       'visible' => true],
    ['key' => 'stats',      'visible' => true],
    ['key' => 'leader',     'visible' => true],
    ['key' => 'pendamping', 'visible' => true],
    ['key' => 'posts',      'visible' => true],
]);
```

### 2. Master map section (sumber kebenaran key → label)

Definisikan satu daftar master 5 section (key → label) di satu tempat — konstanta/method statik, kandidat lokasi: `App\Settings\GeneralSettings` (mis. `GeneralSettings::HOME_SECTIONS`) agar bisa dipakai bersama oleh Filament page maupun logika normalisasi.

**Normalisasi** (dipakai saat membaca `home_sections`): gabungkan data tersimpan dengan master map — buang key yang tidak ada di master (section usang), dan **append** key master yang belum ada di data tersimpan (default `visible => true`) di urutan belakang. Ini menjaga forward-compat: menambah section baru di kode tidak menghilangkannya dari beranda meski data settings lama belum memuatnya.

### 3. UI admin (`ManageGeneralSettings`)

Tambah `Section::make('Tata Letak Beranda')` di form, berisi `Repeater::make('home_sections')`:

- `->reorderable()` dengan drag-handle → atur urutan
- `->addable(false)->deletable(false)` → 5 section tetap
- `->itemLabel(fn (array $state) => label_dari_master_map[$state['key']] ?? $state['key'])`
- Field per item:
  - `Hidden::make('key')` (menyimpan key, tidak diedit user)
  - `Toggle::make('visible')->label('Tampilkan')->default(true)`
- Deskripsi section menjelaskan: geser untuk mengubah urutan, toggle untuk sembunyikan; catatan bahwa section tetap tersembunyi otomatis bila datanya kosong.

Saat form dimuat, `home_sections` dinormalisasi lebih dulu (lihat #2) agar Repeater selalu menampilkan 5 item lengkap.

### 4. Rendering (`home.blade.php`)

Ganti urutan hardcoded menjadi loop atas `$settings->home_sections` (yang dinormalisasi; `$settings` sudah di-share ke view via `AppServiceProvider` composer).

Untuk tiap entry:
1. Skip bila `visible === false` (toggle admin).
2. Render partial sesuai `key` (via `@switch`/`match`), **tetap** dengan guard "data ada" yang sekarang (mis. `@if ($stats->isNotEmpty())`), termasuk fallback hero biru bila `$slides` kosong pada key `hero`.

Hasil: dua lapis penyembunyian — **manual** (toggle admin) **dan** **otomatis** (data kosong). `HomeController` tetap mem-fetch semua data seperti sekarang; hanya blade yang berubah cara menyusunnya.

## Testing

Feature test (`tests/Feature`) untuk route `/`:

1. **Urutan terhormati:** set `home_sections` dengan urutan diacak (mis. `posts` di atas `hero`), sediakan data untuk section terkait, hit `/`, assert posisi penanda tiap section di HTML mengikuti urutan tersebut (bandingkan `strpos`).
2. **Toggle off menyembunyikan:** set satu section `visible => false` meski datanya ada, assert penanda section itu **tidak** muncul.
3. **Auto-hide tetap jalan:** section `visible => true` tapi datanya kosong → tetap tidak muncul (regresi perilaku lama).
4. **Normalisasi:** simpan `home_sections` tanpa salah satu key (mensimulasikan data lama), assert section itu tetap ter-render (di-append oleh normalisasi).

## Berkas yang tersentuh

- `app/Settings/GeneralSettings.php` — field `home_sections` + master map + helper normalisasi
- `database/settings/2026_07_03_060000_add_home_sections_to_general_settings.php` — migration baru
- `app/Filament/Pages/ManageGeneralSettings.php` — Section + Repeater
- `resources/views/home.blade.php` — loop berdasarkan `home_sections`
- `tests/Feature/HomeSectionLayoutTest.php` — feature test baru

## Risiko / Catatan

- Repeater Filament menyimpan ulang seluruh array saat submit; pastikan `key` ikut tersimpan lewat `Hidden` field (bukan hanya label tampilan).
- Perlu memastikan cast `array` pada settings terbaca benar (spatie/laravel-settings mendukung array property secara native).
