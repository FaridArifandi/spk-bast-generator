<?php

namespace App\Services;

use App\Enums\Peran;
use App\Models\BastSmPpk;
use App\Models\Dokumen;
use App\Models\Parameter;
use App\Support\Indonesia as Id;
use Carbon\CarbonInterface;

/**
 * Menyusun kamus placeholder (<<KEY>>) untuk SPK dan semua jenis BAST.
 * Port dari buildDataContext() dan buildBastSmPpkContext() sistem lama,
 * tetapi bekerja dengan relasi database (bukan pencarian string).
 */
class DokumenContextBuilder
{
    /**
     * @return array{spk:array,ppl-pml:array,ppl-sm:array,pml-sm:array,entri-sm:array,details:array,total_honor:int}
     */
    public function build(Dokumen $d): array
    {
        $d->loadMissing(['mitra', 'pegawai', 'pmlMitra', 'pmlPegawai', 'ppk', 'ketuaTim', 'details']);

        $petugas = $this->petugas($d);
        $pml = $this->pml($d, $petugas);
        $ppk = $d->ppk;
        $ketua = $d->ketuaTim;

        $details = $this->detailRows($d);
        $total = (int) ($d->details->sum('nilai_perjanjian') ?: $d->total_honor);
        $terbilangHonor = Id::terbilangRupiah($total);

        // Judul & uraian
        $judul = JudulPeriode::bangun([
            'judul' => $d->judul_pekerjaan ?: ($d->details->first()->uraian_tugas ?? ''),
            'tipe_periode' => $d->tipe_periode,
            'bulan' => $d->bulan,
            'triwulan' => $d->triwulan,
            'tahap' => $d->tahap,
            'subround' => $d->subround,
            'semester' => $d->semester,
            'tahun' => $d->tahun,
        ]);

        $uniqueTasks = $d->details->pluck('uraian_tugas')->map(fn ($t) => trim((string) $t))->filter()->unique()->values();
        if ($uniqueTasks->count() > 1) {
            $uraian = $uniqueTasks->map(function ($t, $i) {
                if (stripos($t, 'hasil ') === 0) {
                    $t = trim(substr($t, 6));
                }
                return ($i + 1).'. '.$t;
            })->implode('; ');
        } else {
            $uraian = stripos($judul, 'hasil ') === 0 ? trim(substr($judul, 6)) : $judul;
        }
        $uraian = mb_strtolower($uraian);

        $jabatan = $d->peran->jabatan();

        // Nomor & tanggal
        $noSpkRaw = trim((string) $d->no_spk);
        $noSpk = Id::bersihNomor($noSpkRaw);
        $noSpkHeader = $noSpkRaw === '' ? '' : (preg_match('/^nomor\s+/i', $noSpkRaw) ? $noSpkRaw : 'Nomor '.$noSpkRaw);

        $tSpk = Id::tanggalTerbilang($d->tanggal_spk ?? $d->tanggal_mulai);
        $batas = $d->batas_penyerahan ?? $d->tanggal_selesai?->copy()->addDays(5);

        $kepka = Id::bersihNomor($d->nomor_kepka);
        $tglKepka = Id::tanggal($d->tanggal_kepka);

        $komponenKetua = [
            'NAMA_KETUA_TIM' => $ketua?->nama ?? '',
            'NIP_KETUA_TIM' => $ketua?->nip ?? '',
            'GOLONGAN/PANGKAT_KETUA_TIM' => $ketua?->pangkat_golongan ?? '',
            'JABATAN_KETUA_TIM' => $ketua?->jabatan ?? '',
        ];

        $spk = [
            'JUDUL_PEKERJAAN_DOKUMEN' => $judul,
            'NO_SPK' => $noSpk,
            'NO_SPK_HEADER' => $noSpkHeader,
            'URAIAN_PEKERJAAN' => $uraian,
            'HARI_TERBILANG' => $tSpk['hari'],
            'TANGGAL_TERBILANG' => $tSpk['tanggal'],
            'BULAN_TERBILANG' => $tSpk['bulan'],
            'TAHUN_TERBILANG' => $tSpk['tahun'],
            'TANGGAL_BAST' => $tSpk['format'],
            'TANGGAL' => $tSpk['angka'],
            'BULAN' => $tSpk['bulan'],
            'TAHUN' => $tSpk['tahunAngka'],
            'TANGGAL_ANGKA' => $tSpk['angka'],
            'BULAN_NAMA' => $tSpk['bulan'],
            'TAHUN_ANGKA' => $tSpk['tahunAngka'],
            'NAMA_PPK' => $ppk?->nama ?? '',
            'NIP_PPK' => $ppk?->nip ?? '',
            'GOLONGAN/PANGKAT_PPK' => $ppk?->pangkat_golongan ?? '',
            'NAMA_PETUGAS' => $petugas['nama'],
            'NAMA_PIHAK_KEDUA' => $petugas['nama'],
            'NIK_PIHAK_PERTAMA' => $petugas['nik'],
            'JABATAN_PETUGAS' => $jabatan,
            'JABATAN_PIHAK_KEDUA' => $jabatan,
            'JABATAN_PIHAK_PERTAMA' => $jabatan,
            'NAMA_PIHAK_PERTAMA' => $ppk?->nama ?? '',
            'ASAL' => $petugas['asal'],
            'TOTAL_HONOR' => Id::rupiah($total),
            'TERBILANG_TOTAL_HONOR' => $terbilangHonor,
            'TERBILANG' => $terbilangHonor,
            'TANGGAL_SELESAI' => Id::tanggal($d->tanggal_selesai),
            'BATAS_PENYERAHAN' => Id::tanggal($batas),
            'BUKU_PEDOMAN' => $d->details->first()->buku_pedoman ?? '',
            'BEBAN_ANGGARAN' => $d->details->first()->beban_anggaran ?? '',
            'NOMOR_KEPKA' => $kepka,
            'TANGGAL_KEPKA' => $tglKepka,
            'NAMA_KANTOR' => (string) Parameter::nilai('NAMA_KANTOR', 'BPS Kota Subulussalam'),
            'ALAMAT_KANTOR' => (string) Parameter::nilai('ALAMAT_KANTOR', 'Jl. Raja Tua Lae Oram Komplek Perkantoran Walikota Subulussalam'),
            'TABEL_LAMPIRAN_ROWS' => '', // diisi renderer HTML
            'IS_PML_MITRA' => $pml['is_mitra'],
        ];

        // ── BAST PPL → PML
        $t1 = Id::tanggalTerbilang($d->tanggal_bast_ppl_pml);
        $pplPml = array_merge($spk, $this->tanggalBast($t1), [
            'NO_BAST_PPL_PML' => (string) $d->no_bast_ppl_pml,
            'NO_BAST_PPL-PML' => (string) $d->no_bast_ppl_pml,
            'NAMA_PIHAK_PERTAMA' => $petugas['nama'],
            'NIK_PIHAK_PERTAMA' => $petugas['nik'],
            'JABATAN_PIHAK_PERTAMA' => Peran::Ppl->jabatan(),
            'NAMA_PIHAK_KEDUA' => $pml['nama'],
            'NIP_PIHAK_KEDUA' => $pml['is_mitra'] ? '' : $pml['nip'],
            'NIK_PIHAK_KEDUA' => $pml['nik'] ?: $pml['nip'],
            'NIK_NIP_PIHAK_KEDUA' => $pml['is_mitra'] ? '' : $pml['nip'],
            'JABATAN_PIHAK_KEDUA' => Peran::Pml->jabatan(),
            // narasi "Perjanjian Kerja ... tanggal/bulan/tahun" memakai tanggal SPK
            'TANGGAL' => $tSpk['angka'],
            'BULAN' => $tSpk['bulan'],
            'TAHUN' => $tSpk['tahunAngka'],
        ]);

        // ── BAST PPL → SM (Ketua Tim)
        $t2 = Id::tanggalTerbilang($d->tanggal_bast_ppl_sm);
        $pplSm = array_merge($spk, $this->tanggalBast($t2), $komponenKetua, [
            'NO_BAST_PPL_SM' => (string) $d->no_bast_ppl_sm,
            'NAMA_PIHAK_PERTAMA' => $petugas['nama'],
            'NIK_PIHAK_PERTAMA' => $petugas['nik'],
            'JABATAN_PIHAK_PERTAMA' => Peran::Ppl->jabatan(),
            'TANGGAL' => $tSpk['angka'],
            'BULAN' => $tSpk['bulan'],
            'TAHUN' => $tSpk['tahunAngka'],
        ]);

        // ── BAST PML → SM. Nomor/tanggal SPK yang dirujuk adalah SPK milik PML itu sendiri.
        [$pmlNoSpk, $pmlTSpk] = $this->spkMilikPml($d, $noSpk, $tSpk);
        $t3 = Id::tanggalTerbilang($d->tanggal_bast_pml_sm);
        $pmlSm = array_merge($pplSm, $this->tanggalBast($t3), [
            'NO_BAST_PML_SM' => (string) $d->no_bast_pml_sm,
            'NO_BAST_PML-SM' => (string) $d->no_bast_pml_sm,
            'NO_SPK' => $pmlNoSpk,
            'TANGGAL' => $pmlTSpk['angka'],
            'BULAN' => $pmlTSpk['bulan'],
            'TAHUN' => $pmlTSpk['tahunAngka'],
            'NAMA_PIHAK_PERTAMA' => $pml['nama'],
            'NIK_PIHAK_PERTAMA' => $pml['nik'] ?: $pml['nip'],
            'NIP_PIHAK_PERTAMA' => $pml['is_mitra'] ? '' : $pml['nip'],
            'JABATAN_PIHAK_PERTAMA' => Peran::Pml->jabatan(),
            'IS_PML_MITRA' => $pml['is_mitra'],
        ]);

        // ── BAST Petugas Entri → SM. (Template entri memakai placeholder NO_BAST_PML_SM.)
        $t4 = Id::tanggalTerbilang($d->tanggal_bast_entri_sm ?? $d->tanggal_selesai);
        $entriSm = array_merge($pplSm, $this->tanggalBast($t4), [
            'NO_BAST_ENTRI_SM' => (string) $d->no_bast_entri_sm,
            'NO_BAST_ENTRI-SM' => (string) $d->no_bast_entri_sm,
            'NO_BAST_PML_SM' => (string) $d->no_bast_entri_sm,
            'NAMA_PIHAK_PERTAMA' => $petugas['nama'],
            'NIK_PIHAK_PERTAMA' => $petugas['nik'],
            'JABATAN_PIHAK_PERTAMA' => Peran::Entri->jabatan(),
        ]);

        return [
            'spk' => $spk,
            'ppl-pml' => $pplPml,
            'ppl-sm' => $pplSm,
            'pml-sm' => $pmlSm,
            'entri-sm' => $entriSm,
            'details' => $details,
            'total_honor' => $total,
            'pml_mitra' => $pml['is_mitra'],
        ];
    }

