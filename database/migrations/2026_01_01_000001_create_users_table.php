<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('nip', 30)->nullable()->index();
            $t->string('jabatan')->nullable();
            $t->string('pangkat')->nullable();
            $t->string('golongan')->nullable();
            $t->boolean('is_ppk')->default(false);
            $t->timestamps();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('username')->unique();
            $t->string('password');
            $t->string('role', 20)->default('PENGGUNA')->index();
            $t->string('sub_role', 40)->nullable();
            $t->foreignId('pegawai_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $t->boolean('aktif')->default(true);
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('pegawai');
    }
};
