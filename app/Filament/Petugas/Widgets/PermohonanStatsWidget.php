<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\FormSubmission;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PermohonanStatsWidget extends StatsOverviewWidget
{
    /**
     * Widget Filament di-lazy-load secara default (baru terisi lewat request
     * susulan setelah terlihat di layar) — dimatikan di sini karena lima
     * hitungan sederhana ini murah dan petugas semestinya langsung melihat
     * angkanya begitu dashboard terbuka, tanpa jeda "loading".
     */
    protected static bool $isLazy = false;

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
