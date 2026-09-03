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
