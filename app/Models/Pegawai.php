<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $table = 'pegawai';

    protected $fillable = ['nama', 'nip', 'jabatan', 'pangkat', 'golongan', 'is_ppk'];

    protected function casts(): array
    {
        return ['is_ppk' => 'boolean'];
    }

    public function getPangkatGolonganAttribute(): string
    {
        return trim(($this->pangkat ?? '').' '.($this->golongan ?? ''));
    }

    public function scopeCari($q, ?string $kata)
    {
        if ($kata) {
            $q->where(fn ($w) => $w->where('nama', 'like', "%$kata%")->orWhere('nip', 'like', "%$kata%")->orWhere('jabatan', 'like', "%$kata%"));
        }
        return $q;
    }
}
