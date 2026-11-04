<?php

namespace App\Models;

use App\Enums\StatusVerifikasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BastSmPpk extends Model
{
    use SoftDeletes;

    protected $table = 'bast_sm_ppk';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_bast' => 'date',
            'tanggal_verifikasi' => 'date',
            'diunduh_at' => 'datetime',
            'status_verifikasi' => StatusVerifikasi::class,
        ];
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

    public function bolehCetak(?User $user): bool
    {
        return $user?->bisaKelolaDokumen() || $this->status_verifikasi->layakCetak();
    }
}
