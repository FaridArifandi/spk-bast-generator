@extends('layouts.guest')
@section('judul', 'Terjadi kesalahan')
@section('isi')
<div class="login-wrap"><div class="login-box" style="text-align:center">
    <h1 style="font-size:3rem;color:var(--brand)">500</h1>
    <p>Terjadi kesalahan.@if(isset($exception) && $exception->getMessage() && ! in_array(500, [404, 419, 500]))<br><span class="muted">{{ $exception->getMessage() }}</span>@endif</p>
    <a class="btn pri" href="{{ url('/') }}">Kembali ke beranda</a>
</div></div>
@endsection
