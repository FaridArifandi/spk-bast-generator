<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use Illuminate\Database\Seeder;

/** Daftar pegawai BPS Kota Subulussalam (dari sheet Pegawai). PPK ditandai dari jabatannya. */
class PegawaiSeeder extends Seeder
{
    use BacaDataSeed;

    public function run(): void
    {
        foreach ($this->dataSeed('pegawai') as $p) {
            $pegawai = Pegawai::updateOrCreate(['nama' => $p['nama']], [
                'nip' => $p['nip'],
                'jabatan' => $p['jabatan'],
                'pangkat' => $p['pangkat'],
                'golongan' => $p['golongan'],
            ]);

            // hanya menyalakan penanda PPK; tidak pernah mematikan penetapan yang dibuat di aplikasi
            if (! empty($p['is_ppk']) && ! $pegawai->is_ppk) {
                $pegawai->forceFill(['is_ppk' => true])->save();
            }
        }
    }
}
