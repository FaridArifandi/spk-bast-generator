<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kegiatan extends Model
{
    protected $table = 'kegiatan';

    protected $fillable = ['kode', 'uraian_tugas', 'satuan', 'harga_satuan', 'beban_anggaran', 'buku_pedoman', 'ketua_tim_id'];

    public function ketuaTim(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'ketua_tim_id');
    }

    public function scopeCari($q, ?string $kata)
    {
        if ($kata) {
            $q->where(fn ($w) => $w->where('uraian_tugas', 'like', "%$kata%")->orWhere('kode', 'like', "%$kata%"));
        }
        return $q;
    }
}
