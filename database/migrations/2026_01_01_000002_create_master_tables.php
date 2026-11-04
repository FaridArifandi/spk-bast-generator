<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mitra', function (Blueprint $t) {
            $t->id();
            $t->string('id_mitra', 30)->unique();
            $t->string('kategori', 60)->nullable();
            $t->string('nama');
            $t->string('nik', 30)->nullable()->index();
            $t->string('sobat_id', 30)->nullable();
            $t->string('email')->nullable();
            $t->string('posisi')->nullable();
            $t->string('asal')->nullable();
            $t->text('alamat_detail')->nullable();
            $t->date('tanggal_lahir')->nullable();
            $t->string('jenis_kelamin', 5)->nullable();
            $t->string('no_telp', 40)->nullable();
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });

        Schema::create('kegiatan', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 30)->unique();
            $t->string('uraian_tugas', 500);
            $t->string('satuan', 60)->default('Dokumen');
            $t->unsignedBigInteger('harga_satuan')->nullable();
            $t->string('beban_anggaran')->nullable();
            $t->string('buku_pedoman')->nullable();
            $t->foreignId('ketua_tim_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('parameters', function (Blueprint $t) {
            $t->string('kode', 60)->primary();
            $t->text('nilai')->nullable();
            $t->string('keterangan')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parameters');
        Schema::dropIfExists('kegiatan');
        Schema::dropIfExists('mitra');
    }
};
