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
