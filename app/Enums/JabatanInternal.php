<?php

namespace App\Enums;

enum JabatanInternal: string
{
    case Mua = 'mua';
    case AsistenRias = 'asisten_rias';
    case Dekorator = 'dekorator';
    case KruDekorasi = 'kru_dekorasi';

    public function label(): string
    {
        return match ($this) {
            self::Mua => 'MUA (Perias)',
            self::AsistenRias => 'Asisten Rias',
            self::Dekorator => 'Dekorator',
            self::KruDekorasi => 'Kru Dekorasi',
        };
    }
}
