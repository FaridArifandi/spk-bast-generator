@extends('layouts.app')
@section('judul', 'Profil saya')
@section('isi')
<div class="grid c2" style="align-items:start;max-width:960px">
    <div class="card">
        <h2>Informasi akun</h2>
        <dl class="kv">
            <dt>Nama</dt><dd>{{ $user->nama }}</dd>
            <dt>Username</dt><dd>{{ $user->username }}</dd>
            <dt>Peran</dt><dd>{{ $user->roleEnum()->label() }}</dd>
            <dt>Pegawai</dt><dd>{{ $user->pegawai?->nama ?? '—' }}</dd>
        </dl>
    </div>
    <form class="card" method="post" action="{{ route('profil.password') }}">
        @csrf @method('put')
        <h2>Ganti password</h2>
        <x-field name="password_lama" label="Password saat ini" type="password" required autocomplete="current-password" />
        <x-field name="password" label="Password baru" type="password" required autocomplete="new-password" hint="Minimal 8 karakter." />
        <x-field name="password_confirmation" label="Ulangi password baru" type="password" required autocomplete="new-password" />
        <button class="btn pri">Simpan password</button>
    </form>
</div>
@endsection
