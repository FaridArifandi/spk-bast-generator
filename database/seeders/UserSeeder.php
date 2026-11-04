<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun pengguna awal (admin, PPK, KATIM per tim, staf), masing-masing dikaitkan ke pegawainya.
 * Semua akun memakai password awal 123456 — WAJIB diganti tiap pengguna lewat menu Profil.
 * Akun yang sudah ada tidak diubah (password-nya aman bila seeder dijalankan ulang).
 * Jalankan setelah PegawaiSeeder.
 */
class UserSeeder extends Seeder
{
    use BacaDataSeed;

    public const PASSWORD_AWAL = '123456';

    public function run(): void
    {
        $pegawaiId = Pegawai::pluck('id', 'nama');
        $baru = [];

        foreach ($this->dataSeed('users') as $u) {
            if (User::where('username', $u['username'])->exists()) {
                continue;
            }

            User::create([
                'nama' => preg_replace('/,.*$/', '', $u['pegawai']),
                'username' => $u['username'],
                'password' => self::PASSWORD_AWAL,
                'role' => $u['role'],
                'sub_role' => $u['sub_role'],
                'pegawai_id' => $pegawaiId[$u['pegawai']] ?? null,
                'aktif' => true,
            ]);

            $baru[] = [$u['username'], $u['role'], $u['sub_role'] ?? '-'];
        }

        if ($baru) {
            $this->command?->table(['Username', 'Peran', 'Tim'], $baru);
            $this->command?->warn('Semua akun baru memakai password awal '.self::PASSWORD_AWAL.'. Minta tiap pengguna menggantinya lewat menu Profil.');
        }
    }
}
