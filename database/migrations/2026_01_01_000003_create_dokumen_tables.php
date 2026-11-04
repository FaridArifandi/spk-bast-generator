<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen', function (Blueprint $t) {
            $t->id();
            $t->string('jenis', 20)->default('SPK_BAST');          // SPK | SPK_BAST | BAST
            $t->string('peran', 10)->default('ppl');               // ppl | pml | entri

            // petugas (mitra ATAU pegawai)
            $t->string('petugas_tipe', 10)->default('mitra');      // mitra | pegawai
            $t->foreignId('mitra_id')->nullable()->constrained('mitra')->nullOnDelete();
            $t->foreignId('pegawai_id')->nullable()->constrained('pegawai')->nullOnDelete();

            // PML (untuk dokumen PPL / entri)
            $t->foreignId('pml_mitra_id')->nullable()->constrained('mitra')->nullOnDelete();
            $t->foreignId('pml_pegawai_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $t->string('pml_nama')->nullable();

            $t->foreignId('ppk_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $t->foreignId('ketua_tim_id')->nullable()->constrained('pegawai')->nullOnDelete();

            // periode
            $t->string('tipe_periode', 20)->nullable();           // bulan|triwulan|tahap|subround|semester
            $t->string('bulan', 20)->nullable();
            $t->string('triwulan', 10)->nullable();
            $t->string('tahap', 10)->nullable();
            $t->string('subround', 10)->nullable();
            $t->string('semester', 10)->nullable();
            $t->unsignedSmallInteger('tahun')->index();

            $t->string('nomor_kepka')->nullable();
            $t->date('tanggal_kepka')->nullable();

            $t->string('no_spk')->nullable()->index();
            $t->date('tanggal_spk')->nullable();
            $t->string('no_bast_ppl_pml')->nullable();
            $t->date('tanggal_bast_ppl_pml')->nullable();
            $t->string('no_bast_ppl_sm')->nullable();
            $t->date('tanggal_bast_ppl_sm')->nullable();
            $t->string('no_bast_pml_sm')->nullable();
            $t->date('tanggal_bast_pml_sm')->nullable();
            $t->string('no_bast_entri_sm')->nullable();
            $t->date('tanggal_bast_entri_sm')->nullable();

            $t->string('judul_pekerjaan', 600)->nullable();
            $t->date('tanggal_mulai')->nullable();
            $t->date('tanggal_selesai')->nullable();
            $t->date('batas_penyerahan')->nullable();
            $t->unsignedBigInteger('total_honor')->default(0);

            $t->string('status_verifikasi', 30)->default('menunggu_verifikasi')->index();
            $t->text('catatan_verifikasi')->nullable();
            $t->date('tanggal_verifikasi')->nullable();
            $t->foreignId('verifikator_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('diunduh_at')->nullable();

            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('penugasan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $t->string('petugas_tipe', 10)->default('mitra');
            $t->foreignId('mitra_id')->nullable()->constrained('mitra')->cascadeOnDelete();
            $t->foreignId('pegawai_id')->nullable()->constrained('pegawai')->cascadeOnDelete();
            $t->string('peran', 10)->default('ppl');
            $t->foreignId('pml_mitra_id')->nullable()->constrained('mitra')->nullOnDelete();
            $t->foreignId('pml_pegawai_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $t->string('pml_nama')->nullable();

            $t->date('tanggal_mulai')->nullable();
            $t->date('tanggal_selesai')->nullable();
            $t->date('batas_terlambat')->nullable();
            $t->string('nomor_kepka')->nullable();
            $t->date('tanggal_kepka')->nullable();

            $t->string('tipe_periode', 20)->default('bulan');
            $t->string('bulan', 20)->nullable();
            $t->string('triwulan', 10)->nullable();
            $t->string('tahap', 10)->nullable();
            $t->string('subround', 10)->nullable();
            $t->string('semester', 10)->nullable();
            $t->unsignedSmallInteger('tahun')->index();

            $t->unsignedInteger('volume')->default(1);
            $t->string('satuan', 60)->default('Dokumen');
            $t->unsignedBigInteger('harga_satuan')->default(0);
            $t->unsignedBigInteger('total_honor')->default(0);

            $t->foreignId('dokumen_id')->nullable()->constrained('dokumen')->nullOnDelete();
            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('dokumen_detail', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dokumen_id')->constrained('dokumen')->cascadeOnDelete();
            $t->unsignedSmallInteger('no_urut')->default(1);
            $t->foreignId('kegiatan_id')->nullable()->constrained('kegiatan')->nullOnDelete();
            $t->foreignId('penugasan_id')->nullable()->constrained('penugasan')->nullOnDelete();
            $t->string('uraian_tugas', 600);
            $t->string('buku_pedoman')->nullable();
            $t->date('tanggal_mulai')->nullable();
            $t->date('tanggal_selesai')->nullable();
            $t->unsignedInteger('volume')->default(1);
            $t->string('satuan', 60)->default('Dokumen');
            $t->unsignedBigInteger('harga_satuan')->default(0);
            $t->unsignedBigInteger('nilai_perjanjian')->default(0);
            $t->string('beban_anggaran')->nullable();
            $t->timestamps();
        });

        Schema::create('bast_sm_ppk', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('tahun')->index();
            $t->string('bulan', 20)->nullable();
            $t->string('uraian_tugas', 600);
            $t->foreignId('ppk_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $t->foreignId('ketua_tim_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $t->string('no_bast')->nullable();
            $t->date('tanggal_bast')->nullable();
            $t->unsignedBigInteger('total_honor')->default(0);
            $t->string('status_verifikasi', 30)->default('menunggu_verifikasi')->index();
            $t->text('catatan_verifikasi')->nullable();
            $t->date('tanggal_verifikasi')->nullable();
            $t->foreignId('verifikator_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('diunduh_at')->nullable();
            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bast_sm_ppk');
        Schema::dropIfExists('dokumen_detail');
        Schema::dropIfExists('penugasan');
        Schema::dropIfExists('dokumen');
    }
};
