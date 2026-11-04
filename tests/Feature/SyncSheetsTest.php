<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Mitra;
use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncSheetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_menarik_data_master_dari_apps_script(): void
    {
        Http::fake(fn ($req) => Http::response(match ($req['sheet']) {
            'Pegawai' => [['Nama_Pegawai' => 'Erni Lusiani, S.Stat.', 'NIP' => '1999', 'Jabatan' => 'PPK']],
            'Mitra' => [['ID_Mitra' => 'M001', 'Nama_Mitra' => 'Aufa Musfidah', 'Tanggal_Lahir' => '1999-04-23T17:00:00.000Z']],
            'Kegiatan' => [['ID_Kegiatan' => 'K1', 'Uraian_Tugas' => 'Pendataan', 'Satuan' => 'Dokumen', 'Harga_Satuan' => 10000]],
            default => [],
        }));

        $this->artisan('spk:sync-sheets', ['--url' => 'https://script.google.com/macros/s/x/exec'])->assertSuccessful();

        $this->assertSame(1, Pegawai::count());
        $this->assertSame('1999-04-24', Mitra::first()->tanggal_lahir->toDateString()); // UTC → Asia/Jakarta
        $this->assertSame(10000, (int) Kegiatan::first()->harga_satuan);

        // idempoten
        $this->artisan('spk:sync-sheets', ['--url' => 'https://script.google.com/macros/s/x/exec'])->assertSuccessful();
        $this->assertSame(1, Mitra::count());
    }

    public function test_gagal_bila_url_kosong(): void
    {
        config(['services.sheets.url' => null]);
        $this->artisan('spk:sync-sheets')->assertFailed();
    }
}
