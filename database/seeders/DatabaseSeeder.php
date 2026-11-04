<?php

namespace Database\Seeders;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->parameter();
        $this->call([PegawaiSeeder::class, KegiatanSeeder::class, UserSeeder::class]);
        $this->admin(); // cadangan: hanya dibuat bila belum ada akun ADMIN
    }

    private function parameter(): void
    {
        $bawaan = [
            'NAMA_KANTOR' => ['BPS Kota Subulussalam', 'Nama kantor (untuk dokumen)'],
            'ALAMAT_KANTOR' => ['Jl. Raja Tua Lae Oram Komplek Perkantoran Walikota Subulussalam', 'Alamat kantor (untuk dokumen)'],
            'TAHUN_AKTIF' => [(string) now()->year, 'Tahun aktif sistem'],
            'BATAS_HONOR_MAKSIMUM' => ['3500000', 'Batas honor maksimum per mitra per bulan (Rp)'],
        ];

        foreach ($bawaan as $kode => [$nilai, $ket]) {
            Parameter::firstOrCreate(['kode' => $kode], ['nilai' => $nilai, 'keterangan' => $ket]);
        }
    }

    private function admin(): void
    {
        if (User::where('role', 'ADMIN')->exists()) {
            return;
        }

        $username = strtolower((string) config('services.admin.username', 'admin'));
        $adminPass = config('services.admin.password');
        $password = $adminPass ?: Str::password(14, symbols: false);

        User::create([
            'nama' => config('services.admin.nama', 'Administrator'),
            'username' => $username,
            'password' => $password,
            'role' => 'ADMIN',
            'aktif' => true,
        ]);

        if (! $adminPass) {
            $this->command?->warn("Akun administrator dibuat. Username: {$username}  Password: {$password}");
            $this->command?->warn('Catat password ini sekarang — tidak akan ditampilkan lagi. Ganti lewat menu Profil.');
        } else {
            $this->command?->info("Akun administrator '{$username}' dibuat dengan password dari ADMIN_PASSWORD.");
        }
    }
}
