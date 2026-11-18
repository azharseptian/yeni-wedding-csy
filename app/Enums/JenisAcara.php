<?php

namespace App\Enums;

enum JenisAcara: string
{
    case Akad = 'akad';
    case Resepsi = 'resepsi';
    case AkadResepsi = 'akad_resepsi';

    public function label(): string
    {
        return match ($this) {
            self::Akad => 'Akad Nikah',
            self::Resepsi => 'Resepsi',
            self::AkadResepsi => 'Akad & Resepsi',
        };
    }
}
