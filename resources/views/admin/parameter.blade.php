@extends('layouts.app')
@section('judul', 'Parameter sistem')
@section('isi')
<form class="card" method="post" action="{{ route('admin.parameter.update') }}" style="max-width:720px">
    @csrf @method('put')
    @foreach($daftar as $kode => [$label, $tipe])
        <x-field :name="$kode" :label="$label" :type="$tipe" :value="$nilai[$kode] ?? null" />
    @endforeach
    <button class="btn pri">Simpan</button>
</form>
@endsection
