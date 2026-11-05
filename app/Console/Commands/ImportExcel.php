<?php

namespace App\Console\Commands;

use App\Models\BastSmPpk;
use App\Models\Dokumen;
use App\Models\DokumenDetail;
use App\Models\Kegiatan;
use App\Models\Mitra;
use App\Models\Parameter;
use App\Models\Pegawai;
use App\Models\Penugasan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportExcel extends Command
{
    protected $signature = 'spk:import-excel
        {file : Path berkas .xlsx (hasil unduhan Google Sheets sistem lama, mis. DB_SPK_BAST.xlsx)}
        {--fresh : Kosongkan data master & dokumen lebih dulu (akun pengguna tidak disentuh)}
        {--only= : Impor sebagian saja, pisahkan koma: parameter,pegawai,mitra,kegiatan,penugasan,dokumen,sm-ppk}
        {--dry-run : Hitung & laporkan saja, tidak menyimpan apa pun}';

    protected $description = 'Impor data dari spreadsheet sistem lama (Google Sheets) ke database Laravel';

    /** @var array<string,array<int,array<string,mixed>>> */
    protected array $sheets = [];
    private array $lapor = [];
    private array $peringatan = [];

    private array $pegawaiCache = [];
    private array $mitraCache = [];

    public function handle(): int
    {
        if (! $this->muatSumber()) {
            return self::FAILURE;
        }

        $only = $this->option('only') ? array_map('trim', explode(',', $this->option('only'))) : $this->bawaanOnly();
        $perlu = fn (string $k) => $only === null || in_array($k, $only, true);

        DB::beginTransaction();
        try {
            if ($this->option('fresh')) {
                $this->kosongkan();
            }

            $perlu('parameter') && $this->impParameter();
            $perlu('pegawai') && $this->impPegawai();
            $this->muatCache();
            $perlu('mitra') && $this->impMitra();
            $perlu('kegiatan') && $this->impKegiatan();
            $this->muatCache();
            $perlu('penugasan') && $this->impPenugasan();
            $perlu('dokumen') && $this->impDokumen();
            $perlu('sm-ppk') && $this->impSmPpk();

            $this->option('dry-run') ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Impor dibatalkan: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->table(['Data', 'Diimpor', 'Dilewati'], collect($this->lapor)->map(fn ($v, $k) => [$k, $v[0], $v[1]])->values()->all());
        foreach (array_slice($this->peringatan, 0, 25) as $p) {
            $this->warn(' • '.$p);
        }
        if (count($this->peringatan) > 25) {
            $this->warn(' • … dan '.(count($this->peringatan) - 25).' peringatan lain.');
        }
        $this->option('dry-run')
            ? $this->comment('Mode --dry-run: tidak ada data yang disimpan.')
            : $this->info('Selesai. Aman dijalankan ulang: data yang sudah ada dilewati. Akun pengguna tidak diimpor.');

        return self::SUCCESS;
    }

    // ───────────────────────── membaca sheet ─────────────────────────

    /** Bagian yang diimpor bila --only tidak diisi (null = semua). */
    protected function bawaanOnly(): ?array
    {
        return null;
    }

    /** Muat data ke $this->sheets; false bila gagal. */
    protected function muatSumber(): bool
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("Berkas tidak ditemukan: $file");
            return false;
        }
        $this->info('Membaca spreadsheet…');
        $this->muatSheet($file);
        return true;
    }

    private function muatSheet(string $file): void
    {
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $book = $reader->load($file);

        foreach ($book->getWorksheetIterator() as $ws) {
            $rows = $ws->toArray(null, false, false, false);
            if (count($rows) < 2) {
                $this->sheets[$this->kunci($ws->getTitle())] = [];
                continue;
            }
            $head = array_map(fn ($h) => $this->kunci((string) $h), array_shift($rows));
            $data = [];
            foreach ($rows as $r) {
                $row = [];
                foreach ($head as $i => $h) {
                    if ($h !== '' && array_key_exists($i, $r)) {
                        $row[$h] = $r[$i];
                    }
                }
                if (collect($row)->contains(fn ($v) => $v !== null && trim((string) $v) !== '')) {
                    $data[] = $row;
                }
            }
            $this->sheets[$this->kunci($ws->getTitle())] = $data;
        }
    }

    protected function kunci(string $s): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower($s));
    }

    private function sheet(string ...$nama): array
    {
        foreach ($nama as $n) {
            if (isset($this->sheets[$this->kunci($n)])) {
                return $this->sheets[$this->kunci($n)];
            }
        }
        return [];
    }

    /** ambil nilai dari beberapa kemungkinan nama kolom */
    private function v(array $row, string ...$kolom): mixed
    {
        foreach ($kolom as $k) {
            $k = $this->kunci($k);
            if (array_key_exists($k, $row) && $row[$k] !== null && trim((string) $row[$k]) !== '') {
                return $row[$k];
            }
        }
        return null;
    }

    private function str(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_float($v) || is_int($v)) {
            return (string) ($v == floor($v) ? sprintf('%.0f', $v) : $v);
        }
        $s = trim((string) $v);
        return $s === '' || $s === '-' ? null : $s;
    }

    private function int(mixed $v): int
    {
        return (int) round((float) preg_replace('/[^0-9.\-]/', '', (string) ($v ?? 0)));
    }

    private function tgl(mixed $v): ?string
    {
        if ($v === null || trim((string) $v) === '') {
            return null;
        }
        try {
            if (is_numeric($v) && (float) $v > 20000 && (float) $v < 80000) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->toDateString();
            }
            $s = trim((string) $v);
            if (preg_match('/^\d{1,2}[\/-]\d{1,2}[\/-]\d{4}$/', $s)) {
                [$d, $m, $y] = preg_split('/[\/-]/', $s);
                return sprintf('%04d-%02d-%02d', $y, $m, $d);
            }
            $c = Carbon::parse($s);
            if (str_contains($s, 'T') && (str_ends_with($s, 'Z') || str_contains($s, '+'))) {
                $c = $c->setTimezone(config('app.timezone'));
            }
            return $c->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function status(mixed $verifikasi, mixed $generate = null): string
    {
        $s = strtolower(trim((string) ($verifikasi ?? '')));
        $g = strtolower(trim((string) ($generate ?? '')));
        foreach ([$s, $g] as $x) {
            if (in_array($x, ['disetujui', 'approved'], true)) {
                return 'disetujui';
            }
            if ($x === 'ditolak') {
                return 'ditolak';
            }
            if (in_array($x, ['tidak_perlu', 'tidak perlu'], true)) {
                return 'tidak_perlu';
            }
        }
        return $s === '' && $g === 'sudah' ? 'disetujui' : 'menunggu_verifikasi';
    }

    private function catat(string $kunci, bool $ok): void
    {
        $this->lapor[$kunci] ??= [0, 0];
        $this->lapor[$kunci][$ok ? 0 : 1]++;
    }

    private function kosongkan(): void
    {
        $this->warn('--fresh: mengosongkan data lama…');
        DokumenDetail::query()->delete();
        Penugasan::query()->delete();
        Dokumen::withTrashed()->forceDelete();
        BastSmPpk::withTrashed()->forceDelete();
        Kegiatan::query()->delete();
        Mitra::query()->delete();
        DB::table('users')->update(['pegawai_id' => null]);
        Pegawai::query()->delete();
    }

    // ───────────────────────── resolver ─────────────────────────

    private function muatCache(): void
    {
        $this->pegawaiCache = Pegawai::all()->all();
        $this->mitraCache = Mitra::all()->all();
    }

    private function nama(?string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $s));
    }

    private function cariPegawai(?string $ref): ?Pegawai
    {
        $ref = $this->str($ref);
        if (! $ref) {
            return null;
        }
        $n = $this->nama($ref);
        foreach ($this->pegawaiCache as $p) {
            if ($p->nip && $p->nip === $ref) {
                return $p;
            }
        }
        foreach ($this->pegawaiCache as $p) {
            if ($this->nama($p->nama) === $n) {
                return $p;
            }
        }
        if (strlen($n) >= 5) {
            foreach ($this->pegawaiCache as $p) {
                $pn = $this->nama($p->nama);
                if (str_contains($pn, $n) || str_contains($n, $pn)) {
                    return $p;
                }
            }
        }
        return null;
    }

    private function cariMitra(?string $ref): ?Mitra
    {
        $ref = $this->str($ref);
        if (! $ref) {
            return null;
        }
        $n = $this->nama($ref);
        foreach ($this->mitraCache as $m) {
            if ($m->id_mitra === $ref || ($m->nik && $m->nik === $ref) || ($m->sobat_id && $m->sobat_id === $ref)) {
                return $m;
            }
        }
        foreach ($this->mitraCache as $m) {
            if ($this->nama($m->nama) === $n) {
                return $m;
            }
        }
        return null;
    }

    /** @return array{0:string,1:?int,2:?int} [tipe, mitra_id, pegawai_id] */
    private function cariPetugas(?string $ref, ?string $tipeHint): ?array
    {
        $preferPegawai = strtolower((string) $tipeHint) === 'pegawai';
        $m = $preferPegawai ? null : $this->cariMitra($ref);
        if ($m) {
            return ['mitra', $m->id, null];
        }
        $p = $this->cariPegawai($ref);
        if ($p) {
            return ['pegawai', null, $p->id];
        }
        $m = $this->cariMitra($ref);
        return $m ? ['mitra', $m->id, null] : null;
    }

    /** PML: pegawai/mitra, atau teks bebas */
    private function pml(?string $ref): array
    {
        $ref = $this->str($ref);
        if (! $ref) {
            return ['pml_mitra_id' => null, 'pml_pegawai_id' => null, 'pml_nama' => null];
        }
        if ($p = $this->cariPegawai($ref)) {
            return ['pml_mitra_id' => null, 'pml_pegawai_id' => $p->id, 'pml_nama' => null];
        }
        if ($m = $this->cariMitra($ref)) {
            return ['pml_mitra_id' => $m->id, 'pml_pegawai_id' => null, 'pml_nama' => null];
        }
        return ['pml_mitra_id' => null, 'pml_pegawai_id' => null, 'pml_nama' => $ref];
    }

    // ───────────────────────── impor per sheet ─────────────────────────

    private function impParameter(): void
    {
        $dipakai = ['NAMA_KANTOR', 'ALAMAT_KANTOR', 'TAHUN_AKTIF', 'BATAS_HONOR_MAKSIMUM'];
        foreach ($this->sheet('Parameter') as $r) {
            $kode = $this->str($this->v($r, 'Kode_Parameter'));
            if (! in_array($kode, $dipakai, true)) {
                continue;
            }
            Parameter::updateOrCreate(['kode' => $kode], ['nilai' => $this->str($this->v($r, 'Nilai')), 'keterangan' => $this->str($this->v($r, 'Keterangan'))]);
            $this->catat('Parameter', true);
        }
    }

    private function impPegawai(): void
    {
        foreach ($this->sheet('Pegawai') as $r) {
            $nama = $this->str($this->v($r, 'Nama_Pegawai', 'Nama'));
            if (! $nama) {
                continue;
            }
            $atribut = [
                'nip' => $this->str($this->v($r, 'NIP')),
                'jabatan' => $this->str($this->v($r, 'Jabatan')),
                'pangkat' => $this->str($this->v($r, 'Pangkat')),
                'golongan' => $this->str($this->v($r, 'Golongan')),
            ];
            // jabatan "Pejabat Pembuat Komitmen" otomatis ditandai PPK (penanda tidak pernah dimatikan oleh impor)
            if (stripos((string) $atribut['jabatan'], 'pejabat pembuat komitmen') !== false) {
                $atribut['is_ppk'] = true;
            }
            Pegawai::updateOrCreate(['nama' => $nama], $atribut);
            $this->catat('Pegawai', true);
        }
    }

    private function impMitra(): void
    {
        foreach ($this->sheet('Mitra') as $r) {
            $id = $this->str($this->v($r, 'ID_Mitra'));
            $nama = $this->str($this->v($r, 'Nama_Mitra', 'Nama'));
            if (! $id || ! $nama) {
                $this->catat('Mitra', false);
                continue;
            }
            Mitra::updateOrCreate(['id_mitra' => $id], [
                'kategori' => $this->str($this->v($r, 'Kategori')),
                'nama' => $nama,
                'nik' => $this->str($this->v($r, 'NIK')),
                'sobat_id' => $this->str($this->v($r, 'Sobat_ID')),
                'email' => $this->str($this->v($r, 'Email')),
                'posisi' => $this->str($this->v($r, 'Posisi')),
                'asal' => $this->str($this->v($r, 'Asal')),
                'alamat_detail' => $this->str($this->v($r, 'Alamat_Detail')),
                'tanggal_lahir' => $this->tgl($this->v($r, 'Tanggal_Lahir')),
                'jenis_kelamin' => $this->str($this->v($r, 'Jenis_Kelamin')),
                'no_telp' => $this->str($this->v($r, 'No_Telp')),
                'aktif' => true,
            ]);
            $this->catat('Mitra', true);
        }
    }

    private function impKegiatan(): void
    {
        foreach ($this->sheet('Kegiatan') as $r) {
            $kode = $this->str($this->v($r, 'ID_Kegiatan'));
            $uraian = $this->str($this->v($r, 'Uraian_Tugas'));
            if (! $kode || ! $uraian) {
                $this->catat('Kegiatan', false);
                continue;
            }
            $tim = $this->str($this->v($r, 'Nama_Tim_Teknis', 'Ketua_Tim'));
            $harga = $this->v($r, 'Harga_Satuan');
            $atribut = [
                'uraian_tugas' => $uraian,
                'satuan' => $this->str($this->v($r, 'Satuan')) ?: 'Dokumen',
                'harga_satuan' => $harga === null ? null : $this->int($harga),
                'beban_anggaran' => $this->str($this->v($r, 'Beban_Anggaran')),
                'buku_pedoman' => $this->str($this->v($r, 'Buku_Pedoman')),
            ];
            // Ketua tim hanya diisi bila sheet menyebutnya; sheet kosong tidak menimpa penetapan di aplikasi.
            if ($ketuaId = ($tim ? $this->cariPegawai($tim)?->id : null)) {
                $atribut['ketua_tim_id'] = $ketuaId;
            }
            Kegiatan::updateOrCreate(['kode' => $kode], $atribut);
            $this->catat('Kegiatan', true);
        }
    }

    private function impPenugasan(): void
    {
        foreach ($this->sheet('Mapping_Petugas') as $i => $r) {
            $kodeKeg = $this->str($this->v($r, 'ID_Kegiatan'));
            $keg = $kodeKeg ? Kegiatan::where('kode', $kodeKeg)->first() : null;
            $petugas = $this->cariPetugas($this->str($this->v($r, 'ID_Mitra_atau_NIP')) ?? $this->str($this->v($r, 'Nama_Petugas')), $this->str($this->v($r, 'Tipe_Petugas')));
            if (! $keg || ! $petugas) {
                $this->catat('Penugasan', false);
                $this->peringatan[] = 'Mapping_Petugas baris '.($i + 2).' dilewati (kegiatan/petugas tidak dikenali).';
                continue;
            }

            $mulai = $this->tgl($this->v($r, 'Tanggal_Mulai'));
            $tahun = (int) ($this->v($r, 'Tahun') ?? ($mulai ? substr($mulai, 0, 4) : now()->year));
            $volume = max(1, $this->int($this->v($r, 'Volume', 'Target_Pekerjaan') ?? 1));
            $harga = $this->int($this->v($r, 'Harga_Satuan') ?? $keg->harga_satuan);
            $total = $this->int($this->v($r, 'Total_Honor') ?? 0) ?: $volume * $harga;

            $kunciPenugasan = [
                'kegiatan_id' => $keg->id,
                'petugas_tipe' => $petugas[0],
                'mitra_id' => $petugas[1],
                'pegawai_id' => $petugas[2],
                'peran' => $this->peran($this->v($r, 'Peran')),
                'tahun' => $tahun,
                'bulan' => $this->str($this->v($r, 'Bulan')),
                'triwulan' => $this->str($this->v($r, 'Triwulan')),
                'tahap' => $this->str($this->v($r, 'Tahap')),
                'subround' => $this->str($this->v($r, 'Subround')),
                'semester' => $this->str($this->v($r, 'Semester')),
            ];
            if (Penugasan::where($kunciPenugasan)->exists()) {
                $this->catat('Penugasan', false);
                continue;
            }

            Penugasan::create(array_merge([
                'kegiatan_id' => $keg->id,
                'petugas_tipe' => $petugas[0],
                'mitra_id' => $petugas[1],
                'pegawai_id' => $petugas[2],
                'peran' => $this->peran($this->v($r, 'Peran')),
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $this->tgl($this->v($r, 'Tanggal_Selesai')),
                'batas_terlambat' => $this->tgl($this->v($r, 'Batas_Terlambat', 'Batas_Terlambar')),
                'nomor_kepka' => $this->str($this->v($r, 'Nomor_Kepka')),
                'tanggal_kepka' => $this->tgl($this->v($r, 'Tanggal_Kepka')),
                'tipe_periode' => $this->str($this->v($r, 'Tipe_Periode')) ?: 'bulan',
                'bulan' => $this->str($this->v($r, 'Bulan')),
                'triwulan' => $this->str($this->v($r, 'Triwulan')),
                'tahap' => $this->str($this->v($r, 'Tahap')),
                'subround' => $this->str($this->v($r, 'Subround')),
                'semester' => $this->str($this->v($r, 'Semester')),
                'tahun' => $tahun,
                'volume' => $volume,
                'satuan' => $this->str($this->v($r, 'Satuan')) ?: $keg->satuan,
                'harga_satuan' => $harga,
                'total_honor' => $total,
            ], $this->pml($this->str($this->v($r, 'PML', 'Nama_PML')))));
            $this->catat('Penugasan', true);
        }
    }

    private function peran(mixed $v, array $row = []): string
    {
        $p = strtolower(trim((string) $v));
        if (in_array($p, ['ppl', 'pml', 'entri'], true)) {
            return $p;
        }
        if (str_contains($p, 'pml')) {
            return 'pml';
        }
        if (str_contains($p, 'entri')) {
            return 'entri';
        }
        if ($row) {
            if ($this->v($row, 'No_BAST_ENTRI_SM')) {
                return 'entri';
            }
            if ($this->v($row, 'No_BAST_PML_SM') && ! $this->v($row, 'No_BAST_PPL_PML') && ! $this->v($row, 'No_BAST_PPL_SM')) {
                return 'pml';
            }
        }
        return 'ppl';
    }

    private function impDokumen(): void
    {
        $detailPerDokumen = collect($this->sheet('Detail_Pekerjaan'))->groupBy(fn ($r) => (string) $this->str($this->v($r, 'ID_Dokumen')));

        foreach ($this->sheet('SPK_BAST') as $i => $r) {
            $idLama = $this->str($this->v($r, 'ID_Dokumen'));
            $verif = $this->v($r, 'Status_Verifikasi_PPK');
            if (strtoupper((string) $verif) === 'DELETED') {
                continue;
            }

            $petugas = $this->cariPetugas($this->str($this->v($r, 'ID_Mitra')), $this->str($this->v($r, 'Jenis_Petugas')));
            $noSpk = $this->str($this->v($r, 'Nomor_SPK', 'No_SPK', 'No SPK'));
            if (! $petugas) {
                $this->catat('Dokumen (SPK/BAST)', false);
                if ($idLama) {
                    $this->peringatan[] = "SPK_BAST $idLama dilewati: petugas tidak dikenali (ID_Mitra=".($this->str($this->v($r, 'ID_Mitra')) ?? 'kosong').').';
                }
                continue;
            }

            $mulai = $this->tgl($this->v($r, 'Tanggal_Mulai'));
            $selesai = $this->tgl($this->v($r, 'Tanggal_Selesai'));
            $tglSpk = $this->tgl($this->v($r, 'Tanggal SPK', 'Tanggal_SPK'));
            $tahun = (int) ($this->v($r, 'Tahun') ?? substr($tglSpk ?: ($mulai ?: now()->toDateString()), 0, 4));
            if ($tahun < 2000 || $tahun > 2100) {
                $tahun = (int) (substr($tglSpk ?: $mulai ?: now()->toDateString(), 0, 4));
            }

            $kunciDok = $noSpk
                ? ['no_spk' => $noSpk, 'peran' => $this->peran($this->v($r, 'Peran'), $r), 'petugas_tipe' => $petugas[0], 'mitra_id' => $petugas[1], 'pegawai_id' => $petugas[2]]
                : ['peran' => $this->peran($this->v($r, 'Peran'), $r), 'petugas_tipe' => $petugas[0], 'mitra_id' => $petugas[1], 'pegawai_id' => $petugas[2],
                    'tahun' => $tahun, 'bulan' => $this->str($this->v($r, 'Bulan')), 'tanggal_mulai' => $mulai, 'judul_pekerjaan' => $this->str($this->v($r, 'Judul_Pekerjaan_Dokumen'))];
            // withTrashed: dokumen yang sengaja dihapus di aplikasi tidak dihidupkan lagi oleh impor ulang
            if (Dokumen::withTrashed()->where($kunciDok)->exists()) {
                $this->catat('Dokumen (SPK/BAST)', false);
                continue;
            }

            $d = Dokumen::create(array_merge([
                'jenis' => $this->str($this->v($r, 'Jenis_Dokumen')) ?: 'SPK_BAST',
                'peran' => $this->peran($this->v($r, 'Peran'), $r),
                'petugas_tipe' => $petugas[0],
                'mitra_id' => $petugas[1],
                'pegawai_id' => $petugas[2],
                'ppk_id' => $this->cariPegawai($this->str($this->v($r, 'PPK')))?->id,
                'ketua_tim_id' => $this->cariPegawai($this->str($this->v($r, 'Ketua_Tim')))?->id,
                'tipe_periode' => $this->str($this->v($r, 'Tipe_Periode')),
                'bulan' => $this->str($this->v($r, 'Bulan')),
                'triwulan' => $this->str($this->v($r, 'Triwulan')),
                'tahap' => $this->str($this->v($r, 'Tahap')),
                'subround' => $this->str($this->v($r, 'Subround')),
                'semester' => $this->str($this->v($r, 'Semester')),
                'tahun' => $tahun,
                'nomor_kepka' => $this->str($this->v($r, 'Nomor_Kepka')),
                'tanggal_kepka' => $this->tgl($this->v($r, 'Tanggal_Kepka')),
                'no_spk' => $noSpk,
                'tanggal_spk' => $tglSpk,
                'no_bast_ppl_pml' => $this->str($this->v($r, 'No_BAST_PPL_PML')),
                'tanggal_bast_ppl_pml' => $this->tgl($this->v($r, 'Tanggal_BAST_PPL_PML')),
                'no_bast_ppl_sm' => $this->str($this->v($r, 'No_BAST_PPL_SM')),
                'tanggal_bast_ppl_sm' => $this->tgl($this->v($r, 'Tanggal_BAST_PPL_SM')),
                'no_bast_pml_sm' => $this->str($this->v($r, 'No_BAST_PML_SM')),
                'tanggal_bast_pml_sm' => $this->tgl($this->v($r, 'Tanggal_BAST_PML_SM')),
                'no_bast_entri_sm' => $this->str($this->v($r, 'No_BAST_ENTRI_SM')),
                'tanggal_bast_entri_sm' => $this->tgl($this->v($r, 'Tanggal_BAST_ENTRI_SM')),
                'judul_pekerjaan' => $this->str($this->v($r, 'Judul_Pekerjaan_Dokumen')),
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'batas_penyerahan' => $this->tgl($this->v($r, 'Batas_Penyerahan', 'Batas_Penyerahan_Telat')),
                'total_honor' => $this->int($this->v($r, 'Total_Honor')),
                'status_verifikasi' => $this->status($verif, $this->v($r, 'Status_Generate_SPK')),
                'catatan_verifikasi' => $this->str($this->v($r, 'Catatan_Verifikasi_PPK')),
                'tanggal_verifikasi' => $this->tgl($this->v($r, 'Tanggal_Verifikasi_PPK')),
            ], $this->pml($this->str($this->v($r, 'PML')))));

            $rows = $idLama ? ($detailPerDokumen[$idLama] ?? collect()) : collect();
            foreach ($rows->values() as $n => $dr) {
                [$dm, $ds] = $this->jangkaWaktu($this->str($this->v($dr, 'Jangka_Waktu')), $mulai, $selesai);
                $keg = ($k = $this->str($this->v($dr, 'ID_Kegiatan'))) ? Kegiatan::where('kode', $k)->first() : null;
                $volume = max(1, $this->int($this->v($dr, 'Volume') ?? 1));
                $harga = $this->int($this->v($dr, 'Harga_Satuan'));
                $d->details()->create([
                    'no_urut' => $this->int($this->v($dr, 'No_Urut') ?? $n + 1) ?: $n + 1,
                    'kegiatan_id' => $keg?->id,
                    'uraian_tugas' => $this->str($this->v($dr, 'Uraian_Tugas')) ?: ($d->judul_pekerjaan ?: '-'),
                    'buku_pedoman' => $this->str($this->v($dr, 'Buku_Pedoman')),
                    'tanggal_mulai' => $dm,
                    'tanggal_selesai' => $ds,
                    'volume' => $volume,
                    'satuan' => $this->str($this->v($dr, 'Satuan')) ?: 'Dokumen',
                    'harga_satuan' => $harga,
                    'nilai_perjanjian' => $this->int($this->v($dr, 'Nilai_Perjanjian')) ?: $volume * $harga,
                    'beban_anggaran' => $this->str($this->v($dr, 'Beban_Anggaran')),
                ]);
            }
            if ($rows->isEmpty() && ($d->judul_pekerjaan || $d->total_honor)) {
                $d->details()->create([
                    'no_urut' => 1,
                    'uraian_tugas' => $d->judul_pekerjaan ?: 'Pendataan Lapangan',
                    'tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai,
                    'volume' => 1, 'satuan' => 'Dokumen',
                    'harga_satuan' => $d->total_honor, 'nilai_perjanjian' => $d->total_honor,
                ]);
            }
            $this->catat('Dokumen (SPK/BAST)', true);
        }
    }

    /** "2026-04-24 s/d 2026-04-30" → [tgl, tgl]; jika tak terbaca pakai periode dokumen. */
    private function jangkaWaktu(?string $teks, ?string $mulai, ?string $selesai): array
    {
        if ($teks && preg_match('/(\d{4}-\d{2}-\d{2}).*?(\d{4}-\d{2}-\d{2})/', $teks, $m)) {
            return [$m[1], $m[2]];
        }
        return [$mulai, $selesai];
    }

    private function impSmPpk(): void
    {
        foreach ($this->sheet('BAST_SM_PPK') as $r) {
            $verif = $this->v($r, 'Status_Verifikasi_PPK');
            if (strtoupper((string) $verif) === 'DELETED') {
                continue;
            }
            $uraian = $this->str($this->v($r, 'Judul_Pekerjaan_Dokumen', 'Uraian_Tugas'));
            if (! $uraian) {
                $this->catat('BAST SM→PPK', false);
                continue;
            }
            $tahunSm = (int) ($this->v($r, 'Tahun') ?? now()->year);
            $noSm = $this->str($this->v($r, 'No_BAST_SM_PPK'));
            $kunciSm = $noSm
                ? ['no_bast' => $noSm]
                : ['tahun' => $tahunSm, 'bulan' => $this->str($this->v($r, 'Bulan')), 'uraian_tugas' => $uraian,
                    'ketua_tim_id' => $this->cariPegawai($this->str($this->v($r, 'Ketua_Tim')))?->id];
            if (BastSmPpk::withTrashed()->where($kunciSm)->exists()) {
                $this->catat('BAST SM→PPK', false);
                continue;
            }
            BastSmPpk::create([
                'tahun' => $tahunSm,
                'bulan' => $this->str($this->v($r, 'Bulan')),
                'uraian_tugas' => $uraian,
                'ppk_id' => $this->cariPegawai($this->str($this->v($r, 'PPK')))?->id,
                'ketua_tim_id' => $this->cariPegawai($this->str($this->v($r, 'Ketua_Tim')))?->id,
                'no_bast' => $noSm,
                'tanggal_bast' => $this->tgl($this->v($r, 'Tanggal_BAST_SM_PPK', 'Tanggal')),
                'total_honor' => $this->int($this->v($r, 'Total_Honor')),
                'status_verifikasi' => $this->status($verif, $this->v($r, 'Status_Generate_BAST_SM_PPK')),
                'catatan_verifikasi' => $this->str($this->v($r, 'Catatan_Verifikasi_PPK')),
                'tanggal_verifikasi' => $this->tgl($this->v($r, 'Tanggal_Verifikasi_PPK')),
            ]);
            $this->catat('BAST SM→PPK', true);
        }
    }
}
