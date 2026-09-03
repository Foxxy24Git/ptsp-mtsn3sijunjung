<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'CMS Sekolah');
        $this->migrator->add('general.logo', null);
        $this->migrator->add('general.address', null);
        $this->migrator->add('general.phone', null);
        $this->migrator->add('general.email', null);
        $this->migrator->add('general.primary_color', '#2563eb');
    }
};
