<?php

namespace App\Enums;

enum ScheduleRole: string
{
    case Mua = 'mua';
    case AsistenRias = 'asisten_rias';
    case Dekorator = 'dekorator';
    case KruDekorasi = 'kru_dekorasi';
    case Mc = 'mc';
    case AmongTamu = 'among_tamu';
    case Vendor = 'vendor';

    public function label(): string
    {
        return match ($this) {
            self::Mua => 'MUA',
            self::AsistenRias => 'Asisten Rias',
            self::Dekorator => 'Dekorator',
            self::KruDekorasi => 'Kru Dekorasi',
            self::Mc => 'MC',
            self::AmongTamu => 'Among Tamu',
            self::Vendor => 'Vendor',
        };
    }

    /** Peran yang dihitung terhadap kapasitas kru dekorasi harian. */
    public function countsTowardDecorCapacity(): bool
    {
        return in_array($this, [self::Dekorator, self::KruDekorasi], true);
    }
}
