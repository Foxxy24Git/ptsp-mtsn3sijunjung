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
