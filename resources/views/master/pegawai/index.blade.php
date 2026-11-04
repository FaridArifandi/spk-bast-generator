@extends('layouts.app')
@section('judul', 'Pegawai')
@section('aksi')@if(auth()->user()->isAdmin())<a class="btn pri" href="{{ route('pegawai.create') }}">+ Tambah pegawai</a>@endif @endsection
@section('isi')
<form class="filters" method="get"><input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / NIP / jabatan"><button class="btn">Cari</button></form>
<div class="card flush"><div class="scroll"><table class="t">
    <thead><tr><th>Nama</th><th>NIP</th><th>Jabatan</th><th>Pangkat / Gol.</th><th></th></tr></thead>
    <tbody>
    @forelse($items as $p)
        <tr>
            <td>{{ $p->nama }} @if($p->is_ppk)<span class="badge info">PPK</span>@endif</td>
            <td>{{ $p->nip }}</td><td>{{ $p->jabatan }}</td><td>{{ $p->pangkat_golongan }}</td>
            <td class="num">@if(auth()->user()->isAdmin())
                <a class="btn sm" href="{{ route('pegawai.edit', $p) }}">Ubah</a>
                <form method="post" action="{{ route('pegawai.destroy', $p) }}" style="display:inline" onsubmit="return confirm('Hapus pegawai ini?')">@csrf @method('delete')<button class="btn sm bad">Hapus</button></form>
            @endif</td>
        </tr>
    @empty<tr><td colspan="5" class="muted">Belum ada data.</td></tr>@endforelse
    </tbody>
</table></div>{{ $items->links() }}</div>
@endsection
