<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0c1f3f">
    <title>@yield('judul', 'Beranda') — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dokumen.css') }}">
    @stack('head')
</head>
<body>
@php($u = auth()->user())
{{-- Ikon (sprite SVG) --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <symbol id="i-dash" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></symbol>
    <symbol id="i-assign" viewBox="0 0 24 24"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/></symbol>
    <symbol id="i-fileplus" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5z"/><path d="M14 2v6h6M12 18v-6M9 15h6"/></symbol>
    <symbol id="i-files" viewBox="0 0 24 24"><path d="M15 2H8a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V6z"/><path d="M15 2v4h4"/><path d="M4 7v13a2 2 0 0 0 2 2h10"/></symbol>
    <symbol id="i-filecheck" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/></symbol>
    <symbol id="i-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
    <symbol id="i-briefcase" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></symbol>
    <symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></symbol>
    <symbol id="i-user" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></symbol>
    <symbol id="i-sliders" viewBox="0 0 24 24"><path d="M21 4h-7M10 4H3M21 12h-9M8 12H3M21 20h-5M12 20H3M14 2v4M8 10v4M16 18v4"/></symbol>
    <symbol id="i-logout" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
</svg>
<div class="app">
    <aside class="side" id="side">
        <div class="brand">
            <img class="logo" src="{{ asset('favicon.svg') }}" alt="">
            <div>{{ config('app.name') }}<small>{{ \App\Models\Parameter::nilai('NAMA_KANTOR', 'BPS') }}</small></div>
        </div>
        <nav>
            <a href="{{ route('dashboard') }}" @class(['on' => request()->routeIs('dashboard')])><svg class="ico"><use href="#i-dash"/></svg>Dashboard</a>

            @if($u->hasRole('ADMIN','PPK'))
                <a href="{{ route('verifikasi.index') }}" @class(['on' => request()->routeIs('verifikasi.*')])><svg class="ico"><use href="#i-shield"/></svg>Verifikasi PPK</a>
            @endif

            <div class="grp">Dokumen</div>
            @if($u->bisaKelolaDokumen())
                <a href="{{ route('penugasan.index') }}" @class(['on' => request()->routeIs('penugasan.*')])><svg class="ico"><use href="#i-assign"/></svg>Penugasan Petugas</a>
                <a href="{{ route('dokumen.create') }}" @class(['on' => request()->routeIs('dokumen.create')])><svg class="ico"><use href="#i-fileplus"/></svg>Buat SPK</a>
            @endif
            <a href="{{ route('dokumen.index') }}" @class(['on' => request()->routeIs('dokumen.index','dokumen.show','dokumen.edit','dokumen.bast')])><svg class="ico"><use href="#i-files"/></svg>SPK &amp; BAST</a>
            <a href="{{ route('bast-sm-ppk.index') }}" @class(['on' => request()->routeIs('bast-sm-ppk.*')])><svg class="ico"><use href="#i-filecheck"/></svg>BAST SM → PPK</a>

            <div class="grp">Data Master</div>
            <a href="{{ route('mitra.index') }}" @class(['on' => request()->routeIs('mitra.*')])><svg class="ico"><use href="#i-users"/></svg>Mitra</a>
            <a href="{{ route('pegawai.index') }}" @class(['on' => request()->routeIs('pegawai.*')])><svg class="ico"><use href="#i-briefcase"/></svg>Pegawai</a>
            <a href="{{ route('kegiatan.index') }}" @class(['on' => request()->routeIs('kegiatan.*')])><svg class="ico"><use href="#i-calendar"/></svg>Kegiatan</a>

            @if($u->isAdmin())
                <div class="grp">Administrasi</div>
                <a href="{{ route('admin.pengguna.index') }}" @class(['on' => request()->routeIs('admin.pengguna.*')])><svg class="ico"><use href="#i-user"/></svg>Pengguna</a>
                <a href="{{ route('admin.parameter') }}" @class(['on' => request()->routeIs('admin.parameter*')])><svg class="ico"><use href="#i-sliders"/></svg>Parameter</a>
            @endif
        </nav>
        <div class="who">
            <div class="me">
                <span class="avatar">{{ mb_strtoupper(mb_substr($u->nama, 0, 1).mb_substr(trim(strstr($u->nama.' ', ' ')), 0, 1)) }}</span>
                <div><b>{{ $u->nama }}</b><span class="role">{{ $u->roleEnum()->label() }}</span></div>
            </div>
            <div class="links">
                <a class="profil" href="{{ route('profil') }}"><svg class="ico" style="width:15px;height:15px"><use href="#i-user"/></svg>Profil</a>
                <form method="post" action="{{ route('logout') }}">@csrf<button type="submit"><svg class="ico" style="width:15px;height:15px"><use href="#i-logout"/></svg>Keluar</button></form>
            </div>
        </div>
    </aside>
    <div class="backdrop" onclick="document.getElementById('side').classList.remove('open')"></div>

    <div class="main">
        <header class="top">
            <div class="row">
                <button class="btn sm menu-btn" type="button" aria-label="Menu" onclick="document.getElementById('side').classList.toggle('open')"><svg class="ico"><use href="#i-menu"/></svg></button>
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
