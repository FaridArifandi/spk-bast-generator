@extends('layouts.guest')
@section('judul', 'Sesi kedaluwarsa')
@section('isi')
<div class="login-wrap"><div class="login-box" style="text-align:center">
    <h1 style="font-size:3rem;color:var(--brand)">419</h1>
    <p>Sesi kedaluwarsa.@if(isset($exception) && $exception->getMessage() && ! in_array(419, [404, 419, 500]))<br><span class="muted">{{ $exception->getMessage() }}</span>@endif</p>
    <a class="btn pri" href="{{ url('/') }}">Kembali ke beranda</a>
</div></div>
@endsection
