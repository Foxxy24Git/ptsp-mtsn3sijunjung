<?php

namespace Tests\Unit;

use App\Settings\GeneralSettings;
use PHPUnit\Framework\TestCase;

class HomeSectionsNormalizeTest extends TestCase
{
    public function test_keeps_order_and_visibility_from_stored(): void
    {
        $result = GeneralSettings::normalizeSections([
            ['key' => 'layanan', 'visible' => false],
            ['key' => 'hero', 'visible' => true],
        ]);

        // Dua entri tersimpan tampil lebih dulu, urutan dipertahankan.
        $this->assertSame('layanan', $result[0]['key']);
        $this->assertFalse($result[0]['visible']);
        $this->assertSame('hero', $result[1]['key']);
        $this->assertTrue($result[1]['visible']);
    }

    public function test_appends_missing_master_keys_as_visible(): void
    {
        $result = GeneralSettings::normalizeSections([
            ['key' => 'layanan', 'visible' => true],
        ]);

        $keys = array_column($result, 'key');
        // Semua key master hadir (dibandingkan ke konstanta, bukan daftar
        // tertulis ulang, supaya test ini tidak basi lagi saat HOME_SECTIONS
        // bertambah di masa depan).
        $this->assertEqualsCanonicalizing(
            array_keys(GeneralSettings::HOME_SECTIONS),
            $keys
        );
        // 'layanan' tetap di depan; sisanya di-append visible=true.
        $this->assertSame('layanan', $result[0]['key']);
        foreach ($result as $section) {
            if ($section['key'] !== 'layanan') {
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
