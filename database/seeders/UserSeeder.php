<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Akun pengguna awal (admin, PPK, KATIM per tim, staf), masing-masing dikaitkan ke pegawainya.
 * Kata sandi lama tidak dibawa. Bila SEED_USER_PASSWORD kosong, tiap akun diberi sandi acak
 * yang ditampilkan sekali. Akun yang sudah ada tidak diubah. Jalankan setelah PegawaiSeeder.
 */
class UserSeeder extends Seeder
{
    use BacaDataSeed;

    public function run(): void
    {
        $sandiSama = config('services.seed.password');
        $pegawaiId = Pegawai::pluck('id', 'nama');
        $baru = [];

        foreach ($this->dataSeed('users') as $u) {
            if (User::where('username', $u['username'])->exists()) {
                continue;
            }

            $sandi = $sandiSama ?: Str::password(12, symbols: false);

            User::create([
                'nama' => preg_replace('/,.*$/', '', $u['pegawai']),
                'username' => $u['username'],
                'password' => $sandi,
                'role' => $u['role'],
                'sub_role' => $u['sub_role'],
                'pegawai_id' => $pegawaiId[$u['pegawai']] ?? null,
                'aktif' => true,
            ]);

            $baru[] = [$u['username'], $u['role'], $u['sub_role'] ?? '-', $sandiSama ? '(dari SEED_USER_PASSWORD)' : $sandi];
        }

        if ($baru) {
            $this->command?->table(['Username', 'Peran', 'Tim', 'Password'], $baru);
            if (! $sandiSama) {
                $this->command?->warn('Catat password di atas sekarang, tidak ditampilkan lagi. Minta tiap pengguna menggantinya lewat menu Profil.');
            }
        }
    }
}
