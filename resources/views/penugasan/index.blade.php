@extends('layouts.app')
@section('judul', 'Penugasan Petugas')
@section('aksi')<a class="btn pri" href="{{ route('penugasan.create') }}">+ Tugaskan petugas</a>@endsection
@section('isi')
<form class="filters" method="get">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama petugas">
    <select name="kegiatan_id" onchange="this.form.submit()"><option value="">Semua kegiatan</option>
        @foreach($kegiatan as $k)<option value="{{ $k->id }}" @selected(request('kegiatan_id') == $k->id)>{{ $k->kode }} — {{ \Illuminate\Support\Str::limit($k->uraian_tugas, 50) }}</option>@endforeach</select>
    <select name="status" onchange="this.form.submit()"><option value="">Semua status</option>
        <option value="belum" @selected(request('status') === 'belum')>Belum dibuatkan SPK</option>
        <option value="sudah" @selected(request('status') === 'sudah')>Sudah dibuatkan SPK</option></select>
    <button class="btn">Cari</button>
</form>
<div class="card flush"><div class="scroll"><table class="t">
    <thead><tr><th>Petugas</th><th>Peran</th><th>Kegiatan</th><th>Periode</th><th class="num">Volume</th><th class="num">Honor</th><th>SPK</th><th></th></tr></thead>
    <tbody>
    @forelse($items as $p)
        <tr>
            <td>{{ $p->petugas_nama }}<div class="muted small">PML: {{ $p->pml_label ?: '—' }}</div></td>
            <td>{{ strtoupper($p->peran) }}</td>
            <td>{{ $p->kegiatan->uraian_tugas }}</td>
            <td>{{ $p->periode_label }}<div class="muted small">{{ \App\Support\Indonesia::jangkaWaktu($p->tanggal_mulai, $p->tanggal_selesai) }}</div></td>
            <td class="num">{{ $p->volume }} {{ $p->satuan }}</td>
            <td class="num">Rp {{ \App\Support\Indonesia::rupiah($p->total_honor) }}</td>
            <td>@if($p->dokumen_id)<a href="{{ route('dokumen.show', $p->dokumen_id) }}">{{ $p->dokumen?->no_spk ?? 'lihat' }}</a>@else<span class="badge warning">Belum</span>@endif</td>
            <td class="num">@unless($p->dokumen_id)
                <div class="row" style="justify-content:flex-end;gap:4px">
                    <a class="btn sm" href="{{ route('penugasan.edit', $p) }}">Ubah</a>
                    <form method="post" action="{{ route('penugasan.destroy', $p) }}" onsubmit="return confirm('Hapus penugasan ini?')">@csrf @method('delete')<button class="btn sm bad">Hapus</button></form>
                </div>
            @endunless</td>
        </tr>
    @empty<tr><td colspan="8" class="muted">Belum ada penugasan.</td></tr>@endforelse
    </tbody>
</table></div>{{ $items->links() }}</div>
@endsection
