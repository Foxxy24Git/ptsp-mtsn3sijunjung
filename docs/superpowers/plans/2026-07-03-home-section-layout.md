# Tata Letak Beranda (Reorder + Show/Hide) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Beri admin kemampuan mengatur urutan dan menampilkan/menyembunyikan 5 section beranda dari admin panel, tanpa mengubah kode.

**Architecture:** Simpan urutan + visibility sebagai satu field array `home_sections` di `GeneralSettings` (pola `spatie/laravel-settings` yang sudah dipakai). Master map key→label + helper normalisasi statik hidup di `GeneralSettings`. Filament `Repeater` (drag-reorder, toggle, tak bisa tambah/hapus) mengedit field ini. `home.blade.php` me-loop `home_sections` yang ternormalisasi dan merender partial yang sudah ada, tetap dengan guard "data ada".

**Tech Stack:** Laravel 13, Filament 5, spatie/laravel-settings, PHPUnit 12 (sqlite `:memory:`, `RefreshDatabase`), Blade.

## Global Constraints

- Bahasa UI & komentar: **Bahasa Indonesia** (ikuti konvensi kode yang ada).
- Default `home_sections` = urutan sekarang, semua `visible => true` — tampilan publik **tidak boleh berubah** sampai admin mengubahnya.
- 5 section **fixed**: `hero`, `stats`, `leader`, `pendamping`, `posts`. Tidak menambah section baru, tidak menambah tabel DB.
- Guard "data ada" yang sekarang **tetap dipertahankan** (auto-hide saat data kosong) — hanya ditambah lapis toggle manual.
- Test framework **PHPUnit** (bukan Pest), namespace `Tests\Feature`, gunakan `RefreshDatabase`.
- Master map key→label adalah satu-satunya sumber kebenaran: `GeneralSettings::HOME_SECTIONS`.

---

### Task 1: Field `home_sections`, master map, normalisasi & migration

**Files:**
- Modify: `app/Settings/GeneralSettings.php`
- Create: `database/settings/2026_07_03_060000_add_home_sections_to_general_settings.php`
- Create: `tests/Unit/HomeSectionsNormalizeTest.php`

**Interfaces:**
- Produces:
  - `GeneralSettings::HOME_SECTIONS` — `array<string,string>` konstanta key→label, urutan default.
  - `GeneralSettings::normalizeSections(array $stored): array` — statik murni; mengembalikan `list<array{key:string,visible:bool}>` berisi hanya key valid (urut sesuai `$stored`, duplikat dibuang), lalu meng-append key master yang belum ada (`visible => true`) di belakang.
  - `GeneralSettings::orderedSections(): array` — instance; `return self::normalizeSections($this->home_sections)`.
  - Property `public array $home_sections;`

- [ ] **Step 1: Tulis unit test normalisasi (gagal dulu)**

Create `tests/Unit/HomeSectionsNormalizeTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Settings\GeneralSettings;
use PHPUnit\Framework\TestCase;

class HomeSectionsNormalizeTest extends TestCase
{
    public function test_keeps_order_and_visibility_from_stored(): void
    {
        $result = GeneralSettings::normalizeSections([
            ['key' => 'posts', 'visible' => false],
            ['key' => 'hero', 'visible' => true],
        ]);

        // Dua entri tersimpan tampil lebih dulu, urutan dipertahankan.
        $this->assertSame('posts', $result[0]['key']);
        $this->assertFalse($result[0]['visible']);
        $this->assertSame('hero', $result[1]['key']);
        $this->assertTrue($result[1]['visible']);
    }

    public function test_appends_missing_master_keys_as_visible(): void
    {
        $result = GeneralSettings::normalizeSections([
            ['key' => 'posts', 'visible' => true],
        ]);

        $keys = array_column($result, 'key');
        // Semua 5 key master hadir.
        $this->assertEqualsCanonicalizing(
            ['hero', 'stats', 'leader', 'pendamping', 'posts'],
            $keys
        );
        // 'posts' tetap di depan; sisanya di-append visible=true.
        $this->assertSame('posts', $result[0]['key']);
        foreach ($result as $section) {
            if ($section['key'] !== 'posts') {
                $this->assertTrue($section['visible']);
            }
        }
    }

    public function test_drops_unknown_and_duplicate_keys(): void
    {
        $result = GeneralSettings::normalizeSections([
            ['key' => 'bogus', 'visible' => true],
            ['key' => 'hero', 'visible' => false],
            ['key' => 'hero', 'visible' => true],
        ]);

        $keys = array_column($result, 'key');
        $this->assertNotContains('bogus', $keys);
        // 'hero' hanya sekali, memakai entri pertama (visible=false).
        $this->assertSame(1, count(array_filter($keys, fn ($k) => $k === 'hero')));
        $heroEntry = collect($result)->firstWhere('key', 'hero');
        $this->assertFalse($heroEntry['visible']);
    }
}
```

