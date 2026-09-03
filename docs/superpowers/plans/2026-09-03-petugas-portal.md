# Portal Petugas PTSP Online Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambah peran Petugas yang login lewat tautan di header publik, memproses permohonan (ubah status/tolak) lewat panel Filament kerja tersendiri di `/petugas`, dengan dashboard statistik dan akun yang dikelola Administrator dari `/admin`.

**Architecture:** Kolom `role` baru pada tabel `users` yang sudah ada, dibaca oleh `User::canAccessPanel()` untuk membedakan akses `/admin` (khusus administrator) dan `/petugas` (administrator + petugas), keduanya berbagi guard `web` yang sama. Panel `petugas` adalah panel Filament kedua yang resource Permohonan-nya memakai ulang langsung class tabel/form yang sudah dipakai admin — tidak ada logika status/riwayat baru. Halaman publik hanya menambah satu tautan kondisional di header.

**Tech Stack:** PHP 8.4, Laravel 13, Filament v5.6, Blade, Tailwind CSS v4, MySQL (lokal) / SQLite in-memory (test), PHPUnit 12.

**Spec:** `docs/superpowers/specs/2026-09-03-petugas-portal-design.md`

## Global Constraints

Berlaku untuk SEMUA task di bawah:

- **Direktori kerja**: `/Users/user/Herd/ptsp-online`. Semua path relatif terhadap direktori ini.
- **Bahasa**: seluruh teks yang dilihat pengguna, label Filament, dan komentar kode ditulis dalam Bahasa Indonesia.
- **Gaya test**: PHPUnit berbasis kelas (BUKAN Pest), `namespace Tests\Feature;`, `use Illuminate\Foundation\Testing\RefreshDatabase;`, `extends Tests\TestCase`. Database test: SQLite `:memory:` (sudah diatur di `phpunit.xml`).
- **Tidak ada factory untuk `Form`/`FormSubmission`** — buat data test dengan `Model::create([...])`, ikuti gaya `tests/Feature/SubmissionResourceTest.php`. `User` **punya** factory (`database/factories/UserFactory.php`) — boleh dipakai langsung, termasuk override (`User::factory()->create(['role' => ...])`).
- **Migrasi wajib kompatibel SQLite**: hanya menambah kolom. JANGAN mengubah tipe kolom atau memakai `enum` MySQL.
- **Status permohonan** — tetap persis empat nilai yang sudah ada: `diajukan`, `diproses`, `selesai`, `ditolak`. Task-task di bawah TIDAK menambah atau mengubah status ini.
- **Verifikasi manual**: URL `.test` TIDAK bisa dijangkau dari shell. Pakai `php artisan serve --port=8123` lalu `curl http://127.0.0.1:8123/...`.
- **Commit** di akhir setiap task, dengan pesan Bahasa Indonesia berawalan `feat:`/`fix:`/`chore:`/`test:`, diakhiri baris `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`.

---

### Task 1: Kolom `role` pada `users` dan akses panel per-role

**Files:**
- Create: `database/migrations/2026_09_03_120000_add_role_to_users_table.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/UserRoleAccessTest.php`