    /** Konteks BAST Ketua Tim (SM) → PPK. */
    public function buildSmPpk(BastSmPpk $b): array
    {
        $b->loadMissing(['ppk', 'ketuaTim']);
        $t = Id::tanggalTerbilang($b->tanggal_bast);

        $judul = JudulPeriode::bangun([
            'judul' => $b->uraian_tugas,
            'tipe_periode' => 'bulan',
            'bulan' => $b->bulan,
            'tahun' => $b->tahun,
        ]);
        $uraian = mb_strtolower(stripos($judul, 'hasil ') === 0 ? trim(substr($judul, 6)) : $judul);

        $no = trim((string) $b->no_bast);
        $no = preg_match('/^nomor\s+/i', $no) ? preg_replace('/^nomor\s+/i', 'Nomor ', $no) : ($no === '' ? '' : 'Nomor '.$no);

        $ppk = $b->ppk;
        $ketua = $b->ketuaTim;

        return [
            'JUDUL_PEKERJAAN_DOKUMEN' => $judul,
            'NO_SPK' => '',
            'NO_BAST_SM_PPK' => $no,
            'NO_BAST_SM-PPK' => $no,
            'HARI_TERBILANG' => $t['hari'],
            'TANGGAL_TERBILANG' => $t['tanggal'],
            'BULAN_TERBILANG' => $t['bulan'],
            'TAHUN_TERBILANG' => $t['tahun'],
            'TANGGAL_BAST' => $t['format'],
            'TANGGAL_ANGKA' => $t['angka'],
            'BULAN_NAMA' => $t['bulan'],
            'TAHUN_ANGKA' => $t['tahunAngka'],
            'TANGGAL' => $t['angka'],
            'BULAN' => $t['bulan'],
            'TAHUN' => $t['tahunAngka'],
            'URAIAN_PEKERJAAN' => $uraian,
            'NAMA_KETUA_TIM' => $ketua?->nama ?? '',
            'NIP_KETUA_TIM' => $ketua?->nip ?? '',
            'GOLONGAN/PANGKAT_KETUA_TIM' => $ketua?->pangkat_golongan ?? '',
            'JABATAN_KETUA_TIM' => $ketua?->jabatan ?? '',
            'NAMA_PPK' => $ppk?->nama ?? '',
            'NIP_PPK' => $ppk?->nip ?? '',
            'GOLONGAN/PANGKAT_PPK' => $ppk?->pangkat_golongan ?? '',
            'NAMA_KANTOR' => (string) Parameter::nilai('NAMA_KANTOR', 'BPS Kota Subulussalam'),
            'ALAMAT_KANTOR' => (string) Parameter::nilai('ALAMAT_KANTOR', 'Jl. Raja Tua Lae Oram Komplek Perkantoran Walikota Subulussalam'),
            'TOTAL_HONOR' => Id::rupiah($b->total_honor),
            'TERBILANG' => Id::terbilangRupiah($b->total_honor),
        ];
    }

