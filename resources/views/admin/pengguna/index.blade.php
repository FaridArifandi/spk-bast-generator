@extends('layouts.app')
@section('judul', 'Pengguna sistem')
@section('aksi')<a class="btn pri" href="{{ route('admin.pengguna.create') }}">+ Tambah pengguna</a>@endsection
@section('isi')
<form class="filters" method="get"><input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / username"><button class="btn">Cari</button></form>
<div class="card flush"><div class="scroll"><table class="t">
    <thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th>Pegawai terkait</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($items as $u)
        <tr>
            <td>{{ $u->nama }}</td><td>{{ $u->username }}</td>
            <td>{{ $u->roleEnum()->label() }} @if($u->sub_role)<span class="muted small">({{ $u->sub_role }})</span>@endif</td>
            <td>{{ $u->pegawai?->nama }}</td>
            <td><span class="badge {{ $u->aktif ? 'success' : 'gray' }}">{{ $u->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td class="num"><a class="btn sm" href="{{ route('admin.pengguna.edit', $u) }}">Ubah</a>
                @if(! $u->is(auth()->user()))<form method="post" action="{{ route('admin.pengguna.destroy', $u) }}" style="display:inline" onsubmit="return confirm('Hapus pengguna ini?')">@csrf @method('delete')<button class="btn sm bad">Hapus</button></form>@endif</td>
        </tr>
    @endforeach
    </tbody>
</table></div>{{ $items->links() }}</div>
@endsection
