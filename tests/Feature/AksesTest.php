<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AksesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create(['nama' => $role, 'username' => strtolower($role), 'password' => 'rahasia-123', 'role' => $role, 'aktif' => true]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_berhasil_dan_gagal(): void
    {
        $this->user('ADMIN');
        $this->post('/login', ['username' => 'admin', 'password' => 'salah'])->assertSessionHasErrors();
        $this->post('/login', ['username' => 'admin', 'password' => 'rahasia-123'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_admin_boleh_halaman_pengguna(): void
    {
        $this->actingAs($this->user('ADMIN'))->get('/admin/pengguna')->assertOk();
    }

    public function test_ppk_dan_katim_tidak_boleh_halaman_pengguna(): void
    {
        $this->actingAs($this->user('PPK'))->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($this->user('KATIM'))->get('/admin/pengguna')->assertForbidden();
    }

    public function test_halaman_utama_admin_terbuka(): void
    {
        $this->actingAs($this->user('ADMIN'));
        foreach (['/dashboard', '/pegawai', '/mitra', '/kegiatan', '/dokumen', '/penugasan', '/verifikasi', '/profil'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
