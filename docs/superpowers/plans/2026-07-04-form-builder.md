# Form Builder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan fitur form builder (ala Google Form) — admin membuat form berisi pertanyaan, publik mengisi di `/form/{slug}`, jawaban dilihat & diekspor CSV di panel Filament.

**Architecture:** Pendekatan B (3 tabel): `forms`, `form_fields` (definisi pertanyaan, id stabil), `form_submissions` (jawaban sebagai JSON di-key oleh id field). Admin memakai Filament resource dengan Repeater untuk menyusun field; halaman publik dirender Blade + `FormController` (pola sama seperti `PageController`/`PostController`).

**Tech Stack:** Laravel 11, Filament v4, Livewire, SQLite (test in-memory), Tailwind, Pest/PHPUnit (`php artisan test`).

## Global Constraints

- Filament v4: schema pakai `Filament\Schemas\Schema`; komponen tabel/aksi pakai namespace `Filament\Actions\*`; ikon pakai `Filament\Support\Icons\Heroicon`.
- Semua teks UI berbahasa Indonesia (ikuti resource yang ada).
- 5 tipe field: `text`, `textarea`, `select`, `checkbox`, `file`. `select`/`checkbox` punya `options`. Semua field punya flag `required`.
- Hanya form `status = published` yang bisa diakses publik; selain itu 404 (`abort_unless`, pola `PageController`).
- Upload file ke disk `public`, folder `forms/`. Batas: `mimes:jpg,jpeg,png,pdf`, `max:5120` (5 MB).
- Test: `Tests\TestCase` + `RefreshDatabase`; Filament diuji via `Livewire::test(...)` dengan `actingAs(User::factory()->create())`; upload pakai `Storage::fake('public')` + `UploadedFile::fake()`.
- Jalankan test: `php artisan test`.
- Commit di tiap akhir task.

---

### Task 1: Migrations + Models

**Files:**
- Create: `database/migrations/2026_07_04_100000_create_forms_table.php`
- Create: `database/migrations/2026_07_04_100001_create_form_fields_table.php`
- Create: `database/migrations/2026_07_04_100002_create_form_submissions_table.php`
- Create: `app/Models/Form.php`
- Create: `app/Models/FormField.php`
- Create: `app/Models/FormSubmission.php`
- Test: `tests/Feature/FormModelTest.php`

**Interfaces:**
- Produces:
  - `App\Models\Form` — `$fillable = ['title','slug','description','success_message','status']`; `getRouteKeyName(): 'slug'`; `fields(): HasMany` (FormField, orderBy `sort_order`); `submissions(): HasMany` (FormSubmission).
  - `App\Models\FormField` — `$fillable = ['form_id','label','type','options','required','sort_order']`; casts `options`→`array`, `required`→`boolean`, `sort_order`→`integer`; `form(): BelongsTo`.
  - `App\Models\FormSubmission` — `$fillable = ['form_id','data']`; casts `data`→`array`; `form(): BelongsTo`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/FormModelTest.php
namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_has_ordered_fields_and_submissions(): void
    {
        $form = Form::create([
            'title' => 'Pendaftaran Siswa',
            'slug' => 'pendaftaran-siswa',
            'status' => 'published',
        ]);

        $form->fields()->create(['label' => 'B', 'type' => 'text', 'required' => true, 'sort_order' => 2]);
        $form->fields()->create(['label' => 'A', 'type' => 'text', 'required' => false, 'sort_order' => 1]);

        $sub = $form->submissions()->create(['data' => ['1' => 'Budi']]);

        $this->assertSame('pendaftaran-siswa', $form->getRouteKey());
        $this->assertSame(['A', 'B'], $form->fields()->pluck('label')->all());
        $this->assertTrue($form->fields->first()->required === false);
        $this->assertSame(['1' => 'Budi'], $sub->fresh()->data);
    }

    public function test_deleting_form_cascades_to_fields_and_submissions(): void
    {
        $form = Form::create(['title' => 'X', 'slug' => 'x', 'status' => 'draft']);
        $form->fields()->create(['label' => 'F', 'type' => 'text', 'sort_order' => 0]);
        $form->submissions()->create(['data' => []]);

        $form->delete();

        $this->assertDatabaseCount('form_fields', 0);
        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_form_field_options_cast_to_array(): void
    {
        $form = Form::create(['title' => 'Y', 'slug' => 'y', 'status' => 'draft']);
        $field = $form->fields()->create([
            'label' => 'Kelas', 'type' => 'select',
            'options' => ['X', 'XI', 'XII'], 'sort_order' => 0,
        ]);

        $this->assertSame(['X', 'XI', 'XII'], $field->fresh()->options);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FormModelTest`
Expected: FAIL — class `App\Models\Form` not found.

- [ ] **Step 3: Write the three migrations**

```php
<?php
// database/migrations/2026_07_04_100000_create_forms_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('success_message')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
```

```php
<?php
// database/migrations/2026_07_04_100001_create_form_fields_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('label');
            $table->string('type'); // text | textarea | select | checkbox | file
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
```

```php
<?php
// database/migrations/2026_07_04_100002_create_form_submissions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
```

- [ ] **Step 4: Write the three models**

```php
<?php
// app/Models/Form.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'success_message',
        'status',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}
```

```php
<?php
// app/Models/FormField.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $fillable = [
        'form_id',
        'label',
        'type',
        'options',
        'required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
```

```php
<?php
// app/Models/FormSubmission.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_id',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=FormModelTest`
Expected: PASS (3 tests).

- [ ] **Step 6: Commit**

```bash
git add database/migrations app/Models/Form.php app/Models/FormField.php app/Models/FormSubmission.php tests/Feature/FormModelTest.php
git commit -m "feat: tabel & model form builder (forms, form_fields, form_submissions)"
```

---

### Task 2: Public form page (routes + FormController@show + Blade)

**Files:**
- Modify: `routes/web.php` (tambah rute form sebelum catch-all, kecualikan `form` di regex)
- Create: `app/Http/Controllers/FormController.php` (method `show`)
- Create: `resources/views/forms/show.blade.php`
- Test: `tests/Feature/FormPublicPageTest.php`

**Interfaces:**
- Consumes: `App\Models\Form` (Task 1).
- Produces:
  - Route names: `forms.show` (`GET /form/{form:slug}`), `forms.submit` (`POST /form/{form:slug}`) — POST didaftarkan di sini, handler-nya diisi Task 3.
  - `FormController::show(Form $form)` → view `forms.show` dengan `$form` (eager-load `fields`).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/FormPublicPageTest.php
namespace Tests\Feature;

use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormPublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_form_page_renders_fields(): void
    {
        $form = Form::create([
            'title' => 'Pendaftaran Siswa',
            'slug' => 'pendaftaran-siswa',
            'description' => 'Isi data dengan benar.',
            'status' => 'published',
        ]);
        $form->fields()->create(['label' => 'Nama Lengkap', 'type' => 'text', 'required' => true, 'sort_order' => 1]);
        $form->fields()->create(['label' => 'Kelas', 'type' => 'select', 'options' => ['X', 'XI'], 'sort_order' => 2]);

        $this->get('/form/pendaftaran-siswa')
            ->assertOk()
            ->assertSee('Pendaftaran Siswa')
            ->assertSee('Isi data dengan benar.')
            ->assertSee('Nama Lengkap')
            ->assertSee('Kelas');
    }

    public function test_draft_form_returns_404(): void
    {
        Form::create(['title' => 'Rahasia', 'slug' => 'rahasia', 'status' => 'draft']);

        $this->get('/form/rahasia')->assertNotFound();
    }

    public function test_unknown_form_returns_404(): void
    {
        $this->get('/form/tidak-ada')->assertNotFound();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FormPublicPageTest`
Expected: FAIL — route `/form/...` not defined (404 on published form too).

- [ ] **Step 3: Add routes**

Modify `routes/web.php`. Tambah `use App\Http\Controllers\FormController;` di grup use, lalu tambahkan blok rute form **sebelum** rute catch-all `/{page:slug}`, dan tambahkan `form` ke pengecualian regex catch-all:

```php
// Form publik (form builder). Didefinisikan sebelum catch-all halaman.
Route::get('/form/{form:slug}', [FormController::class, 'show'])->name('forms.show');
Route::post('/form/{form:slug}', [FormController::class, 'submit'])->name('forms.submit');

// Halaman statis by slug (catch-all satu segmen) — WAJIB paling akhir.
Route::get('/{page:slug}', [PageController::class, 'show'])
    ->where('page', '(?!admin$|up$|form$)[A-Za-z0-9._-]+')
    ->name('pages.show');
```

(Catatan: ubah regex existing `(?!admin$|up$)` menjadi `(?!admin$|up$|form$)`.)

- [ ] **Step 4: Create FormController with show()**

```php
<?php
// app/Http/Controllers/FormController.php
namespace App\Http\Controllers;

use App\Models\Form;

class FormController extends Controller
{
    /**
     * Tampilkan form publik by slug. Hanya yang published; selain itu 404.
     */
    public function show(Form $form)
    {
        abort_unless($form->status === 'published', 404);

        $form->load('fields');

        return view('forms.show', compact('form'));
    }
}
```

- [ ] **Step 5: Create the Blade view**

```blade
{{-- resources/views/forms/show.blade.php --}}
@extends('layouts.app')

@section('title', $form->title)

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-12">
        <h1 class="text-3xl font-bold text-gray-900">{{ $form->title }}</h1>

        @if ($form->description)
            <p class="mt-3 text-gray-600">{{ $form->description }}</p>
        @endif

        @if (session('form_success'))
            <div class="mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('form_success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <p class="font-medium">Periksa kembali isian Anda:</p>
                <ul class="mt-1 list-disc pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('forms.submit', $form) }}" enctype="multipart/form-data" class="mt-8 space-y-6">
            @csrf

            @foreach ($form->fields as $field)
                @php $name = 'field_'.$field->id; @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-800">
                        {{ $field->label }}
                        @if ($field->required)<span class="text-red-500">*</span>@endif
                    </label>

                    @if ($field->type === 'text')
                        <input type="text" name="{{ $name }}" value="{{ old($name) }}"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                    @elseif ($field->type === 'textarea')
                        <textarea name="{{ $name }}" rows="4"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">{{ old($name) }}</textarea>
                    @elseif ($field->type === 'select')
                        <select name="{{ $name }}"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                            <option value="">— Pilih —</option>
                            @foreach (($field->options ?? []) as $opt)
                                <option value="{{ $opt }}" @selected(old($name) === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif ($field->type === 'checkbox')
                        <div class="mt-2 space-y-2">
                            @foreach (($field->options ?? []) as $opt)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="{{ $name }}[]" value="{{ $opt }}"
                                        @checked(in_array($opt, (array) old($name, [])))
                                        class="rounded border-gray-300 text-primary focus:ring-primary">
                                    {{ $opt }}
                                </label>
                            @endforeach
                        </div>
                    @elseif ($field->type === 'file')
                        <input type="file" name="{{ $name }}"
                            class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-primary">
                    @endif
                </div>
            @endforeach

            <button type="submit"
                class="rounded-md bg-primary px-5 py-2.5 font-medium text-white shadow-sm hover:opacity-90">
                Kirim
            </button>
        </form>
    </div>
@endsection
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=FormPublicPageTest`
Expected: PASS (3 tests).

- [ ] **Step 7: Commit**

```bash
git add routes/web.php app/Http/Controllers/FormController.php resources/views/forms/show.blade.php tests/Feature/FormPublicPageTest.php
git commit -m "feat: halaman publik form (/form/{slug}) + render field"
```

---

### Task 3: Form submission (FormController@submit + validasi + simpan)

**Files:**
- Modify: `app/Http/Controllers/FormController.php` (tambah method `submit`)
- Test: `tests/Feature/FormSubmitTest.php`

**Interfaces:**
- Consumes: `App\Models\Form`, `App\Models\FormField`, route `forms.submit` (Task 2).
- Produces: `FormController::submit(Request $request, Form $form)` — validasi dinamis, simpan `FormSubmission` (`data` di-key oleh `field->id`, file → path string di disk `public`), redirect back dengan `session('form_success')`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/FormSubmitTest.php
namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormSubmitTest extends TestCase
{
    use RefreshDatabase;

    private function makeForm(): Form
    {
        $form = Form::create([
            'title' => 'Pendaftaran', 'slug' => 'pendaftaran',
            'success_message' => 'Terima kasih sudah mendaftar!', 'status' => 'published',
        ]);
        $form->fields()->create(['label' => 'Nama', 'type' => 'text', 'required' => true, 'sort_order' => 1]);
        $form->fields()->create(['label' => 'Kelas', 'type' => 'select', 'options' => ['X', 'XI'], 'required' => true, 'sort_order' => 2]);
        $form->fields()->create(['label' => 'Ekskul', 'type' => 'checkbox', 'options' => ['Basket', 'Musik'], 'sort_order' => 3]);

        return $form;
    }

    /** Peta label → id field (id sebenarnya, jangan di-hardcode). */
    private function fieldIds(Form $form): \Illuminate\Support\Collection
    {
        return $form->fields()->pluck('id', 'label');
    }

    public function test_valid_submission_is_stored_and_shows_success(): void
    {
        $form = $this->makeForm();
        $id = $this->fieldIds($form);

        $this->post('/form/pendaftaran', [
            'field_'.$id['Nama'] => 'Budi',
            'field_'.$id['Kelas'] => 'X',
            'field_'.$id['Ekskul'] => ['Basket', 'Musik'],
        ])
            ->assertRedirect('/form/pendaftaran')
            ->assertSessionHas('form_success', 'Terima kasih sudah mendaftar!');

        $this->assertDatabaseCount('form_submissions', 1);
        $data = FormSubmission::first()->data;
        $this->assertSame('Budi', $data[$id['Nama']]);
        $this->assertSame('X', $data[$id['Kelas']]);
        $this->assertSame(['Basket', 'Musik'], $data[$id['Ekskul']]);
    }

    public function test_required_field_missing_fails_validation(): void
    {
        $form = $this->makeForm();
        $id = $this->fieldIds($form);

        $this->post('/form/pendaftaran', ['field_'.$id['Kelas'] => 'X'])
            ->assertSessionHasErrors('field_'.$id['Nama']);

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_select_value_outside_options_is_rejected(): void
    {
        $form = $this->makeForm();
        $id = $this->fieldIds($form);

        $this->post('/form/pendaftaran', ['field_'.$id['Nama'] => 'Budi', 'field_'.$id['Kelas'] => 'XII'])
            ->assertSessionHasErrors('field_'.$id['Kelas']);

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_file_upload_is_stored_and_path_recorded(): void
    {
        Storage::fake('public');
        $form = Form::create(['title' => 'Berkas', 'slug' => 'berkas', 'status' => 'published']);
        $field = $form->fields()->create(['label' => 'Foto', 'type' => 'file', 'required' => true, 'sort_order' => 1]);

        $this->post('/form/berkas', [
            'field_'.$field->id => UploadedFile::fake()->image('foto.jpg'),
        ])->assertRedirect('/form/berkas');

        $path = FormSubmission::first()->data[$field->id];
        $this->assertStringStartsWith('forms/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_draft_form_cannot_be_submitted(): void
    {
        Form::create(['title' => 'Draft', 'slug' => 'draft-form', 'status' => 'draft']);

        $this->post('/form/draft-form', [])->assertNotFound();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FormSubmitTest`
Expected: FAIL — `submit()` belum ada / method not found (error 500 atau BadMethodCall).

- [ ] **Step 3: Implement submit()**

Tambahkan di `app/Http/Controllers/FormController.php` — import `Illuminate\Http\Request`, `Illuminate\Validation\Rule`, `App\Models\FormField` di atas; lalu method:

```php
    /**
     * Terima & simpan jawaban form. Validasi dibangun dinamis dari definisi field.
     */
    public function submit(Request $request, Form $form)
    {
        abort_unless($form->status === 'published', 404);

        $form->load('fields');

        $rules = [];
        $attributes = [];

        foreach ($form->fields as $field) {
            $key = 'field_'.$field->id;
            $attributes[$key] = $field->label;
            $required = $field->required ? 'required' : 'nullable';

            $rules[$key] = match ($field->type) {
                'select' => [$required, Rule::in($field->options ?? [])],
                'checkbox' => [$required, 'array'],
                'file' => [$required, 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
                default => [$required, 'string', 'max:5000'],
            };

            if ($field->type === 'checkbox') {
                $rules[$key.'.*'] = [Rule::in($field->options ?? [])];
            }
        }

        $validated = $request->validate($rules, [], $attributes);

        $data = [];
        foreach ($form->fields as $field) {
            $key = 'field_'.$field->id;

            if ($field->type === 'file') {
                if ($request->hasFile($key)) {
                    $data[$field->id] = $request->file($key)->store('forms', 'public');
                }
                continue;
            }

            $data[$field->id] = $validated[$key] ?? ($field->type === 'checkbox' ? [] : null);
        }

        $form->submissions()->create(['data' => $data]);

        return redirect()
            ->route('forms.show', $form)
            ->with('form_success', $form->success_message ?: 'Terima kasih, jawaban Anda telah dikirim.');
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FormSubmitTest`
Expected: PASS (5 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/FormController.php tests/Feature/FormSubmitTest.php
git commit -m "feat: proses submit form (validasi dinamis, upload file, simpan jawaban)"
```

---

### Task 4: Filament FormResource (form builder)

**Files:**
- Create: `app/Filament/Resources/Forms/FormResource.php`
- Create: `app/Filament/Resources/Forms/Schemas/FormSchema.php`
- Create: `app/Filament/Resources/Forms/Tables/FormsTable.php`
- Create: `app/Filament/Resources/Forms/Pages/ListForms.php`
- Create: `app/Filament/Resources/Forms/Pages/CreateForm.php`
- Create: `app/Filament/Resources/Forms/Pages/EditForm.php`
- Test: `tests/Feature/FormResourceTest.php`

**Interfaces:**
- Consumes: `App\Models\Form`, `App\Models\FormField` (Task 1).
- Produces: Filament resource pages `App\Filament\Resources\Forms\Pages\{ListForms,CreateForm,EditForm}`. Field builder = Repeater `fields` (relationship) dengan komponen `label`, `type`, `options` (visible untuk select/checkbox), `required`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/FormResourceTest.php
namespace Tests\Feature;

use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_form_list_renders(): void
    {
        Livewire::test(ListForms::class)->assertOk();
    }

    public function test_can_create_form_with_fields(): void
    {
        Livewire::test(CreateForm::class)
            ->fillForm([
                'title' => 'Pendaftaran Siswa',
                'slug' => 'pendaftaran-siswa',
                'status' => 'published',
                'fields' => [
                    ['label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['label' => 'Kelas', 'type' => 'select', 'options' => ['X', 'XI'], 'required' => true],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $form = Form::where('slug', 'pendaftaran-siswa')->first();
        $this->assertNotNull($form);
        $this->assertSame(2, $form->fields()->count());
        $this->assertSame('select', $form->fields()->where('label', 'Kelas')->first()->type);
    }

    public function test_form_slug_must_be_unique(): void
    {
        Form::create(['title' => 'Ada', 'slug' => 'ada', 'status' => 'draft']);

        Livewire::test(CreateForm::class)
            ->fillForm(['title' => 'Ada Lagi', 'slug' => 'ada', 'status' => 'draft'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FormResourceTest`
Expected: FAIL — class `App\Filament\Resources\Forms\Pages\ListForms` not found.

- [ ] **Step 3: Create the schema (form builder)**

```php
<?php
// app/Filament/Resources/Forms/Schemas/FormSchema.php
namespace App\Filament\Resources\Forms\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FormSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul Form')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Alamat form: /form/{slug}. Otomatis dari judul, bisa diubah.'),
                Textarea::make('description')
                    ->label('Deskripsi (opsional)')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('success_message')
                    ->label('Pesan setelah kirim (opsional)')
                    ->rows(2)
                    ->placeholder('Terima kasih, jawaban Anda telah dikirim.')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(['draft' => 'Draft', 'published' => 'Published'])
                    ->default('draft')
                    ->required(),
                Repeater::make('fields')
                    ->label('Pertanyaan')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Pertanyaan baru')
                    ->addActionLabel('Tambah pertanyaan')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('label')
                            ->label('Pertanyaan')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'text' => 'Teks singkat',
                                'textarea' => 'Teks panjang',
                                'select' => 'Pilihan (dropdown)',
                                'checkbox' => 'Checkbox (pilih banyak)',
                                'file' => 'Upload file',
                            ])
                            ->default('text')
                            ->required()
                            ->live(),
                        TagsInput::make('options')
                            ->label('Pilihan')
                            ->helperText('Ketik lalu Enter untuk tiap pilihan.')
                            ->visible(fn (Get $get): bool => in_array($get('type'), ['select', 'checkbox']))
                            ->columnSpanFull(),
                        Toggle::make('required')
                            ->label('Wajib diisi')
                            ->default(false),
                    ]),
            ]);
    }
}
```

- [ ] **Step 4: Create the table**

```php
<?php
// app/Filament/Resources/Forms/Tables/FormsTable.php
namespace App\Filament\Resources\Forms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors(['success' => 'published', 'gray' => 'draft']),
                TextColumn::make('submissions_count')
                    ->label('Jawaban')
                    ->counts('submissions'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

- [ ] **Step 5: Create the resource + pages**

```php
<?php
// app/Filament/Resources/Forms/FormResource.php
namespace App\Filament\Resources\Forms;

use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Forms\Schemas\FormSchema;
use App\Filament\Resources\Forms\Tables\FormsTable;
use App\Models\Form;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Form';

    protected static ?string $modelLabel = 'Form';

    protected static ?string $pluralModelLabel = 'Form';

    public static function form(Schema $schema): Schema
    {
        return FormSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }
}
```

```php
<?php
// app/Filament/Resources/Forms/Pages/ListForms.php
namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListForms extends ListRecords
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
```

```php
<?php
// app/Filament/Resources/Forms/Pages/CreateForm.php
namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use Filament\Resources\Pages\CreateRecord;

class CreateForm extends CreateRecord
{
    protected static string $resource = FormResource::class;
}
```

```php
<?php
// app/Filament/Resources/Forms/Pages/EditForm.php
namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditForm extends EditRecord
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=FormResourceTest`
Expected: PASS (3 tests). Jika `Heroicon::OutlinedClipboardDocumentList` tidak ada di versi ini, ganti dengan `Heroicon::OutlinedDocumentText`.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Forms tests/Feature/FormResourceTest.php
git commit -m "feat: FilamentResource Form builder (Repeater pertanyaan)"
```

---

### Task 5: Submissions relation manager + detail modal

**Files:**
- Create: `app/Filament/Resources/Forms/RelationManagers/SubmissionsRelationManager.php`
- Create: `resources/views/filament/form-submission-detail.blade.php`
- Modify: `app/Filament/Resources/Forms/FormResource.php` (daftarkan relation manager di `getRelations()`)
- Test: `tests/Feature/FormSubmissionsRelationTest.php`

**Interfaces:**
- Consumes: `App\Models\Form`, `App\Models\FormSubmission` (Task 1), `FormResource` (Task 4).
- Produces: `SubmissionsRelationManager` (relationship `submissions`) — kolom `created_at`; ViewAction membuka modal berisi label→jawaban; DeleteAction.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/FormSubmissionsRelationTest.php
namespace Tests\Feature;

use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\RelationManagers\SubmissionsRelationManager;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormSubmissionsRelationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_relation_manager_lists_submissions(): void
    {
        $form = Form::create(['title' => 'Daftar', 'slug' => 'daftar', 'status' => 'published']);
        $field = $form->fields()->create(['label' => 'Nama', 'type' => 'text', 'sort_order' => 1]);
        $sub = $form->submissions()->create(['data' => [$field->id => 'Budi']]);

        Livewire::test(SubmissionsRelationManager::class, [
            'ownerRecord' => $form,
            'pageClass' => EditForm::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$sub]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FormSubmissionsRelationTest`
Expected: FAIL — class `SubmissionsRelationManager` not found.

- [ ] **Step 3: Create the detail Blade view**

```blade
{{-- resources/views/filament/form-submission-detail.blade.php --}}
<div class="space-y-4 text-sm">
    @foreach ($submission->form->fields as $field)
        @php $value = $submission->data[$field->id] ?? null; @endphp
        <div>
            <div class="font-medium text-gray-500">{{ $field->label }}</div>
            <div class="mt-0.5 text-gray-900">
                @if ($field->type === 'file' && $value)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($value) }}"
                       target="_blank" class="text-primary underline">Lihat file</a>
                @elseif (is_array($value))
                    {{ implode(', ', $value) ?: '—' }}
                @else
                    {{ $value !== null && $value !== '' ? $value : '—' }}
                @endif
            </div>
        </div>
    @endforeach
</div>
```

- [ ] **Step 4: Create the relation manager**

```php
<?php
// app/Filament/Resources/Forms/RelationManagers/SubmissionsRelationManager.php
namespace App\Filament\Resources\Forms\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'Jawaban';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#'),
                TextColumn::make('created_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading('Detail Jawaban')
                    ->modalContent(fn ($record): View => view(
                        'filament.form-submission-detail',
                        ['submission' => $record],
                    )),
                DeleteAction::make(),
            ]);
    }
}
```

- [ ] **Step 5: Register the relation manager**

Ubah `getRelations()` di `app/Filament/Resources/Forms/FormResource.php`:

```php
    public static function getRelations(): array
    {
        return [
            RelationManagers\SubmissionsRelationManager::class,
        ];
    }
