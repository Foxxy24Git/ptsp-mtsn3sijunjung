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
