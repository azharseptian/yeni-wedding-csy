<?php

namespace App\Support;

class Whatsapp
{
    /** Normalisasi nomor Indonesia ke format 62xxx (08xx / +62 / 62). */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return match (true) {
            str_starts_with($digits, '62') => $digits,
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => $digits,
        };
    }

    public static function link(string $phone, string $message): string
    {
        return 'https://wa.me/'.static::normalize($phone).'?text='.rawurlencode($message);
    }
}
