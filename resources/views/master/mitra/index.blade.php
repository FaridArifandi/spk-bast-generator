@extends('layouts.app')
@section('judul', 'Mitra')
@section('aksi')@if(auth()->user()->isAdmin())<a class="btn pri" href="{{ route('mitra.create') }}">+ Tambah mitra</a>@endif @endsection
@section('isi')
<form class="filters" method="get">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / ID / NIK / asal">
    <select name="kategori" onchange="this.form.submit()"><option value="">Semua kategori</option>
        @foreach($kategori as $k)<option @selected(request('kategori') === $k)>{{ $k }}</option>@endforeach</select>
    <button class="btn">Cari</button>
</form>
<div class="card flush"><div class="scroll"><table class="t">
    <thead><tr><th>ID</th><th>Nama</th><th>NIK</th><th>Posisi</th><th>Asal</th><th>Kategori</th><th></th></tr></thead>
    <tbody>
    @forelse($items as $m)
        <tr>
            <td>{{ $m->id_mitra }}</td>
            <td>{{ $m->nama }} @unless($m->aktif)<span class="badge gray">nonaktif</span>@endunless</td>
            <td>{{ $m->nik }}</td><td>{{ $m->posisi }}</td><td>{{ $m->asal }}</td><td>{{ $m->kategori }}</td>
            <td class="num">@if(auth()->user()->isAdmin())
                <a class="btn sm" href="{{ route('mitra.edit', $m) }}">Ubah</a>
                <form method="post" action="{{ route('mitra.destroy', $m) }}" style="display:inline" onsubmit="return confirm('Hapus mitra ini?')">@csrf @method('delete')<button class="btn sm bad">Hapus</button></form>
            @endif</td>
        </tr>
    @empty<tr><td colspan="7" class="muted">Belum ada data.</td></tr>@endforelse
    </tbody>
</table></div>{{ $items->links() }}</div>
@endsection
