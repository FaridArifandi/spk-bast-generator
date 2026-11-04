<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Helper format Indonesia: terbilang, tanggal, rupiah, jangka waktu.
 * Port dari utils.js / render.js sistem lama.
 */
class Indonesia
{
    public const BULAN = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    private const SATUAN = [
        '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan',
        'Sepuluh', 'Sebelas', 'Dua Belas', 'Tiga Belas', 'Empat Belas', 'Lima Belas',
        'Enam Belas', 'Tujuh Belas', 'Delapan Belas', 'Sembilan Belas',
    ];

    private const PULUHAN = [
        '', '', 'Dua Puluh', 'Tiga Puluh', 'Empat Puluh', 'Lima Puluh',
        'Enam Puluh', 'Tujuh Puluh', 'Delapan Puluh', 'Sembilan Puluh',
    ];

    private static function ratus(int $n): string
    {
        if ($n === 0) {
            return '';
        }
        if ($n < 20) {
            return self::SATUAN[$n];
        }
        if ($n < 100) {
            $p = intdiv($n, 10);
            $s = $n % 10;
            return self::PULUHAN[$p].($s > 0 ? ' '.self::SATUAN[$s] : '');
        }
        $r = intdiv($n, 100);
        $sisa = $n % 100;
        $prefix = $r === 1 ? 'Seratus' : self::SATUAN[$r].' Ratus';
        return $prefix.($sisa > 0 ? ' '.self::ratus($sisa) : '');
    }

    /** 660000 => "Enam Ratus Enam Puluh Ribu" */
    public static function terbilang(int|float|string|null $angka): string
    {
        $angka = (int) floor(abs((float) $angka));
        if ($angka === 0) {
            return 'Nol';
        }

        $triliun = intdiv($angka, 1_000_000_000_000);
        $miliar = intdiv($angka % 1_000_000_000_000, 1_000_000_000);
        $juta = intdiv($angka % 1_000_000_000, 1_000_000);
        $ribu = intdiv($angka % 1_000_000, 1_000);
        $sisa = $angka % 1_000;

        $hasil = '';
        if ($triliun > 0) {
            $hasil .= self::ratus($triliun).' Triliun ';
        }
        if ($miliar > 0) {
            $hasil .= self::ratus($miliar).' Miliar ';
        }
        if ($juta > 0) {
            $hasil .= self::ratus($juta).' Juta ';
        }
        if ($ribu > 0) {
            $hasil .= $ribu === 1 ? 'Seribu ' : self::ratus($ribu).' Ribu ';
        }
        if ($sisa > 0) {
            $hasil .= self::ratus($sisa);
        }

        return trim($hasil);
    }

    public static function terbilangRupiah(int|float|string|null $angka): string
    {
        return self::terbilang($angka).' Rupiah';
    }

    public static function rupiah(int|float|string|null $angka): string
    {
        return number_format((float) $angka, 0, ',', '.');
    }

    public static function tanggal(CarbonInterface|string|null $tgl): string
    {
        $c = self::carbon($tgl);
        return $c ? $c->day.' '.self::BULAN[$c->month].' '.$c->year : '-';
    }

    /**
     * Rincian tanggal dalam kata, untuk kalimat pembuka dokumen.
     * @return array{hari:string,tanggal:string,bulan:string,tahun:string,format:string,angka:string,tahunAngka:string}
     */
    public static function tanggalTerbilang(CarbonInterface|string|null $tgl): array
    {
        $c = self::carbon($tgl);
        if (! $c) {
            return ['hari' => '-', 'tanggal' => '-', 'bulan' => '-', 'tahun' => '-', 'format' => '-', 'angka' => '-', 'tahunAngka' => '-'];
        }

        return [
            'hari' => self::HARI[$c->dayOfWeek],
            'tanggal' => self::terbilang($c->day),
            'bulan' => self::BULAN[$c->month],
            'tahun' => self::terbilang($c->year),
            'format' => $c->format('d-m-Y'),
            'angka' => $c->format('d'),
            'tahunAngka' => (string) $c->year,
        ];
    }

    /** "24 s.d. 30 April 2026" / "10 April s.d. 10 Mei 2026" */
    public static function jangkaWaktu(CarbonInterface|string|null $mulai, CarbonInterface|string|null $selesai): string
    {
        $a = self::carbon($mulai);
        $b = self::carbon($selesai);
        if (! $a && ! $b) {
            return '—';
        }
        if ($a && ! $b) {
            return self::tanggal($a);
        }
        if (! $a && $b) {
            return self::tanggal($b);
        }
        if ($a->year === $b->year) {
            if ($a->month === $b->month) {
                return $a->day.' s.d. '.$b->day.' '.self::BULAN[$b->month].' '.$b->year;
            }
            return $a->day.' '.self::BULAN[$a->month].' s.d. '.$b->day.' '.self::BULAN[$b->month].' '.$b->year;
        }
        return self::tanggal($a).' s.d. '.self::tanggal($b);
    }

    public static function namaBulan(?int $bulan): string
    {
        return $bulan && isset(self::BULAN[$bulan]) ? self::BULAN[$bulan] : '';
    }

    /** index bulan 1..12 dari nama bulan (case-insensitive), null jika tak dikenal */
    public static function indexBulan(?string $nama): ?int
    {
        if (! $nama) {
            return null;
        }
        foreach (self::BULAN as $i => $n) {
            if (strcasecmp($n, trim($nama)) === 0) {
                return $i;
            }
        }
        return null;
    }

    /** @return array{triwulan:int,subround:int,semester:int} */
    public static function periode(int $bulan): array
    {
        return [
            'triwulan' => intdiv($bulan - 1, 3) + 1,
            'subround' => $bulan <= 4 ? 1 : ($bulan <= 8 ? 2 : 3),
            'semester' => $bulan <= 6 ? 1 : 2,
        ];
    }

    public static function carbon(CarbonInterface|string|null $tgl): ?CarbonInterface
    {
        if ($tgl instanceof CarbonInterface) {
            return $tgl;
        }
        if ($tgl === null || trim((string) $tgl) === '') {
            return null;
        }
        try {
            return Carbon::parse($tgl);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Hilangkan awalan "Nomor " dan rapikan spasi. */
    public static function bersihNomor(?string $no): string
    {
        return trim(preg_replace('/^\s*nomor\s+/i', '', (string) $no));
    }
}
