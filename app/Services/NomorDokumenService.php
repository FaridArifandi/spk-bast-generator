<?php

namespace App\Services;

use App\Models\BastSmPpk;
use App\Models\Dokumen;

/**
 * Penomoran otomatis & deteksi duplikasi nomor SPK / BAST.
 * Format yang dipakai sistem lama:
 *   SPK  : "12 TAHUN 2026"
 *   BAST : "Nomor 012 Tahun 2026"
 */
class NomorDokumenService
{
    private const KOLOM_SPK = ['no_spk'];
    private const KOLOM_BAST = ['no_bast_ppl_pml', 'no_bast_ppl_sm', 'no_bast_pml_sm', 'no_bast_entri_sm'];

    public function formatSpk(int $urut, int $tahun): string
    {
        return "$urut TAHUN $tahun";
    }

    public function formatBast(int $urut, int $tahun): string
    {
        return sprintf('Nomor %03d Tahun %d', $urut, $tahun);
    }

    /** Nomor urut tertinggi yang sudah dipakai pada tahun tersebut. */
    public function maks(int $tahun): array
    {
        $maksSpk = 0;
        $maksBast = 0;

        Dokumen::withTrashed()->where('tahun', $tahun)->get(array_merge(self::KOLOM_SPK, self::KOLOM_BAST))
            ->each(function ($d) use (&$maksSpk, &$maksBast) {
                foreach (self::KOLOM_SPK as $k) {
                    $maksSpk = max($maksSpk, $this->urut($d->$k));
                }
                foreach (self::KOLOM_BAST as $k) {
                    $maksBast = max($maksBast, $this->urut($d->$k));
                }
            });

        BastSmPpk::withTrashed()->where('tahun', $tahun)->pluck('no_bast')
            ->each(function ($no) use (&$maksBast) {
                $maksBast = max($maksBast, $this->urut($no));
            });

        return ['spk' => $maksSpk, 'bast' => $maksBast];
    }

    /** Usulan nomor berikutnya. @return array{spk:string,bast:string,spk_urut:int,bast_urut:int} */
    public function usulan(int $tahun): array
    {
        $m = $this->maks($tahun);
        return [
            'spk' => $this->formatSpk($m['spk'] + 1, $tahun),
            'bast' => $this->formatBast($m['bast'] + 1, $tahun),
            'spk_urut' => $m['spk'] + 1,
            'bast_urut' => $m['bast'] + 1,
        ];
    }

    /** Ambil angka urut pertama pada nomor ("Nomor 012 Tahun 2026" → 12). */
    public function urut(?string $nomor): int
    {
        if (! $nomor) {
            return 0;
        }
        $nomor = preg_replace('/^\s*nomor\s+/i', '', $nomor);
        return preg_match('/\d+/', $nomor, $m) ? (int) $m[0] : 0;
    }

    public function normal(?string $nomor): string
    {
        return strtolower(preg_replace('/\s+/', '', preg_replace('/^\s*nomor\s*/i', '', (string) $nomor)));
    }

    /**
     * Cari dokumen yang sudah memakai nomor SPK ini (selain dokumen $abaikanId).
     */
    public function pakaiSpk(string $nomor, ?int $abaikanId = null): ?Dokumen
    {
        return $this->cariDi(self::KOLOM_SPK, $nomor, $abaikanId);
    }

    /** Nomor BAST dianggap bentrok jika dipakai di kolom BAST mana pun (kecuali di dokumen yang sama). */
    public function pakaiBast(string $nomor, ?int $abaikanId = null): ?Dokumen
    {
        return $this->cariDi(self::KOLOM_BAST, $nomor, $abaikanId);
    }

    /**
     * Nomor BAST dipakai di mana pun: kolom BAST dokumen maupun BAST SM → PPK
     * (keduanya satu urutan penomoran). Abaikan satu baris saat sedang menyunting barisnya sendiri.
     */
    public function bastDipakai(string $nomor, ?int $abaikanDokumenId = null, ?int $abaikanSmPpkId = null): bool
    {
        return $this->pakaiBast($nomor, $abaikanDokumenId) !== null
            || $this->pakaiBastSmPpk($nomor, $abaikanSmPpkId);
    }

    public function pakaiBastSmPpk(string $nomor, ?int $abaikanId = null): bool
    {
        $target = $this->normal($nomor);
        return BastSmPpk::withTrashed()
            ->when($abaikanId, fn ($q) => $q->where('id', '!=', $abaikanId))
            ->pluck('no_bast')
            ->contains(fn ($n) => $this->normal($n) === $target);
    }

    private function cariDi(array $kolom, string $nomor, ?int $abaikanId): ?Dokumen
    {
        $target = $this->normal($nomor);
        if ($target === '') {
            return null;
        }

        return Dokumen::withTrashed()
            ->when($abaikanId, fn ($q) => $q->where('id', '!=', $abaikanId))
            ->where(function ($q) use ($kolom) {
                foreach ($kolom as $k) {
                    $q->orWhereNotNull($k);
                }
            })
            ->get(array_merge(['id'], $kolom))
            ->first(function ($d) use ($kolom, $target) {
                foreach ($kolom as $k) {
                    if ($d->$k && $this->normal($d->$k) === $target) {
                        return true;
                    }
                }
                return false;
            });
    }
}
