<?php

namespace Tests\Feature;

use App\Models\BastSmPpk;
use App\Models\Dokumen;
use App\Models\DokumenDetail;
use App\Models\Kegiatan;
use App\Models\Mitra;
use App\Models\Pegawai;
use App\Services\DokumenRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DokumenRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_semua_template_html_dan_word_bisa_dirender(): void
    {
        $ppk = Pegawai::create(['nama' => 'Erni Lusiani, S.Stat.', 'nip' => '19900101', 'is_ppk' => true, 'jabatan' => 'PPK']);
        $ketua = Pegawai::create(['nama' => 'Budi Santoso', 'nip' => '19850101', 'jabatan' => 'Ketua Tim']);
        $pmlPegawai = Pegawai::create(['nama' => 'PML Organik', 'nip' => '19880101']);
        $petugasPegawai = Pegawai::create(['nama' => 'Petugas Organik', 'nip' => '19920101']);
        $mitra = Mitra::create(['id_mitra' => 'M001', 'nama' => 'Aufa Musfidah', 'nik' => '1175010101010001', 'asal' => 'Subulussalam']);
        $pmlMitra = Mitra::create(['id_mitra' => 'M002', 'nama' => 'PML Mitra', 'nik' => '1175010101010002', 'asal' => 'Subulussalam']);

        $kegiatan = Kegiatan::create([
            'kode' => 'K001',
            'uraian_tugas' => 'Pendataan Survei Ekonomi',
            'satuan' => 'Dokumen',
            'harga_satuan' => 15000,
            'ketua_tim_id' => $ketua->id,
        ]);

        $renderer = app(DokumenRenderer::class);

        // 1. PPL Mitra dengan PML Mitra
        $d1 = Dokumen::create([
            'jenis' => 'SPK_BAST',
            'peran' => 'ppl',
            'petugas_tipe' => 'mitra',
            'mitra_id' => $mitra->id,
            'pml_mitra_id' => $pmlMitra->id,
            'ppk_id' => $ppk->id,
            'ketua_tim_id' => $ketua->id,
            'tipe_periode' => 'bulan',
            'bulan' => 'Oktober',
            'tahun' => 2026,
            'no_spk' => '10 TAHUN 2026',
            'tanggal_spk' => '2026-10-01',
            'no_bast_ppl_pml' => 'Nomor 010 Tahun 2026',
            'tanggal_bast_ppl_pml' => '2026-10-25',
            'no_bast_ppl_sm' => 'Nomor 011 Tahun 2026',
            'tanggal_bast_ppl_sm' => '2026-10-26',
            'judul_pekerjaan' => 'Pendataan Survei Ekonomi',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-25',
            'total_honor' => 1500000,
            'status_verifikasi' => 'disetujui',
        ]);
        DokumenDetail::create([
            'dokumen_id' => $d1->id,
            'no_urut' => 1,
            'kegiatan_id' => $kegiatan->id,
            'uraian_tugas' => 'Pendataan Survei Ekonomi',
            'volume' => 100,
            'satuan' => 'Dokumen',
            'harga_satuan' => 15000,
            'nilai_perjanjian' => 1500000,
        ]);

        foreach (['spk', 'ppl-pml', 'ppl-sm'] as $jenis) {
            $this->assertNotEmpty($renderer->html($d1, $jenis));
            $w = $renderer->word($d1, $jenis);
            $this->assertFileExists($w['path']);
            @unlink($w['path']);
        }

        // 2. PPL Mitra dengan PML Organik
        $d2 = Dokumen::create([
            'jenis' => 'SPK_BAST',
            'peran' => 'ppl',
            'petugas_tipe' => 'mitra',
            'mitra_id' => $mitra->id,
            'pml_pegawai_id' => $pmlPegawai->id,
            'ppk_id' => $ppk->id,
            'ketua_tim_id' => $ketua->id,
            'tipe_periode' => 'bulan',
            'bulan' => 'Oktober',
            'tahun' => 2026,
            'no_spk' => '11 TAHUN 2026',
            'tanggal_spk' => '2026-10-01',
            'no_bast_ppl_pml' => 'Nomor 012 Tahun 2026',
            'tanggal_bast_ppl_pml' => '2026-10-25',
            'judul_pekerjaan' => 'Pendataan Survei Ekonomi',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-25',
            'total_honor' => 1500000,
            'status_verifikasi' => 'disetujui',
        ]);
        DokumenDetail::create([
            'dokumen_id' => $d2->id,
            'no_urut' => 1,
            'kegiatan_id' => $kegiatan->id,
            'uraian_tugas' => 'Pendataan Survei Ekonomi',
            'volume' => 100,
            'satuan' => 'Dokumen',
            'harga_satuan' => 15000,
            'nilai_perjanjian' => 1500000,
        ]);
        $this->assertNotEmpty($renderer->html($d2, 'ppl-pml'));
        $w2 = $renderer->word($d2, 'ppl-pml');
        $this->assertFileExists($w2['path']);
        @unlink($w2['path']);

        // 3. Dokumen PML Mitra (bast-pml-sm-mitra)
        $dPml = Dokumen::create([
            'jenis' => 'SPK_BAST',
            'peran' => 'pml',
            'petugas_tipe' => 'mitra',
            'mitra_id' => $pmlMitra->id,
            'ppk_id' => $ppk->id,
            'ketua_tim_id' => $ketua->id,
            'tipe_periode' => 'bulan',
            'bulan' => 'Oktober',
            'tahun' => 2026,
            'no_spk' => '12 TAHUN 2026',
            'tanggal_spk' => '2026-10-01',
            'no_bast_pml_sm' => 'Nomor 013 Tahun 2026',
            'tanggal_bast_pml_sm' => '2026-10-26',
            'judul_pekerjaan' => 'Pengawasan Survei Ekonomi',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-25',
            'total_honor' => 1000000,
            'status_verifikasi' => 'disetujui',
        ]);
        DokumenDetail::create([
            'dokumen_id' => $dPml->id,
            'no_urut' => 1,
            'kegiatan_id' => $kegiatan->id,
            'uraian_tugas' => 'Pengawasan Survei Ekonomi',
            'volume' => 10,
            'satuan' => 'Dokumen',
            'harga_satuan' => 100000,
            'nilai_perjanjian' => 1000000,
        ]);
        $this->assertNotEmpty($renderer->html($dPml, 'pml-sm'));
        $wPml = $renderer->word($dPml, 'pml-sm');
        $this->assertFileExists($wPml['path']);
        @unlink($wPml['path']);

        // 4. Dokumen PML Organik (bast-pml-sm-organik)
        $dPmlOrg = Dokumen::create([
            'jenis' => 'SPK_BAST',
            'peran' => 'pml',
            'petugas_tipe' => 'pegawai',
            'pegawai_id' => $petugasPegawai->id,
            'ppk_id' => $ppk->id,
            'ketua_tim_id' => $ketua->id,
            'tipe_periode' => 'bulan',
            'bulan' => 'Oktober',
            'tahun' => 2026,
            'no_spk' => '13 TAHUN 2026',
            'tanggal_spk' => '2026-10-01',
            'no_bast_pml_sm' => 'Nomor 014 Tahun 2026',
            'tanggal_bast_pml_sm' => '2026-10-26',
            'judul_pekerjaan' => 'Pengawasan Survei Ekonomi',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-25',
            'total_honor' => 0,
            'status_verifikasi' => 'disetujui',
        ]);
        DokumenDetail::create([
            'dokumen_id' => $dPmlOrg->id,
            'no_urut' => 1,
            'kegiatan_id' => $kegiatan->id,
            'uraian_tugas' => 'Pengawasan Survei Ekonomi',
            'volume' => 10,
            'satuan' => 'Dokumen',
            'harga_satuan' => 0,
            'nilai_perjanjian' => 0,
        ]);
        $this->assertNotEmpty($renderer->html($dPmlOrg, 'pml-sm'));
        $wPmlOrg = $renderer->word($dPmlOrg, 'pml-sm');
        $this->assertFileExists($wPmlOrg['path']);
        @unlink($wPmlOrg['path']);

        // 5. Dokumen Entri (bast-entri-sm)
        $dEntri = Dokumen::create([
            'jenis' => 'SPK_BAST',
            'peran' => 'entri',
            'petugas_tipe' => 'mitra',
            'mitra_id' => $mitra->id,
            'ppk_id' => $ppk->id,
            'ketua_tim_id' => $ketua->id,
            'tipe_periode' => 'bulan',
            'bulan' => 'Oktober',
            'tahun' => 2026,
            'no_spk' => '14 TAHUN 2026',
            'tanggal_spk' => '2026-10-01',
            'no_bast_entri_sm' => 'Nomor 015 Tahun 2026',
            'tanggal_bast_entri_sm' => '2026-10-26',
            'judul_pekerjaan' => 'Pengolahan Survei Ekonomi',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-25',
            'total_honor' => 500000,
            'status_verifikasi' => 'disetujui',
        ]);
        DokumenDetail::create([
            'dokumen_id' => $dEntri->id,
            'no_urut' => 1,
            'kegiatan_id' => $kegiatan->id,
            'uraian_tugas' => 'Pengolahan Survei Ekonomi',
            'volume' => 50,
            'satuan' => 'Dokumen',
            'harga_satuan' => 10000,
            'nilai_perjanjian' => 500000,
        ]);
        $this->assertNotEmpty($renderer->html($dEntri, 'entri-sm'));
        $wEntri = $renderer->word($dEntri, 'entri-sm');
        $this->assertFileExists($wEntri['path']);
        @unlink($wEntri['path']);
    }
}
