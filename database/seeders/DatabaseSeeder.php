<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->parameter();
        $this->call([PegawaiSeeder::class, KegiatanSeeder::class, UserSeeder::class]);
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
}