    // ───────────────────────────────────────────────────────────

    private function tanggalBast(array $t): array
    {
        return [
            'HARI_TERBILANG' => $t['hari'],
            'TANGGAL_TERBILANG' => $t['tanggal'],
            'BULAN_TERBILANG' => $t['bulan'],
            'TAHUN_TERBILANG' => $t['tahun'],
            'TANGGAL_BAST' => $t['format'],
            'TANGGAL_BAST_SM_PPK' => $t['format'],
            'TANGGAL_ANGKA' => $t['angka'],
            'BULAN_NAMA' => $t['bulan'],
            'TAHUN_ANGKA' => $t['tahunAngka'],
        ];
    }

    /** @return array{nama:string,nik:string,nip:string,asal:string,organik:bool} */
    private function petugas(Dokumen $d): array
    {
        if ($d->petugas_tipe === 'pegawai' && $d->pegawai) {
            return [
                'nama' => $d->pegawai->nama,
                'nik' => (string) $d->pegawai->nip,
                'nip' => (string) $d->pegawai->nip,
                'asal' => (string) Parameter::nilai('NAMA_KANTOR', 'BPS'),
                'organik' => true,
            ];
        }
        return [
            'nama' => (string) $d->mitra?->nama,
            'nik' => (string) $d->mitra?->nik,
            'nip' => '',
            'asal' => (string) $d->mitra?->asal,
            'organik' => false,
        ];
    }

