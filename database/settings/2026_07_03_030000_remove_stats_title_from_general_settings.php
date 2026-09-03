<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->deleteIfExists('general.stats_title');
    }

    public function down(): void
    {
        $this->migrator->add('general.stats_title', 'Statistik Kampus');
    }
};
