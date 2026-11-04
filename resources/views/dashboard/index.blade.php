@extends('layouts.app')
@section('judul', 'Dashboard')
@section('isi')
<form class="filters" method="get">
    <select name="tahun" onchange="this.form.submit()">
        @foreach($daftarTahun as $t)<option value="{{ $t }}" @selected($t == $tahun)>{{ $t }}</option>@endforeach
    </select>
    <select name="bulan" onchange="this.form.submit()">
        <option value="semua" @selected(! $bulan)>Semua bulan</option>
        @foreach(\App\Support\Indonesia::BULAN as $b)<option @selected($b === $bulan)>{{ $b }}</option>@endforeach
    </select>
</form>

<div class="grid c4" style="margin-bottom:18px">
    <div class="stat"><b>{{ $stat['dokumen'] }}</b><span>SPK tahun {{ $tahun }}</span></div>
    <div class="stat"><b>{{ $stat['menunggu'] }}</b><span>Menunggu verifikasi PPK</span></div>
    <div class="stat"><b>{{ $stat['disetujui'] }}</b><span>SPK disetujui</span></div>
    <div class="stat"><b>{{ $stat['penugasan_belum'] }}</b><span>Penugasan belum dibuatkan SPK</span></div>
</div>

<div class="grid c2" style="align-items:start">
    <div class="card flush">
        <div class="head" style="align-items:center;justify-content:space-between">
            <div>
                <h2 style="margin:0">Rekap honor mitra — {{ $bulan ?? 'semua bulan' }} {{ $tahun }}</h2>
                @if($batas)<span class="small muted">Batas: Rp {{ \App\Support\Indonesia::rupiah($batas) }}</span>@endif
            </div>
            <a class="btn sm" href="{{ route('dashboard.export-honor', ['tahun' => $tahun, 'bulan' => $bulan ?: 'semua']) }}">⬇ Unduh Excel</a>
        </div>
        <div class="scroll"><table class="t">
            <thead><tr><th>Mitra</th><th class="num">SPK</th><th class="num">Total honor</th></tr></thead>
            <tbody>
            @forelse($rekap as $r)
                <tr>
                    <td>{{ $r->mitra->nama }} <span class="muted small">{{ $r->mitra->id_mitra }}</span></td>
                    <td class="num">{{ $r->jumlah }}</td>
                    <td class="num">Rp {{ \App\Support\Indonesia::rupiah($r->total) }}
                        @if($r->melebihi)<span class="badge danger" title="Melebihi batas honor">Melebihi batas</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">Belum ada SPK pada periode ini.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="card flush">
        <div class="head"><h2 style="margin:0">SPK terbaru</h2><a class="small" href="{{ route('dokumen.index') }}">Lihat semua</a></div>
        <div class="scroll"><table class="t">
            <thead><tr><th>Petugas</th><th>Nomor SPK</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($terbaru as $d)
                <tr>
                    <td><a href="{{ route('dokumen.show', $d) }}">{{ $d->petugas_nama }}</a><div class="muted small">{{ $d->periode_label }}</div></td>
                    <td>{{ $d->no_spk }}</td>
                    <td><x-status :s="$d->status_verifikasi" /></td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">Belum ada dokumen.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endsection
