# PTSP Online Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun aplikasi PTSP Online — katalog layanan berbasis kartu, formulir pengajuan dinamis yang dikelola operator lewat CMS, kode resi, dan pelacakan status publik.

**Architecture:** Fork CMS-web menjadi instance Laravel mandiri. Tabel `forms`/`form_fields`/`form_submissions` yang sudah ada diperluas dengan kolom PTSP (label di panel diganti jadi "Layanan"/"Permohonan"), ditambah tabel `work_units` dan `submission_status_logs`. Halaman publik berupa Blade server-rendered; panel operator memakai Filament v5.

**Tech Stack:** PHP 8.4, Laravel 13, Filament v5, Blade, Tailwind CSS v4, MySQL (produksi) / SQLite in-memory (test), PHPUnit 12.

**Spec:** `docs/superpowers/specs/2026-09-03-ptsp-online-design.md`

## Global Constraints

Berlaku untuk SEMUA task di bawah:

- **Direktori kerja**: `/Users/user/Herd/ptsp-online` (dibuat di Task 1). Semua path relatif terhadap direktori ini.
- **Bahasa**: seluruh teks yang dilihat pengguna, label Filament, dan komentar kode ditulis dalam Bahasa Indonesia.
- **Gaya test**: PHPUnit berbasis kelas (BUKAN Pest), `namespace Tests\Feature;` atau `Tests\Unit`, `use Illuminate\Foundation\Testing\RefreshDatabase;`, `extends Tests\TestCase`. Database test: SQLite `:memory:` (sudah diatur di `phpunit.xml`). Data provider: PHPUnit 12 tidak lagi membaca anotasi docblock `@dataProvider` — pakai atribut `#[\PHPUnit\Framework\Attributes\DataProvider('namaMethod')]`.
- **Tidak ada factory** untuk `Form`/`FormField`/`FormSubmission`. Buat data test dengan `Model::create([...])` — ikuti gaya `tests/Feature/FormPublicPageTest.php`.
- **Migrasi wajib kompatibel SQLite**: hanya menambah kolom/tabel. JANGAN mengubah tipe kolom atau memakai `enum` MySQL.
- **Warna**: seluruh aksen memakai `var(--color-primary)` (kelas Tailwind `bg-primary`, `text-primary`, `border-primary`). JANGAN pernah menulis nilai hijau langsung di Blade.
- **Status permohonan** — persis empat nilai: `diajukan`, `diproses`, `selesai`, `ditolak`.
- **Alfabet kode resi** — `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (tanpa `0`, `O`, `1`, `I`).
- **Unggahan berkas** — disk `local`, mimes `jpg,jpeg,png,pdf`, maksimum `5120` KB.
- **Verifikasi manual**: URL `.test` TIDAK bisa dijangkau dari shell. Pakai `php artisan serve --port=8123` lalu `curl http://127.0.0.1:8123/...`.
- **Commit** di akhir setiap task, dengan pesan Bahasa Indonesia berawalan `feat:`/`fix:`/`chore:`/`test:`.

---

### Task 1: Bootstrap instance PTSP Online

Fork CMS-web menjadi proyek mandiri yang bisa dijalankan, dengan seluruh test bawaannya hijau. Tidak ada fitur PTSP di task ini — hanya fondasi.

**Files:**
- Create: seluruh isi `/Users/user/Herd/ptsp-online/` (hasil salin dari `CMS-web/`)
- Modify: `.env`, `.env.example`, `README.md`
- Modify: `/Users/user/Herd` (repo luar) — berhenti melacak folder ini

**Interfaces:**
- Consumes: —
- Produces: proyek Laravel yang bisa dijalankan di `/Users/user/Herd/ptsp-online`, dengan `php artisan test` hijau.

- [ ] **Step 1: Rename folder proyek (menghilangkan spasi) dan lepaskan dari repo luar**

Folder saat ini bernama `PTSP Online` (berspasi) sehingga Herd tidak bisa memetakannya ke domain yang valid. Folder ini sudah berisi `docs/` yang harus ikut terbawa.

```bash
cd /Users/user/Herd
git rm -r --cached "PTSP Online" --quiet
mv "PTSP Online" ptsp-online
git commit -q -m "chore: pindahkan PTSP Online jadi proyek mandiri ptsp-online"
```

- [ ] **Step 2: Salin codebase CMS-web**

`rsync` tanpa `--delete` sehingga `docs/` yang sudah ada tidak terhapus. `vendor/` dan `node_modules/` sengaja tidak disalin — akan diinstal ulang agar bersih.

```bash
cd /Users/user/Herd
rsync -a \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='.env' \
  --exclude='.phpunit.result.cache' \
  --exclude='storage/framework/cache/data' \
  --exclude='storage/framework/sessions' \
  --exclude='storage/framework/views' \
  --exclude='storage/logs' \
  --exclude='public/storage' \
  --exclude='.DS_Store' \
  CMS-web/ ptsp-online/
```

- [ ] **Step 3: Install dependensi**

```bash
cd /Users/user/Herd/ptsp-online
composer install
npm install
```

- [ ] **Step 4: Buat `.env` dan database**

Kredensial MySQL disalin dari CMS-web supaya sama dengan yang sudah terbukti jalan di mesin ini.

```bash
cd /Users/user/Herd/ptsp-online
cp .env.example .env
DBU=$(grep -E '^DB_USERNAME=' ../CMS-web/.env | cut -d= -f2-)
DBP=$(grep -E '^DB_PASSWORD=' ../CMS-web/.env | cut -d= -f2-)
php -r '
$f=".env"; $s=file_get_contents($f);
$s=preg_replace("/^APP_NAME=.*$/m", "APP_NAME=\"PTSP Online\"", $s);
$s=preg_replace("/^APP_URL=.*$/m", "APP_URL=http://ptsp-online.test", $s);
$s=preg_replace("/^DB_CONNECTION=.*$/m", "DB_CONNECTION=mysql", $s);
$s=preg_replace("/^DB_DATABASE=.*$/m", "DB_DATABASE=ptsp_online", $s);
file_put_contents($f,$s);
'
php artisan key:generate
mysql -u "$DBU" ${DBP:+-p"$DBP"} -e "CREATE DATABASE IF NOT EXISTS ptsp_online CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Terapkan perubahan yang sama (kecuali `APP_KEY`) ke `.env.example` supaya deploy berikutnya konsisten.

- [ ] **Step 5: Migrasi, seed, dan build aset**

```bash
cd /Users/user/Herd/ptsp-online
php artisan migrate --seed
npm run build
```

- [ ] **Step 6: Jalankan test bawaan untuk memastikan baseline hijau**

Run: `cd /Users/user/Herd/ptsp-online && php artisan test`
Expected: PASS — seluruh test yang diwarisi dari CMS-web lulus. Jika ada yang merah, PERBAIKI DULU sebelum lanjut; task berikutnya berasumsi baseline hijau.

- [ ] **Step 7: Verifikasi aplikasi benar-benar melayani permintaan**

```bash
cd /Users/user/Herd/ptsp-online
php artisan serve --port=8123 &
sleep 3
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8123/
kill %1
```
Expected: `200`

- [ ] **Step 8: Perbarui README**

Ganti judul menjadi `# PTSP Online`, ganti deskripsi menjadi satu paragraf tentang layanan PTSP, dan ganti seluruh `cms-web.test` menjadi `ptsp-online.test` serta `cms_web` menjadi `ptsp_online`. Tambahkan baris yang menunjuk ke spec: `Acuan desain ada di docs/superpowers/specs/2026-09-03-ptsp-online-design.md`.

- [ ] **Step 9: Inisialisasi git repo sendiri dan commit**

```bash
cd /Users/user/Herd/ptsp-online
git init -q
git add -A
git commit -q -m "chore: fork CMS-web jadi instance PTSP Online"
git log --oneline -1
```

---

### Task 2: Skema & model definisi layanan

Menambah kolom PTSP ke `forms`, tabel master `work_units`, dan kolom `help_text` + tipe field baru ke `form_fields`.

**Files:**
- Create: `database/migrations/2026_09_03_100000_create_work_units_table.php`
- Create: `database/migrations/2026_09_03_100001_add_service_columns_to_forms_table.php`
- Create: `database/migrations/2026_09_03_100002_add_help_text_to_form_fields_table.php`
- Create: `app/Models/WorkUnit.php`
- Modify: `app/Models/Form.php`
- Test: `tests/Feature/ServiceModelTest.php`

