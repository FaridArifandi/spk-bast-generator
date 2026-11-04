<?php

namespace App\Services;

use App\Models\Kegiatan;
use App\Models\Penugasan;
use App\Models\User;
use App\Support\Indonesia as Id;
use Illuminate\Support\Facades\DB;

class PenugasanService
{
    /**
     * Simpan satu kegiatan untuk banyak petugas sekaligus (wizard KATIM lama).
     *
     * @param array $data  field umum (kegiatan_id, periode, tanggal, kepka)
     * @param array<int,array> $rows  petugas, peran, pml, volume, harga_satuan
     * @return int jumlah baris tersimpan
     */
    public function simpan(array $data, array $rows, User $user): int
    {
        $kegiatan = Kegiatan::findOrFail($data['kegiatan_id']);
        $tipe = $data['tipe_periode'] ?? 'bulan';
        $mulai = Id::carbon($data['tanggal_mulai'] ?? null);

        $periode = [
            'tipe_periode' => $tipe,
            'bulan' => null, 'triwulan' => null, 'tahap' => null, 'subround' => null, 'semester' => null,
        ];
        $periode[$tipe] = $data[$tipe] ?? null;
        if (empty($periode['bulan']) && $mulai) {
            $periode['bulan'] = Id::BULAN[$mulai->month];
        }

        $batas = Id::carbon($data['batas_terlambat'] ?? null)
            ?? Id::carbon($data['tanggal_selesai'] ?? null)?->copy()->addDays(5);

        return DB::transaction(function () use ($rows, $data, $kegiatan, $periode, $user, $batas) {
            $n = 0;
            foreach ($rows as $r) {
                if (empty($r['petugas'])) {
                    continue;
                }
                $p = new Penugasan();
                $p->setPetugasDari($r['petugas']);
                $p->setPmlDari($r['pml'] ?? null);
                $p->kegiatan_id = $kegiatan->id;
                $p->peran = $r['peran'] ?? 'ppl';
                $p->tanggal_mulai = $data['tanggal_mulai'] ?? null;
                $p->tanggal_selesai = $data['tanggal_selesai'] ?? null;
                $p->batas_terlambat = $batas?->toDateString();
                $p->nomor_kepka = $data['nomor_kepka'] ?? null;
                $p->tanggal_kepka = $data['tanggal_kepka'] ?? null;
                $p->forceFill($periode);
                $p->tahun = (int) $data['tahun'];

                $volume = max(1, (int) ($r['volume'] ?? 1));
                $harga = (int) ($r['harga_satuan'] ?? $kegiatan->harga_satuan ?? 0);
                $p->volume = $volume;
                $p->satuan = $kegiatan->satuan ?: 'Dokumen';
                $p->harga_satuan = $harga;
                $p->total_honor = $volume * $harga;
                $p->dibuat_oleh = $user->id;
                $p->save();
                $n++;
            }
            return $n;
        });
    }
}
