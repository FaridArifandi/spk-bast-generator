<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    protected $table = 'mitra';

    protected $fillable = [
        'id_mitra', 'kategori', 'nama', 'nik', 'sobat_id', 'email', 'posisi', 'asal',
        'alamat_detail', 'tanggal_lahir', 'jenis_kelamin', 'no_telp', 'aktif',
    ];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date', 'aktif' => 'boolean'];
    }

    public function scopeCari($q, ?string $kata)
    {
        if ($kata) {
            $q->where(fn ($w) => $w->where('nama', 'like', "%$kata%")
                ->orWhere('id_mitra', 'like', "%$kata%")
                ->orWhere('nik', 'like', "%$kata%")
                ->orWhere('asal', 'like', "%$kata%"));
        }
        return $q;
    }
}
