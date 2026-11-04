@extends('layouts.app')
@section('judul', $item->exists ? 'Ubah pegawai' : 'Tambah pegawai')
@section('isi')
<form class="card" method="post" action="{{ $item->exists ? route('pegawai.update', $item) : route('pegawai.store') }}" style="max-width:720px">
    @csrf @if($item->exists) @method('put') @endif
    <x-field name="nama" label="Nama (beserta gelar)" :value="$item->nama" required />
    <div class="grid c2">
        <x-field name="nip" label="NIP" :value="$item->nip" />
        <x-field name="jabatan" label="Jabatan" :value="$item->jabatan" />
        <x-field name="pangkat" label="Pangkat" :value="$item->pangkat" />
        <x-field name="golongan" label="Golongan" :value="$item->golongan" hint="Contoh: (III/c)" />
    </div>
    <div class="field"><label style="font-weight:400"><input type="checkbox" name="is_ppk" value="1" @checked(old('is_ppk', $item->is_ppk))> Pejabat Pembuat Komitmen (PPK) — dipilih otomatis saat membuat SPK</label></div>
    <div class="row"><button class="btn pri">Simpan</button><a class="btn" href="{{ route('pegawai.index') }}">Batal</a></div>
</form>
@endsection
