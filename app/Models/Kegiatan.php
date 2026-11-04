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

    /** Kegiatan yang boleh dipakai user: semua untuk ADMIN; untuk KATIM hanya timnya + yang belum punya ketua tim. */
    public function scopeUntuk($q, ?User $user)
    {
        if (! $user || $user->isAdmin()) {
            return $q;
        }

        return $q->where(fn ($w) => $w->whereNull('ketua_tim_id')
            ->when($user->pegawai_id, fn ($x) => $x->orWhere('ketua_tim_id', $user->pegawai_id)));
    }

    public function scopeCari($q, ?string $kata)
    {
        if ($kata) {
            $q->where(fn ($w) => $w->where('uraian_tugas', 'like', "%$kata%")->orWhere('kode', 'like', "%$kata%"));
        }
        return $q;
    }
}