```

(Tambahkan `use App\Filament\Resources\Forms\RelationManagers;` bila perlu, atau referensikan penuh seperti di atas.)

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=FormSubmissionsRelationTest`
Expected: PASS (1 test).

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Forms/RelationManagers resources/views/filament/form-submission-detail.blade.php app/Filament/Resources/Forms/FormResource.php tests/Feature/FormSubmissionsRelationTest.php
git commit -m "feat: relation manager jawaban form + modal detail"
```

---

### Task 6: Export CSV jawaban

**Files:**
- Create: `app/Support/FormCsvExporter.php`
- Modify: `app/Filament/Resources/Forms/RelationManagers/SubmissionsRelationManager.php` (tambah header action Export CSV yang memanggil exporter)
- Test: `tests/Feature/FormExportCsvTest.php`

**Interfaces:**
- Consumes: `App\Models\Form`, `App\Models\FormSubmission` (Task 1), `SubmissionsRelationManager` (Task 5).
- Produces:
  - `App\Support\FormCsvExporter::toCsvString(Form $form): string` — baris pertama `Waktu` + label tiap field; tiap baris berikut = jawaban satu submission (array digabung `', '`, file = path).
  - `App\Support\FormCsvExporter::download(Form $form): Symfony\Component\HttpFoundation\StreamedResponse` — stream `toCsvString` sebagai file `jawaban-{slug}.csv`.
  - Header action `export` di relation manager memanggil `FormCsvExporter::download(...)`.

Logika CSV diekstrak ke kelas tersendiri agar dapat diuji langsung (tanpa bergantung pada API unduhan Livewire yang rapuh).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/FormExportCsvTest.php
namespace Tests\Feature;

use App\Models\Form;
use App\Support\FormCsvExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormExportCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_contains_labels_and_answers_in_field_order(): void
    {
        $form = Form::create(['title' => 'Daftar', 'slug' => 'daftar', 'status' => 'published']);
        $f1 = $form->fields()->create(['label' => 'Nama', 'type' => 'text', 'sort_order' => 1]);
        $f2 = $form->fields()->create(['label' => 'Ekskul', 'type' => 'checkbox', 'options' => ['Basket', 'Musik'], 'sort_order' => 2]);
        $form->submissions()->create(['data' => [$f1->id => 'Budi', $f2->id => ['Basket', 'Musik']]]);

        $csv = FormCsvExporter::toCsvString($form->fresh());
        $lines = array_values(array_filter(explode("\n", trim($csv))));

        // Header + 1 baris data.
        $this->assertStringContainsString('Waktu', $lines[0]);
        $this->assertStringContainsString('Nama', $lines[0]);
        $this->assertStringContainsString('Ekskul', $lines[0]);
        $this->assertStringContainsString('Budi', $lines[1]);
        $this->assertStringContainsString('Basket, Musik', $lines[1]);
    }

    public function test_download_returns_csv_response(): void
    {
        $form = Form::create(['title' => 'Kosong', 'slug' => 'kosong', 'status' => 'published']);

        $response = FormCsvExporter::download($form);

        $this->assertSame('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('jawaban-kosong.csv', $response->headers->get('Content-Disposition'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FormExportCsvTest`
