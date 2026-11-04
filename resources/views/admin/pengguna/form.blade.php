@extends('layouts.app')
@section('judul', $item->exists ? 'Ubah pengguna' : 'Tambah pengguna')
@section('isi')
<form class="card" method="post" action="{{ $item->exists ? route('admin.pengguna.update', $item) : route('admin.pengguna.store') }}" style="max-width:720px">
    @csrf @if($item->exists) @method('put') @endif
    <div class="grid c2">
        <x-field name="nama" label="Nama" :value="$item->nama" required />
        <x-field name="username" label="Username" :value="$item->username" required hint="Huruf kecil, angka, titik, strip." />
        <x-select name="role" label="Peran" required>
            @foreach($roles as $v => $l)<option value="{{ $v }}" @selected(old('role', $item->role) === $v)>{{ $l }}</option>@endforeach
        </x-select>
        <x-field name="sub_role" label="Sub-peran (opsional)" :value="$item->sub_role" hint="Mis. nama tim untuk KATIM" />
        <x-select name="pegawai_id" label="Terkait pegawai" kosong="— tidak ada —">
            @foreach($pegawai as $p)<option value="{{ $p->id }}" @selected(old('pegawai_id', $item->pegawai_id) == $p->id)>{{ $p->nama }}</option>@endforeach
        </x-select>
        <x-field name="password" :label="$item->exists ? 'Password baru (kosongkan bila tidak diganti)' : 'Password'" type="password" :required="! $item->exists" autocomplete="new-password" hint="Minimal 8 karakter." />
    </div>
    <div class="field"><label style="font-weight:400"><input type="checkbox" name="aktif" value="1" @checked(old('aktif', $item->aktif))> Akun aktif</label></div>
    <div class="row"><button class="btn pri">Simpan</button><a class="btn" href="{{ route('admin.pengguna.index') }}">Batal</a></div>
</form>
@endsection