**Interfaces:**
- Consumes: proyek dari Task 1.
- Produces:
  - `App\Models\WorkUnit` dengan `$fillable = ['name','slug','sort_order']` dan relasi `services(): HasMany`.
  - `App\Models\Form` dengan relasi `workUnit(): BelongsTo`, tiga scope (`scopeServices()`, `scopePublished()`, `scopeOrdered()`), dan `isPublishedService(): bool`.
  - Tipe field yang valid bertambah: `date`, `number`.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/ServiceModelTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_services_published_menyaring_draft_dan_form_biasa(): void
    {
        Form::create(['title' => 'Layanan Terbit', 'slug' => 'terbit', 'status' => 'published', 'is_service' => true]);
        Form::create(['title' => 'Layanan Draft', 'slug' => 'draft', 'status' => 'draft', 'is_service' => true]);
        Form::create(['title' => 'Form Kontak', 'slug' => 'kontak', 'status' => 'published', 'is_service' => false]);

        $hasil = Form::query()->services()->published()->get();

        $this->assertCount(1, $hasil);
        $this->assertSame('Layanan Terbit', $hasil->first()->title);
    }

    public function test_scope_ordered_mengurutkan_sort_order_lalu_id(): void
    {
        $b = Form::create(['title' => 'B', 'slug' => 'b', 'status' => 'published', 'sort_order' => 2]);
        $a = Form::create(['title' => 'A', 'slug' => 'a', 'status' => 'published', 'sort_order' => 1]);
        $c = Form::create(['title' => 'C', 'slug' => 'c', 'status' => 'published', 'sort_order' => 1]);

        $this->assertSame(
            [$a->id, $c->id, $b->id],
            Form::query()->ordered()->pluck('id')->all(),
        );
    }

    public function test_layanan_terhubung_ke_satuan_kerja(): void
    {
        $unit = WorkUnit::create(['name' => 'Tata Usaha (TU)', 'slug' => 'tata-usaha', 'sort_order' => 1]);
        $layanan = Form::create([
            'title' => 'Pengambilan Ijazah',
            'slug' => 'pengambilan-ijazah',
            'status' => 'published',
            'work_unit_id' => $unit->id,
            'organizer' => 'PTSP',
            'duration_text' => '30 Menit',
            'fee_text' => 'Gratis',
        ]);

        $this->assertSame('Tata Usaha (TU)', $layanan->workUnit->name);
        $this->assertTrue($unit->services->contains($layanan));
    }

    public function test_menghapus_satuan_kerja_tidak_menghapus_layanannya(): void
    {
        $unit = WorkUnit::create(['name' => 'Kesiswaan', 'slug' => 'kesiswaan']);
        $layanan = Form::create(['title' => 'Mutasi', 'slug' => 'mutasi', 'status' => 'published', 'work_unit_id' => $unit->id]);

        $unit->delete();

        $this->assertNull($layanan->fresh()->work_unit_id);
        $this->assertNotNull($layanan->fresh());
    }

    public function test_is_published_service_hanya_benar_untuk_layanan_terbit(): void
    {
        $terbit = Form::create(['title' => 'A', 'slug' => 'a', 'status' => 'published', 'is_service' => true]);
        $draft = Form::create(['title' => 'B', 'slug' => 'b', 'status' => 'draft', 'is_service' => true]);
        $biasa = Form::create(['title' => 'C', 'slug' => 'c', 'status' => 'published', 'is_service' => false]);

        $this->assertTrue($terbit->isPublishedService());
        $this->assertFalse($draft->isPublishedService());
        $this->assertFalse($biasa->isPublishedService());
    }

    public function test_field_menyimpan_help_text(): void
    {
        $layanan = Form::create(['title' => 'X', 'slug' => 'x', 'status' => 'published']);
        $field = $layanan->fields()->create([
            'label' => 'Tanggal Lahir',
            'type' => 'date',
            'help_text' => 'Sesuai akta kelahiran.',
            'sort_order' => 1,
        ]);

        $this->assertSame('Sesuai akta kelahiran.', $field->fresh()->help_text);
        $this->assertSame('date', $field->fresh()->type);
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=ServiceModelTest`
Expected: FAIL — `Class "App\Models\WorkUnit" not found`.

- [ ] **Step 3: Buat migrasi `work_units`**

File: `database/migrations/2026_09_03_100000_create_work_units_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_units');
    }
};
```

- [ ] **Step 4: Buat migrasi kolom layanan di `forms`**

File: `database/migrations/2026_09_03_100001_add_service_columns_to_forms_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            // Pembeda layanan PTSP dari form biasa (mis. form kontak).
            $table->boolean('is_service')->default(true);
            // Bagian kiri badge kartu: "PTSP / Tata Usaha (TU)".
            $table->string('organizer', 100)->default('PTSP');
            $table->foreignId('work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            // Teks bebas karena satuannya bercampur: menit, jam, hari, dan rentang.
            $table->string('duration_text', 50)->nullable();
            $table->string('fee_text', 50)->default('Gratis');
            $table->unsignedInteger('sort_order')->default(0);
            $table->longText('requirements')->nullable();
            $table->longText('legal_basis')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_unit_id');
            $table->dropColumn([
                'is_service', 'organizer', 'duration_text',
                'fee_text', 'sort_order', 'requirements', 'legal_basis',
            ]);
        });
    }
};
```

- [ ] **Step 5: Buat migrasi `help_text` di `form_fields`**

File: `database/migrations/2026_09_03_100002_add_help_text_to_form_fields_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            // Keterangan kecil di bawah field pada formulir publik.
            $table->string('help_text', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('help_text');
        });
    }
};
```

- [ ] **Step 6: Buat model `WorkUnit`**

File: `app/Models/WorkUnit.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkUnit extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function services(): HasMany
    {
        return $this->hasMany(Form::class);
    }
}
```

- [ ] **Step 7: Perbarui model `Form`**

Di `app/Models/Form.php`, tambahkan kolom baru ke `$fillable`, tambahkan `casts()`, relasi, dan tiga scope. Tambahkan juga import `BelongsTo` dan `Builder`.

```php
    protected $fillable = [
        'title',
        'slug',
        'description',
        'success_message',
        'status',
        'is_service',
        'organizer',
        'work_unit_id',
        'duration_text',
        'fee_text',
        'sort_order',
        'requirements',
        'legal_basis',
    ];

    protected function casts(): array
    {
        return [
            'is_service' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function scopeServices(Builder $query): Builder
    {
        return $query->where('is_service', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** Urutan katalog: sort_order menaik, id sebagai pemecah seri. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Penjaga akses publik. Diletakkan di model, bukan di controller, karena
     * tiga controller berbeda (katalog, rincian, pengajuan) memakai aturan yang
     * sama persis.
     */
    public function isPublishedService(): bool
    {
        return $this->is_service && $this->status === 'published';
    }
```

- [ ] **Step 8: Tambahkan tipe `date` dan `number` ke daftar tipe field**

Di `app/Models/FormField.php`, tambahkan `'help_text'` ke `$fillable` dan tambahkan konstanta yang menjadi satu-satunya sumber kebenaran daftar tipe:

```php
    /** Tipe field yang didukung: kunci = nilai kolom `type`, nilai = label operator. */
    public const TYPES = [
        'text' => 'Teks singkat',
        'textarea' => 'Teks panjang',
        'date' => 'Tanggal',
        'number' => 'Angka',
        'select' => 'Pilihan (dropdown)',
        'checkbox' => 'Checkbox (pilih banyak)',
        'file' => 'Upload berkas',
    ];
```

- [ ] **Step 9: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=ServiceModelTest`
Expected: PASS — 6 test lulus.

- [ ] **Step 10: Jalankan seluruh test agar tidak ada regresi**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 11: Terapkan migrasi ke database pengembangan dan commit**

```bash
php artisan migrate
git add -A
git commit -q -m "feat: skema layanan PTSP (satuan kerja, kolom katalog, help_text)"
```

---

### Task 3: Skema & model permohonan

Menambah identitas pemohon, kode resi, dan status ke `form_submissions`, plus tabel riwayat status.

**Files:**
- Create: `database/migrations/2026_09_03_110000_add_application_columns_to_form_submissions_table.php`
- Create: `database/migrations/2026_09_03_110001_create_submission_status_logs_table.php`
- Create: `app/Models/SubmissionStatusLog.php`
- Modify: `app/Models/FormSubmission.php`
- Test: `tests/Feature/ApplicationModelTest.php`

**Interfaces:**
- Consumes: `App\Models\Form` dari Task 2.
- Produces:
  - `App\Models\FormSubmission` dengan konstanta `FormSubmission::STATUSES` (array `kunci => label`), relasi `statusLogs(): HasMany`, dan method `recordStatus(string $status, ?string $note = null, ?int $userId = null): SubmissionStatusLog`.
  - `App\Models\SubmissionStatusLog` dengan `$fillable = ['form_submission_id','status','note','user_id']`.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/ApplicationModelTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationModelTest extends TestCase
{
    use RefreshDatabase;

    private function layanan(): Form
    {
        return Form::create(['title' => 'Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);
    }

    public function test_permohonan_menyimpan_identitas_dan_default_status_diajukan(): void
    {
        $permohonan = $this->layanan()->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [],
        ]);

        $this->assertSame('diajukan', $permohonan->fresh()->status);
        $this->assertSame('Budi Santoso', $permohonan->fresh()->applicant_name);
    }

    public function test_record_status_menulis_baris_riwayat(): void
    {
        $operator = User::factory()->create();
        $permohonan = $this->layanan()->submissions()->create([
            'receipt_code' => 'PTSP-2609-B8L4RY',
            'applicant_name' => 'Siti',
            'applicant_whatsapp' => '6281200000000',
            'applicant_email' => 'siti@example.com',
            'data' => [],
        ]);

        $permohonan->recordStatus('diproses', 'Berkas sedang diverifikasi.', $operator->id);

        $log = $permohonan->statusLogs()->latest('id')->first();
        $this->assertSame('diproses', $log->status);
        $this->assertSame('Berkas sedang diverifikasi.', $log->note);
        $this->assertSame($operator->id, $log->user_id);
    }

    public function test_menghapus_permohonan_ikut_menghapus_riwayatnya(): void
    {
        $permohonan = $this->layanan()->submissions()->create([
            'receipt_code' => 'PTSP-2609-C9M5SZ',
            'applicant_name' => 'Andi',
            'applicant_whatsapp' => '6281300000000',
            'applicant_email' => 'andi@example.com',
            'data' => [],
        ]);
        $permohonan->recordStatus('diajukan');

        $permohonan->delete();

        $this->assertDatabaseCount('submission_status_logs', 0);
    }

    public function test_daftar_status_berisi_empat_nilai(): void
    {
        $this->assertSame(
            ['diajukan', 'diproses', 'selesai', 'ditolak'],
            array_keys(FormSubmission::STATUSES),
        );
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=ApplicationModelTest`
Expected: FAIL — kolom `receipt_code` tidak ada.

- [ ] **Step 3: Buat migrasi kolom permohonan**

File: `database/migrations/2026_09_03_110000_add_application_columns_to_form_submissions_table.php`

Kolom identitas dibuat nullable di tingkat basis data karena tabel ini juga menampung jawaban form biasa yang tidak punya blok identitas; kewajiban ditegakkan di tingkat validasi (Task 8).

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('receipt_code', 20)->nullable()->unique();
            $table->string('applicant_name', 150)->nullable();
            $table->string('applicant_whatsapp', 20)->nullable()->index();
            $table->string('applicant_email', 150)->nullable();
            $table->string('status', 20)->default('diajukan')->index();
            $table->text('admin_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_code', 'applicant_name', 'applicant_whatsapp',
                'applicant_email', 'status', 'admin_note',
            ]);
        });
    }
};
```

- [ ] **Step 4: Buat migrasi `submission_status_logs`**

File: `database/migrations/2026_09_03_110001_create_submission_status_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_submission_id')->constrained('form_submissions')->cascadeOnDelete();
            $table->string('status', 20);
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_status_logs');
    }
};
```

- [ ] **Step 5: Buat model `SubmissionStatusLog`**

File: `app/Models/SubmissionStatusLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionStatusLog extends Model
{
    protected $fillable = ['form_submission_id', 'status', 'note', 'user_id'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 6: Perbarui model `FormSubmission`**

Tambahkan konstanta, `$fillable`, relasi, dan helper. Import `HasMany`.

```php
    /** Empat status permohonan. Kunci = nilai kolom, nilai = label operator/publik. */
    public const STATUSES = [
        'diajukan' => 'Diajukan',
        'diproses' => 'Diproses',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
    ];

    protected $fillable = [
        'form_id',
        'data',
        'receipt_code',
        'applicant_name',
        'applicant_whatsapp',
        'applicant_email',
        'status',
        'admin_note',
    ];

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SubmissionStatusLog::class)->orderBy('id');
    }

    /** Catat satu langkah riwayat status. Tidak mengubah kolom `status` di permohonan. */
    public function recordStatus(string $status, ?string $note = null, ?int $userId = null): SubmissionStatusLog
    {
        return $this->statusLogs()->create([
            'status' => $status,
            'note' => $note,
            'user_id' => $userId,
        ]);
    }
```

- [ ] **Step 7: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=ApplicationModelTest`
Expected: PASS — 4 test lulus.

- [ ] **Step 8: Jalankan seluruh test, migrasi, lalu commit**

```bash
php artisan test
php artisan migrate
git add -A
git commit -q -m "feat: skema permohonan (identitas pemohon, kode resi, status, riwayat)"
```

---

### Task 4: Kelas pendukung — kode resi & normalisasi nomor WhatsApp

Dua kelas murni tanpa ketergantungan HTTP, diuji lewat unit test.

**Files:**
- Create: `app/Support/ReceiptCode.php`
- Create: `app/Support/WhatsappNumber.php`
- Test: `tests/Unit/ReceiptCodeTest.php`
- Test: `tests/Unit/WhatsappNumberTest.php`

**Interfaces:**
- Consumes: `App\Models\FormSubmission` dari Task 3 (untuk cek keunikan).
- Produces:
  - `App\Support\ReceiptCode::generate(?\DateTimeInterface $at = null): string` — satu kode acak, TIDAK cek database.
  - `App\Support\ReceiptCode::generateUnique(?\DateTimeInterface $at = null): string` — mengulang sampai kode belum dipakai.
  - `App\Support\WhatsappNumber::normalize(string $raw): string`
  - `App\Support\WhatsappNumber::lastFour(string $raw): string`

- [ ] **Step 1: Tulis test kode resi yang gagal**

File: `tests/Unit/ReceiptCodeTest.php`

```php
<?php

namespace Tests\Unit;

use App\Support\ReceiptCode;
use PHPUnit\Framework\TestCase;

class ReceiptCodeTest extends TestCase
{
    public function test_format_kode_sesuai_pola(): void
    {
        $kode = ReceiptCode::generate(new \DateTimeImmutable('2026-09-03'));

        $this->assertMatchesRegularExpression('/^PTSP-2609-[A-Z2-9]{6}$/', $kode);
    }

    public function test_kode_tidak_pernah_memuat_karakter_ambigu(): void
    {
        // 200 percobaan cukup untuk menangkap kebocoran satu karakter dari
        // alfabet 32 huruf; peluang lolos bila alfabetnya salah sangat kecil.
        for ($i = 0; $i < 200; $i++) {
            $acak = substr(ReceiptCode::generate(), 10);

            $this->assertSame(
                0,
                preg_match('/[0O1I]/', $acak),
                "Kode {$acak} memuat karakter ambigu.",
            );
        }
    }

    public function test_dua_kode_berturut_turut_berbeda(): void
    {
        $this->assertNotSame(ReceiptCode::generate(), ReceiptCode::generate());
    }
}
```

- [ ] **Step 2: Tulis test normalisasi nomor yang gagal**

File: `tests/Unit/WhatsappNumberTest.php`

```php
<?php

namespace Tests\Unit;

use App\Support\WhatsappNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WhatsappNumberTest extends TestCase
{
    public static function nomorProvider(): array
    {
        return [
            'awalan nol' => ['081234567890'],
            'dengan tanda hubung' => ['0812-3456-7890'],
            'dengan kode negara dan spasi' => ['+62 812 3456 7890'],
            'dengan tanda kurung' => ['(0812) 3456 7890'],
            'sudah ternormalisasi' => ['6281234567890'],
        ];
    }

    #[DataProvider('nomorProvider')]
    public function test_semua_bentuk_penulisan_menghasilkan_nomor_yang_sama(string $masukan): void
    {
        $this->assertSame('6281234567890', WhatsappNumber::normalize($masukan));
    }

    public function test_empat_digit_terakhir_diambil_setelah_normalisasi(): void
    {
        $this->assertSame('7890', WhatsappNumber::lastFour('0812-3456-7890'));
    }
}
```

- [ ] **Step 3: Jalankan kedua test untuk memastikan gagal**

Run: `php artisan test --filter='ReceiptCodeTest|WhatsappNumberTest'`
Expected: FAIL — `Class "App\Support\ReceiptCode" not found`.

- [ ] **Step 4: Implementasikan `ReceiptCode`**

File: `app/Support/ReceiptCode.php`

```php
<?php

namespace App\Support;

use App\Models\FormSubmission;

class ReceiptCode
{
    /**
     * Alfabet sengaja membuang 0, O, 1, dan I: kode resi sering dibaca ulang
     * dari kertas atau disebutkan lewat telepon, dan keempat karakter itu
     * paling sering tertukar.
     */
    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const PANJANG_ACAK = 6;

    public static function generate(?\DateTimeInterface $at = null): string
    {
        $at ??= new \DateTimeImmutable();
        $acak = '';
        $batas = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < self::PANJANG_ACAK; $i++) {
            $acak .= self::ALPHABET[random_int(0, $batas)];
        }

        return sprintf('PTSP-%s-%s', $at->format('ym'), $acak);
    }

    /** Ulangi pembangkitan sampai mendapat kode yang belum dipakai. */
    public static function generateUnique(?\DateTimeInterface $at = null): string
    {
        do {
            $kode = self::generate($at);
        } while (FormSubmission::where('receipt_code', $kode)->exists());

        return $kode;
    }
}
```

- [ ] **Step 5: Implementasikan `WhatsappNumber`**

File: `app/Support/WhatsappNumber.php`

```php
<?php

namespace App\Support;

class WhatsappNumber
{
    /**
     * Samakan semua gaya penulisan nomor Indonesia menjadi bentuk 62xxxxxxxxxx.
     * Bentuk seragam ini yang membuat pencocokan 4 digit terakhir saat
     * pelacakan bisa diandalkan.
     */
    public static function normalize(string $raw): string
    {
        $digit = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digit === '') {
            return '';
        }

        if (str_starts_with($digit, '62')) {
            return $digit;
        }

        if (str_starts_with($digit, '0')) {
            return '62'.substr($digit, 1);
        }

        return '62'.$digit;
    }

    public static function lastFour(string $raw): string
    {
        return substr(self::normalize($raw), -4);
    }
}
```

- [ ] **Step 6: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter='ReceiptCodeTest|WhatsappNumberTest'`
Expected: PASS — 9 test lulus (3 kode resi + 6 nomor).

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -q -m "feat: pembangkit kode resi dan normalisasi nomor WhatsApp"
```

---

### Task 5: Katalog layanan publik

Halaman `/layanan` berisi grid kartu, chip filter satuan kerja, dan pencarian judul.

**Files:**
- Create: `app/Http/Controllers/ServiceController.php`
- Create: `resources/views/layanan/index.blade.php`
- Create: `resources/views/partials/service-card.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ServiceCatalogTest.php`

**Interfaces:**
- Consumes: `Form::services()`, `Form::published()`, `Form::ordered()`, `WorkUnit` dari Task 2.
- Produces:
  - Rute bernama `layanan.index` (`GET /layanan`).
  - `resources/views/partials/service-card.blade.php` menerima dua variabel: `$layanan` (model `Form`) dan `$nomor` (int).
  - Rute bernama `layanan.show` dan `layanan.ajukan` DIRUJUK oleh kartu — didefinisikan di Task 6 dan Task 7. Agar task ini bisa dites sendiri, definisikan ketiga rute sekaligus di Step 4, dengan `show`/`ajukan` sementara mengembalikan `abort(404)`.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/ServiceCatalogTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    private WorkUnit $tu;

    private WorkUnit $kesiswaan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tu = WorkUnit::create(['name' => 'Tata Usaha (TU)', 'slug' => 'tata-usaha', 'sort_order' => 1]);
        $this->kesiswaan = WorkUnit::create(['name' => 'Kesiswaan', 'slug' => 'kesiswaan', 'sort_order' => 2]);

        Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published', 'work_unit_id' => $this->tu->id, 'duration_text' => '30 Menit', 'sort_order' => 1]);
        Form::create(['title' => 'SPP Legalisasi Ijazah Online', 'slug' => 'legalisasi-online', 'status' => 'published', 'work_unit_id' => $this->tu->id, 'duration_text' => '1-2 Hari', 'sort_order' => 2]);
        Form::create(['title' => 'SPP Mutasi Masuk', 'slug' => 'mutasi-masuk', 'status' => 'published', 'work_unit_id' => $this->kesiswaan->id, 'duration_text' => '3 Hari', 'sort_order' => 3]);
        Form::create(['title' => 'Layanan Belum Terbit', 'slug' => 'belum-terbit', 'status' => 'draft', 'work_unit_id' => $this->tu->id, 'sort_order' => 4]);
        Form::create(['title' => 'Form Kontak Biasa', 'slug' => 'kontak', 'status' => 'published', 'is_service' => false, 'sort_order' => 5]);
    }

    public function test_katalog_hanya_menampilkan_layanan_terbit(): void
    {
        $this->get('/layanan')
            ->assertOk()
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertSee('SPP Mutasi Masuk')
            ->assertSee('30 Menit')
            ->assertSee('Gratis')
            ->assertDontSee('Layanan Belum Terbit')
            ->assertDontSee('Form Kontak Biasa');
    }

    public function test_filter_satuan_kerja_menyaring_hasil(): void
    {
        $this->get('/layanan?unit=kesiswaan')
            ->assertOk()
            ->assertSee('SPP Mutasi Masuk')
            ->assertDontSee('SPP Pengambilan Ijazah');
    }

    public function test_pencarian_judul_menyaring_hasil(): void
    {
        $this->get('/layanan?q=legalisasi')
            ->assertOk()
            ->assertSee('SPP Legalisasi Ijazah Online')
            ->assertDontSee('SPP Mutasi Masuk');
    }

    public function test_nomor_kartu_tetap_global_walau_hasil_tersaring(): void
    {
        // "SPP Mutasi Masuk" adalah layanan ke-3 pada urutan penuh. Saat katalog
        // disaring ke Kesiswaan ia menjadi satu-satunya hasil, tapi nomornya
        // harus tetap #3 — bukan #1 — supaya bisa dirujuk secara lisan.
        $this->get('/layanan?unit=kesiswaan')
            ->assertOk()
            ->assertSee('#3')
            ->assertDontSee('#1');
    }

    public function test_chip_satuan_kerja_ditampilkan(): void
    {
        $this->get('/layanan')
            ->assertOk()
            ->assertSee('Seluruh Satuan Kerja')
            ->assertSee('Tata Usaha (TU)')
            ->assertSee('Kesiswaan');
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=ServiceCatalogTest`
Expected: FAIL — semua test mengembalikan 404 karena rute `/layanan` belum ada.

- [ ] **Step 3: Buat controller**

File: `app/Http/Controllers/ServiceController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /** Katalog kartu layanan, dengan filter satuan kerja & pencarian judul. */
    public function index(Request $request)
    {
        $semua = Form::query()
            ->services()
            ->published()
            ->ordered()
            ->with('workUnit')
            ->get();

        // Nomor kartu dihitung dari urutan PENUH, bukan dari hasil yang sedang
        // tersaring, sehingga nomor sebuah layanan tidak berubah saat difilter.
        $nomor = $semua->pluck('id')->flip()->map(fn (int $index): int => $index + 1);

        $unit = $request->query('unit');
        $q = trim((string) $request->query('q', ''));

        $layanan = $semua
            ->when($unit, fn ($daftar) => $daftar->filter(
                fn (Form $item): bool => $item->workUnit?->slug === $unit,
            ))
            ->when($q !== '', fn ($daftar) => $daftar->filter(
                fn (Form $item): bool => Str::contains(Str::lower($item->title), Str::lower($q)),
            ))
            ->values();

        $satuanKerja = WorkUnit::orderBy('sort_order')->orderBy('name')->get();

        return view('layanan.index', compact('layanan', 'nomor', 'satuanKerja', 'unit', 'q'));
    }
}
```

- [ ] **Step 4: Daftarkan rute dan perbaiki regex catch-all**

Di `routes/web.php`, tambahkan blok berikut SEBELUM rute catch-all `/{page:slug}`:

```php
// Layanan PTSP. Wajib didefinisikan sebelum catch-all halaman statis.
Route::get('/layanan', [ServiceController::class, 'index'])->name('layanan.index');
Route::get('/layanan/{form:slug}', fn () => abort(404))->name('layanan.show');
Route::get('/layanan/{form:slug}/ajukan', fn () => abort(404))->name('layanan.ajukan');
```

Tambahkan `use App\Http\Controllers\ServiceController;` di bagian atas berkas.

Lalu ubah regex catch-all agar tidak menelan rute PTSP. Baris saat ini:

```php
    ->where('page', '(?!admin$|up$|form$)[A-Za-z0-9._-]+')
```

menjadi:

```php
    ->where('page', '(?!admin$|up$|form$|layanan$|lacak$|permohonan$)[A-Za-z0-9._-]+')
```

- [ ] **Step 5: Buat partial kartu layanan**

File: `resources/views/partials/service-card.blade.php`

```blade
{{-- Kartu katalog layanan. Variabel: $layanan (Form), $nomor (int). --}}
<article class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
    <div class="flex items-start justify-between gap-3">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.878.53 2.32-.348a17 17 0 014.486-5.276c.878-.442 1.047-1.621.348-2.32L11.16 3.66A2.25 2.25 0 009.568 3z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
            </svg>
            {{ $layanan->organizer }} / {{ $layanan->workUnit?->name ?? 'Umum' }}
        </span>
        <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">#{{ $nomor }}</span>
    </div>

    <h3 class="mt-4 line-clamp-2 text-lg font-semibold leading-snug text-gray-900">
        {{ $layanan->title }}
    </h3>

    {{-- mt-auto mendorong blok bawah ke dasar kartu supaya semua kartu
         dalam satu baris berakhir rata walau panjang judulnya berbeda. --}}
    <div class="mt-auto pt-4">
        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 text-sm">
            <span class="flex items-center gap-1.5 text-gray-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $layanan->duration_text ?: 'Menyesuaikan' }}
            </span>
            <span class="flex items-center gap-1.5 font-medium text-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                </svg>
                {{ $layanan->fee_text }}
            </span>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <a href="{{ route('layanan.show', $layanan) }}"
               class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                Rincian
            </a>
            <a href="{{ route('layanan.ajukan', $layanan) }}"
               class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary px-3 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                Ajukan
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
</article>
```

- [ ] **Step 6: Buat halaman katalog**

File: `resources/views/layanan/index.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Daftar Layanan')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Daftar Layanan Tersedia</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Pilih layanan di bawah untuk melihat rincian syarat atau memulai pengajuan.
                </p>
            </div>

            <form method="GET" action="{{ route('layanan.index') }}" class="sm:w-72">
                @if ($unit)
                    <input type="hidden" name="unit" value="{{ $unit }}">
                @endif
                <label for="q" class="sr-only">Cari layanan</label>
                <input id="q" type="search" name="q" value="{{ $q }}"
                       placeholder="Cari nama layanan..."
                       class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
            </form>
        </div>

        {{-- Chip filter memakai query string sehingga tetap bekerja tanpa
             JavaScript dan bisa di-bookmark. --}}
        <div class="mt-6 flex flex-wrap gap-2">
            @php $chipAktif = 'border-primary bg-primary text-white'; @endphp
            @php $chipMati = 'border-gray-300 bg-white text-gray-700 hover:border-primary hover:text-primary'; @endphp

            <a href="{{ route('layanan.index', array_filter(['q' => $q])) }}"
               class="rounded-full border px-4 py-1.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 {{ $unit ? $chipMati : $chipAktif }}">
                Seluruh Satuan Kerja
            </a>
            @foreach ($satuanKerja as $sk)
                <a href="{{ route('layanan.index', array_filter(['unit' => $sk->slug, 'q' => $q])) }}"
                   class="rounded-full border px-4 py-1.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 {{ $unit === $sk->slug ? $chipAktif : $chipMati }}">
                    {{ $sk->name }}
                </a>
            @endforeach
        </div>

        @if ($layanan->isEmpty())
            <p class="mt-10 rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center text-gray-500">
                Tidak ada layanan yang cocok dengan pilihan Anda.
            </p>
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($layanan as $item)
                    @include('partials.service-card', ['layanan' => $item, 'nomor' => $nomor[$item->id]])
                @endforeach
            </div>
        @endif
    </div>
@endsection
```

- [ ] **Step 7: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=ServiceCatalogTest`
Expected: PASS — 5 test lulus.

- [ ] **Step 8: Bangun aset dan verifikasi halaman di browser lokal**

```bash
npm run build
php artisan serve --port=8123 &
sleep 3
curl -s http://127.0.0.1:8123/layanan | grep -c 'Daftar Layanan Tersedia'
kill %1
```
Expected: `1`

- [ ] **Step 9: Jalankan seluruh test dan commit**

```bash
php artisan test
git add -A
git commit -q -m "feat: katalog layanan PTSP dengan kartu, filter satuan kerja, dan pencarian"
```

---

### Task 6: Halaman rincian layanan

Halaman `/layanan/{slug}` menampilkan syarat dan dasar hukum, dengan kartu ringkas berisi tombol ajukan.

**Files:**
- Modify: `app/Http/Controllers/ServiceController.php`
- Create: `resources/views/layanan/show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ServiceDetailTest.php`

**Interfaces:**
- Consumes: rute `layanan.show` yang dibuat sebagai stub di Task 5.
- Produces: `ServiceController::show(Form $form)`. Penjaga akses memakai `Form::isPublishedService()` dari Task 2, yang juga dipakai Task 7 & 8.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/ServiceDetailTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_rincian_menampilkan_syarat_dan_meta(): void
    {
        $unit = WorkUnit::create(['name' => 'Tata Usaha (TU)', 'slug' => 'tata-usaha']);
        Form::create([
            'title' => 'SPP Pengambilan Ijazah',
            'slug' => 'pengambilan-ijazah',
            'status' => 'published',
            'work_unit_id' => $unit->id,
            'duration_text' => '30 Menit',
            'fee_text' => 'Gratis',
            'requirements' => '<p>Membawa kartu identitas asli.</p>',
            'legal_basis' => '<p>Permendikbud Nomor 1 Tahun 2021.</p>',
        ]);

        $this->get('/layanan/pengambilan-ijazah')
            ->assertOk()
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertSee('Membawa kartu identitas asli.')
            ->assertSee('Permendikbud Nomor 1 Tahun 2021.')
            ->assertSee('30 Menit')
            ->assertSee('Tata Usaha (TU)')
            ->assertSee('Ajukan Permohonan');
    }

    public function test_layanan_draft_mengembalikan_404(): void
    {
        Form::create(['title' => 'Rahasia', 'slug' => 'rahasia', 'status' => 'draft']);

        $this->get('/layanan/rahasia')->assertNotFound();
    }

    public function test_form_biasa_bukan_layanan_mengembalikan_404(): void
    {
        Form::create(['title' => 'Kontak', 'slug' => 'kontak', 'status' => 'published', 'is_service' => false]);

        $this->get('/layanan/kontak')->assertNotFound();
    }

    public function test_slug_tidak_dikenal_mengembalikan_404(): void
    {
        $this->get('/layanan/tidak-ada')->assertNotFound();
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=ServiceDetailTest`
Expected: FAIL — test pertama mendapat 404 karena rute masih stub `abort(404)`.

- [ ] **Step 3: Tambahkan method ke controller**

Di `app/Http/Controllers/ServiceController.php`:

```php
    /** Halaman rincian syarat & dasar hukum satu layanan. */
    public function show(Form $form)
    {
        // Form biasa (is_service = false) tetap dilayani rute /form/{slug} lama.
        abort_unless($form->isPublishedService(), 404);

        $form->load('workUnit');

        return view('layanan.show', ['layanan' => $form]);
    }
```

- [ ] **Step 4: Ganti stub rute `layanan.show`**

Di `routes/web.php`, ubah baris stub menjadi:

```php
Route::get('/layanan/{form:slug}', [ServiceController::class, 'show'])->name('layanan.show');
```

- [ ] **Step 5: Buat halaman rincian**

File: `resources/views/layanan/show.blade.php`

```blade
@extends('layouts.app')

@section('title', $layanan->title)

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10">
        <a href="{{ route('layanan.index') }}" class="text-sm text-gray-500 hover:text-primary">&larr; Kembali ke daftar layanan</a>

        <h1 class="mt-3 text-2xl font-bold text-gray-900 sm:text-3xl">{{ $layanan->title }}</h1>
        @if ($layanan->description)
            <p class="mt-2 text-gray-600">{{ $layanan->description }}</p>
        @endif

        {{-- Di ponsel kartu ringkas tampil lebih dulu (order-first) supaya tombol
             ajukan terlihat tanpa menggulir melewati seluruh syarat. --}}
        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <aside class="order-first lg:order-last">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:sticky lg:top-24">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Satuan Kerja</dt>
                            <dd class="text-right font-medium text-gray-900">{{ $layanan->workUnit?->name ?? 'Umum' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Waktu Layanan</dt>
                            <dd class="text-right font-medium text-gray-900">{{ $layanan->duration_text ?: 'Menyesuaikan' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Biaya</dt>
                            <dd class="text-right font-medium text-primary">{{ $layanan->fee_text }}</dd>
                        </div>
                    </dl>

                    <a href="{{ route('layanan.ajukan', $layanan) }}"
                       class="mt-5 flex w-full items-center justify-center rounded-lg bg-primary px-4 py-3 font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                        Ajukan Permohonan
                    </a>
                </div>
            </aside>

            <div class="lg:col-span-2">
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Persyaratan &amp; Alur</h2>
                    <div class="prose mt-3 max-w-none">
                        {!! $layanan->requirements ?: '<p>Rincian persyaratan belum diisi operator.</p>' !!}
                    </div>
                </section>

                @if ($layanan->legal_basis)
                    <section class="mt-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900">Dasar Hukum</h2>
                        <div class="prose mt-3 max-w-none">{!! $layanan->legal_basis !!}</div>
                    </section>
                @endif
            </div>
        </div>
    </div>
@endsection
```

- [ ] **Step 6: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=ServiceDetailTest`
Expected: PASS — 4 test lulus.

- [ ] **Step 7: Jalankan seluruh test dan commit**

```bash
php artisan test
git add -A
git commit -q -m "feat: halaman rincian layanan dengan syarat dan dasar hukum"
```

---

### Task 7: Formulir pengajuan — tampilan

Halaman `/layanan/{slug}/ajukan`: blok identitas bawaan + field dinamis bernomor + area unggah tarik-lepas.

**Files:**
- Create: `app/Http/Controllers/ApplicationController.php`
- Create: `resources/views/layanan/ajukan.blade.php`
- Create: `resources/views/partials/field-input.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/js/app.js`
- Modify: `resources/css/app.css`
- Test: `tests/Feature/ApplicationFormPageTest.php`

**Interfaces:**
- Consumes: `Form::isPublishedService()` dari Task 2.
- Produces:
  - `ApplicationController::create(Form $form)`, rute `layanan.ajukan`.
  - `resources/views/partials/field-input.blade.php` menerima `$field` (`FormField`) dan `$nomor` (int); nama input HTML selalu `field_{id}`.
  - Rute `layanan.kirim` (`POST`) DIRUJUK oleh form — didefinisikan sebagai stub di Step 4 dan diisi di Task 8.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/ApplicationFormPageTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationFormPageTest extends TestCase
{
    use RefreshDatabase;

    private function layanan(): Form
    {
        $layanan = Form::create([
            'title' => 'SPP Legalisasi Ijazah Online',
            'slug' => 'legalisasi-online',
            'status' => 'published',
        ]);
        $layanan->fields()->create(['label' => 'Tanda terima pengambilan ijazah/STTB', 'type' => 'text', 'required' => true, 'sort_order' => 1]);
        $layanan->fields()->create(['label' => 'Nama', 'type' => 'text', 'required' => true, 'sort_order' => 2]);
        $layanan->fields()->create(['label' => 'Upload Ijazah', 'type' => 'file', 'required' => false, 'sort_order' => 3]);

        return $layanan;
    }

    public function test_halaman_menampilkan_blok_identitas_bawaan(): void
    {
        $this->layanan();

        $this->get('/layanan/legalisasi-online/ajukan')
            ->assertOk()
            ->assertSee('Identitas &amp; Kontak Pemohon', false)
            ->assertSee('Nama Lengkap Pemohon')
            ->assertSee('Layanan yang Dituju')
            ->assertSee('Nomor WhatsApp')
            ->assertSee('Alamat Email')
            ->assertSee('SPP Legalisasi Ijazah Online');
    }

    public function test_field_dinamis_ditampilkan_bernomor_urut(): void
    {
        $this->layanan();

        $this->get('/layanan/legalisasi-online/ajukan')
            ->assertOk()
            ->assertSee('Kelengkapan Berkas &amp; Data Isian', false)
            ->assertSee('1. Tanda terima pengambilan ijazah/STTB')
            ->assertSee('2. Nama')
            ->assertSee('3. Upload Ijazah');
    }

    public function test_field_opsional_ditandai_eksplisit(): void
    {
        $this->layanan();

        $this->get('/layanan/legalisasi-online/ajukan')
            ->assertOk()
            ->assertSee('(Opsional)');
    }

    public function test_area_unggah_menampilkan_batasan_berkas(): void
    {
        $this->layanan();

        $this->get('/layanan/legalisasi-online/ajukan')
            ->assertOk()
            ->assertSee('Klik atau Tarik File ke Area Ini')
            ->assertSee('Format: PDF, JPG, PNG (Maksimal 5MB)');
    }

    public function test_tombol_kirim_memakai_teks_yang_disepakati(): void
    {
        $this->layanan();

        $this->get('/layanan/legalisasi-online/ajukan')
            ->assertOk()
            ->assertSee('Kirim Permohonan Sekarang');
    }

    public function test_layanan_draft_tidak_bisa_diajukan(): void
    {
        Form::create(['title' => 'Rahasia', 'slug' => 'rahasia', 'status' => 'draft']);

        $this->get('/layanan/rahasia/ajukan')->assertNotFound();
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=ApplicationFormPageTest`
Expected: FAIL — rute masih stub `abort(404)`.

- [ ] **Step 3: Buat controller**

File: `app/Http/Controllers/ApplicationController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Form;

class ApplicationController extends Controller
{
    /** Formulir pengajuan satu layanan. */
    public function create(Form $form)
    {
        abort_unless($form->isPublishedService(), 404);

        $form->load('fields');

        return view('layanan.ajukan', ['layanan' => $form]);
    }
}
```

- [ ] **Step 4: Perbarui rute**

Di `routes/web.php`, ganti stub `layanan.ajukan` dan tambahkan rute kirim:

```php
Route::get('/layanan/{form:slug}/ajukan', [ApplicationController::class, 'create'])->name('layanan.ajukan');
Route::post('/layanan/{form:slug}/ajukan', fn () => abort(404))->name('layanan.kirim');
```

Tambahkan `use App\Http\Controllers\ApplicationController;` di bagian atas berkas.

- [ ] **Step 5: Buat partial input field**

File: `resources/views/partials/field-input.blade.php`

```blade
{{-- Satu baris pertanyaan pada blok "Kelengkapan Berkas & Data Isian".
     Variabel: $field (FormField), $nomor (int). --}}
@php
    $name = 'field_'.$field->id;
    $inputClass = 'mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary';
@endphp

<div>
    <label for="{{ $name }}" class="flex items-center gap-2 text-sm font-semibold text-gray-800">
        @if ($field->type === 'file')
            <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        @else
            <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
            </svg>
        @endif

        <span>{{ $nomor }}. {{ $field->label }}</span>

        @if ($field->required)
            <span class="text-red-500" aria-hidden="true">*</span>
            <span class="sr-only">wajib diisi</span>
        @else
            {{-- Ditulis eksplisit: ketiadaan bintang merah saja terlalu ambigu. --}}
            <span class="text-xs font-normal text-gray-400">(Opsional)</span>
        @endif
    </label>

    @if ($field->type === 'text')
        <input id="{{ $name }}" type="text" name="{{ $name }}" value="{{ old($name) }}"
               @required($field->required)
               placeholder="Masukkan {{ Str::lower($field->label) }}..."
               class="{{ $inputClass }}">

    @elseif ($field->type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="4" @required($field->required)
                  placeholder="Masukkan {{ Str::lower($field->label) }}..."
                  class="{{ $inputClass }}">{{ old($name) }}</textarea>

    @elseif ($field->type === 'date')
        <input id="{{ $name }}" type="date" name="{{ $name }}" value="{{ old($name) }}"
               @required($field->required) class="{{ $inputClass }}">

    @elseif ($field->type === 'number')
        <input id="{{ $name }}" type="number" name="{{ $name }}" value="{{ old($name) }}"
               @required($field->required) class="{{ $inputClass }}">

    @elseif ($field->type === 'select')
        <select id="{{ $name }}" name="{{ $name }}" @required($field->required) class="{{ $inputClass }}">
            <option value="">&mdash; Pilih &mdash;</option>
            @foreach (($field->options ?? []) as $opt)
                <option value="{{ $opt }}" @selected(old($name) === $opt)>{{ $opt }}</option>
            @endforeach
        </select>

    @elseif ($field->type === 'checkbox')
        <div class="mt-2 space-y-2">
            @foreach (($field->options ?? []) as $opt)
                <label class="flex items-center gap-3 text-gray-700">
                    <input type="checkbox" name="{{ $name }}[]" value="{{ $opt }}"
                           @checked(in_array($opt, (array) old($name, [])))
                           class="h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary">
                    {{ $opt }}
                </label>
            @endforeach
        </div>

    @elseif ($field->type === 'file')
        {{-- Input file asli selalu ada di DOM sehingga tetap bisa dioperasikan
             keyboard & pembaca layar; JavaScript hanya menambah tarik-lepas. --}}
        <div data-dropzone
             class="dropzone relative mt-2 rounded-xl border-2 border-dashed border-gray-300 bg-primary/5 px-6 py-8 text-center transition">
            <input id="{{ $name }}" type="file" name="{{ $name }}" @required($field->required)
                   accept=".pdf,.jpg,.jpeg,.png"
                   class="dropzone__input block w-full text-sm text-gray-600">

            <div data-dropzone-idle class="dropzone__idle pointer-events-none">
                <svg class="mx-auto h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                </svg>
                <p class="mt-2 text-sm font-semibold text-gray-800">Klik atau Tarik File ke Area Ini</p>
                <p class="mt-0.5 text-xs text-gray-500">Format: PDF, JPG, PNG (Maksimal 5MB)</p>
            </div>

            <div data-dropzone-filled class="dropzone__filled" hidden>
                <p class="text-sm font-semibold text-gray-800" data-dropzone-name></p>
                <button type="button" data-dropzone-clear
                        class="relative z-10 mt-2 text-xs font-medium text-primary underline">
                    Hapus &amp; pilih berkas lain
                </button>
            </div>
        </div>
    @endif

    @if ($field->help_text)
        <p class="mt-1.5 text-xs text-gray-500">{{ $field->help_text }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
```

- [ ] **Step 6: Buat halaman formulir**

File: `resources/views/layanan/ajukan.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Ajukan '.$layanan->title)

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10">
        <a href="{{ route('layanan.show', $layanan) }}" class="text-sm text-gray-500 hover:text-primary">&larr; Kembali ke rincian layanan</a>

        <form method="POST" action="{{ route('layanan.kirim', $layanan) }}" enctype="multipart/form-data"
              data-locking-form class="mt-4 space-y-6">
            @csrf

            {{-- Honeypot: tersembunyi dari manusia, tapi terisi oleh bot yang
                 mengisi semua input. Bukan display:none agar tidak dilewati
                 sebagian bot yang sudah pintar. --}}
            <div class="absolute left-[-9999px]" aria-hidden="true">
                <label for="website">Jangan isi kolom ini</label>
                <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 pb-3">
                    <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
                        <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                        </svg>
                        Identitas &amp; Kontak Pemohon
                    </h2>
                    <p class="text-xs text-red-500">* Wajib diisi dengan benar</p>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="applicant_name" class="block text-sm font-medium text-gray-700">
                            Nama Lengkap Pemohon <span class="text-red-500">*</span>
                        </label>
                        <input id="applicant_name" type="text" name="applicant_name" value="{{ old('applicant_name') }}" required
                               placeholder="Ketik nama lengkap sesuai KTP"
                               class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                        @error('applicant_name')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="layanan_dituju" class="block text-sm font-medium text-gray-700">Layanan yang Dituju</label>
                        <input id="layanan_dituju" type="text" value="{{ $layanan->title }}" readonly
                               class="mt-1.5 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 font-medium text-primary">
                    </div>

                    <div>
                        <label for="applicant_whatsapp" class="block text-sm font-medium text-gray-700">
                            Nomor WhatsApp / HP Aktif <span class="text-red-500">*</span>
                        </label>
                        <input id="applicant_whatsapp" type="tel" name="applicant_whatsapp" value="{{ old('applicant_whatsapp') }}" required
                               placeholder="Contoh: 081234567890"
                               class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                        <p class="mt-1 text-xs text-gray-500">Untuk koordinasi &amp; verifikasi saat melacak permohonan.</p>
                        @error('applicant_whatsapp')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="applicant_email" class="block text-sm font-medium text-gray-700">
                            Alamat Email Aktif <span class="text-red-500">*</span>
                        </label>
                        <input id="applicant_email" type="email" name="applicant_email" value="{{ old('applicant_email') }}" required
                               placeholder="Contoh: nama@gmail.com"
                               class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                        <p class="mt-1 text-xs text-gray-500">Dipakai bila petugas perlu menghubungi Anda.</p>
                        @error('applicant_email')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            @if ($layanan->fields->isNotEmpty())
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 pb-3">
                        <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
                            <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                            </svg>
                            Kelengkapan Berkas &amp; Data Isian
                        </h2>
                        <p class="text-xs text-red-500">* Menandakan kolom wajib diisi</p>
                    </div>

                    <div class="mt-5 space-y-6">
                        @foreach ($layanan->fields as $index => $field)
                            @include('partials.field-input', ['field' => $field, 'nomor' => $index + 1])
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="flex justify-end">
                <button type="submit" data-submit-button
                        class="inline-flex items-center gap-2 rounded-full bg-primary px-7 py-3.5 text-base font-semibold text-white shadow-md transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                    <span data-submit-label>Kirim Permohonan Sekarang</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </div>
        </form>
    </div>
@endsection
```

- [ ] **Step 7: Tambahkan gaya area unggah**

Di akhir `resources/css/app.css`, tambahkan:

```css
/* ==========================================================================
   Area unggah berkas (dropzone) pada formulir pengajuan PTSP.
   Markup di resources/views/partials/field-input.blade.php.
   Tanpa JavaScript, input file bawaan tetap tampil dan berfungsi; kelas
   .is-enhanced baru dipasang JS setelah tarik-lepas siap.
   ========================================================================== */
@layer components {
    .dropzone.is-enhanced .dropzone__input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .dropzone.is-enhanced .dropzone__idle,
    .dropzone.is-enhanced .dropzone__filled {
        display: block;
    }

    .dropzone.is-dragging {
        border-color: var(--color-primary);
        background-color: color-mix(in srgb, var(--color-primary) 12%, transparent);
    }
}
```

- [ ] **Step 8: Tambahkan JavaScript tarik-lepas dan pengunci tombol kirim**

Di akhir `resources/js/app.js`, tambahkan:

```js
// Area unggah berkas: tarik-lepas + umpan balik nama berkas setelah dipilih.
// Tanpa umpan balik, pemohon tidak yakin berkasnya benar-benar terpilih.
document.querySelectorAll('[data-dropzone]').forEach((zone) => {
    const input = zone.querySelector('input[type="file"]')
    const idle = zone.querySelector('[data-dropzone-idle]')
    const filled = zone.querySelector('[data-dropzone-filled]')
    const nameEl = zone.querySelector('[data-dropzone-name]')
    const clearBtn = zone.querySelector('[data-dropzone-clear]')

    if (!input || !idle || !filled || !nameEl) return

    zone.classList.add('is-enhanced')

    const render = () => {
        const file = input.files && input.files[0]
        if (file) {
            const mb = (file.size / 1024 / 1024).toFixed(2)
            nameEl.textContent = `${file.name} (${mb} MB)`
        }
        idle.hidden = Boolean(file)
        filled.hidden = !file
    }

    input.addEventListener('change', render)

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            input.value = ''
            render()
        })
    }

    ;['dragenter', 'dragover'].forEach((event) => {
        zone.addEventListener(event, (e) => {
            e.preventDefault()
            zone.classList.add('is-dragging')
        })
    })

    ;['dragleave', 'drop'].forEach((event) => {
        zone.addEventListener(event, (e) => {
            e.preventDefault()
            zone.classList.remove('is-dragging')
        })
    })

    zone.addEventListener('drop', (e) => {
        if (e.dataTransfer && e.dataTransfer.files.length > 0) {
            input.files = e.dataTransfer.files
            render()
        }
    })

    render()
})

// Kunci tombol kirim setelah diklik supaya permohonan tidak terkirim dua kali
// saat koneksi lambat dan pemohon menekan tombol berulang kali.
document.querySelectorAll('[data-locking-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-submit-button]')
        const label = form.querySelector('[data-submit-label]')
        if (!button || button.disabled) return

        button.disabled = true
        if (label) label.textContent = 'Mengirim...'
    })
})
```

- [ ] **Step 9: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=ApplicationFormPageTest`
Expected: PASS — 6 test lulus.

- [ ] **Step 10: Bangun aset, jalankan seluruh test, dan commit**

```bash
npm run build
php artisan test
git add -A
git commit -q -m "feat: tampilan formulir pengajuan dengan blok identitas dan area unggah"
```

---

### Task 8: Pemrosesan pengajuan

Validasi, honeypot, penyimpanan berkas privat, pembuatan kode resi, dan pencatatan riwayat.

**Files:**
- Create: `app/Support/DynamicFieldRules.php`
- Modify: `app/Http/Controllers/ApplicationController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ApplicationSubmitTest.php`

**Interfaces:**
- Consumes: `ReceiptCode::generateUnique()`, `WhatsappNumber::normalize()` (Task 4); `FormSubmission::recordStatus()` (Task 3).
- Produces:
  - `App\Support\DynamicFieldRules::key(FormField $field): string`
  - `App\Support\DynamicFieldRules::rules(Form $form): array`
  - `App\Support\DynamicFieldRules::attributes(Form $form): array`
  - `ApplicationController::store(Request $request, Form $form)` — mengalihkan ke rute `permohonan.selesai` dengan session `receipt_code` dan `service_title`.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/ApplicationSubmitTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationSubmitTest extends TestCase
{
    use RefreshDatabase;

    private Form $layanan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->layanan = Form::create([
            'title' => 'SPP Surat Pengganti Ijazah Hilang',
            'slug' => 'ijazah-hilang',
            'status' => 'published',
        ]);
        $this->layanan->fields()->create(['label' => 'Nama', 'type' => 'text', 'required' => true, 'sort_order' => 1]);
        $this->layanan->fields()->create(['label' => 'Upload Surat Kehilangan', 'type' => 'file', 'required' => true, 'sort_order' => 2]);
    }

    private function isian(array $ganti = []): array
    {
        $fields = $this->layanan->fields;

        return array_merge([
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '0812-3456-7890',
            'applicant_email' => 'budi@example.com',
            'field_'.$fields[0]->id => 'Budi Santoso',
            'field_'.$fields[1]->id => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
        ], $ganti);
    }

    public function test_pengajuan_valid_membuat_permohonan_dengan_resi_dan_riwayat(): void
    {
        Storage::fake('local');

        $response = $this->post('/layanan/ijazah-hilang/ajukan', $this->isian());

        $response->assertRedirect(route('permohonan.selesai'));

        $permohonan = FormSubmission::sole();
        $this->assertSame('diajukan', $permohonan->status);
        $this->assertSame('Budi Santoso', $permohonan->applicant_name);
        $this->assertMatchesRegularExpression('/^PTSP-\d{4}-[A-Z2-9]{6}$/', $permohonan->receipt_code);
        $this->assertCount(1, $permohonan->statusLogs);
        $this->assertSame('diajukan', $permohonan->statusLogs->first()->status);
    }

    public function test_nomor_whatsapp_disimpan_ternormalisasi(): void
    {
        Storage::fake('local');

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian());

        $this->assertSame('6281234567890', FormSubmission::sole()->applicant_whatsapp);
    }

    public function test_berkas_tersimpan_di_disk_privat_bukan_publik(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian());

        $path = FormSubmission::sole()->data[$this->layanan->fields[1]->id];

        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_field_wajib_yang_kosong_ditolak(): void
    {
        Storage::fake('local');
        $fields = $this->layanan->fields;

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian([
            'field_'.$fields[0]->id => '',
        ]))->assertSessionHasErrors('field_'.$fields[0]->id);

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_identitas_yang_kosong_ditolak(): void
    {
        Storage::fake('local');

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian([
            'applicant_email' => '',
        ]))->assertSessionHasErrors('applicant_email');

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_nomor_whatsapp_terlalu_pendek_ditolak(): void
    {
        Storage::fake('local');

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian([
            'applicant_whatsapp' => '0812',
        ]))->assertSessionHasErrors('applicant_whatsapp');

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_honeypot_terisi_tidak_membuat_permohonan(): void
    {
        Storage::fake('local');

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian([
            'website' => 'http://spam.example.com',
        ]))->assertRedirect(route('layanan.index'));

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_layanan_draft_tidak_menerima_pengajuan(): void
    {
        Storage::fake('local');
        Form::create(['title' => 'Rahasia', 'slug' => 'rahasia', 'status' => 'draft']);

        $this->post('/layanan/rahasia/ajukan', [
            'applicant_name' => 'X',
            'applicant_whatsapp' => '081234567890',
            'applicant_email' => 'x@example.com',
        ])->assertNotFound();
    }

    public function test_field_unik_menolak_nilai_yang_sudah_dipakai(): void
    {
        Storage::fake('local');
        $this->layanan->fields[0]->update(['is_unique' => true]);

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian());
        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian())
            ->assertSessionHasErrors('field_'.$this->layanan->fields[0]->id);

        $this->assertDatabaseCount('form_submissions', 1);
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=ApplicationSubmitTest`
Expected: FAIL — rute POST masih stub `abort(404)`.

- [ ] **Step 3: Buat pembangun aturan validasi dinamis**

File: `app/Support/DynamicFieldRules.php`

```php
<?php

namespace App\Support;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Validation\Rule;

/**
 * Menerjemahkan definisi field yang dibuat operator menjadi aturan validasi
 * Laravel. Dipisahkan dari controller supaya bisa dites sendiri dan dipakai
 * ulang bila nanti ada kanal pengajuan lain.
 */
class DynamicFieldRules
{
    public static function key(FormField $field): string
    {
        return 'field_'.$field->id;
    }

    public static function rules(Form $form): array
    {
        $rules = [];

        foreach ($form->fields as $field) {
            $key = self::key($field);
            $wajib = $field->required ? 'required' : 'nullable';

            $rules[$key] = match ($field->type) {
                'select' => [$wajib, Rule::in($field->options ?? [])],
                'checkbox' => [$wajib, 'array'],
                'date' => [$wajib, 'date'],
                'number' => [$wajib, 'numeric'],
                'file' => [$wajib, 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
                default => [$wajib, 'string', 'max:5000'],
            };

            if ($field->type === 'checkbox') {
                $rules[$key.'.*'] = [Rule::in($field->options ?? [])];
            }
        }

        return $rules;
    }

    /** Nama field yang dipakai dalam pesan error, memakai label dari operator. */
    public static function attributes(Form $form): array
    {
        $attributes = [];

        foreach ($form->fields as $field) {
            $attributes[self::key($field)] = $field->label;
        }

        return $attributes;
    }
}
```

- [ ] **Step 4: Implementasikan `store`**

Tambahkan ke `app/Http/Controllers/ApplicationController.php` (beserta import `Illuminate\Http\Request`, `Illuminate\Support\Facades\DB`, `Illuminate\Validation\ValidationException`, `App\Support\DynamicFieldRules`, `App\Support\ReceiptCode`, `App\Support\WhatsappNumber`, `App\Models\FormSubmission`):

```php
    /** Terima pengajuan, simpan berkas ke disk privat, terbitkan kode resi. */
    public function store(Request $request, Form $form)
    {
        abort_unless($form->isPublishedService(), 404);

        // Honeypot: manusia tidak pernah melihat kolom ini, bot mengisinya.
        // Ditolak diam-diam supaya bot tidak belajar dari pesan error.
        if (filled($request->input('website'))) {
            return redirect()->route('layanan.index');
        }

        $form->load('fields');

        $aturan = array_merge([
            'applicant_name' => ['required', 'string', 'max:150'],
            'applicant_whatsapp' => ['required', 'string', 'max:25'],
            'applicant_email' => ['required', 'email', 'max:150'],
        ], DynamicFieldRules::rules($form));

        $atribut = array_merge([
            'applicant_name' => 'Nama Lengkap Pemohon',
            'applicant_whatsapp' => 'Nomor WhatsApp',
            'applicant_email' => 'Alamat Email',
        ], DynamicFieldRules::attributes($form));

        $validated = $request->validate($aturan, [], $atribut);

        $nomor = WhatsappNumber::normalize($validated['applicant_whatsapp']);
        if (strlen($nomor) < 10 || strlen($nomor) > 15) {
            throw ValidationException::withMessages([
                'applicant_whatsapp' => 'Nomor WhatsApp tidak valid. Contoh penulisan: 081234567890.',
            ]);
        }

        $this->tolakNilaiDuplikat($form, $validated);

        $permohonan = DB::transaction(function () use ($form, $request, $validated, $nomor): FormSubmission {
            $data = [];

            foreach ($form->fields as $field) {
                $key = DynamicFieldRules::key($field);

                if ($field->type === 'file') {
                    if ($request->hasFile($key)) {
                        // Disk 'local' berada di luar public/: berkas ijazah & KTP
                        // tidak boleh bisa diunduh siapa pun yang menebak URL.
                        $data[$field->id] = $request->file($key)->store('permohonan/'.$form->id, 'local');
                    }

                    continue;
                }

                $data[$field->id] = $validated[$key] ?? ($field->type === 'checkbox' ? [] : null);
            }

            $permohonan = $form->submissions()->create([
                'receipt_code' => ReceiptCode::generateUnique(),
                'applicant_name' => $validated['applicant_name'],
                'applicant_whatsapp' => $nomor,
                'applicant_email' => $validated['applicant_email'],
                'status' => 'diajukan',
                'data' => $data,
            ]);

            $permohonan->recordStatus('diajukan', 'Permohonan diterima sistem.');

            return $permohonan;
        });

        return redirect()
            ->route('permohonan.selesai')
            ->with('receipt_code', $permohonan->receipt_code)
            ->with('service_title', $form->title);
    }

    /**
     * Field bertanda "tidak boleh duplikat" (mis. NIS) menolak nilai yang sudah
     * pernah dipakai pada layanan yang sama.
     */
    private function tolakNilaiDuplikat(Form $form, array $validated): void
    {
        $errors = [];

        foreach ($form->fields as $field) {
            if (! $field->is_unique) {
                continue;
            }

            $key = DynamicFieldRules::key($field);
            $nilai = $validated[$key] ?? null;

            if ($nilai === null || $nilai === '' || $nilai === []) {
                continue;
            }

            $terpakai = $form->submissions()
                ->get(['data'])
                ->contains(fn (FormSubmission $s): bool => ($s->data[$field->id] ?? null) === $nilai);

            if ($terpakai) {
                $errors[$key] = "Nilai untuk \"{$field->label}\" sudah pernah digunakan dan tidak boleh sama.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
```

- [ ] **Step 5: Ganti stub rute POST dan pasang rate limit**

Di `routes/web.php`:

```php
Route::post('/layanan/{form:slug}/ajukan', [ApplicationController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('layanan.kirim');
```

`store()` mengalihkan ke rute `permohonan.selesai` yang baru dibangun di Task 9. Agar task ini bisa dijalankan dan dites sendiri, daftarkan stub-nya sekarang — Task 9 akan menggantinya:

```php
Route::get('/permohonan/selesai', fn () => response('Selesai', 200))->name('permohonan.selesai');
```

- [ ] **Step 6: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=ApplicationSubmitTest`
Expected: PASS — 9 test lulus.

Catatan: `test_field_unik_menolak_nilai_yang_sudah_dipakai` mengirim dua permintaan berturut-turut; batas `throttle:5,60` tidak terlampaui.

- [ ] **Step 7: Jalankan seluruh test dan commit**

```bash
php artisan test
git add -A
git commit -q -m "feat: pemrosesan pengajuan dengan kode resi, honeypot, dan berkas privat"
```

---

### Task 9: Halaman sukses

Menampilkan kode resi setelah pengajuan berhasil, dengan tombol salin dan cetak.

**Files:**
- Modify: `app/Http/Controllers/ApplicationController.php`
- Create: `resources/views/permohonan/selesai.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/js/app.js`
- Test: `tests/Feature/ApplicationSuccessPageTest.php`

**Interfaces:**
- Consumes: session `receipt_code` dan `service_title` yang di-flash oleh `ApplicationController::store()` (Task 8).
- Produces: rute bernama `permohonan.selesai` (`GET /permohonan/selesai`).

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/ApplicationSuccessPageTest.php`

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationSuccessPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_menampilkan_kode_resi_dari_session(): void
    {
        $this->withSession([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'service_title' => 'SPP Pengambilan Ijazah',
        ])->get('/permohonan/selesai')
            ->assertOk()
            ->assertSee('PTSP-2609-A7K3QX')
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertSee('Simpan kode ini');
    }

    public function test_tanpa_session_dialihkan_ke_halaman_lacak(): void
    {
        // Mencegah kode resi orang lain ditemukan dengan membuka URL langsung.
        $this->get('/permohonan/selesai')->assertRedirect('/lacak');
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=ApplicationSuccessPageTest`
Expected: FAIL — 404, rute belum ada.

- [ ] **Step 3: Tambahkan method `selesai` ke controller**

```php
    /** Halaman bukti pengajuan. Kode resi hanya datang dari session sekali pakai. */
    public function selesai()
    {
        $kode = session('receipt_code');

        if (! $kode) {
            return redirect()->route('lacak.index');
        }

        return view('permohonan.selesai', [
            'kode' => $kode,
            'layanan' => session('service_title'),
        ]);
    }
```

- [ ] **Step 4: Daftarkan rute**

Di `routes/web.php`, sebelum catch-all:

```php
Route::get('/permohonan/selesai', [ApplicationController::class, 'selesai'])->name('permohonan.selesai');
```

Rute `lacak.index` dibuat di Task 10. Agar task ini bisa dites sendiri, daftarkan juga stub-nya sekarang:

```php
Route::get('/lacak', fn () => response('Lacak', 200))->name('lacak.index');
```

- [ ] **Step 5: Buat halaman sukses**

File: `resources/views/permohonan/selesai.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Permohonan Terkirim')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-14">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>

            <h1 class="mt-5 text-2xl font-bold text-gray-900">Permohonan Terkirim</h1>
            @if ($layanan)
                <p class="mt-1 text-gray-600">{{ $layanan }}</p>
            @endif

            <p class="mt-6 text-sm font-medium text-gray-500">Kode Resi Anda</p>
            {{-- Spasi antar blok membuat kode jauh lebih mudah dibaca ulang
                 dari layar maupun dari kertas. --}}
            <p class="mt-1 font-mono text-2xl font-bold tracking-[0.2em] text-primary sm:text-3xl" data-copy-source>{{ $kode }}</p>

            <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Simpan kode ini. Kode resi adalah satu-satunya cara melacak status permohonan Anda.
            </p>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <button type="button" data-copy-button data-copy-value="{{ $kode }}"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    Salin Kode
                </button>
                <button type="button" onclick="window.print()"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    Cetak Bukti
                </button>
                <a href="{{ route('lacak.index') }}"
                   class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    Lacak Permohonan
                </a>
            </div>
        </div>
    </div>
@endsection
```

- [ ] **Step 6: Tambahkan JavaScript tombol salin**

Di akhir `resources/js/app.js`:

```js
// Tombol salin kode resi. Bila clipboard API ditolak browser, teks kode tetap
// terlihat di layar sehingga pemohon masih bisa menyalinnya manual.
document.querySelectorAll('[data-copy-button]').forEach((button) => {
    button.addEventListener('click', async () => {
        const value = button.dataset.copyValue
        if (!value || !navigator.clipboard) return

        try {
            await navigator.clipboard.writeText(value)
            const original = button.textContent
            button.textContent = 'Tersalin!'
            setTimeout(() => { button.textContent = original }, 1500)
        } catch {
            // Diamkan: pemohon masih bisa menyalin manual dari layar.
        }
    })
})
```

- [ ] **Step 7: Jalankan test, bangun aset, dan commit**

```bash
php artisan test --filter=ApplicationSuccessPageTest
npm run build
php artisan test
git add -A
git commit -q -m "feat: halaman bukti pengajuan dengan kode resi, salin, dan cetak"
```

---

### Task 10: Halaman lacak permohonan

Verifikasi kode resi + 4 digit terakhir WhatsApp, lalu tampilkan status dan timeline.

**Files:**
- Create: `app/Http/Controllers/TrackingController.php`
- Create: `resources/views/lacak/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TrackingTest.php`

**Interfaces:**
- Consumes: `WhatsappNumber::lastFour()` (Task 4), `FormSubmission::statusLogs()` dan `FormSubmission::STATUSES` (Task 3).
- Produces: rute `lacak.index` (`GET /lacak`) dan `lacak.cari` (`POST /lacak`).

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/TrackingTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private function permohonan(): FormSubmission
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);
        $layanan->fields()->create(['label' => 'Nama Murid', 'type' => 'text', 'required' => true, 'sort_order' => 1]);

        $permohonan = $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'status' => 'diproses',
            'admin_note' => 'Berkas sedang diverifikasi petugas.',
            'data' => [$layanan->fields->first()->id => 'Rahasia Isian Formulir'],
        ]);
        $permohonan->recordStatus('diajukan', 'Permohonan diterima sistem.');
        $permohonan->recordStatus('diproses', 'Berkas sedang diverifikasi petugas.');

        return $permohonan;
    }

    public function test_halaman_lacak_menampilkan_formulir(): void
    {
        $this->get('/lacak')
            ->assertOk()
            ->assertSee('Lacak Permohonan')
            ->assertSee('Kode Resi');
    }

    public function test_kombinasi_benar_menampilkan_status_dan_timeline(): void
    {
        $this->permohonan();

        $this->post('/lacak', [
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'whatsapp_last4' => '7890',
        ])
            ->assertOk()
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertSee('Diproses')
            ->assertSee('Berkas sedang diverifikasi petugas.')
            ->assertSee('Permohonan diterima sistem.');
    }

    public function test_isian_formulir_tidak_ditampilkan_di_halaman_lacak(): void
    {
        // Verifikasi 4 digit terlalu lemah untuk membuka data pribadi lengkap.
        $this->permohonan();

        $this->post('/lacak', [
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'whatsapp_last4' => '7890',
        ])->assertOk()->assertDontSee('Rahasia Isian Formulir');
    }

    public function test_kode_resi_huruf_kecil_tetap_diterima(): void
    {
        $this->permohonan();

        $this->post('/lacak', [
            'receipt_code' => 'ptsp-2609-a7k3qx',
            'whatsapp_last4' => '7890',
        ])->assertOk()->assertSee('SPP Pengambilan Ijazah');
    }

    public function test_digit_salah_ditolak_dengan_pesan_umum(): void
    {
        $this->permohonan();

        $this->post('/lacak', [
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'whatsapp_last4' => '0000',
        ])->assertSessionHasErrors(['receipt_code' => 'Kode resi atau nomor tidak cocok.']);
    }

    public function test_kode_resi_tidak_dikenal_ditolak_dengan_pesan_yang_sama(): void
    {
        $this->permohonan();

        $this->post('/lacak', [
            'receipt_code' => 'PTSP-2609-ZZZZZZ',
            'whatsapp_last4' => '7890',
        ])->assertSessionHasErrors(['receipt_code' => 'Kode resi atau nomor tidak cocok.']);
    }

    public function test_digit_bukan_empat_angka_ditolak(): void
    {
        $this->post('/lacak', [
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'whatsapp_last4' => 'abcd',
        ])->assertSessionHasErrors('whatsapp_last4');
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=TrackingTest`
Expected: FAIL — rute `/lacak` masih stub yang mengembalikan teks polos.

- [ ] **Step 3: Buat controller**

File: `app/Http/Controllers/TrackingController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use App\Support\WhatsappNumber;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function index()
    {
        return view('lacak.index', ['permohonan' => null]);
    }

    public function cari(Request $request)
    {
        $validated = $request->validate([
            'receipt_code' => ['required', 'string', 'max:20'],
            'whatsapp_last4' => ['required', 'digits:4'],
        ], [], [
            'receipt_code' => 'Kode Resi',
            'whatsapp_last4' => '4 Digit Terakhir WhatsApp',
        ]);

        $permohonan = FormSubmission::query()
            ->with(['form', 'statusLogs'])
            ->where('receipt_code', strtoupper(trim($validated['receipt_code'])))
            ->first();

        $cocok = $permohonan
            && WhatsappNumber::lastFour((string) $permohonan->applicant_whatsapp) === $validated['whatsapp_last4'];

        if (! $cocok) {
            // Satu pesan untuk kedua kemungkinan kegagalan: memberi tahu bagian
            // mana yang salah akan membantu penebakan.
            return back()
                ->withInput()
                ->withErrors(['receipt_code' => 'Kode resi atau nomor tidak cocok.']);
        }

        return view('lacak.index', ['permohonan' => $permohonan]);
    }
}
```

- [ ] **Step 4: Ganti stub rute dan pasang rate limit**

Di `routes/web.php`, ganti stub `lacak.index`:

```php
Route::get('/lacak', [TrackingController::class, 'index'])->name('lacak.index');
Route::post('/lacak', [TrackingController::class, 'cari'])
    ->middleware('throttle:10,1')
    ->name('lacak.cari');
```

Tambahkan `use App\Http\Controllers\TrackingController;` di bagian atas berkas.

- [ ] **Step 5: Buat halaman lacak**

File: `resources/views/lacak/index.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Lacak Permohonan')

@php
    $warnaStatus = [
        'diajukan' => 'bg-gray-100 text-gray-700',
        'diproses' => 'bg-amber-100 text-amber-800',
        'selesai' => 'bg-green-100 text-green-800',
        'ditolak' => 'bg-red-100 text-red-800',
    ];
@endphp

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-12">
        <h1 class="text-2xl font-bold text-gray-900">Lacak Permohonan</h1>
        <p class="mt-1 text-sm text-gray-600">
            Masukkan kode resi beserta 4 digit terakhir nomor WhatsApp yang Anda daftarkan.
        </p>

        <form method="POST" action="{{ route('lacak.cari') }}" class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="receipt_code" class="block text-sm font-medium text-gray-700">Kode Resi</label>
                    <input id="receipt_code" type="text" name="receipt_code" value="{{ old('receipt_code') }}" required
                           placeholder="PTSP-2609-A7K3QX"
                           class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 font-mono uppercase text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label for="whatsapp_last4" class="block text-sm font-medium text-gray-700">4 Digit Terakhir WA</label>
                    <input id="whatsapp_last4" type="text" name="whatsapp_last4" value="{{ old('whatsapp_last4') }}" required
                           inputmode="numeric" maxlength="4" placeholder="7890"
                           class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                </div>
            </div>

            @if ($errors->any())
                <p class="mt-3 text-sm font-medium text-red-600">{{ $errors->first() }}</p>
            @endif

            <button type="submit"
                    class="mt-5 w-full rounded-lg bg-primary px-5 py-3 font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 sm:w-auto">
                Cek Status
            </button>
        </form>

        @if ($permohonan)
            <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm text-gray-500">{{ $permohonan->receipt_code }}</p>
                        <h2 class="mt-1 text-lg font-semibold text-gray-900">{{ $permohonan->form->title }}</h2>
                        <p class="mt-0.5 text-sm text-gray-500">
                            Diajukan {{ $permohonan->created_at->translatedFormat('d F Y, H:i') }}
                        </p>
                    </div>
                    {{-- Badge selalu memuat teks, bukan hanya warna: perbedaan
                         "Selesai" dan "Ditolak" tidak boleh bergantung pada
                         kemampuan membedakan merah dan hijau. --}}
                    <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $warnaStatus[$permohonan->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ \App\Models\FormSubmission::STATUSES[$permohonan->status] ?? $permohonan->status }}
                    </span>
                </div>

                @if ($permohonan->admin_note)
                    <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700">
                        <span class="font-medium text-gray-900">Catatan petugas:</span>
                        {{ $permohonan->admin_note }}
                    </div>
                @endif

                <h3 class="mt-6 text-sm font-semibold text-gray-900">Riwayat</h3>
                <ol class="mt-3 space-y-4 border-l-2 border-gray-200 pl-5">
                    @foreach ($permohonan->statusLogs as $log)
                        <li class="relative">
                            <span class="absolute -left-[27px] top-1.5 h-3 w-3 rounded-full bg-primary ring-4 ring-white"></span>
                            <p class="text-sm font-medium text-gray-900">
                                {{ \App\Models\FormSubmission::STATUSES[$log->status] ?? $log->status }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $log->created_at->translatedFormat('d F Y, H:i') }}</p>
                            @if ($log->note)
                                <p class="mt-1 text-sm text-gray-600">{{ $log->note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>
@endsection
```

- [ ] **Step 6: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=TrackingTest`
Expected: PASS — 7 test lulus.

- [ ] **Step 7: Jalankan seluruh test dan commit**

```bash
php artisan test
git add -A
git commit -q -m "feat: halaman lacak permohonan dengan verifikasi resi dan timeline status"
```

---

### Task 11: Panel admin — Satuan Kerja

Master sederhana agar operator bisa menambah/mengurutkan satuan kerja sendiri.

**Files:**
- Create: `app/Filament/Resources/WorkUnits/WorkUnitResource.php`
- Create: `app/Filament/Resources/WorkUnits/Pages/ListWorkUnits.php`
- Create: `app/Filament/Resources/WorkUnits/Pages/CreateWorkUnit.php`
- Create: `app/Filament/Resources/WorkUnits/Pages/EditWorkUnit.php`
- Create: `app/Filament/Resources/WorkUnits/Schemas/WorkUnitForm.php`
- Create: `app/Filament/Resources/WorkUnits/Tables/WorkUnitsTable.php`
- Test: `tests/Feature/WorkUnitResourceTest.php`

**Interfaces:**
- Consumes: `App\Models\WorkUnit` (Task 2).
- Produces: grup navigasi Filament bernama `PTSP` yang juga dipakai Task 12 & 13.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/WorkUnitResourceTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkUnitResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_daftar_satuan_kerja_bisa_dibuka_admin(): void
    {
        WorkUnit::create(['name' => 'Tata Usaha (TU)', 'slug' => 'tata-usaha']);

        $this->actingAs(User::factory()->create())
            ->get('/admin/work-units')
            ->assertOk()
            ->assertSee('Tata Usaha (TU)');
    }

    public function test_tamu_tidak_bisa_membuka_halaman_satuan_kerja(): void
    {
        $this->get('/admin/work-units')->assertRedirect();
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=WorkUnitResourceTest`
Expected: FAIL — 404, resource belum ada.

- [ ] **Step 3: Buat resource lewat generator lalu sesuaikan**

```bash
php artisan make:filament-resource WorkUnit --generate
```

Generator membuat kerangka di `app/Filament/Resources/WorkUnits/`. Jika struktur berkas yang dihasilkan berbeda dari daftar di atas, IKUTI struktur generator — itu yang sesuai versi Filament terpasang.

- [ ] **Step 4: Sesuaikan `WorkUnitResource`**

```php
    protected static ?string $model = WorkUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'PTSP';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Satuan Kerja';

    protected static ?string $modelLabel = 'Satuan Kerja';

    protected static ?string $pluralModelLabel = 'Satuan Kerja';
```

- [ ] **Step 5: Isi skema form**

File: `app/Filament/Resources/WorkUnits/Schemas/WorkUnitForm.php` — isi `components([...])` dengan:

```php
                TextInput::make('name')
                    ->label('Nama Satuan Kerja')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->helperText('Dipakai pada filter katalog: /layanan?unit={slug}'),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0)
                    ->helperText('Angka lebih kecil tampil lebih dulu pada chip filter.'),
```

Import yang dibutuhkan: `Filament\Forms\Components\TextInput`, `Filament\Schemas\Components\Utilities\Set`, `Illuminate\Support\Str`.

- [ ] **Step 6: Isi tabel**

File: `app/Filament/Resources/WorkUnits/Tables/WorkUnitsTable.php` — isi dengan:

```php
            ->columns([
                TextColumn::make('sort_order')->label('Urutan')->sortable(),
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('services_count')->label('Jumlah Layanan')->counts('services'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
```

- [ ] **Step 7: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=WorkUnitResourceTest`
Expected: PASS — 2 test lulus.

Jika URL resource ternyata bukan `/admin/work-units`, jalankan `php artisan route:list --path=admin | grep -i work` dan sesuaikan URL di test.

- [ ] **Step 8: Jalankan seluruh test dan commit**

```bash
php artisan test
git add -A
git commit -q -m "feat: pengelolaan satuan kerja di panel admin"
```

---

### Task 12: Panel admin — Layanan

Mengubah `FormResource` yang diwarisi CMS-web menjadi pengelola Layanan bertab, lengkap dengan pengurutan geser.

**Files:**
- Modify: `app/Filament/Resources/Forms/FormResource.php`
- Modify: `app/Filament/Resources/Forms/Schemas/FormSchema.php`
- Modify: `app/Filament/Resources/Forms/Tables/FormsTable.php`
- Test: `tests/Feature/ServiceResourceTest.php`

**Interfaces:**
- Consumes: kolom layanan & `FormField::TYPES` (Task 2), grup navigasi `PTSP` (Task 11).
- Produces: panel "Layanan" di `/admin/forms` yang hanya menampilkan `is_service = true`.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/ServiceResourceTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_hanya_menampilkan_layanan_bukan_form_biasa(): void
    {
        Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published', 'is_service' => true]);
        Form::create(['title' => 'Form Kontak Biasa', 'slug' => 'kontak', 'status' => 'published', 'is_service' => false]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/forms')
            ->assertOk()
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertDontSee('Form Kontak Biasa');
    }

    public function test_halaman_edit_layanan_bisa_dibuka(): void
    {
        $layanan = Form::create(['title' => 'SPP Legalisasi', 'slug' => 'legalisasi', 'status' => 'published']);

        $this->actingAs(User::factory()->create())
            ->get("/admin/forms/{$layanan->id}/edit")
            ->assertOk()
            ->assertSee('Informasi Layanan')
            ->assertSee('Rincian &amp; Syarat', false)
            ->assertSee('Field Formulir');
    }

    public function test_tamu_tidak_bisa_membuka_daftar_layanan(): void
    {
        $this->get('/admin/forms')->assertRedirect();
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=ServiceResourceTest`
Expected: FAIL — test pertama gagal karena form biasa masih ikut tampil; test kedua gagal karena tab belum ada.

- [ ] **Step 3: Perbarui `FormResource`**

Ganti properti label dan tambahkan penyaringan query. Import tambahan: `Illuminate\Database\Eloquent\Builder`.

```php
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'PTSP';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Layanan';

    protected static ?string $modelLabel = 'Layanan';

    protected static ?string $pluralModelLabel = 'Layanan';

    /**
     * Panel ini hanya mengurus layanan PTSP. Form biasa (is_service = false)
     * sengaja disembunyikan supaya operator tidak bingung melihat dua jenis
     * data dalam satu daftar.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_service', true);
    }
```

- [ ] **Step 4: Susun ulang `FormSchema` menjadi empat tab**

Ganti seluruh isi `configure()` di `app/Filament/Resources/Forms/Schemas/FormSchema.php`:

```php
        return $schema
            ->components([
                Tabs::make('Layanan')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Informasi Layanan')->schema([
                            TextInput::make('title')
                                ->label('Nama Layanan')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->helperText('Alamat layanan: /layanan/{slug}. Otomatis dari nama, bisa diubah.'),
                            TextInput::make('organizer')
                                ->label('Penyelenggara')
                                ->default('PTSP')
                                ->required()
                                ->maxLength(100)
                                ->helperText('Bagian kiri label kartu, mis. "PTSP" atau "Panitia PPDB".'),
                            Select::make('work_unit_id')
                                ->label('Satuan Kerja')
                                ->relationship('workUnit', 'name')
                                ->searchable()
                                ->preload()
                                ->helperText('Bagian kanan label kartu, sekaligus dasar filter di katalog.'),
                            TextInput::make('duration_text')
                                ->label('Waktu Layanan')
                                ->maxLength(50)
                                ->placeholder('30 Menit')
                                ->helperText('Ditulis bebas karena satuannya bisa menit, jam, atau hari.'),
                            TextInput::make('fee_text')
                                ->label('Biaya')
                                ->default('Gratis')
                                ->required()
                                ->maxLength(50),
                            Textarea::make('description')
                                ->label('Ringkasan singkat (opsional)')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])->columns(2),

                        Tabs\Tab::make('Rincian & Syarat')->schema([
                            RichEditor::make('requirements')
                                ->label('Persyaratan & Alur')
                                ->helperText('Tampil di halaman rincian layanan.')
                                ->columnSpanFull(),
                            RichEditor::make('legal_basis')
                                ->label('Dasar Hukum (opsional)')
                                ->columnSpanFull(),
                        ]),

                        Tabs\Tab::make('Field Formulir')->schema([
                            Repeater::make('fields')
                                ->label('Kelengkapan Berkas & Data Isian')
                                ->relationship()
                                ->orderColumn('sort_order')
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Isian baru')
                                ->addActionLabel('Tambah isian / berkas')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('label')
                                        ->label('Nama isian')
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('type')
                                        ->label('Tipe')
                                        ->options(FormField::TYPES)
                                        ->default('text')
                                        ->required()
                                        ->live(),
                                    TagsInput::make('options')
                                        ->label('Pilihan')
                                        ->helperText('Ketik lalu Enter untuk tiap pilihan.')
                                        ->visible(fn (Get $get): bool => in_array($get('type'), ['select', 'checkbox']))
                                        ->columnSpanFull(),
                                    TextInput::make('help_text')
                                        ->label('Keterangan (opsional)')
                                        ->maxLength(255)
                                        ->helperText('Tampil sebagai teks kecil di bawah isian.')
                                        ->columnSpanFull(),
                                    Toggle::make('required')
                                        ->label('Wajib diisi')
                                        ->default(false),
                                    Toggle::make('is_unique')
                                        ->label('Tidak boleh duplikat')
                                        ->helperText('Aktifkan untuk isian unik seperti NIS agar satu nilai hanya bisa dipakai sekali.')
                                        ->default(false),
                                ]),
                        ]),

                        Tabs\Tab::make('Pengaturan')->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options(['draft' => 'Draft', 'published' => 'Terbit'])
                                ->default('draft')
                                ->required()
                                ->helperText('Hanya layanan berstatus Terbit yang muncul di katalog publik.'),
                            TextInput::make('sort_order')
                                ->label('Urutan')
                                ->numeric()
                                ->default(0)
                                ->helperText('Menentukan nomor kartu (#1, #2, ...) di katalog.'),
                            Textarea::make('success_message')
                                ->label('Pesan setelah kirim (opsional)')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])->columns(2),
                    ]),
            ]);
```

Import tambahan yang dibutuhkan: `App\Models\FormField`, `Filament\Forms\Components\RichEditor`, `Filament\Schemas\Components\Tabs`.

Jika `Filament\Schemas\Components\Tabs` tidak ditemukan, cari lokasi sebenarnya dengan `grep -rl "class Tabs" vendor/filament/` dan pakai namespace yang ditemukan.

- [ ] **Step 5: Perbarui `FormsTable`**

Ganti isi `configure()` di `app/Filament/Resources/Forms/Tables/FormsTable.php`:

```php
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Nama Layanan')
                    ->searchable(),
                TextColumn::make('workUnit.name')
                    ->label('Satuan Kerja')
                    ->badge()
                    ->placeholder('Umum'),
                TextColumn::make('duration_text')
                    ->label('Waktu')
                    ->placeholder('Menyesuaikan'),
                TextColumn::make('fee_text')
                    ->label('Biaya'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors(['success' => 'published', 'gray' => 'draft']),
                TextColumn::make('submissions_count')
                    ->label('Permohonan')
                    ->counts('submissions'),
            ])
            ->defaultSort('sort_order')
            // Urutan geser di sini langsung menentukan nomor kartu di katalog publik.
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
```

- [ ] **Step 6: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=ServiceResourceTest`
Expected: PASS — 3 test lulus.

- [ ] **Step 7: Jalankan seluruh test dan commit**

Perhatikan `tests/Feature/FormResourceTest.php` bawaan CMS-web: bila ia mengandalkan label lama ("Form", "Pertanyaan") atau menguji form biasa lewat panel, perbarui test tersebut agar sesuai label & penyaringan baru. Jangan menghapusnya.

```bash
php artisan test
git add -A
git commit -q -m "feat: panel Layanan bertab dengan pengurutan geser dan field builder"
```

---

### Task 13: Panel admin — Permohonan

Daftar permohonan, ubah status dengan riwayat, unduh berkas ber-autentikasi, dan export CSV.

**Files:**
- Create: `app/Actions/UpdateSubmissionStatus.php`
- Create: `app/Http/Controllers/SubmissionFileController.php`
- Create: `app/Filament/Resources/Submissions/SubmissionResource.php` (+ Pages & Tables hasil generator)
- Modify: `resources/views/filament/form-submission-detail.blade.php`
- Modify: `app/Support/FormCsvExporter.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/UpdateSubmissionStatusTest.php`
- Test: `tests/Feature/SubmissionFileDownloadTest.php`
- Test: `tests/Feature/SubmissionResourceTest.php`

**Interfaces:**
- Consumes: `FormSubmission::recordStatus()` & `STATUSES` (Task 3).
- Produces:
  - `App\Actions\UpdateSubmissionStatus::handle(FormSubmission $submission, string $status, ?string $note = null, ?int $userId = null): void`
  - Rute `permohonan.berkas` (`GET /permohonan/{submission}/berkas/{field}`).

- [ ] **Step 1: Tulis test perubahan status yang gagal**

File: `tests/Feature/UpdateSubmissionStatusTest.php`

```php
<?php

namespace Tests\Feature;

use App\Actions\UpdateSubmissionStatus;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmissionStatusTest extends TestCase
{
    use RefreshDatabase;

    private function permohonan(): FormSubmission
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);

        return $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [],
        ]);
    }

    public function test_mengubah_status_memperbarui_kolom_dan_menulis_riwayat(): void
    {
        $permohonan = $this->permohonan();
        $operator = User::factory()->create();

        app(UpdateSubmissionStatus::class)->handle($permohonan, 'selesai', 'Ijazah siap diambil di TU.', $operator->id);

        $permohonan->refresh();
        $this->assertSame('selesai', $permohonan->status);
        $this->assertSame('Ijazah siap diambil di TU.', $permohonan->admin_note);

        $log = $permohonan->statusLogs()->latest('id')->first();
        $this->assertSame('selesai', $log->status);
        $this->assertSame($operator->id, $log->user_id);
    }

    public function test_status_tidak_dikenal_ditolak(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(UpdateSubmissionStatus::class)->handle($this->permohonan(), 'entah-apa');
    }
}
```

- [ ] **Step 2: Tulis test unduh berkas yang gagal**

File: `tests/Feature/SubmissionFileDownloadTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionFileDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function siapkan(): array
    {
        Storage::fake('local');

        $layanan = Form::create(['title' => 'SPP Ijazah Hilang', 'slug' => 'ijazah-hilang', 'status' => 'published']);
        $field = $layanan->fields()->create(['label' => 'Surat Kehilangan', 'type' => 'file', 'required' => true, 'sort_order' => 1]);

        $path = UploadedFile::fake()->create('surat.pdf', 50, 'application/pdf')->store('permohonan/'.$layanan->id, 'local');

        $permohonan = $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [$field->id => $path],
        ]);

        return [$permohonan, $field];
    }

    public function test_tamu_tidak_bisa_mengunduh_berkas(): void
    {
        [$permohonan, $field] = $this->siapkan();

        $this->get("/permohonan/{$permohonan->id}/berkas/{$field->id}")->assertForbidden();
    }

    public function test_operator_yang_login_bisa_mengunduh_berkas(): void
    {
        [$permohonan, $field] = $this->siapkan();

        $this->actingAs(User::factory()->create())
            ->get("/permohonan/{$permohonan->id}/berkas/{$field->id}")
            ->assertOk()
            ->assertDownload();
    }

    public function test_field_dari_layanan_lain_ditolak(): void
    {
        [$permohonan] = $this->siapkan();

        $lain = Form::create(['title' => 'Lain', 'slug' => 'lain', 'status' => 'published']);
        $fieldLain = $lain->fields()->create(['label' => 'X', 'type' => 'file', 'sort_order' => 1]);

        $this->actingAs(User::factory()->create())
            ->get("/permohonan/{$permohonan->id}/berkas/{$fieldLain->id}")
            ->assertNotFound();
    }
}
```

- [ ] **Step 3: Jalankan kedua test untuk memastikan gagal**

Run: `php artisan test --filter='UpdateSubmissionStatusTest|SubmissionFileDownloadTest'`
Expected: FAIL — kelas dan rute belum ada.

- [ ] **Step 4: Buat action perubahan status**

File: `app/Actions/UpdateSubmissionStatus.php`

```php
<?php

namespace App\Actions;

use App\Models\FormSubmission;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya jalan mengubah status permohonan. Dipisahkan dari Filament
 * supaya perubahan status selalu diiringi pencatatan riwayat — riwayat itulah
 * yang dibaca pemohon di halaman lacak.
 */
class UpdateSubmissionStatus
{
    public function handle(FormSubmission $submission, string $status, ?string $note = null, ?int $userId = null): void
    {
        if (! array_key_exists($status, FormSubmission::STATUSES)) {
            throw new \InvalidArgumentException("Status tidak dikenal: {$status}");
        }

        DB::transaction(function () use ($submission, $status, $note, $userId): void {
            $submission->update([
                'status' => $status,
                'admin_note' => $note,
            ]);

            $submission->recordStatus($status, $note, $userId);
        });
    }
}
```

- [ ] **Step 5: Buat controller unduh berkas dan rutenya**

File: `app/Http/Controllers/SubmissionFileController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SubmissionFileController extends Controller
{
    /**
     * Berkas permohonan berisi ijazah, KTP, dan surat kehilangan sehingga
     * disimpan di disk privat. Penjagaan ditulis di controller (bukan middleware
     * `auth`) agar responsnya pasti 403 dan tidak bergantung pada keberadaan
     * rute login bernama.
     */
    public function download(FormSubmission $submission, FormField $field)
    {
        abort_unless(Auth::check(), 403);
        abort_unless($field->form_id === $submission->form_id, 404);

        $path = $submission->data[$field->id] ?? null;
        abort_if(! is_string($path) || $path === '', 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
```

Di `routes/web.php`, sebelum catch-all:

```php
Route::get('/permohonan/{submission}/berkas/{field}', [SubmissionFileController::class, 'download'])
    ->name('permohonan.berkas');
```

Tambahkan `use App\Http\Controllers\SubmissionFileController;`.

- [ ] **Step 6: Jalankan kedua test untuk memastikan lulus**

Run: `php artisan test --filter='UpdateSubmissionStatusTest|SubmissionFileDownloadTest'`
Expected: PASS — 5 test lulus.

- [ ] **Step 7: Tulis test resource Permohonan yang gagal**

File: `tests/Feature/SubmissionResourceTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_permohonan_menampilkan_resi_dan_pemohon(): void
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);
        $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [],
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/form-submissions')
            ->assertOk()
            ->assertSee('PTSP-2609-A7K3QX')
            ->assertSee('Budi Santoso')
            ->assertSee('SPP Pengambilan Ijazah');
    }

    public function test_tamu_tidak_bisa_membuka_daftar_permohonan(): void
    {
        $this->get('/admin/form-submissions')->assertRedirect();
    }
}
```

- [ ] **Step 8: Hasilkan resource dan sesuaikan**

```bash
php artisan make:filament-resource FormSubmission --generate
```

Sesuaikan properti pada resource yang dihasilkan:

```php
    protected static string|\UnitEnum|null $navigationGroup = 'PTSP';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Permohonan';

    protected static ?string $modelLabel = 'Permohonan';

    protected static ?string $pluralModelLabel = 'Permohonan';
```

Karena permohonan hanya lahir dari pengajuan publik, hapus halaman `Create` dari `getPages()` dan hapus berkas halamannya.

Jika URL yang dihasilkan bukan `/admin/form-submissions`, jalankan `php artisan route:list --path=admin | grep -i submission` lalu sesuaikan URL di test Step 7.

- [ ] **Step 9: Isi tabel Permohonan**

Pada berkas tabel hasil generator, isi `configure()` dengan:

```php
        return $table
            ->columns([
                TextColumn::make('receipt_code')
                    ->label('Kode Resi')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('applicant_name')
                    ->label('Pemohon')
                    ->searchable(),
                TextColumn::make('form.title')
                    ->label('Layanan')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FormSubmission::STATUSES[$state] ?? $state)
                    ->colors([
                        'gray' => 'diajukan',
                        'warning' => 'diproses',
                        'success' => 'selesai',
                        'danger' => 'ditolak',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(FormSubmission::STATUSES),
                SelectFilter::make('form_id')
                    ->label('Layanan')
                    ->relationship('form', 'title'),
            ])
            ->recordActions([
                Action::make('ubahStatus')
                    ->label('Ubah Status')
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('Ubah Status Permohonan')
                    ->schema([
                        Select::make('status')
                            ->label('Status Baru')
                            ->options(FormSubmission::STATUSES)
                            ->required(),
                        Textarea::make('admin_note')
                            ->label('Catatan untuk pemohon')
                            ->rows(3)
                            ->helperText('Tampil di halaman lacak, mis. "Ijazah siap diambil di TU".'),
                    ])
                    ->fillForm(fn (FormSubmission $record): array => [
                        'status' => $record->status,
                        'admin_note' => $record->admin_note,
                    ])
                    ->action(function (FormSubmission $record, array $data, UpdateSubmissionStatus $updater): void {
                        $updater->handle($record, $data['status'], $data['admin_note'] ?? null, Auth::id());
                    }),
                ViewAction::make()
                    ->label('Detail')
                    ->modalHeading('Detail Permohonan')
                    ->modalContent(fn ($record): View => view(
                        'filament.form-submission-detail',
                        ['submission' => $record],
                    )),
            ]);
```

Import yang dibutuhkan berkas tabel ini: `App\Actions\UpdateSubmissionStatus`, `App\Models\FormSubmission`, `App\Support\FormCsvExporter`, `Filament\Actions\Action`, `Filament\Actions\ViewAction`, `Filament\Forms\Components\Select`, `Filament\Forms\Components\Textarea`, `Filament\Tables\Columns\TextColumn`, `Filament\Tables\Filters\SelectFilter`, `Filament\Tables\Table`, `Illuminate\Contracts\View\View`, `Illuminate\Support\Facades\Auth`, `Symfony\Component\HttpFoundation\StreamedResponse`.

Jika `Action::make(...)->schema([...])` ditolak versi Filament terpasang, ganti `->schema(` menjadi `->form(` pada action tersebut.

- [ ] **Step 10: Perbarui tampilan detail permohonan**

Ganti seluruh isi `resources/views/filament/form-submission-detail.blade.php`:

```blade
{{-- Detail satu permohonan di panel operator. Variabel: $submission. --}}
<div class="space-y-5 text-sm">
    <div>
        <h3 class="font-semibold text-gray-900">Identitas Pemohon</h3>
        <dl class="mt-2 grid gap-2 sm:grid-cols-2">
            <div><dt class="text-gray-500">Kode Resi</dt><dd class="font-mono">{{ $submission->receipt_code }}</dd></div>
            <div><dt class="text-gray-500">Nama</dt><dd>{{ $submission->applicant_name }}</dd></div>
            <div><dt class="text-gray-500">WhatsApp</dt><dd>{{ $submission->applicant_whatsapp }}</dd></div>
            <div><dt class="text-gray-500">Email</dt><dd>{{ $submission->applicant_email }}</dd></div>
        </dl>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900">Isian &amp; Berkas</h3>
        <div class="mt-2 space-y-3">
            @foreach ($submission->form->fields as $field)
                @php $value = $submission->data[$field->id] ?? null; @endphp
                <div>
                    <div class="font-medium text-gray-500">{{ $field->label }}</div>
                    <div class="mt-0.5 text-gray-900">
                        @if ($field->type === 'file' && $value)
                            <a href="{{ route('permohonan.berkas', ['submission' => $submission->id, 'field' => $field->id]) }}"
                               class="text-primary underline">Unduh berkas</a>
                        @elseif (is_array($value))
                            {{ implode(', ', $value) ?: '—' }}
                        @else
                            {{ $value !== null && $value !== '' ? $value : '—' }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900">Riwayat Status</h3>
        <ol class="mt-2 space-y-2">
            @foreach ($submission->statusLogs as $log)
                <li>
                    <span class="font-medium">{{ \App\Models\FormSubmission::STATUSES[$log->status] ?? $log->status }}</span>
                    <span class="text-gray-500">— {{ $log->created_at->format('d M Y H:i') }}</span>
                    @if ($log->note)
                        <div class="text-gray-600">{{ $log->note }}</div>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</div>
```

- [ ] **Step 11: Tambahkan export CSV seluruh permohonan**

Baca `app/Support/FormCsvExporter.php` lebih dulu, lalu tambahkan method `downloadAll(): StreamedResponse` yang MENGIKUTI struktur, delimiter (`;`), dan penulisan BOM UTF-8 yang persis sama dengan method yang sudah ada. Kolomnya, berurutan:

`Kode Resi`, `Layanan`, `Nama Pemohon`, `WhatsApp`, `Email`, `Status`, `Catatan`, `Tanggal Pengajuan`

Baris diambil dari `FormSubmission::with('form')->latest('created_at')->cursor()`. Nilai `Status` memakai label dari `FormSubmission::STATUSES`.

Lalu pasang di `toolbarActions` tabel Permohonan:

```php
            ->toolbarActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse => FormCsvExporter::downloadAll()),
            ]);
```

- [ ] **Step 12: Jalankan test resource untuk memastikan lulus**

Run: `php artisan test --filter=SubmissionResourceTest`
Expected: PASS — 2 test lulus.

- [ ] **Step 13: Jalankan seluruh test dan commit**

```bash
php artisan test
git add -A
git commit -q -m "feat: panel Permohonan dengan ubah status berriwayat dan unduh berkas ber-autentikasi"
```

---

### Task 14: Data awal (seeder)

Mengisi tiga satuan kerja dan lima layanan beserta field-nya, idempotent.

**Files:**
- Create: `database/seeders/PtspServiceSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/PtspServiceSeederTest.php`

**Interfaces:**
- Consumes: `WorkUnit`, `Form`, `FormField` (Task 2).
- Produces: `Database\Seeders\PtspServiceSeeder` yang aman dijalankan berulang.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/PtspServiceSeederTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\WorkUnit;
use Database\Seeders\PtspServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PtspServiceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_satuan_kerja_dan_lima_layanan(): void
    {
        $this->seed(PtspServiceSeeder::class);

        $this->assertSame(3, WorkUnit::count());
        $this->assertSame(5, Form::query()->services()->count());

        $layanan = Form::where('slug', 'surat-pengganti-ijazah-hilang')->sole();
        $this->assertSame('SPP Surat Pengganti Ijazah Hilang', $layanan->title);
        $this->assertSame('1 Jam', $layanan->duration_text);
        $this->assertSame('Gratis', $layanan->fee_text);
        $this->assertSame('published', $layanan->status);
        $this->assertSame('Tata Usaha (TU)', $layanan->workUnit->name);
        $this->assertSame(
            ['Nama', 'Upload Surat Kehilangan', 'Fotokopi Ijazah', 'Foto 3x4'],
            $layanan->fields->pluck('label')->all(),
        );
    }

    public function test_seeder_aman_dijalankan_dua_kali(): void
    {
        $this->seed(PtspServiceSeeder::class);
        $this->seed(PtspServiceSeeder::class);

        $this->assertSame(3, WorkUnit::count());
        $this->assertSame(5, Form::query()->services()->count());
        $this->assertSame(4, Form::where('slug', 'surat-pengganti-ijazah-hilang')->sole()->fields()->count());
    }

    public function test_seeder_tidak_menimpa_perubahan_operator(): void
    {
        $this->seed(PtspServiceSeeder::class);

        $layanan = Form::where('slug', 'pengambilan-ijazah')->sole();
        $layanan->update(['duration_text' => '15 Menit', 'requirements' => '<p>Diubah operator.</p>']);

        $this->seed(PtspServiceSeeder::class);

        $layanan->refresh();
        $this->assertSame('15 Menit', $layanan->duration_text);
        $this->assertSame('<p>Diubah operator.</p>', $layanan->requirements);
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=PtspServiceSeederTest`
Expected: FAIL — `Class "Database\Seeders\PtspServiceSeeder" not found`.

- [ ] **Step 3: Buat seeder**

File: `database/seeders/PtspServiceSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

/**
 * Data awal PTSP. Idempotent: dicocokkan berdasarkan slug dan TIDAK pernah
 * menimpa layanan yang sudah ada, supaya perubahan operator tidak hilang
 * bila seeder dijalankan ulang setelah deploy.
 */
class PtspServiceSeeder extends Seeder
{
    private const SATUAN_KERJA = [
        ['name' => 'Tata Usaha (TU)', 'slug' => 'tata-usaha', 'sort_order' => 1],
        ['name' => 'Kesiswaan', 'slug' => 'kesiswaan', 'sort_order' => 2],
        ['name' => 'P2M2', 'slug' => 'p2m2', 'sort_order' => 3],
    ];

    private const LAYANAN = [
        [
            'slug' => 'pengambilan-ijazah',
            'title' => 'SPP Pengambilan Ijazah',
            'duration_text' => '30 Menit',
            'sort_order' => 1,
            'fields' => [
                ['label' => 'Nama Murid', 'type' => 'text', 'required' => true],
            ],
        ],
        [
            'slug' => 'legalisasi-ijazah-online',
            'title' => 'SPP Legalisasi Ijazah Online',
            'duration_text' => '1-2 Hari',
            'sort_order' => 2,
            'fields' => [
                ['label' => 'Tanda terima pengambilan ijazah/STTB', 'type' => 'text', 'required' => true],
                ['label' => 'Nama', 'type' => 'text', 'required' => true],
                ['label' => 'Upload Ijazah', 'type' => 'file', 'required' => false],
            ],
        ],
        [
            'slug' => 'surat-pengganti-ijazah-hilang',
            'title' => 'SPP Surat Pengganti Ijazah Hilang',
            'duration_text' => '1 Jam',
            'sort_order' => 3,
            'fields' => [
                ['label' => 'Nama', 'type' => 'text', 'required' => true],
                ['label' => 'Upload Surat Kehilangan', 'type' => 'file', 'required' => true],
                ['label' => 'Fotokopi Ijazah', 'type' => 'file', 'required' => true],
                ['label' => 'Foto 3x4', 'type' => 'file', 'required' => true],
            ],
        ],
        [
            'slug' => 'surat-pengganti-ijazah-rusak',
            'title' => 'SPP Surat Pengganti Ijazah Rusak',
            'duration_text' => '58 Menit',
            'sort_order' => 4,
            'fields' => [
                ['label' => 'Nama', 'type' => 'text', 'required' => true],
                ['label' => 'Ijazah Asli', 'type' => 'file', 'required' => true],
                ['label' => 'Ijazah Fotokopi', 'type' => 'file', 'required' => true],
            ],
        ],
        [
            'slug' => 'kesalahan-penulisan-ijazah',
            'title' => 'SPP Kesalahan Penulisan Ijazah',
            'duration_text' => '1 Jam',
            'sort_order' => 5,
            'fields' => [
                ['label' => 'Nama', 'type' => 'text', 'required' => true],
                ['label' => 'Ijazah Asli', 'type' => 'file', 'required' => true],
                ['label' => 'Ijazah Fotokopi', 'type' => 'file', 'required' => true],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::SATUAN_KERJA as $satuan) {
            WorkUnit::firstOrCreate(['slug' => $satuan['slug']], $satuan);
        }

        $tataUsaha = WorkUnit::where('slug', 'tata-usaha')->sole();

        foreach (self::LAYANAN as $definisi) {
            $layanan = Form::firstOrCreate(
                ['slug' => $definisi['slug']],
                [
                    'title' => $definisi['title'],
                    'status' => 'published',
                    'is_service' => true,
                    'organizer' => 'PTSP',
                    'work_unit_id' => $tataUsaha->id,
                    'duration_text' => $definisi['duration_text'],
                    'fee_text' => 'Gratis',
                    'sort_order' => $definisi['sort_order'],
                    'requirements' => '<p>Rincian persyaratan layanan ini belum diisi. Silakan ubah lewat menu Layanan di panel admin.</p>',
                    'success_message' => 'Permohonan Anda telah kami terima.',
                ],
            );

            // Field hanya dibuat saat layanan baru lahir. Bila operator sudah
            // menyesuaikan daftar isian, seeder tidak boleh menambahinya lagi.
            if (! $layanan->wasRecentlyCreated) {
                continue;
            }

            foreach ($definisi['fields'] as $urutan => $field) {
                $layanan->fields()->create([
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'required' => $field['required'],
                    'sort_order' => $urutan + 1,
                ]);
            }
        }
    }
}
```

- [ ] **Step 4: Daftarkan di `DatabaseSeeder`**

Tambahkan `PtspServiceSeeder::class` ke pemanggilan `$this->call([...])` di `database/seeders/DatabaseSeeder.php`, setelah seeder admin.

- [ ] **Step 5: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=PtspServiceSeederTest`
Expected: PASS — 3 test lulus.

- [ ] **Step 6: Isi database pengembangan dan verifikasi katalog**

```bash
php artisan db:seed --class=PtspServiceSeeder
php artisan serve --port=8123 &
sleep 3
curl -s http://127.0.0.1:8123/layanan | grep -c 'SPP Surat Pengganti Ijazah Hilang'
kill %1
```
Expected: `1`

- [ ] **Step 7: Jalankan seluruh test dan commit**

```bash
php artisan test
git add -A
git commit -q -m "feat: data awal tiga satuan kerja dan lima layanan ijazah"
```

---

### Task 15: Navigasi & beranda

Menyembunyikan modul CMS yang tidak dipakai, menambahkan menu publik, dan menampilkan layanan di beranda.

**Files:**
- Modify: `app/Filament/Resources/Slides/SlideResource.php`
- Modify: `app/Filament/Resources/Stats/StatResource.php`
- Modify: `app/Filament/Resources/LeaderQuotes/LeaderQuoteResource.php`
- Modify: `app/Filament/Resources/GalleryItems/GalleryItemResource.php`
- Modify: `app/Filament/Resources/Posts/PostResource.php`
- Modify: `app/Filament/Resources/PostCategories/PostCategoryResource.php`
- Modify: `app/Settings/GeneralSettings.php`
- Modify: `app/Http/Controllers/HomeController.php`
- Modify: `resources/views/home.blade.php`
- Create: `resources/views/partials/layanan.blade.php`
- Modify: `database/seeders/PtspServiceSeeder.php`
- Test: `tests/Feature/PtspNavigationTest.php`

**Interfaces:**
- Consumes: `partials/service-card.blade.php` (Task 5), `PtspServiceSeeder` (Task 14).
- Produces: kunci section beranda baru `layanan`.

- [ ] **Step 1: Tulis test yang gagal**

File: `tests/Feature/PtspNavigationTest.php`

```php
<?php

namespace Tests\Feature;

use App\Filament\Resources\GalleryItems\GalleryItemResource;
use App\Filament\Resources\LeaderQuotes\LeaderQuoteResource;
use App\Filament\Resources\PostCategories\PostCategoryResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Slides\SlideResource;
use App\Filament\Resources\Stats\StatResource;
use App\Models\Menu;
use Database\Seeders\PtspServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PtspNavigationTest extends TestCase
{
    use RefreshDatabase;

    public static function resourceTersembunyiProvider(): array
    {
        return [
            [SlideResource::class],
            [StatResource::class],
            [LeaderQuoteResource::class],
            [GalleryItemResource::class],
            [PostResource::class],
            [PostCategoryResource::class],
        ];
    }

    #[DataProvider('resourceTersembunyiProvider')]
    public function test_modul_cms_yang_tidak_dipakai_tidak_muncul_di_navigasi(string $resource): void
    {
        $this->assertFalse($resource::shouldRegisterNavigation());
    }

    public function test_beranda_menampilkan_layanan(): void
    {
        $this->seed(PtspServiceSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertSee('SPP Surat Pengganti Ijazah Rusak');
    }

    public function test_seeder_menambahkan_menu_layanan_dan_lacak(): void
    {
        $this->seed(PtspServiceSeeder::class);

        $this->assertTrue(Menu::where('target', '/layanan')->exists());
        $this->assertTrue(Menu::where('target', '/lacak')->exists());
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=PtspNavigationTest`
Expected: FAIL — resource masih terdaftar di navigasi dan beranda belum memuat layanan.

- [ ] **Step 3: Sembunyikan enam resource CMS**

Tambahkan properti berikut ke masing-masing dari enam resource yang terdaftar di **Files**:

```php
    /**
     * Modul bawaan CMS-web yang tidak dipakai PTSP. Sengaja disembunyikan,
     * bukan dihapus: menghapusnya berarti menyentuh migrasi, seeder, dan
     * layout beranda sekaligus. Pembersihan dijadwalkan terpisah.
     */
    protected static bool $shouldRegisterNavigation = false;
```

- [ ] **Step 4: Tambahkan section `layanan` ke daftar section beranda**

Di `app/Settings/GeneralSettings.php`, tambahkan entri ke konstanta `HOME_SECTIONS`, tepat setelah `'hero'`:

```php
        'layanan' => 'Layanan PTSP',
```

- [ ] **Step 5: Sediakan data layanan untuk beranda**

Di `app/Http/Controllers/HomeController.php`, tambahkan import `App\Models\Form` dan blok berikut sebelum `return view(...)`:

```php
        $semuaLayanan = Form::query()
            ->services()
            ->published()
            ->ordered()
            ->with('workUnit')
            ->get();

        // Nomor kartu tetap global walau beranda hanya menampilkan enam teratas.
        $nomorLayanan = $semuaLayanan->pluck('id')->flip()->map(fn (int $index): int => $index + 1);
        $layananUnggulan = $semuaLayanan->take(6);
```

Lalu tambahkan `'layananUnggulan'` dan `'nomorLayanan'` ke daftar variabel yang dikirim ke view `home`.

- [ ] **Step 6: Buat partial section layanan**

File: `resources/views/partials/layanan.blade.php`

```blade
{{-- Section "Layanan PTSP" di beranda. Variabel: $layananUnggulan, $nomorLayanan. --}}
<section class="mx-auto max-w-6xl px-4 py-14">
    <div class="mb-8 flex items-end justify-between" data-reveal>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Layanan PTSP</h2>
            <p class="mt-1 text-sm text-gray-500">Ajukan permohonan secara daring, pantau statusnya lewat kode resi.</p>
        </div>
        <a href="{{ route('layanan.index') }}" class="hidden text-sm font-medium text-primary hover:underline sm:block">
            Lihat semua &rarr;
        </a>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal-group>
        @foreach ($layananUnggulan as $item)
            @include('partials.service-card', ['layanan' => $item, 'nomor' => $nomorLayanan[$item->id]])
        @endforeach
    </div>
</section>
```

- [ ] **Step 7: Pasang section di beranda**

Di `resources/views/home.blade.php`, tambahkan case baru di dalam `@switch($section['key'])`, tepat setelah blok `@case('hero')`:

```blade
            @case('layanan')
                {{-- Layanan PTSP. Hanya tampil bila ada layanan yang sudah terbit. --}}
                @if ($layananUnggulan->isNotEmpty())
                    @include('partials.layanan')
                @endif
                @break
```

- [ ] **Step 8: Seed menu publik**

Di `PtspServiceSeeder::run()`, tambahkan di bagian akhir:

```php
        // Menu publik. firstOrCreate berdasarkan target supaya operator boleh
        // mengganti label atau urutannya tanpa dikembalikan seeder.
        Menu::firstOrCreate(
            ['target' => '/layanan'],
            ['label' => 'Layanan', 'type' => 'url', 'sort_order' => 10],
        );
        Menu::firstOrCreate(
            ['target' => '/lacak'],
            ['label' => 'Lacak Permohonan', 'type' => 'url', 'sort_order' => 20],
        );
```

Tambahkan `use App\Models\Menu;` di bagian atas seeder.

- [ ] **Step 9: Jalankan test untuk memastikan lulus**

Run: `php artisan test --filter=PtspNavigationTest`
Expected: PASS — 8 test lulus (6 dari data provider + 2 lainnya).

- [ ] **Step 10: Pastikan normalisasi section beranda masih benar**

Run: `php artisan test --filter=HomeSectionsNormalize`
Expected: PASS. Test ini menjaga agar penambahan kunci `layanan` tidak merusak urutan section yang sudah tersimpan di database.

- [ ] **Step 11: Verifikasi manual seluruh alur**

```bash
npm run build
php artisan serve --port=8123 &
sleep 3
for u in / /layanan /layanan/pengambilan-ijazah /layanan/pengambilan-ijazah/ajukan /lacak; do
  printf '%s -> %s\n' "$u" "$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8123$u)"
done
kill %1
```
Expected: kelima URL mengembalikan `200`.

- [ ] **Step 12: Jalankan seluruh test dan commit**

```bash
php artisan test
git add -A
git commit -q -m "feat: navigasi PTSP, section layanan di beranda, dan penyembunyian modul CMS lama"
```

---

## Catatan penutup untuk pelaksana

Setelah Task 15 selesai, seluruh cakupan fase 1 pada spec sudah terpenuhi. Yang **sengaja belum** dikerjakan dan tidak boleh ditambahkan tanpa permintaan baru: pengiriman email/WhatsApp, status "Perlu Perbaikan Berkas", status yang bisa ditambah operator, akun pemohon, template surat balasan, dashboard statistik, dan pembersihan modul CMS yang tidak dipakai. Rinciannya ada di bab 9 spec.
