@extends('layouts.guest')
@section('judul', 'Masuk')
@section('isi')
<div class="login-wrap">
    <form class="login-box" method="post" action="{{ route('login.proses') }}">
        @csrf
        <h1>{{ config('app.name') }}</h1>
        <p class="muted" style="margin-top:0">Pembuat dokumen SPK &amp; BAST mitra statistik<br>{{ \App\Models\Parameter::nilai('NAMA_KANTOR', '') }}</p>
        <x-field name="username" label="Username" required autofocus autocomplete="username" />
        <x-field name="password" label="Password" type="password" required autocomplete="current-password" />
        <div class="field"><label style="font-weight:400"><input type="checkbox" name="ingat" value="1"> Ingat saya di perangkat ini</label></div>
        <button class="btn pri" style="width:100%;justify-content:center" type="submit">Masuk</button>
    </form>
</div>
@endsection
