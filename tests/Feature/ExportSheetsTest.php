<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Mitra;
use App\Services\SheetsExportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExportSheetsTest extends TestCase
{
    use RefreshDatabase;

    private function aktifkan(): void
    {
        config(['services.sheets.url' => 'https://script.google.com/macros/s/x/exec', 'services.sheets.token' => 'rahasia']);
        Cache::flush();
    }

    public function test_tanpa_url_atau_token_dilewati(): void
    {
        config(['services.sheets.url' => null, 'services.sheets.token' => null]);
        Http::fake();

        $this->artisan('spk:export-sheets')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_kirim_lalu_dilewati_bila_tak_ada_perubahan(): void
    {
        $this->aktifkan();
        $this->seed(DatabaseSeeder::class);
        Mitra::create(['id_mitra' => 'M001', 'nama' => 'Budi', 'nik' => '1175000000000001', 'aktif' => true]);
        Http::fake(['*' => Http::response(['status' => 'ok', 'rows' => ['Kegiatan' => 76]])]);

        $this->artisan('spk:export-sheets')->assertSuccessful();
        Http::assertSentCount(1);
        Http::assertSent(function ($req) {
            $b = $req->data();
            return $b['token'] === 'rahasia'
                && count($b['sheets']['Kegiatan']['rows']) === 76
                && ! str_contains(json_encode($b['sheets']['Mitra']), '1175000000000001'); // NIK tidak diekspor
        });

        $this->artisan('spk:export-sheets')->assertSuccessful();
        Http::assertSentCount(1); // tidak ada perubahan -> tidak kirim lagi

        Kegiatan::first()->update(['satuan' => 'Rumah Tangga']);
        $this->artisan('spk:export-sheets')->assertSuccessful();
        Http::assertSentCount(2);
    }

    public function test_galat_apps_script_dilaporkan_dan_dicoba_lagi_nanti(): void
    {
        $this->aktifkan();
        Http::fake(['*' => Http::response(['status' => 'error', 'message' => 'token salah'])]);

        $this->artisan('spk:export-sheets')->assertFailed();
        $this->assertNull(Cache::get(SheetsExportService::CACHE_HASH));
    }

    public function test_impor_excel_ulang_tidak_menggandakan_dan_tombol_hanya_admin(): void
    {
        $this->aktifkan();
        Http::fake(['*' => Http::response(['status' => 'ok', 'rows' => []])]);
        $this->seed(DatabaseSeeder::class);

        $katim = \App\Models\User::where('role', 'KATIM')->first();
        $this->actingAs($katim)->post('/admin/sheets/sinkron')->assertForbidden();

        $admin = \App\Models\User::where('role', 'ADMIN')->first();
        $this->actingAs($admin)->post('/admin/sheets/sinkron')->assertRedirect();
        Http::assertSentCount(1);
    }
}
