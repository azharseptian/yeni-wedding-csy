<?php

namespace App\Enums;

enum SnapshotItemType: string
{
    case Paket = 'paket';
    case KomponenDinamis = 'komponen_dinamis';
    case LayananVendor = 'layanan_vendor';
    case Tambahan = 'tambahan';
}
