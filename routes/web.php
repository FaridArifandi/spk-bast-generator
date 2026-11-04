<?php

use App\Http\Controllers\Admin\ParameterController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BastSmPpkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PenugasanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\VerifikasiController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'form'])->name('login');
    Route::post('/login', [LoginController::class, 'masuk'])->middleware('throttle:10,1')->name('login.proses');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'keluar'])->name('logout');
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/export-honor', \App\Http\Controllers\RekapHonorExportController::class)->name('dashboard.export-honor');

    Route::get('/profil', [ProfilController::class, 'show'])->name('profil');
    Route::put('/profil/password', [ProfilController::class, 'ubahPassword'])->name('profil.password');

    // ── Data master (baca: semua peran)
    Route::get('/pegawai', [PegawaiController::class, 'index'])->name('pegawai.index');
    Route::get('/mitra', [MitraController::class, 'index'])->name('mitra.index');
    Route::get('/kegiatan', [KegiatanController::class, 'index'])->name('kegiatan.index');

    // ── Data master (kelola)
    Route::middleware('role:ADMIN')->group(function () {
        Route::resource('pegawai', PegawaiController::class)->except(['index', 'show']);
        Route::resource('mitra', MitraController::class)->except(['index', 'show']);

        Route::get('/admin/pengguna', [UserController::class, 'index'])->name('admin.pengguna.index');
        Route::resource('admin/pengguna', UserController::class)->except(['index', 'show'])->names('admin.pengguna')->parameters(['pengguna' => 'user']);
        Route::get('/admin/parameter', [ParameterController::class, 'edit'])->name('admin.parameter');
        Route::put('/admin/parameter', [ParameterController::class, 'update'])->name('admin.parameter.update');
    });

    Route::middleware('role:ADMIN,KATIM')->group(function () {
        Route::resource('kegiatan', KegiatanController::class)->except(['index', 'show']);

        Route::resource('penugasan', PenugasanController::class)->except(['show']);

        Route::get('/dokumen/buat', [DokumenController::class, 'create'])->name('dokumen.create');
        Route::post('/dokumen', [DokumenController::class, 'store'])->name('dokumen.store');
        Route::get('/dokumen/{dokumen}/ubah', [DokumenController::class, 'edit'])->name('dokumen.edit');
        Route::put('/dokumen/{dokumen}', [DokumenController::class, 'update'])->name('dokumen.update');
        Route::delete('/dokumen/{dokumen}', [DokumenController::class, 'destroy'])->name('dokumen.destroy');
        Route::get('/dokumen/{dokumen}/bast', [DokumenController::class, 'bast'])->name('dokumen.bast');
        Route::put('/dokumen/{dokumen}/bast', [DokumenController::class, 'bastSimpan'])->name('dokumen.bast.simpan');

        Route::resource('bast-sm-ppk', BastSmPpkController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['bast-sm-ppk' => 'bast']);
    });

    // ── Dokumen: lihat (semua peran)
    Route::get('/dokumen', [DokumenController::class, 'index'])->name('dokumen.index');
    Route::get('/dokumen/unduh-zip', [DokumenController::class, 'unduhZip'])->name('dokumen.unduh-zip');
    Route::get('/dokumen/{dokumen}', [DokumenController::class, 'show'])->whereNumber('dokumen')->name('dokumen.show');
    Route::get('/dokumen/{dokumen}/unduh/{jenis}', [DokumenController::class, 'unduh'])->whereNumber('dokumen')->name('dokumen.unduh');

    Route::get('/bast-sm-ppk', [BastSmPpkController::class, 'index'])->name('bast-sm-ppk.index');
    Route::get('/bast-sm-ppk/{bast}', [BastSmPpkController::class, 'show'])->whereNumber('bast')->name('bast-sm-ppk.show');
    Route::get('/bast-sm-ppk/{bast}/unduh', [BastSmPpkController::class, 'unduh'])->whereNumber('bast')->name('bast-sm-ppk.unduh');

    // ── Verifikasi PPK
    Route::middleware('role:ADMIN,PPK')->group(function () {
        Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index');
        Route::post('/verifikasi/dokumen/{dokumen}', [VerifikasiController::class, 'dokumen'])->name('verifikasi.dokumen');
        Route::post('/verifikasi/bast-sm-ppk/{bast}', [VerifikasiController::class, 'bastSmPpk'])->name('verifikasi.bast');
    });
});
