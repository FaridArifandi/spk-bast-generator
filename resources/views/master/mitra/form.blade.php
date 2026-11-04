@extends('layouts.app')
@section('judul', $item->exists ? 'Ubah mitra' : 'Tambah mitra')
@section('isi')
<form class="card" method="post" action="{{ $item->exists ? route('mitra.update', $item) : route('mitra.store') }}" style="max-width:820px">
    @csrf @if($item->exists) @method('put') @endif
    <div class="grid c2">
        <x-field name="id_mitra" label="ID Mitra" :value="$item->id_mitra" required hint="Contoh: M2026001" />
        <x-field name="nama" label="Nama" :value="$item->nama" required />
        <x-field name="nik" label="NIK" :value="$item->nik" />
        <x-field name="sobat_id" label="Sobat ID" :value="$item->sobat_id" />
        <x-field name="kategori" label="Kategori" :value="$item->kategori" hint="Kepka / Tambahan / Khusus Mahasiswa" />
        <x-field name="posisi" label="Posisi" :value="$item->posisi" />
        <x-field name="asal" label="Asal (kecamatan)" :value="$item->asal" />
        <x-field name="email" label="Email" type="email" :value="$item->email" />
        <x-field name="tanggal_lahir" label="Tanggal lahir" type="date" :value="$item->tanggal_lahir?->format('Y-m-d')" />
        <x-select name="jenis_kelamin" label="Jenis kelamin" kosong="—"><option value="Lk" @selected(old('jenis_kelamin', $item->jenis_kelamin) === 'Lk')>Laki-laki</option><option value="Pr" @selected(old('jenis_kelamin', $item->jenis_kelamin) === 'Pr')>Perempuan</option></x-select>
        <x-field name="no_telp" label="No. telepon" :value="$item->no_telp" />
    </div>
    <div class="field"><label>Alamat lengkap</label><textarea name="alamat_detail" rows="2">{{ old('alamat_detail', $item->alamat_detail) }}</textarea></div>
    <div class="field"><label style="font-weight:400"><input type="checkbox" name="aktif" value="1" @checked(old('aktif', $item->aktif ?? true))> Aktif (muncul saat membuat penugasan)</label></div>
    <div class="row"><button class="btn pri">Simpan</button><a class="btn" href="{{ route('mitra.index') }}">Batal</a></div>
</form>
@endsection
