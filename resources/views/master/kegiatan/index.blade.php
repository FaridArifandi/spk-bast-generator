@extends('layouts.app')
@section('judul', 'Kegiatan')
@section('aksi')@if(auth()->user()->bisaKelolaDokumen())<a class="btn pri" href="{{ route('kegiatan.create') }}">+ Tambah kegiatan</a>@endif @endsection
@section('isi')
<form class="filters" method="get"><input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode / uraian"><button class="btn">Cari</button></form>
<div class="card flush"><div class="scroll"><table class="t">
    <thead><tr><th>Kode</th><th>Uraian tugas</th><th>Satuan</th><th class="num">Harga satuan</th><th>Ketua tim</th><th></th></tr></thead>
    <tbody>
    @forelse($items as $k)
        <tr>
            <td>{{ $k->kode }}</td><td>{{ $k->uraian_tugas }}</td><td>{{ $k->satuan }}</td>
            <td class="num">{{ $k->harga_satuan !== null ? 'Rp '.\App\Support\Indonesia::rupiah($k->harga_satuan) : '—' }}</td>
            <td>{{ $k->ketuaTim?->nama }}</td>
            <td class="num">@if(auth()->user()->bisaKelolaDokumen())
                <a class="btn sm" href="{{ route('kegiatan.edit', $k) }}">Ubah</a>
                <form method="post" action="{{ route('kegiatan.destroy', $k) }}" style="display:inline" onsubmit="return confirm('Hapus kegiatan ini?')">@csrf @method('delete')<button class="btn sm bad">Hapus</button></form>
            @endif</td>
        </tr>
    @empty<tr><td colspan="6" class="muted">Belum ada data.</td></tr>@endforelse
    </tbody>
</table></div>{{ $items->links() }}</div>
@endsection