**Interfaces:**
- Consumes: tabel `users` yang sudah ada.
- Produces:
  - Kolom `users.role` (string, default `administrator`).
  - `App\Models\User::ROLE_ADMINISTRATOR`, `User::ROLE_PETUGAS`, `User::ROLES` (array `kunci => label`).
  - `User::canAccessPanel(Panel $panel): bool` — `admin` hanya untuk `role = administrator`; `petugas` untuk `role = administrator` ATAU `role = petugas`; panel lain selalu `false`.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/UserRoleAccessTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_default_adalah_administrator(): void
    {
        $user = User::factory()->create();

        // Sengaja TIDAK memanggil refresh(): default harus sudah benar di
        // objek hasil create(), bukan cuma setelah dibaca ulang dari DB —
        // itulah yang membuat canAccessPanel() aman dipakai di request yang
        // sama dengan pembuatan user.
        $this->assertSame('administrator', $user->role);
    }

    public function test_administrator_bisa_akses_panel_admin_dan_petugas(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->assertTrue($admin->canAccessPanel(Panel::make()->id('admin')));
        $this->assertTrue($admin->canAccessPanel(Panel::make()->id('petugas')));
    }

    public function test_petugas_hanya_bisa_akses_panel_petugas(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->assertFalse($petugas->canAccessPanel(Panel::make()->id('admin')));
        $this->assertTrue($petugas->canAccessPanel(Panel::make()->id('petugas')));
    }

    public function test_panel_tak_dikenal_selalu_ditolak(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->assertFalse($admin->canAccessPanel(Panel::make()->id('entah-apa')));
    }

    public function test_daftar_role_berisi_dua_nilai(): void
    {
        $this->assertSame(
            ['administrator', 'petugas'],
            array_keys(User::ROLES),
        );
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=UserRoleAccessTest`
Expected: FAIL — kolom `role` tidak ada / konstanta `ROLE_ADMINISTRATOR` tidak ditemukan.

- [ ] **Step 3: Buat migrasi**

File: `database/migrations/2026_09_03_120000_add_role_to_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Default 'administrator' supaya akun admin yang sudah ada
            // otomatis mendapat nilai ini tanpa migrasi data terpisah.
            $table->string('role', 20)->default('administrator');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
```

- [ ] **Step 4: Perbarui model `User`**

File: `app/Models/User.php` — ganti seluruh isi file dengan:

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMINISTRATOR = 'administrator';

    public const ROLE_PETUGAS = 'petugas';

    /** Dua role aplikasi. Kunci = nilai kolom `role`, nilai = label di form. */
    public const ROLES = [
        self::ROLE_ADMINISTRATOR => 'Administrator',
        self::ROLE_PETUGAS => 'Petugas',
    ];

    /**
     * Default di level PHP, bukan cuma di level kolom database: tanpa ini,
     * instance yang baru dibuat (mis. `User::factory()->create()`) punya
     * `role` bernilai null di memori sampai di-refresh dari DB — cukup
     * untuk membuat `canAccessPanel()` salah menolak admin yang baru saja
     * login di siklus request yang sama.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => self::ROLE_ADMINISTRATOR,
    ];

    /**
     * Administrator boleh masuk panel admin maupun petugas (mencakup semua
     * kemampuan petugas). Petugas hanya boleh masuk panel petugas.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->role === self::ROLE_ADMINISTRATOR,
            'petugas' => in_array($this->role, [self::ROLE_ADMINISTRATOR, self::ROLE_PETUGAS], true),
            default => false,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

- [ ] **Step 5: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=UserRoleAccessTest`
Expected: PASS — 5 test lulus.

- [ ] **Step 6: Jalankan seluruh test agar tidak ada regresi**

Run: `php artisan test`
Expected: PASS. (Panel admin harus tetap berfungsi sama seperti sebelumnya karena akun yang sudah ada otomatis `role = administrator`.)

- [ ] **Step 7: Terapkan migrasi ke database pengembangan dan commit**

```bash
php artisan migrate
git add -A
git commit -m "$(cat <<'EOF'
feat: kolom role pada users dan akses panel per-role

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 2: Seeder akun demo (admin eksplisit + petugas baru)

**Files:**
- Modify: `database/seeders/AdminUserSeeder.php`
- Create: `database/seeders/PetugasUserSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/PetugasUserSeederTest.php`

**Interfaces:**
- Consumes: `User::ROLE_ADMINISTRATOR`, `User::ROLE_PETUGAS` dari Task 1.
- Produces: seeder `Database\Seeders\PetugasUserSeeder`, idempotent, membuat akun `petugas@sekolah.test` / `password` dengan `role = petugas`. Dipakai untuk pengujian manual lokal — akun petugas sungguhan dibuat lewat resource "Kelola Petugas" di Task 5.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/PetugasUserSeederTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PetugasUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_satu_akun_petugas_demo(): void
    {
        $this->seed(PetugasUserSeeder::class);

        $petugas = User::where('email', 'petugas@sekolah.test')->sole();
        $this->assertSame(User::ROLE_PETUGAS, $petugas->role);
    }

    public function test_seeder_aman_dijalankan_dua_kali(): void
    {
        $this->seed(PetugasUserSeeder::class);
        $this->seed(PetugasUserSeeder::class);

        $this->assertSame(1, User::where('email', 'petugas@sekolah.test')->count());
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=PetugasUserSeederTest`
Expected: FAIL — `Class "Database\Seeders\PetugasUserSeeder" not found`.

- [ ] **Step 3: Buat seeder `PetugasUserSeeder`**

File: `database/seeders/PetugasUserSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PetugasUserSeeder extends Seeder
{
    /**
     * Seed satu akun Petugas demo untuk pengujian manual lokal.
     *
     * Idempotent: aman dijalankan berulang (updateOrCreate berdasarkan email).
     * Akun petugas sungguhan dibuat Administrator lewat resource "Kelola
     * Petugas" di panel admin — seeder ini murni kemudahan pengujian lokal.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'petugas@sekolah.test'],
            [
                'name' => 'Petugas Demo',
                'password' => Hash::make('password'),
                'role' => User::ROLE_PETUGAS,
                'email_verified_at' => now(),
            ],
        );
    }
}
```

- [ ] **Step 4: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=PetugasUserSeederTest`
Expected: PASS — 2 test lulus.

- [ ] **Step 5: Perbarui `AdminUserSeeder` agar eksplisit soal role**

File: `database/seeders/AdminUserSeeder.php` — ganti isi method `run()`:

```php
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sekolah.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMINISTRATOR,
                'email_verified_at' => now(),
            ],
        );
    }
