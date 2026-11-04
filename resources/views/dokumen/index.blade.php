@extends('layouts.app')
@section('judul', 'SPK & BAST')
@section('aksi')
    <a class="btn" href="{{ route('dokumen.unduh-zip', request()->only(['tahun', 'bulan'])) }}">⬇ Unduh Disetujui (.zip)</a>
    @if(auth()->user()->bisaKelolaDokumen())<a class="btn pri" href="{{ route('dokumen.create') }}">+ Buat SPK</a>@endif
@endsection
@section('isi')
<form class="filters" method="get">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / nomor SPK / judul">
    <select name="tahun"><option value="">Semua tahun</option>@foreach($daftarTahun as $t)<option @selected(request('tahun') == $t)>{{ $t }}</option>@endforeach</select>
    <select name="bulan"><option value="">Semua bulan</option>@foreach(\App\Support\Indonesia::BULAN as $b)<option @selected(request('bulan') === $b)>{{ $b }}</option>@endforeach</select>
    <select name="peran"><option value="">Semua peran</option>@foreach($peran as $v => $l)<option value="{{ $v }}" @selected(request('peran') === $v)>{{ strtoupper($v) }}</option>@endforeach</select>
    <select name="status"><option value="">Semua status</option>@foreach($status as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>@endforeach</select>
    <button class="btn">Terapkan</button>
</form>
<div class="card flush"><div class="scroll"><table class="t">
    <thead><tr><th>Petugas</th><th>Peran</th><th>Nomor SPK</th><th>Pekerjaan</th><th class="num">Honor</th><th>BAST</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($items as $d)
        <tr>
            <td><a href="{{ route('dokumen.show', $d) }}">{{ $d->petugas_nama }}</a><div class="muted small">{{ $d->periode_label }}</div></td>
            <td>{{ strtoupper($d->peran->value) }}</td>
            <td>{{ $d->no_spk }}<div class="muted small">{{ $d->tanggal_spk?->translatedFormat('d M Y') }}</div></td>
            <td>{{ \Illuminate\Support\Str::limit($d->judul_pekerjaan, 70) }}</td>
            <td class="num">Rp {{ \App\Support\Indonesia::rupiah($d->total_honor) }}</td>
            <td>@foreach($d->jenisBast() as $j)<span class="badge {{ $d->bastTerisi($j) ? 'success' : 'gray' }}" title="{{ \App\Services\DokumenRenderer::JENIS[$j] }}">{{ strtoupper(str_replace('-', '→', $j)) }}</span> @endforeach</td>
            <td><x-status :s="$d->status_verifikasi" /></td>
            <td class="num"><a class="btn sm" href="{{ route('dokumen.show', $d) }}">Buka</a></td>
        </tr>
    @empty<tr><td colspan="8" class="muted">Belum ada dokumen.</td></tr>@endforelse
    </tbody>
</table></div>{{ $items->links() }}</div>
@endsection
