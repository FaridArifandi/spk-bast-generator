<?php

namespace Tests\Feature;

use App\Models\BastSmPpk;
use App\Models\Dokumen;
use App\Models\DokumenDetail;
use App\Models\Kegiatan;
use App\Models\Mitra;
use App\Models\Pegawai;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SistemPerbaikanTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, bool $aktif = true): User
    {
        return User::create([
            'nama' => $role,
            'username' => strtolower($role).'_'.uniqid(),
            'password' => 'rahasia-123',
            'role' => $role,
            'aktif' => $aktif,
        ]);
    }

    public function test_kalkulasi_honor_bast_sm_ppk_tidak_double_counting_spk_multi_detail(): void
    {
        $ppk = Pegawai::create(['nama' => 'PPK Test', 'nip' => '1990', 'is_ppk' => true]);
        $ketua = Pegawai::create(['nama' => 'Ketua Test', 'nip' => '1985']);
        $mitra = Mitra::create(['id_mitra' => 'M01', 'nama' => 'Mitra A']);

        $keg1 = Kegiatan::create(['kode' => 'K1', 'uraian_tugas' => 'Kegiatan Satu', 'harga_satuan' => 100000]);
        $keg2 = Kegiatan::create(['kode' => 'K2', 'uraian_tugas' => 'Kegiatan Dua', 'harga_satuan' => 200000]);

        // SPK yang memiliki 2 pekerjaan: Keg 1 (Rp 100.000) dan Keg 2 (Rp 200.000). Total SPK = Rp 300.000
        $doc = Dokumen::create([
            'jenis' => 'SPK_BAST',
            'peran' => 'ppl',
            'petugas_tipe' => 'mitra',
            'mitra_id' => $mitra->id,
            'ppk_id' => $ppk->id,
            'ketua_tim_id' => $ketua->id,
            'tipe_periode' => 'bulan',
            'bulan' => 'Oktober',
            'tahun' => 2026,
            'no_spk' => '01/2026',
            'total_honor' => 300000,
            'status_verifikasi' => 'disetujui',
        ]);

        DokumenDetail::create([
            'dokumen_id' => $doc->id,
            'no_urut' => 1,
            'kegiatan_id' => $keg1->id,
            'uraian_tugas' => 'Kegiatan Satu',
            'volume' => 1,
            'harga_satuan' => 100000,
            'nilai_perjanjian' => 100000,
        ]);

        DokumenDetail::create([
            'dokumen_id' => $doc->id,
            'no_urut' => 2,
            'kegiatan_id' => $keg2->id,
            'uraian_tugas' => 'Kegiatan Dua',
            'volume' => 1,
            'harga_satuan' => 200000,
            'nilai_perjanjian' => 200000,
        ]);

        $admin = $this->user('ADMIN');

        // BAST SM-PPK untuk 'Kegiatan Satu' tanpa mengisi total_honor manual
        $this->actingAs($admin)->post('/bast-sm-ppk', [
            'uraian_tugas' => 'Kegiatan Satu',
            'bulan' => 'Oktober',
            'tahun' => 2026,
            'ketua_tim_id' => $ketua->id,
            'ppk_id' => $ppk->id,
            'no_bast' => 'BAST-01/2026',
            'tanggal_bast' => '2026-10-31',
        ])->assertRedirect();

        $bast1 = BastSmPpk::where('uraian_tugas', 'Kegiatan Satu')->first();
        $this->assertNotNull($bast1);
        // Harus 100.000, BUKAN 300.000 (total_honor Dokumen)
        $this->assertSame(100000, (int) $bast1->total_honor);
    }

    public function test_penghapusan_pegawai_dicegah_bila_ada_relasi(): void
    {
        $pegawai = Pegawai::create(['nama' => 'Pegawai Terpakai', 'nip' => '12345']);
        Kegiatan::create(['kode' => 'K3', 'uraian_tugas' => 'Kegiatan Tiga', 'ketua_tim_id' => $pegawai->id]);

        $admin = $this->user('ADMIN');
        $this->actingAs($admin)->delete("/pegawai/{$pegawai->id}")
            ->assertSessionHas('galat');

        $this->assertDatabaseHas('pegawai', ['id' => $pegawai->id]);
    }

    public function test_penghapusan_mitra_dicegah_bila_masih_ada_penugasan(): void
    {
        $mitra = Mitra::create(['id_mitra' => 'M02', 'nama' => 'Mitra B']);
        $keg = Kegiatan::create(['kode' => 'K4', 'uraian_tugas' => 'Kegiatan Empat']);

        Penugasan::create([
            'kegiatan_id' => $keg->id,
            'petugas_tipe' => 'mitra',
            'mitra_id' => $mitra->id,
            'peran' => 'ppl',
            'tahun' => 2026,
            'volume' => 1,
            'harga_satuan' => 50000,
            'total_honor' => 50000,
        ]);

        $admin = $this->user('ADMIN');
        $this->actingAs($admin)->delete("/mitra/{$mitra->id}")
            ->assertSessionHas('galat');

        $this->assertDatabaseHas('mitra', ['id' => $mitra->id]);
    }

    public function test_edit_penugasan_berhasil_memperbarui_honor(): void
    {
        $mitra = Mitra::create(['id_mitra' => 'M03', 'nama' => 'Mitra C']);
        $keg = Kegiatan::create(['kode' => 'K5', 'uraian_tugas' => 'Kegiatan Lima']);

        $p = Penugasan::create([
            'kegiatan_id' => $keg->id,
            'petugas_tipe' => 'mitra',
            'mitra_id' => $mitra->id,
            'peran' => 'ppl',
            'tipe_periode' => 'bulan',
            'bulan' => 'Oktober',
            'tahun' => 2026,
            'volume' => 1,
            'harga_satuan' => 50000,
            'total_honor' => 50000,
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-15',
        ]);

        $katim = $this->user('KATIM');
        $this->actingAs($katim)->get("/penugasan/{$p->id}/edit")->assertOk();

        $this->actingAs($katim)->put("/penugasan/{$p->id}", [
            'kegiatan_id' => $keg->id,
            'tipe_periode' => 'bulan',
            'bulan' => 'Oktober',
            'tahun' => 2026,
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-20',
            'petugas' => 'mitra:'.$mitra->id,
            'peran' => 'ppl',
            'volume' => 5,
            'harga_satuan' => 60000,
        ])->assertRedirect('/penugasan');

        $p->refresh();
        $this->assertSame(5, (int) $p->volume);
        $this->assertSame(60000, (int) $p->harga_satuan);
        $this->assertSame(300000, (int) $p->total_honor); // 5 * 60000
    }

    public function test_user_nonaktif_dikeluarkan_otomatis(): void
    {
        $user = $this->user('PENGGUNA', aktif: false);
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_export_honor_excel(): void
    {
        $admin = $this->user('ADMIN');
        $res = $this->actingAs($admin)->get('/dashboard/export-honor?tahun=2026&bulan=Oktober');
        $res->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', (string) $res->headers->get('content-type'));
    }

    public function test_unduh_zip_dokumen_disetujui(): void
    {
        $ppk = Pegawai::create(['nama' => 'PPK Z', 'nip' => '1999', 'is_ppk' => true]);
        $mitra = Mitra::create(['id_mitra' => 'M99', 'nama' => 'Mitra Z']);
        $keg = Kegiatan::create(['kode' => 'KZ', 'uraian_tugas' => 'Kegiatan Z']);

        $doc = Dokumen::create([
            'jenis' => 'SPK_BAST',
            'peran' => 'ppl',
            'petugas_tipe' => 'mitra',
            'mitra_id' => $mitra->id,
            'ppk_id' => $ppk->id,
            'tipe_periode' => 'bulan',
            'bulan' => 'Oktober',
            'tahun' => 2026,
            'no_spk' => 'SPK-Z/2026',
            'tanggal_spk' => '2026-10-01',
            'status_verifikasi' => 'disetujui',
            'total_honor' => 50000,
        ]);

        DokumenDetail::create([
            'dokumen_id' => $doc->id,
            'no_urut' => 1,
            'kegiatan_id' => $keg->id,
            'uraian_tugas' => 'Kegiatan Z',
            'volume' => 1,
            'harga_satuan' => 50000,
            'nilai_perjanjian' => 50000,
        ]);

        $admin = $this->user('ADMIN');
        $res = $this->actingAs($admin)->get('/dokumen/unduh-zip?tahun=2026&bulan=Oktober');
        $res->assertOk();
        $this->assertStringContainsString('application/zip', (string) $res->headers->get('content-type'));
    }
}

