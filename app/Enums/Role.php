<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'ADMIN';
    case Ppk = 'PPK';
    case Katim = 'KATIM';
    case Pengguna = 'PENGGUNA';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Ppk => 'Pejabat Pembuat Komitmen (PPK)',
            self::Katim => 'Ketua Tim (KATIM)',
            self::Pengguna => 'Pengguna / Staf',
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
