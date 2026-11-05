<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Services\SheetsExportService;
use Illuminate\Http\Request;

class ParameterController extends Controller
{
    /** Parameter yang dipakai aplikasi: kode => [label, tipe] */
    public const DAFTAR = [
        'NAMA_KANTOR' => ['Nama kantor (untuk dokumen)', 'text'],
        'ALAMAT_KANTOR' => ['Alamat kantor (untuk dokumen)', 'text'],
        'TAHUN_AKTIF' => ['Tahun aktif sistem', 'number'],
        'BATAS_HONOR_MAKSIMUM' => ['Batas honor maksimum per mitra per bulan (Rp)', 'number'],
    ];

    public function edit(SheetsExportService $sheets)
    {
        return view('admin.parameter', [
            'daftar' => self::DAFTAR,
            'nilai' => Parameter::pluck('nilai', 'kode'),
            'sheetsSiap' => $sheets->siap(),
            'sheetsStatus' => $sheets->statusTerakhir(),
        ]);
    }

    public function sinkronSheets(SheetsExportService $sheets)
    {
        $hasil = $sheets->kirim(true);

        return back()->with($hasil['status'] === 'ok' ? 'sukses' : 'galat', 'Ekspor ke Sheets: '.$hasil['pesan']);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (self::DAFTAR as $kode => [, $tipe]) {
            $rules[$kode] = $tipe === 'number' ? ['nullable', 'integer', 'min:0'] : ['nullable', 'string', 'max:500'];
        }
        $data = $request->validate($rules);

        foreach ($data as $kode => $nilai) {
            Parameter::updateOrCreate(['kode' => $kode], ['nilai' => $nilai, 'keterangan' => self::DAFTAR[$kode][0]]);
        }

        return back()->with('sukses', 'Parameter disimpan.');
    }
}