Expected: FAIL — class `App\Support\FormCsvExporter` not found.

- [ ] **Step 3: Create the exporter**

```php
<?php
// app/Support/FormCsvExporter.php
namespace App\Support;

use App\Models\Form;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormCsvExporter
{
    /**
     * Bangun isi CSV: header (Waktu + label field) lalu satu baris per submission.
     */
    public static function toCsvString(Form $form): string
    {
        $form->loadMissing('fields', 'submissions');
        $fields = $form->fields;

        $out = fopen('php://temp', 'r+');

        $header = ['Waktu'];
        foreach ($fields as $field) {
            $header[] = $field->label;
        }
        fputcsv($out, $header);

        foreach ($form->submissions()->orderBy('created_at')->get() as $submission) {
            $row = [(string) $submission->created_at];
            foreach ($fields as $field) {
                $value = $submission->data[$field->id] ?? '';
                $row[] = is_array($value) ? implode(', ', $value) : (string) $value;
            }
            fputcsv($out, $row);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /**
     * Stream CSV sebagai unduhan file.
     */
    public static function download(Form $form): StreamedResponse
    {
        $filename = 'jawaban-'.$form->slug.'.csv';
        $csv = self::toCsvString($form);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
```

- [ ] **Step 4: Add the export header action**

Di `SubmissionsRelationManager`, tambah import:

