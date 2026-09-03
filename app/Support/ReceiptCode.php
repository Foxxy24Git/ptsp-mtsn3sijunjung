<?php

namespace App\Support;

use App\Models\FormSubmission;

class ReceiptCode
{
    /**
     * Alfabet sengaja membuang 0, O, 1, dan I: kode resi sering dibaca ulang
     * dari kertas atau disebutkan lewat telepon, dan keempat karakter itu
     * paling sering tertukar.
     */
    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const PANJANG_ACAK = 6;

    public static function generate(?\DateTimeInterface $at = null): string
    {
        $at ??= new \DateTimeImmutable();
        $acak = '';
        $batas = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < self::PANJANG_ACAK; $i++) {
            $acak .= self::ALPHABET[random_int(0, $batas)];
        }

        return sprintf('PTSP-%s-%s', $at->format('ym'), $acak);
    }

    /** Ulangi pembangkitan sampai mendapat kode yang belum dipakai. */
    public static function generateUnique(?\DateTimeInterface $at = null): string
    {
        do {
            $kode = self::generate($at);
        } while (FormSubmission::where('receipt_code', $kode)->exists());

        return $kode;
    }
}