```

- [ ] **Step 6: Daftarkan seeder baru di `DatabaseSeeder`**

File: `database/seeders/DatabaseSeeder.php`

```php
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            PetugasUserSeeder::class,
            PtspServiceSeeder::class,
        ]);
    }
```

- [ ] **Step 7: Jalankan seluruh test, migrasi seed, dan commit**

```bash
php artisan test
php artisan db:seed --class=PetugasUserSeeder
git add -A
git commit -m "$(cat <<'EOF'
feat: seeder akun demo petugas dan role eksplisit di seeder admin

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: Panel Filament Petugas — provider, resource Permohonan, ubah status & tolak

**Files:**
- Create: `app/Providers/Filament/PetugasPanelProvider.php`
- Modify: `bootstrap/providers.php`
- Create: `app/Filament/Petugas/Resources/Permohonan/PermohonanResource.php`
- Create: `app/Filament/Petugas/Resources/Permohonan/Pages/ListPermohonan.php`
- Test: `tests/Feature/PetugasPanelAccessTest.php`
- Test: `tests/Feature/PetugasUbahStatusTest.php`

**Interfaces:**
- Consumes: `User::canAccessPanel()` dari Task 1; `App\Filament\Resources\FormSubmissions\Tables\FormSubmissionsTable::configure()` dan `App\Actions\UpdateSubmissionStatus` yang SUDAH ADA (tidak diubah).
- Produces: panel Filament `petugas` di `/petugas`, dengan satu resource (`index` saja, tanpa `create`/`edit`) yang tabelnya identik dengan Permohonan di admin — kolom, filter, aksi **Ubah Status** (mencakup `ditolak`), dan aksi **Detail**.

- [ ] **Step 1: Tulis test akses yang gagal**

File: `tests/Feature/PetugasPanelAccessTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_pergi_saat_akses_petugas(): void
    {
        $this->get('/petugas')->assertRedirect();
    }

    public function test_petugas_bisa_membuka_dashboard_petugas(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas)->get('/petugas')->assertOk();
    }

    public function test_petugas_tidak_bisa_membuka_panel_admin(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas)->get('/admin')->assertForbidden();
    }

    public function test_administrator_tetap_bisa_membuka_kedua_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/petugas')->assertOk();
    }

    public function test_petugas_melihat_antrian_permohonan(): void
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);
        $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [],
        ]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas/permohonan')
            ->assertOk()
            ->assertSee('PTSP-2609-A7K3QX')
            ->assertSee('Budi Santoso');
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=PetugasPanelAccessTest`
Expected: FAIL — rute `/petugas` mengembalikan 404 (panel belum terdaftar).

