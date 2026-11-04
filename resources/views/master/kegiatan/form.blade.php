@extends('layouts.app')
@section('judul', $item->exists ? 'Ubah kegiatan' : 'Tambah kegiatan')
@section('isi')
<form class="card" method="post" action="{{ $item->exists ? route('kegiatan.update', $item) : route('kegiatan.store') }}" style="max-width:760px">
    @csrf @if($item->exists) @method('put') @endif
    <div class="grid c2">
        <x-field name="kode" label="Kode kegiatan" :value="$item->kode" required hint="Contoh: KEG001" />
        <x-field name="satuan" label="Satuan" :value="$item->satuan" required />
    </div>
    <x-field name="uraian_tugas" label="Uraian tugas" :value="$item->uraian_tugas" required />
    <div class="grid c2">
        <x-field name="harga_satuan" label="Harga satuan (Rp)" type="number" min="0" :value="$item->harga_satuan" />
        <x-field name="beban_anggaran" label="Beban anggaran (MAK)" :value="$item->beban_anggaran" />
        <x-field name="buku_pedoman" label="Buku pedoman" :value="$item->buku_pedoman" hint="Tampil di Pasal 2 SPK; kosong = “Ada”" />
        <x-select name="ketua_tim_id" label="Ketua tim (penandatangan BAST)" kosong="— pilih —">
            @foreach($pegawai as $p)<option value="{{ $p->id }}" @selected(old('ketua_tim_id', $item->ketua_tim_id) == $p->id)>{{ $p->nama }}</option>@endforeach
        </x-select>
    </div>
    <div class="row"><button class="btn pri">Simpan</button><a class="btn" href="{{ route('kegiatan.index') }}">Batal</a></div>
</form>
@endsection
