@extends('layouts.app')
@section('judul', $bast->exists ? 'Ubah BAST SM → PPK' : 'Buat BAST SM → PPK')
@section('isi')
<form class="card" method="post" action="{{ $bast->exists ? route('bast-sm-ppk.update', $bast) : route('bast-sm-ppk.store') }}" style="max-width:820px">
    @csrf @if($bast->exists) @method('put') @endif
    <div class="field">
        <label>Ambil uraian dari kegiatan</label>
        <select id="pilih-kegiatan" onchange="if(this.value){document.getElementById('uraian_tugas').value=this.value}">
            <option value="">— ketik manual atau pilih —</option>
            @foreach($kegiatan as $k)<option value="{{ $k->uraian_tugas }}">{{ $k->kode }} — {{ $k->uraian_tugas }}</option>@endforeach
        </select>
    </div>
    <x-field name="uraian_tugas" label="Uraian pekerjaan" :value="$bast->uraian_tugas" required />
    <div class="grid c3">
        <x-select name="bulan" label="Bulan" required>@foreach(\App\Support\Indonesia::BULAN as $b)<option @selected(old('bulan', $bast->bulan) === $b)>{{ $b }}</option>@endforeach</x-select>
        <x-field name="tahun" label="Tahun" type="number" :value="$bast->tahun" required />
        <x-field name="total_honor" label="Total honor (Rp)" type="number" min="0" :value="$bast->exists ? $bast->total_honor : null" hint="Kosong = dijumlah dari SPK kegiatan & bulan yang sama." />
        <x-select name="ketua_tim_id" label="Ketua tim (SM)" required kosong="— pilih —">@foreach($pegawai as $g)<option value="{{ $g->id }}" @selected(old('ketua_tim_id', $bast->ketua_tim_id) == $g->id)>{{ $g->nama }}</option>@endforeach</x-select>
        <x-select name="ppk_id" label="PPK" required kosong="— pilih —">@foreach($pegawai as $g)<option value="{{ $g->id }}" @selected(old('ppk_id', $bast->ppk_id ?? $ppkDefault?->id) == $g->id)>{{ $g->nama }}</option>@endforeach</x-select>
        <span></span>
        <x-field name="no_bast" label="Nomor BAST" :value="$bast->no_bast ?: ($usulan['bast'] ?? '')" required />
        <x-field name="tanggal_bast" label="Tanggal BAST" type="date" :value="$bast->tanggal_bast?->format('Y-m-d')" required />
    </div>
    <div class="row"><button class="btn pri">Simpan</button><a class="btn" href="{{ route('bast-sm-ppk.index') }}">Batal</a></div>
</form>
@endsection
