<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Ekspor searah database → Google Sheets (via Apps Script doPost).
 * Database tetap sumber utama; isi tab di Sheets ditulis ulang tiap ekspor (aman diulang).
 * Data pribadi mitra (NIK, alamat, tanggal lahir, telepon, email) sengaja tidak diekspor.
 */
class SheetsExportService
{
    public const CACHE_HASH = 'sheets_export.hash';
    public const CACHE_STATUS = 'sheets_export.status';

    public function siap(): bool
    {
        return filled(config('services.sheets.url')) && filled(config('services.sheets.token'));
    }

    /** Sidik jari ringkas: berubah bila ada baris ditambah/diubah/dihapus. */
    public function sidikJari(): string
    {
        $bagian = [];
        foreach (['pegawai', 'mitra', 'kegiatan', 'penugasan', 'dokumen', 'dokumen_detail', 'bast_sm_ppk'] as $t) {
            $q = DB::table($t);
            $bagian[] = $t.':'.$q->count().':'.($q->max('updated_at') ?? '');
        }
        $bagian[] = 'hapus:'.DB::table('dokumen')->whereNotNull('deleted_at')->count().DB::table('bast_sm_ppk')->whereNotNull('deleted_at')->count();

        return md5(implode('|', $bagian));
    }

    /**
     * @return array{status:string,pesan:string,waktu:string}
     */
    public function kirim(bool $paksa = false): array
    {
        if (! $this->siap()) {
            return $this->catat('lewati', 'SHEETS_URL atau SHEETS_TOKEN belum diisi di .env.');
        }

        $hash = $this->sidikJari();
        if (! $paksa && Cache::get(self::CACHE_HASH) === $hash) {
            return $this->catat('lewati', 'Tidak ada perubahan sejak ekspor terakhir.', false);
        }

        try {
            $res = Http::timeout(120)->retry(2, 1000)->acceptJson()->asJson()->post(config('services.sheets.url'), [
                'token' => config('services.sheets.token'),
                'sheets' => $this->susun(),
            ]);
            $json = $res->json();
        } catch (\Throwable $e) {
            return $this->catat('gagal', 'Tidak bisa menghubungi Apps Script: '.$e->getMessage());
        }

        if (! $res->successful() || ! is_array($json) || ($json['status'] ?? null) !== 'ok') {
            $pesan = is_array($json) ? ($json['message'] ?? 'respons tidak dikenali') : 'respons bukan JSON (cek deployment Apps Script: akses "Anyone")';
            return $this->catat('gagal', "Apps Script menolak (HTTP {$res->status()}): {$pesan}");
        }

        Cache::forever(self::CACHE_HASH, $hash);

        return $this->catat('ok', 'Terkirim: '.collect($json['rows'] ?? [])->map(fn ($n, $k) => "$k $n")->implode(', '));
    }

    public function statusTerakhir(): ?array
    {
        return Cache::get(self::CACHE_STATUS);
    }

    private function catat(string $status, string $pesan, bool $simpan = true): array
    {
        $hasil = ['status' => $status, 'pesan' => $pesan, 'waktu' => now()->toDateTimeString()];
        // "lewati karena tak ada perubahan" tidak menimpa catatan ekspor sukses terakhir
        if ($simpan) {
            Cache::forever(self::CACHE_STATUS, $hasil);
        }

        return $hasil;
    }

