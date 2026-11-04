<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Beranda') — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dokumen.css') }}">
    @stack('head')
</head>
<body>
@php($u = auth()->user())
<div class="app">
    <aside class="side" id="side">
        <div class="brand">{{ config('app.name') }}<small>{{ \App\Models\Parameter::nilai('NAMA_KANTOR', 'BPS') }}</small></div>
        <nav>
            <a href="{{ route('dashboard') }}" @class(['on' => request()->routeIs('dashboard')])>Dashboard</a>

            @if($u->hasRole('ADMIN','PPK'))
                <a href="{{ route('verifikasi.index') }}" @class(['on' => request()->routeIs('verifikasi.*')])>Verifikasi PPK</a>
            @endif

            <div class="grp">Dokumen</div>
            @if($u->bisaKelolaDokumen())
                <a href="{{ route('penugasan.index') }}" @class(['on' => request()->routeIs('penugasan.*')])>Penugasan Petugas</a>
                <a href="{{ route('dokumen.create') }}" @class(['on' => request()->routeIs('dokumen.create')])>Buat SPK</a>
            @endif
            <a href="{{ route('dokumen.index') }}" @class(['on' => request()->routeIs('dokumen.index','dokumen.show','dokumen.edit','dokumen.bast')])>SPK &amp; BAST</a>
            <a href="{{ route('bast-sm-ppk.index') }}" @class(['on' => request()->routeIs('bast-sm-ppk.*')])>BAST SM → PPK</a>

            <div class="grp">Data Master</div>
            <a href="{{ route('mitra.index') }}" @class(['on' => request()->routeIs('mitra.*')])>Mitra</a>
            <a href="{{ route('pegawai.index') }}" @class(['on' => request()->routeIs('pegawai.*')])>Pegawai</a>
            <a href="{{ route('kegiatan.index') }}" @class(['on' => request()->routeIs('kegiatan.*')])>Kegiatan</a>

            @if($u->isAdmin())
                <div class="grp">Administrasi</div>
                <a href="{{ route('admin.pengguna.index') }}" @class(['on' => request()->routeIs('admin.pengguna.*')])>Pengguna</a>
                <a href="{{ route('admin.parameter') }}" @class(['on' => request()->routeIs('admin.parameter*')])>Parameter</a>
            @endif
        </nav>
        <div class="who">
            <b>{{ $u->nama }}</b>
            {{ $u->roleEnum()->label() }}<br>
            <a href="{{ route('profil') }}" style="color:#93c5fd">Profil &amp; password</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form>
        </div>
    </aside>

    <div class="main">
        <header class="top">
            <div class="row">
                <button class="btn sm menu-btn" type="button" onclick="document.getElementById('side').classList.toggle('open')">☰</button>
                <h1>@yield('judul', 'Beranda')</h1>
            </div>
            <div class="row no-print">@yield('aksi')</div>
        </header>
        <main class="content">
            @if(session('sukses'))<div class="alert ok">{{ session('sukses') }}</div>@endif
            @if(session('galat'))<div class="alert bad">{{ session('galat') }}</div>@endif
            @if($errors->any() && ! $errors->has('__none'))
                <div class="alert bad">Ada isian yang perlu diperbaiki — lihat pesan di bawah masing-masing kolom.</div>
            @endif
            @yield('isi')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
