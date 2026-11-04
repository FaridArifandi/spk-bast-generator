<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Http;

/**
 * Tarik data langsung dari Google Sheets lewat Apps Script lama
 * (endpoint doGet ?sheet=NamaSheet yang mengembalikan JSON).
 */
class SyncSheets extends ImportExcel
{
    protected $signature = 'spk:sync-sheets
        {--url= : URL Apps Script (/exec); default dari SHEETS_URL di .env}
        {--only= : Bagian yang ditarik: parameter,pegawai,mitra,kegiatan,penugasan,dokumen,sm-ppk (default: data master saja)}
        {--fresh : Kosongkan data master & dokumen lebih dulu (akun pengguna tidak disentuh)}
        {--dry-run : Hitung & laporkan saja, tidak menyimpan apa pun}';

    protected $description = 'Sinkron data dari Google Sheets (via Apps Script) ke database';

    /** peta bagian → nama sheet */
    private const SHEET = [
        'parameter' => ['Parameter'],
        'pegawai' => ['Pegawai'],
        'mitra' => ['Mitra'],
        'kegiatan' => ['Kegiatan'],
        'penugasan' => ['Mapping_Petugas'],
        'dokumen' => ['SPK_BAST', 'Detail_Pekerjaan'],
        'sm-ppk' => ['BAST_SM_PPK'],
    ];

    /**
     * Default hanya data master: aman dijalankan berulang (update-or-create).
     * Penugasan & dokumen selalu dibuat baru, jadi hanya ditarik bila diminta (sebaiknya dengan --fresh).
     */
    protected function bawaanOnly(): ?array
    {
        return ['parameter', 'pegawai', 'mitra', 'kegiatan'];
    }

    protected function muatSumber(): bool
    {
        $url = $this->option('url') ?: config('services.sheets.url');
        if (! $url) {
            $this->error('URL Apps Script belum diisi. Set SHEETS_URL di .env atau pakai --url=.');
            return false;
        }

        $only = $this->option('only') ? array_map('trim', explode(',', $this->option('only'))) : $this->bawaanOnly();
        $perlu = collect($only)->flatMap(fn ($k) => self::SHEET[$k] ?? [])->unique()->values();

        if ($perlu->isEmpty()) {
            $this->error('Nilai --only tidak dikenali. Pilihan: '.implode(', ', array_keys(self::SHEET)));
            return false;
        }

        foreach ($perlu as $nama) {
            $this->line("Mengambil sheet {$nama}…");
            try {
                $res = Http::timeout(90)->retry(2, 800)->acceptJson()->get($url, ['sheet' => $nama]);
                $data = $res->json();
            } catch (\Throwable $e) {
                $this->error("Gagal menghubungi Apps Script: {$e->getMessage()}");
                return false;
            }

            if (! $res->successful() || ! is_array($data)) {
                $this->error("Respons tidak valid untuk sheet {$nama} (HTTP {$res->status()}). Pastikan deployment Apps Script diakses 'Anyone'.");
                return false;
            }
            if (isset($data['status']) && $data['status'] === 'error') {
                $this->error("Sheet {$nama}: ".($data['message'] ?? 'galat tidak diketahui'));
                return false;
            }

            $this->sheets[$this->kunci($nama)] = $this->normalisasi($data);
        }

        return true;
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function normalisasi(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            if (! is_array($r)) {
                continue;
            }
            $row = [];
            foreach ($r as $h => $v) {
                $k = $this->kunci((string) $h);
                if ($k !== '') {
                    $row[$k] = $v;
                }
            }
            if (collect($row)->contains(fn ($v) => $v !== null && trim((string) $v) !== '')) {
                $out[] = $row;
            }
        }
        return $out;
    }
}
