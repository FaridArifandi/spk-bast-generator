<?php

namespace App\Enums;

enum Peran: string
{
    case Ppl = 'ppl';
    case Pml = 'pml';
    case Entri = 'entri';

    public function label(): string
    {
        return match ($this) {
            self::Ppl => 'PPL (Petugas Pendataan Lapangan)',
            self::Pml => 'PML (Petugas Pengawas Lapangan)',
            self::Entri => 'Petugas Entri',
        };
    }

    public function jabatan(): string
    {
        return match ($this) {
            self::Ppl => 'Petugas Pendataan Lapangan',
            self::Pml => 'Petugas Pengawas Lapangan',
            self::Entri => 'Petugas Entri',
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }
        return $out;
    }
}
