<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Pegawai;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_mengisi_pegawai_kegiatan_dan_akun_yang_saling_terhubung(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(23, Pegawai::count());
        $this->assertSame(76, Kegiatan::count());
        $this->assertSame(11, User::count());

        // PPK ditandai dari jabatan
        $this->assertSame(['Arma Juwita, S.Tr.Stat'], Pegawai::where('is_ppk', true)->pluck('nama')->all());

        // semua kegiatan punya ketua tim
        $this->assertSame(0, Kegiatan::whereNull('ketua_tim_id')->count());

        // akun terhubung ke pegawai yang benar
        $tri = User::where('username', 'triwahyudi')->firstOrFail();
        $this->assertSame('KATIM', $tri->role);
        $this->assertSame('distribusi', $tri->sub_role);
        $this->assertSame('Tri Wahyudi, S.Stat', $tri->pegawai->nama);
        $this->assertSame(0, User::whereNull('pegawai_id')->count());

        // akun admin cadangan tidak dibuat karena sudah ada ADMIN dari data
        $this->assertFalse(User::where('username', 'admin')->exists());
    }

    public function test_seeder_dapat_diulang_tanpa_duplikat_dan_tanpa_mengubah_password(): void
    {
        $this->seed(DatabaseSeeder::class);
        $hash = User::where('username', 'armajuwita')->value('password');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(23, Pegawai::count());
        $this->assertSame(76, Kegiatan::count());
        $this->assertSame(11, User::count());
        $this->assertSame($hash, User::where('username', 'armajuwita')->value('password'));
    }
}
