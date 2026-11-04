<?php

namespace App\Services;

/**
 * Menyusun judul pekerjaan lengkap dengan periode (Bulan/Triwulan/Tahap/Subround/Semester/Tahun).
 * Port dari buildJudulDenganPeriode() sistem lama.
 */
class JudulPeriode
{
    /**
     * @param array{judul?:?string,tipe_periode?:?string,bulan?:?string,triwulan?:?string,tahap?:?string,subround?:?string,semester?:?string,tahun?:int|string|null} $r
     */
    public static function bangun(array $r): string
    {
        $raw = trim((string) ($r['judul'] ?? ''));
        if ($raw === '') {
            return '';
        }

        $raw = self::rapikan($raw);

        $tipe = strtolower(trim((string) ($r['tipe_periode'] ?? '')));
        $subround = (string) ($r['subround'] ?? '');
        $semester = (string) ($r['semester'] ?? '');
        $triwulan = (string) ($r['triwulan'] ?? '');
        $tahap = (string) ($r['tahap'] ?? '');
        $bulan = (string) ($r['bulan'] ?? '');
        $tahun = (string) ($r['tahun'] ?? '');
        $lower = mb_strtolower($raw);

        $periode = [];
        if ($tipe === 'subround' && $subround !== '') {
            $periode[] = "Subround $subround";
        } elseif ($tipe === 'semester' && $semester !== '') {
            $periode[] = "Semester $semester";
        } elseif ($tipe === 'triwulan' && $triwulan !== '') {
            $periode[] = "Triwulan $triwulan";
        } elseif ($tipe === 'tahap' && $tahap !== '') {
            $periode[] = "Tahap $tahap";
        } elseif ($tipe === 'bulan' && $bulan !== '') {
            $periode[] = "Bulan $bulan";
        } else {
            if ($tahap !== '' && (str_contains($lower, 'tahap') || $bulan === '')) {
                $periode[] = "Tahap $tahap";
            } elseif ($semester !== '' && (str_contains($lower, 'semester') || $bulan === '')) {
                $periode[] = "Semester $semester";
            } elseif ($subround !== '' && (str_contains($lower, 'subround') || str_contains($lower, 'ksa') || ($bulan === '' && $triwulan === ''))) {
                $periode[] = "Subround $subround";
            } elseif ($triwulan !== '' && (str_contains($lower, 'triwulan') || $bulan === '')) {
                $periode[] = "Triwulan $triwulan";
            } elseif ($bulan !== '') {
                $periode[] = "Bulan $bulan";
            }
        }
        if ($tahun !== '') {
            $periode[] = "Tahun $tahun";
        }

        $final = $raw;
        if ($periode) {
            $periodeStr = implode(' ', $periode);
            $sudahAda =
                str_contains($lower, mb_strtolower($periodeStr))
                || ($subround !== '' && str_contains($lower, 'subround '.mb_strtolower($subround)))
                || ($semester !== '' && str_contains($lower, 'semester '.mb_strtolower($semester)))
                || ($triwulan !== '' && str_contains($lower, 'triwulan '.mb_strtolower($triwulan)))
                || ($tahap !== '' && str_contains($lower, 'tahap '.mb_strtolower($tahap)))
                || ($bulan !== '' && str_contains($lower, mb_strtolower($bulan)));

            if (! $sudahAda) {
                if ($bulan !== '') {
                    $raw = trim(preg_replace('/\s*\bbulanan\s*$/iu', '', $raw));
                }
                foreach ([['triwulan', $triwulan], ['semester', $semester], ['subround', $subround], ['tahap', $tahap]] as [$kata, $nilai]) {
                    if ($nilai !== '') {
                        $raw = trim(preg_replace('/\s*\b'.$kata.'\s*$/iu', '', $raw));
                    }
                }
                $final = trim($raw.' '.$periodeStr);
            }
        }

        $final = preg_replace(
            ['/\btriwulan\s+triwulan\b/iu', '/\bsemester\s+semester\b/iu', '/\bsubround\s+subround\b/iu', '/\btahap\s+tahap\b/iu'],
            ['Triwulan', 'Semester', 'Subround', 'Tahap'],
            $final
        );
        $final = self::rapikan($final);

        return mb_strtoupper($final);
    }

    private static function rapikan(string $s): string
    {
        $bulan = '(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)';
        $s = preg_replace(
            [
                '/\bbulanan\s+bulan\b/iu',
                '/\bbulan\s+bulanan\b/iu',
                '/\bbulanan\s+bulanan\b/iu',
                '/\bbulanan\s+'.$bulan.'\b/iu',
            ],
            ['Bulan', 'Bulan', 'Bulanan', 'Bulan $1'],
            $s
        );
        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