- [ ] **Step 3: Buat `PetugasPanelProvider`**

File: `app/Providers/Filament/PetugasPanelProvider.php`

```php
<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PetugasPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('petugas')
            ->path('petugas')
            ->login()
            ->colors([
                'primary' => Color::Sky,
            ])
            ->discoverResources(in: app_path('Filament/Petugas/Resources'), for: 'App\Filament\Petugas\Resources')
            ->discoverPages(in: app_path('Filament/Petugas/Pages'), for: 'App\Filament\Petugas\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Petugas/Widgets'), for: 'App\Filament\Petugas\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

Catatan: TIDAK memanggil `->default()` — panel `admin` yang sudah ada tetap satu-satunya panel default. `discoverPages`/`discoverWidgets` menunjuk ke direktori yang belum berisi apa pun di task ini; ini aman (dibuktikan oleh `app/Filament/Widgets` yang juga belum ada tapi sudah dipakai `AdminPanelProvider` tanpa error).

- [ ] **Step 4: Daftarkan provider di `bootstrap/providers.php`**

File: `bootstrap/providers.php`

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\PetugasPanelProvider::class,
];
```

- [ ] **Step 5: Buat resource `PermohonanResource` (petugas)**

File: `app/Filament/Petugas/Resources/Permohonan/PermohonanResource.php`

```php
<?php

namespace App\Filament\Petugas\Resources\Permohonan;

use App\Filament\Petugas\Resources\Permohonan\Pages\ListPermohonan;
use App\Filament\Resources\FormSubmissions\Tables\FormSubmissionsTable;
use App\Models\FormSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Tabelnya memakai ulang FormSubmissionsTable yang sama dengan panel admin
 * (kolom, filter, aksi Ubah Status & Detail identik) — satu sumber
 * kebenaran, lihat spec §2.4. Tidak ada halaman create/edit: satu-satunya
 * cara memproses permohonan adalah lewat aksi pada tabel ini.
 */
class PermohonanResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * Wajib eksplisit: nama kelas ini akan dijamakkan Filament dengan aturan
     * bahasa Inggris ("Permohonans"), yang tidak cocok dengan nama folder
     * ("Permohonan") dan berakhir membuat URL dobel (`/permohonan/permohonans`)
     * kalau dibiarkan otomatis.
     */
    protected static ?string $slug = 'permohonan';

    protected static ?string $navigationLabel = 'Permohonan';

    protected static ?string $modelLabel = 'Permohonan';

    protected static ?string $pluralModelLabel = 'Permohonan';

    public static function table(Table $table): Table
    {
        return FormSubmissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermohonan::route('/'),
        ];
    }
}
```

- [ ] **Step 6: Buat halaman `ListPermohonan`**

File: `app/Filament/Petugas/Resources/Permohonan/Pages/ListPermohonan.php`

```php
<?php

namespace App\Filament\Petugas\Resources\Permohonan\Pages;

use App\Filament\Petugas\Resources\Permohonan\PermohonanResource;
use Filament\Resources\Pages\ListRecords;

class ListPermohonan extends ListRecords
{
    protected static string $resource = PermohonanResource::class;
}
```

- [ ] **Step 7: Jalankan test akses untuk memastikan lulus**

Run: `php artisan test --filter=PetugasPanelAccessTest`
Expected: PASS — 5 test lulus.

- [ ] **Step 8: Tulis test Ubah Status & Tolak yang gagal**

File: `tests/Feature/PetugasUbahStatusTest.php`