- [ ] **Step 2: Jalankan test — pastikan gagal**

Run: `php artisan test tests/Unit/HomeSectionsNormalizeTest.php`
Expected: FAIL — `Error: Call to undefined method App\Settings\GeneralSettings::normalizeSections()` (atau property `home_sections` belum ada).

- [ ] **Step 3: Tambah property, master map & normalisasi ke `GeneralSettings`**

Edit `app/Settings/GeneralSettings.php`. Tambah property `home_sections` setelah `public ?string $youtube;`, dan tambahkan konstanta + method sebelum `group()`:

```php
    public ?string $youtube;

    public array $home_sections;

    /**
     * Master section beranda: key => label. Urutan di sini = urutan default
     * dan menjadi satu-satunya sumber kebenaran daftar section yang valid.
     */
    public const HOME_SECTIONS = [
        'hero' => 'Hero / Slider',
        'stats' => 'Statistik',
        'leader' => 'Pimpinan',
        'pendamping' => 'Pendamping / Waka',
        'posts' => 'Berita Terbaru',
    ];

    /**
     * Bersihkan data tersimpan: buang key tak dikenal & duplikat (pakai entri
     * pertama), pertahankan urutan tersimpan, lalu append key master yang belum
     * ada (visible=true) di belakang. Menjamin selalu 5 section lengkap.
     *
     * @param  array<int,array{key?:string,visible?:bool}>  $stored
     * @return array<int,array{key:string,visible:bool}>
     */
    public static function normalizeSections(array $stored): array
    {
        $known = array_keys(self::HOME_SECTIONS);
        $result = [];
        $seen = [];

        foreach ($stored as $section) {
            $key = $section['key'] ?? null;

            if ($key === null || ! in_array($key, $known, true) || isset($seen[$key])) {
                continue;
            }

            $result[] = ['key' => $key, 'visible' => (bool) ($section['visible'] ?? true)];
            $seen[$key] = true;
        }

        foreach ($known as $key) {
            if (! isset($seen[$key])) {
                $result[] = ['key' => $key, 'visible' => true];
            }
        }

        return $result;
    }

    /**
     * home_sections yang sudah dinormalisasi, siap dirender di beranda.
     *
     * @return array<int,array{key:string,visible:bool}>
     */
    public function orderedSections(): array
    {
        return self::normalizeSections($this->home_sections);
    }
```

- [ ] **Step 4: Jalankan test — pastikan lulus**

