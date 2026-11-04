<?php

namespace App\Models;

use App\Models\Concerns\HasPetugas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penugasan extends Model
{
    use HasPetugas;

    protected $table = 'penugasan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'batas_terlambat' => 'date',
            'tanggal_kepka' => 'date',
        ];
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(Dokumen::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function scopeBelumDipakai($q)
    {
        return $q->whereNull('dokumen_id');
    }

    public function getPeriodeLabelAttribute(): string
    {
        return match ($this->tipe_periode) {
            'triwulan' => 'Triwulan '.$this->triwulan.' '.$this->tahun,
            'tahap' => 'Tahap '.$this->tahap.' '.$this->tahun,
            'subround' => 'Subround '.$this->subround.' '.$this->tahun,
            'semester' => 'Semester '.$this->semester.' '.$this->tahun,
            default => trim($this->bulan.' '.$this->tahun),
        };
    }
}