```php
use App\Support\FormCsvExporter;
use Filament\Actions\Action;
use Symfony\Component\HttpFoundation\StreamedResponse;
```

Lalu di `table()`, tambahkan `->headerActions([...])` sebelum `->recordActions`:

```php
            ->headerActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse => FormCsvExporter::download($this->getOwnerRecord())),
            ])
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=FormExportCsvTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Support/FormCsvExporter.php app/Filament/Resources/Forms/RelationManagers/SubmissionsRelationManager.php tests/Feature/FormExportCsvTest.php
git commit -m "feat: export CSV jawaban form (via FormCsvExporter)"
```

---

### Task 7: Integrasi menu (tipe `form`)

**Files:**
- Modify: `app/Models/Menu.php` (tambah cabang `form` di `url()`)
- Modify: `app/Filament/Resources/Menus/Schemas/MenuForm.php` (tambah opsi `form` + dropdown target form)
- Test: `tests/Feature/MenuFormTypeTest.php`

**Interfaces:**
- Consumes: `App\Models\Form` (Task 1), `App\Models\Menu` (existing), route `forms.show` (Task 2).
- Produces: `Menu::url()` untuk `type = 'form'` → `route('forms.show', $target)` (`/form/{slug}`). Form menu builder punya opsi tipe "Form".

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/MenuFormTypeTest.php
namespace Tests\Feature;

