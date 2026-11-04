<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\Mitra;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AksesTimTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?Pegawai $pegawai = null): User
    {
        return User::create([
            'nama' => $role,
            'username' => strtolower($role).'_'.uniqid(),
            'password' => 'rahasia-123',
            'role' => $role,
            'aktif' => true,
            'pegawai_id' => $pegawai?->id,
        ]);
    }

    private function dokumen(Pegawai $ketua, Pegawai $ppk, string $status = 'menunggu_verifikasi'): Dokumen
    {
        $mitra = Mitra::create(['id_mitra' => 'M'.uniqid(), 'nama' => 'Mitra Uji']);

        return Dokumen::create([
            'jenis' => 'SPK_BAST',
            'peran' => 'ppl',
            'petugas_tipe' => 'mitra',
            'mitra_id' => $mitra->id,
            'ppk_id' => $ppk->id,
            'ketua_tim_id' => $ketua->id,
            'tipe_periode' => 'bulan',
            'bulan' => 'Oktober',
            'tahun' => 2026,
            'no_spk' => 'SPK-'.uniqid(),
            'status_verifikasi' => $status,
            'total_honor' => 100000,
        ]);
    }

    public function test_katim_tidak_boleh_menghapus_spk_tim_lain_tapi_boleh_milik_sendiri(): void
    {
        $ketuaA = Pegawai::create(['nama' => 'Ketua A']);
        $ketuaB = Pegawai::create(['nama' => 'Ketua B']);
        $ppk = Pegawai::create(['nama' => 'PPK', 'is_ppk' => true]);

        $milikB = $this->dokumen($ketuaB, $ppk);
        $milikA = $this->dokumen($ketuaA, $ppk);
        $katimA = $this->user('KATIM', $ketuaA);

        $this->actingAs($katimA)->delete("/dokumen/{$milikB->id}")->assertForbidden();
        $this->assertNotSoftDeleted($milikB);

        $this->actingAs($katimA)->delete("/dokumen/{$milikA->id}")->assertRedirect();
        $this->assertSoftDeleted($milikA);
    }

    public function test_ppk_hanya_boleh_memverifikasi_spk_miliknya(): void
    {
        $ketua = Pegawai::create(['nama' => 'Ketua']);
        $ppkA = Pegawai::create(['nama' => 'PPK A', 'is_ppk' => true]);
        $ppkB = Pegawai::create(['nama' => 'PPK B', 'is_ppk' => true]);

        $doc = $this->dokumen($ketua, $ppkA);
        $userB = $this->user('PPK', $ppkB);
        $userA = $this->user('PPK', $ppkA);

        $this->actingAs($userB)->post("/verifikasi/dokumen/{$doc->id}", ['keputusan' => 'disetujui'])->assertForbidden();
        $this->assertSame('menunggu_verifikasi', $doc->fresh()->status_verifikasi->value);

        $this->actingAs($userA)->post("/verifikasi/dokumen/{$doc->id}", ['keputusan' => 'disetujui'])->assertRedirect();
        $this->assertSame('disetujui', $doc->fresh()->status_verifikasi->value);
    }

    public function test_spk_belum_disetujui_tidak_bisa_diunduh_siapa_pun(): void
    {
        $ketua = Pegawai::create(['nama' => 'Ketua']);
        $ppk = Pegawai::create(['nama' => 'PPK', 'is_ppk' => true]);
        $doc = $this->dokumen($ketua, $ppk);

        foreach (['ADMIN', 'KATIM', 'PENGGUNA'] as $role) {
            $this->actingAs($this->user($role))
                ->get("/dokumen/{$doc->id}/unduh/spk")
                ->assertSessionHas('galat');
        }
    }

    public function test_hapus_spk_disetujui_butuh_konfirmasi(): void
    {
        $ketua = Pegawai::create(['nama' => 'Ketua']);
        $ppk = Pegawai::create(['nama' => 'PPK', 'is_ppk' => true]);
        $doc = $this->dokumen($ketua, $ppk, 'disetujui');
        $admin = $this->user('ADMIN');

        $this->actingAs($admin)->delete("/dokumen/{$doc->id}")->assertSessionHas('galat');
        $this->assertNotSoftDeleted($doc);

        $this->actingAs($admin)->delete("/dokumen/{$doc->id}", ['konfirmasi' => 1])->assertRedirect();
        $this->assertSoftDeleted($doc);
    }
}
