@extends('layouts.guest')
@section('judul', 'Masuk')
@section('isi')
<div class="login-wrap split">
    <aside class="login-hero">
        <div class="mark"><img class="logo" src="{{ asset('favicon.svg') }}" alt="">{{ config('app.name') }}</div>
        <div>
            <h2>Dokumen SPK &amp; BAST mitra statistik, rapi dan terverifikasi.</h2>
            <ul>
                <li>Buat SPK langsung dari penugasan petugas, lengkap dengan penomoran otomatis.</li>
                <li>Verifikasi PPK sebelum dokumen diunduh dalam format Word.</li>
                <li>Pantau rekap honor mitra dan batas maksimum tiap bulan.</li>
            </ul>
        </div>
        <div class="foot">{{ \App\Models\Parameter::nilai('NAMA_KANTOR', '') }}</div>
    </aside>
    <div class="login-pane">
        <form class="login-box" method="post" action="{{ route('login.proses') }}">
            @csrf
            <img class="logo-sm" src="{{ asset('favicon.svg') }}" alt="">
            <h1>Masuk</h1>
            <p class="muted" style="margin-top:0;margin-bottom:22px">Gunakan akun {{ config('app.name') }} Anda.</p>
            <x-field name="username" label="Username" required autofocus autocomplete="username" />
            <x-field name="password" label="Password" type="password" required autocomplete="current-password" />
            <div class="field"><label style="font-weight:500"><input type="checkbox" name="ingat" value="1"> Ingat saya di perangkat ini</label></div>
            <button class="btn pri" style="width:100%" type="submit">Masuk</button>
        </form>
    </div>
</div>
@endsection
