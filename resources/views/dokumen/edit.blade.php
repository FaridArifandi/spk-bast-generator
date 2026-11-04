@extends('layouts.app')
@section('judul', 'Ubah SPK')
@section('isi')
<form method="post" action="{{ route('dokumen.update', $dokumen) }}" style="max-width:900px">
    @csrf @method('put')
    <div class="alert warn">Petugas: <b>{{ $dokumen->petugas_nama }}</b> ({{ strtoupper($dokumen->peran->value) }}). Mengubah SPK yang sudah diputuskan PPK akan mengembalikannya ke “Menunggu verifikasi”.</div>
    <div class="card">
        <div class="grid c2">
            <x-field name="no_spk" label="Nomor SPK" :value="$dokumen->no_spk" required />
            <x-field name="tanggal_spk" label="Tanggal SPK" type="date" :value="$dokumen->tanggal_spk?->format('Y-m-d')" required />
            <x-select name="ppk_id" label="PPK" required>@foreach($pegawai as $g)<option value="{{ $g->id }}" @selected(old('ppk_id', $dokumen->ppk_id) == $g->id)>{{ $g->nama }}</option>@endforeach</x-select>
            <x-select name="ketua_tim_id" label="Ketua tim (SM)" kosong="—">@foreach($pegawai as $g)<option value="{{ $g->id }}" @selected(old('ketua_tim_id', $dokumen->ketua_tim_id) == $g->id)>{{ $g->nama }}</option>@endforeach</x-select>
            <x-field name="nomor_kepka" label="Nomor SK/Kepka" :value="$dokumen->nomor_kepka" />
            <x-field name="tanggal_kepka" label="Tanggal SK/Kepka" type="date" :value="$dokumen->tanggal_kepka?->format('Y-m-d')" />
            <x-field name="tanggal_mulai" label="Tanggal mulai" type="date" :value="$dokumen->tanggal_mulai?->format('Y-m-d')" />
            <x-field name="tanggal_selesai" label="Tanggal selesai" type="date" :value="$dokumen->tanggal_selesai?->format('Y-m-d')" />
            <x-field name="batas_penyerahan" label="Batas penyerahan" type="date" :value="$dokumen->batas_penyerahan?->format('Y-m-d')" />
        </div>
        <x-field name="judul_pekerjaan" label="Judul pekerjaan" :value="$dokumen->judul_pekerjaan" required />
    </div>
    <div class="card flush">
        <div class="head"><h2 style="margin:0">Rincian pekerjaan</h2></div>
        <div class="scroll"><table class="t">
            <thead><tr><th>Uraian</th><th style="width:100px">Volume</th><th style="width:150px">Harga satuan</th><th class="num">Nilai</th></tr></thead>
            <tbody>
            @foreach($dokumen->details as $d)
                <tr>
                    <td>{{ $d->uraian_tugas }}</td>
                    <td><input type="number" min="1" name="details[{{ $d->id }}][volume]" value="{{ old("details.$d->id.volume", $d->volume) }}"></td>
                    <td><input type="number" min="0" name="details[{{ $d->id }}][harga_satuan]" value="{{ old("details.$d->id.harga_satuan", $d->harga_satuan) }}"></td>
                    <td class="num">Rp {{ \App\Support\Indonesia::rupiah($d->nilai_perjanjian) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </div>
    <div class="row"><button class="btn pri">Simpan perubahan</button><a class="btn" href="{{ route('dokumen.show', $dokumen) }}">Batal</a></div>
</form>
@endsection
