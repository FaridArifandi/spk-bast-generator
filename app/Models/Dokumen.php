<?php

namespace App\Models;

use App\Enums\Peran;
use App\Enums\StatusVerifikasi;
use App\Models\Concerns\HasPetugas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dokumen extends Model
{
    use HasPetugas, SoftDeletes;

    protected $table = 'dokumen';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_kepka' => 'date',
            'tanggal_spk' => 'date',
            'tanggal_bast_ppl_pml' => 'date',
            'tanggal_bast_ppl_sm' => 'date',
            'tanggal_bast_pml_sm' => 'date',
            'tanggal_bast_entri_sm' => 'date',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'batas_penyerahan' => 'date',
            'tanggal_verifikasi' => 'date',
            'diunduh_at' => 'datetime',
            'status_verifikasi' => StatusVerifikasi::class,
            'peran' => Peran::class,
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(DokumenDetail::class)->orderBy('no_urut');
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(Penugasan::class);
    }

    public function ppk(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'ppk_id');
    }

    public function ketuaTim(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'ketua_tim_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifikator_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** Tab dokumen yang relevan untuk peran petugas ini. */
    public function jenisBast(): array
    {
        return match ($this->peran) {
            Peran::Pml => ['pml-sm'],
            Peran::Entri => ['entri-sm'],
            default => ['ppl-pml', 'ppl-sm'],
        };
    }

    public function bastTerisi(string $jenis): bool
    {
        return match ($jenis) {
            'ppl-pml' => filled($this->no_bast_ppl_pml),
            'ppl-sm' => filled($this->no_bast_ppl_sm),
            'pml-sm' => filled($this->no_bast_pml_sm),
            'entri-sm' => filled($this->no_bast_entri_sm),
            default => true,
        };
    }

    /** SPK hanya boleh dicetak setelah disetujui PPK; BAST juga, kecuali bagi admin/katim. */
    public function bolehCetak(string $jenis, ?User $user): bool
    {
        if ($jenis === 'spk') {
            return $this->status_verifikasi->layakCetak();
        }
        if ($user?->bisaKelolaDokumen()) {
            return true;
        }
        return $this->status_verifikasi->layakCetak();
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

    public function scopeCari($q, ?string $kata)
    {
        if ($kata) {
            $q->where(function ($w) use ($kata) {
                $w->where('no_spk', 'like', "%$kata%")
                    ->orWhere('judul_pekerjaan', 'like', "%$kata%")
                    ->orWhereHas('mitra', fn ($m) => $m->where('nama', 'like', "%$kata%"))
                    ->orWhereHas('pegawai', fn ($m) => $m->where('nama', 'like', "%$kata%"));
            });
        }
        return $q;
    }
}
