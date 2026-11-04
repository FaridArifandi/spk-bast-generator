@extends('layouts.app')
@section('judul', 'Buat SPK')
@section('isi')
<form class="card" method="get" action="{{ route('dokumen.create') }}">
    <h2>1. Pilih petugas</h2>
    <div class="row">
        <select name="petugas" style="max-width:420px" onchange="this.form.submit()">
            <option value="">— petugas yang punya penugasan belum ber-SPK —</option>
            @foreach($petugasList as $p)<option value="{{ $p->ref }}" @selected($ref === $p->ref)>{{ $p->nama }} ({{ $p->jumlah }} penugasan)</option>@endforeach
        </select>
        <noscript><button class="btn">Pilih</button></noscript>
    </div>
    @if($petugasList->isEmpty())<p class="muted">Belum ada penugasan yang menunggu SPK. Buat dulu di menu <a href="{{ route('penugasan.create') }}">Penugasan Petugas</a>.</p>@endif
</form>

@if($ref && $penugasan->isNotEmpty())
<form method="post" action="{{ route('dokumen.store') }}">
    @csrf
    <div class="card flush">
        <div class="head"><h2 style="margin:0">2. Pilih penugasan yang dimasukkan ke SPK</h2><span class="muted small">Penugasan berperan sama dapat digabung dalam satu SPK.</span></div>
        @error('penugasan_ids')<div class="err" style="padding:8px 18px">{{ $message }}</div>@enderror
        <div class="scroll"><table class="t">
            <thead><tr><th></th><th>Kegiatan</th><th>Peran</th><th>Periode</th><th class="num">Volume</th><th class="num">Honor</th></tr></thead>
            <tbody>
            @foreach($penugasan as $p)
                <tr>
                    <td><input type="checkbox" name="penugasan_ids[]" value="{{ $p->id }}" @checked(in_array($p->id, (array) old('penugasan_ids', $penugasan->count() === 1 ? [$p->id] : [])))></td>
                    <td>{{ $p->kegiatan->uraian_tugas }}</td><td>{{ strtoupper($p->peran) }}</td>
                    <td>{{ $p->periode_label }}</td><td class="num">{{ $p->volume }} {{ $p->satuan }}</td>
                    <td class="num">Rp {{ \App\Support\Indonesia::rupiah($p->total_honor) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <h2>3. Nomor &amp; penandatangan</h2>
        <div class="grid c2">
            <x-field name="no_spk" label="Nomor SPK" :value="$usulan['spk']" required hint="Usulan otomatis dari nomor terakhir tahun ini; boleh diubah." />
            <x-field name="tanggal_spk" label="Tanggal SPK" type="date" :value="now()->toDateString()" required />
            <x-select name="ppk_id" label="PPK" required kosong="— pilih PPK —">
                @foreach($pegawai as $g)<option value="{{ $g->id }}" @selected(old('ppk_id', $ppkDefault?->id) == $g->id)>{{ $g->nama }}</option>@endforeach
            </x-select>
            <x-select name="ketua_tim_id" label="Ketua tim (SM)" kosong="— ikut data kegiatan —" hint="Penandatangan BAST. Kosong = ketua tim dari kegiatan.">
                @foreach($pegawai as $g)<option value="{{ $g->id }}" @selected(old('ketua_tim_id') == $g->id)>{{ $g->nama }}</option>@endforeach
            </x-select>
        </div>
        <x-field name="judul_pekerjaan" label="Judul pekerjaan (opsional)" hint="Kosong = diambil otomatis dari nama kegiatan." :value="old('judul_pekerjaan')" />
        <div class="row"><button class="btn pri">Buat SPK</button><a class="btn" href="{{ route('dokumen.index') }}">Batal</a></div>
    </div>
</form>
@endif
@endsection
