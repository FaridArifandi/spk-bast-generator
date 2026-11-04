<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['nama', 'username', 'password', 'role', 'sub_role', 'pegawai_id', 'aktif'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'aktif' => 'boolean',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function roleEnum(): Role
    {
        return Role::tryFrom((string) $this->role) ?? Role::Pengguna;
    }

    public function hasRole(Role|string ...$roles): bool
    {
        foreach ($roles as $r) {
            $value = $r instanceof Role ? $r->value : strtoupper($r);
            if ($this->role === $value) {
                return true;
            }
        }
        return false;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin);
    }

    /** Boleh membuat/mengubah dokumen & penugasan */
    public function bisaKelolaDokumen(): bool
    {
        return $this->hasRole(Role::Admin, Role::Katim);
    }

    public function bisaVerifikasi(): bool
    {
        return $this->hasRole(Role::Admin, Role::Ppk);
    }

    /**
     * Boleh mengelola data milik tim tertentu. ADMIN selalu boleh; KATIM hanya untuk timnya sendiri
     * (pegawai akunnya = ketua tim data tsb). Data tanpa ketua tim terbuka bagi semua KATIM
     * sampai ketua tim ditetapkan.
     */
    public function bisaKelolaTim(?int $ketuaTimId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if (! $this->hasRole(Role::Katim)) {
            return false;
        }

        return $ketuaTimId === null
            || ($this->pegawai_id !== null && (int) $this->pegawai_id === (int) $ketuaTimId);
    }

    /** Boleh memutuskan verifikasi dokumen milik PPK tertentu (ADMIN bebas; PPK hanya miliknya sendiri). */
    public function bisaVerifikasiPpk(?int $ppkId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if (! $this->hasRole(Role::Ppk)) {
            return false;
        }

        return $ppkId === null
            || ($this->pegawai_id !== null && (int) $this->pegawai_id === (int) $ppkId);
    }
}
