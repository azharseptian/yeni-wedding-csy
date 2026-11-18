<?php

namespace App\Enums;

enum RoleName: string
{
    case Owner = 'owner';
    case TimInternal = 'tim_internal';
    case VendorKeluarga = 'vendor_keluarga';
    case Klien = 'klien';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner / Admin',
            self::TimInternal => 'Tim MUA / Dekorasi',
            self::VendorKeluarga => 'Vendor Keluarga',
            self::Klien => 'Calon Pengantin / Klien',
        };
    }
}
