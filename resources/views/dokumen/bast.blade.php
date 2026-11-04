@extends('layouts.app')
@section('judul', 'Nomor & tanggal BAST')
@section('isi')
<form class="card" method="post" action="{{ route('dokumen.bast.simpan', $dokumen) }}" style="max-width:760px">
    @csrf @method('put')
    <p class="muted">Petugas <b>{{ $dokumen->petugas_nama }}</b> ({{ strtoupper($dokumen->peran->value) }}) — {{ $dokumen->periode_label }}.<br>
        Nomor berikutnya yang tersedia untuk tahun {{ $dokumen->tahun }}: <b>{{ $usulan['bast'] }}</b></p>
    @foreach($baris as $b)
        <h3>{{ $b['judul'] }}</h3>
        <div class="grid c2">
            <x-field :name="$b['no_field']" label="Nomor BAST" :value="$b['no']" required />
            <x-field :name="$b['tgl_field']" label="Tanggal BAST" type="date" :value="$b['tanggal']" required />
        </div>
    @endforeach
    <div class="row"><button class="btn pri">Simpan</button><a class="btn" href="{{ route('dokumen.show', $dokumen) }}">Batal</a></div>
</form>
@endsection
