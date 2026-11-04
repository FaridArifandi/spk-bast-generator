<?php

namespace App\Http\Controllers;

use App\Models\BastSmPpk;
use App\Models\Dokumen;
use App\Models\Mitra;
use App\Models\Penugasan;
use App\Services\HonorService;
use App\Support\Indonesia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, HonorService $honor)
    {
        $tahunDefault = (int) \App\Models\Parameter::nilai('TAHUN_AKTIF', now()->year);
        $tahun = (int) $request->input('tahun', $tahunDefault);
        $bulan = $request->input('bulan', Indonesia::BULAN[now()->month]);
        if ($bulan === 'semua') {
            $bulan = null;
        }

        $stat = [
            'dokumen' => Dokumen::where('tahun', $tahun)->count(),
            'menunggu' => Dokumen::where('tahun', $tahun)->where('status_verifikasi', 'menunggu_verifikasi')->count()
                + BastSmPpk::where('tahun', $tahun)->where('status_verifikasi', 'menunggu_verifikasi')->count(),
            'disetujui' => Dokumen::where('tahun', $tahun)->where('status_verifikasi', 'disetujui')->count(),
            'ditolak' => Dokumen::where('tahun', $tahun)->where('status_verifikasi', 'ditolak')->count(),
            'penugasan_belum' => Penugasan::belumDipakai()->where('tahun', $tahun)->count(),
            'mitra' => Mitra::where('aktif', true)->count(),
        ];

        return view('dashboard.index', [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'stat' => $stat,
            'rekap' => $honor->rekapMitra($tahun, $bulan),
            'batas' => $honor->batasMaksimum(),
            'terbaru' => Dokumen::with(['mitra', 'pegawai'])->where('tahun', $tahun)->latest('id')->limit(8)->get(),
            'daftarTahun' => range(now()->year + 1, 2024),
        ]);
    }
}