```php
<?php

namespace Tests\Feature;

use App\Filament\Petugas\Resources\Permohonan\Pages\ListPermohonan;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PetugasUbahStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Livewire::test() memanggil komponen langsung tanpa melalui rute
        // HTTP /petugas/..., jadi Filament tidak tahu panel mana yang aktif
        // kecuali diberi tahu eksplisit — tanpa ini, resource mencoba
        // membangun URL ke panel default (admin) dan gagal.
        Filament::setCurrentPanel('petugas');
    }

    private function permohonan(): FormSubmission
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);

        return $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [],
        ]);
    }

    public function test_petugas_bisa_memproses_permohonan_lewat_ubah_status(): void
    {
        $permohonan = $this->permohonan();
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas);

        Livewire::test(ListPermohonan::class)
            ->callTableAction('ubahStatus', $permohonan, data: [
                'status' => 'diproses',
                'admin_note' => 'Sedang diverifikasi.',
            ]);

        $permohonan->refresh();
        $this->assertSame('diproses', $permohonan->status);
        $this->assertSame('Sedang diverifikasi.', $permohonan->admin_note);
        $this->assertSame($petugas->id, $permohonan->statusLogs()->latest('id')->first()->user_id);
    }

    public function test_petugas_bisa_menolak_permohonan(): void
    {
        $permohonan = $this->permohonan();
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas);

        Livewire::test(ListPermohonan::class)
            ->callTableAction('ubahStatus', $permohonan, data: [
                'status' => 'ditolak',
                'admin_note' => 'Berkas tidak lengkap.',
            ]);

        $this->assertSame('ditolak', $permohonan->fresh()->status);
        $this->assertSame('ditolak', $permohonan->statusLogs()->latest('id')->first()->status);
    }
}
```

- [ ] **Step 9: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=PetugasUbahStatusTest`
Expected: PASS — 2 test lulus. (Tidak perlu implementasi tambahan: aksi `ubahStatus` sudah ada di `FormSubmissionsTable` yang dipakai ulang Step 5.)

- [ ] **Step 10: Jalankan seluruh test agar tidak ada regresi, lalu commit**

```bash
php artisan test
git add -A
git commit -m "$(cat <<'EOF'
feat: panel Filament petugas dengan antrian permohonan, ubah status, dan tolak

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 4: Widget statistik di dashboard petugas

**Files:**
- Create: `app/Filament/Petugas/Widgets/PermohonanStatsWidget.php`
- Test: `tests/Feature/PetugasDashboardStatsTest.php`

**Interfaces:**
- Consumes: `App\Models\FormSubmission` dari fase sebelumnya (tidak berubah).
- Produces: widget `PermohonanStatsWidget`, otomatis muncul di dashboard `/petugas` lewat `discoverWidgets` yang sudah dikonfigurasi Task 3 (tidak perlu mengubah `PetugasPanelProvider`).

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/PetugasDashboardStatsTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_menampilkan_kelima_label_statistik(): void
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);

        $data = [
            ['PTSP-2609-STAT1', 'diajukan'],
            ['PTSP-2609-STAT2', 'diproses'],
            ['PTSP-2609-STAT3', 'diproses'],
            ['PTSP-2609-STAT4', 'selesai'],
            ['PTSP-2609-STAT5', 'ditolak'],
        ];
        foreach ($data as [$resi, $status]) {
            $layanan->submissions()->create([
                'receipt_code' => $resi,
                'applicant_name' => 'Pemohon',
                'applicant_whatsapp' => '6281234567890',
                'applicant_email' => 'pemohon@example.com',
                'status' => $status,
                'data' => [],
            ]);
        }

        // Fixture: 1 diajukan, 2 diproses, 1 selesai, 1 ditolak — total 5.
        // Angka dipilih berbeda-beda supaya tiap assertSee membuktikan
        // hitungan yang benar, bukan cuma label yang tampil.
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas')
            ->assertOk()
            ->assertSeeInOrder(['Total Permohonan', 'Diajukan', 'Diproses', 'Selesai', 'Ditolak'])
            ->assertSeeInOrder(['Total Permohonan', '5'])
            ->assertSeeInOrder(['Diproses', '2']);
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=PetugasDashboardStatsTest`
Expected: FAIL — label statistik belum ada di halaman.

- [ ] **Step 3: Buat widget**

File: `app/Filament/Petugas/Widgets/PermohonanStatsWidget.php`

```php
<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\FormSubmission;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PermohonanStatsWidget extends StatsOverviewWidget
{
    /**
     * Hitungan murni per status, tanpa filter `is_service`: tabel Permohonan
     * di admin (yang tabelnya dipakai ulang panel ini, lihat spec §2.4) juga
     * tidak memfilternya, sehingga angka di widget selalu konsisten dengan
     * jumlah baris yang terlihat di antrian.
     */
    protected function getStats(): array
    {
        return [
            Stat::make('Total Permohonan', FormSubmission::count()),
            Stat::make('Diajukan', FormSubmission::where('status', 'diajukan')->count()),
            Stat::make('Diproses', FormSubmission::where('status', 'diproses')->count()),
            Stat::make('Selesai', FormSubmission::where('status', 'selesai')->count()),
            Stat::make('Ditolak', FormSubmission::where('status', 'ditolak')->count()),
        ];
    }
}
```

- [ ] **Step 4: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=PetugasDashboardStatsTest`
Expected: PASS.

