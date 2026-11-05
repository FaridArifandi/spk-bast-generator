@extends('layouts.app')
@section('judul', 'Parameter sistem')
@section('isi')
<form class="card" method="post" action="{{ route('admin.parameter.update') }}" style="max-width:720px">
    @csrf @method('put')
    @foreach($daftar as $kode => [$label, $tipe])
        <x-field :name="$kode" :label="$label" :type="$tipe" :value="$nilai[$kode] ?? null" />
    @endforeach
    <button class="btn pri">Simpan</button>
</form>

<div class="card" style="max-width:720px;margin-top:16px">
    <h3 style="margin-top:0">Ekspor ke Google Sheets</h3>
    <p class="muted">Database tetap sumber utama. Isi spreadsheet ditulis ulang otomatis tiap 10 menit bila ada perubahan (searah; jangan mengedit langsung di Sheets).</p>
    @if(! $sheetsSiap)
        <p>Belum aktif: isi <code>SHEETS_URL</code> dan <code>SHEETS_TOKEN</code> di <code>.env</code>.</p>
    @else
        @if($sheetsStatus)
            <p>Terakhir: <strong>{{ $sheetsStatus['status'] }}</strong> · {{ $sheetsStatus['waktu'] }}<br><span class="muted">{{ $sheetsStatus['pesan'] }}</span></p>
        @endif
        <form method="post" action="{{ route('admin.sheets.sinkron') }}">
            @csrf
            <button class="btn">Sinkronkan sekarang</button>
        </form>
    @endif
</div>
@endsection
