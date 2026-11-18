<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Penawaran = 'penawaran';
    case Terkonfirmasi = 'terkonfirmasi';
    case BelumMemenuhiAmbang = 'belum_memenuhi_ambang';
    case SiapPelaksanaan = 'siap_pelaksanaan';
    case Lunas = 'lunas';
    case BatalRefund = 'batal_refund';

    public function label(): string
    {
        return match ($this) {
            self::Penawaran => 'Penawaran (Tentatif)',
            self::Terkonfirmasi => 'Terkonfirmasi (DP)',
            self::BelumMemenuhiAmbang => 'Belum Memenuhi Ambang',
            self::SiapPelaksanaan => 'Siap Pelaksanaan',
            self::Lunas => 'Lunas',
            self::BatalRefund => 'Batal - Refund',
        };
    }

    /** Status yang mengunci tanggal & harga (AB-02, AB-05). */
    public static function locking(): array
    {
        return [self::Terkonfirmasi, self::BelumMemenuhiAmbang, self::SiapPelaksanaan, self::Lunas];
    }
}