- [ ] **Step 5: Jalankan seluruh test dan commit**

```bash
php artisan test
git add -A
git commit -m "$(cat <<'EOF'
feat: widget statistik permohonan di dashboard petugas

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 5: Resource "Petugas" di panel admin (kelola akun staf)

**Files:**
- Create: `app/Filament/Resources/Petugas/PetugasResource.php`
- Create: `app/Filament/Resources/Petugas/Schemas/PetugasForm.php`
- Create: `app/Filament/Resources/Petugas/Tables/PetugasTable.php`
- Create: `app/Filament/Resources/Petugas/Pages/ListPetugas.php`
- Create: `app/Filament/Resources/Petugas/Pages/CreatePetugas.php`
- Create: `app/Filament/Resources/Petugas/Pages/EditPetugas.php`
- Test: `tests/Feature/PetugasResourceTest.php`

**Interfaces:**
- Consumes: `User::ROLE_PETUGAS` dari Task 1. Resource ini hanya terdaftar di panel `admin` (lewat `discoverResources` yang sudah ada), jadi otomatis hanya bisa diakses `role = administrator`.
- Produces: resource `App\Filament\Resources\Petugas\PetugasResource` — daftar/tambah/ubah/hapus akun `role = petugas`, tidak pernah menampilkan akun administrator.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/PetugasResourceTest.php`

```php
<?php

namespace Tests\Feature;

use App\Filament\Resources\Petugas\Pages\CreatePetugas;
use App\Filament\Resources\Petugas\Pages\EditPetugas;
use App\Filament\Resources\Petugas\Pages\ListPetugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PetugasResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]));
    }

    public function test_admin_bisa_membuat_akun_petugas(): void
    {
        Livewire::test(CreatePetugas::class)
            ->fillForm([
                'name' => 'Siti Rahma',
                'email' => 'siti@sekolah.test',
                'password' => 'rahasia123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $petugas = User::where('email', 'siti@sekolah.test')->sole();
        $this->assertSame(User::ROLE_PETUGAS, $petugas->role);
        $this->assertTrue(Hash::check('rahasia123', $petugas->password));
    }

    public function test_daftar_petugas_tidak_menampilkan_akun_administrator(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS, 'name' => 'Petugas Satu']);
        $adminLain = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR, 'name' => 'Admin Lain']);

        Livewire::test(ListPetugas::class)
            ->assertCanSeeTableRecords([$petugas])
            ->assertCanNotSeeTableRecords([$adminLain]);
    }

    public function test_mengedit_tanpa_isi_password_tidak_mengubah_password_lama(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);
        $passwordLama = $petugas->password;

        Livewire::test(EditPetugas::class, ['record' => $petugas->getRouteKey()])
            ->fillForm(['name' => 'Nama Baru', 'email' => $petugas->email, 'password' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($passwordLama, $petugas->fresh()->password);
        $this->assertSame('Nama Baru', $petugas->fresh()->name);
    }

    public function test_admin_bisa_menghapus_akun_petugas(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        Livewire::test(EditPetugas::class, ['record' => $petugas->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($petugas);
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=PetugasResourceTest`
Expected: FAIL — `Class "App\Filament\Resources\Petugas\Pages\CreatePetugas" not found`.

