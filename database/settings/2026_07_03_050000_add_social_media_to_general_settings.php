<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Tautan sosial media situs publik. Default null: ikon disembunyikan
        // selama admin belum mengisinya (kosong = sembunyi).
        $this->migrator->add('general.whatsapp', null);
        $this->migrator->add('general.instagram', null);
        $this->migrator->add('general.facebook', null);
        $this->migrator->add('general.youtube', null);
    }
};
