<?php

namespace Database\Seeders;

use App\Models\Kegiatan;
use App\Models\Pegawai;
use Illuminate\Database\Seeder;

/** Master nama kegiatan (bukan dokumen SPK/BAST) beserta ketua timnya. Jalankan setelah PegawaiSeeder. */
class KegiatanSeeder extends Seeder
{
    use BacaDataSeed;

    public function run(): void
    {
        $pegawaiId = Pegawai::pluck('id', 'nama');

        foreach ($this->dataSeed('kegiatan') as $k) {
            $atribut = [
                'uraian_tugas' => $k['uraian_tugas'],
                'satuan' => $k['satuan'] ?: 'Dokumen',
                'harga_satuan' => $k['harga_satuan'],
                'beban_anggaran' => $k['beban_anggaran'],
            ];

            // ketua tim hanya diisi bila ditemukan; tidak menimpa penetapan yang sudah ada dengan kosong
            if (! empty($k['ketua_tim']) && isset($pegawaiId[$k['ketua_tim']])) {
                $atribut['ketua_tim_id'] = $pegawaiId[$k['ketua_tim']];
            }

            Kegiatan::updateOrCreate(['kode' => $k['kode']], $atribut);
        }
    }
}
