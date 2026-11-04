<?php

namespace App\Models\Concerns;

use App\Models\Mitra;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Petugas bisa berupa Mitra ATAU Pegawai (organik). Dipakai Penugasan & Dokumen.
 */
trait HasPetugas
{
    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function pmlMitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class, 'pml_mitra_id');
    }

    public function pmlPegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pml_pegawai_id');
    }

    public function getPetugasNamaAttribute(): string
    {
        return $this->petugas_tipe === 'pegawai'
            ? (string) $this->pegawai?->nama
            : (string) $this->mitra?->nama;
    }

    /** NIK untuk mitra, NIP untuk pegawai organik */
    public function getPetugasNomorIndukAttribute(): string
    {
        return $this->petugas_tipe === 'pegawai'
            ? (string) $this->pegawai?->nip
            : (string) $this->mitra?->nik;
    }

    public function getPmlLabelAttribute(): string
    {
        return $this->pmlMitra?->nama ?? $this->pmlPegawai?->nama ?? (string) $this->pml_nama;
    }

    /** Isi kolom petugas dari string "mitra:12" / "pegawai:3" */
    public function setPetugasDari(string $ref): void
    {
        [$tipe, $id] = array_pad(explode(':', $ref, 2), 2, null);
        $this->petugas_tipe = $tipe === 'pegawai' ? 'pegawai' : 'mitra';
        $this->mitra_id = $this->petugas_tipe === 'mitra' ? (int) $id : null;
        $this->pegawai_id = $this->petugas_tipe === 'pegawai' ? (int) $id : null;
    }

    public function setPmlDari(?string $ref): void
    {
        $this->pml_mitra_id = $this->pml_pegawai_id = null;
        if (! $ref) {
            return;
        }
        [$tipe, $id] = array_pad(explode(':', $ref, 2), 2, null);
        if ($tipe === 'pegawai') {
            $this->pml_pegawai_id = (int) $id;
        } elseif ($tipe === 'mitra') {
            $this->pml_mitra_id = (int) $id;
        }
    }

    public function getPetugasRefAttribute(): string
    {
        return $this->petugas_tipe === 'pegawai' ? 'pegawai:'.$this->pegawai_id : 'mitra:'.$this->mitra_id;
    }
}