    /** @return array{nama:string,nik:string,nip:string,is_mitra:bool} */
    private function pml(Dokumen $d, array $petugas): array
    {
        // Dokumen milik PML sendiri → PML = petugasnya
        if ($d->peran === Peran::Pml) {
            return [
                'nama' => $petugas['nama'],
                'nik' => $petugas['organik'] ? '' : $petugas['nik'],
                'nip' => $petugas['organik'] ? $petugas['nip'] : '',
                'is_mitra' => ! $petugas['organik'],
            ];
        }
        if ($d->pmlPegawai) {
            return ['nama' => $d->pmlPegawai->nama, 'nik' => '', 'nip' => (string) $d->pmlPegawai->nip, 'is_mitra' => false];
        }
        if ($d->pmlMitra) {
            return ['nama' => $d->pmlMitra->nama, 'nik' => (string) $d->pmlMitra->nik, 'nip' => '', 'is_mitra' => true];
        }
        if (filled($d->pml_nama)) {
            return ['nama' => $d->pml_nama, 'nik' => '-', 'nip' => '', 'is_mitra' => false];
        }
        return ['nama' => '', 'nik' => '', 'nip' => '', 'is_mitra' => true];
    }

    /** @return array{0:string,1:array} nomor SPK dan komponen tanggal SPK milik PML */
    private function spkMilikPml(Dokumen $d, string $noSpkSendiri, array $tSpkSendiri): array
    {
        if ($d->peran === Peran::Pml || (! $d->pml_mitra_id && ! $d->pml_pegawai_id)) {
            return [$noSpkSendiri, $tSpkSendiri];
        }

        $q = Dokumen::query()
            ->where('peran', Peran::Pml->value)
            ->where('tahun', $d->tahun)
            ->where('id', '!=', $d->id)
            ->whereNotNull('no_spk')
            ->when($d->bulan, fn ($w) => $w->where('bulan', $d->bulan));

        $q->where(function ($w) use ($d) {
            if ($d->pml_mitra_id) {
                $w->orWhere('mitra_id', $d->pml_mitra_id);
            }
            if ($d->pml_pegawai_id) {
                $w->orWhere('pegawai_id', $d->pml_pegawai_id);
            }
        });

        $pmlDoc = $q->latest('id')->first();
        if (! $pmlDoc) {
            return [$noSpkSendiri, $tSpkSendiri];
        }

        return [Id::bersihNomor($pmlDoc->no_spk), Id::tanggalTerbilang($pmlDoc->tanggal_spk ?? $pmlDoc->tanggal_mulai)];
    }

    /** Baris tabel lampiran SPK. */
    private function detailRows(Dokumen $d): array
    {
        $rows = [];
        foreach ($d->details as $i => $x) {
            $rows[] = [
                'NO_URUT' => (string) ($x->no_urut ?: $i + 1),
                'URAIAN_TUGAS' => (string) $x->uraian_tugas,
                'JANGKA_WAKTU' => Id::jangkaWaktu($x->tanggal_mulai, $x->tanggal_selesai),
                'VOLUME' => (string) $x->volume,
                'SATUAN' => (string) $x->satuan,
                'HARGA_SATUAN' => Id::rupiah($x->harga_satuan),
                'NILAI_PERJANJIAN' => Id::rupiah($x->nilai_perjanjian),
                'BEBAN_ANGGARAN' => (string) $x->beban_anggaran,
            ];
        }
        return $rows;
    }
}