Run: `php artisan test tests/Unit/HomeSectionsNormalizeTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Buat settings migration**

Create `database/settings/2026_07_03_060000_add_home_sections_to_general_settings.php`:

```php
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Urutan + visibility section beranda. Default = urutan lama, semua
        // tampil, agar tampilan publik tidak berubah sampai admin mengubahnya.
        $this->migrator->add('general.home_sections', [
            ['key' => 'hero', 'visible' => true],
            ['key' => 'stats', 'visible' => true],
            ['key' => 'leader', 'visible' => true],
            ['key' => 'pendamping', 'visible' => true],
            ['key' => 'posts', 'visible' => true],
        ]);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('general.home_sections');
    }
};
```

- [ ] **Step 6: Jalankan migrasi (dev) & seluruh test**

Run: `php artisan migrate` (untuk DB dev; test memakai `:memory:` yang bermigrasi otomatis)
Run: `php artisan test tests/Unit/HomeSectionsNormalizeTest.php`
Expected: migrate sukses tanpa error; unit test tetap PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Settings/GeneralSettings.php database/settings/2026_07_03_060000_add_home_sections_to_general_settings.php tests/Unit/HomeSectionsNormalizeTest.php
git commit -m "feat: field home_sections + normalisasi urutan section beranda

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Render beranda berdasarkan `home_sections`

**Files:**
- Modify: `resources/views/home.blade.php`
- Create: `tests/Feature/HomeSectionLayoutTest.php`

**Interfaces:**
- Consumes: `$settings->orderedSections()` (dari Task 1); `$slides`, `$stats`, `$leader`, `$pendampings`, `$posts` (dari `HomeController`, tak berubah); `$settings` di-share via `AppServiceProvider` composer.
- Produces: (perilaku) urutan & visibilitas section HTML `/` mengikuti `home_sections`.

**Catatan penanda test (pakai konten yang sudah ada, tanpa media):**
- `hero` (fallback, saat `$slides` kosong) → teks `Selamat Datang di`
- `stats` → atribut `data-countup` / label stat
- `posts` → heading `Berita Terbaru`
- `leader`/`pendamping` butuh media (foto) untuk tampil — **tidak** diuji render-nya di sini; logika loop sudah tercakup oleh hero/stats/posts.

- [ ] **Step 1: Tulis feature test (gagal dulu)**

Create `tests/Feature/HomeSectionLayoutTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Stat;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSectionLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function setSections(array $sections): void
    {
        $settings = app(GeneralSettings::class);
        $settings->home_sections = $sections;
        $settings->save();
    }

    private function makeStat(): Stat
    {
        return Stat::create([
            'icon' => 'heroicon-o-users',
            'label' => 'Mahasiswa Aktif',
            'value' => 15307,
            'color' => '#7f1d1d',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    public function test_sections_render_in_configured_order(): void
    {
        $this->makeStat();
        $this->setSections([
            ['key' => 'posts', 'visible' => true],
            ['key' => 'stats', 'visible' => true],
            ['key' => 'hero', 'visible' => true],
        ]);

        $content = $this->get('/')->assertOk()->getContent();

        $posPosts = strpos($content, 'Berita Terbaru');
        $posStats = strpos($content, 'data-countup');
        $posHero = strpos($content, 'Selamat Datang di');

        $this->assertNotFalse($posPosts);
        $this->assertNotFalse($posStats);
        $this->assertNotFalse($posHero);
        $this->assertLessThan($posStats, $posPosts, 'posts harus sebelum stats');
        $this->assertLessThan($posHero, $posStats, 'stats harus sebelum hero');
    }

    public function test_section_hidden_when_visible_false(): void
    {
        $this->makeStat();
        $this->setSections([
            ['key' => 'hero', 'visible' => true],
            ['key' => 'stats', 'visible' => false],
            ['key' => 'posts', 'visible' => true],
        ]);

        $response = $this->get('/')->assertOk();
        // Section berita tetap tampil, statistik disembunyikan manual.
        $response->assertSee('Berita Terbaru');
        $response->assertDontSee('data-countup', false);
    }

    public function test_section_auto_hidden_when_data_empty(): void
    {
        // stats visible=true tapi tak ada Stat -> tetap tak tampil (regresi lama).
        $this->setSections([
            ['key' => 'stats', 'visible' => true],
            ['key' => 'posts', 'visible' => true],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-countup', false);
    }

    public function test_missing_key_is_still_rendered_via_normalization(): void
    {
        // Data lama tanpa 'posts' -> normalisasi meng-append, section tetap muncul.
        $this->setSections([
            ['key' => 'hero', 'visible' => true],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Berita Terbaru');
    }
}
```

- [ ] **Step 2: Jalankan test — pastikan gagal**

Run: `php artisan test tests/Feature/HomeSectionLayoutTest.php`
Expected: FAIL — `test_sections_render_in_configured_order` gagal karena urutan masih hardcoded (posts saat ini setelah hero/stats).

- [ ] **Step 3: Ganti isi `home.blade.php` menjadi loop**

Replace the whole `@section('content') ... @endsection` block in `resources/views/home.blade.php` with:

```blade
@extends('layouts.app')

@section('content')
    {{-- Urutan & visibilitas section diatur admin via home_sections. Tiap
         section tetap punya guard "data ada": toggle admin menyembunyikan
         manual, data kosong menyembunyikan otomatis. --}}
    @foreach ($settings->orderedSections() as $section)
        @continue(! ($section['visible'] ?? true))

        @switch($section['key'])
            @case('hero')
                {{-- Hero: carousel full-bleed bila ada slide aktif, kalau belum ada pakai hero biru. --}}
                @if ($slides->isNotEmpty())
                    @include('partials.slider', ['slides' => $slides])
                @else
                    <section class="bg-primary text-white">
                        <div class="mx-auto max-w-6xl px-4 py-20 text-center">
                            <h1 class="text-3xl font-extrabold sm:text-5xl">Selamat Datang di {{ $settings->site_name }}</h1>
                            <p class="mx-auto mt-4 max-w-2xl text-base text-white/90 sm:text-lg">
                                Informasi terkini seputar kegiatan, prestasi, dan pengumuman sekolah.
                            </p>
                            <a href="{{ route('posts.index') }}" class="mt-8 inline-block rounded-lg bg-white px-6 py-3 text-sm font-semibold text-primary shadow transition hover:bg-gray-100">
                                Lihat Semua Berita
                            </a>
                        </div>
                    </section>
                @endif
                @break

            @case('stats')
                {{-- Statistik kampus (count-up + hover-fill). Hanya tampil bila ada statistik aktif. --}}
                @if ($stats->isNotEmpty())
                    @include('partials.stats', ['stats' => $stats])
                @endif
                @break

            @case('leader')
                {{-- Sambutan / quote pimpinan (reveal saat scroll). Hanya tampil bila ada pimpinan aktif. --}}
                @if ($leader)
                    @include('partials.leaders', ['leader' => $leader])
                @endif
                @break

            @case('pendamping')
                {{-- Barisan foto di bawah pimpinan (mis. waka). Hanya tampil bila ada pendamping aktif. --}}
                @if ($pendampings->isNotEmpty())
                    @include('partials.pendampings', ['pendampings' => $pendampings])
                @endif
                @break

            @case('posts')
                {{-- Berita terbaru --}}
                <section class="mx-auto max-w-6xl px-4 py-14">
                    <div class="mb-8 flex items-end justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Berita Terbaru</h2>
                            <p class="mt-1 text-sm text-gray-500">Kabar terbaru dari {{ $settings->site_name }}</p>
                        </div>
                        <a href="{{ route('posts.index') }}" class="hidden text-sm font-medium text-primary hover:underline sm:block">Selengkapnya →</a>
                    </div>

                    @if ($posts->isEmpty())
                        <p class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
                            Belum ada berita yang dipublikasikan.
                        </p>
                    @else
                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($posts as $post)
                                @include('partials.post-card', ['post' => $post])
                            @endforeach
                        </div>
                    @endif
                </section>
                @break
        @endswitch
    @endforeach
@endsection
```

- [ ] **Step 4: Jalankan test — pastikan lulus**

Run: `php artisan test tests/Feature/HomeSectionLayoutTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Jalankan test regresi beranda terkait**

Run: `php artisan test tests/Feature/StatTest.php tests/Feature/SliderTest.php tests/Feature/LeaderQuoteTest.php tests/Feature/PendampingTest.php`
Expected: PASS semua (urutan default tak berubah, section lama tetap muncul).

- [ ] **Step 6: Commit**

```bash
git add resources/views/home.blade.php tests/Feature/HomeSectionLayoutTest.php
git commit -m "feat: render section beranda berdasarkan urutan & visibility home_sections

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: UI admin — Repeater "Tata Letak Beranda"

**Files:**
- Modify: `app/Filament/Pages/ManageGeneralSettings.php`
- Create: `tests/Feature/ManageHomeLayoutTest.php`

**Interfaces:**
- Consumes: `GeneralSettings::HOME_SECTIONS`, `GeneralSettings::normalizeSections()` (Task 1); field form `home_sections`.
- Produces: section form "Tata Letak Beranda" berisi `Repeater` yang reorderable, tak addable/deletable, tiap item `Hidden key` + `Toggle visible`.

- [ ] **Step 1: Tulis feature test Filament (gagal dulu)**

Create `tests/Feature/ManageHomeLayoutTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageGeneralSettings;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageHomeLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_settings_page_renders(): void
    {
        Livewire::test(ManageGeneralSettings::class)->assertOk();
    }

    public function test_form_prefills_all_five_sections(): void
    {
        // Simpan data tak lengkap; form harus menampilkan 5 section ternormalisasi.
        $settings = app(GeneralSettings::class);
        $settings->home_sections = [['key' => 'posts', 'visible' => true]];
        $settings->save();

        Livewire::test(ManageGeneralSettings::class)
            ->assertOk()
            ->assertFormSet(function (array $state): bool {
                return count($state['home_sections']) === 5;
            });
    }

    public function test_saving_persists_order_and_visibility(): void
    {
        Livewire::test(ManageGeneralSettings::class)
            ->fillForm([
                'home_sections' => [
                    ['key' => 'posts', 'visible' => true],
                    ['key' => 'hero', 'visible' => false],
                    ['key' => 'stats', 'visible' => true],
                    ['key' => 'leader', 'visible' => true],
                    ['key' => 'pendamping', 'visible' => true],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $saved = app(GeneralSettings::class)->home_sections;
        $this->assertSame('posts', $saved[0]['key']);
        $this->assertFalse($saved[1]['visible']); // hero disembunyikan
        $this->assertSame('hero', $saved[1]['key']);
    }
}
```

- [ ] **Step 2: Jalankan test — pastikan gagal**

Run: `php artisan test tests/Feature/ManageHomeLayoutTest.php`
Expected: FAIL — `test_form_prefills_all_five_sections` gagal (belum ada field `home_sections` di form, count ≠ 5).

- [ ] **Step 3: Tambah section Repeater & prefill ke `ManageGeneralSettings`**

Edit `app/Filament/Pages/ManageGeneralSettings.php`.

Tambahkan import (di blok `use`):

```php
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
```

Tambahkan section baru di akhir array `->components([ ... ])`, setelah `Section::make('Sosial Media')...`:

```php
                Section::make('Tata Letak Beranda')
                    ->description('Seret untuk mengubah urutan section, matikan tombol untuk menyembunyikan. Catatan: section tetap tersembunyi otomatis bila datanya masih kosong.')
                    ->schema([
                        Repeater::make('home_sections')
                            ->hiddenLabel()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderableWithDragAndDrop()
                            ->itemLabel(fn (array $state): ?string => GeneralSettings::HOME_SECTIONS[$state['key'] ?? ''] ?? null)
                            ->schema([
                                Hidden::make('key'),
                                Toggle::make('visible')
                                    ->label('Tampilkan')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ]),
```

Tambahkan method prefill (agar Repeater selalu tampil 5 section lengkap walau data lama tak lengkap) sebagai method pada class, setelah `form()`:

```php
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['home_sections'] = GeneralSettings::normalizeSections($data['home_sections'] ?? []);

        return $data;
    }
```

- [ ] **Step 4: Jalankan test — pastikan lulus**

Run: `php artisan test tests/Feature/ManageHomeLayoutTest.php`
Expected: PASS (3 tests).

> Catatan bila `reorderableWithDragAndDrop()` tak tersedia di versi Filament ini: Repeater reorderable secara default; ganti baris tersebut dengan `->reorderable()` atau hapus (drag-handle tetap muncul). Verifikasi cepat: `grep -rn "reorderableWithDragAndDrop\|function reorderable" vendor/filament/forms/src/Components/Repeater.php`.

- [ ] **Step 5: Jalankan seluruh test suite**

Run: `php artisan test`
Expected: PASS semua (tidak ada regresi).

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Pages/ManageGeneralSettings.php tests/Feature/ManageHomeLayoutTest.php
git commit -m "feat: UI admin Tata Letak Beranda (reorder + show/hide section)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Verifikasi Manual (opsional, setelah semua task)

1. `php artisan migrate` (bila belum) lalu buka admin panel → **Pengaturan Situs**.
2. Di section **Tata Letak Beranda**: seret "Berita Terbaru" ke atas, matikan toggle "Statistik", **Save**.
3. Buka `/` di browser → berita tampil paling atas, statistik tidak muncul meski ada datanya.
4. Nyalakan kembali toggle statistik → statistik muncul lagi (bila ada Stat aktif).
