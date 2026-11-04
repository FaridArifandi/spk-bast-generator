@extends('layouts.app')
@section('judul', 'BAST Ketua Tim → PPK')
@section('aksi')@if(auth()->user()->bisaKelolaDokumen())<a class="btn pri" href="{{ route('bast-sm-ppk.create') }}">+ Buat BAST SM → PPK</a>@endif @endsection
@section('isi')
<form class="filters" method="get">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari uraian / nomor">
    <select name="tahun"><option value="">Semua tahun</option>@foreach($daftarTahun as $t)<option @selected(request('tahun') == $t)>{{ $t }}</option>@endforeach</select>
    <select name="status"><option value="">Semua status</option>@foreach($status as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>@endforeach</select>
    <button class="btn">Terapkan</button>
</form>
<div class="card flush"><div class="scroll"><table class="t">
    <thead><tr><th>Nomor</th><th>Uraian</th><th>Periode</th><th>Ketua tim</th><th class="num">Honor</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($items as $b)
        <tr>
            <td>{{ $b->no_bast }}</td><td>{{ \Illuminate\Support\Str::limit($b->uraian_tugas, 70) }}</td>
            <td>{{ $b->bulan }} {{ $b->tahun }}</td><td>{{ $b->ketuaTim?->nama }}</td>
            <td class="num">Rp {{ \App\Support\Indonesia::rupiah($b->total_honor) }}</td>
            <td><x-status :s="$b->status_verifikasi" /></td>
            <td class="num"><a class="btn sm" href="{{ route('bast-sm-ppk.show', $b) }}">Buka</a></td>
        </tr>
    @empty<tr><td colspan="7" class="muted">Belum ada data.</td></tr>@endforelse
    </tbody>
</table></div>{{ $items->links() }}</div>
@endsection