use App\Models\Form;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuFormTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_url_for_form_type_points_to_form_page(): void
    {
        Form::create(['title' => 'Pendaftaran', 'slug' => 'pendaftaran', 'status' => 'published']);

        $menu = Menu::create([
            'label' => 'Daftar', 'type' => 'form', 'target' => 'pendaftaran', 'sort_order' => 0,
        ]);

        $this->assertSame(url('/form/pendaftaran'), $menu->url());
    }

    public function test_menu_url_empty_target_returns_hash(): void
    {
        $menu = Menu::create(['label' => 'Kosong', 'type' => 'form', 'target' => '', 'sort_order' => 0]);

        $this->assertSame('#', $menu->url());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MenuFormTypeTest`
Expected: FAIL — `type = 'form'` jatuh ke `default` (mengembalikan target mentah `pendaftaran`, bukan `/form/pendaftaran`).

- [ ] **Step 3: Add form branch to Menu::url()**

Di `app/Models/Menu.php`, dalam `match ($this->type)` tambahkan cabang `form` sebelum `default`:

```php
        return match ($this->type) {
            'page' => url('/'.ltrim($target, '/')),
            'post' => route('posts.show', $target),
            'form' => route('forms.show', $target),
            default => $target,
        };
```

Perbarui juga docblock method (tambah baris `- form: target = slug form → /form/{slug}`).

- [ ] **Step 4: Add form option to the menu builder**

Di `app/Filament/Resources/Menus/Schemas/MenuForm.php`, tambahkan `'form' => 'Form'` ke opsi Select `type`:

```php
                Select::make('type')
                    ->options([
                        'page' => 'Page',
                        'post' => 'Post',
                        'url' => 'URL',
                        'form' => 'Form',
                    ])
                    ->default('url')
                    ->required(),
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=MenuFormTypeTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Models/Menu.php app/Filament/Resources/Menus/Schemas/MenuForm.php tests/Feature/MenuFormTypeTest.php
git commit -m "feat: tipe menu 'form' menuju halaman /form/{slug}"
```

---

### Task 8: Full suite + verifikasi akhir

**Files:** (tidak ada file baru — verifikasi)

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test`
Expected: SEMUA test PASS (termasuk suite lama: Slider, FilamentResources, HomeSectionLayout, dst.). Perhatikan regresi pada rute catch-all halaman akibat pengecualian `form$`.

- [ ] **Step 2: Manual smoke test (opsional, bila menjalankan app)**

```bash
php artisan migrate
php artisan serve
```
Buka `/admin` → menu "Form" → buat form dengan beberapa pertanyaan (status Published) → buka `/form/{slug}` → isi & kirim → kembali ke Edit Form → tab "Jawaban" → lihat detail & Export CSV.

- [ ] **Step 3: Commit (bila ada penyesuaian)**

```bash
git add -A
git commit -m "test: verifikasi penuh fitur form builder"
```

---

## Catatan Implementasi

- **Nama kelas schema** sengaja `FormSchema` (bukan `FormForm`) untuk menghindari stutter, tetap mengikuti pola `configure(Schema $schema)`.
- **Konflik nama `Form`:** model `App\Models\Form` aman karena Filament v4 memakai `Filament\Schemas\Schema`, bukan kelas global bernama `Form`.
- Jika konstanta `Heroicon::OutlinedClipboardDocumentList` tidak tersedia, pakai `Heroicon::OutlinedDocumentText`.
- **Urutan rute** krusial: rute `/form/...` dan pengecualian regex `form$` harus ada agar catch-all halaman tidak menelan URL form.
