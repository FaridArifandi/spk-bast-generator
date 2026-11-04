<?php

namespace App\Services;

use App\Models\Dokumen;
use App\Models\Mitra;
use App\Models\Parameter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HonorService
{
    public function batasMaksimum(): int
    {
        return (int) Parameter::nilai('BATAS_HONOR_MAKSIMUM', 0);
    }

    /**
     * Rekap honor per mitra untuk bulan & tahun tertentu (dokumen ditolak tidak dihitung).
     *
     * @return Collection<int,object{mitra:Mitra,jumlah:int,total:int,melebihi:bool}>
     */
    public function rekapMitra(int $tahun, ?string $bulan = null): Collection
    {
        $baris = Dokumen::query()
            ->where('petugas_tipe', 'mitra')
            ->whereNotNull('mitra_id')
            ->where('tahun', $tahun)
            ->where('status_verifikasi', '!=', 'ditolak')
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->groupBy('mitra_id')
            ->select('mitra_id', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(total_honor) as total'))
            ->orderByDesc('total')
            ->get();

        $mitra = Mitra::whereIn('id', $baris->pluck('mitra_id'))->get()->keyBy('id');
        $batas = $this->batasMaksimum();

        return $baris->map(fn ($b) => (object) [
            'mitra' => $mitra[$b->mitra_id] ?? null,
            'jumlah' => (int) $b->jumlah,
            'total' => (int) $b->total,
            'melebihi' => $batas > 0 && (int) $b->total > $batas,
        ])->filter(fn ($o) => $o->mitra);
    }
}