    /** @return array<string,array{headers:array<int,string>,rows:array<int,array<int,mixed>>}> */
    public function susun(): array
    {
        $pegawai = DB::table('pegawai')->get()->keyBy('id');
        $mitra = DB::table('mitra')->get()->keyBy('id');
        $kegiatan = DB::table('kegiatan')->get()->keyBy('id');

        $namaPegawai = fn ($id) => $id ? ($pegawai[$id]->nama ?? '') : '';
        $petugas = fn ($tipe, $mid, $pid) => $tipe === 'pegawai'
            ? [$pegawai[$pid]->nip ?? '', $pegawai[$pid]->nama ?? '']
            : [$mitra[$mid]->id_mitra ?? '', $mitra[$mid]->nama ?? ''];
        $pml = fn ($o) => $o->pml_pegawai_id ? $namaPegawai($o->pml_pegawai_id)
            : ($o->pml_mitra_id ? ($mitra[$o->pml_mitra_id]->nama ?? '') : ($o->pml_nama ?? ''));
        $tgl = fn ($v) => $v ? substr((string) $v, 0, 10) : '';

        $tab = [];

        $tab['Pegawai'] = [
            'headers' => ['Nama_Pegawai', 'NIP', 'Jabatan', 'Pangkat', 'Golongan', 'PPK'],
            'rows' => $pegawai->map(fn ($p) => [$p->nama, $p->nip, $p->jabatan, $p->pangkat, $p->golongan, $p->is_ppk ? 'ya' : ''])->values()->all(),
        ];

        $tab['Mitra'] = [
            'headers' => ['ID_Mitra', 'Kategori', 'Nama_Mitra', 'Sobat_ID', 'Posisi', 'Asal', 'Aktif'],
            'rows' => $mitra->map(fn ($m) => [$m->id_mitra, $m->kategori, $m->nama, $m->sobat_id, $m->posisi, $m->asal, $m->aktif ? 'ya' : 'tidak'])->values()->all(),
        ];

        $tab['Kegiatan'] = [
            'headers' => ['ID_Kegiatan', 'Uraian_Tugas', 'Satuan', 'Harga_Satuan', 'Beban_Anggaran', 'Buku_Pedoman', 'Ketua_Tim'],
            'rows' => $kegiatan->map(fn ($k) => [$k->kode, $k->uraian_tugas, $k->satuan, $k->harga_satuan, $k->beban_anggaran, $k->buku_pedoman, $namaPegawai($k->ketua_tim_id)])->values()->all(),
        ];

        $tab['Mapping_Petugas'] = [
            'headers' => ['ID_Kegiatan', 'Uraian_Tugas', 'Tipe_Petugas', 'ID_Mitra_atau_NIP', 'Nama_Petugas', 'Peran', 'PML', 'Tipe_Periode', 'Bulan', 'Triwulan', 'Tahap', 'Subround', 'Semester', 'Tahun', 'Tanggal_Mulai', 'Tanggal_Selesai', 'Volume', 'Satuan', 'Harga_Satuan', 'Total_Honor', 'Sudah_Dibuat_Dokumen'],
            'rows' => DB::table('penugasan')->orderBy('id')->get()->map(function ($o) use ($kegiatan, $petugas, $pml, $tgl) {
                [$id, $nama] = $petugas($o->petugas_tipe, $o->mitra_id, $o->pegawai_id);
                $k = $kegiatan[$o->kegiatan_id] ?? null;

                return [$k->kode ?? '', $k->uraian_tugas ?? '', $o->petugas_tipe, $id, $nama, $o->peran, $pml($o), $o->tipe_periode, $o->bulan, $o->triwulan, $o->tahap, $o->subround, $o->semester, $o->tahun, $tgl($o->tanggal_mulai), $tgl($o->tanggal_selesai), $o->volume, $o->satuan, $o->harga_satuan, $o->total_honor, $o->dokumen_id ? 'ya' : ''];
            })->all(),
        ];

        $dokumen = DB::table('dokumen')->whereNull('deleted_at')->orderBy('id')->get();
        $tab['SPK_BAST'] = [
            'headers' => ['ID_Dokumen', 'Jenis_Dokumen', 'Peran', 'Jenis_Petugas', 'ID_Mitra', 'Nama_Petugas', 'PPK', 'Ketua_Tim', 'PML', 'Tipe_Periode', 'Bulan', 'Triwulan', 'Tahap', 'Subround', 'Semester', 'Tahun', 'Nomor_Kepka', 'Tanggal_Kepka', 'Nomor_SPK', 'Tanggal_SPK', 'No_BAST_PPL_PML', 'Tanggal_BAST_PPL_PML', 'No_BAST_PPL_SM', 'Tanggal_BAST_PPL_SM', 'No_BAST_PML_SM', 'Tanggal_BAST_PML_SM', 'No_BAST_ENTRI_SM', 'Tanggal_BAST_ENTRI_SM', 'Judul_Pekerjaan_Dokumen', 'Tanggal_Mulai', 'Tanggal_Selesai', 'Batas_Penyerahan', 'Total_Honor', 'Status_Verifikasi_PPK', 'Catatan_Verifikasi_PPK', 'Tanggal_Verifikasi_PPK'],
            'rows' => $dokumen->map(function ($d) use ($petugas, $pml, $namaPegawai, $tgl) {
                [$id, $nama] = $petugas($d->petugas_tipe, $d->mitra_id, $d->pegawai_id);

                return [$d->id, $d->jenis, $d->peran, $d->petugas_tipe, $id, $nama, $namaPegawai($d->ppk_id), $namaPegawai($d->ketua_tim_id), $pml($d), $d->tipe_periode, $d->bulan, $d->triwulan, $d->tahap, $d->subround, $d->semester, $d->tahun, $d->nomor_kepka, $tgl($d->tanggal_kepka), $d->no_spk, $tgl($d->tanggal_spk), $d->no_bast_ppl_pml, $tgl($d->tanggal_bast_ppl_pml), $d->no_bast_ppl_sm, $tgl($d->tanggal_bast_ppl_sm), $d->no_bast_pml_sm, $tgl($d->tanggal_bast_pml_sm), $d->no_bast_entri_sm, $tgl($d->tanggal_bast_entri_sm), $d->judul_pekerjaan, $tgl($d->tanggal_mulai), $tgl($d->tanggal_selesai), $tgl($d->batas_penyerahan), $d->total_honor, $d->status_verifikasi, $d->catatan_verifikasi, $tgl($d->tanggal_verifikasi)];
            })->all(),
        ];

        $idAktif = $dokumen->pluck('id')->all();
        $tab['Detail_Pekerjaan'] = [
            'headers' => ['ID_Dokumen', 'No_Urut', 'ID_Kegiatan', 'Uraian_Tugas', 'Buku_Pedoman', 'Jangka_Waktu', 'Volume', 'Satuan', 'Harga_Satuan', 'Nilai_Perjanjian', 'Beban_Anggaran'],
            'rows' => DB::table('dokumen_detail')->whereIn('dokumen_id', $idAktif)->orderBy('dokumen_id')->orderBy('no_urut')->get()->map(fn ($x) => [
                $x->dokumen_id, $x->no_urut, $kegiatan[$x->kegiatan_id]->kode ?? '', $x->uraian_tugas, $x->buku_pedoman,
                ($x->tanggal_mulai && $x->tanggal_selesai) ? $tgl($x->tanggal_mulai).' s/d '.$tgl($x->tanggal_selesai) : '',
                $x->volume, $x->satuan, $x->harga_satuan, $x->nilai_perjanjian, $x->beban_anggaran,
            ])->all(),
        ];

        $tab['BAST_SM_PPK'] = [
            'headers' => ['ID', 'Tahun', 'Bulan', 'Judul_Pekerjaan_Dokumen', 'PPK', 'Ketua_Tim', 'No_BAST_SM_PPK', 'Tanggal_BAST_SM_PPK', 'Total_Honor', 'Status_Verifikasi_PPK', 'Catatan_Verifikasi_PPK', 'Tanggal_Verifikasi_PPK'],
            'rows' => DB::table('bast_sm_ppk')->whereNull('deleted_at')->orderBy('id')->get()->map(fn ($b) => [
                $b->id, $b->tahun, $b->bulan, $b->uraian_tugas, $namaPegawai($b->ppk_id), $namaPegawai($b->ketua_tim_id), $b->no_bast, $tgl($b->tanggal_bast), $b->total_honor, $b->status_verifikasi, $b->catatan_verifikasi, $tgl($b->tanggal_verifikasi),
            ])->all(),
        ];

        return $tab;
    }
}
