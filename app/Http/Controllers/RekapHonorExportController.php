<?php

namespace App\Http\Controllers;

use App\Models\Parameter;
use App\Services\HonorService;
use App\Support\Indonesia;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapHonorExportController extends Controller
{
    public function __invoke(Request $request, HonorService $honor): StreamedResponse
    {
        $tahun = (int) $request->input('tahun', (int) Parameter::nilai('TAHUN_AKTIF', now()->year));
        $bulan = $request->input('bulan');
        if ($bulan === 'semua' || ! $bulan) {
            $bulan = null;
        }

        $rekap = $honor->rekapMitra($tahun, $bulan);
        $batas = $honor->batasMaksimum();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Honor Mitra');

        $periodeText = $bulan ? "Bulan $bulan Tahun $tahun" : "Tahun $tahun (Semua Bulan)";
        $kantor = (string) Parameter::nilai('NAMA_KANTOR', 'Badan Pusat Statistik');

        // Header Dokumen
        $sheet->setCellValue('A1', 'REKAPITULASI HONOR MITRA STATISTIK');
        $sheet->setCellValue('A2', strtoupper($kantor));
        $sheet->setCellValue('A3', "Periode: $periodeText");
        if ($batas > 0) {
            $sheet->setCellValue('A4', 'Batas Honor Maksimum per Mitra/Bulan: Rp '.Indonesia::rupiah($batas));
        }

        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);

        $rowStart = 6;
        $headers = ['No', 'ID Mitra', 'Nama Mitra', 'NIK', 'Asal', 'Jumlah SPK', 'Total Honor (Rp)', 'Keterangan'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        foreach ($headers as $idx => $h) {
            $sheet->setCellValue($cols[$idx].$rowStart, $h);
        }

        $sheet->getStyle("A{$rowStart}:H{$rowStart}")->getFont()->setBold(true);
        $sheet->getStyle("A{$rowStart}:H{$rowStart}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = $rowStart + 1;
        $no = 1;
        $totalSemua = 0;

        foreach ($rekap as $item) {
            $m = $item->mitra;
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValueExplicit("B{$row}", (string) $m->id_mitra, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", (string) $m->nama);
            $sheet->setCellValueExplicit("D{$row}", (string) $m->nik, DataType::TYPE_STRING);
            $sheet->setCellValue("E{$row}", (string) ($m->asal ?: '-'));
            $sheet->setCellValue("F{$row}", $item->jumlah);
            $sheet->setCellValue("G{$row}", $item->total);
            $sheet->setCellValue("H{$row}", $item->melebihi ? 'MELEBIHI BATAS' : 'Normal');

            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $totalSemua += $item->total;
            $row++;
        }

        // Baris Total
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("G{$row}", $totalSemua);
        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');

        // Border tabel
        $styleBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF999999'],
                ],
            ],
        ];
        $sheet->getStyle("A{$rowStart}:H{$row}")->applyFromArray($styleBorder);

        // Auto width
        foreach ($cols as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $namaFile = 'Rekap_Honor_Mitra_'.($bulan ?: 'Semua')."_{$tahun}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

