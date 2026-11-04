<?php

namespace App\Services;

use App\Enums\StatusVerifikasi;
use App\Models\Dokumen;
use App\Models\Pegawai;
use App\Models\Penugasan;
use App\Models\User;
use App\Support\Indonesia as Id;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DokumenService
{
    /**
     * Buat SPK dari satu atau beberapa penugasan milik petugas yang sama (dan peran yang sama).
     *
     * @param Collection<int,Penugasan> $penugasan
     * @param array $input  no_spk, tanggal_spk, ppk_id, ketua_tim_id, judul_pekerjaan, nomor_kepka, tanggal_kepka
     */
    public function buatDariPenugasan(Collection $penugasan, array $input, User $user): Dokumen
    {
        if ($penugasan->isEmpty()) {
            throw new InvalidArgumentException('Pilih minimal satu penugasan.');
        }
        $penugasan->load('kegiatan');

        $pertama = $penugasan->first();
        if ($penugasan->pluck('petugas_ref')->unique()->count() > 1) {
            throw new InvalidArgumentException('Semua penugasan dalam satu SPK harus milik petugas yang sama.');
        }
        if ($penugasan->pluck('peran')->unique()->count() > 1) {
            throw new InvalidArgumentException('Peran petugas (PPL/PML/Entri) harus sama dalam satu SPK.');
        }
        if ($penugasan->whereNotNull('dokumen_id')->isNotEmpty()) {
            throw new InvalidArgumentException('Ada penugasan yang sudah dipakai di SPK lain.');
        }

        return DB::transaction(function () use ($penugasan, $pertama, $input, $user) {
            $mulai = $penugasan->pluck('tanggal_mulai')->filter()->min();
            $selesai = $penugasan->pluck('tanggal_selesai')->filter()->max();
            $batas = $penugasan->pluck('batas_terlambat')->filter()->max() ?? $selesai?->copy()->addDays(5);
            $ketuaId = $input['ketua_tim_id'] ?? $pertama->kegiatan?->ketua_tim_id;

            $d = new Dokumen();
            $d->jenis = 'SPK_BAST';
            $d->peran = $pertama->peran;
            $d->petugas_tipe = $pertama->petugas_tipe;
            $d->mitra_id = $pertama->mitra_id;
            $d->pegawai_id = $pertama->pegawai_id;
            $d->pml_mitra_id = $pertama->pml_mitra_id;
            $d->pml_pegawai_id = $pertama->pml_pegawai_id;
            $d->pml_nama = $pertama->pml_nama;
            $d->ppk_id = $input['ppk_id'] ?? $this->ppkDefault()?->id;
            $d->ketua_tim_id = $ketuaId;

            foreach (['tipe_periode', 'bulan', 'triwulan', 'tahap', 'subround', 'semester', 'tahun'] as $k) {
                $d->$k = $pertama->$k;
            }

            $d->nomor_kepka = $input['nomor_kepka'] ?? $pertama->nomor_kepka;
            $d->tanggal_kepka = $input['tanggal_kepka'] ?? $pertama->tanggal_kepka;
            $d->no_spk = $input['no_spk'];
            $d->tanggal_spk = $input['tanggal_spk'];
            $d->judul_pekerjaan = $input['judul_pekerjaan']
                ?: $penugasan->pluck('kegiatan.uraian_tugas')->unique()->implode('; ');
            $d->tanggal_mulai = $mulai;
            $d->tanggal_selesai = $selesai;
            $d->batas_penyerahan = $batas;
            $d->total_honor = (int) $penugasan->sum('total_honor');
            $d->status_verifikasi = StatusVerifikasi::Menunggu;
            $d->dibuat_oleh = $user->id;
            $d->save();

            foreach ($penugasan->values() as $i => $p) {
                $d->details()->create([
                    'no_urut' => $i + 1,
                    'kegiatan_id' => $p->kegiatan_id,
                    'penugasan_id' => $p->id,
                    'uraian_tugas' => $p->kegiatan?->uraian_tugas ?? '-',
                    'buku_pedoman' => $p->kegiatan?->buku_pedoman ?: 'Ada',
                    'tanggal_mulai' => $p->tanggal_mulai,
                    'tanggal_selesai' => $p->tanggal_selesai,
                    'volume' => $p->volume,
                    'satuan' => $p->satuan,
                    'harga_satuan' => $p->harga_satuan,
                    'nilai_perjanjian' => $p->total_honor,
                    'beban_anggaran' => $p->kegiatan?->beban_anggaran,
                ]);
            }

            Penugasan::whereIn('id', $penugasan->pluck('id'))->update(['dokumen_id' => $d->id]);

            return $d;
        });
    }

    /**
     * Ubah data SPK (judul, nomor, tanggal, pejabat, rincian volume/harga). Status kembali ke
     * "menunggu verifikasi" jika dokumen sebelumnya sudah diputuskan PPK.
     */
    public function perbarui(Dokumen $d, array $input): Dokumen
    {
        return DB::transaction(function () use ($d, $input) {
            $d->fill(collect($input)->only([
                'no_spk', 'tanggal_spk', 'ppk_id', 'ketua_tim_id', 'judul_pekerjaan',
                'nomor_kepka', 'tanggal_kepka', 'tanggal_mulai', 'tanggal_selesai', 'batas_penyerahan',
            ])->all());

            if (! empty($input['details'])) {
                foreach ($d->details as $det) {
                    $row = $input['details'][$det->id] ?? null;
                    if (! $row) {
                        continue;
                    }
                    $det->volume = max(1, (int) $row['volume']);
                    $det->harga_satuan = max(0, (int) $row['harga_satuan']);
                    $det->nilai_perjanjian = $det->volume * $det->harga_satuan;
                    $det->save();
                }
                $d->total_honor = (int) $d->details()->sum('nilai_perjanjian');
            }

            if (in_array($d->status_verifikasi, [StatusVerifikasi::Disetujui, StatusVerifikasi::Ditolak], true)) {
                $d->status_verifikasi = StatusVerifikasi::Menunggu;
                $d->tanggal_verifikasi = null;
                $d->verifikator_id = null;
            }

            $d->save();
            return $d;
        });
    }

    /** Hapus lunak dokumen & bebaskan penugasannya agar bisa dibuatkan SPK ulang. */
    public function hapus(Dokumen $d): void
    {
        DB::transaction(function () use ($d) {
            Penugasan::where('dokumen_id', $d->id)->update(['dokumen_id' => null]);
            $d->delete();
        });
    }

    public function ppkDefault(): ?Pegawai
    {
        return Pegawai::where('is_ppk', true)->first();
    }

    /** Batas penyerahan bawaan: tanggal selesai + 5 hari. */
    public static function batasBawaan(?string $selesai): ?string
    {
        return Id::carbon($selesai)?->copy()->addDays(5)->toDateString();
    }
}