- [ ] **Step 3: Buat form**

File: `app/Filament/Resources/Petugas/Schemas/PetugasForm.php`

```php
<?php

namespace App\Filament\Resources\Petugas\Schemas;

use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class PetugasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('role')->default(User::ROLE_PETUGAS),
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->helperText('Kosongkan saat mengedit bila tidak ingin mengubah password.'),
            ]);
    }
}
```

- [ ] **Step 4: Buat tabel**

File: `app/Filament/Resources/Petugas/Tables/PetugasTable.php`

```php
<?php

namespace App\Filament\Resources\Petugas\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PetugasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y')->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
```

- [ ] **Step 5: Buat resource**

File: `app/Filament/Resources/Petugas/PetugasResource.php`

```php
<?php

namespace App\Filament\Resources\Petugas;

use App\Filament\Resources\Petugas\Pages\CreatePetugas;
use App\Filament\Resources\Petugas\Pages\EditPetugas;
use App\Filament\Resources\Petugas\Pages\ListPetugas;
use App\Filament\Resources\Petugas\Schemas\PetugasForm;
use App\Filament\Resources\Petugas\Tables\PetugasTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PetugasResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'PTSP';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Petugas';

    protected static ?string $modelLabel = 'Petugas';

    protected static ?string $pluralModelLabel = 'Petugas';

    /** Resource ini HANYA mengelola akun petugas — akun administrator tidak pernah muncul di sini. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', User::ROLE_PETUGAS);
    }

    public static function form(Schema $schema): Schema
    {
        return PetugasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PetugasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPetugas::route('/'),
            'create' => CreatePetugas::route('/create'),
            'edit' => EditPetugas::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 6: Buat tiga halaman**

File: `app/Filament/Resources/Petugas/Pages/ListPetugas.php`

```php
<?php

namespace App\Filament\Resources\Petugas\Pages;

