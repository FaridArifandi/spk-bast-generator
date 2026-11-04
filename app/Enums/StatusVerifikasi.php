<?php

namespace App\Enums;

enum StatusVerifikasi: string
{
    case Menunggu = 'menunggu_verifikasi';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case TidakPerlu = 'tidak_perlu';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu Verifikasi',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
            self::TidakPerlu => 'Tidak Perlu',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Menunggu => 'warning',
            self::Disetujui => 'success',
            self::Ditolak => 'danger',
            self::TidakPerlu => 'gray',
        };
    }

    public function layakCetak(): bool
    {
        return $this === self::Disetujui || $this === self::TidakPerlu;
    }
}
