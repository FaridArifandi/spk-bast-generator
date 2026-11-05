<?php

namespace App\Console\Commands;

use App\Services\SheetsExportService;
use Illuminate\Console\Command;

class ExportSheets extends Command
{
    protected $signature = 'spk:export-sheets {--force : Kirim walau tidak ada perubahan}';

    protected $description = 'Ekspor searah database ke Google Sheets (via Apps Script doPost)';

    public function handle(SheetsExportService $svc): int
    {
        $hasil = $svc->kirim((bool) $this->option('force'));
        $baris = "[{$hasil['status']}] {$hasil['pesan']}";

        match ($hasil['status']) {
            'ok' => $this->info($baris),
            'gagal' => $this->error($baris),
            default => $this->line($baris),
        };

        return $hasil['status'] === 'gagal' ? self::FAILURE : self::SUCCESS;
    }
}