use App\Filament\Resources\Petugas\PetugasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPetugas extends ListRecords
{
    protected static string $resource = PetugasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
```

File: `app/Filament/Resources/Petugas/Pages/CreatePetugas.php`

```php
<?php

namespace App\Filament\Resources\Petugas\Pages;

use App\Filament\Resources\Petugas\PetugasResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePetugas extends CreateRecord
{
    protected static string $resource = PetugasResource::class;
}
```

File: `app/Filament/Resources/Petugas/Pages/EditPetugas.php`

```php
<?php

namespace App\Filament\Resources\Petugas\Pages;

use App\Filament\Resources\Petugas\PetugasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPetugas extends EditRecord
{
    protected static string $resource = PetugasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
```

- [ ] **Step 7: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=PetugasResourceTest`
Expected: PASS — 4 test lulus.

- [ ] **Step 8: Jalankan seluruh test dan commit**

```bash
php artisan test
git add -A
git commit -m "$(cat <<'EOF'
feat: resource Petugas di panel admin untuk kelola akun staf

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 6: Tautan masuk petugas di header publik

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Test: `tests/Feature/PetugasHeaderLinkTest.php`

**Interfaces:**
- Consumes: `auth()->user()->role`, `User::ROLE_PETUGAS` dari Task 1.
- Produces: satu tautan baru di header publik, tidak mengubah struktur `$navMenus`/`$settings` yang sudah ada.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/PetugasHeaderLinkTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasHeaderLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_melihat_tautan_masuk_petugas(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Masuk Petugas');
    }

    public function test_petugas_yang_sudah_login_melihat_tautan_dashboard_dengan_nama(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS, 'name' => 'Rina']);

        $this->actingAs($petugas)
            ->get('/')
            ->assertOk()
            ->assertSee('Dashboard Petugas — Rina');
    }

    public function test_administrator_yang_login_tetap_melihat_tautan_masuk_petugas(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee('Masuk Petugas');
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=PetugasHeaderLinkTest`
Expected: FAIL — teks "Masuk Petugas" belum ada di halaman.

- [ ] **Step 3: Tambahkan tautan di header**

File: `resources/views/layouts/app.blade.php` — sisipkan blok berikut di antara `</nav>` (penutup navigasi desktop) dan `<details class="relative md:hidden">` (toggle mobile):

```blade
            {{-- Tautan staf: masuk atau ke dashboard petugas. Selalu tampil
                 (desktop & mobile) — bukan bagian dari menu konten seperti
                 $navMenus, karena itu ditempatkan terpisah dari <nav>. --}}
            @php $petugasUser = auth()->user(); @endphp
            <a href="{{ url('/petugas') }}"
               class="flex items-center gap-1.5 rounded-md px-2.5 py-2 text-sm font-medium text-[var(--header-fg)] hover:bg-[var(--header-hover)]"
               aria-label="{{ $petugasUser && $petugasUser->role === \App\Models\User::ROLE_PETUGAS ? 'Dashboard Petugas' : 'Masuk Petugas' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                <span class="hidden sm:inline">
                    @if ($petugasUser && $petugasUser->role === \App\Models\User::ROLE_PETUGAS)
                        Dashboard Petugas — {{ $petugasUser->name }}
                    @else
                        Masuk Petugas
                    @endif
                </span>
            </a>
```

- [ ] **Step 4: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=PetugasHeaderLinkTest`
Expected: PASS — 3 test lulus.

- [ ] **Step 5: Bangun aset dan verifikasi visual di browser lokal**

```bash
npm run build
php artisan serve --port=8123 &
sleep 3
curl -s http://127.0.0.1:8123/ | grep -c 'Masuk Petugas'
kill %1
```
Expected: `1`

- [ ] **Step 6: Jalankan seluruh test dan commit**

```bash
php artisan test
git add -A
git commit -m "$(cat <<'EOF'
feat: tautan masuk petugas di header situs publik

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 7: Regresi penuh, dokumentasi, dan verifikasi manual

**Files:**
- Modify: `README.md`

**Interfaces:**
- Consumes: seluruh task sebelumnya.
- Produces: dokumentasi kredensial petugas demo, tidak ada kode baru.

- [ ] **Step 1: Jalankan seluruh test suite**

Run: `php artisan test`
Expected: PASS — seluruh test lama maupun baru lulus, tanpa regresi.

- [ ] **Step 2: Perbarui README**

File: `README.md` — tambahkan bagian baru setelah bagian "Admin Panel":

```markdown
## Panel Petugas

- URL: `/petugas` (mis. `http://ptsp-online.test/petugas`)
- Tautan "Masuk Petugas" tersedia di pojok kanan atas semua halaman publik.

### Kredensial Petugas Demo

| Field | Nilai |
|---|---|
| Email | `petugas@sekolah.test` |
| Password | `password` |

> ⚠️ Akun ini untuk pengujian lokal. Akun petugas sungguhan dibuat
> Administrator lewat menu **Petugas** di panel admin (`/admin`), bukan
> lewat akun demo ini.
```

- [ ] **Step 3: Verifikasi manual kedua panel berjalan**

```bash
cd /Users/user/Herd/ptsp-online
php artisan serve --port=8123 &
sleep 3
echo "admin: $(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8123/admin/login)"
echo "petugas: $(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8123/petugas/login)"
kill %1
```
Expected: kedua baris menampilkan `200`.

- [ ] **Step 4: Commit dokumentasi**

```bash
git add -A
git commit -m "$(cat <<'EOF'
docs: dokumentasikan panel dan kredensial demo petugas di README

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```
